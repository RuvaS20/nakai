<?php
session_start();
require_once '../../db/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gallery') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get gallery_id
    $stmt = $conn->prepare("SELECT gallery_id FROM galleries WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $gallery = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get status filter if provided
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';
    
    // Updated query with DISTINCT and subquery for image
    $query = "
        SELECT DISTINCT
            eb.booking_id,
            eb.title,
            eb.description,
            eb.start_date,
            eb.end_date,
            eb.booking_status,
            eb.created_at,
            a.name as artist_name,
            a.phone as artist_phone,
            a.profile_image_url as artist_image,
            u.email as artist_email,
            es.name as space_name,
            es.capacity,
            es.daily_rate,
            (SELECT image_url 
             FROM exhibition_images ei 
             WHERE ei.exhibition_id = eb.booking_id 
             LIMIT 1) as exhibition_image,
            g.gallery_id
        FROM exhibition_bookings eb
        JOIN exhibition_spaces es ON eb.space_id = es.space_id
        JOIN artists a ON eb.artist_id = a.artist_id
        JOIN users u ON a.user_id = u.user_id
        JOIN galleries g ON es.gallery_id = g.gallery_id
        WHERE g.gallery_id = ?
    ";
    
    // Add status filter if not 'all'
    if ($status !== 'all') {
        $query .= " AND eb.booking_status = ?";
        $params = [$gallery['gallery_id'], $status];
    } else {
        $params = [$gallery['gallery_id']];
    }
    
    $query .= " ORDER BY eb.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates for each request
    foreach ($requests as &$request) {
        $request['formatted_dates'] = [
            'start' => date('M d, Y', strtotime($request['start_date'])),
            'end' => date('M d, Y', strtotime($request['end_date']))
        ];
    }
    
    echo json_encode(['success' => true, 'requests' => $requests]);

} catch (Exception $e) {
    error_log("Error in get_requests.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>