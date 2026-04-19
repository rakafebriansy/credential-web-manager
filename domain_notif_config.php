<?php
require_once 'config/init.php';

if (!isLoggedIn()) {
    redirect('login');
}

if ($_SESSION['role'] !== 'admin') {
    redirect('index');
}

$page_title = 'Konfigurasi Notifikasi Domain';
$current_page = 'domain_notif_config.php';

// Create config table
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS notif_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE,
    config_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Handle save
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $provider = $_POST['provider'] ?? 'fonnte';
    $api_key = $_POST['api_key'] ?? '';
    $group_id = $_POST['group_id'] ?? '';
    $notify_days = $_POST['notify_days'] ?? '7';
    
    $configs = [
        'wa_provider' => $provider,
        'wa_api_key' => $api_key,
        'wa_group_id' => $group_id,
        'notify_days' => $notify_days
    ];
    
    foreach ($configs as $key => $value) {
        $stmt = $conn->prepare("INSERT INTO notif_config (config_key, config_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = ?");
        $stmt->bind_param("sss", $key, $value, $value);
        $stmt->execute();
    }
    $message = 'Konfigurasi berhasil disimpan!';
}

// Load config
$config = [];
$result = mysqli_query($conn, "SELECT * FROM notif_config");
while ($row = mysqli_fetch_assoc($result)) {
    $config[$row['config_key']] = $row['config_value'];
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <?php include 'includes/topbar.php'; ?>
    
    <div class="content" style="padding: 20px;">
        <div style="margin-bottom: 30px;">
            <h1 style="margin: 0; font-size: 28px; color: var(--text-primary);">
                <i class="fab fa-whatsapp" style="color: #25D366;"></i> Konfigurasi Notifikasi WhatsApp
            </h1>
            <p style="margin: 8px 0 0; color: var(--text-secondary);">Atur notifikasi domain expired ke grup WhatsApp</p>
        </div>
        
        <?php if ($message): ?>
        <div style="padding: 16px 20px; background: #dcfce7; color: #166534; border-radius: 12px; margin-bottom: 20px;">
            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
            <!-- Form Konfigurasi -->
            <div style="background: var(--card-bg); border-radius: 16px; padding: 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.08);">
                <h3 style="margin: 0 0 20px; font-size: 18px;"><i class="fas fa-cog"></i> Pengaturan API</h3>
                
                <form method="POST">
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500;">Provider WhatsApp API</label>
                        <select name="provider" style="width: 100%; padding: 12px; border: 2px solid var(--border-color); border-radius: 10px; background: var(--bg-secondary); color: var(--text-primary);">
                            <option value="fonnte" <?php echo ($config['wa_provider'] ?? '') == 'fonnte' ? 'selected' : ''; ?>>Fonnte</option>
                            <option value="wablas" <?php echo ($config['wa_provider'] ?? '') == 'wablas' ? 'selected' : ''; ?>>Wablas</option>
                        </select>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500;">API Key</label>
                        <input type="text" name="api_key" value="<?php echo htmlspecialchars($config['wa_api_key'] ?? ''); ?>" placeholder="Masukkan API Key" style="width: 100%; padding: 12px; border: 2px solid var(--border-color); border-radius: 10px; background: var(--bg-secondary); color: var(--text-primary);">
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500;">Group ID / Nomor Grup</label>
                        <input type="text" name="group_id" value="<?php echo htmlspecialchars($config['wa_group_id'] ?? ''); ?>" placeholder="ID Grup WhatsApp" style="width: 100%; padding: 12px; border: 2px solid var(--border-color); border-radius: 10px; background: var(--bg-secondary); color: var(--text-primary);">
                        <small style="color: var(--text-secondary);">Contoh: 6281234567890-1234567890@g.us</small>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500;">Notifikasi H- (hari sebelum expired)</label>
                        <input type="number" name="notify_days" value="<?php echo htmlspecialchars($config['notify_days'] ?? '7'); ?>" min="1" max="30" style="width: 100%; padding: 12px; border: 2px solid var(--border-color); border-radius: 10px; background: var(--bg-secondary); color: var(--text-primary);">
                    </div>
                    
                    <button type="submit" style="width: 100%; padding: 14px; border: none; border-radius: 10px; background: linear-gradient(135deg, #25D366, #128C7E); color: white; font-size: 16px; font-weight: 600; cursor: pointer;">
                        <i class="fas fa-save"></i> Simpan Konfigurasi
                    </button>
                </form>
            </div>
            
            <!-- Info & Test -->
            <div>
                <div style="background: var(--card-bg); border-radius: 16px; padding: 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-bottom: 20px;">
                    <h3 style="margin: 0 0 16px; font-size: 18px;"><i class="fas fa-info-circle"></i> Cara Penggunaan</h3>
                    <ol style="margin: 0; padding-left: 20px; line-height: 1.8; color: var(--text-secondary);">
                        <li>Daftar di <a href="https://fonnte.com" target="_blank">Fonnte.com</a> atau <a href="https://wablas.com" target="_blank">Wablas.com</a></li>
                        <li>Dapatkan API Key dari dashboard provider</li>
                        <li>Dapatkan Group ID dari grup WhatsApp</li>
                        <li>Masukkan konfigurasi di form ini</li>
                        <li>Jalankan cron job untuk notifikasi otomatis</li>
                    </ol>
                </div>
                
                <div style="background: var(--card-bg); border-radius: 16px; padding: 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-bottom: 20px;">
                    <h3 style="margin: 0 0 16px; font-size: 18px;"><i class="fas fa-terminal"></i> Cron Job</h3>
                    <p style="margin-bottom: 12px; color: var(--text-secondary);">Jalankan perintah ini setiap hari jam 8 pagi:</p>
                    <code style="display: block; padding: 12px; background: #1e293b; color: #10b981; border-radius: 8px; font-size: 13px; overflow-x: auto;">
                        0 8 * * * php <?php echo realpath('domain_notif_cron.php'); ?>
                    </code>
                </div>
                
                <div style="background: linear-gradient(135deg, #fef3c7, #fde68a); border-radius: 16px; padding: 24px;">
                    <h3 style="margin: 0 0 12px; font-size: 16px; color: #92400e;"><i class="fas fa-bell"></i> Test Notifikasi</h3>
                    <p style="margin-bottom: 16px; color: #78350f; font-size: 14px;">Kirim notifikasi test ke grup WhatsApp</p>
                    <button onclick="testNotification()" style="padding: 12px 24px; border: none; border-radius: 10px; background: #f59e0b; color: white; font-weight: 600; cursor: pointer;">
                        <i class="fab fa-whatsapp"></i> Kirim Test
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function testNotification() {
    if (confirm('Kirim notifikasi test ke grup WhatsApp?')) {
        fetch('domain_notif_test.php')
        .then(res => res.json())
        .then(data => {
            alert(data.message);
        })
        .catch(err => {
            alert('Error: ' + err.message);
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?>
