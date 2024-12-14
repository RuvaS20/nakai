<?php 
session_start();
require_once '../../db/database.php';

// Check if user is logged in and is an artist
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'artist') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    // Get the request body and decode JSON
    $data = json_decode(file_get_contents('php://input'), true);
    $booking_id = $data['booking_id'];

    // Get the database connection
    $db = new Database();
    $conn = $db->getConnection();

    // Verify the booking belongs to this artist and is pending
    $stmt = $conn->prepare("
        SELECT eb.booking_id 
        FROM exhibition_bookings eb
        JOIN artists a ON eb.artist_id = a.artist_id 
        WHERE eb.booking_id = ? 
        AND a.user_id = ? 
        AND eb.booking_status = 'pending'
    ");
    $stmt->execute([$booking_id, $_SESSION['user_id']]);

    if ($stmt->rowCount() === 0) {
        throw new Exception("Invalid booking or unauthorized action");
    }

    // Update the booking status to cancelled
    $stmt = $conn->prepare("
        UPDATE exhibition_bookings 
        SET booking_status = 'cancelled' 
        WHERE booking_id = ?
    ");
    $stmt->execute([$booking_id]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>