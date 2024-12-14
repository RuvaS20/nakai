// Modal handling
const addModal = document.getElementById('addModal');
const editModal = document.getElementById('editModal');
const statusModal = document.getElementById('statusModal');
const closeBtns = document.querySelectorAll('.close');

// Form handling
const addSpaceForm = document.getElementById('addSpaceForm');
const editSpaceForm = document.getElementById('editSpaceForm');
const statusForm = document.getElementById('statusForm');

// Modal open functions
function openAddModal() {
    addModal.style.display = 'block';
    addSpaceForm.reset();
}

function openEditModal(spaceId) {
    editModal.style.display = 'block';
    fetchSpaceDetails(spaceId);
}

function openStatusModal(spaceId) {
    statusModal.style.display = 'block';
    document.getElementById('status_space_id').value = spaceId;

    // Fetch current status
    fetch(`../../actions/gallery/get_space.php?id=${spaceId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('status').value = data.space.status;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading space details', 'error');
        });
}

// Modal close functions
function closeAddModal() {
    addModal.style.display = 'none';
    addSpaceForm.reset();
}

function closeEditModal() {
    editModal.style.display = 'none';
    editSpaceForm.reset();
}

function closeStatusModal() {
    statusModal.style.display = 'none';
    statusForm.reset();
}

// Close modals when clicking outside
window.onclick = function (event) {
    if (event.target === addModal) closeAddModal();
    if (event.target === editModal) closeEditModal();
    if (event.target === statusModal) closeStatusModal();
}

// Close buttons
closeBtns.forEach(btn => {
    btn.onclick = function () {
        const modal = this.closest('.modal');
        if (modal === addModal) closeAddModal();
        if (modal === editModal) closeEditModal();
        if (modal === statusModal) closeStatusModal();
    }
});

// Fetch space details for editing
function fetchSpaceDetails(spaceId) {
    fetch(`../../actions/gallery/get_space.php?id=${spaceId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const space = data.space;
                editSpaceForm.innerHTML = `
                    <input type="hidden" name="space_id" value="${space.space_id}">
                    <div class="form-group">
                        <label for="edit_name">Space Name <span class="required">*</span></label>
                        <input type="text" id="edit_name" name="name" value="${space.name}" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_capacity">Capacity (people) <span class="required">*</span></label>
                        <input type="number" id="edit_capacity" name="capacity" min="1" value="${space.capacity}" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_daily_rate">Daily Rate ($) <span class="required">*</span></label>
                        <input type="number" id="edit_daily_rate" name="daily_rate" min="0" step="0.01" value="${space.daily_rate}" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea id="edit_description" name="description" rows="4">${space.description || ''}</textarea>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="submit" class="submit-btn">Update Space</button>
                        <button type="button" class="cancel-btn" onclick="closeEditModal()">Cancel</button>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading space details', 'error');
        });
}

// Form submissions
addSpaceForm.addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = {
        name: this.name.value,
        capacity: parseInt(this.capacity.value),
        daily_rate: parseFloat(this.daily_rate.value),
        description: this.description.value
    };

    fetch('../../actions/gallery/add_space.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Space added successfully', 'success');
                closeAddModal();
                setTimeout(() => location.reload(), 1500);
            } else {
                throw new Error(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert(error.message || 'Error adding space', 'error');
        });
});

editSpaceForm.addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = {
        space_id: this.space_id.value,
        name: this.name.value,
        capacity: parseInt(this.capacity.value),
        daily_rate: parseFloat(this.daily_rate.value),
        description: this.description.value
    };

    fetch('../../actions/gallery/update_space.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Space updated successfully', 'success');
                closeEditModal();
                setTimeout(() => location.reload(), 1500);
            } else {
                throw new Error(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert(error.message || 'Error updating space', 'error');
        });
});

statusForm.addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = {
        space_id: this.space_id.value,
        status: this.status.value
    };

    fetch('../../actions/gallery/update_space_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Status updated successfully', 'success');
                closeStatusModal();
                setTimeout(() => location.reload(), 1500);
            } else {
                throw new Error(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert(error.message || 'Error updating status', 'error');
        });
});

// Delete space function
function confirmDelete(spaceId) {
    if (confirm('Are you sure you want to delete this space? This action cannot be undone.')) {
        fetch('../../actions/gallery/delete_space.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ space_id: spaceId })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Space deleted successfully', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert(error.message || 'Error deleting space', 'error');
            });
    }
}

// Alert function for feedback
function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert ${type}`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i>
        ${message}
    `;

    document.querySelector('.main-content').insertBefore(
        alertDiv,
        document.querySelector('.spaces-header')
    );

    setTimeout(() => alertDiv.remove(), 3000);
}
