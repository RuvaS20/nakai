<?php

// CSRF Token Functions
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Email Validation
function validateEmail($email) {
    if (empty($email)) {
        return false;
    }
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Password Validation
function validatePassword($password) {
    // Password must be at least 8 characters
    // Must contain at least one uppercase letter
    // Must contain at least one lowercase letter
    // Must contain at least one number
    if (strlen($password) < 8) {
        return false;
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    
    return true;
}

// Name Validation
function validateName($name) {
    return !empty($name) && strlen($name) <= 255 && preg_match('/^[\p{L}\s\'-]+$/u', $name);
}

// Phone Number Validation
function validatePhone($phone) {
    if (empty($phone)) {
        return true; // Phone is optional
    }
    // Accepts formats: +1234567890, 123-456-7890, (123) 456-7890, etc.
    return preg_match('/^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4}$/', $phone);
}

// URL Validation
function validateURL($url) {
    if (empty($url)) {
        return true; // URL is optional
    }
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

// Text Field Validation
function validateTextField($text, $maxLength = 1000) {
    if (empty($text)) {
        return true; // Text fields are optional
    }
    return strlen($text) <= $maxLength;
}

// Input Sanitization
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Role Validation
function validateRole($role) {
    return in_array($role, ['artist', 'gallery']);
}

// Error Handling
function setValidationError($message) {
    $_SESSION['register_error'] = $message;
}

function setValidationSuccess($message) {
    $_SESSION['register_success'] = $message;
}

// Complete Form Validation
function validateRegistrationForm($data, $role) {
    // Required fields for both roles
    if (!validateEmail($data['email'])) {
        setValidationError('Invalid email format');
        return false;
    }
    
    if (!validatePassword($data['password'])) {
        setValidationError('Password must be at least 8 characters long and contain uppercase, lowercase, and numbers');
        return false;
    }
    
    if (!validateRole($role)) {
        setValidationError('Invalid role selected');
        return false;
    }
    
    // Role-specific validation
    if ($role === 'artist') {
        if (!validateName($data['artist-name'])) {
            setValidationError('Invalid artist name');
            return false;
        }
        
        if (!empty($data['artist-bio']) && !validateTextField($data['artist-bio'])) {
            setValidationError('Artist bio is too long');
            return false;
        }
        
        if (!empty($data['artist-phone']) && !validatePhone($data['artist-phone'])) {
            setValidationError('Invalid phone number format');
            return false;
        }
    } else { // gallery
        if (!validateName($data['gallery-name'])) {
            setValidationError('Invalid gallery name');
            return false;
        }
        
        if (!empty($data['gallery-description']) && !validateTextField($data['gallery-description'])) {
            setValidationError('Gallery description is too long');
            return false;
        }
        
        if (!empty($data['gallery-phone']) && !validatePhone($data['gallery-phone'])) {
            setValidationError('Invalid phone number format');
            return false;
        }
        
        if (!empty($data['gallery-website']) && !validateURL($data['gallery-website'])) {
            setValidationError('Invalid website URL');
            return false;
        }
    }
    
    return true;
}
?>
