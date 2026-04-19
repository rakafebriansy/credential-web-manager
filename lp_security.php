<?php
require_once 'config/init.php';

if (!isLoggedIn()) {
    redirect('login');
}

// Get user role and holding
$user_role = $_SESSION['role'] ?? 'admin';
$user_holding = $_SESSION['holding'] ?? null;

// Restrict access for user_ojs
if ($user_role == 'user_ojs') {
    redirect('ojs_progress');
}

$page_title = 'LP Progress & Error WP';
$current_page = 'lp_security.php';
$user = getCurrentUser($conn);

// Create lp_security table if not exists
$create_table = "CREATE TABLE IF NOT EXISTS lp_security (
    id INT AUTO_INCREMENT PRIMARY KEY,
    website_id INT NOT NULL,
    ganti_wp_admin ENUM('Done', 'Belum') DEFAULT 'Belum',
    plugin_wordfence ENUM('Done', 'Belum') DEFAULT 'Belum',
    update_all_plugin ENUM('Done', 'Belum') DEFAULT 'Belum',
    konfigurasi_rate_limit ENUM('Done', 'Belum') DEFAULT 'Belum',
    last_update DATETIME DEFAULT NULL,
    pic VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_website (website_id),
    FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE
)";
mysqli_query($conn, $create_table);

// Build WHERE clause based on user role
$holding_filter = "";
if ($user_role == 'user_holding' && $user_holding) {
    $holding_filter = " AND UPPER(w.holding) = '" . mysqli_real_escape_string($conn, strtoupper($user_holding)) . "'";
}

// Get all LP websites from websites table
$websites_query = "SELECT w.*, 
    COALESCE(lp.ganti_wp_admin, 'Belum') as ganti_wp_admin,
    COALESCE(lp.plugin_wordfence, 'Belum') as plugin_wordfence,
    COALESCE(lp.update_all_plugin, 'Belum') as update_all_plugin,
    COALESCE(lp.konfigurasi_rate_limit, 'Belum') as konfigurasi_rate_limit,
    lp.last_update,
    COALESCE(lp.pic, w.pic) as security_pic
    FROM websites w
    LEFT JOIN lp_security lp ON w.id = lp.website_id
    WHERE LOWER(w.jenis_web) = 'lp' $holding_filter
    ORDER BY w.holding ASC, w.link_url ASC";
$websites_result = mysqli_query($conn, $websites_query);
$total_lp = mysqli_num_rows($websites_result);

// Count statistics
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN lp.ganti_wp_admin = 'Done' THEN 1 ELSE 0 END) as wp_admin_done,
    SUM(CASE WHEN lp.plugin_wordfence = 'Done' THEN 1 ELSE 0 END) as wordfence_done,
    SUM(CASE WHEN lp.update_all_plugin = 'Done' THEN 1 ELSE 0 END) as plugin_done,
    SUM(CASE WHEN lp.konfigurasi_rate_limit = 'Done' THEN 1 ELSE 0 END) as rate_limit_done
    FROM websites w
    LEFT JOIN lp_security lp ON w.id = lp.website_id
    WHERE LOWER(w.jenis_web) = 'lp' $holding_filter";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// PIC options
$pic_options = ['Abdul Fazri', 'Andika', 'Isma', 'Surya', 'Ridho'];
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
  
            
            <!-- Main Table -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-table"></i> Progress & Error WP (<?php echo $total_lp; ?> LP)</h3>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="text" id="searchInput" class="form-control" placeholder="Cari..." style="width: 200px;">
                        <button class="btn btn-success btn-sm" onclick="saveAllChanges()">
                            <i class="fas fa-save"></i> Simpan Semua
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($total_lp == 0): ?>
                        <div class="empty-state">
                            <i class="fas fa-folder-open"></i>
                            <h3>Belum ada data LP</h3>
                            <p>Tambahkan website dengan jenis "LP" di menu Kelola Website</p>
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table lp-security-table" id="lpSecurityTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Holding</th>
                                    <th>Link URL</th>
                                    <th class="security-col">Ganti wp-admin</th>
                                    <th class="security-col">Plugin wordfence</th>
                                    <th class="security-col">Update all plugin</th>
                                    <th class="security-col">Konfigurasi Rate Limit</th>
                                    <th>Last Update</th>
                                    <th>PIC</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                while ($website = mysqli_fetch_assoc($websites_result)): 
                                    $holding = strtoupper($website['holding']);
                                    $holding_colors = [
                                        'RIN' => '#FF6B6B', 'GP' => '#4ECDC4', 'PI' => '#45B7D1',
                                        'IJL' => '#FFA07A', 'AL-MAKKI' => '#98D8C8', 'RIVIERA' => '#F7DC6F',
                                        'TADCENT' => '#BB8FCE', 'EDC' => '#85C1E2', 'LPK' => '#F8B739',
                                        'LPK MKM' => '#F8B739', 'GB' => '#52B788', 'STAIKU' => '#E63946',
                                        'POLTEK' => '#457B9D', 'SCI' => '#E76F51'
                                    ];
                                    $color = isset($holding_colors[$holding]) ? $holding_colors[$holding] : '#6c757d';
                                ?>
                                <tr data-id="<?php echo $website['id']; ?>">
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
                                    <td class="status-cell">
                                        <select class="status-select" data-field="ganti_wp_admin" data-id="<?php echo $website['id']; ?>" onchange="updateStatus(this)">
                                            <option value="Belum" <?php echo $website['ganti_wp_admin'] == 'Belum' ? 'selected' : ''; ?>>Belum</option>
                                            <option value="Done" <?php echo $website['ganti_wp_admin'] == 'Done' ? 'selected' : ''; ?>>Done</option>
                                        </select>
                                    </td>
                                    <td class="status-cell">
                                        <select class="status-select" data-field="plugin_wordfence" data-id="<?php echo $website['id']; ?>" onchange="updateStatus(this)">
                                            <option value="Belum" <?php echo $website['plugin_wordfence'] == 'Belum' ? 'selected' : ''; ?>>Belum</option>
                                            <option value="Done" <?php echo $website['plugin_wordfence'] == 'Done' ? 'selected' : ''; ?>>Done</option>
                                        </select>
                                    </td>
                                    <td class="status-cell">
                                        <select class="status-select" data-field="update_all_plugin" data-id="<?php echo $website['id']; ?>" onchange="updateStatus(this)">
                                            <option value="Belum" <?php echo $website['update_all_plugin'] == 'Belum' ? 'selected' : ''; ?>>Belum</option>
                                            <option value="Done" <?php echo $website['update_all_plugin'] == 'Done' ? 'selected' : ''; ?>>Done</option>
                                        </select>
                                    </td>
                                    <td class="status-cell">
                                        <select class="status-select" data-field="konfigurasi_rate_limit" data-id="<?php echo $website['id']; ?>" onchange="updateStatus(this)">
                                            <option value="Belum" <?php echo $website['konfigurasi_rate_limit'] == 'Belum' ? 'selected' : ''; ?>>Belum</option>
                                            <option value="Done" <?php echo $website['konfigurasi_rate_limit'] == 'Done' ? 'selected' : ''; ?>>Done</option>
                                        </select>
                                    </td>
                                    <td class="last-update-cell">
                                        <?php echo $website['last_update'] ? date('d/m/Y H:i', strtotime($website['last_update'])) : '-'; ?>
                                    </td>
                                    <td class="pic-cell">
                                        <select class="pic-select" data-field="pic" data-id="<?php echo $website['id']; ?>" onchange="updateStatus(this)">
                                            <option value="">-- Pilih --</option>
                                            <?php foreach ($pic_options as $pic): ?>
                                            <option value="<?php echo $pic; ?>" <?php echo $website['security_pic'] == $pic ? 'selected' : ''; ?>><?php echo $pic; ?></option>
                                            <?php endforeach; ?>
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
.lp-security-table thead th {
    background: linear-gradient(135deg, #ffc107, #ff9800);
    color: #000;
    font-weight: 600;
    text-align: center;
    padding: 12px 8px;
    font-size: 13px;
    white-space: nowrap;
}

.lp-security-table thead th.security-col {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
}

.lp-security-table tbody td {
    vertical-align: middle;
    padding: 10px 8px;
}

.status-select, .pic-select {
    padding: 8px 12px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 100px;
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.status-select:hover, .pic-select:hover {
    border-color: #667eea;
    transform: translateY(-1px);
}

.status-select:focus, .pic-select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.status-select[data-value="Done"], 
.status-select option[value="Done"]:checked {
    background-color: #d4edda;
    color: #155724;
    border-color: #28a745;
}

.status-select.done {
    background: linear-gradient(135deg, #d4edda, #c3e6cb) !important;
    color: #155724 !important;
    border-color: #28a745 !important;
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.2);
}

.status-select.belum {
    background: linear-gradient(135deg, #fff3cd, #ffeeba) !important;
    color: #856404 !important;
    border-color: #ffc107 !important;
    box-shadow: 0 2px 8px rgba(255, 193, 7, 0.2);
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

.stat-icon.purple {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
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
function updateStatus(select) {
    const id = select.dataset.id;
    const field = select.dataset.field;
    const value = select.value;
    
    // Update visual style
    if (field !== 'pic') {
        select.classList.remove('done', 'belum');
        select.classList.add(value.toLowerCase());
    }
    
    // Send AJAX request
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('website_id', id);
    formData.append('field', field);
    formData.append('value', value);
    
    fetch('lp_security_action', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Tersimpan!');
            // Update last update cell
            const row = select.closest('tr');
            const lastUpdateCell = row.querySelector('.last-update-cell');
            if (lastUpdateCell && data.last_update) {
                lastUpdateCell.textContent = data.last_update;
            }
        } else {
            showNotification('Gagal menyimpan!', true);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error!', true);
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

// Initialize select styles
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.status-select').forEach(select => {
        select.classList.add(select.value.toLowerCase());
    });
});

// Search functionality
document.getElementById('searchInput')?.addEventListener('keyup', function() {
    const searchValue = this.value.toLowerCase().trim();
    const tbody = document.querySelector('#lpSecurityTable tbody');
    
    if (tbody) {
        const rows = tbody.querySelectorAll('tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchValue) ? '' : 'none';
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
