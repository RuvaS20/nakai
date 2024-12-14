<?php
session_start();
require_once '../../db/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gallery') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['space_id']) || !isset($data['name']) || !isset($data['capacity']) || !isset($data['daily_rate'])) {
        throw new Exception("Missing required fields");
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // Verify space belongs to gallery
    $stmt = $conn->prepare("
        SELECT es.space_id 
        FROM exhibition_spaces es
        JOIN galleries g ON es.gallery_id = g.gallery_id
        WHERE es.space_id = ? AND g.user_id = ?
    ");
    
    $stmt->execute([$data['space_id'], $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        throw new Exception("Space not found or unauthorized");
    }
    
    // Update space
    $stmt = $conn->prepare("
        UPDATE exhibition_spaces 
        SET name = ?, 
            capacity = ?, 
            daily_rate = ?, 
            description = ?
        WHERE space_id = ?
    ");
    
    $stmt->execute([
        $data['name'],
        $data['capacity'],
        $data['daily_rate'],
        $data['description'] ?? null,
        $data['space_id']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Space updated successfully']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>