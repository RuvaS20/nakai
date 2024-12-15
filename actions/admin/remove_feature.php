<?php
session_start();
require_once '../../db/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['feature_id'])) {
        throw new Exception("Feature ID not provided");
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Soft delete by setting is_active to 0
    $stmt = $conn->prepare("
        UPDATE featured_exhibitions 
        SET is_active = 0 
        WHERE id = ?
    ");
    $stmt->execute([$data['feature_id']]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}