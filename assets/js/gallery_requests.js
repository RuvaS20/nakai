// Function to handle request approval
function approveRequest(bookingId) {
    if (confirm('Are you sure you want to approve this request?')) {
        updateBookingStatus(bookingId, 'approved');
    }
}

// Function to handle request rejection
function rejectRequest(bookingId) {
    if (confirm('Are you sure you want to reject this request?')) {
        updateBookingStatus(bookingId, 'rejected');
    }
}

// Function to handle booking cancellation
function cancelBooking(bookingId) {
    if (confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
        updateBookingStatus(bookingId, 'cancelled');
    }
}

// Function to update booking status
function updateBookingStatus(bookingId, status) {
    fetch('../../actions/gallery/update_booking.php', {
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
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
}

// Function to view booking details
function viewDetails(bookingId) {
    const modal = document.getElementById('detailsModal');
    const modalContent = document.getElementById('modalContent');

    fetch(`../../actions/gallery/get_booking.php?id=${bookingId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modalContent.innerHTML = `
                    <h2>${data.booking.title}</h2>
                    <div class="detail-content">
                        <!-- Artist Information -->
                        <section class="detail-section">
                            <h3>Artist Information</h3>
                            <p><strong>Name:</strong> ${data.booking.artist_name}</p>
                            <p><strong>Email:</strong> ${data.booking.artist_email}</p>
                            <p><strong>Phone:</strong> ${data.booking.artist_phone || 'Not provided'}</p>
                        </section>

                        <!-- Exhibition Details -->
                        <section class="detail-section">
                            <h3>Exhibition Details</h3>
                            <p><strong>Space:</strong> ${data.booking.space_name}</p>
                            <p><strong>Dates:</strong> ${data.booking.formatted_dates}</p>
                            <p><strong>Status:</strong> <span class="status-${data.booking.booking_status}">
                                ${data.booking.booking_status.toUpperCase()}</span></p>
                        </section>

                        <!-- Description -->
                        <section class="detail-section">
                            <h3>Description</h3>
                            <p>${data.booking.description}</p>
                        </section>
                    </div>
                `;
                modal.style.display = 'block';
            } else {
                alert('Error loading booking details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while loading the details');
        });
}

// Load requests when the page loads
document.addEventListener('DOMContentLoaded', function() {
    // Modal handling
    const modal = document.getElementById('detailsModal');
    const closeBtn = document.querySelector('.close');

    closeBtn.onclick = function() {
        modal.style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    }

    // Get current status from URL or default to 'all'
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status') || 'all';

    // Fetch requests
    fetch(`../../actions/gallery/get_requests.php?status=${status}`)
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error('Network response was not ok: ' + text);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const requestsGrid = document.querySelector('.requests-grid');
                if (!data.requests || data.requests.length === 0) {
                    requestsGrid.innerHTML = `
                        <div class="no-requests">
                            <i class="fas fa-inbox fa-2x"></i>
                            <p>No requests found for the selected filter.</p>
                        </div>
                    `;
                    return;
                }

                requestsGrid.innerHTML = data.requests.map(request => `
    <div class="request-card status-${request.booking_status}">
        <div class="request-header">
            <div class="artist-info">
                ${request.artist_image
                        ? `<img src="../../${request.artist_image}" alt="${request.artist_name}" class="artist-profile-image">`
                        : '<i class="fas fa-user-circle"></i>'
                    }
                <div>
                    <h3>${request.title}</h3>
                    <p>by ${request.artist_name}</p>
                </div>
            </div>
            <span class="status-badge">
                ${request.booking_status.charAt(0).toUpperCase() + request.booking_status.slice(1)}
            </span>
        </div>

        <div class="request-details">
            <div class="detail-item">
                <i class="fas fa-door-open"></i>
                <span>${request.space_name}</span>
            </div>
            <div class="detail-item">
                <i class="fas fa-calendar"></i>
                <span>${request.formatted_dates.start} - ${request.formatted_dates.end}</span>
            </div>
            <div class="detail-item">
                <i class="fas fa-users"></i>
                <span>Capacity: ${request.capacity}</span>
            </div>
            <div class="detail-item">
                <i class="fas fa-dollar-sign"></i>
                <span>Rate: $${request.daily_rate}/day</span>
            </div>
        </div>

                        <div class="request-description">
                            ${request.description || 'No description provided.'}
                        </div>

                        <div class="request-actions">
                            ${request.booking_status === 'pending' ? `
                                <button class="action-btn approve" onclick="approveRequest(${request.booking_id})">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                <button class="action-btn reject" onclick="rejectRequest(${request.booking_id})">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            ` : ''}
                            ${request.booking_status === 'approved' ? `
                                <button class="action-btn cancel" onclick="cancelBooking(${request.booking_id})">
                                    <i class="fas fa-ban"></i> Cancel Booking
                                </button>
                            ` : ''}
                            <button class="action-btn view" onclick="viewDetails(${request.booking_id})">
                                <i class="fas fa-eye"></i> View Details
                            </button>
                        </div>
                    </div>
                `).join('');
            } else {
                throw new Error(data.message || 'Unknown error occurred');
            }
        })
        .catch(error => {
            console.error('Error details:', error);
            const requestsGrid = document.querySelector('.requests-grid');
            requestsGrid.innerHTML = `
                <div class="no-requests error">
                    <i class="fas fa-exclamation-circle fa-2x"></i>
                    <p>Error loading requests: ${error.message}</p>
                    <button onclick="location.reload()" class="action-btn">
                        <i class="fas fa-redo"></i> Try Again
                    </button>
                </div>
            `;
        });
});
