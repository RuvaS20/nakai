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
    
    if (!isset($data['user_id'])) {
        throw new Exception("User ID not provided");
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Begin transaction
    $conn->beginTransaction();

    // Get user role and check for active bookings
    $stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
    $stmt->execute([$data['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User not found");
    }

    // Check for active bookings
    if ($user['role'] === 'artist') {
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM exhibition_bookings eb
            JOIN artists a ON eb.artist_id = a.artist_id
            WHERE a.user_id = ? AND eb.booking_status = 'approved'
            AND eb.end_date >= CURRENT_DATE
        ");
    } else {
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM exhibition_bookings eb
            JOIN exhibition_spaces es ON eb.space_id = es.space_id
            JOIN galleries g ON es.gallery_id = g.gallery_id
            WHERE g.user_id = ? AND eb.booking_status = 'approved'
            AND eb.end_date >= CURRENT_DATE
        ");
    }
    $stmt->execute([$data['user_id']]);
    $active_bookings = $stmt->fetchColumn();

    if ($active_bookings > 0) {
        throw new Exception("Cannot delete user with active bookings");
    }

    // Delete user
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$data['user_id']]);

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}