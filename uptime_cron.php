<?php
/**
 * Uptime Monitor Cron Job
 * 
 * Jalankan script ini via cron job setiap 5 menit:
 * */5 * * * * php /path/to/uptime_cron.php
 * 
 * Atau via wget/curl:
 * */5 * * * * wget -q -O /dev/null https://yourdomain.com/uptime_cron.php?key=YOUR_SECRET_KEY
 */

// Security key untuk akses cron (ganti ini!)
define('CRON_SECRET_KEY', 'change_this_secret_key_12345');

// Allow CLI execution tanpa key
$is_cli = (php_sapi_name() === 'cli');

// Check security key untuk web access
if (!$is_cli) {
    $provided_key = $_GET['key'] ?? '';
    if ($provided_key !== CRON_SECRET_KEY) {
        http_response_code(403);
        die('Access denied');
    }
}

require_once 'config/database.php';

// Get semua website
$websites = mysqli_query($conn, "SELECT * FROM websites");
$checked = 0;
$results = [];

while ($website = mysqli_fetch_assoc($websites)) {
    $result = checkWebsiteUptime($conn, $website);
    $results[] = [
        'name' => $website['link_url'],
        'holding' => $website['holding'],
        'status' => $result['status'],
        'response_time' => $result['response_time']
    ];
    $checked++;
}

// Output results
if ($is_cli) {
    echo "Uptime Monitor Cron - " . date('Y-m-d H:i:s') . "\n";
    echo "Checked: $checked websites\n";
    echo str_repeat("-", 60) . "\n";
    foreach ($results as $r) {
        $status_icon = $r['status'] === 'up' ? '✓' : '✗';
        echo sprintf("  %s [%s] %s - %s (%dms)\n", 
            $status_icon, 
            strtoupper($r['holding']), 
            $r['name'], 
            $r['status'], 
            $r['response_time']
        );
    }
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'checked' => $checked,
        'results' => $results
    ]);
}

/**
 * Check website uptime
 */
function checkWebsiteUptime($conn, $website) {
    $website_id = $website['id'];
    $url = $website['link_url'];
    
    // Perform HTTP check
    $start_time = microtime(true);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'UptimeMonitor/1.0'
    ]);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $end_time = microtime(true);
    $response_time = round(($end_time - $start_time) * 1000);
    
    // Determine status
    $success = ($status_code >= 200 && $status_code < 400) && empty($error);
    $status = $success ? 'up' : 'down';
    
    // Get or create uptime_status record
    $statusResult = mysqli_query($conn, "SELECT * FROM uptime_status WHERE website_id = $website_id");
    
    if ($existing = mysqli_fetch_assoc($statusResult)) {
        $total_checks = $existing['total_checks'] + 1;
        $successful_checks = $existing['successful_checks'] + ($success ? 1 : 0);
        $uptime_percentage = ($successful_checks / $total_checks) * 100;
        
        $sql = "UPDATE uptime_status SET 
                status = '$status',
                last_check = NOW(),
                last_response_time = $response_time,
                last_status_code = $status_code,
                total_checks = $total_checks,
                successful_checks = $successful_checks,
                uptime_percentage = $uptime_percentage
                WHERE website_id = $website_id";
    } else {
        $successful_checks = $success ? 1 : 0;
        $uptime_percentage = $success ? 100 : 0;
        
        $sql = "INSERT INTO uptime_status (website_id, status, last_check, last_response_time, last_status_code, total_checks, successful_checks, uptime_percentage) 
                VALUES ($website_id, '$status', NOW(), $response_time, $status_code, 1, $successful_checks, $uptime_percentage)";
    }
    mysqli_query($conn, $sql);
    
    // Log the check
    $error_escaped = mysqli_real_escape_string($conn, $error);
    $log_sql = "INSERT INTO uptime_logs (website_id, status, response_time, status_code, error_message) 
                VALUES ($website_id, '$status', $response_time, $status_code, '$error_escaped')";
    mysqli_query($conn, $log_sql);
    
    return [
        'status' => $status,
        'response_time' => $response_time,
        'status_code' => $status_code
    ];
}

// Cleanup old logs (simpan 30 hari terakhir)
$cleanup_sql = "DELETE FROM uptime_logs WHERE checked_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
mysqli_query($conn, $cleanup_sql);
?>
