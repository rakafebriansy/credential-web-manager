// Toggle sidebar on mobile
const menuToggle = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebarToggle');

if (menuToggle) {
    menuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
    });
}

if (sidebarToggle) {
    sidebarToggle.addEventListener('click', () => {
        sidebar.classList.remove('active');
    });
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', (e) => {
    if (window.innerWidth <= 768) {
        if (sidebar && menuToggle && !sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
            sidebar.classList.remove('active');
        }
    }
});

// Modal functions
function openModal() {
    document.getElementById('websiteModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Tambah Website';
    document.getElementById('formAction').value = 'create';
    document.getElementById('websiteForm').reset();
    document.getElementById('websiteId').value = '';
}

function closeModal() {
    document.getElementById('websiteModal').style.display = 'none';
}

function editWebsite(website) {
    document.getElementById('websiteModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Edit Website';
    document.getElementById('formAction').value = 'update';
    
    document.getElementById('websiteId').value = website.id;
    document.getElementById('holding').value = website.holding;
    document.getElementById('link_url').value = website.link_url;
    document.getElementById('jenis_web').value = website.jenis_web;
    document.getElementById('letak_server').value = website.letak_server;
    document.getElementById('pic').value = website.pic;
}

function deleteWebsite(id) {
    if (confirm('Apakah Anda yakin ingin menghapus website ini?')) {
        window.location.href = 'websites_action.php?action=delete&id=' + id;
    }
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('websiteModal');
    if (modal && event.target == modal) {
        closeModal();
    }
});

// Search functionality
window.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const table = document.getElementById('websitesTable');
            if (table) {
                const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
                for (let i = 0; i < rows.length; i++) {
                    const row = rows[i];
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchValue) ? '' : 'none';
                }
            }
        });
    }
});
// Forum Functions
function toggleLike(type, id) {
    const data = new FormData();
    data.append('action', 'toggle_like');
    
    if (type === 'post') {
        data.append('post_id', id);
    } else {
        data.append('reply_id', id);
    }
    
    fetch('forum_action', {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            const likeBtn = document.querySelector(`[onclick="toggleLike('${type}', ${id})"]`);
            const likeCount = likeBtn.querySelector('.like-count');
            
            if (result.liked) {
                likeBtn.classList.add('liked');
                likeCount.textContent = parseInt(likeCount.textContent) + 1;
            } else {
                likeBtn.classList.remove('liked');
                likeCount.textContent = parseInt(likeCount.textContent) - 1;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function updatePostStatus(postId, status) {
    const data = new FormData();
    data.append('action', 'update_status');
    data.append('post_id', postId);
    data.append('status', status);
    
    fetch('forum_action', {
        method: 'POST',
        body: data
    })
    .then(response => {
        if (response.ok) {
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function openImageModal(imageSrc) {
    // Create modal if it doesn't exist
    let modal = document.getElementById('imageModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'imageModal';
        modal.className = 'image-modal';
        modal.innerHTML = `
            <div class="image-modal-content">
                <span class="image-modal-close" onclick="closeImageModal()">&times;</span>
                <img id="modalImage" src="" alt="Image">
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    document.getElementById('modalImage').src = imageSrc;
    modal.style.display = 'flex';
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Forum post form toggle
function togglePostForm() {
    const form = document.getElementById('newPostForm');
    const btn = document.getElementById('newPostBtn');
    
    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'block';
        btn.innerHTML = '<i class="fas fa-times"></i> Cancel';
        btn.classList.add('btn-secondary');
        btn.classList.remove('btn-primary');
    } else {
        form.style.display = 'none';
        btn.innerHTML = '<i class="fas fa-plus"></i> New Post';
        btn.classList.add('btn-primary');
        btn.classList.remove('btn-secondary');
    }
}

// Close image modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('imageModal');
    if (modal && event.target === modal) {
        closeImageModal();
    }
});