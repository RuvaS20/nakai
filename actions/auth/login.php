<?php
require_once '../../db/database.php';
require_once '../../functions/auth.php';
require_once '../../functions/validation.php';

session_start();

// If not POST request, redirect to login page
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /nakai/auth/login.php");
    exit();
}

try {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        throw new Exception("Invalid request");
    }

    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        throw new Exception("All fields are required");
    }

    if (!validateEmail($email)) {
        throw new Exception("Invalid email format");
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Check if user exists and verify password
    $stmt = $conn->prepare("SELECT user_id, password_hash, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];

        // Redirect to appropriate dashboard
        header("Location: /nakai/views/{$user['role']}/dashboard.php");
        exit();
    } else {
        throw new Exception("Invalid email or password");
    }

} catch (Exception $e) {
    $_SESSION['login_error'] = $e->getMessage();
    header("Location: /nakai/auth/login.php");
    exit();
}
