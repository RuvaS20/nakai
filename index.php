<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'functions/exhibitions.php';
require_once 'functions/news.php';

// Get the data first
$heroExhibition = getFeaturedHeroExhibition();
$featuredExhibitions = getFeaturedExhibitions(3);

?>
<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Nakai Nakai Art Gallery</title>

        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Faculty+Glyphic&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Lato:wght@100;300;400;700;900&display=swap"
            rel="stylesheet">

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

        <link rel="stylesheet" href="assets/css/footer.css">
        <link rel="stylesheet" href="assets/css/index.css">

        <!-- Preload critical images -->
        <?php if ($heroExhibition): ?>
        <link rel="preload" as="image" href="<?php echo htmlspecialchars($heroExhibition['image_url']); ?>">
        <?php endif; ?>
    </head>

    <body>
        <div class="hero-section"
            <?php if ($heroExhibition): ?>style="background-image: url('<?php echo htmlspecialchars($heroExhibition['image_url']); ?>')"
            <?php endif; ?>>
            <div class="overlay"></div>
            <nav class="main-nav">
                <a href="index.php" class="nav-logo">Nakai Nakai</a>
                <div class="nav-links">
                    <a href="views/public/artists.php">Artists</a>
                    <a href="views/public/galleries.php">Galleries</a>
                    <a href="auth/login.php">Login</a>
                    <a href="auth/register.php">Register</a>
                </div>
            </nav>

            <div class="hero-content">
                <div class="hero-tag">Featured Exhibition</div>
                <h1 class="hero-title"><?php echo htmlspecialchars($heroExhibition['title'] ?? 'Coming Soon'); ?></h1>
                <div class="hero-date">
                    <?php if ($heroExhibition): ?>
                    <?php 
                    $start = new DateTime($heroExhibition['start_date']);
                    $end = new DateTime($heroExhibition['end_date']);
                    echo $start->format('j F') . ' – ' . $end->format('j F Y');
                    ?>
                </div>
                <div class="hero-location"><?php echo htmlspecialchars($heroExhibition['location']); ?></div>
                <?php endif; ?>
                <a href="views/public/exhibitions.php?id=<?php echo htmlspecialchars($heroExhibition['id'] ?? ''); ?>"
                    class="hero-cta">
                    Find out more
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="scroll-indicator">
                <i class="fas fa-chevron-down"></i>
            </div>
        </div>

        <section class="exhibitions-section">
            <h1 class="section-title">EXHIBITIONS</h1>

            <div class="exhibition-grid">
                <?php foreach ($featuredExhibitions as $exhibition): ?>
                <div class="exhibition-item">
                    <div class="exhibition-image">
                        <img src="<?php echo htmlspecialchars($exhibition['image_url']); ?>"
                            alt="<?php echo htmlspecialchars($exhibition['title']); ?>" loading="lazy">
                    </div>
                    <div class="exhibition-info">
                        <div class="exhibition-tag"><?php echo htmlspecialchars($exhibition['subtitle']); ?></div>
                        <h2 class="exhibition-title"><?php echo htmlspecialchars($exhibition['title']); ?></h2>
                        <h3 class="exhibition-subtitle">BY <?php echo htmlspecialchars($exhibition['artist_name']); ?>
                        </h3>
                        <div class="exhibition-date">
                            Until <?php echo (new DateTime($exhibition['end_date']))->format('j M'); ?>,
                            <?php echo $exhibition['booking_status'] === 'approved' ? 'Now Showing' : 'Coming Soon'; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="see-all-link">
                <a href="views/public/exhibitions.php">See all exhibitions <i class="fas fa-arrow-right"></i></a>
            </div>
        </section>

        <section class="news-section">
            <h1 class="section-title">ART NEWS</h1>

            <div class="news-grid">
                <?php
        require_once 'functions/news.php';
        
        $newsData = fetchArtNews();
        if ($newsData && isset($newsData['response']['results'])) {
            foreach ($newsData['response']['results'] as $article) {
                $fields = $article['fields'] ?? [];
                $thumbnail = $fields['thumbnail'] ?? 'assets/images/placeholder.jpg';
                $summary = $fields['trailText'] ?? '';
                ?>
                <article class="news-card">
                    <div class="news-image">
                        <img src="<?php echo htmlspecialchars($thumbnail); ?>"
                            alt="<?php echo htmlspecialchars($article['webTitle']); ?>">
                    </div>
                    <div class="news-content">
                        <h3 class="news-title">
                            <a href="<?php echo htmlspecialchars($article['webUrl']); ?>" target="_blank">
                                <?php echo htmlspecialchars($article['webTitle']); ?>
                            </a>
                        </h3>
                        <p class="news-summary"><?php echo htmlspecialchars($summary); ?></p>
                        <div class="news-date">
                            <?php echo date('F j, Y', strtotime($article['webPublicationDate'])); ?>
                        </div>
                    </div>
                </article>
                <?php
            }
        }
        ?>
            </div>
        </section>

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

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const nav = document.querySelector('.main-nav');
            const heroSection = document.querySelector('.hero-section');
            const scrollIndicator = document.querySelector('.scroll-indicator');
            const exhibitionsSection = document.querySelector('.exhibitions-section');

            // Smooth scroll to exhibitions when clicking the arrow
            scrollIndicator.addEventListener('click', () => {
                exhibitionsSection.scrollIntoView({
                    behavior: 'smooth'
                });
            });

            // Change nav styles based on scroll position
            function updateNavStyle() {
                const heroBottom = heroSection.offsetTop + heroSection.offsetHeight;
                if (window.scrollY < heroBottom - nav.offsetHeight) {
                    nav.classList.remove('over-content');
                    nav.classList.add('over-hero');
                } else {
                    nav.classList.remove('over-hero');
                    nav.classList.add('over-content');
                }
            }

            // Initial check
            updateNavStyle();

            // Update on scroll
            window.addEventListener('scroll', updateNavStyle);
        });
        </script>
    </body>

</html>
