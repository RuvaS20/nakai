<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    header("Location: /nakai/views/$role/dashboard.php");
    exit();
}

require_once '../functions/validation.php';

// Get error message if any
$error = isset($_SESSION['register_error']) ? $_SESSION['register_error'] : '';
unset($_SESSION['register_error']);

// Get success message if any
$success = isset($_SESSION['register_success']) ? $_SESSION['register_success'] : '';
unset($_SESSION['register_success']);
?>
<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Nakai Nakai Art Gallery - Register</title>

        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Faculty+Glyphic&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Lato:wght@100;300;400;700;900&display=swap"
            rel="stylesheet">

        <!-- CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link rel="stylesheet" href="../assets/css/common.css">
        <link rel="stylesheet" href="../assets/css/nav.css">
        <link rel="stylesheet" href="../assets/css/footer.css">
        <link rel="stylesheet" href="../assets/css/register.css">

        <script>
        // Define the function in the global scope
        window.toggleForm = function(type) {
            const artistForm = document.querySelector('.artist-form');
            const galleryForm = document.querySelector('.gallery-form');
            const artistOption = document.querySelector('.toggle-option:first-child');
            const galleryOption = document.querySelector('.toggle-option:last-child');

            if (type === 'artist') {
                artistForm.classList.add('active');
                galleryForm.classList.remove('active');
                artistOption.classList.add('active');
                artistOption.classList.remove('inactive');
                galleryOption.classList.remove('active');
                galleryOption.classList.add('inactive');
            } else {
                galleryForm.classList.add('active');
                artistForm.classList.remove('active');
                galleryOption.classList.add('active');
                galleryOption.classList.remove('inactive');
                artistOption.classList.remove('active');
                artistOption.classList.add('inactive');
            }
        }

        // Add initialization code
        document.addEventListener('DOMContentLoaded', function() {

            // Check if all required elements exist
            const elements = {
                artistForm: document.querySelector('.artist-form'),
                galleryForm: document.querySelector('.gallery-form'),
                artistToggle: document.querySelector('.toggle-option:first-child'),
                galleryToggle: document.querySelector('.toggle-option:last-child')
            };

            // Only call toggleForm if both forms exist
            if (elements.artistForm && elements.galleryForm) {
                toggleForm('artist');
            } else {
                console.error('Missing required form elements:', elements);
            }
        });
        </script>
    </head>

    <body>
        <!-- Navigation -->
        <nav class="main-nav">
            <h1><a href="/nakai/index.php" style="text-decoration: none; color: inherit;">Nakai Nakai</a></h1>
            <div class="nav-links">
                <a href="../views/public/about.php">Artists</a>
                <a href="../views/public/exhibitions.php">Exhibitions</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-menu">
                    <a href="#" class="user-trigger">
                        <i class="fas fa-user-circle"></i>
                        Account
                    </a>
                    <div class="dropdown-content">
                        <?php if ($_SESSION['role'] === 'artist'): ?>
                        <a href="/nakai/views/artist/dashboard.php">Dashboard</a>
                        <a href="/nakai/views/artist/portfolio.php">Portfolio</a>
                        <?php elseif ($_SESSION['role'] === 'gallery'): ?>
                        <a href="/nakai/views/gallery/dashboard.php">Dashboard</a>
                        <a href="/nakai/views/gallery/spaces.php">Spaces</a>
                        <?php endif; ?>
                        <a href="/nakai/auth/logout.php">Logout</a>
                    </div>
                </div>
                <?php else: ?>
                <a href="/nakai/auth/login.php">Login</a>
                <a href="/nakai/auth/register.php" class="active">Register</a>
                <?php endif; ?>
            </div>
        </nav>

        <div class="main-content">
            <div class="geometric-shapes">
                <div class="shape circle"></div>
                <div class="shape square"></div>
                <div class="shape triangle"></div>
                <div class="shape rectangle"></div>
                <div class="shape circle-small"></div>
            </div>
            <div class="registration-container">
                <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if (!empty($success)): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <div class="toggle-header">
                    <div class="toggle-option active" onclick="toggleForm('artist')">
                        <h2>Artist Registration</h2>
                        <p>Join our community as an artist</p>
                    </div>
                    <div class="toggle-option inactive" onclick="toggleForm('gallery')">
                        <h2>Gallery Registration</h2>
                        <p>Register your gallery</p>
                    </div>
                </div>

                <!-- Artist Registration Form -->
                <form class="registration-form artist-form" method="POST" action="../actions/auth/register.php">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="role" value="artist">

                    <div class="form-group">
                        <label for="artist-email">Email Address <span>*</span></label>
                        <input type="email" id="artist-email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="artist-password">Password <span>*</span></label>
                        <input type="password" id="artist-password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="artist-name">Full Name <span>*</span></label>
                        <input type="text" id="artist-name" name="artist-name" required>
                    </div>
                    <div class="form-group">
                        <label for="artist-bio">Bio</label>
                        <textarea id="artist-bio" name="artist-bio"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="artist-phone">Phone Number</label>
                        <input type="tel" id="artist-phone" name="artist-phone">
                    </div>
                    <button type="submit" class="btn">Register as Artist</button>
                </form>

                <!-- Gallery Registration Form -->
                <form class="registration-form gallery-form" method="POST" action="../actions/auth/register.php">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="role" value="gallery">

                    <div class="form-group">
                        <label for="gallery-email">Email Address <span>*</span></label>
                        <input type="email" id="gallery-email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="gallery-password">Password <span>*</span></label>
                        <input type="password" id="gallery-password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="gallery-name">Gallery Name <span>*</span></label>
                        <input type="text" id="gallery-name" name="gallery-name" required>
                    </div>
                    <div class="form-group">
                        <label for="gallery-description">Description</label>
                        <textarea id="gallery-description" name="gallery-description"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="gallery-address">Address</label>
                        <textarea id="gallery-address" name="gallery-address"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="gallery-phone">Phone Number</label>
                        <input type="tel" id="gallery-phone" name="gallery-phone">
                    </div>
                    <div class="form-group">
                        <label for="gallery-website">Website</label>
                        <input type="url" id="gallery-website" name="gallery-website">
                    </div>
                    <button type="submit" class="btn">Register as Gallery</button>
                </form>
            </div>
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