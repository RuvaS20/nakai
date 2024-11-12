<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if current page matches path
function isCurrentPage($path) {
    return strpos($_SERVER['PHP_SELF'], $path) !== false ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Faculty+Glyphic&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@100;300;400;700;900&display=swap" rel="stylesheet">
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/common.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/nav.css?v=<?php echo time(); ?>">
</head>
<body>
    <nav class="main-nav">
        <h1><a href="/nakai/index.php" style="text-decoration: none; color: inherit;">Nakai Nakai</a></h1>
        
        <div class="nav-links">
            <a href="/nakai/public/artists.php" class="<?php echo isCurrentPage('artists.php'); ?>">Artists</a>
            <a href="/nakai/public/galleries.php" class="<?php echo isCurrentPage('galleries.php'); ?>">Galleries</a>
            <a href="/nakai/public/exhibitions.php" class="<?php echo isCurrentPage('exhibitions.php'); ?>">Exhibitions</a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-menu">
                    <a href="#" class="user-trigger">
                        <i class="fas fa-user-circle"></i>
                        Account
                    </a>
                    <div class="dropdown-content">
                        <?php if ($_SESSION['role'] === 'artist'): ?>
                            <a href="/nakai/dashboard/artist/profile.php">My Profile</a>
                            <a href="/nakai/dashboard/artist/artworks.php">My Artworks</a>
                        <?php elseif ($_SESSION['role'] === 'gallery'): ?>
                            <a href="/nakai/dashboard/gallery/profile.php">Gallery Profile</a>
                            <a href="/nakai/dashboard/gallery/spaces.php">Exhibition Spaces</a>
                        <?php endif; ?>
                        <a href="/nakai/auth/logout.php">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="/nakai/auth/login.php" class="<?php echo isCurrentPage('login.php'); ?>">Login</a>
                <a href="/nakai/auth/register.php" class="<?php echo isCurrentPage('register.php'); ?>">Register</a>
            <?php endif; ?>
        </div>
    </nav>
