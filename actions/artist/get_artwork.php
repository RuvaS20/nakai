<?php
session_start();
require_once '../../db/database.php';

// Check if user is logged in and is an artist
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'artist') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $artwork_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    
    if (!$artwork_id) {
        throw new Exception("Invalid artwork ID");
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Get artwork details ensuring it belongs to the logged-in artist
    $stmt = $conn->prepare("
        SELECT a.* 
        FROM artworks a 
        JOIN artists ar ON a.artist_id = ar.artist_id 
        WHERE a.artwork_id = ? AND ar.user_id = ?
    ");
    
    $stmt->execute([$artwork_id, $_SESSION['user_id']]);
    $artwork = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$artwork) {
        throw new Exception("Artwork not found or unauthorized");
    }

    echo json_encode(['success' => true, 'artwork' => $artwork]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}