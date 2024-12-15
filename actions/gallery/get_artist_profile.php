<?php
session_start();
require_once '../../db/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gallery') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $artist_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    
    if (!$artist_id) {
        throw new Exception("Invalid artist ID");
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get gallery ID
    $stmt = $conn->prepare("SELECT gallery_id FROM galleries WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $gallery = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$gallery) {
        throw new Exception("Gallery not found");
    }
    
    // Get artist details
    $stmt = $conn->prepare("
        SELECT 
            a.*,
            u.email
        FROM artists a
        JOIN users u ON a.user_id = u.user_id
        WHERE a.artist_id = ?
    ");
    $stmt->execute([$artist_id]);
    $artist = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$artist) {
        throw new Exception("Artist not found");
    }
    
    // Get artist's artworks
    $stmt = $conn->prepare("
        SELECT 
            aw.*,
            eb.booking_id as exhibition_id
        FROM artworks aw
        LEFT JOIN exhibition_bookings eb ON aw.exhibition_id = eb.booking_id
        WHERE aw.artist_id = ?
        ORDER BY aw.created_at DESC
    ");
    $stmt->execute([$artist_id]);
    $artworks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get exhibition details for the artist in this gallery
    $stmt = $conn->prepare("
        SELECT 
            eb.*,
            es.name as space_name,
            es.capacity,
            es.daily_rate
        FROM exhibition_bookings eb
        JOIN exhibition_spaces es ON eb.space_id = es.space_id
        WHERE eb.artist_id = ?
        AND es.gallery_id = ?
        AND eb.booking_status = 'approved'
        ORDER BY eb.start_date DESC
    ");
    $stmt->execute([$artist_id, $gallery['gallery_id']]);
    $exhibitions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'artist' => $artist,
        'artworks' => $artworks,
        'exhibitions' => $exhibitions
    ]);

} catch (Exception $e) {
    error_log("Error in get_artist_profile.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>