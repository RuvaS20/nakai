<?php
session_start();
require_once '../../db/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $booking_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    
    if (!$booking_id) {
        throw new Exception("Invalid booking ID");
    }

    $db = new Database();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("
        SELECT 
            eb.*,
            a.name as artist_name,
            a.email as artist_email,
            a.phone as artist_phone,
            g.name as gallery_name,
            es.name as space_name,
            es.capacity as space_capacity,
            es.daily_rate,
            ei.image_url
        FROM exhibition_bookings eb
        JOIN artists a ON eb.artist_id = a.artist_id
        JOIN exhibition_spaces es ON eb.space_id = es.space_id
        JOIN galleries g ON es.gallery_id = g.gallery_id
        LEFT JOIN exhibition_images ei ON eb.booking_id = ei.exhibition_id
        WHERE eb.booking_id = ?
    ");
    
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception("Booking not found");
    }

    echo json_encode(['success' => true, 'booking' => $booking]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}