<?php
require_once 'config/init.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$action = $_POST['action'] ?? '';

if ($action === 'update') {
    $website_id = intval($_POST['website_id'] ?? 0);
    $field = $_POST['field'] ?? '';
    $value = $_POST['value'] ?? '';
    
    // Validate field name
    $allowed_fields = ['ganti_wp_admin', 'plugin_wordfence', 'update_all_plugin', 'konfigurasi_rate_limit', 'pic'];
    
    if (!in_array($field, $allowed_fields)) {
        echo json_encode(['success' => false, 'error' => 'Invalid field']);
        exit();
    }
    
    if ($website_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid website ID']);
        exit();
    }
    
    // Sanitize value
    $value = mysqli_real_escape_string($conn, $value);
    
    // Check if record exists
    $check = mysqli_query($conn, "SELECT id FROM lp_security WHERE website_id = $website_id");
    
    if (mysqli_num_rows($check) > 0) {
        // Update existing record
        $sql = "UPDATE lp_security SET 
                $field = '$value', 
                last_update = NOW() 
                WHERE website_id = $website_id";
    } else {
        // Insert new record
        $sql = "INSERT INTO lp_security (website_id, $field, last_update) 
                VALUES ($website_id, '$value', NOW())";
    }
    
    if (mysqli_query($conn, $sql)) {
        // Get updated last_update time
        $result = mysqli_query($conn, "SELECT last_update FROM lp_security WHERE website_id = $website_id");
        $row = mysqli_fetch_assoc($result);
        $last_update = $row['last_update'] ? date('d/m/Y H:i', strtotime($row['last_update'])) : '-';
        
        echo json_encode([
            'success' => true, 
            'message' => 'Updated successfully',
            'last_update' => $last_update
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
    exit();
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
?>
