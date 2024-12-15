document.addEventListener('DOMContentLoaded', function () {
    // Modal Elements
    const createUserModal = document.getElementById('createUserModal');
    const featureModal = document.getElementById('featureModal');
    const closeBtns = document.querySelectorAll('.close');

    // Form Elements
    const createUserForm = document.getElementById('createUserForm');
    const featureForm = document.getElementById('featureForm');

    // Modal Open Functions
    window.openCreateUserModal = function () {
        createUserModal.style.display = 'block';
    }

    window.openFeatureModal = function () {
        featureModal.style.display = 'block';
        loadAvailableExhibitions();
    }

    // Modal Close Functions
    closeBtns.forEach(btn => {
        btn.onclick = function () {
            const modal = this.closest('.modal');
            closeModal(modal);
        }
    });

    window.onclick = function (event) {
        if (event.target.classList.contains('modal')) {
            closeModal(event.target);
        }
    }

    function closeModal(modal) {
        modal.style.display = 'none';
        if (modal.querySelector('form')) {
            modal.querySelector('form').reset();
        }
    }

    // Create User Form Submission
    createUserForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('../../actions/admin/create_user.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('User created successfully', 'success');
                    closeModal(createUserModal);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification(error.message || 'Error creating user', 'error');
            });
    });

    // Delete User Function
    window.deleteUser = function (userId) {
        if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            return;
        }

        fetch('../../actions/admin/delete_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ user_id: userId })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('User deleted successfully', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification(error.message || 'Error deleting user', 'error');
            });
    }

    // Load Available Exhibitions for Feature Modal
    function loadAvailableExhibitions() {
        fetch('../../actions/admin/get_available_exhibitions.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const select = document.getElementById('exhibition');
                    select.innerHTML = '<option value="">Select Exhibition</option>';
                    data.exhibitions.forEach(exhibition => {
                        select.innerHTML += `
                            <option value="${exhibition.booking_id}">
                                ${exhibition.title} - ${exhibition.artist_name}
                            </option>
                        `;
                    });
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error loading exhibitions', 'error');
            });
    }

    // Feature Exhibition Form Submission
    featureForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('../../actions/admin/feature_exhibition.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Exhibition featured successfully', 'success');
                    closeModal(featureModal);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification(error.message || 'Error featuring exhibition', 'error');
            });
    });

    // Remove Featured Exhibition
    window.removeFeature = function (featureId) {
        if (!confirm('Are you sure you want to remove this exhibition from featured?')) {
            return;
        }

        fetch('../../actions/admin/remove_feature.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ feature_id: featureId })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Exhibition removed from featured', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification(error.message || 'Error removing feature', 'error');
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
