document.addEventListener('DOMContentLoaded', function () {
    // Modal Elements
    const exhibitionModal = document.getElementById('exhibitionModal');
    const bookingModal = document.getElementById('bookingModal');
    const closeBtns = document.querySelectorAll('.close');

    // Modal Close Functions
    closeBtns.forEach(btn => {
        btn.onclick = function () {
            const modal = this.closest('.modal');
            closeModal(modal);
        }
    });

    // Close modal when clicking outside
    window.onclick = function (event) {
        if (event.target.classList.contains('modal')) {
            closeModal(event.target);
        }
    }

    function closeModal(modal) {
        modal.style.display = 'none';
    }

    // View Exhibition Details
    window.viewExhibition = function (bookingId) {
        fetch(`../../actions/admin/get_exhibition.php?id=${bookingId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const exhibition = data.exhibition;
                    const start = new Date(exhibition.start_date).toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric'
                    });
                    const end = new Date(exhibition.end_date).toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric'
                    });

                    document.getElementById('exhibitionDetails').innerHTML = `
                        <h2>${exhibition.title}</h2>
                        <div class="detail-content">
                            <section class="detail-section">
                                <h3>Exhibition Details</h3>
                                <p><strong>Status:</strong> ${exhibition.status}</p>
                                <p><strong>Dates:</strong> ${start} - ${end}</p>
                                <p><strong>Description:</strong> ${exhibition.description || 'No description provided'}</p>
                            </section>

                            <section class="detail-section">
                                <h3>Artist Information</h3>
                                <p><strong>Name:</strong> ${exhibition.artist_name}</p>
                                <p><strong>Email:</strong> ${exhibition.artist_email}</p>
                                <p><strong>Phone:</strong> ${exhibition.artist_phone || 'Not provided'}</p>
                            </section>

                            <section class="detail-section">
                                <h3>Gallery Information</h3>
                                <p><strong>Gallery:</strong> ${exhibition.gallery_name}</p>
                                <p><strong>Space:</strong> ${exhibition.space_name}</p>
                                <p><strong>Address:</strong> ${exhibition.gallery_address}</p>
                            </section>
                        </div>
                    `;
                    exhibitionModal.style.display = 'block';
                } else {
                    showNotification(data.message || 'Error loading exhibition details', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error loading exhibition details', 'error');
            });
    }

    // View Booking Details
    window.viewBooking = function (bookingId) {
        fetch(`../../actions/admin/get_booking.php?id=${bookingId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const booking = data.booking;
                    const start = new Date(booking.start_date).toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric'
                    });
                    const end = new Date(booking.end_date).toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric'
                    });

                    document.getElementById('bookingDetails').innerHTML = `
                        <h2>Booking Request Details</h2>
                        <div class="detail-content">
                            <section class="detail-section">
                                <h3>Exhibition Information</h3>
                                <p><strong>Title:</strong> ${booking.title}</p>
                                <p><strong>Dates:</strong> ${start} - ${end}</p>
                                <p><strong>Status:</strong> 
                                    <span class="status-badge status-${booking.booking_status}">
                                        ${booking.booking_status.charAt(0).toUpperCase() + booking.booking_status.slice(1)}
                                    </span>
                                </p>
                                <p><strong>Description:</strong> ${booking.description || 'No description provided'}</p>
                            </section>

                            <section class="detail-section">
                                <h3>Artist Information</h3>
                                <p><strong>Name:</strong> ${booking.artist_name}</p>
                                <p><strong>Email:</strong> ${booking.artist_email}</p>
                                <p><strong>Phone:</strong> ${booking.artist_phone || 'Not provided'}</p>
                            </section>

                            <section class="detail-section">
                                <h3>Gallery Information</h3>
                                <p><strong>Gallery:</strong> ${booking.gallery_name}</p>
                                <p><strong>Space:</strong> ${booking.space_name}</p>
                                <p><strong>Capacity:</strong> ${booking.space_capacity} people</p>
                                <p><strong>Daily Rate:</strong> $${booking.daily_rate}</p>
                            </section>

                            ${booking.booking_status === 'pending' ? `
                                <div class="modal-actions">
                                    <button onclick="updateBookingStatus(${booking.booking_id}, 'approved')" class="approve-btn">
                                        Approve Request
                                    </button>
                                    <button onclick="updateBookingStatus(${booking.booking_id}, 'rejected')" class="reject-btn">
                                        Reject Request
                                    </button>
                                </div>
                            ` : ''}
                        </div>
                    `;
                    bookingModal.style.display = 'block';
                } else {
                    showNotification(data.message || 'Error loading booking details', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error loading booking details', 'error');
            });
    }

    // Update Booking Status
    window.updateBookingStatus = function (bookingId, status) {
        if (!confirm(`Are you sure you want to ${status} this booking request?`)) {
            return;
        }

        fetch('../../actions/admin/update_booking_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                booking_id: bookingId,
                status: status
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(`Booking ${status} successfully`, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification(error.message || `Error ${status}ing booking`, 'error');
            });
    }

    // Cancel Exhibition
    window.cancelExhibition = function (bookingId) {
        if (!confirm('Are you sure you want to cancel this exhibition? This action cannot be undone.')) {
            return;
        }

        fetch('../../actions/admin/cancel_exhibition.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                booking_id: bookingId
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Exhibition cancelled successfully', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification(error.message || 'Error cancelling exhibition', 'error');
            });
    }

    // Notification System
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i>
            ${message}
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
});
