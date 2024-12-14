<?php
// actions/artist/upload_artwork.php
session_start();
require_once '../../db/database.php';

// Check if user is logged in and is an artist
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'artist') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get artist_id
    $stmt = $conn->prepare("SELECT artist_id FROM artists WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $artist = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$artist) {
        throw new Exception("Artist profile not found");
    }

    // Validate file upload
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("No image uploaded or upload error");
    }

    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    $file_info = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($file_info, $_FILES['image']['tmp_name']);
    finfo_close($file_info);

    if (!in_array($mime_type, $allowed_types)) {
        throw new Exception("Invalid file type. Only JPG and PNG are allowed.");
    }

    // Create upload directory if it doesn't exist
    $upload_dir = '../../assets/images/artworks/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generate unique filename
    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('artwork_') . '.' . $file_extension;
    $image_path = 'assets/images/artworks/' . $filename;
    $full_path = $upload_dir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $full_path)) {
        throw new Exception("Error saving the file");
    }

    // Begin transaction
    $conn->beginTransaction();

    // Insert artwork record
    $stmt = $conn->prepare("
        INSERT INTO artworks (artist_id, exhibition_id, title, description, image_url)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $artist['artist_id'],
        !empty($_POST['exhibition_id']) ? $_POST['exhibition_id'] : null,
        $_POST['title'],
        $_POST['description'],
        $image_path
    ]);

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    // Delete uploaded file if it exists
    if (isset($full_path) && file_exists($full_path)) {
        unlink($full_path);
    }

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>