<?php
session_start();
require_once '../../db/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get total counts
    // Replace the existing stats query with this:
$stmt = $conn->prepare("
    SELECT 
        CASE role 
            WHEN 'artist' THEN 'artist'
            WHEN 'gallery' THEN 'gallery'
        END as role,
        COUNT(*) as count
    FROM users 
    WHERE role != 'admin'
    GROUP BY role
");
$stmt->execute();
$user_counts = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $user_counts[$row['role']] = $row['count'];
}
    
    // Get current exhibitions count
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM exhibition_bookings 
        WHERE booking_status = 'approved'
        AND start_date <= CURRENT_DATE 
        AND end_date >= CURRENT_DATE
    ");
    $stmt->execute();
    $current_exhibitions = $stmt->fetchColumn();

    // Get upcoming exhibitions count
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM exhibition_bookings 
        WHERE booking_status = 'approved'
        AND start_date > CURRENT_DATE
    ");
    $stmt->execute();
    $upcoming_exhibitions = $stmt->fetchColumn();

    // Get pending bookings count
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM exhibition_bookings 
        WHERE booking_status = 'pending'
    ");
    $stmt->execute();
    $pending_bookings = $stmt->fetchColumn();

    // Get featured exhibitions
    $stmt = $conn->prepare("
        SELECT fe.*, eb.booking_status
        FROM featured_exhibitions fe
        LEFT JOIN exhibition_bookings eb ON fe.title = eb.title
        WHERE fe.is_active = 1
        ORDER BY fe.display_order ASC
    ");
    $stmt->execute();
    $featured_exhibitions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all users
    $user_type = isset($_GET['type']) ? $_GET['type'] : 'all';
    $query = "
        SELECT u.user_id, u.email, u.role, u.created_at,
        CASE 
            WHEN u.role = 'artist' THEN a.name
            WHEN u.role = 'gallery' THEN g.name
        END as name,
        CASE 
            WHEN u.role = 'artist' THEN (
                SELECT COUNT(*) FROM exhibition_bookings eb
                WHERE eb.artist_id = a.artist_id
            )
            WHEN u.role = 'gallery' THEN (
                SELECT COUNT(*) FROM exhibition_bookings eb
                JOIN exhibition_spaces es ON eb.space_id = es.space_id
                WHERE es.gallery_id = g.gallery_id
            )
        END as booking_count
        FROM users u
        LEFT JOIN artists a ON u.user_id = a.user_id
        LEFT JOIN galleries g ON u.user_id = g.user_id
        WHERE u.role != 'admin'";

    if ($user_type !== 'all') {
        $query .= " AND u.role = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$user_type]);
    } else {
        $stmt = $conn->prepare($query);
        $stmt->execute();
    }
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $error = "An error occurred while loading the dashboard.";
}

// Get any success/error messages
$success = isset($_SESSION['admin_success']) ? $_SESSION['admin_success'] : '';
$error = isset($_SESSION['admin_error']) ? $_SESSION['admin_error'] : '';
unset($_SESSION['admin_success'], $_SESSION['admin_error']);
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Dashboard - Nakai Nakai</title>
        <link href="https://fonts.googleapis.com/css2?family=Faculty+Glyphic&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Lato:wght@100;300;400;700;900&display=swap"
            rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link rel="stylesheet" href="../../assets/css/admin_dashboard.css">
    </head>

    <body>
        <nav class="admin-nav">
            <a href="dashboard.php" class="nav-logo">Nakai Nakai Admin</a>
            <div class="nav-links">
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="exhibitions.php">Exhibitions & Bookings</a>
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

            <!-- Statistics Section -->
            <section class="stats-section">
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Users</h3>
                        <p>Artists: <?php echo $user_counts['artist'] ?? 0; ?></p>
                        <p>Galleries: <?php echo $user_counts['gallery'] ?? 0; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Exhibitions</h3>
                        <p>Current: <?php echo $current_exhibitions; ?></p>
                        <p>Upcoming: <?php echo $upcoming_exhibitions; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Booking Requests</h3>
                        <p>Pending: <?php echo $pending_bookings; ?></p>
                    </div>
                </div>
            </section>

            <!-- Users Section -->
            <section class="users-section">
                <div class="section-header">
                    <h2>User Management</h2>
                    <div class="actions">
                        <button onclick="openCreateUserModal()" class="create-btn">
                            <i class="fas fa-plus"></i> Create User
                        </button>
                        <div class="filter-buttons">
                            <a href="?type=all"
                                class="filter-btn <?php echo $user_type === 'all' ? 'active' : ''; ?>">All</a>
                            <a href="?type=artist"
                                class="filter-btn <?php echo $user_type === 'artist' ? 'active' : ''; ?>">Artists</a>
                            <a href="?type=gallery"
                                class="filter-btn <?php echo $user_type === 'gallery' ? 'active' : ''; ?>">Galleries</a>
                        </div>
                    </div>
                </div>

                <div class="users-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Type</th>
                                <th>Join Date</th>
                                <th>Bookings</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($user['role'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td><?php echo $user['booking_count']; ?></td>
                                <td>
                                    <button onclick="deleteUser(<?php echo $user['user_id']; ?>)" class="delete-btn">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Featured Exhibitions Section -->
            <section class="featured-section">
                <div class="section-header">
                    <h2>Featured Exhibitions</h2>
                    <button onclick="openFeatureModal()" class="feature-btn">
                        <i class="fas fa-plus"></i> Add Featured Exhibition
                    </button>
                </div>

                <div class="featured-grid">
                    <?php foreach ($featured_exhibitions as $exhibition): ?>
                    <div class="featured-card">
                        <h3><?php echo htmlspecialchars($exhibition['title']); ?></h3>
                        <p class="status">
                            <?php echo ucfirst(htmlspecialchars($exhibition['booking_status'] ?? 'pending')); ?></p>
                        <p class="dates">
                            <?php 
                            $start = new DateTime($exhibition['start_date']);
                            $end = new DateTime($exhibition['end_date']);
                            echo $start->format('M d') . ' - ' . $end->format('M d, Y');
                            ?>
                        </p>
                        <button onclick="removeFeature(<?php echo $exhibition['id']; ?>)" class="remove-btn">
                            <i class="fas fa-times"></i> Remove
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>

        <!-- Create User Modal -->
        <div id="createUserModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Create New User</h2>
                <form id="createUserForm">
                    <div class="form-group">
                        <label for="user_type">User Type</label>
                        <select id="user_type" name="role" required>
                            <option value="artist">Artist</option>
                            <option value="gallery">Gallery</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <button type="submit">Create User</button>
                </form>
            </div>
        </div>

        <!-- Feature Exhibition Modal -->
        <div id="featureModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Add Featured Exhibition</h2>
                <form id="featureForm">
                    <div class="form-group">
                        <label for="exhibition">Exhibition</label>
                        <select id="exhibition" name="exhibition_id" required>
                            <!-- Will be populated via AJAX -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="display_order">Display Order</label>
                        <input type="number" id="display_order" name="display_order" min="1" required>
                    </div>
                    <button type="submit">Add to Featured</button>
                </form>
            </div>
        </div>

        <script src="../../assets/js/admin_dashboard.js"></script>
    </body>

</html>