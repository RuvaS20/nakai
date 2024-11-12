<?php
function redirect($path) {
    header("Location: " . $path);
    exit();
}

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        throw new Exception('CSRF token validation failed');
    }
    return true;
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags($data));
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePassword($password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    return strlen($password) >= 8 && 
           preg_match('/[A-Z]/', $password) && 
           preg_match('/[a-z]/', $password) && 
           preg_match('/[0-9]/', $password);
}

function displayError($message) {
    return "<div class='error-message'>" . htmlspecialchars($message) . "</div>";
}

function displaySuccess($message) {
    return "<div class='success-message'>" . htmlspecialchars($message) . "</div>";
}
?>
