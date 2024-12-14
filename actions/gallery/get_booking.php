<?php
session_start();
require_once '../../db/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gallery') {
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
    
    // Updated query with correct join to get email from users table
    $stmt = $conn->prepare("
        SELECT 
            eb.*,
            a.name as artist_name,
            a.phone as artist_phone,
            a.profile_image_url as artist_image,
            u.email as artist_email,
            es.name as space_name,
            es.capacity,
            es.daily_rate,
            ei.image_url as exhibition_image
        FROM exhibition_bookings eb
        JOIN exhibition_spaces es ON eb.space_id = es.space_id
        JOIN artists a ON eb.artist_id = a.artist_id
        JOIN users u ON a.user_id = u.user_id
        LEFT JOIN exhibition_images ei ON eb.booking_id = ei.exhibition_id
        JOIN galleries g ON es.gallery_id = g.gallery_id
        WHERE eb.booking_id = ? AND g.user_id = ?
    ");
    
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        throw new Exception("Booking not found or unauthorized");
    }
    
    // Format dates
    $start = new DateTime($booking['start_date']);
    $end = new DateTime($booking['end_date']);
    $booking['formatted_dates'] = $start->format('M d, Y') . ' - ' . $end->format('M d, Y');
    
    echo json_encode(['success' => true, 'booking' => $booking]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>