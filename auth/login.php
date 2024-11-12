<?php
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/User.php';
require_once '../classes/Session.php';
require_once '../includes/functions.php';

Session::start();

// Redirect if already logged in
if (Session::isLoggedIn()) {
    $role = Session::getRole();
    redirect("/nakai/dashboard/$role/profile.php");
}

$error = '';
$success = '';

// Check if user just registered
if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $success = "Registration successful! Please login.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            throw new Exception("Invalid request");
        }

        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            throw new Exception("All fields are required");
        }

        if (!validateEmail($email)) {
            throw new Exception("Invalid email format");
        }

        $db = new Database();
        $user = new User($db->getConnection());
        $user->email = $email;

        $userData = $user->emailExists();
        
        if ($userData && password_verify($password, $userData['password_hash'])) {
            Session::set('user_id', $userData['user_id']);
            Session::set('role', $userData['role']);
            
            // Redirect based on role
            redirect("/nakai/dashboard/{$userData['role']}/profile.php");
        } else {
            throw new Exception("Invalid email or password");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nakai Nakai Art Gallery - Login</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/common.css">
    <link rel="stylesheet" href="../assets/css/login.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Faculty+Glyphic&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Faculty+Glyphic&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap');
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="main-content">
        <div class="left-section">
            <!-- Login Form -->
            <form class="login-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <?php if (!empty($error)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn">Sign In</button>
            </form>
        </div>
        <div class="right-section">
            <h2>Welcome to Nakai Nakai Art Gallery</h2>
            <p>
                Join our exclusive art community to explore unique pieces, connect with artists, 
                and stay updated on upcoming exhibitions. 
                <br><br>
                Don't have an account? Create one now to start your artistic journey today.
            </p>
            <button class="btn" onclick="window.location.href='register.php'">Create Account</button>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
