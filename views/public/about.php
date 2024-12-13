<?php
require_once '../../db/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get featured artists
    $artistsQuery = "SELECT name, bio FROM artists ORDER BY RAND() LIMIT 3";
    $artistsStmt = $conn->query($artistsQuery);
    $artists = $artistsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get galleries
    $galleriesQuery = "SELECT name, description, website FROM galleries";
    $galleriesStmt = $conn->query($galleriesQuery);
    $galleries = $galleriesStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $artists = [];
    $galleries = [];
}
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>About - Nakai Nakai Art Gallery</title>

        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Faculty+Glyphic&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Lato:wght@100;300;400;700;900&display=swap"
            rel="stylesheet">

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

        <!-- CSS -->
        <link rel="stylesheet" href="../../assets/css/common.css">
        <link rel="stylesheet" href="../../assets/css/nav.css">
        <link rel="stylesheet" href="../../assets/css/footer.css">
        <link rel="stylesheet" href="../../assets/css/about.css">
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

        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-content">
                <h1>Discover Art, Connect Communities</h1>
                <p>Join the leading platform connecting artists and galleries across Africa</p>
            </div>
        </section>

        <!-- Mission Section -->
        <section class="mission-section">
            <div class="container">
                <div class="mission-grid">
                    <div class="mission-item">
                        <div class="mission-icon">
                            <i class="fas fa-paint-brush"></i>
                        </div>
                        <h3>Empower Artists</h3>
                        <p>Providing a platform for artists to showcase their work and connect with leading galleries
                        </p>
                    </div>
                    <div class="mission-item">
                        <div class="mission-icon">
                            <i class="fas fa-museum"></i>
                        </div>
                        <h3>Support Galleries</h3>
                        <p>Enabling galleries to discover talent and manage exhibitions efficiently</p>
                    </div>
                    <div class="mission-item">
                        <div class="mission-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>Build Community</h3>
                        <p>Creating meaningful connections within the African art ecosystem</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Featured Artists Section -->
        <section class="featured-section">
            <div class="container">
                <h2>Featured Artists</h2>
                <div class="featured-grid">
                    <?php foreach ($artists as $artist): ?>
                    <div class="featured-item">
                        <div class="featured-content">
                            <h3><?php echo htmlspecialchars($artist['name']); ?></h3>
                            <p><?php echo htmlspecialchars($artist['bio'] ?? 'Contemporary artist exploring various mediums and themes.'); ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Gallery Showcase -->
        <section class="gallery-showcase">
            <div class="container">
                <h2>Our Partner Galleries</h2>
                <div class="gallery-grid">
                    <?php foreach ($galleries as $gallery): ?>
                    <div class="gallery-item">
                        <div class="gallery-content">
                            <h3><?php echo htmlspecialchars($gallery['name']); ?></h3>
                            <p><?php echo htmlspecialchars($gallery['description'] ?? 'A contemporary art space showcasing diverse artistic expressions.'); ?>
                            </p>
                            <?php if (!empty($gallery['website'])): ?>
                            <a href="<?php echo htmlspecialchars($gallery['website']); ?>" class="gallery-link"
                                target="_blank">
                                Visit Website <i class="fas fa-arrow-right"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Join Section -->
        <section class="join-section">
            <div class="container">
                <div class="join-content">
                    <h2>Join Our Community</h2>
                    <p>Whether you're an artist looking to showcase your work or a gallery seeking to discover new
                        talent, Nakai Nakai is your platform.</p>
                    <div class="join-buttons">
                        <a href="../../auth/register.php" class="join-btn artist-btn">Register as Artist</a>
                        <a href="../../auth/register.php" class="join-btn gallery-btn">Register as Gallery</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="main-footer">
            <div class="footer-content">
                <div class="footer-logo">
                    <a href="../../index.php">
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
