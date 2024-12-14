<?php
session_start();
require_once '../../db/database.php';

// Check if user is logged in and is an artist
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'artist') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $booking_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    
    $db = new Database();
    $conn = $db->getConnection();

    // Verify the booking belongs to this artist
    $stmt = $conn->prepare("
        SELECT eb.booking_id, eb.title, eb.description, 
               DATE(eb.start_date) as start_date, 
               DATE(eb.end_date) as end_date
        FROM exhibition_bookings eb
        JOIN artists a ON eb.artist_id = a.artist_id
        WHERE eb.booking_id = ? 
        AND a.user_id = ?
        AND eb.booking_status = 'pending'
    ");
    
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception("Booking not found or unauthorized");
    }

    echo json_encode($booking);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>