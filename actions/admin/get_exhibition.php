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
        throw new Exception("Invalid exhibition ID");
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
            g.address as gallery_address,
            es.name as space_name,
            ei.image_url,
            CASE 
                WHEN eb.end_date < CURRENT_DATE THEN 'past'
                WHEN eb.start_date > CURRENT_DATE THEN 'upcoming'
                ELSE 'current'
            END as status
        FROM exhibition_bookings eb
        JOIN artists a ON eb.artist_id = a.artist_id
        JOIN exhibition_spaces es ON eb.space_id = es.space_id
        JOIN galleries g ON es.gallery_id = g.gallery_id
        LEFT JOIN exhibition_images ei ON eb.booking_id = ei.exhibition_id
        WHERE eb.booking_id = ?
    ");
    
    $stmt->execute([$booking_id]);
    $exhibition = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exhibition) {
        throw new Exception("Exhibition not found");
    }

    echo json_encode(['success' => true, 'exhibition' => $exhibition]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}