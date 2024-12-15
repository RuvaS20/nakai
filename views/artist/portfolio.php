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
    
    // Get artist_id
    $stmt = $conn->prepare("SELECT artist_id FROM artists WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $artist = $stmt->fetch(PDO::FETCH_ASSOC);
    $artist_id = $artist['artist_id'];

    // Get approved exhibitions for dropdown
    $stmt = $conn->prepare("
        SELECT booking_id, title 
        FROM exhibition_bookings 
        WHERE artist_id = ? AND booking_status = 'approved'
        ORDER BY start_date DESC
    ");
    $stmt->execute([$artist_id]);
    $exhibitions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all artworks grouped by exhibition
    $stmt = $conn->prepare("
        SELECT 
            a.*,
            eb.title as exhibition_title,
            eb.booking_status
        FROM artworks a
        LEFT JOIN exhibition_bookings eb ON a.exhibition_id = eb.booking_id
        WHERE a.artist_id = ?
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$artist_id]);
    $artworks = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $exhibitions = [];
    $artworks = [];
}
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Artist Portfolio - Nakai Nakai</title>

        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Faculty+Glyphic&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Lato:wght@100;300;400;700;900&display=swap"
            rel="stylesheet">

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">


        <!-- Custom CSS -->
        <link rel="stylesheet" href="../../assets/css/artist_portfolio.css">
    </head>

    <body>
        <!-- Navigation -->
        <nav class="artist-nav">
            <a href="dashboard.php" class="nav-logo">Nakai Nakai</a>
            <div class="nav-links">
                <a href="dashboard.php"
                    class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
                <a href="portfolio.php"
                    class="<?php echo basename($_SERVER['PHP_SELF']) === 'portfolio.php' ? 'active' : ''; ?>">Portfolio</a>
                <a href="exhibitions.php"
                    class="<?php echo basename($_SERVER['PHP_SELF']) === 'exhibitions.php' ? 'active' : ''; ?>">Exhibitions</a>
                <a href="profile.php"
                    class="<?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>"
                    title="Profile">
                    <i class="fas fa-user-circle"></i>
                </a>
                <a href="../../auth/logout.php" class="logout-link">
                    Logout
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </nav>

        <main class="main-content">
            <!-- Upload Section -->
            <section class="upload-section">
                <h2>Add New Artwork</h2>
                <form id="uploadForm" class="upload-form" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Artwork Title <span class="required">*</span></label>
                        <input type="text" id="title" name="title" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="exhibition">Exhibition (Optional)</label>
                        <select id="exhibition" name="exhibition_id">
                            <option value="">None (General Portfolio)</option>
                            <?php foreach ($exhibitions as $exhibition): ?>
                            <option value="<?php echo htmlspecialchars($exhibition['booking_id']); ?>">
                                <?php echo htmlspecialchars($exhibition['title']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="image">Artwork Image <span class="required">*</span></label>
                        <div class="file-upload">
                            <input type="file" id="image" name="image" accept="image/*" required>
                            <label for="image" class="file-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Choose an image</span>
                            </label>
                        </div>
                        <div id="imagePreview" class="image-preview"></div>
                    </div>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-plus"></i> Add Artwork
                    </button>
                </form>
            </section>

            <!-- Portfolio Grid -->
            <section class="portfolio-section">
                <div class="portfolio-header">
                    <h2>My Portfolio</h2>
                    <div class="portfolio-filters">
                        <button class="filter-btn active" data-filter="all">All</button>
                        <button class="filter-btn" data-filter="exhibition">Exhibition</button>
                        <button class="filter-btn" data-filter="general">General</button>
                    </div>
                </div>

                <div class="artwork-grid">
                    <?php foreach ($artworks as $artwork): ?>
                    <div class="artwork-item"
                        data-category="<?php echo $artwork['exhibition_id'] ? 'exhibition' : 'general'; ?>">
                        <div class="artwork-image">
                            <img src="../../<?php echo htmlspecialchars($artwork['image_url']); ?>"
                                alt="<?php echo htmlspecialchars($artwork['title']); ?>" loading="lazy">

                            <div class="artwork-overlay">
                                <button class="edit-btn" type="button"
                                    onclick="openEditModal(<?php echo $artwork['artwork_id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="delete-btn" type="button"
                                    onclick="confirmDelete(<?php echo $artwork['artwork_id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>

                        <div class="artwork-info">
                            <h3><?php echo htmlspecialchars($artwork['title']); ?></h3>
                            <?php if ($artwork['exhibition_title']): ?>
                            <p class="exhibition-tag">
                                <i class="fas fa-palette"></i>
                                <?php echo htmlspecialchars($artwork['exhibition_title']); ?>
                            </p>
                            <?php endif; ?>
                            <p class="artwork-description">
                                <?php echo htmlspecialchars($artwork['description']); ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>

        <!-- Edit Modal -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Edit Artwork</h2>
                <form id="editForm">
                    <input type="hidden" id="edit_artwork_id" name="artwork_id">

                    <div class="form-group">
                        <label for="edit_title">Title</label>
                        <input type="text" id="edit_title" name="title" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea id="edit_description" name="description" rows="4"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="edit_exhibition">Exhibition</label>
                        <select id="edit_exhibition" name="exhibition_id">
                            <option value="">None (General Portfolio)</option>
                            <?php foreach ($exhibitions as $exhibition): ?>
                            <option value="<?php echo htmlspecialchars($exhibition['booking_id']); ?>">
                                <?php echo htmlspecialchars($exhibition['title']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_image">New Image (Optional)</label>
                        <div class="file-upload">
                            <input type="file" id="edit_image" name="image" accept="image/*">
                            <label for="edit_image" class="file-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Choose a new image</span>
                            </label>
                        </div>
                        <div id="editImagePreview" class="image-preview"></div>
                    </div>

                    <button type="submit" class="submit-btn">Update Artwork</button>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div id="deleteModal" class="modal">
            <div class="modal-content">
                <h2>Confirm Delete</h2>
                <p>Are you sure you want to delete this artwork? This action cannot be undone.</p>
                <div class="modal-actions">
                    <button id="confirmDelete" class="delete-btn">Delete</button>
                    <button id="cancelDelete" class="cancel-btn">Cancel</button>
                </div>
            </div>
        </div>

        <script src="../../assets/js/artist_portfolio.js"></script>
    </body>

</html>