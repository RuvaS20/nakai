<?php
session_start();
require_once '../../db/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['booking_id']) || !isset($data['status'])) {
        throw new Exception("Missing required parameters");
    }

    if (!in_array($data['status'], ['approved', 'rejected'])) {
        throw new Exception("Invalid status");
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Begin transaction
    $conn->beginTransaction();

    // Check if space is still available if approving
    if ($data['status'] === 'approved') {
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM exhibition_bookings
            WHERE space_id = (
                SELECT space_id FROM exhibition_bookings WHERE booking_id = ?
            )
            AND booking_status = 'approved'
            AND (
                (start_date BETWEEN 
                    (SELECT start_date FROM exhibition_bookings WHERE booking_id = ?) 
                    AND 
                    (SELECT end_date FROM exhibition_bookings WHERE booking_id = ?)
                )
                OR 
                (end_date BETWEEN 
                    (SELECT start_date FROM exhibition_bookings WHERE booking_id = ?) 
                    AND 
                    (SELECT end_date FROM exhibition_bookings WHERE booking_id = ?)
                )
            )
        ");
        $stmt->execute([
            $data['booking_id'],
            $data['booking_id'],
            $data['booking_id'],
            $data['booking_id'],
            $data['booking_id']
        ]);
        
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Space is already booked for these dates");
        }
    }

    // Update booking status
    $stmt = $conn->prepare("
        UPDATE exhibition_bookings 
        SET booking_status = ?,
            updated_at = CURRENT_TIMESTAMP 
        WHERE booking_id = ?
    ");
    
    $stmt->execute([$data['status'], $data['booking_id']]);

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}