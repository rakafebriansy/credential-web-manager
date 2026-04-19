<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/init.php';

if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

$user_role = $_SESSION['role'] ?? 'admin';
if ($user_role != 'admin') {
    header("Location: ../index.php");
    exit();
}


// Filter
$filter_holding = $_GET['holding'] ?? '';
$search_query = $_GET['search'] ?? '';

$filtered_data = $bisnis_data;
if (!empty($filter_holding)) {
    $filtered_data = array_filter($bisnis_data, function ($key) use ($filter_holding) {
        return $key === $filter_holding;
    }, ARRAY_FILTER_USE_KEY);
}
if (!empty($search_query)) {
    $sq = strtolower($search_query);
    $filtered_data = array_filter($bisnis_data, function ($item) use ($sq) {
        return strpos(strtolower($item['nama_lengkap']), $sq) !== false ||
            strpos(strtolower($item['profil_mitra']), $sq) !== false ||
            strpos(strtolower($item['masalah_utama']), $sq) !== false ||
            strpos(strtolower($item['solusi_jasa']), $sq) !== false;
    });
}

// Statistics
$total_holding = count($bisnis_data);
$total_solusi = 0;
$total_value_props = 0;
foreach ($bisnis_data as $item) {
    $total_solusi++;
    $total_value_props += count($item['value_props']);
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - OneSCI</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (function() {
            const t = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
    <style>
        :root {
            --bg-primary: #f8fafc;
            --bg-secondary: #f1f5f9;
            --card-bg: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --sidebar-bg: #1e293b;
        }

        [data-theme="dark"] {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --card-bg: #1e293b;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --border-color: #334155;
            --sidebar-bg: #0f172a;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: var(--sidebar-bg);
            padding: 20px 0;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h2 {
            color: #fff;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-header h2 i {
            color: #6366f1;
        }

        .sidebar-nav {
            padding: 20px 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.2s;
            font-size: 14px;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .nav-item.active {
            background: rgba(99, 102, 241, 0.2);
            color: #fff;
            border-right: 3px solid #6366f1;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .main-content {
            margin-left: 260px;
            min-height: 100vh;
            background: var(--bg-primary);
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            background: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
        }

        .content {
            padding: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stat-content h3 {
            font-size: 28px;
            font-weight: 700;
        }

        .stat-content p {
            color: var(--text-secondary);
            font-size: 14px;
            margin-top: 4px;
        }

        .toolbar {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
        }

        .toolbar-left {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            padding: 12px 16px 12px 44px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            background: var(--card-bg);
            color: var(--text-primary);
            font-size: 14px;
            width: 300px;
            transition: all 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: #6366f1;
        }

        .search-box i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        .filter-select {
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            background: var(--card-bg);
            color: var(--text-primary);
            font-size: 14px;
            min-width: 180px;
        }

        .btn {
            padding: 10px 18px;
            border-radius: 10px;
            font-size: 14px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            text-decoration: none;
            font-weight: 500;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-primary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .btn-info {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
        }

        .btn-secondary {
            background: var(--card-bg);
            color: var(--text-primary);
            border: 2px solid var(--border-color);
        }

        .proposals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(420px, 1fr));
            gap: 24px;
        }

        .proposal-card {
            background: var(--card-bg);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
        }

        .proposal-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .proposal-header {
            padding: 20px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }

        .holding-info {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .holding-badge {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
            color: white;
        }

        .holding-name h3 {
            font-size: 16px;
            margin-bottom: 2px;
        }

        .holding-name p {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .view-btn {
            padding: 10px 18px;
            border-radius: 10px;
            background: var(--bg-secondary);
            color: var(--text-primary);
            border: none;
            cursor: pointer;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .view-btn:hover {
            background: #6366f1;
            color: white;
        }

        .proposal-body {
            padding: 24px;
        }

        .section-item {
            margin-bottom: 18px;
        }

        .section-item:last-child {
            margin-bottom: 0;
        }

        .section-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .section-content {
            font-size: 14px;
            line-height: 1.6;
            color: var(--text-primary);
        }

        .section-content.truncate {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .value-props-mini {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }

        .value-prop-tag {
            padding: 6px 12px;
            background: linear-gradient(135deg, #ede9fe, #ddd6fe);
            color: #5b21b6;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .proposal-footer {
            padding: 16px 24px;
            background: var(--bg-secondary);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-stats {
            display: flex;
            gap: 20px;
        }

        .footer-stat {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: var(--text-secondary);
        }

        .footer-stat i {
            font-size: 14px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 20px;
            backdrop-filter: blur(5px);
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: var(--card-bg);
            border-radius: 24px;
            width: 100%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: 28px 32px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background: var(--card-bg);
            z-index: 10;
        }

        .modal-header h2 {
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modal-close {
            background: var(--bg-secondary);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            font-size: 20px;
            cursor: pointer;
            color: var(--text-secondary);
            transition: all 0.2s;
        }

        .modal-close:hover {
            background: #ef4444;
            color: white;
        }

        .modal-body {
            padding: 32px;
        }

        .detail-section {
            margin-bottom: 28px;
            padding-bottom: 28px;
            border-bottom: 1px solid var(--border-color);
        }

        .detail-section:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .detail-section h4 {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .detail-section h4 i {
            font-size: 16px;
        }

        .detail-section p {
            font-size: 15px;
            line-height: 1.8;
            color: var(--text-primary);
        }

        .detail-highlight {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: 1px solid #f59e0b;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 28px;
        }

        .detail-highlight h4 {
            color: #92400e;
        }

        .detail-highlight p {
            color: #78350f;
            font-style: italic;
            font-size: 16px;
        }

        .detail-problem {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            border: 1px solid #f87171;
            border-radius: 16px;
            padding: 24px;
        }

        .detail-problem h4 {
            color: #991b1b;
        }

        .detail-problem p {
            color: #7f1d1d;
        }

        .detail-solution {
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
            border: 1px solid #4ade80;
            border-radius: 16px;
            padding: 24px;
        }

        .detail-solution h4 {
            color: #166534;
        }

        .detail-solution p {
            color: #14532d;
        }

        .value-props-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .value-prop-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 16px;
            background: linear-gradient(135deg, #ede9fe, #ddd6fe);
            border-radius: 12px;
        }

        .value-prop-item i {
            color: #7c3aed;
            font-size: 18px;
            margin-top: 2px;
        }

        .value-prop-item span {
            font-size: 15px;
            color: #5b21b6;
            font-weight: 500;
        }

        .detail-target {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            border: 1px solid #60a5fa;
            border-radius: 16px;
            padding: 24px;
        }

        .detail-target h4 {
            color: #1e40af;
        }

        .detail-target p {
            color: #1e3a8a;
        }

        .modal-footer {
            padding: 24px 32px;
            border-top: 1px solid var(--border-color);
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            position: sticky;
            bottom: 0;
            background: var(--card-bg);
        }

        @media print {

            .sidebar,
            .topbar,
            .toolbar,
            .modal {
                display: none !important;
            }

            .main-content {
                margin-left: 0 !important;
            }

            .proposal-card {
                break-inside: avoid;
            }
        }

        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }

            .proposals-grid {
                grid-template-columns: 1fr;
            }

            .search-box input {
                width: 100%;
            }
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="../index.php" style="text-decoration: none;">
                <h2><i class="fas fa-globe"></i> OneSCI</h2>
            </a>
        </div>
        <nav class="sidebar-nav">
            <a href="../index.php" class="nav-item"><i class="fas fa-home"></i> Dashboard</a>
            <a href="../websites.php" class="nav-item"><i class="fas fa-globe"></i> Websites</a>
            <a href="../protection.php" class="nav-item"><i class="fas fa-shield-alt"></i> Protection</a>
            <a href="../tiket_kunjungan.php" class="nav-item"><i class="fas fa-ticket-alt"></i> Tiket Kunjungan</a>
            <a href="index.php" class="nav-item active"><i class="fas fa-briefcase"></i> Bisnis Proposal</a>
            <a href="hosting.php" class="nav-item"><i class="fas fa-server"></i> Hosting & Domain</a>
        </nav>
        <div class="sidebar-footer">
            <a href="../logout.php" class="nav-item" style="color: #ef4444;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="topbar">
            <div>
                <h1 style="font-size: 20px;"><i class="fas fa-briefcase" style="color: #8b5cf6;"></i> Bisnis Proposal</h1>
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <button onclick="toggleTheme()" style="background: none; border: none; cursor: pointer; font-size: 18px; color: var(--text-secondary);"><i class="fas fa-moon" id="themeIcon"></i></button>
                <span style="color: var(--text-secondary); font-size: 14px;"><i class="fas fa-user"></i> <?php echo htmlspecialchars($user['full_name'] ?? 'Admin'); ?></span>
            </div>
        </div>

        <div class="content">
            <!-- Page Header -->
            <div style="margin-bottom: 30px;">
                <h1 style="font-size: 32px; margin-bottom: 8px;"><i class="fas fa-briefcase" style="color: #8b5cf6;"></i> Bisnis Proposal</h1>
                <p style="color: var(--text-secondary); font-size: 16px;">Database proposal bisnis untuk setiap holding SCI Group</p>
            </div>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #8b5cf6, #6366f1);"><i class="fas fa-building"></i></div>
                    <div class="stat-content">
                        <h3><?php echo $total_holding; ?></h3>
                        <p>Total Holding</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669);"><i class="fas fa-lightbulb"></i></div>
                    <div class="stat-content">
                        <h3><?php echo $total_solusi; ?></h3>
                        <p>Solusi Bisnis</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);"><i class="fas fa-star"></i></div>
                    <div class="stat-content">
                        <h3><?php echo $total_value_props; ?></h3>
                        <p>Value Propositions</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);"><i class="fas fa-handshake"></i></div>
                    <div class="stat-content">
                        <h3><?php echo count($filtered_data); ?></h3>
                        <p>Ditampilkan</p>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="toolbar">
                <div class="toolbar-left">
                    <form method="GET" id="filterForm" style="display: flex; gap: 15px; align-items: center;">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Cari proposal..." value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>
                        <select name="holding" class="filter-select" onchange="this.form.submit()">
                            <option value="">Semua Holding</option>
                            <?php foreach (array_keys($bisnis_data) as $h): ?>
                                <option value="<?php echo $h; ?>" <?php echo $filter_holding == $h ? 'selected' : ''; ?>><?php echo $h; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button class="btn btn-secondary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
                    <button class="btn btn-info" onclick="exportData()"><i class="fas fa-file-excel"></i> Export</button>
                </div>
            </div>

            <!-- Proposals Grid -->
            <div class="proposals-grid">
                <?php foreach ($filtered_data as $holding => $data): ?>
                    <div class="proposal-card">
                        <div class="proposal-header">
                            <div class="holding-info">
                                <div class="holding-badge" style="background: <?php echo $data['color']; ?>;"><?php echo $holding; ?></div>
                                <div class="holding-name">
                                    <h3><?php echo $holding; ?></h3>
                                    <p><?php echo $data['nama_lengkap']; ?></p>
                                </div>
                            </div>
                            <button class="view-btn" onclick='viewDetail("<?php echo $holding; ?>")'>
                                <i class="fas fa-eye"></i> Detail
                            </button>
                        </div>
                        <div class="proposal-body">
                            <div class="section-item">
                                <div class="section-label"><i class="fas fa-user-tie"></i> Profil Mitra</div>
                                <div class="section-content truncate"><?php echo htmlspecialchars($data['profil_mitra']); ?></div>
                            </div>
                            <div class="section-item">
                                <div class="section-label"><i class="fas fa-exclamation-triangle"></i> Masalah Utama</div>
                                <div class="section-content truncate"><?php echo htmlspecialchars($data['masalah_utama']); ?></div>
                            </div>
                            <div class="section-item">
                                <div class="section-label"><i class="fas fa-star"></i> Value Proposition</div>
                                <div class="value-props-mini">
                                    <?php foreach (array_slice($data['value_props'], 0, 2) as $vp): ?>
                                        <span class="value-prop-tag"><?php echo htmlspecialchars(substr($vp, 0, 30)); ?><?php echo strlen($vp) > 30 ? '...' : ''; ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($data['value_props']) > 2): ?>
                                        <span class="value-prop-tag">+<?php echo count($data['value_props']) - 2; ?> lagi</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="proposal-footer">
                            <div class="footer-stats">
                                <div class="footer-stat"><i class="fas fa-star"></i> <?php echo count($data['value_props']); ?> Value Props</div>
                                <div class="footer-stat"><i class="fas fa-lightbulb"></i> 1 Solusi</div>
                            </div>
                            <button class="btn btn-primary" onclick='viewDetail("<?php echo $holding; ?>")'><i class="fas fa-arrow-right"></i></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal" id="detailModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-briefcase"></i> <span id="modalHolding"></span></h2>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody"></div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal()">Tutup</button>
                <button class="btn btn-info" onclick="copyPitch()"><i class="fas fa-copy"></i> Copy Pitch</button>
                <button class="btn btn-primary" onclick="printDetail()"><i class="fas fa-print"></i> Print</button>
            </div>
        </div>
    </div>

    <script>
        const bisnisData = <?php echo json_encode($bisnis_data); ?>;
        let currentHolding = '';

        function toggleTheme() {
            const html = document.documentElement;
            const newTheme = html.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            document.getElementById('themeIcon').className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
        document.getElementById('themeIcon').className = (localStorage.getItem('theme') || 'light') === 'dark' ? 'fas fa-sun' : 'fas fa-moon';

        function viewDetail(holding) {
            currentHolding = holding;
            const data = bisnisData[holding];
            document.getElementById('modalHolding').innerHTML = `<span style="display:inline-block;width:40px;height:40px;background:${data.color};border-radius:10px;text-align:center;line-height:40px;font-size:12px;color:white;margin-right:12px;">${holding}</span> ${data.nama_lengkap}`;

            let html = '';

            // Opening Pitch
            html += `<div class="detail-highlight">
        <h4><i class="fas fa-microphone"></i> Opening Pitch</h4>
        <p>"${data.opening_pitch}"</p>
    </div>`;

            // Profil Mitra
            html += `<div class="detail-section">
        <h4><i class="fas fa-user-tie"></i> Profil Mitra</h4>
        <p>${data.profil_mitra}</p>
    </div>`;

            // Masalah Utama
            html += `<div class="detail-problem">
        <h4><i class="fas fa-exclamation-triangle"></i> Masalah Utama</h4>
        <p>${data.masalah_utama}</p>
    </div>`;

            // Solusi
            html += `<div class="detail-solution" style="margin-top: 20px;">
        <h4><i class="fas fa-lightbulb"></i> Solusi / Jasa yang Ditawarkan</h4>
        <p>${data.solusi_jasa}</p>
    </div>`;

            // Value Propositions
            html += `<div class="detail-section" style="margin-top: 28px;">
        <h4><i class="fas fa-star"></i> Value Proposition</h4>
        <div class="value-props-list">`;
            data.value_props.forEach(vp => {
                html += `<div class="value-prop-item"><i class="fas fa-check-circle"></i><span>${vp}</span></div>`;
            });
            html += `</div></div>`;

            // Skema Kerjasama
            html += `<div class="detail-section">
        <h4><i class="fas fa-handshake"></i> Skema Kerjasama</h4>
        <p>${data.skema_kerjasama}</p>
    </div>`;

            // Target Bisnis
            html += `<div class="detail-target">
        <h4><i class="fas fa-bullseye"></i> Target Bisnis</h4>
        <p>${data.target_bisnis}</p>
    </div>`;

            document.getElementById('modalBody').innerHTML = html;
            document.getElementById('detailModal').classList.add('show');
        }

        function closeModal() {
            document.getElementById('detailModal').classList.remove('show');
        }

        function copyPitch() {
            const data = bisnisData[currentHolding];
            navigator.clipboard.writeText(data.opening_pitch).then(() => {
                alert('Opening pitch berhasil dicopy!');
            });
        }

        function printDetail() {
            window.print();
        }

        function exportData() {
            let csv = 'Holding,Nama Lengkap,Profil Mitra,Masalah Utama,Solusi Jasa,Value Prop 1,Value Prop 2,Value Prop 3,Skema Kerjasama,Target Bisnis,Opening Pitch\n';
            Object.entries(bisnisData).forEach(([holding, data]) => {
                csv += `"${holding}","${data.nama_lengkap}","${data.profil_mitra.replace(/"/g,'""')}","${data.masalah_utama.replace(/"/g,'""')}","${data.solusi_jasa.replace(/"/g,'""')}","${data.value_props[0] || ''}","${data.value_props[1] || ''}","${data.value_props[2] || ''}","${data.skema_kerjasama.replace(/"/g,'""')}","${data.target_bisnis.replace(/"/g,'""')}","${data.opening_pitch.replace(/"/g,'""')}"\n`;
            });
            const blob = new Blob([csv], {
                type: 'text/csv'
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'bisnis_proposal_' + new Date().toISOString().slice(0, 10) + '.csv';
            a.click();
        }

        // Close modal on outside click
        document.getElementById('detailModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        // Search on enter
        document.querySelector('.search-box input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') document.getElementById('filterForm').submit();
        });
    </script>
</body>

</html>