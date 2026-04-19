<?php
require_once 'config/init.php';

if (!isLoggedIn()) {
    redirect('login');
}

$page_title = 'Tiket Kunjungan';
$current_page = 'tiket_kunjungan.php';

// Get user role and holding
$user_role = $_SESSION['role'] ?? 'admin';
$user_holding = $_SESSION['holding'] ?? null;

// Restrict user_ojs (they can't access this page)
if ($user_role == 'user_ojs') {
    redirect('ojs_progress');
}

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        // If user_holding, force their holding
        if ($user_role == 'user_holding' && $user_holding) {
            $holding = $user_holding;
        } else {
            $holding = trim($_POST['holding'] ?? '');
        }
        $tanggal_kunjungan = $_POST['tanggal_kunjungan'] ?? '';
        $waktu_mulai = $_POST['waktu_mulai'] ?? '';
        $waktu_selesai = $_POST['waktu_selesai'] ?? '';
        $tujuan = trim($_POST['tujuan'] ?? '');
        $pic = trim($_POST['pic'] ?? '');
        $catatan = trim($_POST['catatan'] ?? '');
        $status = 'scheduled';
        
        if (empty($holding) || empty($tanggal_kunjungan)) {
            $error_message = 'Holding dan tanggal kunjungan wajib diisi!';
        } else {
            $stmt = $conn->prepare("INSERT INTO tiket_kunjungan (holding, tanggal_kunjungan, waktu_mulai, waktu_selesai, tujuan, pic, catatan, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $user_id = $_SESSION['user_id'] ?? 0;
            $stmt->bind_param("ssssssssi", $holding, $tanggal_kunjungan, $waktu_mulai, $waktu_selesai, $tujuan, $pic, $catatan, $status, $user_id);
            
            if ($stmt->execute()) {
                $success_message = 'Tiket kunjungan berhasil dibuat!';
            } else {
                $error_message = 'Gagal membuat tiket: ' . $conn->error;
            }
        }
    } elseif ($action === 'update_status') {
        $id = intval($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $hasil_kunjungan = trim($_POST['hasil_kunjungan'] ?? '');
        
        if ($id > 0 && !empty($status)) {
            $stmt = $conn->prepare("UPDATE tiket_kunjungan SET status = ?, hasil_kunjungan = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssi", $status, $hasil_kunjungan, $id);
            
            if ($stmt->execute()) {
                $success_message = 'Status tiket berhasil diupdate!';
            } else {
                $error_message = 'Gagal update status!';
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM tiket_kunjungan WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $success_message = 'Tiket berhasil dihapus!';
            }
        }
    }
}

// Get all tickets
$filter_holding = $_GET['holding'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_bulan = $_GET['bulan'] ?? date('Y-m');

$where = "WHERE 1=1";

// If user is user_holding, only show their holding's tickets
if ($user_role == 'user_holding' && $user_holding) {
    $where .= " AND holding = '" . mysqli_real_escape_string($conn, $user_holding) . "'";
} else {
    // Admin can filter by holding
    if (!empty($filter_holding)) {
        $where .= " AND holding = '" . mysqli_real_escape_string($conn, $filter_holding) . "'";
    }
}

if (!empty($filter_status)) {
    $where .= " AND status = '" . mysqli_real_escape_string($conn, $filter_status) . "'";
}
if (!empty($filter_bulan)) {
    $where .= " AND DATE_FORMAT(tanggal_kunjungan, '%Y-%m') = '" . mysqli_real_escape_string($conn, $filter_bulan) . "'";
}

$tickets = [];
$result = mysqli_query($conn, "SELECT * FROM tiket_kunjungan $where ORDER BY created_at DESC");
while ($row = mysqli_fetch_assoc($result)) {
    $tickets[] = $row;
}

// Get statistics
$stats_where = "WHERE DATE_FORMAT(tanggal_kunjungan, '%Y-%m') = '$filter_bulan'";
if ($user_role == 'user_holding' && $user_holding) {
    $stats_where .= " AND holding = '" . mysqli_real_escape_string($conn, $user_holding) . "'";
}

$stats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM tiket_kunjungan $stats_where"));

// Get holding list
$holding_colors = [
    'RIN'=>'#FF6B6B','GP'=>'#4ECDC4','PI'=>'#45B7D1','IJL'=>'#FFA07A',
    'AL-MAKKI'=>'#98D8C8','RIVIERA'=>'#F7DC6F','TADCENT'=>'#BB8FCE',
    'EDC'=>'#85C1E2','LPK'=>'#F8B739','GB'=>'#52B788','STAIKU'=>'#E63946',
    'POLTEK'=>'#457B9D','SCI'=>'#E76F51','PUBLIKASIKU'=>'#9B59B6',
    'EBISKRAF'=>'#3498DB','MSDM'=>'#1ABC9C','LPK-STC'=>'#E67E22',
    'FOUNDATION'=>'#9B59B6','IB-FOUNDATION'=>'#34495E','SBS'=>'#16A085'
];

$status_labels = [
    'scheduled' => ['label' => 'Terjadwal', 'color' => '#3b82f6', 'icon' => 'fa-calendar-check'],
    'in_progress' => ['label' => 'Sedang Berlangsung', 'color' => '#f59e0b', 'icon' => 'fa-spinner'],
    'completed' => ['label' => 'Selesai', 'color' => '#10b981', 'icon' => 'fa-check-circle'],
    'cancelled' => ['label' => 'Dibatalkan', 'color' => '#ef4444', 'icon' => 'fa-times-circle']
];

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<style>
/* Tiket Kunjungan Styles */
.ticket-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
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

.stat-icon.blue { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
.stat-icon.yellow { background: linear-gradient(135deg, #f59e0b, #d97706); }
.stat-icon.green { background: linear-gradient(135deg, #10b981, #059669); }
.stat-icon.red { background: linear-gradient(135deg, #ef4444, #dc2626); }

.stat-content h3 {
    font-size: 28px;
    font-weight: 700;
    margin: 0;
    color: var(--text-primary);
}

.stat-content p {
    margin: 4px 0 0;
    color: var(--text-secondary);
    font-size: 14px;
}

/* Filter Bar */
.filter-bar {
    display: flex;
    gap: 15px;
    margin-bottom: 25px;
    flex-wrap: wrap;
    align-items: center;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-group label {
    font-size: 13px;
    color: var(--text-secondary);
    white-space: nowrap;
}

.filter-group select, .filter-group input {
    padding: 10px 14px;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    background: var(--card-bg);
    color: var(--text-primary);
    font-size: 14px;
    min-width: 150px;
}

/* Ticket Cards */
.tickets-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.ticket-card {
    background: var(--card-bg);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.ticket-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}

.ticket-header {
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--border-color);
}

.ticket-holding {
    display: flex;
    align-items: center;
    gap: 10px;
}

.holding-badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    color: white;
}

.ticket-id {
    font-size: 12px;
    color: var(--text-secondary);
}

.ticket-status {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
}

.ticket-body {
    padding: 20px;
}

.ticket-date {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px dashed var(--border-color);
}

.date-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border-radius: 12px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
}

.date-icon .day {
    font-size: 20px;
    font-weight: 700;
    line-height: 1;
}

.date-icon .month {
    font-size: 10px;
    text-transform: uppercase;
}

.date-info h4 {
    margin: 0;
    font-size: 16px;
    color: var(--text-primary);
}

.date-info p {
    margin: 4px 0 0;
    font-size: 13px;
    color: var(--text-secondary);
}

.ticket-details {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.detail-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 14px;
}

.detail-row i {
    width: 20px;
    color: var(--text-secondary);
    margin-top: 2px;
}

.detail-row span {
    color: var(--text-primary);
    flex: 1;
}

.ticket-footer {
    padding: 16px 20px;
    background: var(--bg-secondary);
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.btn-sm {
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 13px;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}

.btn-primary {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white;
}

.btn-success {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.btn-danger {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.btn-secondary {
    background: var(--bg-secondary);
    color: var(--text-primary);
    border: 1px solid var(--border-color);
}

.btn-sm:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal.show {
    display: flex;
}

.modal-content {
    background: var(--card-bg);
    border-radius: 20px;
    width: 100%;
    max-width: 550px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 25px 50px rgba(0,0,0,0.25);
}

.modal-header {
    padding: 24px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    font-size: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--text-primary);
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--text-secondary);
    padding: 5px;
}

.modal-body {
    padding: 24px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--text-primary);
    font-size: 14px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid var(--border-color);
    border-radius: 12px;
    background: var(--bg-secondary);
    color: var(--text-primary);
    font-size: 14px;
    transition: all 0.3s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.modal-footer {
    padding: 20px 24px;
    border-top: 1px solid var(--border-color);
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

/* Alert */
.alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.alert-success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #86efac;
}

.alert-danger {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-secondary);
}

.empty-state i {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.3;
}

.empty-state h3 {
    margin: 0 0 10px;
    color: var(--text-primary);
}

/* Responsive */
@media (max-width: 768px) {
    .tickets-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-bar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        flex-direction: column;
        align-items: stretch;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .ticket-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<div class="main-content">
    <?php include 'includes/topbar.php'; ?>
    
    <div class="content">
        <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div>
                <h1 style="margin: 0; font-size: 28px; color: var(--text-primary);">
                    <i class="fas fa-ticket-alt" style="color: #6366f1;"></i> Tiket Kunjungan
                    <?php if ($user_role == 'user_holding' && $user_holding): ?>
                    <span style="font-size: 16px; font-weight: 500; color: var(--text-secondary);">(<?php echo $user_holding; ?>)</span>
                    <?php endif; ?>
                </h1>
                <p style="margin: 8px 0 0; color: var(--text-secondary);">Kelola jadwal kunjungan per holding</p>
            </div>
            <button class="btn-sm btn-primary" onclick="openAddModal()" style="padding: 12px 24px; font-size: 14px;">
                <i class="fas fa-plus"></i> Buat Tiket Baru
            </button>
        </div>
        
        <?php if ($user_role == 'user_holding'): ?>
        <!-- Info Box for Holding Users -->
        <div class="info-box" style="background: linear-gradient(135deg, #fef3c7, #fde68a); border: 1px solid #f59e0b; border-radius: 16px; padding: 20px 24px; margin-bottom: 25px; display: flex; align-items: flex-start; gap: 16px;">
            <div style="background: #f59e0b; color: white; width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i class="fas fa-exclamation-triangle" style="font-size: 20px;"></i>
            </div>
            <div>
                <h4 style="margin: 0 0 8px; color: #92400e; font-size: 16px;">
                    <i class="fas fa-info-circle"></i> Informasi Penting
                </h4>
                <p style="margin: 0; color: #78350f; font-size: 14px; line-height: 1.6;">
                    <strong>Silakan request H-1 sebelum jadwal kunjungan!</strong><br>
                    Pastikan semua content dan dokumen yang diperlukan sudah siap sebelum kunjungan dilaksanakan.
                </p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="ticket-stats">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-content">
                    <h3><?php echo $stats['scheduled'] ?? 0; ?></h3>
                    <p>Terjadwal</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow"><i class="fas fa-spinner"></i></div>
                <div class="stat-content">
                    <h3><?php echo $stats['in_progress'] ?? 0; ?></h3>
                    <p>Sedang Berlangsung</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
                <div class="stat-content">
                    <h3><?php echo $stats['completed'] ?? 0; ?></h3>
                    <p>Selesai</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red"><i class="fas fa-times-circle"></i></div>
                <div class="stat-content">
                    <h3><?php echo $stats['cancelled'] ?? 0; ?></h3>
                    <p>Dibatalkan</p>
                </div>
            </div>
        </div>
        
        <!-- Filter Bar -->
        <form method="GET" class="filter-bar">
            <?php if ($user_role == 'admin'): ?>
            <div class="filter-group">
                <label><i class="fas fa-building"></i> Holding:</label>
                <select name="holding" onchange="this.form.submit()">
                    <option value="">Semua Holding</option>
                    <?php foreach (array_keys($holding_colors) as $h): ?>
                    <option value="<?php echo $h; ?>" <?php echo $filter_holding == $h ? 'selected' : ''; ?>><?php echo $h; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php else: ?>
            <div class="filter-group">
                <label><i class="fas fa-building"></i> Holding:</label>
                <input type="text" value="<?php echo $user_holding; ?>" readonly style="background: var(--bg-secondary); font-weight: 600; min-width: 150px;">
            </div>
            <?php endif; ?>
            <div class="filter-group">
                <label><i class="fas fa-filter"></i> Status:</label>
                <select name="status" onchange="this.form.submit()">
                    <option value="">Semua Status</option>
                    <?php foreach ($status_labels as $key => $val): ?>
                    <option value="<?php echo $key; ?>" <?php echo $filter_status == $key ? 'selected' : ''; ?>><?php echo $val['label']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-calendar"></i> Bulan:</label>
                <input type="month" name="bulan" value="<?php echo $filter_bulan; ?>" onchange="this.form.submit()">
            </div>
        </form>
        
        <!-- Tickets Grid -->
        <?php if (empty($tickets)): ?>
        <div class="empty-state">
            <i class="fas fa-ticket-alt"></i>
            <h3>Belum ada tiket kunjungan</h3>
            <p>Klik tombol "Buat Tiket Baru" untuk membuat jadwal kunjungan</p>
        </div>
        <?php else: ?>
        <div class="tickets-grid">
            <?php foreach ($tickets as $ticket): 
                $h = strtoupper($ticket['holding']);
                $color = $holding_colors[$h] ?? '#6c757d';
                $status = $status_labels[$ticket['status']] ?? $status_labels['scheduled'];
                $date = new DateTime($ticket['tanggal_kunjungan']);
                $months_id = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
            ?>
            <div class="ticket-card">
                <div class="ticket-header">
                    <div class="ticket-holding">
                        <span class="holding-badge" style="background: <?php echo $color; ?>;"><?php echo $h; ?></span>
                        <span class="ticket-id">#TK<?php echo str_pad($ticket['id'], 4, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <span class="ticket-status" style="background: <?php echo $status['color']; ?>20; color: <?php echo $status['color']; ?>;">
                        <i class="fas <?php echo $status['icon']; ?>"></i>
                        <?php echo $status['label']; ?>
                    </span>
                </div>
                <div class="ticket-body">
                    <div class="ticket-date">
                        <div class="date-icon">
                            <span class="day"><?php echo $date->format('d'); ?></span>
                            <span class="month"><?php echo $months_id[$date->format('n')-1]; ?></span>
                        </div>
                        <div class="date-info">
                            <h4><?php echo $date->format('l, d F Y'); ?></h4>
                            <p><i class="fas fa-clock"></i> <?php echo $ticket['waktu_mulai'] ? substr($ticket['waktu_mulai'],0,5) : '-'; ?> - <?php echo $ticket['waktu_selesai'] ? substr($ticket['waktu_selesai'],0,5) : '-'; ?> WIB</p>
                        </div>
                    </div>
                    <div class="ticket-details">
                        <div class="detail-row">
                            <i class="fas fa-bullseye"></i>
                            <span><?php echo htmlspecialchars($ticket['tujuan'] ?: 'Tidak ada tujuan'); ?></span>
                        </div>
                        <div class="detail-row">
                            <i class="fas fa-user"></i>
                            <span>PIC: <?php echo htmlspecialchars($ticket['pic'] ?: '-'); ?></span>
                        </div>
                        <?php if ($ticket['catatan']): ?>
                        <div class="detail-row">
                            <i class="fas fa-sticky-note"></i>
                            <span><?php echo htmlspecialchars($ticket['catatan']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($ticket['hasil_kunjungan']): ?>
                        <div class="detail-row" style="background: #dcfce7; padding: 10px; border-radius: 8px; margin-top: 5px;">
                            <i class="fas fa-clipboard-check" style="color: #166534;"></i>
                            <span style="color: #166534;"><strong>Hasil:</strong> <?php echo htmlspecialchars($ticket['hasil_kunjungan']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="ticket-footer">
                    <?php if ($ticket['status'] == 'scheduled'): ?>
                    <button class="btn-sm btn-success" onclick="updateStatus(<?php echo $ticket['id']; ?>, 'in_progress')">
                        <i class="fas fa-play"></i> Mulai
                    </button>
                    <?php elseif ($ticket['status'] == 'in_progress'): ?>
                    <button class="btn-sm btn-success" onclick="openCompleteModal(<?php echo $ticket['id']; ?>)">
                        <i class="fas fa-check"></i> Selesai
                    </button>
                    <?php endif; ?>
                    <?php if ($ticket['status'] != 'completed' && $ticket['status'] != 'cancelled'): ?>
                    <button class="btn-sm btn-danger" onclick="updateStatus(<?php echo $ticket['id']; ?>, 'cancelled')">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <?php endif; ?>
                    <button class="btn-sm btn-secondary" onclick="deleteTicket(<?php echo $ticket['id']; ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Ticket Modal -->
<div class="modal" id="addModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-plus-circle"></i> Buat Tiket Kunjungan</h2>
            <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-group">
                    <label><i class="fas fa-building"></i> Holding *</label>
                    <?php if ($user_role == 'user_holding' && $user_holding): ?>
                    <input type="text" value="<?php echo $user_holding; ?>" readonly style="background: var(--bg-secondary); font-weight: 600;">
                    <input type="hidden" name="holding" value="<?php echo $user_holding; ?>">
                    <?php else: ?>
                    <select name="holding" required>
                        <option value="">Pilih Holding</option>
                        <?php foreach (array_keys($holding_colors) as $h): ?>
                        <option value="<?php echo $h; ?>"><?php echo $h; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-calendar"></i> Tanggal Kunjungan *</label>
                    <input type="date" name="tanggal_kunjungan" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Waktu Mulai</label>
                        <input type="time" name="waktu_mulai" value="09:00">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Waktu Selesai</label>
                        <input type="time" name="waktu_selesai" value="17:00">
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-bullseye"></i> Tujuan Kunjungan</label>
                    <input type="text" name="tujuan" placeholder="Contoh: Maintenance Server, Training, dll">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-user"></i> PIC (Person In Charge)</label>
                    <input type="text" name="pic" placeholder="Nama penanggung jawab">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-sticky-note"></i> Catatan</label>
                    <textarea name="catatan" rows="3" placeholder="Catatan tambahan..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-sm btn-secondary" onclick="closeModal('addModal')">Batal</button>
                <button type="submit" class="btn-sm btn-primary"><i class="fas fa-save"></i> Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Complete Modal -->
<div class="modal" id="completeModal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-clipboard-check"></i> Selesaikan Kunjungan</h2>
            <button class="modal-close" onclick="closeModal('completeModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="id" id="complete_ticket_id">
            <input type="hidden" name="status" value="completed">
            <div class="modal-body">
                <div class="form-group">
                    <label><i class="fas fa-clipboard-list"></i> Hasil Kunjungan *</label>
                    <textarea name="hasil_kunjungan" rows="4" placeholder="Tuliskan hasil/laporan kunjungan..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-sm btn-secondary" onclick="closeModal('completeModal')">Batal</button>
                <button type="submit" class="btn-sm btn-success"><i class="fas fa-check"></i> Selesai</button>
            </div>
        </form>
    </div>
</div>

<!-- Hidden forms for status update and delete -->
<form id="statusForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="update_status">
    <input type="hidden" name="id" id="status_ticket_id">
    <input type="hidden" name="status" id="status_value">
    <input type="hidden" name="hasil_kunjungan" value="">
</form>

<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="delete_ticket_id">
</form>

<script>
function openAddModal() {
    document.getElementById('addModal').classList.add('show');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}

function openCompleteModal(id) {
    document.getElementById('complete_ticket_id').value = id;
    document.getElementById('completeModal').classList.add('show');
}

function updateStatus(id, status) {
    if (status === 'cancelled' && !confirm('Yakin ingin membatalkan tiket ini?')) return;
    
    document.getElementById('status_ticket_id').value = id;
    document.getElementById('status_value').value = status;
    document.getElementById('statusForm').submit();
}

function deleteTicket(id) {
    if (!confirm('Yakin ingin menghapus tiket ini?')) return;
    
    document.getElementById('delete_ticket_id').value = id;
    document.getElementById('deleteForm').submit();
}

// Close modal on outside click
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
