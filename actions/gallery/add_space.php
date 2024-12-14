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
    
    if (!isset($data['name']) || !isset($data['capacity']) || !isset($data['daily_rate'])) {
        throw new Exception("Missing required fields");
    }
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get gallery_id
    $stmt = $conn->prepare("SELECT gallery_id FROM galleries WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $gallery = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Insert new space
    $stmt = $conn->prepare("
        INSERT INTO exhibition_spaces 
        (gallery_id, name, capacity, daily_rate, description, status) 
        VALUES (?, ?, ?, ?, ?, 'available')
    ");
    
    $stmt->execute([
        $gallery['gallery_id'],
        $data['name'],
        $data['capacity'],
        $data['daily_rate'],
        $data['description'] ?? null
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Space added successfully']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>