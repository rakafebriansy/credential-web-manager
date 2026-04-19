<?php
/**
 * Domain Expiry Notification Cron Job
 * Jalankan file ini setiap hari via cron job atau task scheduler
 * Contoh cron: 0 8 * * * php /path/to/domain_notif_cron.php
 */

require_once 'config/database.php';

// Konfigurasi WhatsApp API
// Gunakan salah satu provider: Fonnte, Wablas, atau WhatsApp Business API
$wa_config = [
    'provider' => 'fonnte', // fonnte, wablas, atau custom
    'api_key' => 'YOUR_API_KEY_HERE', // Ganti dengan API key Anda
    'group_id' => 'YOUR_GROUP_ID_HERE', // ID grup WhatsApp
    'sender' => '', // Nomor pengirim (jika diperlukan)
];

// Fungsi kirim WhatsApp via Fonnte
function sendWhatsAppFonnte($phone, $message, $api_key) {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'target' => $phone,
            'message' => $message,
        ],
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . $api_key
        ],
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, true);
}

// Fungsi kirim WhatsApp via Wablas
function sendWhatsAppWablas($phone, $message, $api_key) {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://pati.wablas.com/api/send-message',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'phone' => $phone,
            'message' => $message,
        ]),
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . $api_key,
            'Content-Type: application/x-www-form-urlencoded'
        ],
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, true);
}

// Fungsi utama kirim notifikasi
function sendNotification($target, $message, $config) {
    switch ($config['provider']) {
        case 'fonnte':
            return sendWhatsAppFonnte($target, $message, $config['api_key']);
        case 'wablas':
            return sendWhatsAppWablas($target, $message, $config['api_key']);
        default:
            return ['status' => false, 'message' => 'Provider tidak dikenal'];
    }
}

// Ambil domain yang expired atau akan expired dalam 7 hari
$today = date('Y-m-d');
$week_later = date('Y-m-d', strtotime('+7 days'));

$query = "SELECT * FROM domain_purchases 
          WHERE tanggal_expired <= '$week_later' 
          AND tanggal_expired >= DATE_SUB('$today', INTERVAL 30 DAY)
          ORDER BY tanggal_expired ASC";

$result = mysqli_query($conn, $query);

$expired_domains = [];
$expiring_soon = [];
$expiring_week = [];

while ($row = mysqli_fetch_assoc($result)) {
    $exp_date = $row['tanggal_expired'];
    $days_left = (strtotime($exp_date) - strtotime($today)) / 86400;
    $row['days_left'] = $days_left;
    
    if ($days_left < 0) {
        $expired_domains[] = $row;
    } elseif ($days_left <= 3) {
        $expiring_soon[] = $row;
    } else {
        $expiring_week[] = $row;
    }
}

// Buat pesan notifikasi
$message = "";

if (!empty($expired_domains)) {
    $message .= "🚨 *DOMAIN SUDAH EXPIRED!*\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━\n";
    foreach ($expired_domains as $d) {
        $days = abs(intval($d['days_left']));
        $message .= "❌ *{$d['domain']}*\n";
        $message .= "   Holding: {$d['holding']}\n";
        $message .= "   Expired: " . date('d/m/Y', strtotime($d['tanggal_expired'])) . " ({$days} hari lalu)\n";
        $message .= "   Registrar: {$d['registrar']}\n\n";
    }
}

if (!empty($expiring_soon)) {
    $message .= "⚠️ *DOMAIN AKAN EXPIRED (1-3 HARI)*\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━\n";
    foreach ($expiring_soon as $d) {
        $days = intval($d['days_left']);
        $emoji = $days == 0 ? "🔴" : "🟠";
        $message .= "{$emoji} *{$d['domain']}*\n";
        $message .= "   Holding: {$d['holding']}\n";
        $message .= "   Expired: " . date('d/m/Y', strtotime($d['tanggal_expired'])) . " ({$days} hari lagi)\n";
        $message .= "   Registrar: {$d['registrar']}\n\n";
    }
}

if (!empty($expiring_week)) {
    $message .= "📅 *DOMAIN AKAN EXPIRED (4-7 HARI)*\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━\n";
    foreach ($expiring_week as $d) {
        $days = intval($d['days_left']);
        $message .= "🟡 *{$d['domain']}*\n";
        $message .= "   Holding: {$d['holding']}\n";
        $message .= "   Expired: " . date('d/m/Y', strtotime($d['tanggal_expired'])) . " ({$days} hari lagi)\n";
        $message .= "   Registrar: {$d['registrar']}\n\n";
    }
}

// Kirim notifikasi jika ada domain yang perlu diperhatikan
if (!empty($message)) {
    $header = "📢 *NOTIFIKASI DOMAIN*\n";
    $header .= "📆 " . date('d F Y H:i') . "\n\n";
    $message = $header . $message;
    
    $message .= "━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "Total: " . count($expired_domains) . " expired, ";
    $message .= count($expiring_soon) . " segera expired, ";
    $message .= count($expiring_week) . " dalam 7 hari\n";
    $message .= "\n_Segera perpanjang domain sebelum expired!_";
    
    // Kirim ke grup WhatsApp
    if ($wa_config['api_key'] !== 'YOUR_API_KEY_HERE') {
        $response = sendNotification($wa_config['group_id'], $message, $wa_config);
        echo "Notifikasi terkirim: " . json_encode($response) . "\n";
    } else {
        echo "API Key belum dikonfigurasi!\n";
        echo "Preview pesan:\n";
        echo $message . "\n";
    }
    
    // Log ke file
    $log = date('Y-m-d H:i:s') . " - Notifikasi dikirim\n";
    file_put_contents('logs/domain_notif.log', $log, FILE_APPEND);
} else {
    echo "Tidak ada domain yang perlu dinotifikasi.\n";
}
?>
