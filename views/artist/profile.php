<?php
session_start();
require_once '../../db/database.php';

// Check if user is logged in and is an artist
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'artist') {
    header("Location: ../../auth/login.php");
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get artist details
    $stmt = $conn->prepare("
        SELECT a.*, u.email 
        FROM artists a 
        JOIN users u ON a.user_id = u.user_id 
        WHERE u.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $artist = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $error = "An error occurred while loading your profile.";
}

// Get messages if any
$success = isset($_SESSION['profile_success']) ? $_SESSION['profile_success'] : '';
$error = isset($_SESSION['profile_error']) ? $_SESSION['profile_error'] : '';
unset($_SESSION['profile_success'], $_SESSION['profile_error']);
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Artist Profile - Nakai Nakai</title>

        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Faculty+Glyphic&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Lato:wght@100;300;400;700;900&display=swap"
            rel="stylesheet">

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

        <!-- Custom CSS -->
        <link rel="stylesheet" href="../../assets/css/artist_profile.css">
    </head>

    <body>
        <!-- Navigation -->
        <nav class="artist-nav">
            <a href="dashboard.php" class="nav-logo">Nakai Nakai</a>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="portfolio.php">Portfolio</a>
                <a href="exhibitions.php">Exhibitions</a>
                <a href="profile.php" class="active" title="Profile">
                    <i class="fas fa-user-circle"></i>
                </a>
                <a href="../../auth/logout.php" class="logout-link">
                    Logout
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </nav>

        <main class="main-content">
            <div class="profile-container">
                <h1 class="profile-title">Profile Settings</h1>

                <?php if (!empty($success)): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <form class="profile-form" method="POST" action="../../actions/artist/update_profile.php"
                    enctype="multipart/form-data">
                    <div class="profile-image-section">
                        <div class="current-image">
                            <?php if (!empty($artist['profile_image_url'])): ?>
                            <img src="../../<?php echo htmlspecialchars($artist['profile_image_url']); ?>"
                                alt="Profile Image">
                            <?php else: ?>
                            <div class="image-placeholder">
                                <i class="fas fa-user"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="image-upload">
                            <input type="file" id="profile_image" name="profile_image" accept="image/*">
                            <label for="profile_image" class="upload-btn">
                                <i class="fas fa-camera"></i>
                                Change Profile Picture
                            </label>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" value="<?php echo htmlspecialchars($artist['email']); ?>"
                                disabled>
                            <small>Email cannot be changed</small>
                        </div>

                        <div class="form-group">
                            <label for="name">Artist Name <span class="required">*</span></label>
                            <input type="text" id="name" name="name"
                                value="<?php echo htmlspecialchars($artist['name']); ?>" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="bio">Bio</label>
                            <textarea id="bio" name="bio"
                                rows="4"><?php echo htmlspecialchars($artist['bio'] ?? ''); ?></textarea>
                            <small>Brief description of your artistic background and style</small>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone"
                                value="<?php echo htmlspecialchars($artist['phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="save-btn">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </main>

        <script>
        // Image preview handling
        document.getElementById('profile_image').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    const currentImage = document.querySelector('.current-image');
                    currentImage.innerHTML = '';
                    currentImage.appendChild(img);
                }
                reader.readAsDataURL(file);
            }
        });
        </script>
    </body>

</html>