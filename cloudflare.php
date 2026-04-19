<?php
require_once 'config/init.php';

if (!isLoggedIn()) {
    redirect('login');
}

// Restrict access for user_ojs
if (isset($_SESSION['role']) && $_SESSION['role'] == 'user_ojs') {
    redirect('ojs_progress');
}

$page_title = 'Cloudflare CDN Management';
$user = getCurrentUser($conn);

// Create cloudflare_cdn table if not exists
$create_table = "CREATE TABLE IF NOT EXISTS cloudflare_cdn (
    id INT AUTO_INCREMENT PRIMARY KEY,
    website_id INT NOT NULL,
    cdn_status VARCHAR(50) DEFAULT 'Cloudflare',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_website (website_id),
    FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE
)";
mysqli_query($conn, $create_table);

// Fix existing table if it has ENUM type - change to VARCHAR
$alter_query = "ALTER TABLE cloudflare_cdn MODIFY COLUMN cdn_status VARCHAR(50) DEFAULT 'Cloudflare'";
mysqli_query($conn, $alter_query); // Ignore error if already VARCHAR

// Get all websites with cloudflare data
$websites_query = "SELECT w.*, 
    COALESCE(cf.cdn_status, 'Cloudflare') as cdn_status
    FROM websites w
    LEFT JOIN cloudflare_cdn cf ON w.id = cf.website_id
    ORDER BY w.holding ASC, w.link_url ASC";
$websites_result = mysqli_query($conn, $websites_query);
$total_websites = mysqli_num_rows($websites_result);

// Count statistics by website type
$stats_query = "SELECT 
    w.jenis_web,
    COUNT(*) as total,
    SUM(CASE WHEN cf.cdn_status LIKE 'Cloudflare%' THEN 1 ELSE 0 END) as cloudflare_cdn,
    SUM(CASE WHEN cf.cdn_status IN ('Bunny', 'Niagahoster', 'Jagoan Hosting') THEN 1 ELSE 0 END) as other_providers
    FROM websites w
    LEFT JOIN cloudflare_cdn cf ON w.id = cf.website_id
    GROUP BY w.jenis_web";
$stats_result = mysqli_query($conn, $stats_query);


?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/topbar.php'; ?>
        
        <div class="content">
            <div class="page-header">
                <h1>Cloudflare CDN Management</h1>
                <p>Kelola pengaturan Cloudflare untuk semua website</p>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <?php while ($stat = mysqli_fetch_assoc($stats_result)): ?>
                <div class="stat-card">
                    <div class="stat-icon <?php echo strtolower($stat['jenis_web']); ?>">
                        <i class="fas fa-<?php echo $stat['jenis_web'] == 'ojs' ? 'book' : ($stat['jenis_web'] == 'Lp' ? 'bullseye' : 'globe'); ?>"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo strtoupper($stat['jenis_web']); ?></h3>
                        <p><?php echo $stat['total']; ?> Total</p>
                        <div class="stat-details">
                            <span class="stat-detail">Cloudflare: <?php echo $stat['cloudflare_cdn']; ?>/<?php echo $stat['total']; ?></span>
                            <span class="stat-detail">Others: <?php echo $stat['other_providers']; ?>/<?php echo $stat['total']; ?></span>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            
            <!-- Main Table -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-cloud"></i> Cloudflare Settings (<?php echo $total_websites; ?> Websites)</h3>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <select id="filterType" class="form-control" style="width: 150px;">
                            <option value="">Semua Jenis</option>
                            <option value="ojs">OJS</option>
                            <option value="Lp">LP</option>
                            <option value="website utama">Website</option>
                            <option value="blog">Blog</option>
                            <option value="react js">React JS</option>
                        </select>
                        <input type="text" id="searchInput" class="form-control" placeholder="Cari..." style="width: 200px;">
                        <button class="btn btn-success btn-sm" onclick="saveAllChanges()">
                            <i class="fas fa-save"></i> Simpan Semua
                        </button>
                        <button class="btn btn-info btn-sm" onclick="testConnection()">
                            <i class="fas fa-test-tube"></i> Test
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($total_websites == 0): ?>
                        <div class="empty-state">
                            <i class="fas fa-cloud"></i>
                            <h3>Belum ada data website</h3>
                            <p>Tambahkan website di menu Kelola Website terlebih dahulu</p>
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table cloudflare-table" id="cloudflareTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Holding</th>
                                    <th>Link URL</th>
                                    <th>Jenis</th>
                                    <th class="cdn-col">Letak CDN</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                mysqli_data_seek($websites_result, 0);
                                while ($website = mysqli_fetch_assoc($websites_result)): 
                                    $holding = strtoupper($website['holding']);
                                    $holding_colors = [
                                        'RIN' => '#FF6B6B', 'GP' => '#4ECDC4', 'PI' => '#45B7D1',
                                        'IJL' => '#FFA07A', 'AL-MAKKI' => '#98D8C8', 'RIVIERA' => '#F7DC6F',
                                        'TADCENT' => '#BB8FCE', 'EDC' => '#85C1E2', 'LPK' => '#F8B739',
                                        'LPK MKM' => '#F8B739', 'GB' => '#52B788', 'STAIKU' => '#E63946',
                                        'POLTEK' => '#457B9D', 'SCI' => '#E76F51', 'PUBLIKASIKU' => '#9C27B0'
                                    ];
                                    $color = isset($holding_colors[$holding]) ? $holding_colors[$holding] : '#6c757d';
                                ?>
                                <tr data-id="<?php echo $website['id']; ?>" data-type="<?php echo strtolower($website['jenis_web']); ?>">
                                    <td><?php echo $no++; ?></td>
                                    <td>
                                        <span class="badge-holding" style="background-color: <?php echo $color; ?>;">
                                            <?php echo $holding; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($website['link_url']); ?>" target="_blank" class="link-url">
                                            <?php echo htmlspecialchars($website['link_url']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower($website['jenis_web']); ?>">
                                            <?php echo strtoupper($website['jenis_web']); ?>
                                        </span>
                                    </td>
                                    <td class="status-cell">
                                        <select class="status-select" data-field="cdn_status" data-id="<?php echo $website['id']; ?>" onchange="updateStatus(this)">
                                            <option value="Cloudflare" <?php echo $website['cdn_status'] == 'Cloudflare' ? 'selected' : ''; ?>>Cloudflare</option>
                                            <option value="Cloudflare 1" <?php echo $website['cdn_status'] == 'Cloudflare 1' ? 'selected' : ''; ?>>Cloudflare 1</option>
                                            <option value="Cloudflare 2" <?php echo $website['cdn_status'] == 'Cloudflare 2' ? 'selected' : ''; ?>>Cloudflare 2</option>
                                            <option value="Cloudflare 3" <?php echo $website['cdn_status'] == 'Cloudflare 3' ? 'selected' : ''; ?>>Cloudflare 3</option>
                                            <option value="Cloudflare 4" <?php echo $website['cdn_status'] == 'Cloudflare 4' ? 'selected' : ''; ?>>Cloudflare 4</option>
                                            <option value="Bunny" <?php echo $website['cdn_status'] == 'Bunny' ? 'selected' : ''; ?>>Bunny</option>
                                            <option value="Niagahoster" <?php echo $website['cdn_status'] == 'Niagahoster' ? 'selected' : ''; ?>>Niagahoster</option>
                                            <option value="Jagoan Hosting" <?php echo $website['cdn_status'] == 'Jagoan Hosting' ? 'selected' : ''; ?>>Jagoan Hosting</option>
                                        </select>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<style>
.cloudflare-table thead th {
    background: linear-gradient(135deg, #f39c12, #e67e22);
    color: white;
    font-weight: 600;
    text-align: center;
    padding: 12px 8px;
    font-size: 13px;
    white-space: nowrap;
}

.cloudflare-table thead th.cdn-col {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
}

.cloudflare-table tbody td {
    vertical-align: middle;
    padding: 10px 8px;
}

.status-select, .pic-select {
    padding: 6px 10px;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.3s;
    min-width: 100px;
    background: white;
}

.security-select {
    min-width: 120px;
}

.minify-select {
    min-width: 80px;
}

.zone-input {
    padding: 6px 8px;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    font-size: 12px;
    width: 120px;
    transition: all 0.3s;
}

.notes-input {
    padding: 6px 8px;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    font-size: 12px;
    width: 150px;
    height: 60px;
    resize: vertical;
    transition: all 0.3s;
}

.status-select:focus, .pic-select:focus, .zone-input:focus, .notes-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.status-select.cloudflare {
    background: linear-gradient(135deg, #d4edda, #c3e6cb) !important;
    color: #155724 !important;
    border-color: #28a745 !important;
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.2);
}

.status-select.cloudflare-1 {
    background: linear-gradient(135deg, #cce5ff, #b8daff) !important;
    color: #004085 !important;
    border-color: #007bff !important;
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.2);
}

.status-select.cloudflare-2 {
    background: linear-gradient(135deg, #e2e3ff, #d4d5ff) !important;
    color: #4a4a8a !important;
    border-color: #6f42c1 !important;
    box-shadow: 0 2px 8px rgba(111, 66, 193, 0.2);
}

.status-select.cloudflare-3 {
    background: linear-gradient(135deg, #ffeaa7, #fdcb6e) !important;
    color: #6c4e00 !important;
    border-color: #fd7e14 !important;
    box-shadow: 0 2px 8px rgba(253, 126, 20, 0.2);
}

.status-select.cloudflare-4 {
    background: linear-gradient(135deg, #ffccdd, #ffb3c6) !important;
    color: #721c24 !important;
    border-color: #e83e8c !important;
    box-shadow: 0 2px 8px rgba(232, 62, 140, 0.2);
}

.status-select.bunny {
    background: linear-gradient(135deg, #fff3cd, #ffeeba) !important;
    color: #856404 !important;
    border-color: #ffc107 !important;
    box-shadow: 0 2px 8px rgba(255, 193, 7, 0.2);
}

.status-select.niagahoster {
    background: linear-gradient(135deg, #d1ecf1, #bee5eb) !important;
    color: #0c5460 !important;
    border-color: #17a2b8 !important;
    box-shadow: 0 2px 8px rgba(23, 162, 184, 0.2);
}

.status-select.jagoan-hosting {
    background: linear-gradient(135deg, #f5c6cb, #f1b0b7) !important;
    color: #721c24 !important;
    border-color: #dc3545 !important;
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.2);
}

.badge-holding {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 20px;
    color: white;
    font-weight: 600;
    font-size: 12px;
    text-align: center;
    min-width: 80px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.15);
}

.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-ojs { background: #17a2b8; color: white; }
.badge-lp { background: #28a745; color: white; }
.badge-website { background: #6f42c1; color: white; }
.badge-blog { background: #fd7e14; color: white; }
.badge-react { background: #20c997; color: white; }

.last-update-cell {
    font-size: 12px;
    color: var(--gray);
    white-space: nowrap;
}

.link-url {
    color: var(--primary);
    text-decoration: none;
    font-size: 12px;
    display: inline-block;
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.link-url:hover {
    text-decoration: underline;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
}

.stat-icon.ojs { background: linear-gradient(135deg, #17a2b8, #138496); }
.stat-icon.lp { background: linear-gradient(135deg, #28a745, #1e7e34); }
.stat-icon.website { background: linear-gradient(135deg, #6f42c1, #5a32a3); }
.stat-icon.blog { background: linear-gradient(135deg, #fd7e14, #e8690b); }
.stat-icon.react { background: linear-gradient(135deg, #20c997, #17a085); }

.stat-info h3 {
    margin: 0 0 5px 0;
    font-size: 18px;
    font-weight: 600;
}

.stat-info p {
    margin: 0 0 10px 0;
    color: var(--gray);
    font-size: 14px;
}

.stat-details {
    display: flex;
    gap: 15px;
}

.stat-detail {
    font-size: 12px;
    color: var(--gray);
    background: #f8f9fa;
    padding: 4px 8px;
    border-radius: 6px;
}

/* Notification */
.save-notification {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #28a745;
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    display: none;
    z-index: 9999;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from { transform: translateX(100px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
</style>

<div class="save-notification" id="saveNotification">
    <i class="fas fa-check-circle"></i> <span id="notificationText">Tersimpan!</span>
</div>

<script>
// Update status when dropdown changes
function updateStatus(element) {
    const id = element.dataset.id;
    const field = element.dataset.field;
    const value = element.value;
    
    // Update visual style for CDN status
    if (element.classList.contains('status-select') && field === 'cdn_status') {
        element.classList.remove('cloudflare', 'cloudflare-1', 'cloudflare-2', 'cloudflare-3', 'cloudflare-4', 'bunny', 'niagahoster', 'jagoan-hosting');
        element.classList.add(value.toLowerCase().replace(' ', '-'));
    }
    
    // Send AJAX request
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('website_id', id);
    formData.append('field', field);
    formData.append('value', value);
    
    fetch('cloudflare_action', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // First check if response is ok
        if (!response.ok) {
            throw new Error('HTTP ' + response.status + ': ' + response.statusText);
        }
        
        // Get response text first to debug
        return response.text();
    })
    .then(text => {
        console.log('Raw response:', text); // Debug log
        
        // Try to parse as JSON
        try {
            const data = JSON.parse(text);
            console.log('Parsed response:', data); // Debug log
            
            if (data.success) {
                showNotification('Tersimpan!');
            } else {
                showNotification('Gagal menyimpan: ' + data.message, true);
                console.error('Save error:', data.message);
            }
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text:', text);
            showNotification('Error: Invalid response format', true);
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        showNotification('Error: ' + error.message, true);
    });
}

// Show notification
function showNotification(message, isError = false) {
    const notification = document.getElementById('saveNotification');
    const text = document.getElementById('notificationText');
    
    text.textContent = message;
    notification.style.background = isError ? '#dc3545' : '#28a745';
    notification.style.display = 'block';
    
    setTimeout(() => {
        notification.style.display = 'none';
    }, 2000);
}

// Save all changes
function saveAllChanges() {
    showNotification('Semua perubahan tersimpan!');
}

// Test connection
function testConnection() {
    const formData = new FormData();
    formData.append('action', 'test');
    
    fetch('cloudflare_action', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        console.log('Test raw response:', text);
        try {
            const data = JSON.parse(text);
            showNotification('Test successful: ' + data.message);
        } catch (e) {
            console.error('Test JSON parse error:', e);
            showNotification('Test response (raw): ' + text.substring(0, 100) + '...', true);
        }
    })
    .catch(error => {
        console.error('Test error:', error);
        showNotification('Test error: ' + error.message, true);
    });
}

// Initialize select styles
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.status-select').forEach(select => {
        if (select.dataset.field === 'cdn_status') {
            select.classList.add(select.value.toLowerCase().replace(' ', '-'));
        }
    });
});

// Search functionality
document.getElementById('searchInput')?.addEventListener('keyup', function() {
    const searchValue = this.value.toLowerCase().trim();
    filterTable();
});

// Filter by type
document.getElementById('filterType')?.addEventListener('change', function() {
    filterTable();
});

function filterTable() {
    const searchValue = document.getElementById('searchInput').value.toLowerCase().trim();
    const filterType = document.getElementById('filterType').value.toLowerCase();
    const tbody = document.querySelector('#cloudflareTable tbody');
    
    if (tbody) {
        const rows = tbody.querySelectorAll('tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const type = row.dataset.type;
            
            const matchesSearch = text.includes(searchValue);
            const matchesType = !filterType || type === filterType;
            
            row.style.display = (matchesSearch && matchesType) ? '' : 'none';
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?>