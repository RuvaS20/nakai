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

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

        <!-- CSS -->
        <link rel="stylesheet" href="../assets/css/common.css">
        <link rel="stylesheet" href="../assets/css/nav.css">
        <link rel="stylesheet" href="../assets/css/footer.css">
        <link rel="stylesheet" href="../assets/css/login.css">
    </head>

    <body>
        <!-- Navigation -->
        <nav class="main-nav">
            <h1><a href="../index.php" class="nav-logo">Nakai Nakai</a></h1>
            <div class="nav-links">
                <a href="../views/public/about.php">About</a>
                <a href="../views/public/exhibitions.php">Exhibitions</a>
                <a href="login.php" class="active">Login</a>
                <a href="register.php">Register</a>
            </div>
        </nav>

        <div class="login-wrapper">
            <div class="geometric-shapes">
                <div class="shape circle"></div>
                <div class="shape triangle"></div>
                <div class="shape rectangle"></div>
            </div>
            <div class="login-container">
                <div class="login-header">
                    <div class="brand-icon">
                        <i class="fas fa-palette"></i>
                    </div>
                    <h1>Welcome Back</h1>
                    <p>Sign in to continue to your account</p>
                </div>

                <?php if (!empty($error)): ?>
                <div class="message error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                <div class="message success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>

                <form class="login-form" method="POST" action="../actions/auth/login.php">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="form-group">
                        <div class="input-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" placeholder="Email Address" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" placeholder="Password" required>
                        </div>
                    </div>

                    <div class="form-options">
                        <div class="remember-me">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Remember me</label>
                        </div>
                        <a href="#" class="forgot-password">Forgot Password?</a>
                    </div>

                    <button type="submit" class="login-btn">
                        <span>Sign In</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>

                    <div class="login-footer">
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