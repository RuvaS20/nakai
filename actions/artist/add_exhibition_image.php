<?php
session_start();
require_once '../../db/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'artist') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $exhibition_id = filter_input(INPUT_POST, 'exhibition_id', FILTER_SANITIZE_NUMBER_INT);
    $is_hero = isset($_POST['is_hero']) && $_POST['is_hero'] === 'true';
    
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("No image uploaded or upload error");
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Verify the exhibition belongs to this artist
    $stmt = $conn->prepare("
        SELECT eb.booking_id 
        FROM exhibition_bookings eb
        JOIN artists a ON eb.artist_id = a.artist_id
        WHERE eb.booking_id = ? AND a.user_id = ?
    ");
    $stmt->execute([$exhibition_id, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        throw new Exception("Unauthorized access to this exhibition");
    }

    // Create upload directory if it doesn't exist
    $upload_dir = '../../assets/images/exhibitions/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generate unique filename
    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('exhibition_') . '.' . $file_extension;
    $image_path = 'assets/images/exhibitions/' . $filename;
    $full_path = $upload_dir . $filename;

    // Begin transaction
    $conn->beginTransaction();

    // If this is a hero image, unset any existing hero
    if ($is_hero) {
        $stmt = $conn->prepare("
            UPDATE exhibition_images 
            SET is_hero = FALSE 
            WHERE exhibition_id = ?
        ");
        $stmt->execute([$exhibition_id]);
    }

    // Get next display order
    $stmt = $conn->prepare("SELECT COALESCE(MAX(display_order), 0) + 1 FROM exhibition_images WHERE exhibition_id = ?");
    $stmt->execute([$exhibition_id]);
    $display_order = $stmt->fetchColumn();

    // Insert new image
    $stmt = $conn->prepare("
        INSERT INTO exhibition_images 
        (exhibition_id, image_url, is_hero, display_order)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$exhibition_id, $image_path, $is_hero, $display_order]);

    // Move uploaded file
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $full_path)) {
        throw new Exception("Error saving the file");
    }

    // Commit transaction
    $conn->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}