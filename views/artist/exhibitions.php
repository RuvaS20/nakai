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

    // Fetch exhibitions
$stmt = $conn->prepare("
    SELECT eb.*, 
           es.name as space_name,
           g.name as gallery_name,
           ei.image_url
    FROM exhibition_bookings eb
    JOIN exhibition_spaces es ON eb.space_id = es.space_id
    JOIN galleries g ON es.gallery_id = g.gallery_id
    LEFT JOIN exhibition_images ei ON eb.booking_id = ei.exhibition_id
    WHERE eb.artist_id = ? 
    AND eb.booking_status != 'cancelled'  -- Add this line to exclude cancelled bookings
    ORDER BY eb.start_date DESC
");
$stmt->execute([$artist_id]);
$exhibitions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch available galleries for form
    $stmt = $conn->prepare("SELECT gallery_id, name FROM galleries");
    $stmt->execute();
    $galleries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("
    SELECT g.name, g.address, g.profile_image, g.website
    FROM galleries g
    ORDER BY g.name ASC
");
$stmt->execute();
$gallery_listings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch pending bookings
    $stmt = $conn->prepare("
        SELECT eb.*, es.name as space_name, g.name as gallery_name 
        FROM exhibition_bookings eb 
        JOIN exhibition_spaces es ON eb.space_id = es.space_id 
        JOIN galleries g ON es.gallery_id = g.gallery_id 
        WHERE eb.artist_id = ? AND eb.booking_status = 'pending'
        ORDER BY eb.created_at DESC
    ");
    $stmt->execute([$artist_id]);
    $pending_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $exhibitions = [];
    $galleries = [];
    $pending_bookings = [];
}
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Exhibitions - Nakai Nakai</title>
        <link href="https://fonts.googleapis.com/css2?family=Faculty+Glyphic&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Lato:wght@100;300;400;700;900&display=swap"
            rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link rel="stylesheet" href="../../assets/css/artist_exhibitions.css">
    </head>

    <body>
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
            <!-- Filter Section -->
            <div class="filter-container">
                <div class="filter-buttons">
                    <button class="filter-btn active" data-filter="all">All</button>
                    <button class="filter-btn" data-filter="current">Current</button>
                    <button class="filter-btn" data-filter="upcoming">Upcoming</button>
                    <button class="filter-btn" data-filter="past">Past</button>
                </div>
            </div>

            <!-- Exhibitions Grid -->
            <div class="exhibitions-grid">
                <?php foreach ($exhibitions as $exhibition): 
                $status = 'pending';
                if ($exhibition['booking_status'] === 'approved') {
                    $now = new DateTime();
                    $start = new DateTime($exhibition['start_date']);
                    $end = new DateTime($exhibition['end_date']);
                    
                    if ($now < $start) {
                        $status = 'upcoming';
                    } elseif ($now > $end) {
                        $status = 'past';
                    } else {
                        $status = 'current';
                    }
                }
            ?>
                <div class="exhibition-card" data-status="<?php echo $status; ?>">
                    <div class="exhibition-image">
                        <img src="../../<?php echo htmlspecialchars($exhibition['image_url'] ?? 'assets/images/placeholder.jpg'); ?>"
                            alt="<?php echo htmlspecialchars($exhibition['title']); ?>">
                    </div>
                    <div class="exhibition-content">
                        <div class="exhibition-status status-<?php echo $exhibition['booking_status']; ?>">
                            <?php echo ucfirst($exhibition['booking_status']); ?>
                        </div>
                        <h3 class="exhibition-title"><?php echo htmlspecialchars($exhibition['title']); ?></h3>
                        <div class="exhibition-details">
                            <p><i class="fas fa-map-marker-alt"></i>
                                <?php echo htmlspecialchars($exhibition['gallery_name']); ?></p>
                            <p><i class="fas fa-calendar"></i>
                                <?php echo date('M d', strtotime($exhibition['start_date'])) . ' - ' . date('M d, Y', strtotime($exhibition['end_date'])); ?>
                            </p>
                            <p><i class="fas fa-door-open"></i>
                                <?php echo htmlspecialchars($exhibition['space_name']); ?></p>
                        </div>

                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pending Bookings Section -->
            <section class="pending-bookings">
                <h2 class="section-title">Manage Pending Requests</h2>
                <?php if (empty($pending_bookings)): ?>
                <div class="no-requests">
                    <p>You have no pending requests, make a booking below.</p>
                </div>
                <?php else: ?>
                <div class="pending-grid">
                    <?php foreach ($pending_bookings as $booking): ?>
                    <div class="pending-card">
                        <div class="pending-content">
                            <h3><?php echo htmlspecialchars($booking['title']); ?></h3>
                            <div class="pending-details">
                                <p><i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($booking['gallery_name']); ?> -
                                    <?php echo htmlspecialchars($booking['space_name']); ?></p>
                                <p><i class="fas fa-calendar"></i>
                                    <?php echo date('M d', strtotime($booking['start_date'])) . ' - ' . date('M d, Y', strtotime($booking['end_date'])); ?>
                                </p>
                            </div>
                            <div class="pending-actions">
                                <button class="edit-btn" onclick="openEditModal(<?php echo $booking['booking_id']; ?>)">
                                    <i class="fas fa-edit"></i> Edit Details
                                </button>

                                <button class="cancel-btn"
                                    onclick="cancelBooking(<?php echo $booking['booking_id']; ?>)">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>

            <!-- Edit Modal -->
            <div id="editModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Edit Exhibition Details</h2>
                    <form id="editForm">
                        <input type="hidden" id="edit_booking_id" name="booking_id">
                        <div class="form-group">
                            <label for="edit_title">Exhibition Title</label>
                            <input type="text" id="edit_title" name="title" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_dates">Exhibition Dates</label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <input type="date" id="edit_start_date" name="start_date" required>
                                <input type="date" id="edit_end_date" name="end_date" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="edit_description">Description</label>
                            <textarea id="edit_description" name="description" required></textarea>
                        </div>
                        <button type="submit" class="submit-btn">Update Details</button>
                    </form>
                </div>
            </div>

            <!-- Booking Section -->
            <section class="booking-section">
                <div class="booking-form-container">
                    <h2 class="section-title">Book an Exhibition Space</h2>
                    <form class="booking-form">
                        <div class="form-group">
                            <label for="gallery">Gallery</label>
                            <select id="gallery" name="gallery" required>
                                <option value="">Select a gallery</option>
                                <?php foreach ($galleries as $gallery): ?>
                                <option value="<?php echo $gallery['gallery_id']; ?>">
                                    <?php echo htmlspecialchars($gallery['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="space">Exhibition Space</label>
                            <select id="space" name="space" required>
                                <option value="">Select a space</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="title">Exhibition Title</label>
                            <input type="text" id="title" name="title" required>
                        </div>
                        <div class="form-group">
                            <label for="dates">Exhibition Dates</label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <input type="date" id="start_date" name="start_date" required>
                                <input type="date" id="end_date" name="end_date" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" required></textarea>
                        </div>
                        <button type="submit" class="submit-btn">Submit Booking Request</button>
                    </form>
                </div>

                <div class="available-slots">
                    <h2 class="section-title">Our Partner Galleries</h2>
                    <div class="gallery-grid">
                        <?php foreach ($gallery_listings as $gallery): ?>
                        <div class="gallery-card">
                            <div class="gallery-image">
                                <?php if ($gallery['profile_image']): ?>
                                <img src="../../<?php echo htmlspecialchars($gallery['profile_image']); ?>"
                                    alt="<?php echo htmlspecialchars($gallery['name']); ?>">
                                <?php else: ?>
                                <img src="../../assets/images/placeholder.jpg" alt="Gallery placeholder">
                                <?php endif; ?>
                            </div>
                            <div class="gallery-info">
                                <h3><?php echo htmlspecialchars($gallery['name']); ?></h3>
                                <p class="gallery-address">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($gallery['address']); ?>
                                </p>
                                <?php if ($gallery['website']): ?>
                                <a href="<?php echo htmlspecialchars($gallery['website']); ?>" class="gallery-website"
                                    target="_blank">
                                    <i class="fas fa-globe"></i> Visit Website
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        </main>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get DOM elements
            const gallerySelect = document.getElementById('gallery');
            const spaceSelect = document.getElementById('space');
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');
            const modal = document.getElementById('editModal');
            const closeBtn = modal.querySelector('.close');
            const editForm = document.getElementById('editForm');

            // Set minimum date for booking to today
            const today = new Date().toISOString().split('T')[0];
            startDate.min = today;
            endDate.min = today;

            // Dynamic spaces loading
            gallerySelect.addEventListener('change', function() {
                const galleryId = this.value;
                if (!galleryId) {
                    spaceSelect.innerHTML = '<option value="">Select a space</option>';
                    return;
                }

                fetch(`../../actions/artist/get_spaces.php?gallery_id=${galleryId}`)
                    .then(response => response.json())
                    .then(spaces => {
                        spaceSelect.innerHTML = '<option value="">Select a space</option>';
                        spaces.forEach(space => {
                            spaceSelect.innerHTML +=
                                `<option value="${space.space_id}">${space.name}</option>`;
                        });
                    })
                    .catch(error => {
                        console.error('Error loading spaces:', error);
                        alert('Error loading spaces. Please try again.');
                    });
            });

            // Date validation
            startDate.addEventListener('change', function() {
                endDate.min = this.value;
                if (endDate.value && endDate.value < this.value) {
                    endDate.value = '';
                }
            });

            // Filter exhibitions
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const filter = this.dataset.filter;
                    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove(
                        'active'));
                    this.classList.add('active');

                    document.querySelectorAll('.exhibition-card').forEach(card => {
                        if (filter === 'all' || card.dataset.status === filter) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });

            // Booking form submission
            document.querySelector('.booking-form').addEventListener('submit', function(e) {
                e.preventDefault();

                // Validate dates
                const startDateValue = new Date(startDate.value);
                const endDateValue = new Date(endDate.value);

                if (startDateValue < new Date(today)) {
                    alert('Start date cannot be in the past');
                    return;
                }

                if (endDateValue < startDateValue) {
                    alert('End date must be after start date');
                    return;
                }

                const formData = new FormData(this);

                fetch('../../actions/artist/submit_booking.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Booking request submitted successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error submitting booking request. Please try again.');
                    });
            });

            // Edit form submission
            editForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                fetch('../../actions/artist/update_booking.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Booking updated successfully!');
                            modal.style.display = 'none';
                            location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error updating booking. Please try again.');
                    });
            });

            // Modal handling
            closeBtn.onclick = function() {
                modal.style.display = 'none';
            }

            window.onclick = function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            }
        });

        // Open edit modal and fetch booking details
        function openEditModal(bookingId) {
            const modal = document.getElementById('editModal');

            fetch(`../../actions/artist/get_booking.php?id=${bookingId}`)
                .then(response => response.json())
                .then(booking => {
                    document.getElementById('edit_booking_id').value = booking.booking_id;
                    document.getElementById('edit_title').value = booking.title;
                    document.getElementById('edit_start_date').value = booking.start_date;
                    document.getElementById('edit_end_date').value = booking.end_date;
                    document.getElementById('edit_description').value = booking.description;
                    modal.style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading booking details. Please try again.');
                });
        }



        // Cancel booking request
        function cancelBooking(bookingId) {
            if (confirm('Are you sure you want to cancel this booking request?')) {
                fetch('../../actions/artist/cancel_booking.php', {
                        method: 'POST',
                        body: JSON.stringify({
                            booking_id: bookingId
                        }),
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error canceling booking: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error canceling booking. Please try again.');
                    });
            }
        }
        </script>
    </body>

</html>