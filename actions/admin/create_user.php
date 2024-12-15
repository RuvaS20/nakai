<?php
session_start();
require_once '../../db/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $role = $_POST['role'];
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $name = $_POST['name'];

    if (!in_array($role, ['artist', 'gallery'])) {
        throw new Exception("Invalid role");
    }

    $db = new Database();
    $conn = $db->getConnection();
    
    // Begin transaction
    $conn->beginTransaction();

    // Create user account
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)");
    $stmt->execute([$email, $password_hash, $role]);
    $user_id = $conn->lastInsertId();

    // Create role-specific profile
    if ($role === 'artist') {
        $stmt = $conn->prepare("INSERT INTO artists (user_id, name) VALUES (?, ?)");
    } else {
        $stmt = $conn->prepare("INSERT INTO galleries (user_id, name) VALUES (?, ?)");
    }
    $stmt->execute([$user_id, $name]);

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}