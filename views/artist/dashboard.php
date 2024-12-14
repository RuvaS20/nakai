<?php
session_start();
require_once '../../db/database.php';

// Check if user is logged in and is an artist
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'artist') {
    header("Location: ../../auth/login.php");
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get artist_id
    $stmt = $conn->prepare("SELECT artist_id FROM artists WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $artist = $stmt->fetch(PDO::FETCH_ASSOC);
    $artist_id = $artist['artist_id'];

    // Get total exhibitions count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM exhibition_bookings WHERE artist_id = ?");
    $stmt->execute([$artist_id]);
    $total_exhibitions = $stmt->fetchColumn();

    // Get current exhibitions count
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM exhibition_bookings 
        WHERE artist_id = ? 
        AND start_date <= CURRENT_DATE 
        AND end_date >= CURRENT_DATE
        AND booking_status = 'approved'
    ");
    $stmt->execute([$artist_id]);
    $current_exhibitions = $stmt->fetchColumn();

    // Get upcoming exhibitions count
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM exhibition_bookings 
        WHERE artist_id = ? 
        AND start_date > CURRENT_DATE
        AND booking_status = 'approved'
    ");
    $stmt->execute([$artist_id]);
    $upcoming_exhibitions = $stmt->fetchColumn();

    // Get pending requests count
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM exhibition_bookings 
        WHERE artist_id = ? 
        AND booking_status = 'pending'
    ");
    $stmt->execute([$artist_id]);
    $pending_requests = $stmt->fetchColumn();

    // Get random featured artwork
$stmt = $conn->prepare("
    SELECT fe.title, fe.id, ei.image_url, g.name as gallery_name
    FROM featured_exhibitions fe
    JOIN exhibition_bookings eb ON fe.title = eb.title
    JOIN exhibition_spaces es ON eb.space_id = es.space_id
    JOIN galleries g ON es.gallery_id = g.gallery_id
    JOIN exhibition_images ei ON eb.booking_id = ei.exhibition_id
    WHERE fe.is_active = 1 AND ei.is_hero = 1
    ORDER BY RAND()
    LIMIT 1
");
    $stmt->execute();
    $featured_artwork = $stmt->fetch(PDO::FETCH_ASSOC);

    // First check if the image_url starts with assets/ or /assets/
$image_path = $featured_artwork['image_url'];
if ($image_path && strpos($image_path, '/') !== 0) {
    $image_path = '../../' . $image_path;
}

} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    // Set default values if database error occurs
    $total_exhibitions = 0;
    $current_exhibitions = 0;
    $upcoming_exhibitions = 0;
    $pending_requests = 0;
    $featured_artwork = null;
}
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Artist Dashboard - Nakai Nakai</title>
        <link href="https://fonts.googleapis.com/css2?family=Faculty+Glyphic&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Lato:wght@100;300;400;700;900&display=swap"
            rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link rel="stylesheet" href="../../assets/css/artist_dashboard.css">
    </head>

    <body>
        <nav class="artist-nav">
            <a href="dashboard.php" class="nav-logo">Nakai Nakai</a>
            <div class="nav-links">
                <a href="dashboard.php"
                    class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
                <a href="portfolio.php"
                    class="<?php echo basename($_SERVER['PHP_SELF']) === 'portfolio.php' ? 'active' : ''; ?>">Portfolio</a>
                <a href="exhibitions.php"
                    class="<?php echo basename($_SERVER['PHP_SELF']) === 'exhibitions.php' ? 'active' : ''; ?>">Exhibitions</a>
                <a href="profile.php"
                    class="<?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>"
                    title="Profile">
                    <i class="fas fa-user-circle"></i>
                </a>
                <a href="../../auth/logout.php" class="logout-link">
                    Logout
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </nav>

        <main class="main-content">
            <!-- Hero Section -->
            <section class="hero-section">
                <div class="hero-image">
                    <img src="<?php echo htmlspecialchars($image_path ?? '/api/placeholder/600/300'); ?>"
                        alt="<?php echo htmlspecialchars($featured_artwork['title'] ?? 'Featured artwork'); ?>">
                    <?php if ($featured_artwork): ?>
                    <div class="artwork-info">
                        <h2><?php echo htmlspecialchars($featured_artwork['title']); ?></h2>
                        <p>Seen At <?php echo htmlspecialchars($featured_artwork['gallery_name']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="hero-content">
                    <h1>Book Your Next Exhibition</h1>
                    <p>Showcase your artwork in leading galleries across Ghana. Browse available exhibition spaces and
                        secure your next showing.</p>
                    <a href="exhibitions.php" class="book-exhibition-btn">
                        Browse Exhibition Spaces
                        <i class="fas fa-arrow-right" style="margin-left: 0.5rem;"></i>
                    </a>
                </div>
            </section>

            <!-- Stats Section -->
            <section class="stats-section">
                <div class="stats-grid">
                    <div class="stat-card">
                        <i class="fas fa-calendar-alt stat-icon"></i>
                        <div class="stat-number"><?php echo $total_exhibitions; ?></div>
                        <div class="stat-label">Total Exhibitions</div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-paint-brush stat-icon"></i>
                        <div class="stat-number"><?php echo $current_exhibitions; ?></div>
                        <div class="stat-label">Current Exhibitions</div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-hourglass-half stat-icon"></i>
                        <div class="stat-number"><?php echo $upcoming_exhibitions; ?></div>
                        <div class="stat-label">Upcoming Exhibitions</div>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-clock stat-icon"></i>
                        <div class="stat-number"><?php echo $pending_requests; ?></div>
                        <div class="stat-label">Pending Requests</div>
                    </div>
                </div>
            </section>
        </main>
    </body>

</html>