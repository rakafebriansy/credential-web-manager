<?php
require_once 'config/init.php';

if (!isLoggedIn()) {
    redirect('login');
}

// Restrict access for user_ojs
if (isset($_SESSION['role']) && $_SESSION['role'] == 'user_ojs') {
    redirect('ojs_progress');
}

$page_title = 'Kelola Website';
$user = getCurrentUser($conn);

// Get all websites
$websites_query = "SELECT * FROM websites ORDER BY holding ASC, created_at DESC";
$websites_result = mysqli_query($conn, $websites_query);
$total_websites = mysqli_num_rows($websites_result);
$websites_result = mysqli_query($conn, $websites_query);
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/topbar.php'; ?>
        
        <div class="content">
            <div class="page-header">
                <h1>Kelola Website</h1>
                <p>Manajemen data website dan holding</p>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Daftar Website (<?php echo $total_websites; ?> data)</h3>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="text" id="searchInput" class="form-control" placeholder="Cari..." style="width: 250px;">
                        <button class="btn btn-primary btn-sm" onclick="openModal()">
                            <i class="fas fa-plus"></i> Tambah Website
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($total_websites == 0): ?>
                        <div class="empty-state">
                            <i class="fas fa-globe"></i>
                            <h3>Belum ada data website</h3>
                            <p>Klik tombol "Tambah Website" untuk menambahkan data baru</p>
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table" id="websitesTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Holding</th>
                                    <th>Link URL</th>
                                    <th>Jenis Web</th>
                                    <th>Letak Server</th>
                                    <th>PIC</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                while ($website = mysqli_fetch_assoc($websites_result)): 
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td>
                                        <?php
                                        $holding = strtoupper($website['holding']);
                                        $holding_colors = [
                                            'RIN' => '#FF6B6B',
                                            'GP' => '#4ECDC4',
                                            'PI' => '#45B7D1',
                                            'IJL' => '#FFA07A',
                                            'AL-MAKKI' => '#98D8C8',
                                            'RIVIERA' => '#F7DC6F',
                                            'TADCENT' => '#BB8FCE',
                                            'EDC' => '#85C1E2',
                                            'LPK' => '#F8B739',
                                            'LPK MKM' => '#F8B739',
                                            'GB' => '#52B788',
                                            'STAIKU' => '#E63946',
                                            'POLTEK' => '#457B9D',
                                            'SCI' => '#E76F51'
                                        ];
                                        $color = isset($holding_colors[$holding]) ? $holding_colors[$holding] : '#6c757d';
                                        ?>
                                        <span class="badge-holding" style="background-color: <?php echo $color; ?>;">
                                            <?php echo $holding; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($website['link_url']); ?>" target="_blank" class="link-url">
                                            <?php echo htmlspecialchars($website['link_url']); ?>
                                        </a>
                                    </td>
                                    <td><span class="badge badge-info"><?php echo htmlspecialchars($website['jenis_web']); ?></span></td>
                                    <td><?php echo htmlspecialchars($website['letak_server']); ?></td>
                                    <td><?php echo htmlspecialchars($website['pic']); ?></td>
                                    <td>
                                        <button class="btn-icon" onclick='editWebsite(<?php echo json_encode($website); ?>)' title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-icon btn-danger" onclick="deleteWebsite(<?php echo $website['id']; ?>)" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Form -->
    <div id="websiteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Tambah Website</h2>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="websiteForm" method="POST" action="websites_action">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="websiteId">
                
                <div class="form-group">
                    <label for="holding">Holding <span class="required">*</span></label>
                    <select id="holding" name="holding" required>
                        <option value="">-- Pilih Holding --</option>
                        <option value="rin">RIN</option>
                        <option value="gp">GP</option>
                 <option value="publikasiku">Publikasiku</option>

                        <option value="pi">PI</option>
                        <option value="ijl">IJL</option>
                        <option value="AL-MAKKI">AL-MAKKI</option>
                        <option value="RIVIERA">RIVIERA</option>
                        <option value="TADCENT">TADCENT</option>
                        <option value="EDC">EDC</option>
                        <option value="lpk">LPK MKM</option>
                        <option value="GB">GB</option>
                        <option value="STAIKU">STAIKU</option>
                        <option value="POLTEK">POLTEK</option>
                        <option value="SCI">SCI</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="link_url">Link URL <span class="required">*</span></label>
                    <input type="url" id="link_url" name="link_url" required placeholder="https://example.com">
                </div>
                
                <div class="form-group">
                    <label for="jenis_web">Jenis Website <span class="required">*</span></label>
                    <select id="jenis_web" name="jenis_web" required>
                        <option value="">-- Pilih Jenis Website --</option>
                        <option value="ojs">OJS</option>
                        <option value="Lp">LP</option>
                        <option value="website utama">Website Utama</option>
                        <option value="blog">Blog</option>
                        <option value="react js">React JS</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="letak_server">Letak Server <span class="required">*</span></label>
                    <input type="text" id="letak_server" name="letak_server" required placeholder="Masukkan letak server">
                </div>
                
                  <div class="form-group">
                    <label for="pic">PIC <span class="required">*</span></label>
                    <select id="pic" name="pic" required>
                        <option value="">-- Pilih PIC --</option>
                        <option value="abdul">Abdul fazri</option>
                        <option value="andika">Andika</option>
                        <option value="isma">Isma</option>
                        <option value="surya">Surya</option>
                        <option value="ridho">Ridho</option>
                    </select>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

<style>
.badge-holding {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 25px;
    color: white;
    font-weight: 600;
    font-size: 13px;
    text-align: center;
    min-width: 90px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.15);
    letter-spacing: 0.5px;
}
</style>

<script>
// Search functionality for websites table
(function() {
    const searchInput = document.getElementById('searchInput');
    const table = document.getElementById('websitesTable');
    
    if (searchInput && table) {
        searchInput.addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase().trim();
            const tbody = table.querySelector('tbody');
            
            if (tbody) {
                const rows = tbody.querySelectorAll('tr');
                
                rows.forEach(function(row) {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchValue)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
        });
    }
})();
</script>

<?php include 'includes/footer.php'; ?>
