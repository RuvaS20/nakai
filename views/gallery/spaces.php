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
    
    // Get gallery_id
    $stmt = $conn->prepare("SELECT gallery_id FROM galleries WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $gallery = $stmt->fetch(PDO::FETCH_ASSOC);
    $gallery_id = $gallery['gallery_id'];

    // Get all spaces for this gallery
    $stmt = $conn->prepare("
        SELECT 
            es.*,
            COUNT(eb.booking_id) as active_bookings
        FROM exhibition_spaces es
        LEFT JOIN exhibition_bookings eb ON es.space_id = eb.space_id
            AND eb.booking_status = 'approved'
            AND eb.end_date >= CURRENT_DATE
        WHERE es.gallery_id = ?
        GROUP BY es.space_id
        ORDER BY es.name ASC
    ");
    $stmt->execute([$gallery_id]);
    $spaces = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $spaces = [];
}

// Get success/error messages if any
$success = isset($_SESSION['space_success']) ? $_SESSION['space_success'] : '';
$error = isset($_SESSION['space_error']) ? $_SESSION['space_error'] : '';
unset($_SESSION['space_success'], $_SESSION['space_error']);
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Exhibition Spaces - Nakai Nakai</title>
        <link href="https://fonts.googleapis.com/css2?family=Faculty+Glyphic&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Lato:wght@100;300;400;700;900&display=swap"
            rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link rel="stylesheet" href="../../assets/css/gallery_spaces.css">
    </head>

    <body>
        <nav class="gallery-nav">
            <a href="dashboard.php" class="nav-logo">Nakai Nakai</a>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="spaces.php" class="active">Spaces</a>
                <a href="requests.php">Requests</a>
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

            <div class="spaces-header">
                <h1>Exhibition Spaces</h1>
                <button type="button" class="add-space-btn" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Add New Space
                </button>
            </div>

            <?php if (empty($spaces)): ?>
            <div class="no-spaces">
                <i class="fas fa-door-closed fa-2x"></i>
                <p>No exhibition spaces found. Add your first space to start accepting bookings!</p>
            </div>
            <?php else: ?>
            <div class="spaces-grid">
                <?php foreach ($spaces as $space): ?>
                <div class="space-card status-<?php echo htmlspecialchars($space['status']); ?>">
                    <div class="space-header">
                        <h3><?php echo htmlspecialchars($space['name']); ?></h3>
                        <span class="status-badge">
                            <?php echo ucfirst(htmlspecialchars($space['status'])); ?>
                        </span>
                    </div>

                    <div class="space-details">
                        <p>
                            <i class="fas fa-users"></i>
                            Capacity: <?php echo htmlspecialchars($space['capacity']); ?> people
                        </p>
                        <p>
                            <i class="fas fa-dollar-sign"></i>
                            Rate: $<?php echo htmlspecialchars($space['daily_rate']); ?>/day
                        </p>
                        <p>
                            <i class="fas fa-calendar-check"></i>
                            Active Bookings: <?php echo $space['active_bookings']; ?>
                        </p>
                    </div>

                    <?php if (!empty($space['description'])): ?>
                    <div class="space-description">
                        <?php echo nl2br(htmlspecialchars($space['description'])); ?>
                    </div>
                    <?php endif; ?>

                    <div class="space-actions">
                        <button class="action-btn edit" onclick="openEditModal(<?php echo $space['space_id']; ?>)">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <?php if ($space['active_bookings'] == 0): ?>
                        <button class="action-btn delete" onclick="confirmDelete(<?php echo $space['space_id']; ?>)">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                        <?php endif; ?>
                        <button class="action-btn status" onclick="openStatusModal(<?php echo $space['space_id']; ?>)">
                            <i class="fas fa-toggle-on"></i> Change Status
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>

        <!-- Add Space Modal -->
        <div id="addModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Add New Exhibition Space</h2>
                <form id="addSpaceForm">
                    <div class="form-group">
                        <label for="name">Space Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="capacity">Capacity (people) <span class="required">*</span></label>
                        <input type="number" id="capacity" name="capacity" min="1" required>
                    </div>

                    <div class="form-group">
                        <label for="daily_rate">Daily Rate ($) <span class="required">*</span></label>
                        <input type="number" id="daily_rate" name="daily_rate" min="0" step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4"></textarea>
                    </div>

                    <div class="modal-actions">
                        <button type="submit" class="submit-btn">Add Space</button>
                        <button type="button" class="cancel-btn" onclick="closeAddModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Space Modal -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Edit Exhibition Space</h2>
                <form id="editSpaceForm">
                    <input type="hidden" id="edit_space_id" name="space_id">
                    <!-- Form fields will be populated by JavaScript -->
                </form>
            </div>
        </div>

        <!-- Status Change Modal -->
        <div id="statusModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Change Space Status</h2>
                <form id="statusForm">
                    <input type="hidden" id="status_space_id" name="space_id">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" required>
                            <option value="available">Available</option>
                            <option value="maintenance">Under Maintenance</option>
                            <option value="booked">Booked</option>
                        </select>
                    </div>
                    <div class="modal-actions">
                        <button type="submit" class="submit-btn">Update Status</button>
                        <button type="button" class="cancel-btn" onclick="closeStatusModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <script src="../../assets/js/gallery_spaces.js"></script>
    </body>

</html>