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
    
    if (!isset($data['booking_id'])) {
        throw new Exception("Booking ID not provided");
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Check if exhibition can be cancelled (not past)
    $stmt = $conn->prepare("
        SELECT end_date 
        FROM exhibition_bookings 
        WHERE booking_id = ? AND booking_status = 'approved'
    ");
    $stmt->execute([$data['booking_id']]);
    $exhibition = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exhibition) {
        throw new Exception("Exhibition not found or not approved");
    }

    if (strtotime($exhibition['end_date']) < strtotime('today')) {
        throw new Exception("Cannot cancel past exhibitions");
    }

    // Update status to cancelled
    $stmt = $conn->prepare("
        UPDATE exhibition_bookings 
        SET booking_status = 'cancelled',
            updated_at = CURRENT_TIMESTAMP 
        WHERE booking_id = ?
    ");
    
    $stmt->execute([$data['booking_id']]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}