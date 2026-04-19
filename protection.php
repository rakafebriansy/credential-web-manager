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

$page_title = 'Protection & Security Check';
$current_page = 'protection.php';
$user = getCurrentUser($conn);

// Build WHERE clause based on user role
$holding_filter = "";
if ($user_role == 'user_holding' && $user_holding) {
    $holding_filter = " WHERE UPPER(w.holding) = '" . mysqli_real_escape_string($conn, strtoupper($user_holding)) . "'";
}

// Get all websites with last scan results
$websites_query = "SELECT w.*, 
    cs.status as last_status, 
    cs.is_infected, 
    cs.infection_status, 
    cs.detection_count,
    cs.detected_keywords,
    cs.scan_time as last_scan,
    cs.response_time
    FROM websites w 
    LEFT JOIN content_scan_results cs ON w.id = cs.website_id 
    $holding_filter
    ORDER BY w.holding ASC";
$websites_result = mysqli_query($conn, $websites_query);

// Get summary stats
$stats_where = $holding_filter ? str_replace("WHERE", "WHERE", $holding_filter) : "";
$stats_query = "SELECT 
    COUNT(DISTINCT w.id) as total,
    SUM(CASE WHEN cs.status = 'online' AND cs.is_infected = 0 THEN 1 ELSE 0 END) as safe,
    SUM(CASE WHEN cs.is_infected = 1 THEN 1 ELSE 0 END) as infected,
    SUM(CASE WHEN cs.status = 'offline' OR cs.status = 'error' THEN 1 ELSE 0 END) as offline
    FROM websites w 
    LEFT JOIN content_scan_results cs ON w.id = cs.website_id
    $holding_filter";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<style>
/* Protection Page Dark Mode Support */
[data-theme="dark"] .scan-tabs {
    background: var(--card-bg);
    border-color: var(--border-color);
}

[data-theme="dark"] .scan-tab {
    color: var(--text-secondary);
    background: transparent;
}

[data-theme="dark"] .scan-tab.active {
    background: var(--primary);
    color: white;
}

[data-theme="dark"] .filter-btn {
    background: var(--bg-secondary);
    color: var(--text-primary);
    border-color: var(--border-color);
}

[data-theme="dark"] .filter-btn.active {
    background: var(--primary);
    color: white;
}

[data-theme="dark"] .infected-row {
    background: rgba(220, 38, 38, 0.15) !important;
}

[data-theme="dark"] .infected-row:hover {
    background: rgba(220, 38, 38, 0.25) !important;
}

[data-theme="dark"] .modal-content {
    background: var(--card-bg);
    color: var(--text-primary);
}

[data-theme="dark"] .modal-header {
    border-color: var(--border-color);
}

[data-theme="dark"] .detection-item {
    background: var(--bg-secondary);
    border-color: var(--border-color);
}

[data-theme="dark"] .detection-context code {
    background: var(--bg-primary);
    color: var(--text-primary);
}

[data-theme="dark"] .status-box {
    background: var(--bg-secondary);
}

[data-theme="dark"] .result-stats .stat-item {
    background: var(--bg-secondary);
}

[data-theme="dark"] .google-link-item {
    background: var(--bg-secondary);
    border-color: var(--border-color);
}

[data-theme="dark"] .alert {
    background: var(--bg-secondary);
    border-color: var(--border-color);
}

/* Scan Tabs */
.scan-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    background: #f8fafc;
    padding: 8px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

.scan-tab {
    flex: 1;
    padding: 12px 20px;
    border: none;
    background: transparent;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s ease;
    color: #64748b;
}

.scan-tab:hover {
    background: #e2e8f0;
}

.scan-tab.active {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

/* Action Bar */
.action-bar {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
    align-items: center;
}

.scan-status {
    margin-left: auto;
    font-size: 14px;
    color: var(--text-secondary);
}

/* Filter Buttons */
.filter-buttons {
    display: flex;
    gap: 8px;
}

.filter-btn {
    padding: 6px 14px;
    border: 1px solid #e2e8f0;
    background: white;
    border-radius: 20px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.2s;
}

.filter-btn:hover {
    border-color: var(--primary);
    color: var(--primary);
}

.filter-btn.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

/* Response Time */
.response-time {
    display: block;
    font-size: 11px;
    color: var(--text-secondary);
    margin-top: 2px;
}

/* Keyword Tags */
.keyword-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.keyword-tag {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
}

.keyword-tag.danger {
    background: #fee2e2;
    color: #dc2626;
}

.keyword-tag.critical {
    background: #7f1d1d;
    color: white;
}

.keyword-more {
    font-size: 11px;
    color: var(--text-secondary);
    padding: 3px 6px;
}

/* Badge Critical */
.badge-critical {
    background: #7f1d1d !important;
    color: white !important;
}

/* Button Icons */
.btn-icon {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    color: #64748b;
    transition: all 0.2s;
    margin: 2px;
}

.btn-icon:hover {
    background: var(--primary);
    color: white;
}

.btn-icon.btn-google {
    background: #fef3c7;
    color: #d97706;
}

.btn-icon.btn-google:hover {
    background: #f59e0b;
    color: white;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-content {
    background: white;
    border-radius: 16px;
    width: 100%;
    max-width: 700px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
}

.modal-lg {
    max-width: 800px;
}

.modal-xl {
    max-width: 1000px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #e2e8f0;
}

.modal-header h2 {
    margin: 0;
    font-size: 18px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-close {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #64748b;
    padding: 8px;
    border-radius: 8px;
}

.modal-close:hover {
    background: #f1f5f9;
    color: #1e293b;
}

.modal-body {
    padding: 24px;
}

/* Detection Items */
.detection-category {
    margin-bottom: 20px;
}

.detection-category h5 {
    font-size: 14px;
    color: var(--text-secondary);
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.detection-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px;
    margin-bottom: 10px;
}

.detection-item.critical {
    border-left: 4px solid #7f1d1d;
}

.detection-item.high {
    border-left: 4px solid #dc2626;
}

.detection-item.medium {
    border-left: 4px solid #f59e0b;
}

.detection-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    flex-wrap: wrap;
}

.detection-type {
    font-weight: 600;
    color: var(--text-primary);
}

.detection-location {
    font-size: 12px;
    color: var(--text-secondary);
}

.detection-info p {
    margin: 4px 0;
    font-size: 13px;
}

.detection-context code {
    display: block;
    background: #1e293b;
    color: #e2e8f0;
    padding: 10px;
    border-radius: 6px;
    font-size: 12px;
    margin-top: 6px;
    word-break: break-all;
    white-space: pre-wrap;
}

/* Status Box */
.status-box {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.status-box.status-success {
    background: #dcfce7;
    color: #166534;
}

.status-box.status-danger, .status-box.status-critical {
    background: #fee2e2;
    color: #991b1b;
}

.status-box.status-warning {
    background: #fef3c7;
    color: #92400e;
}

/* Result Stats */
.result-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 12px;
    margin-bottom: 20px;
}

.result-stats .stat-item {
    background: #f8fafc;
    padding: 12px;
    border-radius: 10px;
    text-align: center;
}

.stat-label {
    display: block;
    font-size: 11px;
    color: var(--text-secondary);
    margin-bottom: 4px;
}

.stat-value {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-primary);
}

/* Google Scan Styles */
.google-scan-header {
    margin-bottom: 20px;
}

.scan-domain {
    display: flex;
    align-items: center;
    gap: 15px;
}

.scan-domain i {
    font-size: 40px;
    color: var(--primary);
}

.scan-domain h3 {
    margin: 0;
    font-size: 20px;
}

.scan-domain a {
    font-size: 13px;
    color: var(--text-secondary);
}

.quick-links {
    margin: 20px 0;
}

.quick-links h4 {
    margin-bottom: 12px;
    font-size: 14px;
}

.link-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-outline {
    padding: 8px 16px;
    border: 1px solid var(--border-color);
    background: transparent;
    border-radius: 8px;
    color: var(--text-primary);
    text-decoration: none;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}

.btn-outline:hover {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.google-links-list {
    max-height: 400px;
    overflow-y: auto;
}

.google-link-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 8px;
}

.link-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.link-keyword {
    font-weight: 600;
}

.link-category {
    font-size: 12px;
    color: var(--text-secondary);
}

/* Modal Actions */
.modal-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid var(--border-color);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 40px;
    color: var(--text-secondary);
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-state h3 {
    margin: 0 0 8px;
    color: var(--text-primary);
}

/* Alert */
.alert {
    padding: 16px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.alert i {
    font-size: 20px;
    margin-top: 2px;
}

.alert-info {
    background: #dbeafe;
    color: #1e40af;
    border: 1px solid #93c5fd;
}

.alert-success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #86efac;
}

.alert-warning {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fcd34d;
}

/* Responsive */
@media (max-width: 768px) {
    .scan-tabs {
        flex-direction: column;
    }
    
    .action-bar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .scan-status {
        margin-left: 0;
        text-align: center;
    }
    
    .filter-buttons {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .modal-content {
        margin: 10px;
        max-height: calc(100vh - 20px);
    }
    
    .result-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .google-link-item {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }
}
</style>

    <div class="main-content">
        <?php include 'includes/topbar.php'; ?>
        
        <div class="content">
            <div class="page-header">
                <h1><i class="fas fa-shield-alt"></i> Protection & Security Check</h1>
                <p>Cek status website dan deteksi malware gambling (slot, judi, togel) via Google Search</p>
            </div>
            
            <!-- Scan Type Tabs -->
            <div class="scan-tabs">
                <button class="scan-tab active" onclick="switchScanType('content')" id="tabContent">
                    <i class="fas fa-file-code"></i> Scan Konten Website
                </button>
                <button class="scan-tab" onclick="switchScanType('google')" id="tabGoogle">
                    <i class="fab fa-google"></i> Scan Google Index
                </button>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-bar">
                <button class="btn btn-primary" onclick="scanAllWebsites()" id="btnScanAll">
                    <i class="fas fa-search"></i> Scan Semua Website
                </button>
                <button class="btn btn-warning" onclick="googleScanAllWebsites()" id="btnGoogleScan" style="display:none;">
                    <i class="fab fa-google"></i> Google Scan Semua
                </button>
                <button class="btn btn-secondary" onclick="refreshStatus()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <span class="scan-status" id="scanStatus"></span>
            </div>
            
            <!-- Summary Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-globe"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="totalSites"><?php echo $stats['total'] ?? mysqli_num_rows($websites_result); ?></h3>
                        <p>Total Website</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="safeSites"><?php echo $stats['safe'] ?? '-'; ?></h3>
                        <p>Website Aman</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon danger">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="infectedSites"><?php echo $stats['infected'] ?? '-'; ?></h3>
                        <p>Terdeteksi Malware</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="offlineSites"><?php echo $stats['offline'] ?? '-'; ?></h3>
                        <p>Website Offline</p>
                    </div>
                </div>
            </div>
            
            <!-- Scan Results Table -->
            <div class="card">
                <div class="card-header">
                    <h3>Hasil Scan Website</h3>
                    <div class="filter-buttons">
                        <button class="filter-btn active" onclick="filterResults('all')">Semua</button>
                        <button class="filter-btn" onclick="filterResults('safe')">Aman</button>
                        <button class="filter-btn" onclick="filterResults('infected')">Terinfeksi</button>
                        <button class="filter-btn" onclick="filterResults('offline')">Offline</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="scanResultsTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Holding</th>
                                    <th>URL</th>
                                    <th>Status</th>
                                    <th>Malware Check</th>
                                    <th>Detected Keywords</th>
                                    <th>Last Scan</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="scanResultsBody">
                                <?php 
                                mysqli_data_seek($websites_result, 0);
                                $no = 1;
                                while ($website = mysqli_fetch_assoc($websites_result)): 
                                    $has_scan = !empty($website['last_status']);
                                    $is_infected = $website['is_infected'] == 1;
                                    $row_class = $is_infected ? 'infected-row' : '';
                                ?>
                                <tr data-id="<?php echo $website['id']; ?>" data-url="<?php echo htmlspecialchars($website['link_url']); ?>" class="<?php echo $row_class; ?>">
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo htmlspecialchars($website['holding']); ?></td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($website['link_url']); ?>" target="_blank" class="link-url">
                                            <?php echo htmlspecialchars($website['link_url']); ?>
                                        </a>
                                    </td>
                                    <td class="status-cell">
                                        <?php if ($has_scan): ?>
                                            <?php if ($website['last_status'] == 'online'): ?>
                                                <span class="badge badge-success"><i class="fas fa-check-circle"></i> Online</span>
                                                <small class="response-time"><?php echo $website['response_time']; ?>ms</small>
                                            <?php elseif ($website['last_status'] == 'offline'): ?>
                                                <span class="badge badge-danger"><i class="fas fa-times-circle"></i> Offline</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning"><i class="fas fa-exclamation-triangle"></i> Error</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Belum Scan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="malware-cell">
                                        <?php if ($has_scan): ?>
                                            <?php if ($is_infected): ?>
                                                <?php 
                                                $status_badge = 'badge-warning';
                                                $icon = 'fa-exclamation-triangle';
                                                if ($website['infection_status'] == 'Terinfeksi Parah') {
                                                    $status_badge = 'badge-critical';
                                                    $icon = 'fa-skull-crossbones';
                                                } elseif ($website['infection_status'] == 'Terinfeksi') {
                                                    $status_badge = 'badge-danger';
                                                    $icon = 'fa-bug';
                                                }
                                                ?>
                                                <span class="badge <?php echo $status_badge; ?>"><i class="fas <?php echo $icon; ?>"></i> <?php echo $website['infection_status']; ?></span>
                                            <?php else: ?>
                                                <span class="badge badge-success"><i class="fas fa-shield-alt"></i> Aman</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="keywords-cell">
                                        <?php if ($has_scan && !empty($website['detected_keywords'])): ?>
                                            <div class="keyword-tags">
                                                <?php 
                                                $keywords = explode(', ', $website['detected_keywords']);
                                                $shown = 0;
                                                foreach ($keywords as $kw): 
                                                    if ($shown >= 3) break;
                                                    if (empty(trim($kw))) continue;
                                                    $shown++;
                                                ?>
                                                    <span class="keyword-tag danger"><?php echo htmlspecialchars(trim($kw)); ?></span>
                                                <?php endforeach; ?>
                                                <?php if (count($keywords) > 3): ?>
                                                    <span class="keyword-more">+<?php echo count($keywords) - 3; ?> lainnya</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($has_scan): ?>
                                            <span class="text-success">Tidak ada deteksi</span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="lastscan-cell">
                                        <?php echo $website['last_scan'] ? date('d/m/Y H:i', strtotime($website['last_scan'])) : '-'; ?>
                                    </td>
                                    <td class="action-cell">
                                        <button type="button" class="btn-icon" onclick="scanSingleWebsite(<?php echo $website['id']; ?>, '<?php echo addslashes(htmlspecialchars($website['link_url'])); ?>')" title="Scan Konten">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        <button type="button" class="btn-icon btn-google" onclick="googleScanSingle(<?php echo $website['id']; ?>, '<?php echo addslashes(htmlspecialchars($website['link_url'])); ?>')" title="Google Scan">
                                            <i class="fab fa-google"></i>
                                        </button>
                                        <button type="button" class="btn-icon" onclick="viewDetails(<?php echo $website['id']; ?>)" title="Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Detail Modal -->
    <div id="detailModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h2><i class="fas fa-info-circle"></i> Detail Scan</h2>
                <button class="modal-close" onclick="closeDetailModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="detailContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
    
    <!-- Google Scan Detail Modal -->
    <div id="googleDetailModal" class="modal">
        <div class="modal-content modal-xl">
            <div class="modal-header">
                <h2><i class="fab fa-google"></i> Detail Google Scan</h2>
                <button class="modal-close" onclick="closeGoogleDetailModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="googleDetailContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/protection.js"></script>
<script>
// Initialize saved results from PHP
<?php 
mysqli_data_seek($websites_result, 0);
$saved_results = [];
while ($w = mysqli_fetch_assoc($websites_result)) {
    if (!empty($w['last_status'])) {
        $saved_results[$w['id']] = [
            'id' => $w['id'],
            'url' => $w['link_url'],
            'status' => $w['last_status'],
            'is_infected' => $w['is_infected'] == 1,
            'infection_status' => $w['infection_status'] ?? 'Aman',
            'detection_count' => intval($w['detection_count']),
            'detected_keywords' => $w['detected_keywords'] ? explode(', ', $w['detected_keywords']) : [],
            'scan_time' => $w['last_scan'],
            'response_time' => intval($w['response_time'])
        ];
    }
}
?>
var savedResults = <?php echo json_encode($saved_results); ?>;

// Load saved results into scanResults
Object.keys(savedResults).forEach(function(id) {
    scanResults[id] = savedResults[id];
});

document.addEventListener('DOMContentLoaded', function() {
    console.log('Protection page loaded, initializing buttons...');
    console.log('Loaded saved results:', Object.keys(savedResults).length);
    
    // Bind Google scan buttons
    document.querySelectorAll('.btn-google').forEach(function(btn) {
        btn.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const row = this.closest('tr');
            if (!row) {
                console.error('Row not found');
                return;
            }
            
            const id = parseInt(row.getAttribute('data-id'));
            const url = row.getAttribute('data-url');
            
            console.log('Google Scan clicked - ID:', id, 'URL:', url);
            
            if (typeof googleScanSingle === 'function') {
                googleScanSingle(id, url);
            } else {
                console.error('googleScanSingle function not found');
                openGoogleSearch(url);
            }
        };
    });
    
    // Bind scan content buttons
    document.querySelectorAll('.btn-icon:not(.btn-google)').forEach(function(btn) {
        if (btn.querySelector('.fa-search')) {
            btn.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const row = this.closest('tr');
                if (!row) return;
                
                const id = parseInt(row.getAttribute('data-id'));
                const url = row.getAttribute('data-url');
                
                console.log('Content Scan clicked - ID:', id, 'URL:', url);
                
                if (typeof scanSingleWebsite === 'function') {
                    scanSingleWebsite(id, url);
                }
            };
        }
    });
    
    console.log('Buttons initialized. Functions available:', {
        googleScanSingle: typeof googleScanSingle,
        scanSingleWebsite: typeof scanSingleWebsite,
        viewDetails: typeof viewDetails
    });
});

// Fallback function to open Google search directly
function openGoogleSearch(url) {
    try {
        const urlObj = new URL(url);
        const domain = urlObj.hostname.replace('www.', '');
        const googleUrl = 'https://www.google.com/search?q=site:' + encodeURIComponent(domain) + '+slot+OR+togel+OR+judi';
        window.open(googleUrl, '_blank');
    } catch(e) {
        alert('Invalid URL: ' + url);
    }
}
</script>
