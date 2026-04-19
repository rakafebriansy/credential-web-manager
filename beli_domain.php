<?php
require_once 'config/init.php';

if (!isLoggedIn()) {
    redirect('login');
}

// Restrict access for user_ojs
if (isset($_SESSION['role']) && $_SESSION['role'] == 'user_ojs') {
    redirect('ojs_progress');
}

$page_title = 'Beli Domain';
$current_page = 'beli_domain.php';

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $domain = trim($_POST['domain'] ?? '');
        $registrar = trim($_POST['registrar'] ?? '');
        $harga = floatval($_POST['harga'] ?? 0);
        $tanggal_beli = $_POST['tanggal_beli'] ?? date('Y-m-d');
        $tanggal_expired = $_POST['tanggal_expired'] ?? '';
        $holding = trim($_POST['holding'] ?? '');
        $pic = trim($_POST['pic'] ?? '');
        $status = $_POST['status'] ?? 'active';
        $catatan = trim($_POST['catatan'] ?? '');
        
        if (empty($domain)) {
            $error_message = 'Nama domain wajib diisi!';
        } else {
            $stmt = $conn->prepare("INSERT INTO domain_purchases (domain, registrar, harga, tanggal_beli, tanggal_expired, holding, pic, status, catatan, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssdssssss", $domain, $registrar, $harga, $tanggal_beli, $tanggal_expired, $holding, $pic, $status, $catatan);
            
            if ($stmt->execute()) {
                $success_message = 'Domain berhasil ditambahkan!';
            } else {
                $error_message = 'Gagal menambahkan domain: ' . $conn->error;
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM domain_purchases WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $success_message = 'Domain berhasil dihapus!';
            }
        }
    } elseif ($action === 'update_status') {
        $id = intval($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE domain_purchases SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $id);
            $stmt->execute();
        }
    }
}

// Get all domains - order by expired date (nearest first)
$domains = [];
$result = mysqli_query($conn, "SELECT * FROM domain_purchases ORDER BY tanggal_expired ASC");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $domains[] = $row;
    }
}

// Stats
$total = count($domains);
$active = count(array_filter($domains, fn($d) => $d['status'] == 'active'));
$expired = count(array_filter($domains, fn($d) => $d['status'] == 'expired'));
$pending = count(array_filter($domains, fn($d) => $d['status'] == 'pending'));

$holding_colors = ['RIN'=>'#FF6B6B','GP'=>'#4ECDC4','PI'=>'#45B7D1','IJL'=>'#FFA07A','AL-MAKKI'=>'#98D8C8','RIVIERA'=>'#F7DC6F','TADCENT'=>'#BB8FCE','EDC'=>'#85C1E2','LPK'=>'#F8B739','GB'=>'#52B788','STAIKU'=>'#E63946','POLTEK'=>'#457B9D','SCI'=>'#E76F51','PUBLIKASIKU'=>'#9B59B6','EBISKRAF'=>'#3498DB','MSDM'=>'#1ABC9C','LPK-STC'=>'#E67E22','FOUNDATION'=>'#9B59B6','IB-FOUNDATION'=>'#34495E','SBS'=>'#16A085'];

include 'includes/header.php';
?>

<div class="main-content">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <div class="content-header" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px;">
            <div>
                <h1><i class="fas fa-shopping-cart"></i> Beli Domain</h1>
                <p>Kelola pembelian domain untuk semua holding</p>
            </div>
            <a href="domain_notif_config" style="padding: 10px 20px; background: linear-gradient(135deg, #25D366, #128C7E); color: white; border-radius: 10px; text-decoration: none; font-size: 14px; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fab fa-whatsapp"></i> Notifikasi WA
            </a>
        </div>

        <?php if ($success_message): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon" style="background:linear-gradient(135deg,#667eea,#764ba2)"><i class="fas fa-globe"></i></div>
                <div class="stat-info">
                    <h3><?php echo $total; ?></h3>
                    <p>Total Domain</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:linear-gradient(135deg,#43e97b,#38f9d7)"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <h3><?php echo $active; ?></h3>
                    <p>Active</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:linear-gradient(135deg,#fa709a,#fee140)"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <h3><?php echo $pending; ?></h3>
                    <p>Pending</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:linear-gradient(135deg,#f093fb,#f5576c)"><i class="fas fa-times-circle"></i></div>
                <div class="stat-info">
                    <h3><?php echo $expired; ?></h3>
                    <p>Expired</p>
                </div>
            </div>
        </div>

        <!-- Add Domain Form -->
        <div class="card add-domain-card">
            <div class="card-header">
                <h3><i class="fas fa-plus-circle"></i> Tambah Domain Baru</h3>
            </div>
            <div class="card-body">
                <form method="POST" class="domain-form">
                    <input type="hidden" name="action" value="add">
                    <div class="form-row">
                        <div class="form-col">
                            <label><i class="fas fa-globe"></i> Nama Domain</label>
                            <input type="text" name="domain" placeholder="contoh.com" required>
                        </div>
                        <div class="form-col">
                            <label><i class="fas fa-store"></i> Registrar</label>
                            <select name="registrar">
                                <option value="">Pilih Registrar</option>
                                <option value="hosting.greenvest@gmail.com">hosting.greenvest@gmail.com</option>
                                <option value="absennyuk.com">absennyuk.com</option>
                                <option value="hosting.publikasiindonesia@gmail.com">hosting.publikasiindonesia@gmail.com</option>
                                <option value="hosting.ridwaninstitute@gmail.com">hosting.ridwaninstitute@gmail.com</option>
                                <option value="hosting.syntax@gmail.com">hosting.syntax@gmail.com</option>
                                <option value="hosting.ubs@gmail.com">hosting.ubs@gmail.com</option>
                                <option value="valensitech@gmail.com">valensitech@gmail.com</option>
                                <option value="priasains@gmail.com">priasains@gmail.com</option>
                                <option value="hosting.riviera@gmail.com">hosting.riviera@gmail.com</option>
                                <option value="serverintdev@gmail.com">serverintdev@gmail.com</option>
                                <option value="hostinger">hostinger</option>
                                <option value="exabytes">exabytes</option>
                            </select>
                        </div>
                        <div class="form-col">
                            <label><i class="fas fa-building"></i> Holding</label>
                            <select name="holding">
                                <option value="">Pilih Holding</option>
                                <?php foreach (array_keys($holding_colors) as $h): ?>
                                <option value="<?php echo $h; ?>"><?php echo $h; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-col">
                            <label><i class="fas fa-calendar-alt"></i> Tanggal Expired</label>
                            <input type="date" name="tanggal_expired">
                        </div>
                        <div class="form-col form-col-btn">
                            <button type="submit" class="btn btn-add"><i class="fas fa-plus"></i> Tambah</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Domain List -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-list"></i> Daftar Domain (<?php echo $total; ?>)</h3>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Cari domain...">
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="data-table" id="domainTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Domain</th>
                                <th>Registrar</th>
                                <th>Holding</th>
                                <th>Expired</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($domains)): ?>
                            <tr><td colspan="6" class="text-center">Belum ada data domain</td></tr>
                            <?php else: ?>
                            <?php $no = 1; foreach ($domains as $d): 
                                $h = strtoupper($d['holding']);
                                $color = $holding_colors[$h] ?? '#6c757d';
                                $days_left = '';
                                $days_class = '';
                                if ($d['tanggal_expired']) {
                                    $exp = new DateTime($d['tanggal_expired']);
                                    $now = new DateTime();
                                    $diff = $now->diff($exp);
                                    if ($exp > $now) {
                                        $days_left = $diff->days . ' hari lagi';
                                        if ($diff->days <= 12) {
                                            $days_class = 'expired-critical';
                                        } elseif ($diff->days <= 30) {
                                            $days_class = 'expired-warning';
                                        } else {
                                            $days_class = 'expired-ok';
                                        }
                                    } else {
                                        $days_left = 'EXPIRED';
                                        $days_class = 'expired-danger';
                                    }
                                }
                            ?>
                            <tr>
                                <td data-label="No"><?php echo $no++; ?></td>
                                <td data-label="Domain"><strong><?php echo htmlspecialchars($d['domain']); ?></strong></td>
                                <td data-label="Registrar"><?php echo htmlspecialchars($d['registrar']); ?></td>
                                <td data-label="Holding"><span class="badge" style="background:<?php echo $color; ?>"><?php echo $h ?: '-'; ?></span></td>
                                <td data-label="Expired">
                                    <div class="expired-info">
                                        <span class="expired-date"><?php echo $d['tanggal_expired'] ? date('d/m/Y', strtotime($d['tanggal_expired'])) : '-'; ?></span>
                                        <?php if ($days_left): ?><span class="expired-days <?php echo $days_class; ?>"><?php echo $days_left; ?></span><?php endif; ?>
                                    </div>
                                </td>
                                <td data-label="Aksi">
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Hapus domain <?php echo htmlspecialchars($d['domain']); ?>?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $d['id']; ?>">
                                        <button type="submit" class="btn-delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
.stat-card { background: var(--card-bg); border-radius: 15px; padding: 20px; display: flex; align-items: center; gap: 15px; border: 1px solid var(--border-color); box-shadow: 0 4px 15px var(--shadow-color); }
.stat-icon { width: 55px; height: 55px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; color: #fff; }
.stat-info h3 { font-size: 28px; font-weight: 700; margin-bottom: 5px; color: var(--text-primary); }
.stat-info p { font-size: 13px; color: var(--text-secondary); }

.card { background: var(--card-bg); border-radius: 15px; border: 1px solid var(--border-color); margin-bottom: 20px; box-shadow: 0 4px 15px var(--shadow-color); }
.card-header { padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
.card-header h3 { font-size: 16px; display: flex; align-items: center; gap: 10px; color: var(--text-primary); }
.card-body { padding: 20px; }

/* Form Styles */
.add-domain-card { background: var(--accent-light); border: 1px solid var(--accent); }
.add-domain-card .card-header { border-bottom: 1px solid var(--accent); }
.add-domain-card .card-header h3 i { color: var(--accent); }

.form-row { display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap; }
.form-col { flex: 1; min-width: 180px; }
.form-col-btn { flex: 0 0 auto; min-width: auto; }
.form-col label { display: block; margin-bottom: 8px; font-size: 12px; color: var(--text-secondary); font-weight: 500; }
.form-col label i { margin-right: 6px; color: var(--accent); }
.form-col input, .form-col select { 
    width: 100%; 
    padding: 12px 15px; 
    background: var(--input-bg); 
    border: 2px solid var(--border-color); 
    border-radius: 10px; 
    color: var(--text-primary); 
    font-size: 14px; 
    transition: all 0.3s;
}
.form-col input:focus, .form-col select:focus { 
    outline: none; 
    border-color: var(--accent); 
    box-shadow: 0 0 0 4px var(--accent-light);
}
.form-col input::placeholder { color: var(--text-muted); }
.form-col select option { background: var(--card-bg); color: var(--text-primary); }

.btn-add { 
    padding: 14px 30px; 
    background: linear-gradient(135deg, #43e97b, #38f9d7); 
    color: #1a1a2e; 
    border: none; 
    border-radius: 10px; 
    font-size: 15px; 
    font-weight: 700;
    cursor: pointer; 
    display: inline-flex; 
    align-items: center; 
    gap: 8px; 
    transition: all 0.3s;
    white-space: nowrap;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.btn-add:hover { 
    transform: translateY(-3px); 
    box-shadow: 0 10px 30px rgba(67,233,123,0.4); 
}
.btn-add:active {
    transform: translateY(-1px);
}

.search-box { display: flex; align-items: center; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 25px; padding: 8px 15px; }
.search-box input { background: none; border: none; color: var(--text-primary); outline: none; width: 200px; margin-left: 10px; }
.search-box i { color: var(--text-muted); }

.table-responsive { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; padding: 14px 15px; text-align: left; font-size: 12px; white-space: nowrap; font-weight: 600; }
.data-table td { padding: 14px 15px; border-bottom: 1px solid var(--border-color); font-size: 13px; vertical-align: middle; color: var(--text-primary); }
.data-table tr:hover { background: var(--hover-bg); }

/* Expired Info Styles */
.expired-info { display: flex; flex-direction: row; gap: 10px; align-items: center; flex-wrap: wrap; }
.expired-date { 
    font-size: 14px; 
    font-weight: 600; 
    color: var(--text-primary); 
    background: var(--bg-tertiary);
    padding: 6px 12px;
    border-radius: 6px;
    border: 1px solid var(--border-color);
}
.expired-days { 
    font-size: 12px; 
    font-weight: 700; 
    padding: 6px 14px; 
    border-radius: 6px; 
    display: inline-block;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    min-width: 110px;
    text-align: center;
}
.expired-ok { background: rgba(67,233,123,0.2); color: #10b981; }
.expired-warning { background: rgba(245,158,11,0.2); color: #f59e0b; }
.expired-critical { 
    background: linear-gradient(135deg, #fa709a, #f5576c); 
    color: #fff; 
    animation: pulse 1s infinite;
    box-shadow: 0 4px 15px rgba(250,112,154,0.4);
}
.expired-danger { 
    background: linear-gradient(135deg, #f5576c, #ff0844); 
    color: #fff; 
    animation: pulse 0.8s infinite; 
    box-shadow: 0 4px 15px rgba(245,87,108,0.5);
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

/* Delete Button */
.btn-delete {
    padding: 8px 12px;
    background: rgba(239,68,68,0.1);
    border: 1px solid rgba(239,68,68,0.3);
    border-radius: 8px;
    color: #ef4444;
    cursor: pointer;
    transition: all 0.3s;
}
.btn-delete:hover {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: #fff;
    transform: scale(1.1);
}

.badge { padding: 5px 14px; border-radius: 20px; font-size: 11px; font-weight: 600; color: #fff; }

.alert { padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
.alert-success { background: rgba(16,185,129,0.1); color: #10b981; border: 1px solid rgba(16,185,129,0.3); }
.alert-danger { background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); }

.text-center { text-align: center; color: var(--text-muted); }

@media (max-width: 1200px) { 
    .stats-row { grid-template-columns: repeat(2, 1fr); } 
    .form-row { flex-direction: column; }
    .form-col { width: 100%; min-width: 100%; }
    .form-col-btn { width: 100%; }
    .btn-add { width: 100%; justify-content: center; }
}
@media (max-width: 768px) { 
    .stats-row { grid-template-columns: repeat(2, 1fr); } 
    .card-header { flex-direction: column; gap: 15px; align-items: flex-start; }
    .card-header h3 { font-size: 14px; }
    .search-box { width: 100%; }
    .search-box input { width: 100%; }
    
    /* Table responsive - card style */
    .data-table thead { display: none; }
    .data-table tbody tr {
        display: block;
        background: var(--card-bg);
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 15px;
        border: 1px solid var(--border-color);
    }
    .data-table tbody td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid var(--border-color);
    }
    .data-table tbody td:last-child { border-bottom: none; }
    .data-table tbody td::before {
        content: attr(data-label);
        font-weight: 600;
        color: var(--text-secondary);
        font-size: 12px;
        text-transform: uppercase;
    }
    .expired-info { align-items: flex-end; }
    .stat-card { padding: 15px; }
    .stat-icon { width: 45px; height: 45px; font-size: 18px; }
    .stat-info h3 { font-size: 22px; }
    .stat-info p { font-size: 11px; }
}
@media (max-width: 480px) {
    .stats-row { grid-template-columns: 1fr; }
    .content-header h1 { font-size: 20px; }
    .content-header p { font-size: 12px; }
}
</style>

<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    const val = this.value.toLowerCase();
    document.querySelectorAll('#domainTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
    });
});
</script>

<?php include 'includes/footer.php'; ?>
