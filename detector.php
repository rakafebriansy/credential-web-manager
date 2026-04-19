<?php
require_once 'config/init.php';

if (!isLoggedIn()) {
    redirect('login');
}

$page_title = 'Website Malware Detector';
$user = getCurrentUser($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .detector-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #1e1e2e 0%, #2d2d44 100%);
            padding: 40px 20px;
        }
        .detector-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .detector-header {
            text-align: center;
            margin-bottom: 40px;
            color: white;
        }
        .detector-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        .detector-header p {
            color: #a0a0b0;
            font-size: 16px;
        }
        .detector-form {
            background: #2d2d44;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
        }
        .detector-form label {
            display: block;
            color: #cdd6f4;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .detector-form input {
            width: 100%;
            padding: 16px 20px;
            background: #1e1e2e;
            border: 2px solid #3d3d5c;
            border-radius: 12px;
            color: #cdd6f4;
            font-size: 16px;
            margin-bottom: 20px;
        }
        .detector-form input:focus {
            outline: none;
            border-color: #667eea;
        }
        .detector-form button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
        }
        .detector-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        .detector-form button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        .result-container {
            background: #2d2d44;
            border-radius: 16px;
            overflow: hidden;
            display: none;
        }
        .result-header {
            padding: 24px 30px;
            border-bottom: 1px solid #3d3d5c;
        }
        .result-header h2 {
            color: #cdd6f4;
            font-size: 22px;
            margin: 0;
        }
        .result-body {
            padding: 30px;
        }
        .status-banner {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 18px 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 16px;
        }
        .status-banner i {
            font-size: 24px;
        }
        .status-banner.safe {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .status-banner.warning {
            background: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        .status-banner.danger {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .status-banner.critical {
            background: rgba(239, 68, 68, 0.25);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.5);
        }
        .detection-section {
            margin-top: 24px;
        }
        .detection-section h3 {
            color: #cdd6f4;
            font-size: 18px;
            margin-bottom: 16px;
        }
        .detection-card {
            background: #1e1e2e;
            border-radius: 12px;
            padding: 18px 20px;
            margin-bottom: 12px;
            border-left: 4px solid;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .detection-card.high {
            border-left-color: #ef4444;
        }
        .detection-card.medium {
            border-left-color: #f59e0b;
        }
        .detection-card.low {
            border-left-color: #3b82f6;
        }
        .detection-card .info {
            flex: 1;
        }
        .detection-card .info p {
            margin: 0 0 8px 0;
            color: #a0a0b0;
            font-size: 14px;
        }
        .detection-card .info p:last-child {
            margin-bottom: 0;
        }
        .detection-card .info strong {
            color: #cdd6f4;
        }
        .detection-card .badge {
            background: #ef4444;
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            flex-shrink: 0;
            margin-left: 16px;
        }
        .detection-card.medium .badge {
            background: #f59e0b;
        }
        .detection-card.low .badge {
            background: #3b82f6;
        }
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-box {
            background: #1e1e2e;
            padding: 16px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-box .label {
            font-size: 12px;
            color: #a0a0b0;
            margin-bottom: 6px;
        }
        .stat-box .value {
            font-size: 20px;
            font-weight: 600;
            color: #cdd6f4;
        }
        .stat-box .value.danger {
            color: #ef4444;
        }
        .stat-box .value.success {
            color: #10b981;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #a0a0b0;
            text-decoration: none;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .back-link:hover {
            color: #cdd6f4;
        }
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 40px;
            color: #a0a0b0;
        }
        .loading-spinner i {
            font-size: 40px;
            margin-bottom: 16px;
            color: #667eea;
        }
        @media (max-width: 768px) {
            .detection-card {
                flex-direction: column;
            }
            .detection-card .badge {
                margin-left: 0;
                margin-top: 12px;
                align-self: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="detector-page">
        <div class="detector-container">
            <a href="protection" class="back-link">
                <i class="fas fa-arrow-left"></i> Kembali ke Protection
            </a>
            
            <div class="detector-header">
                <h1><i class="fas fa-shield-virus"></i> Website Malware Detector</h1>
                <p>Analisis website untuk mendeteksi konten judi, slot, togel, dan malware lainnya</p>
            </div>
            
            <div class="detector-form">
                <label>URL Website</label>
                <input type="url" id="urlInput" placeholder="https://example.com" required>
                <button type="button" id="scanBtn" onclick="analyzeWebsite()">
                    <i class="fas fa-search"></i> Analisis Website
                </button>
            </div>
            
            <div class="loading-spinner" id="loadingSpinner">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Sedang menganalisis website...</p>
            </div>
            
            <div class="result-container" id="resultContainer">
                <div class="result-header">
                    <h2>Hasil Analisis</h2>
                </div>
                <div class="result-body" id="resultBody">
                    <!-- Results will be loaded here -->
                </div>
            </div>
        </div>
    </div>
    
    <script>
        async function analyzeWebsite() {
            const url = document.getElementById('urlInput').value.trim();
            if (!url) {
                alert('Masukkan URL website');
                return;
            }
            
            // Validate URL
            try {
                new URL(url);
            } catch (e) {
                alert('URL tidak valid. Pastikan format: https://example.com');
                return;
            }
            
            const btn = document.getElementById('scanBtn');
            const loading = document.getElementById('loadingSpinner');
            const resultContainer = document.getElementById('resultContainer');
            const resultBody = document.getElementById('resultBody');
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scanning...';
            loading.style.display = 'block';
            resultContainer.style.display = 'none';
            
            try {
                const formData = new FormData();
                formData.append('url', url);
                formData.append('id', 0);
                
                const response = await fetch('scan_website', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                console.log('Scan result:', result);
                
                loading.style.display = 'none';
                resultContainer.style.display = 'block';
                
                // Determine status class
                let statusClass = 'safe';
                let statusIcon = 'check-circle';
                if (result.is_infected) {
                    if (result.detection_count > 10) {
                        statusClass = 'critical';
                        statusIcon = 'skull-crossbones';
                    } else if (result.detection_count > 3) {
                        statusClass = 'danger';
                        statusIcon = 'exclamation-triangle';
                    } else {
                        statusClass = 'warning';
                        statusIcon = 'exclamation-circle';
                    }
                }
                
                // Build detections HTML
                let detectionsHtml = '';
                if (result.detections && result.detections.length > 0) {
                    result.detections.forEach(d => {
                        detectionsHtml += `
                            <div class="detection-card ${d.severity}">
                                <div class="info">
                                    <p><strong>Tipe:</strong> ${d.type}</p>
                                    <p><strong>Kata Kunci:</strong> ${d.keyword}</p>
                                    <p><strong>Konten:</strong> ${escapeHtml(d.content)}</p>
                                </div>
                                <span class="badge">${d.keyword}</span>
                            </div>
                        `;
                    });
                }
                
                resultBody.innerHTML = `
                    <div class="status-banner ${statusClass}">
                        <i class="fas fa-${statusIcon}"></i>
                        <span><strong>Status:</strong> ${result.infection_status}</span>
                    </div>
                    
                    <div class="stats-row">
                        <div class="stat-box">
                            <div class="label">HTTP Status</div>
                            <div class="value">${result.http_code}</div>
                        </div>
                        <div class="stat-box">
                            <div class="label">Response Time</div>
                            <div class="value">${result.response_time}ms</div>
                        </div>
                        <div class="stat-box">
                            <div class="label">Deteksi</div>
                            <div class="value ${result.detection_count > 0 ? 'danger' : 'success'}">${result.detection_count}</div>
                        </div>
                    </div>
                    
                    ${result.detections && result.detections.length > 0 ? `
                    <div class="detection-section">
                        <h3>Deteksi Frontend:</h3>
                        ${detectionsHtml}
                    </div>
                    ` : ''}
                    
                    ${result.error ? `
                    <div class="status-banner warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>${result.error}</span>
                    </div>
                    ` : ''}
                `;
                
            } catch (error) {
                console.error('Error:', error);
                loading.style.display = 'none';
                resultContainer.style.display = 'block';
                resultBody.innerHTML = `
                    <div class="status-banner danger">
                        <i class="fas fa-times-circle"></i>
                        <span>Error: ${error.message}</span>
                    </div>
                `;
            }
            
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-search"></i> Analisis Website';
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Allow Enter key to submit
        document.getElementById('urlInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                analyzeWebsite();
            }
        });
    </script>
</body>
</html>
