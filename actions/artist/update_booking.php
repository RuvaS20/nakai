<?php
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

    // Verify the booking belongs to this artist
    $stmt = $conn->prepare("
        SELECT eb.booking_id 
        FROM exhibition_bookings eb
        JOIN artists a ON eb.artist_id = a.artist_id
        WHERE eb.booking_id = ? 
        AND a.user_id = ?
        AND eb.booking_status = 'pending'
    ");
    
    $stmt->execute([$_POST['booking_id'], $_SESSION['user_id']]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("Invalid booking or unauthorized action");
    }

    // Update the booking
    $stmt = $conn->prepare("
        UPDATE exhibition_bookings 
        SET title = ?, 
            description = ?, 
            start_date = ?, 
            end_date = ? 
        WHERE booking_id = ?
    ");

    $stmt->execute([
        $_POST['title'],
        $_POST['description'],
        $_POST['start_date'],
        $_POST['end_date'],
        $_POST['booking_id']
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>