<?php
// Start output buffering to prevent any unwanted output
ob_start();

// Suppress error display and log them instead
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    require_once 'config/init.php';
    
    // Clean any previous output
    ob_clean();
    
    header('Content-Type: application/json');
    
    // Debug logging
    error_log("Cloudflare Action - POST data: " . print_r($_POST, true));

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'update') {
    $website_id = (int)$_POST['website_id'];
    $field = $_POST['field'];
    $value = $_POST['value'];
    
    // Validate field
    $allowed_fields = ['cdn_status'];
    $allowed_values = ['Cloudflare', 'Cloudflare 1', 'Cloudflare 2', 'Cloudflare 3', 'Cloudflare 4', 'Bunny', 'Niagahoster', 'Jagoan Hosting'];
    
    if (!in_array($field, $allowed_fields)) {
        echo json_encode(['success' => false, 'message' => 'Invalid field']);
        exit;
    }
    
    if (!in_array($value, $allowed_values)) {
        echo json_encode(['success' => false, 'message' => 'Invalid CDN value: ' . $value]);
        exit;
    }
    
    // Ensure table exists with VARCHAR (more flexible than ENUM)
    $create_table = "CREATE TABLE IF NOT EXISTS cloudflare_cdn (
        id INT AUTO_INCREMENT PRIMARY KEY,
        website_id INT NOT NULL,
        cdn_status VARCHAR(50) DEFAULT 'Cloudflare',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_website (website_id),
        FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    mysqli_query($conn, $create_table);
    
    // Try to alter existing table if it has ENUM type
    $alter_query = "ALTER TABLE cloudflare_cdn MODIFY COLUMN cdn_status VARCHAR(50) DEFAULT 'Cloudflare'";
    mysqli_query($conn, $alter_query); // Ignore error if already VARCHAR
    
    // Check if record exists
    $check_query = "SELECT id FROM cloudflare_cdn WHERE website_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    
    if (!$check_stmt) {
        echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . mysqli_error($conn)]);
        exit;
    }
    
    mysqli_stmt_bind_param($check_stmt, 'i', $website_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    $current_time = date('Y-m-d H:i:s');
    
    if (mysqli_num_rows($check_result) > 0) {
        // Update existing record
        $update_query = "UPDATE cloudflare_cdn SET $field = ? WHERE website_id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, 'si', $value, $website_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            echo json_encode([
                'success' => true, 
                'message' => 'Updated successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update: ' . mysqli_error($conn)]);
        }
    } else {
        // Insert new record
        $insert_query = "INSERT INTO cloudflare_cdn (website_id, $field) VALUES (?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, 'is', $website_id, $value);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            echo json_encode([
                'success' => true, 
                'message' => 'Created successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create: ' . mysqli_error($conn)]);
        }
    }
} elseif ($action === 'test') {
    echo json_encode(['success' => true, 'message' => 'Connection test successful', 'timestamp' => date('Y-m-d H:i:s')]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

} catch (Exception $e) {
    // Clean output buffer and send error as JSON
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
} finally {
    // End output buffering
    ob_end_flush();
}
?>