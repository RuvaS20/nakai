// Store modal as a global variable
const modal = document.getElementById('artistModal');
const closeBtn = document.querySelector('.close');

document.addEventListener('DOMContentLoaded', function () {
    // Modal close handlers
    if (closeBtn) {
        closeBtn.onclick = function () {
            modal.style.display = 'none';
        }
    }

    // Close modal when clicking outside
    window.onclick = function (event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    }
});

// View Artist Profile
function viewArtistProfile(artistId) {
    fetch(`../../actions/gallery/get_artist_profile.php?id=${artistId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const artist = data.artist;
                const artworks = data.artworks;
                const exhibitions = data.exhibitions;

                let exhibitionStatus = '';

                
                document.getElementById('artistProfileContent').innerHTML = `
    <div class="artist-profile">
        <div class="artist-header">
            <div class="artist-details">
                <h2>${artist.name}</h2>
                <p class="artist-contact">
                    <i class="fas fa-envelope"></i> ${artist.email}
                    ${artist.phone ? `<br><i class="fas fa-phone"></i> ${artist.phone}` : ''}
                </p>
            </div>
        </div>
        
        ${artist.bio ? `
            <div class="artist-bio">
                <h3>About</h3>
                <p>${artist.bio}</p>
            </div>
        ` : ''}
        
        <div class="artist-stats">
            <div class="stat">
                <span class="stat-number">${artworks.length}</span>
                <span class="stat-label">Artworks</span>
            </div>
            <div class="stat">
                <span class="stat-number">${exhibitions.length}</span>
                <span class="stat-label">Exhibitions</span>
            </div>
        </div>
        
        <div class="artworks-section">
            <h3>Artworks & Exhibitions</h3>
            ${artworks.length > 0 ? `
                <div class="modal-artworks-grid">
                    ${artworks.map(artwork => {
                    const exhibition = exhibitions.find(e => e.booking_id === artwork.exhibition_id);
                    if (exhibition) {
                        const now = new Date();
                        const startDate = new Date(exhibition.start_date);
                        const endDate = new Date(exhibition.end_date);

                        if (now >= startDate && now <= endDate) {
                            exhibitionStatus = '<span class="status current">Currently Exhibited</span>';
                        } else if (now < startDate) {
                            exhibitionStatus = '<span class="status upcoming">Upcoming Exhibition</span>';
                        } else {
                            exhibitionStatus = '<span class="status past">Past Exhibition</span>';
                        }
                    }

                    return `
                            <div class="modal-artwork-card">
                                <div class="modal-artwork-image">
                                    <img src="../../${artwork.image_url}" alt="${artwork.title}">
                                </div>
                                <div class="modal-artwork-info">
                                    <h4>${artwork.title}</h4>
                                    ${artwork.description ? `<p>${artwork.description}</p>` : ''}
                                    ${exhibition ? `
                                        <div class="exhibition-info">
                                            ${exhibitionStatus}
                                            <p>
                                                <i class="fas fa-calendar"></i> 
                                                ${new Date(exhibition.start_date).toLocaleDateString()} - 
                                                ${new Date(exhibition.end_date).toLocaleDateString()}
                                            </p>
                                            <p>
                                                <i class="fas fa-door-open"></i> 
                                                ${exhibition.space_name}
                                            </p>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        `;
                }).join('')}
                </div>
            ` : `
                <div class="no-artworks">
                    <i class="fas fa-palette"></i>
                    <p>No artworks available</p>
                </div>
            `}
        </div>
    </div>
`;

                if (modal) {
                    modal.style.display = 'block';
                }
            } else {
                showNotification(data.message || 'Error loading artist profile', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error loading artist profile', 'error');
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
