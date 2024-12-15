<?php
session_start();
require_once '../../db/database.php';

// Check if user is logged in and is a gallery
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gallery') {
    header("Location: ../../auth/login.php");
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get gallery_id
    $stmt = $conn->prepare("SELECT gallery_id FROM galleries WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $gallery = $stmt->fetch(PDO::FETCH_ASSOC);
    $gallery_id = $gallery['gallery_id'];

    // Get total spaces count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM exhibition_spaces WHERE gallery_id = ?");
    $stmt->execute([$gallery_id]);
    $total_spaces = $stmt->fetchColumn();

    // Get active exhibitions count
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM exhibition_bookings eb
        JOIN exhibition_spaces es ON eb.space_id = es.space_id
        WHERE es.gallery_id = ?
        AND eb.booking_status = 'approved'
        AND eb.start_date <= CURRENT_DATE 
        AND eb.end_date >= CURRENT_DATE
    ");
    $stmt->execute([$gallery_id]);
    $active_exhibitions = $stmt->fetchColumn();

    // Get pending requests count
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM exhibition_bookings eb
        JOIN exhibition_spaces es ON eb.space_id = es.space_id
        WHERE es.gallery_id = ?
        AND eb.booking_status = 'pending'
    ");
    $stmt->execute([$gallery_id]);
    $pending_requests = $stmt->fetchColumn();

    // Get recent booking requests (limited to 5)
    $stmt = $conn->prepare("
        SELECT 
            eb.booking_id,
            eb.title,
            eb.start_date,
            eb.end_date,
            eb.booking_status,
            a.name as artist_name,
            es.name as space_name
        FROM exhibition_bookings eb
        JOIN exhibition_spaces es ON eb.space_id = es.space_id
        JOIN artists a ON eb.artist_id = a.artist_id
        WHERE es.gallery_id = ?
        AND eb.booking_status = 'pending'
        ORDER BY eb.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$gallery_id]);
    $recent_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get current and upcoming exhibitions
$stmt = $conn->prepare("
    SELECT DISTINCT
        eb.booking_id,
        eb.title,
        eb.start_date,
        eb.end_date,
        a.name as artist_name,
        es.name as space_name,
        (SELECT image_url 
         FROM exhibition_images ei 
         WHERE ei.exhibition_id = eb.booking_id 
         LIMIT 1) as image_url
    FROM exhibition_bookings eb
    JOIN exhibition_spaces es ON eb.space_id = es.space_id
    JOIN artists a ON eb.artist_id = a.artist_id
    WHERE es.gallery_id = ?
    AND eb.booking_status = 'approved'
    AND eb.end_date >= CURRENT_DATE
    ORDER BY eb.start_date ASC
");
    $stmt->execute([$gallery_id]);
    $exhibitions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    // Set default values if database error occurs
    $total_spaces = 0;
    $active_exhibitions = 0;
    $pending_requests = 0;
    $recent_requests = [];
    $exhibitions = [];
}
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gallery Dashboard - Nakai Nakai</title>
        <link href="https://fonts.googleapis.com/css2?family=Faculty+Glyphic&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Lato:wght@100;300;400;700;900&display=swap"
            rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link rel="stylesheet" href="../../assets/css/gallery_dashboard.css">
    </head>

    <body>
        <nav class="gallery-nav">
            <a href="dashboard.php" class="nav-logo">Nakai Nakai</a>
            <div class="nav-links">
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="exhibitions.php">Artists</a>
                <a href="requests.php">Requests</a>
                <a href="spaces.php">Spaces</a>
                <a href="profile.php" title="Profile">
                    <i class="fas fa-user-circle"></i>
                </a>
                <a href="../../auth/logout.php" class="logout-link">
                    Logout
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </nav>

        <main class="main-content">
            <!-- Stats Section -->
            <section class="stats-section">
                <div class="stats-grid">
                    <div class="stat-card">
                        <i class="fas fa-door-open stat-icon"></i>
                        <div class="stat-number"><?php echo $total_spaces; ?></div>
                        <div class="stat-label">Total Spaces</div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-palette stat-icon"></i>
                        <div class="stat-number"><?php echo $active_exhibitions; ?></div>
                        <div class="stat-label">Active Exhibitions</div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-clock stat-icon"></i>
                        <div class="stat-number"><?php echo $pending_requests; ?></div>
                        <div class="stat-label">Pending Requests</div>
                    </div>
                </div>
            </section>

            <!-- Recent Requests Section -->
            <section class="requests-section">
                <h2>Recent Booking Requests</h2>
                <?php if (empty($recent_requests)): ?>
                <div class="no-requests">
                    <p>No pending requests at the moment.</p>
                </div>
                <?php else: ?>
                <div class="requests-grid">
                    <?php foreach ($recent_requests as $request): ?>
                    <div class="request-card">
                        <div class="request-header">
                            <h3><?php echo htmlspecialchars($request['title']); ?></h3>
                            <span class="request-status status-pending">Pending</span>
                        </div>
                        <div class="request-details">
                            <p>
                                <i class="fas fa-user"></i>
                                <?php echo htmlspecialchars($request['artist_name']); ?>
                            </p>
                            <p>
                                <i class="fas fa-door-open"></i>
                                <?php echo htmlspecialchars($request['space_name']); ?>
                            </p>
                            <p>
                                <i class="fas fa-calendar"></i>
                                <?php 
                                        $start = new DateTime($request['start_date']);
                                        $end = new DateTime($request['end_date']);
                                        echo $start->format('M d, Y') . ' - ' . $end->format('M d, Y');
                                    ?>
                            </p>
                        </div>
                        <div class="request-actions">
                            <a href="requests.php?id=<?php echo $request['booking_id']; ?>" class="view-btn">
                                View Details
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>

            <!-- Calendar Section -->
            <section class="calendar-section">
                <h2>Exhibition Calendar</h2>
                <div class="calendar-grid">
                    <?php foreach ($exhibitions as $exhibition): 
                    $start = new DateTime($exhibition['start_date']);
                    $end = new DateTime($exhibition['end_date']);
                    $now = new DateTime();
                    $status = ($now >= $start && $now <= $end) ? 'current' : 'upcoming';
                ?>
                    <div class="calendar-card status-<?php echo $status; ?>">
                        <div class="calendar-image">
                            <?php if (!empty($exhibition['image_url'])): ?>
                            <img src="../../<?php echo htmlspecialchars($exhibition['image_url']); ?>"
                                alt="<?php echo htmlspecialchars($exhibition['title']); ?>">
                            <?php else: ?>
                            <div class="placeholder-image">
                                <i class="fas fa-image"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="calendar-content">
                            <div class="calendar-status"><?php echo ucfirst($status); ?></div>
                            <h3><?php echo htmlspecialchars($exhibition['title']); ?></h3>
                            <p class="calendar-artist">
                                <i class="fas fa-user"></i>
                                <?php echo htmlspecialchars($exhibition['artist_name']); ?>
                            </p>
                            <p class="calendar-space">
                                <i class="fas fa-door-open"></i>
                                <?php echo htmlspecialchars($exhibition['space_name']); ?>
                            </p>
                            <p class="calendar-dates">
                                <i class="fas fa-calendar"></i>
                                <?php echo $start->format('M d') . ' - ' . $end->format('M d, Y'); ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>
    </body>

</html>