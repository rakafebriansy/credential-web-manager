<?php
require_once 'config/init.php';

if (!isLoggedIn()) {
    redirect('login');
}

// Get user role
$user_role = $_SESSION['role'] ?? 'admin';

// Restrict access for user_holding
if ($user_role == 'user_holding') {
    redirect('tiket_kunjungan');
}

$page_title = 'Website Credentials';
$current_page = 'website_credentials.php';
$user = getCurrentUser($conn);

// Check if table exists, if not create it
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'website_credentials'");
if (mysqli_num_rows($check_table) == 0) {
    $create_table = "CREATE TABLE IF NOT EXISTS website_credentials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        website_id INT NOT NULL,
        holding VARCHAR(50) NOT NULL,
        nama_website VARCHAR(255) NOT NULL,
        username_admin VARCHAR(100) NOT NULL,
        password_admin TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    mysqli_query($conn, $create_table);
}

// Check if user has entered password
$access_granted = false;
if (isset($_SESSION['credentials_access']) && $_SESSION['credentials_access'] === true) {
    $access_granted = true;
}

// Handle password verification
if (isset($_POST['verify_password'])) {
    $entered_password = $_POST['access_password'];
    if ($entered_password === 'itmpassworditm') {
        $_SESSION['credentials_access'] = true;
        $access_granted = true;
        header('Location: website_credentials');
        exit;
    } else {
        $error_message = 'Password salah!';
    }
}

if ($access_granted) {
    // Check user role
    $user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'admin';
    
    // Get all websites for dropdown - filter by OJS for user_ojs role
    if ($user_role == 'user_ojs') {
        $websites_query = "SELECT id, holding, link_url FROM websites WHERE LOWER(jenis_web) = 'ojs' ORDER BY holding ASC";
    } else {
        $websites_query = "SELECT id, holding, link_url FROM websites ORDER BY holding ASC";
    }
    $websites_result = mysqli_query($conn, $websites_query);
    
    // Get all credentials data - filter by OJS for user_ojs role
    if ($user_role == 'user_ojs') {
        $query = "SELECT wc.id, wc.website_id, wc.holding, wc.username_admin, wc.password_admin, w.link_url 
                  FROM website_credentials wc
                  LEFT JOIN websites w ON wc.website_id = w.id
                  WHERE LOWER(w.jenis_web) = 'ojs'
                  ORDER BY wc.holding ASC, wc.created_at DESC";
    } else {
        $query = "SELECT wc.id, wc.website_id, wc.holding, wc.username_admin, wc.password_admin, w.link_url 
                  FROM website_credentials wc
                  LEFT JOIN websites w ON wc.website_id = w.id
                  ORDER BY wc.holding ASC, wc.created_at DESC";
    }
    $result = mysqli_query($conn, $query);
    $total_credentials = mysqli_num_rows($result);
}
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/topbar.php'; ?>
        
        <div class="content">
            <div class="page-header">
                <h1><i class="fas fa-lock"></i> Website Credentials</h1>
                <p>Data kredensial admin website (Protected)</p>
            </div>
            
            <?php if (!$access_granted): ?>
            <!-- Password Protection Modal -->
            <div class="card" style="max-width: 500px; margin: 50px auto;">
                <div class="card-header">
                    <h3><i class="fas fa-shield-alt"></i> Akses Terbatas</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    <p style="text-align: center; margin-bottom: 20px;">
                        <i class="fas fa-lock" style="font-size: 48px; color: #dc3545;"></i>
                    </p>
                    <p style="text-align: center; margin-bottom: 30px;">
                        Halaman ini dilindungi. Masukkan password untuk mengakses.
                    </p>
                    <form method="POST">
                        <div class="form-group">
                            <label for="access_password">Password</label>
                            <input type="password" id="access_password" name="access_password" class="form-control" required autofocus>
                        </div>
                        <button type="submit" name="verify_password" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-unlock"></i> Buka Akses
                        </button>
                    </form>
                </div>
            </div>
            <?php else: ?>
            
            <div class="card">
                <div class="card-header">
                    <h3>Data Credentials (<?php echo $total_credentials; ?> data)</h3>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="text" id="searchInput" class="form-control" placeholder="Cari..." style="width: 250px;">
                        <a href="add_credential" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Tambah Credentials
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($total_credentials == 0): ?>
                        <div class="empty-state">
                            <i class="fas fa-key"></i>
                            <h3>Belum ada data credentials</h3>
                            <p>Klik tombol "Tambah Credentials" untuk menambahkan data baru</p>
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table" id="credentialsTable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Holding</th>
                                    <th>Link URL</th>
                                    <th>Username Admin</th>
                                    <th>Password Admin</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                if (isset($result)) {
                                    while ($row = mysqli_fetch_assoc($result)): 
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td>
                                        <?php
                                        $holding = strtoupper($row['holding']);
                                        $holding_colors = [
                                            'RIN' => '#FF6B6B', 'GP' => '#4ECDC4', 'PI' => '#45B7D1',
                                            'IJL' => '#FFA07A', 'AL-MAKKI' => '#98D8C8', 'RIVIERA' => '#F7DC6F',
                                            'TADCENT' => '#BB8FCE', 'EDC' => '#85C1E2', 'LPK' => '#F8B739',
                                            'LPK MKM' => '#F8B739', 'GB' => '#52B788', 'STAIKU' => '#E63946',
                                            'POLTEK' => '#457B9D', 'SCI' => '#E76F51'
                                        ];
                                        $color = isset($holding_colors[$holding]) ? $holding_colors[$holding] : '#6c757d';
                                        ?>
                                        <span class="badge-holding" style="background-color: <?php echo $color; ?>;">
                                            <?php echo $holding; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['link_url']): ?>
                                        <a href="<?php echo htmlspecialchars($row['link_url']); ?>" target="_blank" class="link-url">
                                            <?php echo htmlspecialchars($row['link_url']); ?>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($row['username_admin']); ?></code>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <input type="password" value="<?php echo htmlspecialchars($row['password_admin']); ?>" 
                                                   id="pass-<?php echo $row['id']; ?>" readonly 
                                                   style="border: none; background: transparent; width: 100px;">
                                            <button class="btn-icon" onclick="togglePassword(<?php echo $row['id']; ?>)" title="Show/Hide">
                                                <i class="fas fa-eye" id="icon-<?php echo $row['id']; ?>"></i>
                                            </button>
                                            <button class="btn-icon" onclick="copyPassword('<?php echo htmlspecialchars($row['password_admin']); ?>')" title="Copy">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <button class="btn-icon" onclick='editCredential(<?php echo json_encode($row); ?>)' title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-icon btn-danger" onclick="deleteCredential(<?php echo $row['id']; ?>)" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    
                    <?php endif; ?>
                </div>
            </div>
            
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($access_granted): ?>
    <!-- Modal Form -->
    <div id="credentialModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Tambah Credentials</h2>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="credentialForm" method="POST" action="website_credentials_action">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="credentialId">
                
                <div class="form-group">
                    <label for="website_id">Pilih Website <span class="required">*</span></label>
                    <select id="website_id" name="website_id" required onchange="updateWebsiteInfo()">
                        <option value="">-- Pilih Website --</option>
                        <?php 
                        if (isset($websites_result) && mysqli_num_rows($websites_result) > 0) {
                            mysqli_data_seek($websites_result, 0);
                            while ($web = mysqli_fetch_assoc($websites_result)): 
                        ?>
                        <option value="<?php echo $web['id']; ?>" 
                                data-holding="<?php echo htmlspecialchars($web['holding']); ?>"
                                data-url="<?php echo htmlspecialchars($web['link_url']); ?>">
                            <?php echo strtoupper($web['holding']); ?> - <?php echo htmlspecialchars($web['link_url']); ?>
                        </option>
                        <?php 
                            endwhile;
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="holding">Holding <span class="required">*</span></label>
                    <input type="text" id="holding" name="holding" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="nama_website">Nama Website <span class="required">*</span></label>
                    <input type="text" id="nama_website" name="nama_website" class="form-control" required placeholder="Contoh: Journal System">
                </div>
                
                <div class="form-group">
                    <label for="username_admin">Username Admin <span class="required">*</span></label>
                    <input type="text" id="username_admin" name="username_admin" class="form-control" required placeholder="Username admin website">
                </div>
                
                <div class="form-group">
                    <label for="password_admin">Password Admin <span class="required">*</span></label>
                    <input type="text" id="password_admin" name="password_admin" class="form-control" required placeholder="Password admin website">
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
    <?php endif; ?>

<style>
.badge-holding {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    color: white;
    font-weight: 600;
    font-size: 12px;
    text-align: center;
    min-width: 80px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.alert {
    padding: 12px 20px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

code {
    background-color: #f4f4f4;
    padding: 4px 8px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
}
</style>

<script>
function updateWebsiteInfo() {
    const select = document.getElementById('website_id');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        document.getElementById('holding').value = option.getAttribute('data-holding').toUpperCase();
    } else {
        document.getElementById('holding').value = '';
    }
}

function openModal() {
    const modal = document.getElementById('credentialModal');
    if (!modal) {
        console.error('Modal not found!');
        return;
    }
    modal.style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Tambah Credentials';
    document.getElementById('formAction').value = 'create';
    document.getElementById('credentialForm').reset();
    document.getElementById('credentialId').value = '';
    document.getElementById('holding').removeAttribute('readonly');
}

function closeModal() {
    document.getElementById('credentialModal').style.display = 'none';
}

function editCredential(credential) {
    const modal = document.getElementById('credentialModal');
    if (!modal) {
        console.error('Modal not found!');
        return;
    }
    modal.style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Edit Credentials';
    document.getElementById('formAction').value = 'update';
    
    document.getElementById('credentialId').value = credential.id;
    document.getElementById('website_id').value = credential.website_id;
    document.getElementById('holding').value = credential.holding.toUpperCase();
    document.getElementById('nama_website').value = credential.nama_website;
    document.getElementById('username_admin').value = credential.username_admin;
    document.getElementById('password_admin').value = credential.password_admin;
}

function deleteCredential(id) {
    if (confirm('Apakah Anda yakin ingin menghapus credentials ini?')) {
        window.location.href = 'website_credentials_action?action=delete&id=' + id;
    }
}

function togglePassword(id) {
    const input = document.getElementById('pass-' + id);
    const icon = document.getElementById('icon-' + id);
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function copyPassword(password) {
    navigator.clipboard.writeText(password).then(function() {
        alert('Password berhasil dicopy!');
    });
}

// Search functionality
(function() {
    const searchInput = document.getElementById('searchInput');
    const table = document.getElementById('credentialsTable');
    
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

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('credentialModal');
    if (modal && event.target == modal) {
        closeModal();
    }
});
</script>

<?php include 'includes/footer.php'; ?>
