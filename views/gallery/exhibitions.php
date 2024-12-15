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
    
    // Get gallery_id first
    $stmt = $conn->prepare("SELECT gallery_id FROM galleries WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $gallery = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$gallery) {
        throw new Exception("Gallery not found");
    }

    // Get search term if provided
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    // Get artists with their artworks and exhibition details
    $query = "
        SELECT DISTINCT
            a.artist_id,
            a.name as artist_name,
            a.bio,
            a.profile_image_url,
            COUNT(DISTINCT aw.artwork_id) as artwork_count,
            COUNT(DISTINCT eb.booking_id) as exhibition_count
        FROM artists a
        LEFT JOIN artworks aw ON a.artist_id = aw.artist_id
        LEFT JOIN exhibition_bookings eb ON a.artist_id = eb.artist_id
        JOIN exhibition_spaces es ON eb.space_id = es.space_id
        WHERE es.gallery_id = :gallery_id";

    if (!empty($search)) {
        $query .= " AND (a.name LIKE :search OR a.bio LIKE :search)";
    }
    
    $query .= " GROUP BY a.artist_id ORDER BY a.name ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':gallery_id', $gallery['gallery_id']);
    
    if (!empty($search)) {
        $stmt->bindValue(':search', "%$search%");
    }
    
    $stmt->execute();
    $artists = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $error = "An error occurred while loading artists.";
    $artists = [];
}
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Exhibitions & Artists - Nakai Nakai</title>
        <link href="https://fonts.googleapis.com/css2?family=Faculty+Glyphic&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Lato:wght@100;300;400;700;900&display=swap"
            rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link rel="stylesheet" href="../../assets/css/gallery_exhibitions.css">
    </head>

    <body>
        <nav class="gallery-nav">
            <a href="dashboard.php" class="nav-logo">Nakai Nakai</a>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="exhibitions.php" class="active">Artists</a>
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
            <!-- Search Section -->
            <section class="search-section">
                <h1>Browse Artists</h1>
                <form class="search-form" action="" method="GET">
                    <div class="search-input">
                        <input type="text" name="search" placeholder="Search artists by name..."
                            value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </section>

            <!-- Artists Grid -->
            <section class="artists-section">
                <div class="artists-grid">
                    <?php if (empty($artists)): ?>
                    <div class="no-results">
                        <i class="fas fa-user-slash"></i>
                        <p>No artists found. Try a different search term.</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($artists as $artist): ?>
                    <div class="artist-card" data-artist-id="<?php echo $artist['artist_id']; ?>">
                        <div class="artist-image">
                            <?php if (!empty($artist['profile_image_url'])): ?>
                            <img src="../../<?php echo htmlspecialchars($artist['profile_image_url']); ?>"
                                alt="<?php echo htmlspecialchars($artist['artist_name']); ?>">
                            <?php else: ?>
                            <i class="fas fa-user-circle"></i>
                            <?php endif; ?>
                        </div>
                        <div class="artist-info">
                            <h3><?php echo htmlspecialchars($artist['artist_name']); ?></h3>
                            <p class="artist-stats">
                                <span><i class="fas fa-palette"></i> <?php echo $artist['artwork_count']; ?>
                                    Artworks</span>
                                <span><i class="fas fa-calendar-alt"></i> <?php echo $artist['exhibition_count']; ?>
                                    Exhibitions</span>
                            </p>
                            <?php if (!empty($artist['bio'])): ?>
                            <p class="artist-bio">
                                <?php echo htmlspecialchars(substr($artist['bio'], 0, 150)) . '...'; ?></p>
                            <?php endif; ?>
                            <button onclick="viewArtistProfile(<?php echo $artist['artist_id']; ?>)"
                                class="view-profile-btn">
                                View Profile
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Artist Profile Modal -->
            <div id="artistModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <div id="artistProfileContent"></div>
                </div>
            </div>
        </main>

        <script src="../../assets/js/gallery_exhibitions.js"></script>
    </body>

</html>