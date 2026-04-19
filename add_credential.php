<?php
require_once 'config/init.php';

if (!isLoggedIn()) {
    redirect('login');
}

// Check access
if (!isset($_SESSION['credentials_access']) || $_SESSION['credentials_access'] !== true) {
    redirect('website_credentials');
}

$page_title = 'Tambah Credentials';
$user = getCurrentUser($conn);

// Ensure table exists
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
        INDEX idx_website_id (website_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    mysqli_query($conn, $create_table);
}

// Get all websites for dropdown
$websites_query = "SELECT id, holding, link_url FROM websites ORDER BY holding ASC";
$websites_result = mysqli_query($conn, $websites_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $website_id = (int)$_POST['website_id'];
    $holding = strtoupper(trim($_POST['holding']));
    $username_admin = trim($_POST['username_admin']);
    $password_admin = trim($_POST['password_admin']);
    
    if (empty($website_id) || empty($holding) || empty($username_admin) || empty($password_admin)) {
        $error = 'Semua field wajib diisi!';
    } else {
        $query = "INSERT INTO website_credentials (website_id, holding, username_admin, password_admin) 
                  VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'isss', $website_id, $holding, $username_admin, $password_admin);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success'] = 'Credentials berhasil ditambahkan!';
                mysqli_stmt_close($stmt);
                redirect('website_credentials');
            } else {
                $error = 'Gagal menambahkan credentials: ' . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = 'Database error: ' . mysqli_error($conn);
        }
    }
}
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/topbar.php'; ?>
        
        <div class="content">
            <div class="page-header">
                <h1><i class="fas fa-plus"></i> Tambah Credentials</h1>
                <p>Tambah data kredensial admin website</p>
            </div>
            
            <div class="card" style="max-width: 800px;">
                <div class="card-header">
                    <h3>Form Tambah Credentials</h3>
                    <a href="website_credentials" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="website_id">Pilih Website <span class="required">*</span></label>
                            <select id="website_id" name="website_id" class="form-control" required onchange="updateHolding()">
                                <option value="">-- Pilih Website --</option>
                                <?php 
                                if ($websites_result && mysqli_num_rows($websites_result) > 0) {
                                    while ($web = mysqli_fetch_assoc($websites_result)): 
                                ?>
                                <option value="<?php echo $web['id']; ?>" 
                                        data-holding="<?php echo htmlspecialchars($web['holding']); ?>">
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
                            <input type="text" id="holding" name="holding" class="form-control" required readonly style="background-color: #f0f0f0;">
                        </div>
                        
                        <div class="form-group">
                            <label for="username_admin">Username Admin <span class="required">*</span></label>
                            <input type="text" id="username_admin" name="username_admin" class="form-control" required placeholder="Username admin website">
                        </div>
                        
                        <div class="form-group">
                            <label for="password_admin">Password Admin <span class="required">*</span></label>
                            <input type="text" id="password_admin" name="password_admin" class="form-control" required placeholder="Password admin website">
                        </div>
                        
                        <div style="margin-top: 20px; display: flex; gap: 10px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan
                            </button>
                            <a href="website_credentials" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<style>
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

.required {
    color: red;
}
</style>

<script>
function updateHolding() {
    const select = document.getElementById('website_id');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        const holding = option.getAttribute('data-holding');
        document.getElementById('holding').value = holding.toUpperCase();
    } else {
        document.getElementById('holding').value = '';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
