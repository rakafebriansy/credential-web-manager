<?php
require_once 'config/init.php';

// header('Content-Type: application/json'); // Moved down to allow debugging if needed
// error_reporting(0); // Disabled for debugging
set_time_limit(300); 

if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'get_health_list':
        echo json_encode(getHealthList($conn));
        break;
    case 'check_health':
        $website_id = intval($_REQUEST['website_id'] ?? 0);
        echo json_encode(checkWebsiteHealth($conn, $website_id));
        break;
    case 'check_all':
        echo json_encode(checkAllHealth($conn));
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Fetch all websites with their latest health status
 */
function getHealthList($conn) {
    $query = "SELECT w.id, w.holding, w.link_url, w.jenis_web, w.pic, 
                     h.status as health_status, h.condition_text, h.description, h.http_code, h.response_time, h.last_check 
              FROM websites w 
              LEFT JOIN website_health h ON w.id = h.website_id 
              ORDER BY w.holding ASC, w.created_at DESC";
    $result = mysqli_query($conn, $query);
    $list = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $list[] = $row;
    }
    return ['success' => true, 'data' => $list];
}

/**
 * Check health for a specific website
 */
function checkWebsiteHealth($conn, $website_id) {
    $query = "SELECT * FROM websites WHERE id = $website_id";
    $result = mysqli_query($conn, $query);
    $website = mysqli_fetch_assoc($result);
    
    if (!$website) {
        return ['success' => false, 'message' => 'Website not found'];
    }
    
    $url = $website['link_url'];
    
    // Basic URL cleanup
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = "http://" . $url;
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'Website Health Detector/1.0 (Mozilla/5.0)',
        CURLOPT_ENCODING => '', // Handles gzip/deflate
    ]);
    
    $start_time = microtime(true);
    $response_body = curl_exec($ch);
    $end_time = microtime(true);
    
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $response_time = round(($end_time - $start_time) * 1000);
    $curl_error = curl_error($ch);
    $curl_errno = curl_errno($ch);
    curl_close($ch);
    
    $status = 'sehat';
    $condition = 'Normal';
    $description = 'Website berjalan dengan normal.';
    
    if ($curl_errno) {
        $status = 'bahaya';
        $condition = 'Connection Error';
        $description = 'Gagal terhubung: ' . $curl_error;
    } elseif ($http_code == 0) {
        $status = 'bahaya';
        $condition = 'Network Error';
        $description = 'Server tidak merespon atau domain tidak terdaftar.';
    } elseif ($http_code >= 500) {
        $status = 'bahaya';
        $condition = 'Server Error (' . $http_code . ')';
        $description = 'Server mengalami masalah internal.';
    } elseif ($http_code >= 400) {
        $status = 'bahaya';
        $condition = 'Client Error (' . $http_code . ')';
        $description = 'Halaman tidak ditemukan atau akses ditolak.';
    } elseif ($http_code >= 300) {
        $status = 'peringatan';
        $condition = 'Redirect (' . $http_code . ')';
        $description = 'Website dialihkan ke halaman lain.';
    } elseif ($response_time > 3000) {
        $status = 'peringatan';
        $condition = 'Slow Response';
        $description = 'Waktu respon website lambat (' . $response_time . 'ms).';
    }
    
    // Scan for error patterns in body if response was received
    if ($response_body) {
        $error_patterns = [
            'Database connection failed' => 'Database Error',
            'Connect Error' => 'Database Error',
            'SQL STATE' => 'Database Error',
            'Fatal error' => 'PHP Fatal Error',
            'Parse error' => 'PHP Parse Error',
            'Service Unavailable' => 'Service Unavailable',
            'Error established a database connection' => 'Database Error',
            'Account Suspended' => 'Hosting Suspended',
            'This Account has been suspended.' => 'Hosting Suspended',
            'Contact your hosting provider for more information' => 'Hosting Suspended'
        ];
        
        foreach ($error_patterns as $pattern => $cond) {
            if (stripos($response_body, $pattern) !== false) {
                $status = 'bahaya';
                $condition = $cond;
                $description = 'Terdeteksi pesan kesalahan sistem pada halaman.';
                break;
            }
        }
    }
    
    // Save/Update database
    $save_query = "INSERT INTO website_health (website_id, status, condition_text, description, http_code, response_time, last_check) 
                   VALUES (?, ?, ?, ?, ?, ?, NOW()) 
                   ON DUPLICATE KEY UPDATE 
                   status = VALUES(status), 
                   condition_text = VALUES(condition_text), 
                   description = VALUES(description), 
                   http_code = VALUES(http_code), 
                   response_time = VALUES(response_time), 
                   last_check = NOW()";
    
    $stmt = mysqli_prepare($conn, $save_query);
    if (!$stmt) {
        return ['success' => false, 'message' => 'Query preparation failed: ' . mysqli_error($conn)];
    }
    
    mysqli_stmt_bind_param($stmt, 'isssii', $website_id, $status, $condition, $description, $http_code, $response_time);
    if (!mysqli_stmt_execute($stmt)) {
        return ['success' => false, 'message' => 'Database execution failed: ' . mysqli_stmt_error($stmt)];
    }
    mysqli_stmt_close($stmt);
    
    return [
        'success' => true,
        'website_id' => $website_id,
        'health_status' => $status,
        'condition_text' => $condition,
        'description' => $description,
        'http_code' => $http_code,
        'response_time' => $response_time,
        'last_check' => date('Y-m-d H:i:s')
    ];
}

/**
 * Check health for all websites
 */
function checkAllHealth($conn) {
    $query = "SELECT id FROM websites";
    $result = mysqli_query($conn, $query);
    $count = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        checkWebsiteHealth($conn, $row['id']);
        $count++;
    }
    return ['success' => true, 'message' => "Selesai mengecek $count website."];
}
?>
