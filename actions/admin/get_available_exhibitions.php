<?php
session_start();
require_once '../../db/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Get approved exhibitions that aren't already featured
    $stmt = $conn->prepare("
        SELECT eb.booking_id, eb.title, a.name as artist_name
        FROM exhibition_bookings eb
        JOIN artists a ON eb.artist_id = a.artist_id
        WHERE eb.booking_status = 'approved'
        AND eb.end_date >= CURRENT_DATE
        AND eb.title NOT IN (
            SELECT title FROM featured_exhibitions WHERE is_active = 1
        )
        ORDER BY eb.start_date ASC
    ");
    $stmt->execute();
    $exhibitions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'exhibitions' => $exhibitions]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}