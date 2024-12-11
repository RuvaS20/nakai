<?php
require_once '../../db/database.php';
require_once '../../functions/auth.php';
require_once '../../functions/validation.php';

session_start();

// If not POST request, redirect to registration page
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /nakai/auth/register.php");
    exit();
}

try {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        throw new Exception("Invalid request");
    }

    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (!in_array($role, ['artist', 'gallery'])) {
        throw new Exception("Invalid role selected");
    }

    if (empty($email) || empty($password)) {
        throw new Exception("Required fields cannot be empty");
    }

    if (!validateEmail($email)) {
        throw new Exception("Invalid email format");
    }

    if (!validatePassword($password)) {
        throw new Exception("Password must be at least 8 characters long and contain uppercase, lowercase, and numbers");
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Check if email exists
    if (emailExists($conn, $email)) {
        throw new Exception("Email already registered");
    }

    // Begin transaction
    $conn->beginTransaction();

    // Create user account
    $user_id = createUser($conn, $email, $password, $role);

    if (!$user_id) {
        throw new Exception("Error creating user account");
    }

    // Create role-specific profile
    if ($role === 'artist') {
        $result = createArtistProfile($conn, [
            'user_id' => $user_id,
            'name' => sanitizeInput($_POST['artist-name']),
            'bio' => sanitizeInput($_POST['artist-bio']),
            'phone' => sanitizeInput($_POST['artist-phone'])
        ]);
    } else {
        $result = createGalleryProfile($conn, [
            'user_id' => $user_id,
            'name' => sanitizeInput($_POST['gallery-name']),
            'description' => sanitizeInput($_POST['gallery-description']),
            'address' => sanitizeInput($_POST['gallery-address']),
            'phone' => sanitizeInput($_POST['gallery-phone']),
            'website' => sanitizeInput($_POST['gallery-website'])
        ]);
    }

    if (!$result) {
        throw new Exception("Error creating profile");
    }

    // Commit transaction
    $conn->commit();
    
    // Set success message and redirect
    $_SESSION['register_success'] = "Registration successful! Please log in.";
    header("Location: /nakai/auth/login.php");
    exit();

} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Set error message and redirect back to registration
    $_SESSION['register_error'] = $e->getMessage();
    header("Location: /nakai/auth/register.php");
    exit();
}
