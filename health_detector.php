<?php
require_once 'config/init.php';

if (!isLoggedIn()) {
    redirect('login');
}

$page_title = 'Deteksi Trouble';
$current_page = 'health_detector.php';
$user = getCurrentUser($conn);

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <?php include 'includes/topbar.php'; ?>
    
    <div class="content">
        <div class="page-header">
            <h1><i class="fas fa-heartbeat"></i> Website Deteksi Trouble</h1>
            <p>Monitoring kondisi kesehatan website secara real-time</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                    <i class="fas fa-globe"></i>
                </div>
                <div class="stat-content">
                    <h3 id="totalCount">-</h3>
                    <p>Total Website</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3 id="sehatCount">-</h3>
                    <p>Sehat</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <h3 id="peringatanCount">-</h3>
                    <p>Peringatan</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-content">
                    <h3 id="bahayaCount">-</h3>
                    <p>Bahaya</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Daftar Status Kesehatan</h3>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <span id="lastUpdate" style="font-size: 12px; color: var(--gray);">Loading...</span>
                    <input type="text" id="healthSearchInput" class="form-control" placeholder="Cari website..." style="width: 200px; padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px;">
                    <select id="statusFilter" class="form-control" style="width: 150px; padding: 8px 12px; border: 1px solid var(--border); border-radius: 6px;" onchange="filterData()">
                        <option value="">Semua Status</option>
                        <option value="sehat">Sehat</option>
                        <option value="peringatan">Peringatan</option>
                        <option value="bahaya">Bahaya</option>
                    </select>
                    <button class="btn btn-primary btn-sm" onclick="checkAllHealth()" id="checkAllBtn">
                        <i class="fas fa-sync-alt"></i> <span class="btn-text">Refresh Semua</span>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Loading State -->
                <div id="loadingState" class="empty-state">
                    <i class="fas fa-spinner fa-spin"></i>
                    <h3>Mengambil data...</h3>
                    <p>Mohon tunggu sebentar sementara kami memuat status kesehatan website.</p>
                </div>

                <!-- Data Table -->
                <div id="healthContainer" style="display: none;">
                    <div class="table-responsive">
                        <table class="table" id="healthTable">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Holding</th>
                                    <th>Website</th>
                                    <th>Kondisi</th>
                                    <th>HTTP</th>
                                    <th>Response</th>
                                    <th>Last Check</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="healthBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.badge-health {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-transform: capitalize;
}
.badge-sehat { background: #d1fae5; color: #10b981; }
.badge-peringatan { background: #fef3c7; color: #f59e0b; }
.badge-bahaya { background: #fee2e2; color: #ef4444; }

.badge-holding {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    color: white;
    font-weight: 600;
    font-size: 11px;
    text-align: center;
    min-width: 80px;
}

.website-info {
    display: flex;
    flex-direction: column;
}
.website-url {
    font-weight: 600;
    color: var(--primary);
    text-decoration: none;
    max-width: 250px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.website-url:hover { text-decoration: underline; }
.pic-info {
    font-size: 11px;
    color: var(--gray);
    margin-top: 2px;
}

.condition-text {
    font-weight: 600;
    font-size: 13px;
}

.response-time {
    font-weight: 500;
}
.response-fast { color: #10b981; }
.response-slow { color: #f59e0b; }
.response-very-slow { color: #ef4444; }
</style>

<script>
let healthData = [];

document.addEventListener('DOMContentLoaded', function() {
    loadHealthData();
});

document.getElementById('healthSearchInput').addEventListener('keyup', filterData);

function loadHealthData() {
    const loading = document.getElementById('loadingState');
    const container = document.getElementById('healthContainer');
    
    fetch('health_detector_action.php?action=get_health_list')
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            healthData = data.data;
            renderTable(healthData);
            updateStats(healthData);
            
            loading.style.display = 'none';
            container.style.display = 'block';
            document.getElementById('lastUpdate').textContent = 'Update: ' + new Date().toLocaleTimeString('id-ID');
        }
    })
    .catch(err => {
        console.error('Error:', err);
        loading.innerHTML = '<i class="fas fa-exclamation-triangle"></i><h3>Gagal memuat data</h3><p>Silakan coba refresh halaman.</p>';
    });
}

function renderTable(data) {
    const tbody = document.getElementById('healthBody');
    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:40px;">Tidak ada data website ditemukan</td></tr>';
        return;
    }
    
    tbody.innerHTML = data.map(item => {
        const status = item.health_status || 'unknown';
        const statusClass = `badge-${status}`;
        const statusIcon = status === 'sehat' ? 'fa-check-circle' : (status === 'peringatan' ? 'fa-exclamation-triangle' : 'fa-times-circle');
        
        const rt = item.response_time || 0;
        const rtClass = rt < 1000 ? 'response-fast' : (rt < 3000 ? 'response-slow' : 'response-very-slow');
        
        return `<tr data-status="${status}">
            <td><span class="badge-health ${statusClass}"><i class="fas ${statusIcon}"></i> ${status}</span></td>
            <td><span class="badge-holding" style="background-color: ${getHoldingColor(item.holding)};">${item.holding.toUpperCase()}</span></td>
            <td>
                <div class="website-info">
                    <a href="${item.link_url}" target="_blank" class="website-url">${item.link_url}</a>
                    <span class="pic-info"><i class="fas fa-user"></i> ${item.pic}</span>
                </div>
            </td>
            <td><span class="condition-text">${item.condition_text || '-'}</span></td>
            <td><strong>${item.http_code || '-'}</strong></td>
            <td><span class="response-time ${rtClass}">${rt ? rt + 'ms' : '-'}</span></td>
            <td style="font-size:12px;color:var(--gray)">${item.last_check ? formatDateTime(item.last_check) : 'Belum dicek'}</td>
            <td>
                <button class="btn-icon" onclick="checkOneHealth(${item.id}, this)" title="Cek Sekarang">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </td>
        </tr>`;
    }).join('');
}

function updateStats(data) {
    document.getElementById('totalCount').textContent = data.length;
    document.getElementById('sehatCount').textContent = data.filter(item => item.health_status === 'sehat').length;
    document.getElementById('peringatanCount').textContent = data.filter(item => item.health_status === 'peringatan').length;
    document.getElementById('bahayaCount').textContent = data.filter(item => item.health_status === 'bahaya').length;
}

function filterData() {
    const search = document.getElementById('healthSearchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    
    const filtered = healthData.filter(item => {
        const matchesSearch = item.link_url.toLowerCase().includes(search) || 
                             item.pic.toLowerCase().includes(search) || 
                             item.holding.toLowerCase().includes(search);
        const matchesStatus = !status || item.health_status === status;
        return matchesSearch && matchesStatus;
    });
    
    renderTable(filtered);
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type} show`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function checkOneHealth(id, btn) {
    const icon = btn.querySelector('i');
    icon.classList.add('fa-spin');
    btn.disabled = true;
    
    fetch(`health_detector_action.php?action=check_health&website_id=${id}`, {
        method: 'POST'
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            const index = healthData.findIndex(item => item.id == id);
            if (index !== -1) {
                healthData[index].health_status = result.health_status;
                healthData[index].condition_text = result.condition_text;
                healthData[index].http_code = result.http_code;
                healthData[index].response_time = result.response_time;
                healthData[index].last_check = result.last_check;
            }
            filterData();
            updateStats(healthData);
            showNotification('Pengecekan selesai!');
        } else {
            showNotification(result.message || 'Gagal mengecek website', 'error');
        }
        icon.classList.remove('fa-spin');
        btn.disabled = false;
    })
    .catch(err => {
        console.error('Error:', err);
        showNotification('Terjadi kesalahan sistem', 'error');
        icon.classList.remove('fa-spin');
        btn.disabled = false;
    });
}

function checkAllHealth() {
    const btn = document.getElementById('checkAllBtn');
    const icon = btn.querySelector('i');
    const text = btn.querySelector('.btn-text');
    
    icon.classList.add('fa-spin');
    text.textContent = 'Mengecek...';
    btn.disabled = true;
    
    fetch(`health_detector_action.php?action=check_all`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            showNotification(result.message);
            loadHealthData();
        } else {
            showNotification(result.message || 'Gagal mengecek semua website', 'error');
        }
        icon.classList.remove('fa-spin');
        text.textContent = 'Refresh Semua';
        btn.disabled = false;
    })
    .catch(err => {
        console.error('Error:', err);
        showNotification('Terjadi kesalahan sistem', 'error');
        icon.classList.remove('fa-spin');
        text.textContent = 'Refresh Semua';
        btn.disabled = false;
    });
}

function getHoldingColor(holding) {
    const colors = {
        'rin': '#FF6B6B', 'gp': '#4ECDC4', 'pi': '#45B7D1', 'ijl': '#FFA07A',
        'al-makki': '#98D8C8', 'riviera': '#F7DC6F', 'tadcent': '#BB8FCE',
        'edc': '#85C1E2', 'lpk': '#F8B739', 'gb': '#52B788', 'staiku': '#E63946',
        'poltek': '#457B9D', 'sci': '#E76F51'
    };
    return colors[holding.toLowerCase()] || '#6c757d';
}

function formatDateTime(str) {
    const date = new Date(str);
    return date.toLocaleString('id-ID', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
}
</script>

<?php include 'includes/footer.php'; ?>
