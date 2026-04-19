<?php

/**
 * OMG-AKTIF - Public Uptime Monitor Status Page
 * Halaman publik untuk melihat status uptime website tanpa login
 */

// UptimeRobot API Configuration
define('UPTIMEROBOT_API_URL', 'https://api.uptimerobot.com/v2/');

$UPTIMEROBOT_ACCOUNTS = [
    ['name' => 'Account 1', 'read_key' => ''],

// Handle AJAX request
if (isset($_GET['action']) && $_GET['action'] === 'get_monitors') {
    header('Content-Type: application/json');
    echo json_encode(getAllMonitors($UPTIMEROBOT_ACCOUNTS));
    exit;
}

function getAllMonitors($accounts)
{
    $allMonitors = [];
    $multiHandle = curl_multi_init();
    $curlHandles = [];

    foreach ($accounts as $index => $account) {
        $params = [
            'api_key' => $account['read_key'],
            'format' => 'json',
            'response_times' => 1,
            'response_times_limit' => 1,
            'custom_uptime_ratios' => '30'
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => UPTIMEROBOT_API_URL . 'getMonitors',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        curl_multi_add_handle($multiHandle, $ch);
        $curlHandles[$index] = $ch;
    }

    $running = null;
    do {
        curl_multi_exec($multiHandle, $running);
        curl_multi_select($multiHandle);
    } while ($running > 0);

    foreach ($accounts as $index => $account) {
        $ch = $curlHandles[$index];
        $response = curl_multi_getcontent($ch);
        $data = json_decode($response, true);

        if ($data && isset($data['stat']) && $data['stat'] === 'ok') {
            $monitors = $data['monitors'] ?? [];
            foreach ($monitors as &$monitor) {
                $monitor['account_name'] = $account['name'];
                $monitor['account_index'] = $index;
                $monitor['average_response_time'] = $monitor['response_times'][0]['value'] ?? 0;
                $monitor['custom_uptime_ratio'] = $monitor['custom_uptime_ratio'] ?? '0';
            }
            $allMonitors = array_merge($allMonitors, $monitors);
        }

        curl_multi_remove_handle($multiHandle, $ch);
        curl_close($ch);
    }

    curl_multi_close($multiHandle);

    usort($allMonitors, function ($a, $b) {
        $order = [9 => 0, 8 => 1, 2 => 2, 1 => 3, 0 => 4];
        return ($order[$a['status']] ?? 5) - ($order[$b['status']] ?? 5);
    });

    return [
        'success' => true,
        'monitors' => $allMonitors,
        'total' => count($allMonitors),
        'accounts_count' => count($accounts)
    ];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OMG-AKTIF | Status Monitor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-card: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --accent: #3b82f6;
            --success: #22c55e;
            --danger: #ef4444;
            --warning: #f59e0b;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .header {
            text-align: center;
            padding: 30px 20px;
            background: linear-gradient(135deg, var(--bg-secondary), var(--bg-primary));
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .header p {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .last-update {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 15px;
            padding: 8px 16px;
            background: var(--bg-card);
            border-radius: 20px;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .last-update i {
            color: var(--accent);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: var(--bg-secondary);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 1.3rem;
        }

        .stat-total .stat-icon {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        }

        .stat-online .stat-icon {
            background: linear-gradient(135deg, #22c55e, #10b981);
        }

        .stat-offline .stat-icon {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .stat-paused .stat-icon {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        /* Filter Section */
        .filter-section {
            background: var(--bg-secondary);
            border-radius: 16px;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-box {
            flex: 1;
            min-width: 200px;
            position: relative;
        }

        .search-box i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        .search-box input {
            width: 100%;
            padding: 12px 12px 12px 42px;
            background: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: var(--text-primary);
            font-size: 0.95rem;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--accent);
        }

        .filter-select {
            padding: 12px 16px;
            background: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: var(--text-primary);
            font-size: 0.95rem;
            min-width: 150px;
        }

        .btn-refresh {
            padding: 12px 20px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-refresh:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(59, 130, 246, 0.4);
        }

        .btn-refresh:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        /* Loading */
        .loading {
            text-align: center;
            padding: 60px 20px;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid var(--bg-card);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Monitor Grid */
        .monitors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 15px;
        }

        .monitor-card {
            background: var(--bg-secondary);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .monitor-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .monitor-card.status-down {
            border-left: 4px solid var(--danger);
        }

        .monitor-card.status-up {
            border-left: 4px solid var(--success);
        }

        .monitor-card.status-paused {
            border-left: 4px solid var(--warning);
        }

        .monitor-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .monitor-name {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 4px;
            word-break: break-word;
        }

        .monitor-url {
            font-size: 0.8rem;
            color: var(--accent);
            text-decoration: none;
            word-break: break-all;
        }

        .monitor-url:hover {
            text-decoration: underline;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }

        .badge-up {
            background: rgba(34, 197, 94, 0.2);
            color: var(--success);
        }

        .badge-down {
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger);
        }

        .badge-paused {
            background: rgba(245, 158, 11, 0.2);
            color: var(--warning);
        }

        .monitor-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .monitor-stat {
            text-align: center;
        }

        .monitor-stat-label {
            font-size: 0.7rem;
            color: var(--text-secondary);
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .monitor-stat-value {
            font-size: 1rem;
            font-weight: 600;
        }

        .response-fast {
            color: var(--success);
        }

        .response-medium {
            color: var(--warning);
        }

        .response-slow {
            color: var(--danger);
        }

        .uptime-good {
            color: var(--success);
        }

        .uptime-warning {
            color: var(--warning);
        }

        .uptime-bad {
            color: var(--danger);
        }

        .account-badge {
            font-size: 0.7rem;
            padding: 3px 8px;
            border-radius: 10px;
            background: var(--bg-card);
            color: var(--text-secondary);
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 30px 20px;
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.8rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .filter-section {
                flex-direction: column;
            }

            .search-box,
            .filter-select,
            .btn-refresh {
                width: 100%;
            }

            .monitors-grid {
                grid-template-columns: 1fr;
            }

            .stat-value {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 10px;
            }

            .header {
                padding: 20px 15px;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .stat-card {
                padding: 15px;
            }

            .monitor-card {
                padding: 15px;
            }
        }
    </style>
</head>

<body>
    <header class="header">
        <h1><i class="fas fa-heartbeat"></i> OMG-AKTIF</h1>
        <p>Real-time Website Status Monitor</p>
        <div class="last-update">
            <i class="fas fa-clock"></i>
            <span id="lastUpdate">Loading...</span>
        </div>
    </header>

    <div class="container">
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card stat-total">
                <div class="stat-icon"><i class="fas fa-globe"></i></div>
                <div class="stat-value" id="totalCount">-</div>
                <div class="stat-label">Total Monitor</div>
            </div>
            <div class="stat-card stat-online">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-value" id="upCount">-</div>
                <div class="stat-label">Online</div>
            </div>
            <div class="stat-card stat-offline">
                <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                <div class="stat-value" id="downCount">-</div>
                <div class="stat-label">Offline</div>
            </div>
            <div class="stat-card stat-paused">
                <div class="stat-icon"><i class="fas fa-pause-circle"></i></div>
                <div class="stat-value" id="pausedCount">-</div>
                <div class="stat-label">Paused</div>
            </div>
        </div>

        <!-- Filter -->
        <div class="filter-section">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Cari monitor...">
            </div>
            <select class="filter-select" id="statusFilter">
                <option value="">Semua Status</option>
                <option value="2">Online</option>
                <option value="9">Offline</option>
                <option value="0">Paused</option>
            </select>
            <select class="filter-select" id="accountFilter">
                <option value="">Semua Account</option>
            </select>
            <button class="btn-refresh" id="refreshBtn" onclick="loadMonitors()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>

        <!-- Loading -->
        <div class="loading" id="loadingState">
            <div class="spinner"></div>
            <p>Mengambil data dari semua akun...</p>
        </div>

        <!-- Monitors Grid -->
        <div class="monitors-grid" id="monitorsGrid" style="display:none;"></div>
    </div>

    <footer class="footer">
        <p>Auto-refresh setiap 60 detik | Data dari UptimeRobot API</p>
    </footer>

    <script>
        let monitorsData = [];

        document.addEventListener('DOMContentLoaded', () => {
            loadMonitors();
            setInterval(loadMonitors, 60000);
        });

        document.getElementById('searchInput').addEventListener('input', filterMonitors);
        document.getElementById('statusFilter').addEventListener('change', filterMonitors);
        document.getElementById('accountFilter').addEventListener('change', filterMonitors);

        function loadMonitors() {
            const btn = document.getElementById('refreshBtn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            btn.disabled = true;

            fetch('?action=get_monitors')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        monitorsData = data.monitors;
                        renderMonitors(data.monitors);
                        updateStats(data.monitors);
                        populateAccountFilter(data.monitors);
                        document.getElementById('lastUpdate').textContent = new Date().toLocaleString('id-ID');
                        document.getElementById('loadingState').style.display = 'none';
                        document.getElementById('monitorsGrid').style.display = 'grid';
                    }
                    btn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
                    btn.disabled = false;
                })
                .catch(err => {
                    document.getElementById('loadingState').innerHTML = `
                <i class="fas fa-exclamation-triangle" style="font-size:3rem;color:var(--danger);margin-bottom:15px;"></i>
                <p>Gagal memuat data. <a href="javascript:loadMonitors()" style="color:var(--accent)">Coba lagi</a></p>
            `;
                    btn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
                    btn.disabled = false;
                });
        }

        function renderMonitors(monitors) {
            const grid = document.getElementById('monitorsGrid');

            if (!monitors.length) {
                grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-secondary)">Tidak ada monitor ditemukan</div>';
                return;
            }

            grid.innerHTML = monitors.map(m => {
                const status = getStatus(m.status);
                const rt = m.average_response_time || 0;
                const rtClass = rt < 500 ? 'response-fast' : rt < 1000 ? 'response-medium' : 'response-slow';
                const uptime = parseFloat(m.custom_uptime_ratio || 0);
                const uptimeClass = uptime >= 99 ? 'uptime-good' : uptime >= 95 ? 'uptime-warning' : 'uptime-bad';

                return `
            <div class="monitor-card status-${status.key}" data-status="${m.status}" data-account="${esc(m.account_name)}" data-name="${esc(m.friendly_name)}">
                <div class="monitor-header">
                    <div>
                        <div class="monitor-name">${esc(m.friendly_name)}</div>
                        <a href="${esc(m.url)}" target="_blank" class="monitor-url">${esc(m.url)}</a>
                    </div>
                    <span class="status-badge badge-${status.key}">
                        <i class="fas ${status.icon}"></i> ${status.text}
                    </span>
                </div>
                <div class="monitor-stats">
                    <div class="monitor-stat">
                        <div class="monitor-stat-label">Response</div>
                        <div class="monitor-stat-value ${rtClass}">${rt}ms</div>
                    </div>
                    <div class="monitor-stat">
                        <div class="monitor-stat-label">Uptime</div>
                        <div class="monitor-stat-value ${uptimeClass}">${uptime.toFixed(1)}%</div>
                    </div>
                    <div class="monitor-stat">
                        <div class="monitor-stat-label">Account</div>
                        <div class="monitor-stat-value"><span class="account-badge">${esc(m.account_name)}</span></div>
                    </div>
                </div>
            </div>`;
            }).join('');
        }

        function updateStats(monitors) {
            document.getElementById('totalCount').textContent = monitors.length;
            document.getElementById('upCount').textContent = monitors.filter(m => m.status === 2).length;
            document.getElementById('downCount').textContent = monitors.filter(m => m.status === 9 || m.status === 8).length;
            document.getElementById('pausedCount').textContent = monitors.filter(m => m.status === 0).length;
        }

        function populateAccountFilter(monitors) {
            const select = document.getElementById('accountFilter');
            const val = select.value;
            const accounts = [...new Set(monitors.map(m => m.account_name))];
            select.innerHTML = '<option value="">Semua Account</option>' + accounts.map(a => `<option value="${a}">${a}</option>`).join('');
            select.value = val;
        }

        function filterMonitors() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const status = document.getElementById('statusFilter').value;
            const account = document.getElementById('accountFilter').value;

            document.querySelectorAll('.monitor-card').forEach(card => {
                const match = (!search || card.dataset.name.toLowerCase().includes(search) || card.querySelector('.monitor-url').textContent.toLowerCase().includes(search)) &&
                    (!status || card.dataset.status === status) &&
                    (!account || card.dataset.account === account);
                card.style.display = match ? '' : 'none';
            });
        }

        function getStatus(status) {
            const map = {
                2: {
                    key: 'up',
                    text: 'Online',
                    icon: 'fa-check-circle'
                },
                9: {
                    key: 'down',
                    text: 'Offline',
                    icon: 'fa-times-circle'
                },
                8: {
                    key: 'down',
                    text: 'Down',
                    icon: 'fa-exclamation-circle'
                },
                0: {
                    key: 'paused',
                    text: 'Paused',
                    icon: 'fa-pause-circle'
                },
                1: {
                    key: 'paused',
                    text: 'Pending',
                    icon: 'fa-clock'
                }
            };
            return map[status] || {
                key: 'paused',
                text: 'Unknown',
                icon: 'fa-question-circle'
            };
        }

        function esc(t) {
            if (!t) return '';
            return String(t).replace(/[&<>"']/g, c => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            } [c]));
        }
    </script>
</body>

</html>