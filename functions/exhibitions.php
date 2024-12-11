<?php
require_once __DIR__ . '/../db/database.php';

function getFeaturedHeroExhibition() {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Remove date condition temporarily
        $query = "SELECT * FROM featured_exhibitions 
                  WHERE is_active = 1 
                  ORDER BY display_order ASC 
                  LIMIT 1";
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result;
    } catch (PDOException $e) {
        error_log("Error fetching hero exhibition: " . $e->getMessage());
        return null;
    }
}

function getFeaturedExhibitions($limit = 3) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $query = "SELECT fe.*, 
                 COALESCE(eb.booking_status, 'pending') as booking_status,
                 COALESCE(a.name, 'Various Artists') as artist_name
         FROM featured_exhibitions fe
         LEFT JOIN exhibition_bookings eb ON fe.title = eb.title
         LEFT JOIN artists a ON eb.artist_id = a.artist_id
         WHERE fe.is_active = 1 
         ORDER BY fe.display_order ASC 
         LIMIT :limit";
        
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Skip first one since it's already shown in hero section
        return array_slice($result, 1, $limit);
    } catch (PDOException $e) {
        error_log("Error in getFeaturedExhibitions: " . $e->getMessage());
        return [];
    }
}
?>
