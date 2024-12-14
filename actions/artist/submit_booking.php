<?php
session_start();
require_once '../../db/database.php';

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

    // Check for date overlap in the same space
$stmt = $conn->prepare("
    SELECT COUNT(*) FROM exhibition_bookings 
    WHERE space_id = ? 
    AND booking_status != 'cancelled'
    AND (
        (start_date BETWEEN ? AND ?) OR
        (end_date BETWEEN ? AND ?) OR
        (start_date <= ? AND end_date >= ?)
    )
");

$stmt->execute([
    $_POST['space'],
    $_POST['start_date'], $_POST['end_date'],
    $_POST['start_date'], $_POST['end_date'],
    $_POST['start_date'], $_POST['end_date']
]);

if ($stmt->fetchColumn() > 0) {
    throw new Exception("This space is already booked for the selected dates");
}
    
    // Insert booking
    $stmt = $conn->prepare("
        INSERT INTO exhibition_bookings 
        (space_id, artist_id, start_date, end_date, title, description, booking_status)
        VALUES (?, ?, ?, ?, ?, ?, 'pending')
    ");
    
    $stmt->execute([
        $_POST['space'],
        $artist['artist_id'],
        $_POST['start_date'],
        $_POST['end_date'],
        $_POST['title'],
        $_POST['description']
    ]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>