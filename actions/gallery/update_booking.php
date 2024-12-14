<?php
session_start();
require_once '../../db/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gallery') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    // Get the request body
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['booking_id']) || !isset($data['status'])) {
        throw new Exception("Missing required parameters");
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // Verify gallery owns this booking
    $stmt = $conn->prepare("
        SELECT eb.booking_id 
        FROM exhibition_bookings eb
        JOIN exhibition_spaces es ON eb.space_id = es.space_id
        JOIN galleries g ON es.gallery_id = g.gallery_id
        WHERE eb.booking_id = ? AND g.user_id = ?
    ");
    $stmt->execute([$data['booking_id'], $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        throw new Exception("Unauthorized access to this booking");
    }
    
    // Update booking status
    $stmt = $conn->prepare("
        UPDATE exhibition_bookings 
        SET booking_status = ?, 
            updated_at = CURRENT_TIMESTAMP 
        WHERE booking_id = ?
    ");
    
    $stmt->execute([$data['status'], $data['booking_id']]);
    
    // If updating to approved, check for space availability
    if ($data['status'] === 'approved') {
        // Add any additional logic for handling approved bookings
        // Such as updating space availability, sending notifications, etc.
    }
    
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>