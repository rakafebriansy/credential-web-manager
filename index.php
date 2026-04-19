<?php
require_once 'config/init.php';

if (!isLoggedIn()) {
    redirect('login');
}

// Redirect user_ojs to ojs_progress
if (isset($_SESSION['role']) && $_SESSION['role'] == 'user_ojs') {
    redirect('ojs_progress');
}

$page_title = 'Dashboard';
$user = getCurrentUser($conn);

// Get statistics
$total_websites = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM websites"))['total'];
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
    <?php include 'includes/topbar.php'; ?>

    <div class="content">
        <div class="page-header">
            <h1>Dashboard</h1>
            <p>Selamat datang kembali, <?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?>!</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-globe"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_websites; ?></h3>
                    <p>Total Website</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3>Active</h3>
                    <p>System Status</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3>1</h3>
                    <p>Total Users</p>
                </div>
            </div>
        </div>

        <!-- Recent Websites -->
        <div class="card">
            <div class="card-header">
                <h3>Website Terbaru</h3>
                <a href="websites" class="view-all">Lihat Semua</a>
            </div>
            <div class="card-body">
                <?php
                $recent_query = "SELECT * FROM websites ORDER BY created_at DESC LIMIT 5";
                $recent_result = mysqli_query($conn, $recent_query);

                if (mysqli_num_rows($recent_result) > 0):
                ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Holding</th>
                                    <th>Link URL</th>
                                    <th>Jenis Web</th>
                                    <th>PIC</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($recent_result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['holding']); ?></td>
                                        <td>
                                            <a href="<?php echo htmlspecialchars($row['link_url']); ?>" target="_blank" class="link-url">
                                                <?php echo htmlspecialchars($row['link_url']); ?>
                                            </a>
                                        </td>
                                        <td><span class="badge badge-info"><?php echo htmlspecialchars($row['jenis_web']); ?></span></td>
                                        <td><?php echo htmlspecialchars($row['pic']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-globe"></i>
                        <h3>Belum ada data website</h3>
                        <p>Klik <a href="websites">Kelola Website</a> untuk menambahkan data</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>