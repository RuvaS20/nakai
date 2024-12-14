<?php
// actions/artist/delete_artwork.php
session_start();
require_once '../../db/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'artist') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get the request body
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['artwork_id'])) {
        throw new Exception("Artwork ID not provided");
    }

    // Verify artwork belongs to artist
    $stmt = $conn->prepare("
        SELECT a.artwork_id, a.image_url 
        FROM artworks a 
        JOIN artists ar ON a.artist_id = ar.artist_id 
        WHERE a.artwork_id = ? AND ar.user_id = ?
    ");
    $stmt->execute([$data['artwork_id'], $_SESSION['user_id']]);
    $artwork = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$artwork) {
        throw new Exception("Artwork not found or unauthorized");
    }

    // Begin transaction
    $conn->beginTransaction();

    // Delete artwork record
    $stmt = $conn->prepare("DELETE FROM artworks WHERE artwork_id = ?");
    $stmt->execute([$data['artwork_id']]);

    // Delete image file
    $image_path = '../../' . $artwork['image_url'];
    if (file_exists($image_path)) {
        unlink($image_path);
    }

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// actions/artist/get_artwork.php
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $artwork_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        
        if (!$artwork_id) {
            throw new Exception("Artwork ID not provided");
        }

        // Get artwork details
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

        echo json_encode($artwork);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>