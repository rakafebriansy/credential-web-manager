<?php
require_once 'config/init.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Create update log table if not exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS ojs_update_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    website_id INT NOT NULL,
    field_name VARCHAR(50) NOT NULL,
    old_value VARCHAR(100),
    new_value VARCHAR(100),
    updated_by INT,
    updated_by_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at)
)");

// Handle get_updates action
if (isset($_GET['action']) && $_GET['action'] == 'get_updates') {
    $last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
    
    $query = "SELECT l.*, w.link_url, w.holding 
              FROM ojs_update_logs l 
              LEFT JOIN websites w ON l.website_id = w.id 
              WHERE l.id > ? 
              ORDER BY l.id DESC LIMIT 10";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $last_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $updates = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $updates[] = $row;
    }
    
    echo json_encode(['success' => true, 'updates' => $updates]);
    exit;
}

// Handle get_recent action
if (isset($_GET['action']) && $_GET['action'] == 'get_recent') {
    $query = "SELECT l.*, w.link_url, w.holding 
              FROM ojs_update_logs l 
              LEFT JOIN websites w ON l.website_id = w.id 
              ORDER BY l.id DESC LIMIT 20";
    $result = mysqli_query($conn, $query);
    
    $updates = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $updates[] = $row;
    }
    
    echo json_encode(['success' => true, 'updates' => $updates]);
    exit;
}

$website_id = (int)$_POST['website_id'];
$field = $_POST['field'];
$value = $_POST['value'];

// Validate field name
$allowed_fields = ['hasil_check', 'versi_ojs', 'plugin_allow_upload', 'google_recaptcha', 'reset_password', 'login_admin'];
if (!in_array($field, $allowed_fields)) {
    echo json_encode(['success' => false, 'message' => 'Invalid field']);
    exit;
}

// Get old value
$old_value = '';
$check_query = "SELECT id, $field as old_val FROM ojs_progress WHERE website_id = ?";
$stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($stmt, 'i', $website_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$exists = mysqli_fetch_assoc($result);
if ($exists) {
    $old_value = $exists['old_val'];
}
mysqli_stmt_close($stmt);

if ($exists) {
    $query = "UPDATE ojs_progress SET $field = ? WHERE website_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'si', $value, $website_id);
} else {
    $query = "INSERT INTO ojs_progress (website_id, $field) VALUES (?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'is', $website_id, $value);
}

if (mysqli_stmt_execute($stmt)) {
    // Log the update
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    $user_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : (isset($_SESSION['username']) ? $_SESSION['username'] : 'Unknown');
    
    $log_query = "INSERT INTO ojs_update_logs (website_id, field_name, old_value, new_value, updated_by, updated_by_name) VALUES (?, ?, ?, ?, ?, ?)";
    $log_stmt = mysqli_prepare($conn, $log_query);
    mysqli_stmt_bind_param($log_stmt, 'isssis', $website_id, $field, $old_value, $value, $user_id, $user_name);
    mysqli_stmt_execute($log_stmt);
    mysqli_stmt_close($log_stmt);
    
    echo json_encode(['success' => true, 'message' => 'Data berhasil disimpan']);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
}

mysqli_stmt_close($stmt);
?>
