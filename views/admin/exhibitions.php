<?php
session_start();
require_once '../../db/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

// Initialize variables with default values
$stats = [
    'total' => 0,
    'current' => 0,
    'pending' => 0
];
$exhibitions = [];
$bookings = [];
$error = '';
$success = '';
$exhibition_filter = isset($_GET['exhibition_status']) ? $_GET['exhibition_status'] : 'all';
$booking_filter = isset($_GET['booking_status']) ? $_GET['booking_status'] : 'all';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get statistics
    $stmt = $conn->prepare("
        SELECT 
    SUM(CASE 
        WHEN booking_status != 'cancelled' 
        THEN 1 
        ELSE 0 
    END) as total,
            SUM(CASE 
                WHEN booking_status = 'approved' 
                AND start_date <= CURRENT_DATE 
                AND end_date >= CURRENT_DATE 
                THEN 1 
                ELSE 0 
            END) as current,
            SUM(CASE 
                WHEN booking_status = 'pending' 
                THEN 1 
                ELSE 0 
            END) as pending
        FROM exhibition_bookings
    ");
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no results, keep default values
    if (!$stats) {
        $stats = [
            'total' => 0,
            'current' => 0,
            'pending' => 0
        ];
    }

    // Get exhibitions with related info
    $exhibition_query = "
        SELECT 
            eb.booking_id,
            eb.title,
            eb.description,
            eb.start_date,
            eb.end_date,
            eb.booking_status,
            a.name as artist_name,
            g.name as gallery_name,
            es.name as space_name,
            ei.image_url
        FROM exhibition_bookings eb
        JOIN artists a ON eb.artist_id = a.artist_id
        JOIN exhibition_spaces es ON eb.space_id = es.space_id
        JOIN galleries g ON es.gallery_id = g.gallery_id
        LEFT JOIN exhibition_images ei ON eb.booking_id = ei.exhibition_id
        WHERE eb.booking_status = 'approved'";

    // Add status filter
    if ($exhibition_filter !== 'all') {
        switch ($exhibition_filter) {
            case 'current':
                $exhibition_query .= " AND eb.start_date <= CURRENT_DATE AND eb.end_date >= CURRENT_DATE";
                break;
            case 'upcoming':
                $exhibition_query .= " AND eb.start_date > CURRENT_DATE";
                break;
            case 'past':
                $exhibition_query .= " AND eb.end_date < CURRENT_DATE";
                break;
        }
    }

    $exhibition_query .= " ORDER BY eb.start_date DESC";
    
    $stmt = $conn->prepare($exhibition_query);
    $stmt->execute();
    $exhibitions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get booking requests
    $booking_query = "
        SELECT 
            eb.booking_id,
            eb.title,
            eb.description,
            eb.start_date,
            eb.end_date,
            eb.booking_status,
            eb.created_at,
            a.name as artist_name,
            a.email as artist_email,
            g.name as gallery_name,
            es.name as space_name
        FROM exhibition_bookings eb
        JOIN artists a ON eb.artist_id = a.artist_id
        JOIN exhibition_spaces es ON eb.space_id = es.space_id
        JOIN galleries g ON es.gallery_id = g.gallery_id
        WHERE 1=1";

    if ($booking_filter !== 'all') {
        $booking_query .= " AND eb.booking_status = ?";
    }

    $booking_query .= " ORDER BY eb.created_at DESC";
    
    $stmt = $conn->prepare($booking_query);
    if ($booking_filter !== 'all') {
        $stmt->execute([$booking_filter]);
    } else {
        $stmt->execute();
    }
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $error = "An error occurred while loading exhibitions.";
}

// Get success/error messages if any
$success = isset($_SESSION['admin_success']) ? $_SESSION['admin_success'] : '';
$error = isset($_SESSION['admin_error']) ? $_SESSION['admin_error'] : '';
unset($_SESSION['admin_success'], $_SESSION['admin_error']);
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Exhibitions & Bookings - Admin Dashboard</title>
        <link href="https://fonts.googleapis.com/css2?family=Faculty+Glyphic&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Lato:wght@100;300;400;700;900&display=swap"
            rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link rel="stylesheet" href="../../assets/css/admin_exhibitions.css">
    </head>

    <body>
        <nav class="admin-nav">
            <a href="dashboard.php" class="nav-logo">Nakai Nakai Admin</a>
            <div class="nav-links">
                <a href="dashboard.php">Dashboard</a>
                <a href="exhibitions.php" class="active">Exhibitions & Bookings</a>
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

            <!-- Stats Section -->
            <section class="stats-section">
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Exhibitions</h3>
                        <p><?php echo $stats['total']; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Current Exhibitions</h3>
                        <p><?php echo $stats['current']; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Pending Requests</h3>
                        <p><?php echo $stats['pending']; ?></p>
                    </div>
                </div>
            </section>

            <!-- Exhibitions Section -->
            <section class="exhibitions-section">
                <div class="section-header">
                    <h2>Exhibitions Overview</h2>
                    <div class="filter-buttons">
                        <a href="?exhibition_status=all"
                            class="filter-btn <?php echo $exhibition_filter === 'all' ? 'active' : ''; ?>">All</a>
                        <a href="?exhibition_status=current"
                            class="filter-btn <?php echo $exhibition_filter === 'current' ? 'active' : ''; ?>">Current</a>
                        <a href="?exhibition_status=upcoming"
                            class="filter-btn <?php echo $exhibition_filter === 'upcoming' ? 'active' : ''; ?>">Upcoming</a>
                        <a href="?exhibition_status=past"
                            class="filter-btn <?php echo $exhibition_filter === 'past' ? 'active' : ''; ?>">Past</a>
                    </div>
                </div>

                <div class="exhibitions-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Exhibition</th>
                                <th>Artist</th>
                                <th>Gallery</th>
                                <th>Space</th>
                                <th>Dates</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($exhibitions as $exhibition): 
                            $status = 'past';
                            $now = new DateTime();
                            $start = new DateTime($exhibition['start_date']);
                            $end = new DateTime($exhibition['end_date']);
                            
                            if ($now >= $start && $now <= $end) {
                                $status = 'current';
                            } elseif ($now < $start) {
                                $status = 'upcoming';
                            }
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($exhibition['title']); ?></td>
                                <td><?php echo htmlspecialchars($exhibition['artist_name']); ?></td>
                                <td><?php echo htmlspecialchars($exhibition['gallery_name']); ?></td>
                                <td><?php echo htmlspecialchars($exhibition['space_name']); ?></td>
                                <td>
                                    <?php 
                                        echo $start->format('M d') . ' - ' . $end->format('M d, Y');
                                    ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $status; ?>">
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                                <td>
                                    <button onclick="viewExhibition(<?php echo $exhibition['booking_id']; ?>)"
                                        class="view-btn">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($status !== 'past'): ?>
                                    <button onclick="cancelExhibition(<?php echo $exhibition['booking_id']; ?>)"
                                        class="cancel-btn">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Booking Requests Section -->
            <section class="bookings-section">
                <div class="section-header">
                    <h2>Booking Requests</h2>
                    <div class="filter-buttons">
                        <a href="?booking_status=all"
                            class="filter-btn <?php echo $booking_filter === 'all' ? 'active' : ''; ?>">All</a>
                        <a href="?booking_status=pending"
                            class="filter-btn <?php echo $booking_filter === 'pending' ? 'active' : ''; ?>">Pending</a>
                        <a href="?booking_status=approved"
                            class="filter-btn <?php echo $booking_filter === 'approved' ? 'active' : ''; ?>">Approved</a>
                        <a href="?booking_status=rejected"
                            class="filter-btn <?php echo $booking_filter === 'rejected' ? 'active' : ''; ?>">Rejected</a>
                    </div>
                </div>

                <div class="bookings-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Exhibition</th>
                                <th>Artist</th>
                                <th>Gallery Space</th>
                                <th>Requested Dates</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['title']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($booking['artist_name']); ?>
                                    <small><?php echo htmlspecialchars($booking['artist_email']); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($booking['gallery_name']); ?> -
                                    <?php echo htmlspecialchars($booking['space_name']); ?>
                                </td>
                                <td>
                                    <?php 
                                        $start = new DateTime($booking['start_date']);
                                        $end = new DateTime($booking['end_date']);
                                        echo $start->format('M d') . ' - ' . $end->format('M d, Y');
                                    ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $booking['booking_status']; ?>">
                                        <?php echo ucfirst($booking['booking_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button onclick="viewBooking(<?php echo $booking['booking_id']; ?>)"
                                        class="view-btn">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($booking['booking_status'] === 'pending'): ?>
                                    <button
                                        onclick="updateBookingStatus(<?php echo $booking['booking_id']; ?>, 'approved')"
                                        class="approve-btn">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button
                                        onclick="updateBookingStatus(<?php echo $booking['booking_id']; ?>, 'rejected')"
                                        class="reject-btn">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>

        <!-- View Exhibition Modal -->
        <div id="exhibitionModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <div id="exhibitionDetails"></div>
            </div>
        </div>

        <!-- View Booking Modal -->
        <div id="bookingModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <div id="bookingDetails"></div>
            </div>
        </div>

        <script src="../../assets/js/admin_exhibitions.js"></script>
    </body>

</html>