document.addEventListener('DOMContentLoaded', function () {
    // Initialize file upload previews
    initializeFileUpload('image', 'imagePreview');
    initializeFileUpload('edit_image', 'editImagePreview');

    // Initialize filter buttons
    initializeFilters();
});

// File upload preview handling
function initializeFileUpload(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);

    input.addEventListener('change', function () {
        preview.innerHTML = '';
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                preview.appendChild(img);
            }
            reader.readAsDataURL(this.files[0]);
        }
    });
}

// Filter functionality
function initializeFilters() {
    const filterBtns = document.querySelectorAll('.filter-btn');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const filter = this.dataset.filter;

            // Update active button
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            // Filter items
            const items = document.querySelectorAll('.artwork-item');
            items.forEach(item => {
                const shouldShow = filter === 'all' || item.dataset.category === filter;
                item.style.display = shouldShow ? 'block' : 'none';
            });
        });
    });
}

// Upload form handling
document.getElementById('uploadForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('../../actions/artist/upload_artwork.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reset form and preview
                this.reset();
                document.getElementById('imagePreview').innerHTML = '';

                // Show success message
                showNotification('Artwork uploaded successfully!', 'success');

                // Reload page to show new artwork
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('Error uploading artwork. Please try again.', 'error');
            console.error('Error:', error);
        });
});

// Edit modal handling
const editModal = document.getElementById('editModal');
const closeEditModal = editModal.querySelector('.close');

function openEditModal(artworkId) {
    // Fetch artwork details
    fetch(`../../actions/artist/get_artwork.php?id=${artworkId}`)
        .then(response => response.json())
        .then(artwork => {
            // Populate form fields
            document.getElementById('edit_artwork_id').value = artwork.artwork_id;
            document.getElementById('edit_title').value = artwork.title;
            document.getElementById('edit_description').value = artwork.description;
            document.getElementById('edit_exhibition').value = artwork.exhibition_id || '';

            // Show modal
            editModal.style.display = 'block';
        })
        .catch(error => {
            showNotification('Error loading artwork details.', 'error');
            console.error('Error:', error);
        });
}

// Edit form submission
document.getElementById('editForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('../../actions/artist/update_artwork.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal
                editModal.style.display = 'none';

                // Show success message
                showNotification('Artwork updated successfully!', 'success');

                // Reload page to show updates
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('Error updating artwork. Please try again.', 'error');
            console.error('Error:', error);
        });
});

// Delete modal handling
const deleteModal = document.getElementById('deleteModal');
let artworkToDelete = null;

function confirmDelete(artworkId) {
    artworkToDelete = artworkId;
    deleteModal.style.display = 'block';
}

document.getElementById('confirmDelete').addEventListener('click', function () {
    if (!artworkToDelete) return;

    fetch('../../actions/artist/delete_artwork.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            artwork_id: artworkToDelete
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal
                deleteModal.style.display = 'none';

                // Show success message
                showNotification('Artwork deleted successfully!', 'success');

                // Remove artwork from grid and reload page
                const artwork = document.querySelector(`[data-id="${artworkToDelete}"]`);
                if (artwork) {
                    artwork.remove();
                }
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('Error deleting artwork. Please try again.', 'error');
            console.error('Error:', error);
        });
});

// Modal close handlers
document.getElementById('cancelDelete').addEventListener('click', function () {
    deleteModal.style.display = 'none';
    artworkToDelete = null;
});

closeEditModal.addEventListener('click', function () {
    editModal.style.display = 'none';
});

// Close modals when clicking outside
window.addEventListener('click', function (event) {
    if (event.target === editModal) {
        editModal.style.display = 'none';
    }
    if (event.target === deleteModal) {
        deleteModal.style.display = 'none';
        artworkToDelete = null;
    }
});

// Notification system
function showNotification(message, type = 'success') {
    // Create notification element if it doesn't exist
    let notification = document.querySelector('.notification');
    if (!notification) {
        notification = document.createElement('div');
        notification.className = 'notification';
        document.body.appendChild(notification);
    }

    // Set notification content and style
    notification.textContent = message;
    notification.className = `notification ${type}`;
    notification.style.display = 'block';

    // Hide notification after 3 seconds
    setTimeout(() => {
        notification.style.display = 'none';
    }, 3000);
}
