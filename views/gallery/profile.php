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
    
    // Get gallery details
    $stmt = $conn->prepare("
        SELECT g.*, u.email 
        FROM galleries g 
        JOIN users u ON g.user_id = u.user_id 
        WHERE u.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $gallery = $stmt->fetch(PDO::FETCH_ASSOC);
    
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
        <title>Gallery Profile - Nakai Nakai</title>

        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Faculty+Glyphic&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Lato:wght@100;300;400;700;900&display=swap"
            rel="stylesheet">

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

        <!-- CSS -->
        <link rel="stylesheet" href="../../assets/css/gallery_profile.css">
    </head>

    <body>
        <nav class="gallery-nav">
            <a href="dashboard.php" class="nav-logo">Nakai Nakai</a>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="spaces.php">Spaces</a>
                <a href="requests.php">Requests</a>
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

                <form class="profile-form" method="POST" action="../../actions/gallery/update_profile.php"
                    enctype="multipart/form-data">
                    <div class="profile-image-section">
                        <div class="current-image">
                            <?php if (!empty($gallery['profile_image'])): ?>
                            <img src="../../<?php echo htmlspecialchars($gallery['profile_image']); ?>"
                                alt="Profile Image">
                            <?php else: ?>
                            <div class="image-placeholder">
                                <i class="fas fa-building"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="image-upload">
                            <input type="file" id="profile_image" name="profile_image" accept="image/*">
                            <label for="profile_image" class="upload-btn">
                                <i class="fas fa-camera"></i>
                                Change Gallery Image
                            </label>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" value="<?php echo htmlspecialchars($gallery['email']); ?>"
                                disabled>
                            <small>Email cannot be changed</small>
                        </div>

                        <div class="form-group">
                            <label for="name">Gallery Name <span class="required">*</span></label>
                            <input type="text" id="name" name="name"
                                value="<?php echo htmlspecialchars($gallery['name']); ?>" required>
                        </div>

                        <div class="form-group full-width">
                            <label for="description">Description</label>
                            <textarea id="description" name="description"
                                rows="4"><?php echo htmlspecialchars($gallery['description'] ?? ''); ?></textarea>
                            <small>Describe your gallery, its focus, and what kind of exhibitions you host</small>
                        </div>

                        <div class="form-group full-width">
                            <label for="address">Address <span class="required">*</span></label>
                            <textarea id="address" name="address" rows="2"
                                required><?php echo htmlspecialchars($gallery['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone"
                                value="<?php echo htmlspecialchars($gallery['phone'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="website">Website</label>
                            <input type="url" id="website" name="website"
                                value="<?php echo htmlspecialchars($gallery['website'] ?? ''); ?>"
                                placeholder="https://">
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