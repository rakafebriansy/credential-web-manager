<?php
require_once 'config/init.php';

if (!isLoggedIn()) {
    redirect('login');
}

$page_title = 'OJS Progress';
$user = getCurrentUser($conn);

// Check if table exists, if not create it
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'web_progress'");
if (mysqli_num_rows($check_table) == 0) {
    $create_table = "CREATE TABLE IF NOT EXISTS web_progress (
        id INT AUTO_INCREMENT PRIMARY KEY,
        website_id INT NOT NULL,
        hasil_check VARCHAR(50) DEFAULT 'Korespondensi Aktif',
        versi_ojs VARCHAR(20) DEFAULT 'OJS 3.2',
        plugin_allow_upload VARCHAR(20) DEFAULT 'Belum',
        google_recaptcha VARCHAR(20) DEFAULT 'Belum',
        reset_password VARCHAR(20) DEFAULT 'Belum',
        login_admin VARCHAR(20) DEFAULT 'Belum',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    mysqli_query($conn, $create_table);
}

// Check user role
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'admin';

// Get ONLY OJS websites with progress data
$query = "SELECT w.*, 
          COALESCE(wp.id, 0) as progress_id,
          COALESCE(wp.hasil_check, 'Korespondensi Aktif') as hasil_check,
          COALESCE(wp.versi_ojs, 'OJS 3.2') as versi_ojs,
          COALESCE(wp.plugin_allow_upload, 'Belum') as plugin_allow_upload,
          COALESCE(wp.google_recaptcha, 'Belum') as google_recaptcha,
          COALESCE(wp.reset_password, 'Belum') as reset_password,
          COALESCE(wp.login_admin, 'Belum') as login_admin
          FROM websites w
          LEFT JOIN web_progress wp ON w.id = wp.website_id
          WHERE LOWER(w.jenis_web) = 'ojs'
          ORDER BY w.holding ASC, w.created_at DESC";
$result = mysqli_query($conn, $query);
$total_websites = mysqli_num_rows($result);
$result = mysqli_query($conn, $query);
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/topbar.php'; ?>
        
        <div class="content">
            <div class="page-header">
                <h1>OJS Progress</h1>
                <p>Monitoring progress dan status website OJS</p>
            </div>
            
            <!-- Recent Updates Panel -->
            <div class="card update-panel">
                <div class="card-header" style="cursor:pointer;" onclick="toggleUpdatePanel()">
                    <h3><i class="fas fa-bell"></i> Update Terbaru <span class="update-badge" id="updateBadge" style="display:none;">0</span></h3>
                    <button class="btn btn-sm" onclick="event.stopPropagation();loadRecentUpdates()"><i class="fas fa-sync-alt"></i></button>
                </div>
                <div class="card-body update-list" id="updateList" style="display:none; max-height:300px; overflow-y:auto;">
                    <div class="update-empty">Memuat update...</div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>OJS Progress (<?php echo $total_websites; ?> data)</h3>
                    <input type="text" id="searchInput" class="form-control" placeholder="Cari..." style="width: 250px;">
                </div>
                <div class="card-body">
                    <?php if ($total_websites == 0): ?>
                        <div class="empty-state">
                            <i class="fas fa-tasks"></i>
                            <h3>Belum ada data</h3>
                            <p>Tambahkan website di menu Kelola Website terlebih dahulu</p>
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table" id="progressTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Holding</th>
                                    <th>Link URL</th>
                                    <th>Hasil Check Korespondensi</th>
                                    <th>Versi OJS</th>
                                    <th>Plugin Allow Upload</th>
                                    <th>Google Recaptcha</th>
                                    <th>Reset Password</th>
                                    <th>Login Admin</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                while ($row = mysqli_fetch_assoc($result)): 
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td>
                                        <?php
                                        $holding = strtoupper($row['holding']);
                                        $holding_colors = [
                                            'RIN' => '#FF6B6B',
                                            'GP' => '#4ECDC4',
                                            'PI' => '#45B7D1',
                                            'IJL' => '#FFA07A',
                                            'AL-MAKKI' => '#98D8C8',
                                            'RIVIERA' => '#F7DC6F',
                                            'TADCENT' => '#BB8FCE',
                                            'EDC' => '#85C1E2',
                                            'LPK' => '#F8B739',
                                            'GB' => '#52B788',
                                            'STAIKU' => '#E63946',
                                            'POLTEK' => '#457B9D',
                                            'SCI' => '#E76F51'
                                        ];
                                        $color = isset($holding_colors[$holding]) ? $holding_colors[$holding] : '#6c757d';
                                        ?>
                                        <span class="badge-holding" style="background-color: <?php echo $color; ?>;">
                                            <?php echo $holding; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($row['link_url']); ?>" target="_blank" class="link-url">
                                            <?php echo htmlspecialchars($row['link_url']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <select class="progress-select" data-website-id="<?php echo $row['id']; ?>" data-field="hasil_check">
                                            <option value="Korespondensi Aktif" <?php echo $row['hasil_check'] == 'Korespondensi Aktif' ? 'selected' : ''; ?>>Korespondensi Aktif</option>
                                            <option value="Tidak Aktif" <?php echo $row['hasil_check'] == 'Tidak Aktif' ? 'selected' : ''; ?>>Tidak Aktif</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="progress-select" data-website-id="<?php echo $row['id']; ?>" data-field="versi_ojs">
                                            <option value="OJS 3.2" <?php echo $row['versi_ojs'] == 'OJS 3.2' ? 'selected' : ''; ?>>OJS 3.2</option>
                                            <option value="OJS 3.3" <?php echo $row['versi_ojs'] == 'OJS 3.3' ? 'selected' : ''; ?>>OJS 3.3</option>
                                            <option value="OJS 3.4" <?php echo $row['versi_ojs'] == 'OJS 3.4' ? 'selected' : ''; ?>>OJS 3.4</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="progress-select" data-website-id="<?php echo $row['id']; ?>" data-field="plugin_allow_upload">
                                            <option value="Done" <?php echo $row['plugin_allow_upload'] == 'Done' ? 'selected' : ''; ?>>Done</option>
                                            <option value="Belum" <?php echo $row['plugin_allow_upload'] == 'Belum' ? 'selected' : ''; ?>>Belum</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="progress-select" data-website-id="<?php echo $row['id']; ?>" data-field="google_recaptcha">
                                            <option value="Done" <?php echo $row['google_recaptcha'] == 'Done' ? 'selected' : ''; ?>>Done</option>
                                            <option value="Belum" <?php echo $row['google_recaptcha'] == 'Belum' ? 'selected' : ''; ?>>Belum</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="progress-select" data-website-id="<?php echo $row['id']; ?>" data-field="reset_password">
                                            <option value="Done" <?php echo $row['reset_password'] == 'Done' ? 'selected' : ''; ?>>Done</option>
                                            <option value="Belum" <?php echo $row['reset_password'] == 'Belum' ? 'selected' : ''; ?>>Belum</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="progress-select" data-website-id="<?php echo $row['id']; ?>" data-field="login_admin">
                                            <option value="Done" <?php echo $row['login_admin'] == 'Done' ? 'selected' : ''; ?>>Done</option>
                                            <option value="Belum" <?php echo $row['login_admin'] == 'Belum' ? 'selected' : ''; ?>>Belum</option>
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
.badge-holding {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    color: white;
    font-weight: 600;
    font-size: 12px;
    text-align: center;
    min-width: 80px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.badge-jenis {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 15px;
    color: white;
    font-weight: 600;
    font-size: 11px;
    text-align: center;
    min-width: 60px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.progress-select {
    padding: 8px 12px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    background-color: #fff;
    cursor: pointer;
    min-width: 140px;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.progress-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
}

.progress-select:hover {
    border-color: #667eea;
}

/* Hasil Check Korespondensi */
.progress-select.korespondensi-aktif {
    background: linear-gradient(135deg, #d4edda, #c3e6cb) !important;
    color: #155724 !important;
    border-color: #28a745 !important;
}

.progress-select.tidak-aktif {
    background: linear-gradient(135deg, #f8d7da, #f5c6cb) !important;
    color: #721c24 !important;
    border-color: #dc3545 !important;
}

/* Versi OJS */
.progress-select.ojs-3-2 {
    background: linear-gradient(135deg, #e2e3ff, #d4d5ff) !important;
    color: #4a4a8a !important;
    border-color: #6f42c1 !important;
}

.progress-select.ojs-3-3 {
    background: linear-gradient(135deg, #d1ecf1, #bee5eb) !important;
    color: #0c5460 !important;
    border-color: #17a2b8 !important;
}

.progress-select.ojs-3-4 {
    background: linear-gradient(135deg, #cce5ff, #b8daff) !important;
    color: #004085 !important;
    border-color: #007bff !important;
}

/* Done / Belum Status */
.progress-select.done {
    background: linear-gradient(135deg, #d4edda, #c3e6cb) !important;
    color: #155724 !important;
    border-color: #28a745 !important;
}

.progress-select.belum {
    background: linear-gradient(135deg, #fff3cd, #ffeeba) !important;
    color: #856404 !important;
    border-color: #ffc107 !important;
}

/* Save notification */
.save-notification {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    padding: 12px 24px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    display: none;
    z-index: 9999;
    animation: slideIn 0.3s ease;
    font-weight: 500;
}

.save-notification.error {
    background: linear-gradient(135deg, #dc3545, #c82333);
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
}

@keyframes slideIn {
    from { transform: translateX(100px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

/* Table header styling */
#progressTable thead th {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    font-weight: 600;
    text-align: center;
    padding: 14px 10px;
    font-size: 13px;
    white-space: nowrap;
    border: none;
}

#progressTable tbody td {
    vertical-align: middle;
    padding: 12px 8px;
    border-bottom: 1px solid #eee;
}

#progressTable tbody tr:hover {
    background-color: #f8f9ff;
}

.link-url {
    color: #667eea;
    text-decoration: none;
    font-size: 12px;
    display: inline-block;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    transition: color 0.3s;
}

.link-url:hover {
    color: #764ba2;
    text-decoration: underline;
}
</style>

<div class="save-notification" id="saveNotification">
    <i class="fas fa-check-circle"></i> <span id="notificationText">Tersimpan!</span>
</div>

<script>
// Apply color class based on value
function applyColorClass(select) {
    const value = select.value;
    const field = select.getAttribute('data-field');
    
    // Remove all color classes
    select.classList.remove(
        'korespondensi-aktif', 'tidak-aktif',
        'ojs-3-2', 'ojs-3-3', 'ojs-3-4',
        'done', 'belum'
    );
    
    // Apply appropriate class based on field and value
    if (field === 'hasil_check') {
        if (value === 'Korespondensi Aktif') {
            select.classList.add('korespondensi-aktif');
        } else {
            select.classList.add('tidak-aktif');
        }
    } else if (field === 'versi_ojs') {
        if (value === 'OJS 3.2') {
            select.classList.add('ojs-3-2');
        } else if (value === 'OJS 3.3') {
            select.classList.add('ojs-3-3');
        } else if (value === 'OJS 3.4') {
            select.classList.add('ojs-3-4');
        }
    } else {
        // For Done/Belum fields
        if (value === 'Done') {
            select.classList.add('done');
        } else {
            select.classList.add('belum');
        }
    }
}

// Show notification
function showNotification(message, isError = false) {
    const notification = document.getElementById('saveNotification');
    const text = document.getElementById('notificationText');
    
    text.textContent = message;
    notification.classList.toggle('error', isError);
    notification.style.display = 'block';
    
    setTimeout(() => {
        notification.style.display = 'none';
    }, 2000);
}

// Search functionality
(function() {
    const searchInput = document.getElementById('searchInput');
    const table = document.getElementById('progressTable');
    
    if (searchInput && table) {
        searchInput.addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase().trim();
            const tbody = table.querySelector('tbody');
            
            if (tbody) {
                const rows = tbody.querySelectorAll('tr');
                
                rows.forEach(function(row) {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchValue)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
        });
    }
})();

// Initialize and auto-save on select change
document.addEventListener('DOMContentLoaded', function() {
    const selects = document.querySelectorAll('.progress-select');
    
    // Apply initial colors
    selects.forEach(function(select) {
        applyColorClass(select);
    });
    
    // Handle change events
    selects.forEach(function(select) {
        select.addEventListener('change', function() {
            const websiteId = this.getAttribute('data-website-id');
            const field = this.getAttribute('data-field');
            const value = this.value;
            
            // Apply color immediately
            applyColorClass(this);
            
            // Save via AJAX
            fetch('web_progress_action', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'website_id=' + websiteId + '&field=' + field + '&value=' + encodeURIComponent(value)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Tersimpan!');
                } else {
                    showNotification('Gagal menyimpan: ' + data.message, true);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Terjadi kesalahan saat menyimpan', true);
            });
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
