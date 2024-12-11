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
$error = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : '';
unset($_SESSION['login_error']);

// Get success message if any
$success = isset($_SESSION['login_success']) ? $_SESSION['login_success'] : '';
unset($_SESSION['login_success']);

// Check if user just registered
if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $success = "Registration successful! Please login.";
}
?>
<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Nakai Nakai Art Gallery - Login</title>

        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Faculty+Glyphic&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Lato:wght@100;300;400;700;900&display=swap"
            rel="stylesheet">

        <!-- CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link rel="stylesheet" href="../assets/css/common.css">
        <link rel="stylesheet" href="../assets/css/nav.css">
        <link rel="stylesheet" href="../assets/css/footer.css">
        <link rel="stylesheet" href="../assets/css/login.css">

        <!-- Add this right after your CSS links -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check if login.css is loaded
            const styles = document.styleSheets;
            let loginCssLoaded = false;
            for (let i = 0; i < styles.length; i++) {
                try {
                    if (styles[i].href && styles[i].href.includes('login.css')) {
                        loginCssLoaded = true;
                        break;
                    }
                } catch (e) {
                    // Handle cross-origin style sheets
                    continue;
                }
            }
            if (!loginCssLoaded) {
                console.error('login.css not loaded');
            }
        });
        </script>
    </head>

    <body>
        <!-- Navigation -->
        <nav class="main-nav">
            <h1><a href="../index.php" style="text-decoration: none; color: inherit;">Nakai Nakai</a></h1>
            <div class="nav-links">
                <a href="../views/public/artists.php">Artists</a>
                <a href="../views/public/galleries.php">Galleries</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-menu">
                    <a href="#" class="user-trigger">
                        <i class="fas fa-user-circle"></i>
                        Account
                    </a>
                    <div class="dropdown-content">
                        <?php if ($_SESSION['role'] === 'artist'): ?>
                        <a href="../views/artist/dashboard.php">Dashboard</a>
                        <a href="../views/artist/portfolio.php">Portfolio</a>
                        <?php elseif ($_SESSION['role'] === 'gallery'): ?>
                        <a href="../views/gallery/dashboard.php">Dashboard</a>
                        <a href="../views/gallery/spaces.php">Spaces</a>
                        <?php endif; ?>
                        <a href="../auth/logout.php">Logout</a>
                    </div>
                </div>
                <?php else: ?>
                <a href="login.php" class="active">Login</a>
                <a href="register.php">Register</a>
                <?php endif; ?>
            </div>
        </nav>

        <div class="main-content">
            <div class="login-container">
                <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if (!empty($success)): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <form class="login-form" method="POST" action="../actions/auth/login.php">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="form-group">
                        <label for="email">Email Address <span>*</span></label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password <span>*</span></label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <button type="submit" class="btn">Sign In</button>

                    <div class="form-footer">
                        <p>Don't have an account? <a href="register.php">Register here</a></p>
                    </div>
                </form>
            </div>
        </div>

        <!-- Footer -->
        <footer class="main-footer">
            <div class="footer-content">
                <div class="footer-logo">
                    <a href="../index.php">
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
