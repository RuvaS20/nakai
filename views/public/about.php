<?php
require_once '../../db/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get 3 random artists
    $artistsQuery = "SELECT name, bio FROM artists ORDER BY RAND() LIMIT 3";
    $artistsStmt = $conn->query($artistsQuery);
    $artists = $artistsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all galleries
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
        <title>Artists & Galleries - Nakai Nakai Art Gallery</title>

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

        <!-- Artists Section -->
        <section class="section artists-section" id="artists">
            <div class="artistic-circle circle-1"></div>
            <div class="artistic-circle circle-2"></div>

            <div class="section-content">
                <div class="section-header">
                    <h1 class="section-title">For Artists</h1>
                    <p class="section-description">
                        Join our vibrant community of artists and showcase your work to leading galleries across Africa.
                        Get discovered and expand your artistic journey with Nakai Nakai.
                    </p>
                </div>

                <div class="cards-container">
                    <?php foreach ($artists as $artist): ?>
                    <div class="card">
                        <h3><?php echo htmlspecialchars($artist['name']); ?></h3>
                        <p><?php echo htmlspecialchars($artist['bio'] ?? 'Contemporary artist exploring various mediums and themes.'); ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>

                <a href="../../auth/register.php" class="register-btn">Register as an Artist</a>
            </div>
        </section>

        <!-- Galleries Section -->
        <section class="section galleries-section" id="galleries">
            <div class="artistic-circle circle-1"></div>
            <div class="artistic-circle circle-2"></div>

            <div class="section-content">
                <div class="section-header">
                    <h1 class="section-title">For Galleries</h1>
                    <p class="section-description">
                        Connect with talented artists, manage your exhibitions, and grow your gallery's presence.
                        Join Nakai Nakai to streamline your gallery operations.
                    </p>
                </div>

                <div class="cards-container">
                    <?php foreach ($galleries as $gallery): ?>
                    <div class="card">
                        <h3><?php echo htmlspecialchars($gallery['name']); ?></h3>
                        <p><?php echo htmlspecialchars($gallery['description'] ?? 'A contemporary art space showcasing diverse artistic expressions.'); ?>
                        </p>
                        <a href="<?php echo htmlspecialchars($gallery['website'] ?? '#'); ?>" class="website"
                            target="_blank">
                            <?php echo htmlspecialchars($gallery['website'] ?? 'Coming Soon'); ?>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>

                <a href="../../auth/register.php" class="register-btn">Register as a Gallery</a>
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
