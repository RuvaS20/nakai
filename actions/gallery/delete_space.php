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
    
    if (!isset($data['space_id'])) {
        throw new Exception("Space ID is required");
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // Begin transaction
    $conn->beginTransaction();
    
    // Check for active bookings and ownership
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
    
    if ($space['active_bookings'] > 0) {
        throw new Exception("Cannot delete space with active bookings");
    }
    
    // Check for any pending bookings
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM exhibition_bookings 
        WHERE space_id = ? AND booking_status = 'pending'
    ");
    $stmt->execute([$data['space_id']]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("Cannot delete space with pending booking requests");
    }
    
    // Delete the space
    $stmt = $conn->prepare("DELETE FROM exhibition_spaces WHERE space_id = ?");
    $stmt->execute([$data['space_id']]);
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Space deleted successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>