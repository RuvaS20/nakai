<?php
// actions/artist/update_artwork.php
session_start();
require_once '../../db/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'artist') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Verify artwork belongs to artist
    $stmt = $conn->prepare("
        SELECT a.artwork_id, a.image_url 
        FROM artworks a 
        JOIN artists ar ON a.artist_id = ar.artist_id 
        WHERE a.artwork_id = ? AND ar.user_id = ?
    ");
    $stmt->execute([$_POST['artwork_id'], $_SESSION['user_id']]);
    $artwork = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$artwork) {
        throw new Exception("Artwork not found or unauthorized");
    }

    // Begin transaction
    $conn->beginTransaction();

    // Handle new image upload if provided
    $image_path = $artwork['image_url'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
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

        // Delete old image
        $old_image_path = '../../' . $artwork['image_url'];
        if (file_exists($old_image_path)) {
            unlink($old_image_path);
        }
    }

    // Update artwork record
    $stmt = $conn->prepare("
        UPDATE artworks 
        SET title = ?, 
            description = ?, 
            exhibition_id = ?,
            image_url = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE artwork_id = ?
    ");

    $stmt->execute([
        $_POST['title'],
        $_POST['description'],
        !empty($_POST['exhibition_id']) ? $_POST['exhibition_id'] : null,
        $image_path,
        $_POST['artwork_id']
    ]);

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    // Delete newly uploaded file if it exists
    if (isset($full_path) && file_exists($full_path)) {
        unlink($full_path);
    }

    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>