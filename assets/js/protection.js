// Protection & Security Check JavaScript - Realtime Scanner

let scanResults = {};
let googleScanResults = {};
let isScanning = false;
let currentScanType = 'content';

// Escape HTML helper function
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Format bytes to human readable
function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Format time
function formatTime(datetime) {
    if (!datetime) return '-';
    try {
        return new Date(datetime).toLocaleString('id-ID', { 
            day: '2-digit', 
            month: '2-digit', 
            year: 'numeric', 
            hour: '2-digit', 
            minute: '2-digit',
            second: '2-digit'
        });
    } catch (e) {
        return datetime;
    }
}

// Switch scan type
function switchScanType(type) {
    currentScanType = type;
    document.getElementById('tabContent').classList.toggle('active', type === 'content');
    document.getElementById('tabGoogle').classList.toggle('active', type === 'google');
    document.getElementById('btnScanAll').style.display = type === 'content' ? 'inline-flex' : 'none';
    document.getElementById('btnGoogleScan').style.display = type === 'google' ? 'inline-flex' : 'none';
}

// Scan single website - REALTIME
async function scanSingleWebsite(id, url) {
    console.log('Scanning website REALTIME:', id, url);
    
    const row = document.querySelector(`tr[data-id="${id}"]`);
    if (!row) {
        console.error('Row not found for id:', id);
        return;
    }

    const statusCell = row.querySelector('.status-cell');
    const malwareCell = row.querySelector('.malware-cell');
    const keywordsCell = row.querySelector('.keywords-cell');
    const lastscanCell = row.querySelector('.lastscan-cell');

    // Show scanning status
    statusCell.innerHTML = '<span class="badge badge-info"><i class="fas fa-spinner fa-spin"></i> Scanning...</span>';
    malwareCell.innerHTML = '<span class="badge badge-secondary"><i class="fas fa-hourglass-half"></i></span>';
    keywordsCell.innerHTML = '<small class="text-muted">Mengambil data...</small>';

    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('url', url);

        // Add timestamp to prevent caching
        const timestamp = new Date().getTime();
        
        const response = await fetch('scan_website.php?t=' + timestamp, { 
            method: 'POST', 
            body: formData,
            cache: 'no-store'
        });
        
        if (!response.ok) {
            throw new Error('HTTP error: ' + response.status);
        }
        
        const result = await response.json();
        
        console.log('Scan result:', result);
        scanResults[id] = result;

        // Update status based on result
        if (result.status === 'online') {
            statusCell.innerHTML = `
                <span class="badge badge-success"><i class="fas fa-check-circle"></i> Online</span>
                <small class="response-time">${result.response_time}ms</small>
            `;
        } else if (result.status === 'offline') {
            statusCell.innerHTML = '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> Offline</span>';
        } else {
            statusCell.innerHTML = '<span class="badge badge-warning"><i class="fas fa-exclamation-triangle"></i> Error</span>';
        }

        // Update malware status
        if (result.is_infected) {
            let statusBadge = 'badge-warning';
            let icon = 'fa-exclamation-triangle';
            
            if (result.infection_status === 'Terinfeksi Parah') {
                statusBadge = 'badge-critical';
                icon = 'fa-skull-crossbones';
            } else if (result.infection_status === 'Terinfeksi') {
                statusBadge = 'badge-danger';
                icon = 'fa-bug';
            }
            
            malwareCell.innerHTML = `<span class="badge ${statusBadge}"><i class="fas ${icon}"></i> ${result.infection_status}</span>`;
            keywordsCell.innerHTML = formatDetections(result.detections);
            row.classList.add('infected-row');
        } else if (result.status === 'online') {
            malwareCell.innerHTML = '<span class="badge badge-success"><i class="fas fa-shield-alt"></i> Aman</span>';
            keywordsCell.innerHTML = '<span class="text-success">Tidak ada deteksi</span>';
            row.classList.remove('infected-row');
        } else {
            malwareCell.innerHTML = '<span class="badge badge-secondary">-</span>';
            keywordsCell.innerHTML = '-';
        }

        // Update last scan time
        lastscanCell.innerHTML = formatTime(result.scan_time);
        
        // Update summary
        updateSummary();
        
    } catch (error) {
        console.error('Scan error:', error);
        statusCell.innerHTML = '<span class="badge badge-danger"><i class="fas fa-times"></i> Error</span>';
        malwareCell.innerHTML = '<span class="badge badge-secondary">Gagal</span>';
        keywordsCell.innerHTML = '<small class="text-danger">' + error.message + '</small>';
    }
}

// Format detections for table display
function formatDetections(detections) {
    if (!detections || detections.length === 0) return '<span class="text-success">-</span>';
    
    // Get unique keywords
    const keywords = [...new Set(detections.map(d => d.keyword))];
    let html = '<div class="keyword-tags">';
    
    // Show first 4 keywords
    for (let i = 0; i < Math.min(keywords.length, 4); i++) {
        const detection = detections.find(d => d.keyword === keywords[i]);
        const severity = detection?.severity || 'medium';
        let badgeClass = 'info';
        
        if (severity === 'critical') badgeClass = 'critical';
        else if (severity === 'high') badgeClass = 'danger';
        else if (severity === 'medium') badgeClass = 'warning';
        
        html += `<span class="keyword-tag ${badgeClass}">${keywords[i]}</span>`;
    }
    
    if (keywords.length > 4) {
        html += `<span class="keyword-more">+${keywords.length - 4} lainnya</span>`;
    }
    
    html += '</div>';
    return html;
}

// Scan all websites
async function scanAllWebsites() {
    if (isScanning) { 
        alert('Scan sedang berjalan, mohon tunggu...'); 
        return; 
    }

    isScanning = true;
    const rows = document.querySelectorAll('#scanResultsBody tr');
    let scannedCount = 0;
    const totalCount = rows.length;

    document.getElementById('scanStatus').innerHTML = `<i class="fas fa-spinner fa-spin"></i> Scanning 0/${totalCount}...`;
    document.getElementById('btnScanAll').disabled = true;

    for (const row of rows) {
        const id = row.dataset.id;
        const url = row.dataset.url;
        
        if (id && url) {
            await scanSingleWebsite(id, url);
            scannedCount++;
            document.getElementById('scanStatus').innerHTML = `<i class="fas fa-spinner fa-spin"></i> Scanning ${scannedCount}/${totalCount}...`;
            
            // Small delay between scans to prevent overwhelming the server
            await new Promise(r => setTimeout(r, 500));
        }
    }

    isScanning = false;
    document.getElementById('btnScanAll').disabled = false;
    document.getElementById('scanStatus').innerHTML = '<i class="fas fa-check-circle text-success"></i> Scan selesai!';
    
    setTimeout(() => { 
        document.getElementById('scanStatus').innerHTML = ''; 
    }, 5000);
}

// Update summary statistics
function updateSummary() {
    let safe = 0, infected = 0, offline = 0;
    
    Object.values(scanResults).forEach(r => {
        if (r.status === 'online' && !r.is_infected) safe++;
        else if (r.is_infected) infected++;
        else if (r.status === 'offline' || r.status === 'error') offline++;
    });
    
    document.getElementById('safeSites').textContent = safe;
    document.getElementById('infectedSites').textContent = infected;
    document.getElementById('offlineSites').textContent = offline;
}

// Filter results
function filterResults(filter) {
    const rows = document.querySelectorAll('#scanResultsBody tr');
    
    document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');

    rows.forEach(row => {
        const result = scanResults[row.dataset.id];
        let show = filter === 'all';
        
        if (result) {
            if (filter === 'safe' && result.status === 'online' && !result.is_infected) show = true;
            else if (filter === 'infected' && result.is_infected) show = true;
            else if (filter === 'offline' && (result.status === 'offline' || result.status === 'error')) show = true;
        } else if (filter === 'all') {
            show = true;
        }
        
        row.style.display = show ? '' : 'none';
    });
}

// View details modal
function viewDetails(id) {
    const result = scanResults[id];
    const modal = document.getElementById('detailModal');
    const content = document.getElementById('detailContent');

    if (!result) {
        content.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-search"></i>
                <h3>Belum ada data scan</h3>
                <p>Klik tombol scan terlebih dahulu untuk menganalisis website ini secara realtime</p>
                <button class="btn btn-primary" onclick="closeDetailModal();" style="margin-top: 15px;">
                    <i class="fas fa-times"></i> Tutup
                </button>
            </div>
        `;
        modal.style.display = 'flex';
        return;
    }

    // Build detections HTML grouped by category
    let detectionsHtml = '';
    if (result.detections && result.detections.length > 0) {
        const grouped = {};
        result.detections.forEach(d => {
            if (!grouped[d.category]) grouped[d.category] = [];
            grouped[d.category].push(d);
        });

        for (const [category, items] of Object.entries(grouped)) {
            detectionsHtml += `<div class="detection-category"><h5><i class="fas fa-folder"></i> ${category} (${items.length})</h5>`;
            items.forEach(d => {
                const severityClass = d.severity === 'critical' ? 'critical' : 
                                     (d.severity === 'high' ? 'danger' : 
                                     (d.severity === 'medium' ? 'warning' : 'info'));
                detectionsHtml += `
                    <div class="detection-item ${d.severity}">
                        <div class="detection-header">
                            <span class="badge badge-${severityClass}">${d.severity.toUpperCase()}</span>
                            <span class="detection-type">${d.type}</span>
                            <span class="detection-location"><i class="fas fa-map-marker-alt"></i> ${d.location}</span>
                        </div>
                        <div class="detection-info">
                            <p><strong>Keyword:</strong> <span class="text-danger">${escapeHtml(d.keyword)}</span></p>
                            <p><strong>Ditemukan:</strong> ${escapeHtml(d.content)}</p>
                            ${d.context ? `<p class="detection-context"><strong>Context:</strong> <code>${escapeHtml(d.context)}</code></p>` : ''}
                        </div>
                    </div>
                `;
            });
            detectionsHtml += '</div>';
        }
    } else if (result.detected_keywords && result.detected_keywords.length > 0) {
        // Fallback for saved results without full detections
        detectionsHtml = '<div class="detection-category"><h5><i class="fas fa-folder"></i> Detected Keywords</h5>';
        result.detected_keywords.forEach(kw => {
            if (kw && kw.trim()) {
                detectionsHtml += `
                    <div class="detection-item high">
                        <div class="detection-header">
                            <span class="badge badge-danger">HIGH</span>
                            <span class="detection-type">${escapeHtml(kw)}</span>
                        </div>
                    </div>
                `;
            }
        });
        detectionsHtml += '</div>';
    }

    const statusClass = result.is_infected ? 
        (result.infection_status === 'Terinfeksi Parah' ? 'critical' : 
        (result.infection_status === 'Terinfeksi' ? 'danger' : 'warning')) : 'success';

    const detectionCount = result.detection_count || (result.detections ? result.detections.length : 0);

    content.innerHTML = `
        <div class="analysis-header">
            <div class="url-display">
                <label>URL Website</label>
                <div class="url-box">
                    <a href="${escapeHtml(result.url)}" target="_blank">${escapeHtml(result.url)}</a>
                    <span class="scan-mode-badge"><i class="fas fa-wifi"></i> Realtime Scan</span>
                </div>
            </div>
        </div>

        <div class="analysis-result">
            <h3><i class="fas fa-chart-bar"></i> Hasil Analisis</h3>
            <div class="status-box status-${statusClass}">
                <i class="fas fa-${result.is_infected ? 'exclamation-circle' : 'check-circle'}"></i>
                <span><strong>Status:</strong> ${result.infection_status || 'Aman'}</span>
            </div>
            
            <div class="result-stats">
                <div class="stat-item">
                    <span class="stat-label">HTTP Status</span>
                    <span class="stat-value">${result.http_code || '-'}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Response Time</span>
                    <span class="stat-value">${result.response_time || 0}ms</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Content Size</span>
                    <span class="stat-value">${formatBytes(result.content_length || 0)}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Deteksi Ditemukan</span>
                    <span class="stat-value ${detectionCount > 0 ? 'text-danger' : 'text-success'}">${detectionCount}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Waktu Scan</span>
                    <span class="stat-value">${formatTime(result.scan_time)}</span>
                </div>
            </div>
        </div>

        ${detectionCount > 0 ? `
        <div class="detections-section">
            <h3><i class="fas fa-bug"></i> Detail Deteksi (${detectionCount})</h3>
            <p class="text-muted">Konten berbahaya yang ditemukan pada website:</p>
            ${detectionsHtml}
        </div>
        ` : `
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <span>Tidak ditemukan konten judi/gambling pada website ini.</span>
        </div>
        `}

        ${result.error ? `
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <span>${escapeHtml(result.error)}</span>
        </div>
        ` : ''}
        
        <div class="modal-actions">
            <button class="btn btn-primary" onclick="scanSingleWebsite(${result.id}, '${escapeHtml(result.url)}'); closeDetailModal();">
                <i class="fas fa-sync"></i> Scan Ulang
            </button>
            <button class="btn btn-secondary" onclick="closeDetailModal()">
                <i class="fas fa-times"></i> Tutup
            </button>
        </div>
    `;

    modal.style.display = 'flex';
}

// Modal functions
function closeDetailModal() { 
    document.getElementById('detailModal').style.display = 'none'; 
}

function closeGoogleDetailModal() { 
    document.getElementById('googleDetailModal').style.display = 'none'; 
}

function refreshStatus() { 
    location.reload(); 
}

// Close modal on outside click
window.addEventListener('click', function(e) {
    if (e.target === document.getElementById('detailModal')) closeDetailModal();
    if (e.target === document.getElementById('googleDetailModal')) closeGoogleDetailModal();
});

// ==================== GOOGLE SCAN ====================

async function googleScanSingle(id, url) {
    console.log('Google Scan started:', id, url);
    
    // Extract domain from URL
    let domain = '';
    try {
        const urlObj = new URL(url);
        domain = urlObj.hostname.replace('www.', '');
    } catch(e) {
        domain = url.replace(/^https?:\/\//, '').replace(/^www\./, '').split('/')[0];
    }
    
    // Generate Google search links directly (no need to call server)
    const searchQueries = [
        { keyword: 'slot gacor', category: 'Slot Gambling', severity: 'high' },
        { keyword: 'slot online', category: 'Slot Gambling', severity: 'high' },
        { keyword: 'togel', category: 'Togel', severity: 'high' },
        { keyword: 'judi online', category: 'Online Gambling', severity: 'high' },
        { keyword: 'casino online', category: 'Casino', severity: 'high' },
        { keyword: 'sbobet', category: 'Sports Betting', severity: 'high' },
        { keyword: 'poker online', category: 'Poker', severity: 'high' },
        { keyword: 'slot88', category: 'Slot Site', severity: 'high' },
        { keyword: 'slot777', category: 'Slot Site', severity: 'high' },
        { keyword: 'pragmatic', category: 'Slot Provider', severity: 'medium' },
        { keyword: 'maxwin', category: 'Slot Terms', severity: 'medium' },
        { keyword: 'data sgp', category: 'Togel Data', severity: 'high' },
        { keyword: 'data hk', category: 'Togel Data', severity: 'high' },
        { keyword: 'bandar togel', category: 'Togel', severity: 'high' },
        { keyword: 'rtp slot', category: 'Slot Terms', severity: 'medium' },
        { keyword: 'akun pro', category: 'Slot Terms', severity: 'medium' },
        { keyword: 'scatter', category: 'Slot Terms', severity: 'low' },
        { keyword: 'bonus deposit', category: 'Gambling Promo', severity: 'medium' },
        { keyword: 'server thailand', category: 'Slot Server', severity: 'medium' },
        { keyword: 'gacor hari ini', category: 'Slot Terms', severity: 'high' },
    ];
    
    const googleLinks = searchQueries.map(q => ({
        keyword: q.keyword,
        category: q.category,
        severity: q.severity,
        url: `https://www.google.com/search?q=site:${encodeURIComponent(domain)}+${encodeURIComponent(q.keyword)}`
    }));
    
    const result = {
        id: id,
        url: url,
        domain: domain,
        google_links: googleLinks,
        quick_links: {
            all: `https://www.google.com/search?q=site:${encodeURIComponent(domain)}+slot+OR+togel+OR+judi`,
            slot: `https://www.google.com/search?q=site:${encodeURIComponent(domain)}+slot+online+slot88+gacor`,
            togel: `https://www.google.com/search?q=site:${encodeURIComponent(domain)}+togel+4d+sgp+hk`,
            casino: `https://www.google.com/search?q=site:${encodeURIComponent(domain)}+casino+poker+sbobet`
        }
    };
    
    googleScanResults[id] = result;
    
    // Show modal directly
    viewGoogleDetails(id);
}

function googleScanAllWebsites() {
    alert('Untuk Google Scan, silakan klik tombol Google pada setiap website untuk membuka panel cek manual.');
}

function viewGoogleDetails(id) {
    const result = googleScanResults[id];
    const modal = document.getElementById('googleDetailModal');
    const content = document.getElementById('googleDetailContent');

    if (!result || !result.google_links) {
        content.innerHTML = `
            <div class="empty-state">
                <i class="fab fa-google" style="font-size: 48px; color: #4285f4; margin-bottom: 16px;"></i>
                <h3>Klik tombol Google Scan terlebih dahulu</h3>
                <p>Tombol Google (kuning) di kolom Action untuk memulai scan</p>
                <button class="btn btn-secondary" onclick="closeGoogleDetailModal()" style="margin-top: 15px;">
                    <i class="fas fa-times"></i> Tutup
                </button>
            </div>
        `;
        modal.style.display = 'flex';
        return;
    }

    let linksHtml = result.google_links.map((link, idx) => {
        const severityClass = link.severity === 'high' ? 'danger' : (link.severity === 'medium' ? 'warning' : 'info');
        return `
            <div class="google-link-item">
                <div class="link-info">
                    <span class="link-keyword">${escapeHtml(link.keyword)}</span>
                    <span class="badge badge-${severityClass}">${link.severity}</span>
                    <span class="link-category">${escapeHtml(link.category)}</span>
                </div>
                <a href="${link.url}" target="_blank" class="btn btn-sm btn-primary" style="white-space: nowrap;">
                    <i class="fab fa-google"></i> Cek
                </a>
            </div>
        `;
    }).join('');

    content.innerHTML = `
        <div class="google-scan-header">
            <div class="scan-domain">
                <i class="fas fa-globe" style="font-size: 40px; color: var(--primary);"></i>
                <div>
                    <h3 style="margin: 0; font-size: 20px;">${escapeHtml(result.domain)}</h3>
                    <a href="${escapeHtml(result.url)}" target="_blank" style="font-size: 13px; color: var(--text-secondary);">${escapeHtml(result.url)}</a>
                </div>
            </div>
        </div>

        <div class="alert alert-info" style="margin: 20px 0;">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>Cara Menggunakan:</strong><br>
                Klik tombol "Cek" untuk membuka pencarian Google dengan query <code>site:${escapeHtml(result.domain)} [keyword]</code>.<br>
                Jika ada hasil yang menunjukkan konten gambling, berarti website terinfeksi.
            </div>
        </div>

        <div class="quick-links" style="margin: 20px 0;">
            <h4 style="margin-bottom: 12px; font-size: 14px;"><i class="fab fa-google"></i> Quick Check (Semua Keyword):</h4>
            <div class="link-buttons" style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="${result.quick_links.all}" target="_blank" class="btn btn-outline" style="padding: 10px 16px;">
                    <i class="fas fa-search"></i> Semua Gambling
                </a>
                <a href="${result.quick_links.slot}" target="_blank" class="btn btn-outline" style="padding: 10px 16px;">
                    <i class="fas fa-dice"></i> Slot
                </a>
                <a href="${result.quick_links.togel}" target="_blank" class="btn btn-outline" style="padding: 10px 16px;">
                    <i class="fas fa-ticket-alt"></i> Togel
                </a>
                <a href="${result.quick_links.casino}" target="_blank" class="btn btn-outline" style="padding: 10px 16px;">
                    <i class="fas fa-coins"></i> Casino
                </a>
            </div>
        </div>

        <div class="google-links-section">
            <h4 style="margin-bottom: 12px; font-size: 14px;"><i class="fas fa-list"></i> Cek Per Keyword (${result.google_links.length})</h4>
            <div class="google-links-list" style="max-height: 350px; overflow-y: auto;">
                ${linksHtml}
            </div>
        </div>
        
        <div class="modal-actions" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color); display: flex; gap: 10px;">
            <button class="btn btn-secondary" onclick="closeGoogleDetailModal()">
                <i class="fas fa-times"></i> Tutup
            </button>
        </div>
    `;

    modal.style.display = 'flex';
}
