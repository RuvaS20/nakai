<?php
session_start();
require_once '../../db/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gallery') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['space_id']) || !isset($data['status'])) {
        throw new Exception("Missing required fields");
    }
    
    // Validate status
    $valid_statuses = ['available', 'maintenance', 'booked'];
    if (!in_array($data['status'], $valid_statuses)) {
        throw new Exception("Invalid status value");
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // Verify space belongs to gallery and check for active bookings
    $stmt = $conn->prepare("
        SELECT es.space_id, 
               COUNT(eb.booking_id) as active_bookings
        FROM exhibition_spaces es
        JOIN galleries g ON es.gallery_id = g.gallery_id
        LEFT JOIN exhibition_bookings eb ON es.space_id = eb.space_id
            AND eb.booking_status = 'approved'
            AND eb.end_date >= CURRENT_DATE
        WHERE es.space_id = ? AND g.user_id = ?
        GROUP BY es.space_id
    ");
    
    $stmt->execute([$data['space_id'], $_SESSION['user_id']]);
    $space = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$space) {
        throw new Exception("Space not found or unauthorized");
    }
    
    // If space has active bookings, only allow status change to 'booked' or 'maintenance'
    if ($space['active_bookings'] > 0 && $data['status'] === 'available') {
        throw new Exception("Cannot set space as available while there are active bookings");
    }
    
    // Update status
    $stmt = $conn->prepare("
        UPDATE exhibition_spaces 
        SET status = ?
        WHERE space_id = ?
    ");
    
    $stmt->execute([$data['status'], $data['space_id']]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Space status updated successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>