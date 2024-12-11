<?php
/**
 * Check if an email already exists in the database
 * 
 * @param PDO $conn Database connection
 * @param string $email Email to check
 * @return bool True if email exists, false otherwise
 */
function emailExists($conn, $email) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Create a new user account
 * 
 * @param PDO $conn Database connection
 * @param string $email User's email
 * @param string $password User's password (plain text)
 * @param string $role User's role ('artist' or 'gallery')
 * @return int|false The new user ID if successful, false otherwise
 */
function createUser($conn, $email, $password, $role) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (email, password_hash, role) VALUES (?, ?, ?)");
    $result = $stmt->execute([$email, $hash, $role]);
    
    if ($result) {
        return $conn->lastInsertId();
    }
    return false;
}

/**
 * Create an artist profile
 * 
 * @param PDO $conn Database connection
 * @param array $data Artist profile data
 * @return bool True if successful, false otherwise
 */
function createArtistProfile($conn, $data) {
    $stmt = $conn->prepare("INSERT INTO artists (user_id, name, bio, phone) VALUES (?, ?, ?, ?)");
    return $stmt->execute([
        $data['user_id'],
        $data['name'],
        $data['bio'],
        $data['phone']
    ]);
}

/**
 * Create a gallery profile
 * 
 * @param PDO $conn Database connection
 * @param array $data Gallery profile data
 * @return bool True if successful, false otherwise
 */
function createGalleryProfile($conn, $data) {
    $stmt = $conn->prepare("INSERT INTO galleries (user_id, name, description, address, phone, website) 
                           VALUES (?, ?, ?, ?, ?, ?)");
    return $stmt->execute([
        $data['user_id'],
        $data['name'],
        $data['description'],
        $data['address'],
        $data['phone'],
        $data['website']
    ]);
}

/**
 * Authenticate a user
 * 
 * @param PDO $conn Database connection
 * @param string $email User's email
 * @param string $password User's password (plain text)
 * @return array|false User data if authentication successful, false otherwise
 */
function authenticateUser($conn, $email, $password) {
    $stmt = $conn->prepare("SELECT user_id, email, password_hash, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        unset($user['password_hash']); // Don't return the password hash
        return $user;
    }
    return false;
}

/**
 * Verify if a user has a specific role
 * 
 * @param PDO $conn Database connection
 * @param int $user_id User ID
 * @param string $role Role to check
 * @return bool True if user has the role, false otherwise
 */
function verifyUserRole($conn, $user_id, $role) {
    $stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    
    return $result && $result['role'] === $role;
}
