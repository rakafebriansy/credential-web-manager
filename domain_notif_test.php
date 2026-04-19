<?php
require_once 'config/init.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Load config
$config = [];
$result = mysqli_query($conn, "SELECT * FROM notif_config");
while ($row = mysqli_fetch_assoc($result)) {
    $config[$row['config_key']] = $row['config_value'];
}

$provider = $config['wa_provider'] ?? '';
$api_key = $config['wa_api_key'] ?? '';
$group_id = $config['wa_group_id'] ?? '';

if (empty($api_key) || empty($group_id)) {
    echo json_encode(['success' => false, 'message' => 'Konfigurasi belum lengkap! Silakan isi API Key dan Group ID.']);
    exit;
}

// Test message
$message = "🔔 *TEST NOTIFIKASI DOMAIN*\n\n";
$message .= "Ini adalah pesan test dari sistem notifikasi domain.\n";
$message .= "Jika Anda menerima pesan ini, berarti konfigurasi sudah benar.\n\n";
$message .= "📆 " . date('d F Y H:i:s') . "\n";
$message .= "✅ Status: Berhasil";

// Send based on provider
if ($provider == 'fonnte') {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => ['target' => $group_id, 'message' => $message],
        CURLOPT_HTTPHEADER => ['Authorization: ' . $api_key],
    ]);
    $response = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);
    
    if ($error) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $error]);
    } else {
        $res = json_decode($response, true);
        if (isset($res['status']) && $res['status'] == true) {
            echo json_encode(['success' => true, 'message' => 'Notifikasi test berhasil dikirim!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal: ' . ($res['reason'] ?? $response)]);
        }
    }
} elseif ($provider == 'wablas') {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://pati.wablas.com/api/send-message',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['phone' => $group_id, 'message' => $message]),
        CURLOPT_HTTPHEADER => ['Authorization: ' . $api_key],
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    
    $res = json_decode($response, true);
    if (isset($res['status']) && $res['status'] == true) {
        echo json_encode(['success' => true, 'message' => 'Notifikasi test berhasil dikirim!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal: ' . ($res['message'] ?? $response)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Provider tidak dikenal']);
}
?>
