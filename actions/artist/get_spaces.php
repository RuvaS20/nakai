<?php
session_start();
require_once '../../db/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'artist') {
    http_response_code(403);
    exit();
}

$gallery_id = filter_input(INPUT_GET, 'gallery_id', FILTER_SANITIZE_NUMBER_INT);

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("
        SELECT space_id, name 
        FROM exhibition_spaces 
        WHERE gallery_id = ? AND status = 'available'
    ");
    $stmt->execute([$gallery_id]);
    
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>