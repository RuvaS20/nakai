<?php
require_once '../../functions/exhibitions.php';
require_once '../../functions/validation.php';

session_start();

// Get exhibitions with optional filtering
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$exhibitions = []; // We'll implement the actual function later

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "SELECT eb.*, 
              a.name as artist_name,
              g.name as gallery_name,
              e.image_url,
              es.name as space_name
              FROM exhibition_bookings eb
              JOIN artists a ON eb.artist_id = a.artist_id
              JOIN exhibition_spaces es ON eb.space_id = es.space_id
              JOIN galleries g ON es.gallery_id = g.gallery_id
              LEFT JOIN exhibition_images e ON eb.booking_id = e.exhibition_id
              WHERE e.is_hero = 1 OR e.display_order = 1
              ORDER BY eb.start_date DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $exhibitions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching exhibitions: " . $e->getMessage());
    $exhibitions = [];
}
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Nakai Nakai Art Gallery - Exhibitions</title>

        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Faculty+Glyphic&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Lato:wght@100;300;400;700;900&display=swap"
            rel="stylesheet">

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

        <!-- CSS -->
        <link rel="stylesheet" href="../../assets/css/nav.css">
        <link rel="stylesheet" href="../../assets/css/footer.css">
        <link rel="stylesheet" href="../../assets/css/common.css">
        <link rel="stylesheet" href="../../assets/css/exhibitions.css">

    </head>

    <body>

        <!-- Navigation -->
        <nav class="main-nav">
            <a href="../../index.php" class="nav-logo">Nakai Nakai</a>
            <div class="nav-links">
                <a href="about.php">About</a>
                <a href="exhibitions.php">Exhibitions</a>
                <a href="../../auth/login.php">Login</a>
                <a href="../../auth/register.php">Register</a>
            </div>
        </nav>

        <!-- Header -->
        <header class="exhibitions-header">
            <h1>Exhibitions</h1>
            <p>Discover our curated selection of contemporary African art exhibitions</p>
        </header>

        <!-- Filter -->
        <div class="filter-container">
            <div class="filter-buttons">
                <button class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>"
                    onclick="window.location.href='?filter=all'">All</button>
                <button class="filter-btn <?php echo $filter === 'current' ? 'active' : ''; ?>"
                    onclick="window.location.href='?filter=current'">Current</button>
                <button class="filter-btn <?php echo $filter === 'upcoming' ? 'active' : ''; ?>"
                    onclick="window.location.href='?filter=upcoming'">Upcoming</button>
            </div>
        </div>

        <!-- Exhibitions Grid -->
        <div class="exhibitions-grid">
            <?php if (empty($exhibitions)): ?>
            <div class="no-exhibitions">
                <p>No exhibitions found.</p>
            </div>
            <?php else: ?>
            <?php foreach ($exhibitions as $exhibition): 
                $startDate = new DateTime($exhibition['start_date']);
                $endDate = new DateTime($exhibition['end_date']);
                $now = new DateTime();
                
                $status = ($now >= $startDate && $now <= $endDate) ? 'current' : 
                         ($now < $startDate ? 'upcoming' : 'past');
                
                if ($filter !== 'all' && $filter !== $status) continue;
            ?>
            <div class="exhibition-card">
                <div class="exhibition-image">
                    <img src="../../<?php echo htmlspecialchars($exhibition['image_url']); ?>"
                        alt="<?php echo htmlspecialchars($exhibition['title']); ?>">
                </div>
                <div class="exhibition-content">
                    <div class="exhibition-status status-<?php echo $status; ?>">
                        <?php echo ucfirst($status); ?>
                    </div>
                    <h2 class="exhibition-title"><?php echo htmlspecialchars($exhibition['title']); ?></h2>
                    <div class="exhibition-artist">By <?php echo htmlspecialchars($exhibition['artist_name']); ?></div>
                    <div class="exhibition-details">
                        <p><i class="far fa-calendar"></i>
                            <?php echo $startDate->format('j M') . ' - ' . $endDate->format('j M Y'); ?></p>
                        <p><i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($exhibition['gallery_name']); ?></p>
                        <p><i class="far fa-building"></i>
                            <?php echo htmlspecialchars($exhibition['space_name']); ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <footer class="main-footer">
            <div class="footer-content">
                <div class="footer-logo">
                    <a href="/nakai/index.php">
                        <h2>Nakai Nakai</h2>
                    </a>
                </div>

                <div class="footer-social">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
                </div>

                <div class="footer-credit">
                    <p>© 2024 Nakai Nakai Art Gallery. All rights reserved.</p>
                    <p>Developed by Ruvarashe Sadya</p>
                </div>
            </div>
        </footer>
    </body>

</html>