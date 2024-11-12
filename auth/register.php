<?php
require_once '../config/database.php';
require_once '../classes/Database.php';
require_once '../classes/User.php';
require_once '../classes/Artist.php';
require_once '../classes/Gallery.php';
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            throw new Exception("Invalid request");
        }

        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'];
        $role = $_POST['role'];

        if (!in_array($role, ['artist', 'gallery'])) {
            throw new Exception("Invalid role selected");
        }

        if (empty($email) || empty($password)) {
            throw new Exception("Required fields cannot be empty");
        }

        if (!validateEmail($email)) {
            throw new Exception("Invalid email format");
        }

        if (!validatePassword($password)) {
            throw new Exception("Password must be at least 8 characters long and contain uppercase, lowercase, and numbers");
        }

        $db = new Database();
        $conn = $db->getConnection();
        $user = new User($conn);

        // Check if email already exists
        $user->email = $email;
        if ($user->emailExists()) {
            throw new Exception("Email already registered");
        }

        // Begin transaction
        $conn->beginTransaction();

        // Create user account
        $user->password = $password;
        $user->role = $role;
        $user_id = $user->create();

        if (!$user_id) {
            throw new Exception("Error creating user account");
        }

        // Create role-specific profile
        if ($role === 'artist') {
            $artist = new Artist($conn);
            $artist->user_id = $user_id;
            $artist->name = sanitizeInput($_POST['artist-name']);
            $artist->bio = sanitizeInput($_POST['artist-bio']);
            $artist->phone = sanitizeInput($_POST['artist-phone']);
            
            if (!$artist->create()) {
                throw new Exception("Error creating artist profile");
            }
        } else {
            $gallery = new Gallery($conn);
            $gallery->user_id = $user_id;
            $gallery->name = sanitizeInput($_POST['gallery-name']);
            $gallery->description = sanitizeInput($_POST['gallery-description']);
            $gallery->address = sanitizeInput($_POST['gallery-address']);
            $gallery->phone = sanitizeInput($_POST['gallery-phone']);
            $gallery->website = sanitizeInput($_POST['gallery-website']);
            
            if (!$gallery->create()) {
                throw new Exception("Error creating gallery profile");
            }
        }

        // Commit transaction
        $conn->commit();
        
        // Redirect to login page
        redirect("/nakai/auth/login.php?registered=1");

    } catch (Exception $e) {
        // Rollback transaction if active
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nakai Nakai Art Gallery - Register</title>

    <!-- Google Fonts -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Faculty+Glyphic&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Faculty+Glyphic&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&display=swap');
    </style>
    
    <!-- Base styles first -->
    <link rel="stylesheet" href="../assets/css/common.css?v=<?php echo time(); ?>">
    
    <!-- Third-party styles -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Page-specific styles last -->
    <link rel="stylesheet" href="../assets/css/register.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="main-content">
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

            <form class="registration-form artist-form active" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="role" value="artist">
                
                <?php if (!empty($error)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

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

            <form class="registration-form gallery-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="role" value="gallery">
                
                <?php if (!empty($error)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
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

    <?php include '../includes/footer.php'; ?>

    <script>
        function toggleForm(type) {
    const artistForm = document.querySelector('.artist-form');
    const galleryForm = document.querySelector('.gallery-form');
    const artistOption = document.querySelector('.toggle-option:first-child');
    const galleryOption = document.querySelector('.toggle-option:last-child');

    if (type === 'artist') {
        artistForm.classList.add('active');
        galleryForm.classList.remove('active');
        artistOption.classList.add('active');
        galleryOption.classList.remove('active');
    } else {
        galleryForm.classList.add('active');
        artistForm.classList.remove('active');
        galleryOption.classList.add('active');
        artistOption.classList.remove('active');
    }
}

    </script>
</body>
</html>
