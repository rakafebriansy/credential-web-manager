<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get user role and holding
$user_role = $_SESSION['role'] ?? 'admin';
$user_holding = $_SESSION['holding'] ?? null;

$page_title = 'Uptime Monitor';
$current_page = 'uptime_monitor.php';
include 'includes/header.php';
include 'includes/sidebar.php';

// Pass holding info to JavaScript
$holding_filter_js = ($user_role == 'user_holding' && $user_holding) ? $user_holding : '';
?>

<main class="main-content">
    <div class="content-header">
        <div class="header-left">
            <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <h1><i class="fas fa-heartbeat"></i> Uptime Monitor <?php if ($user_holding): ?><small style="font-size: 14px; color: var(--text-secondary);">(<?php echo $user_holding; ?>)</small><?php endif; ?></h1>
        </div>
        <div class="header-right">
            <span class="last-update" id="lastUpdate">Loading...</span>
            <button class="btn btn-success" onclick="refreshData()" id="refreshBtn">
                <i class="fas fa-sync-alt"></i> <span class="btn-text">Refresh</span>
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card stat-total">
            <div class="stat-icon"><i class="fas fa-globe"></i></div>
            <div class="stat-info">
                <h3 id="totalCount">-</h3>
                <p>Total</p>
            </div>
        </div>
        <div class="stat-card stat-online">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <h3 id="upCount">-</h3>
                <p>Online</p>
            </div>
        </div>
        <div class="stat-card stat-offline">
            <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
            <div class="stat-info">
                <h3 id="downCount">-</h3>
                <p>Offline</p>
            </div>
        </div>
        <div class="stat-card stat-paused">
            <div class="stat-icon"><i class="fas fa-pause-circle"></i></div>
            <div class="stat-info">
                <h3 id="pausedCount">-</h3>
                <p>Paused</p>
            </div>
        </div>
        <div class="stat-card stat-accounts">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <h3 id="accountsCount">-</h3>
                <p>Accounts</p>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <div class="filter-row">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Cari monitor...">
            </div>
            <select id="accountFilter" onchange="filterMonitors()">
                <option value="">Semua Account</option>
            </select>
            <select id="statusFilter" onchange="filterMonitors()">
                <option value="">Semua Status</option>
                <option value="2">Online</option>
                <option value="9">Offline</option>
                <option value="0">Paused</option>
            </select>
        </div>
    </div>

    <!-- Loading State -->
    <div id="loadingState" class="loading-state">
        <div class="loader"></div>
        <p>Mengambil data dari semua akun UptimeRobot...</p>
    </div>

    <!-- Monitor Cards (Mobile) & Table (Desktop) -->
    <div id="monitorsContainer" style="display: none;">
        <!-- Mobile Cards View -->
        <div class="monitor-cards" id="monitorCards"></div>
        
        <!-- Desktop Table View -->
        <div class="card table-card">
            <div class="table-responsive">
                <table class="data-table" id="monitorsTable">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Account</th>
                            <th>Monitor</th>
                            <th>URL</th>
                            <th>Response</th>
                            <th>Uptime</th>
                            <th>Last Check</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="monitorsBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Logs Modal -->
<div class="modal" id="logsModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-history"></i> <span id="logsTitle">Monitor Logs</span></h3>
            <button class="modal-close" onclick="closeLogsModal()">&times;</button>
        </div>
        <div class="modal-body" id="logsContent"></div>
    </div>
</div>

<style>
:root {
    --primary: #667eea;
    --success: #28a745;
    --danger: #dc3545;
    --warning: #ffc107;
    --info: #17a2b8;
}

/* Header */
.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 10px;
}
.header-left { display: flex; align-items: center; gap: 12px; }
.header-left h1 { margin: 0; font-size: 20px; color: #333; }
.header-right { display: flex; align-items: center; gap: 10px; }
.last-update { color: #888; font-size: 12px; }

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}
.stat-card {
    background: white;
    border-radius: 12px;
    padding: 15px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.2s;
}
.stat-card:hover { transform: translateY(-2px); }
.stat-icon {
    width: 45px;
    height: 45px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
}
.stat-total .stat-icon { background: linear-gradient(135deg, #667eea, #764ba2); }
.stat-online .stat-icon { background: linear-gradient(135deg, #11998e, #38ef7d); }
.stat-offline .stat-icon { background: linear-gradient(135deg, #eb3349, #f45c43); }
.stat-paused .stat-icon { background: linear-gradient(135deg, #f093fb, #f5576c); }
.stat-accounts .stat-icon { background: linear-gradient(135deg, #4facfe, #00f2fe); }
.stat-info h3 { font-size: 22px; margin: 0; color: #333; font-weight: 700; }
.stat-info p { margin: 2px 0 0; color: #888; font-size: 12px; }

/* Filter Section */
.filter-section {
    background: white;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.filter-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.search-box {
    flex: 1;
    min-width: 200px;
    position: relative;
}
.search-box i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #aaa;
}
.search-box input {
    width: 100%;
    padding: 10px 10px 10px 38px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s;
}
.search-box input:focus { outline: none; border-color: var(--primary); }
.filter-row select {
    padding: 10px 15px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    background: white;
    min-width: 140px;
}

/* Loading State */
.loading-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.loader {
    width: 40px;
    height: 40px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid var(--primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 15px;
}
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

/* Table Card */
.table-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
}
.data-table { width: 100%; border-collapse: collapse; }
.data-table th {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 12px 10px;
    font-size: 12px;
    font-weight: 600;
    text-align: left;
    white-space: nowrap;
}
.data-table td {
    padding: 10px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 13px;
    vertical-align: middle;
}
.data-table tbody tr:hover { background: #f8f9ff; }

/* Status Badge */
.status-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.status-up { background: #d4edda; color: #155724; }
.status-down { background: #f8d7da; color: #721c24; }
.status-paused { background: #fff3cd; color: #856404; }
.status-pending { background: #e2e3e5; color: #383d41; }

/* Account Badge */
.account-badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
    white-space: nowrap;
}

/* URL Link */
.url-link {
    color: var(--primary);
    text-decoration: none;
    font-size: 12px;
    max-width: 180px;
    display: inline-block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.url-link:hover { text-decoration: underline; }

/* Response Time */
.response-time { font-weight: 600; font-size: 12px; }
.response-time.fast { color: var(--success); }
.response-time.medium { color: var(--warning); }
.response-time.slow { color: var(--danger); }

/* Uptime Bar */
.uptime-bar {
    width: 80px;
    height: 18px;
    background: #e9ecef;
    border-radius: 9px;
    position: relative;
    overflow: hidden;
}
.uptime-fill { height: 100%; border-radius: 9px; }
.uptime-fill.good { background: linear-gradient(90deg, #28a745, #20c997); }
.uptime-fill.warning { background: linear-gradient(90deg, #ffc107, #fd7e14); }
.uptime-fill.bad { background: linear-gradient(90deg, #dc3545, #c82333); }
.uptime-bar span {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 9px;
    font-weight: 700;
    color: #333;
}

/* Buttons */
.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}
.btn-success { background: var(--success); color: white; }
.btn-success:hover { background: #218838; }
.btn-primary { background: var(--primary); color: white; }
.btn-primary:hover { background: #5a6fd6; }
.btn-sm { padding: 6px 10px; font-size: 12px; }

/* Monitor Cards (Mobile) */
.monitor-cards { display: none; }
.monitor-card {
    background: white;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.monitor-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}
.monitor-card-title {
    font-weight: 600;
    font-size: 14px;
    color: #333;
    margin-bottom: 4px;
}
.monitor-card-url {
    font-size: 11px;
    color: var(--primary);
    word-break: break-all;
}
.monitor-card-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #f0f0f0;
}
.monitor-stat {
    text-align: center;
}
.monitor-stat-label {
    font-size: 10px;
    color: #888;
    margin-bottom: 2px;
}
.monitor-stat-value {
    font-size: 13px;
    font-weight: 600;
    color: #333;
}
.monitor-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #f0f0f0;
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 15px;
}
.modal.show { display: flex; }
.modal-content {
    background: white;
    border-radius: 12px;
    width: 100%;
    max-width: 600px;
    max-height: 80vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.modal-header {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-header h3 { margin: 0; font-size: 16px; display: flex; align-items: center; gap: 8px; }
.modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #666; }
.modal-body { padding: 15px 20px; overflow-y: auto; flex: 1; }

/* Log Items */
.log-item {
    display: flex;
    align-items: center;
    padding: 10px;
    border-bottom: 1px solid #f0f0f0;
    gap: 12px;
}
.log-item:last-child { border-bottom: none; }
.log-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
}
.log-icon.up { background: var(--success); }
.log-icon.down { background: var(--danger); }
.log-info { flex: 1; }
.log-info strong { display: block; font-size: 13px; }
.log-info small { color: #888; font-size: 11px; }
.log-duration { font-size: 12px; color: #666; }

/* Responsive */
@media (max-width: 1200px) {
    .stats-grid { grid-template-columns: repeat(5, 1fr); }
}

@media (max-width: 992px) {
    .stats-grid { grid-template-columns: repeat(3, 1fr); }
    .table-card { display: none; }
    .monitor-cards { display: block; }
}

@media (max-width: 768px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .stat-card { padding: 12px; }
    .stat-icon { width: 40px; height: 40px; font-size: 16px; }
    .stat-info h3 { font-size: 18px; }
    .header-left h1 { font-size: 16px; }
    .btn-text { display: none; }
    .filter-row { flex-direction: column; }
    .search-box { min-width: 100%; }
    .filter-row select { width: 100%; }
}

@media (max-width: 480px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .stat-card:last-child { grid-column: span 2; }
    .content-header { flex-direction: column; align-items: flex-start; }
    .header-right { width: 100%; justify-content: space-between; }
}
</style>

<script>
let monitorsData = [];
const accountColors = ['#e3f2fd','#fce4ec','#f3e5f5','#e8eaf6','#e0f7fa','#e8f5e9','#fff3e0','#fbe9e7','#efebe9','#f5f5f5'];
const accountTextColors = ['#1976d2','#c2185b','#7b1fa2','#303f9f','#00838f','#2e7d32','#ef6c00','#d84315','#5d4037','#616161'];

document.addEventListener('DOMContentLoaded', function() {
    loadMonitors();
    setInterval(loadMonitors, 60000);
});

document.getElementById('searchInput').addEventListener('keyup', filterMonitors);

function loadMonitors() {
    const btn = document.getElementById('refreshBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span class="btn-text">Loading...</span>';
    btn.disabled = true;
    
    fetch('uptime_action.php?action=get_monitors')
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            monitorsData = data.monitors;
            renderMonitors(data.monitors);
            renderMonitorCards(data.monitors);
            updateStats(data.monitors, data.accounts_count);
            populateAccountFilter(data.monitors);
            document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString('id-ID');
            document.getElementById('loadingState').style.display = 'none';
            document.getElementById('monitorsContainer').style.display = 'block';
        } else {
            showError(data.message || 'Failed to load');
        }
        btn.innerHTML = '<i class="fas fa-sync-alt"></i> <span class="btn-text">Refresh</span>';
        btn.disabled = false;
    })
    .catch(err => {
        showError('Error: ' + err.message);
        btn.innerHTML = '<i class="fas fa-sync-alt"></i> <span class="btn-text">Refresh</span>';
        btn.disabled = false;
    });
}

function populateAccountFilter(monitors) {
    const select = document.getElementById('accountFilter');
    const val = select.value;
    const accounts = [...new Set(monitors.map(m => m.account_name))];
    select.innerHTML = '<option value="">Semua Account</option>' + accounts.map(a => `<option value="${a}">${a}</option>`).join('');
    select.value = val;
}

function renderMonitors(monitors) {
    const tbody = document.getElementById('monitorsBody');
    if (!monitors.length) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px;">No monitors found</td></tr>';
        return;
    }
    
    tbody.innerHTML = monitors.map(m => {
        const status = getStatusInfo(m.status);
        const rt = m.average_response_time || 0;
        const rtClass = rt < 500 ? 'fast' : rt < 1000 ? 'medium' : 'slow';
        const uptime = parseFloat(m.custom_uptime_ratio || 0);
        const uptimeClass = uptime >= 99 ? 'good' : uptime >= 95 ? 'warning' : 'bad';
        const idx = m.account_index || 0;
        
        return `<tr data-status="${m.status}" data-account="${esc(m.account_name)}" data-id="${m.id}">
            <td><span class="status-badge ${status.class}"><i class="fas ${status.icon}"></i> ${status.text}</span></td>
            <td><span class="account-badge" style="background:${accountColors[idx%10]};color:${accountTextColors[idx%10]}">${esc(m.account_name)}</span></td>
            <td><strong style="font-size:12px">${esc(m.friendly_name)}</strong></td>
            <td><a href="${esc(m.url)}" target="_blank" class="url-link">${esc(m.url)}</a></td>
            <td><span class="response-time ${rtClass}">${rt}ms</span></td>
            <td><div class="uptime-bar"><div class="uptime-fill ${uptimeClass}" style="width:${uptime}%"></div><span>${uptime.toFixed(1)}%</span></div></td>
            <td style="font-size:11px;color:#888">${formatTime(m.last_check)}</td>
            <td><button class="btn btn-sm btn-primary" onclick="viewLogs(${m.id},'${esc(m.friendly_name)}','${esc(m.api_key)}')"><i class="fas fa-history"></i></button></td>
        </tr>`;
    }).join('');
}

function renderMonitorCards(monitors) {
    const container = document.getElementById('monitorCards');
    if (!monitors.length) {
        container.innerHTML = '<div style="text-align:center;padding:40px;color:#888">No monitors found</div>';
        return;
    }
    
    container.innerHTML = monitors.map(m => {
        const status = getStatusInfo(m.status);
        const rt = m.average_response_time || 0;
        const uptime = parseFloat(m.custom_uptime_ratio || 0);
        const idx = m.account_index || 0;
        
        return `<div class="monitor-card" data-status="${m.status}" data-account="${esc(m.account_name)}" data-id="${m.id}">
            <div class="monitor-card-header">
                <div>
                    <div class="monitor-card-title">${esc(m.friendly_name)}</div>
                    <a href="${esc(m.url)}" target="_blank" class="monitor-card-url">${esc(m.url)}</a>
                </div>
                <span class="status-badge ${status.class}"><i class="fas ${status.icon}"></i> ${status.text}</span>
            </div>
            <div class="monitor-card-stats">
                <div class="monitor-stat">
                    <div class="monitor-stat-label">Response</div>
                    <div class="monitor-stat-value">${rt}ms</div>
                </div>
                <div class="monitor-stat">
                    <div class="monitor-stat-label">Uptime</div>
                    <div class="monitor-stat-value">${uptime.toFixed(1)}%</div>
                </div>
                <div class="monitor-stat">
                    <div class="monitor-stat-label">Account</div>
                    <div class="monitor-stat-value"><span class="account-badge" style="background:${accountColors[idx%10]};color:${accountTextColors[idx%10]}">${esc(m.account_name)}</span></div>
                </div>
            </div>
            <div class="monitor-card-footer">
                <span style="font-size:11px;color:#888">Last: ${formatTime(m.last_check)}</span>
                <button class="btn btn-sm btn-primary" onclick="viewLogs(${m.id},'${esc(m.friendly_name)}','${esc(m.api_key)}')"><i class="fas fa-history"></i> Logs</button>
            </div>
        </div>`;
    }).join('');
}

function updateStats(monitors, accountsCount) {
    document.getElementById('totalCount').textContent = monitors.length;
    document.getElementById('upCount').textContent = monitors.filter(m => m.status === 2).length;
    document.getElementById('downCount').textContent = monitors.filter(m => m.status === 9 || m.status === 8).length;
    document.getElementById('pausedCount').textContent = monitors.filter(m => m.status === 0).length;
    document.getElementById('accountsCount').textContent = accountsCount || '-';
}

function getStatusInfo(status) {
    const map = {
        2: { text: 'Online', class: 'status-up', icon: 'fa-check-circle' },
        9: { text: 'Offline', class: 'status-down', icon: 'fa-times-circle' },
        8: { text: 'Down', class: 'status-down', icon: 'fa-exclamation-circle' },
        0: { text: 'Paused', class: 'status-paused', icon: 'fa-pause-circle' },
        1: { text: 'Pending', class: 'status-pending', icon: 'fa-clock' }
    };
    return map[status] || { text: 'Unknown', class: 'status-pending', icon: 'fa-question-circle' };
}

function filterMonitors() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const account = document.getElementById('accountFilter').value;
    
    // Filter table rows
    document.querySelectorAll('#monitorsTable tbody tr[data-id]').forEach(row => {
        const match = (!search || row.textContent.toLowerCase().includes(search)) &&
                      (!status || row.dataset.status === status) &&
                      (!account || row.dataset.account === account);
        row.style.display = match ? '' : 'none';
    });
    
    // Filter cards
    document.querySelectorAll('.monitor-card[data-id]').forEach(card => {
        const match = (!search || card.textContent.toLowerCase().includes(search)) &&
                      (!status || card.dataset.status === status) &&
                      (!account || card.dataset.account === account);
        card.style.display = match ? '' : 'none';
    });
}

function viewLogs(id, name, apiKey) {
    document.getElementById('logsModal').classList.add('show');
    document.getElementById('logsTitle').textContent = name;
    document.getElementById('logsContent').innerHTML = '<div style="text-align:center;padding:30px"><div class="loader"></div><p>Loading logs...</p></div>';
    
    fetch(`uptime_action.php?action=get_logs&id=${id}&api_key=${encodeURIComponent(apiKey)}`)
    .then(res => res.json())
    .then(data => {
        if (data.success && data.logs.length) {
            document.getElementById('logsContent').innerHTML = data.logs.map(log => {
                const isUp = log.type === 2;
                return `<div class="log-item">
                    <div class="log-icon ${isUp ? 'up' : 'down'}"><i class="fas fa-arrow-${isUp ? 'up' : 'down'}"></i></div>
                    <div class="log-info"><strong>${isUp ? 'Up' : 'Down'}</strong><small>${formatTime(log.datetime)}</small></div>
                    <div class="log-duration">${formatDuration(log.duration)}</div>
                </div>`;
            }).join('');
        } else {
            document.getElementById('logsContent').innerHTML = '<div style="text-align:center;padding:30px;color:#888"><i class="fas fa-history" style="font-size:40px;opacity:0.3"></i><p>No logs available</p></div>';
        }
    });
}

function closeLogsModal() { document.getElementById('logsModal').classList.remove('show'); }
function refreshData() { loadMonitors(); }
function formatTime(ts) { return ts ? new Date(ts * 1000).toLocaleString('id-ID') : '-'; }
function formatDuration(s) {
    if (!s) return '-';
    if (s < 60) return s + 's';
    if (s < 3600) return Math.floor(s/60) + 'm';
    if (s < 86400) return Math.floor(s/3600) + 'h';
    return Math.floor(s/86400) + 'd';
}
function esc(t) { return t ? String(t).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])) : ''; }
function showError(msg) {
    document.getElementById('loadingState').innerHTML = `<i class="fas fa-exclamation-triangle" style="font-size:40px;color:#dc3545"></i><p>${msg}</p><button class="btn btn-primary" onclick="loadMonitors()">Try Again</button>`;
}

document.getElementById('logsModal').addEventListener('click', e => { if (e.target.id === 'logsModal') closeLogsModal(); });
</script>

<?php include 'includes/footer.php'; ?>
