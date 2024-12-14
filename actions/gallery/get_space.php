<?php
session_start();
require_once '../../db/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gallery') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $space_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    
    if (!$space_id) {
        throw new Exception("Invalid space ID");
    }
    
    $db = new Database();
    $conn = $db->getConnection();

    // Verify space belongs to gallery
    $stmt = $conn->prepare("
        SELECT es.* 
        FROM exhibition_spaces es
        JOIN galleries g ON es.gallery_id = g.gallery_id
        WHERE es.space_id = ? AND g.user_id = ?
    ");
    
    $stmt->execute([$space_id, $_SESSION['user_id']]);
    $space = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$space) {
        throw new Exception("Space not found or unauthorized");
    }
    
    echo json_encode(['success' => true, 'space' => $space]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>