<?php
session_start();
require_once '../../db/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $exhibition_id = $_POST['exhibition_id'];
    $display_order = $_POST['display_order'];

    $db = new Database();
    $conn = $db->getConnection();

    // Get exhibition details
    $stmt = $conn->prepare("
        SELECT eb.*, ei.image_url
        FROM exhibition_bookings eb
        LEFT JOIN exhibition_images ei ON eb.booking_id = ei.exhibition_id
        WHERE eb.booking_id = ?
    ");
    $stmt->execute([$exhibition_id]);
    $exhibition = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$exhibition) {
        throw new Exception("Exhibition not found");
    }

    // Add to featured exhibitions
    $stmt = $conn->prepare("
        INSERT INTO featured_exhibitions 
        (title, subtitle, description, image_url, start_date, end_date, display_order, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, 1)
    ");
    $stmt->execute([
        $exhibition['title'],
        '',  // subtitle can be added if needed
        $exhibition['description'],
        $exhibition['image_url'],
        $exhibition['start_date'],
        $exhibition['end_date'],
        $display_order
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}