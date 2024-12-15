<?php
session_start();
require_once '../../db/database.php';

// Check if user is logged in and is a gallery
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'gallery') {
    header("Location: ../../auth/login.php");
    exit();
}

// Get status filter if set
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$valid_statuses = ['all', 'pending', 'approved', 'rejected'];
if (!in_array($status_filter, $valid_statuses)) {
    $status_filter = 'all';
}

// Get success/error messages if any
$success = isset($_SESSION['request_success']) ? $_SESSION['request_success'] : '';
$error = isset($_SESSION['request_error']) ? $_SESSION['request_error'] : '';
unset($_SESSION['request_success'], $_SESSION['request_error']);
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Exhibition Requests - Nakai Nakai</title>
        <link href="https://fonts.googleapis.com/css2?family=Faculty+Glyphic&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Lato:wght@100;300;400;700;900&display=swap"
            rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link rel="stylesheet" href="../../assets/css/gallery_requests.css">
    </head>

    <body>
        <nav class="gallery-nav">
            <a href="dashboard.php" class="nav-logo">Nakai Nakai</a>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="spaces.php">Spaces</a>
                <a href="requests.php" class="active">Requests</a>
                <a href="profile.php" title="Profile">
                    <i class="fas fa-user-circle"></i>
                </a>
                <a href="../../auth/logout.php" class="logout-link">
                    Logout
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </nav>

        <main class="main-content">
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

            <div class="requests-header">
                <h1>Exhibition Requests</h1>
                <div class="filter-buttons">
                    <a href="?status=all" class="filter-btn <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                        All Requests
                    </a>
                    <a href="?status=pending"
                        class="filter-btn <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                        Pending
                    </a>
                    <a href="?status=approved"
                        class="filter-btn <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">
                        Approved
                    </a>
                    <a href="?status=rejected"
                        class="filter-btn <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">
                        Rejected
                    </a>
                </div>
            </div>

            <!-- Requests will be loaded here by JavaScript -->
            <div class="requests-grid">
                <!-- Loading indicator -->
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    Loading requests...
                </div>
            </div>
        </main>

        <!-- Details Modal -->
        <div id="detailsModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <div id="modalContent"></div>
            </div>
        </div>

        <script src="../../assets/js/gallery_requests.js"></script>
    </body>

</html>