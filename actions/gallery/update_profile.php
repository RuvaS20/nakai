<?php
session_start();
require_once '../../db/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gallery') {
    header("Location: ../../auth/login.php");
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Begin transaction
    $conn->beginTransaction();

    $image_path = null;
    
    // Handle image upload if provided
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($file_info, $_FILES['profile_image']['tmp_name']);
        finfo_close($file_info);

        if (!in_array($mime_type, $allowed_types)) {
            throw new Exception("Invalid file type. Only JPG and PNG are allowed.");
        }

        // Create upload directory if it doesn't exist
        $upload_dir = '../../assets/images/galleries/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Generate unique filename
        $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('gallery_') . '.' . $file_extension;
        $image_path = 'assets/images/galleries/' . $filename;
        $full_path = $upload_dir . $filename;

        // Move uploaded file
        if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $full_path)) {
            throw new Exception("Error saving the profile image");
        }

        // Delete old profile image if exists
        $stmt = $conn->prepare("SELECT profile_image FROM galleries WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $old_image = $stmt->fetchColumn();

        if ($old_image && file_exists('../../' . $old_image)) {
            unlink('../../' . $old_image);
        }
    }

    // Update gallery profile
    $stmt = $conn->prepare("
        UPDATE galleries 
        SET name = ?, 
            description = ?, 
            address = ?,
            phone = ?,
            website = ?
            " . ($image_path ? ", profile_image = ?" : "") . "
        WHERE user_id = ?
    ");

    $params = [
        $_POST['name'],
        $_POST['description'],
        $_POST['address'],
        $_POST['phone'],
        $_POST['website']
    ];

    if ($image_path) {
        $params[] = $image_path;
    }
    $params[] = $_SESSION['user_id'];

    $stmt->execute($params);

    // Commit transaction
    $conn->commit();

    $_SESSION['profile_success'] = "Profile updated successfully!";
    header("Location: ../../views/gallery/profile.php");
    exit();

} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    // Delete newly uploaded file if it exists
    if (isset($full_path) && file_exists($full_path)) {
        unlink($full_path);
    }

    $_SESSION['profile_error'] = $e->getMessage();
    header("Location: ../../views/gallery/profile.php");
    exit();
}
?>