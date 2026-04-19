<?php
require_once 'config/init.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Error handling
error_reporting(0);
set_time_limit(120);

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized', 'status' => 'error']);
    exit();
}

$url = $_POST['url'] ?? $_GET['url'] ?? '';
$id = $_POST['id'] ?? $_GET['id'] ?? 0;

if (empty($url)) {
    echo json_encode(['error' => 'URL is required', 'status' => 'error']);
    exit();
}

// Validate URL
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(['error' => 'Invalid URL format', 'status' => 'error', 'url' => $url]);
    exit();
}

// Comprehensive gambling/malware keywords - UPDATED for better detection
$malware_patterns = [
    // Slot - Critical (most common)
    ['pattern' => 'slot\s*gacor', 'keyword' => 'Slot Gacor', 'category' => 'Slot', 'severity' => 'critical'],
    ['pattern' => 'slot\s*online', 'keyword' => 'Slot Online', 'category' => 'Slot', 'severity' => 'critical'],
    ['pattern' => 'slot88', 'keyword' => 'Slot88', 'category' => 'Slot', 'severity' => 'critical'],
    ['pattern' => 'slot777', 'keyword' => 'Slot777', 'category' => 'Slot', 'severity' => 'critical'],
    ['pattern' => 'slot\s*dana', 'keyword' => 'Slot Dana', 'category' => 'Slot', 'severity' => 'critical'],
    ['pattern' => 'slot\s*deposit', 'keyword' => 'Slot Deposit', 'category' => 'Slot', 'severity' => 'critical'],
    ['pattern' => 'rtp\s*slot', 'keyword' => 'RTP Slot', 'category' => 'Slot', 'severity' => 'high'],
    ['pattern' => 'bocoran\s*slot', 'keyword' => 'Bocoran Slot', 'category' => 'Slot', 'severity' => 'critical'],
    ['pattern' => 'pola\s*slot', 'keyword' => 'Pola Slot', 'category' => 'Slot', 'severity' => 'high'],
    ['pattern' => 'demo\s*slot', 'keyword' => 'Demo Slot', 'category' => 'Slot', 'severity' => 'high'],
    ['pattern' => 'akun\s*slot', 'keyword' => 'Akun Slot', 'category' => 'Slot', 'severity' => 'high'],
    ['pattern' => 'link\s*slot', 'keyword' => 'Link Slot', 'category' => 'Slot', 'severity' => 'critical'],
    ['pattern' => 'situs\s*slot', 'keyword' => 'Situs Slot', 'category' => 'Slot', 'severity' => 'critical'],
    ['pattern' => 'agen\s*slot', 'keyword' => 'Agen Slot', 'category' => 'Slot', 'severity' => 'critical'],
    ['pattern' => 'bandar\s*slot', 'keyword' => 'Bandar Slot', 'category' => 'Slot', 'severity' => 'critical'],
    ['pattern' => 'daftar\s*slot', 'keyword' => 'Daftar Slot', 'category' => 'Slot', 'severity' => 'critical'],
    
    // Slot Providers
    ['pattern' => 'pragmatic\s*play', 'keyword' => 'Pragmatic Play', 'category' => 'Slot Provider', 'severity' => 'high'],
    ['pattern' => 'pragmatic', 'keyword' => 'Pragmatic', 'category' => 'Slot Provider', 'severity' => 'high'],
    ['pattern' => 'pg\s*soft', 'keyword' => 'PG Soft', 'category' => 'Slot Provider', 'severity' => 'high'],
    ['pattern' => 'habanero', 'keyword' => 'Habanero', 'category' => 'Slot Provider', 'severity' => 'high'],
    ['pattern' => 'joker123', 'keyword' => 'Joker123', 'category' => 'Slot Provider', 'severity' => 'high'],
    ['pattern' => 'joker\s*gaming', 'keyword' => 'Joker Gaming', 'category' => 'Slot Provider', 'severity' => 'high'],
    ['pattern' => 'spadegaming', 'keyword' => 'Spadegaming', 'category' => 'Slot Provider', 'severity' => 'high'],
    ['pattern' => 'microgaming', 'keyword' => 'Microgaming', 'category' => 'Slot Provider', 'severity' => 'high'],
    
    // Slot Games
    ['pattern' => 'gates\s*of\s*olympus', 'keyword' => 'Gates of Olympus', 'category' => 'Slot Games', 'severity' => 'high'],
    ['pattern' => 'sweet\s*bonanza', 'keyword' => 'Sweet Bonanza', 'category' => 'Slot Games', 'severity' => 'high'],
    ['pattern' => 'starlight\s*princess', 'keyword' => 'Starlight Princess', 'category' => 'Slot Games', 'severity' => 'high'],
    ['pattern' => 'mahjong\s*ways', 'keyword' => 'Mahjong Ways', 'category' => 'Slot Games', 'severity' => 'high'],
    ['pattern' => 'wild\s*west\s*gold', 'keyword' => 'Wild West Gold', 'category' => 'Slot Games', 'severity' => 'high'],
    ['pattern' => 'aztec\s*gems', 'keyword' => 'Aztec Gems', 'category' => 'Slot Games', 'severity' => 'high'],
    ['pattern' => 'lucky\s*neko', 'keyword' => 'Lucky Neko', 'category' => 'Slot Games', 'severity' => 'high'],
    
    // Slot Terms
    ['pattern' => 'maxwin', 'keyword' => 'Maxwin', 'category' => 'Slot Terms', 'severity' => 'high'],
    ['pattern' => 'gacor', 'keyword' => 'Gacor', 'category' => 'Slot Terms', 'severity' => 'high'],
    ['pattern' => 'scatter', 'keyword' => 'Scatter', 'category' => 'Slot Terms', 'severity' => 'medium'],
    ['pattern' => 'jackpot', 'keyword' => 'Jackpot', 'category' => 'Slot Terms', 'severity' => 'medium'],
    ['pattern' => 'free\s*spin', 'keyword' => 'Free Spin', 'category' => 'Slot Terms', 'severity' => 'medium'],
    ['pattern' => 'spin\s*gratis', 'keyword' => 'Spin Gratis', 'category' => 'Slot Terms', 'severity' => 'medium'],
    
    // Togel
    ['pattern' => 'togel\s*online', 'keyword' => 'Togel Online', 'category' => 'Togel', 'severity' => 'critical'],
    ['pattern' => 'togel\s*singapore', 'keyword' => 'Togel Singapore', 'category' => 'Togel', 'severity' => 'critical'],
    ['pattern' => 'togel\s*hongkong', 'keyword' => 'Togel Hongkong', 'category' => 'Togel', 'severity' => 'critical'],
    ['pattern' => 'togel\s*sydney', 'keyword' => 'Togel Sydney', 'category' => 'Togel', 'severity' => 'critical'],
    ['pattern' => 'togel', 'keyword' => 'Togel', 'category' => 'Togel', 'severity' => 'high'],
    ['pattern' => 'toto\s*macau', 'keyword' => 'Toto Macau', 'category' => 'Togel', 'severity' => 'high'],
    ['pattern' => 'data\s*sgp', 'keyword' => 'Data SGP', 'category' => 'Togel', 'severity' => 'high'],
    ['pattern' => 'data\s*hk', 'keyword' => 'Data HK', 'category' => 'Togel', 'severity' => 'high'],
    ['pattern' => 'keluaran\s*sgp', 'keyword' => 'Keluaran SGP', 'category' => 'Togel', 'severity' => 'high'],
    ['pattern' => 'keluaran\s*hk', 'keyword' => 'Keluaran HK', 'category' => 'Togel', 'severity' => 'high'],
    ['pattern' => 'pengeluaran\s*sgp', 'keyword' => 'Pengeluaran SGP', 'category' => 'Togel', 'severity' => 'high'],
    ['pattern' => 'prediksi\s*togel', 'keyword' => 'Prediksi Togel', 'category' => 'Togel', 'severity' => 'high'],
    ['pattern' => 'angka\s*jitu', 'keyword' => 'Angka Jitu', 'category' => 'Togel', 'severity' => 'high'],
    ['pattern' => 'syair\s*togel', 'keyword' => 'Syair Togel', 'category' => 'Togel', 'severity' => 'high'],
    
    // Casino & Poker
    ['pattern' => 'casino\s*online', 'keyword' => 'Casino Online', 'category' => 'Casino', 'severity' => 'critical'],
    ['pattern' => 'live\s*casino', 'keyword' => 'Live Casino', 'category' => 'Casino', 'severity' => 'critical'],
    ['pattern' => 'poker\s*online', 'keyword' => 'Poker Online', 'category' => 'Poker', 'severity' => 'critical'],
    ['pattern' => 'domino\s*qq', 'keyword' => 'Domino QQ', 'category' => 'Poker', 'severity' => 'high'],
    ['pattern' => 'dominoqq', 'keyword' => 'DominoQQ', 'category' => 'Poker', 'severity' => 'high'],
    ['pattern' => 'bandarq', 'keyword' => 'BandarQ', 'category' => 'Poker', 'severity' => 'high'],
    ['pattern' => 'pkv\s*games', 'keyword' => 'PKV Games', 'category' => 'Poker', 'severity' => 'high'],
    ['pattern' => 'idn\s*poker', 'keyword' => 'IDN Poker', 'category' => 'Poker', 'severity' => 'high'],
    ['pattern' => 'baccarat', 'keyword' => 'Baccarat', 'category' => 'Casino', 'severity' => 'high'],
    ['pattern' => 'roulette', 'keyword' => 'Roulette', 'category' => 'Casino', 'severity' => 'high'],
    ['pattern' => 'blackjack', 'keyword' => 'Blackjack', 'category' => 'Casino', 'severity' => 'high'],
    
    // Sports Betting
    ['pattern' => 'judi\s*bola', 'keyword' => 'Judi Bola', 'category' => 'Betting', 'severity' => 'critical'],
    ['pattern' => 'taruhan\s*bola', 'keyword' => 'Taruhan Bola', 'category' => 'Betting', 'severity' => 'critical'],
    ['pattern' => 'sbobet', 'keyword' => 'SBOBET', 'category' => 'Betting', 'severity' => 'critical'],
    ['pattern' => 'ibcbet', 'keyword' => 'IBCBET', 'category' => 'Betting', 'severity' => 'high'],
    ['pattern' => 'maxbet', 'keyword' => 'Maxbet', 'category' => 'Betting', 'severity' => 'high'],
    ['pattern' => 'taruhan\s*online', 'keyword' => 'Taruhan Online', 'category' => 'Betting', 'severity' => 'high'],
    ['pattern' => 'betting\s*online', 'keyword' => 'Betting Online', 'category' => 'Betting', 'severity' => 'high'],
    
    // General Gambling
    ['pattern' => 'judi\s*online', 'keyword' => 'Judi Online', 'category' => 'Gambling', 'severity' => 'critical'],
    ['pattern' => 'situs\s*judi', 'keyword' => 'Situs Judi', 'category' => 'Gambling', 'severity' => 'critical'],
    ['pattern' => 'agen\s*judi', 'keyword' => 'Agen Judi', 'category' => 'Gambling', 'severity' => 'critical'],
    ['pattern' => 'bandar\s*judi', 'keyword' => 'Bandar Judi', 'category' => 'Gambling', 'severity' => 'critical'],
    ['pattern' => 'bonus\s*new\s*member', 'keyword' => 'Bonus New Member', 'category' => 'Promo', 'severity' => 'high'],
    ['pattern' => 'bonus\s*deposit', 'keyword' => 'Bonus Deposit', 'category' => 'Promo', 'severity' => 'high'],
    ['pattern' => 'link\s*alternatif', 'keyword' => 'Link Alternatif', 'category' => 'Gambling', 'severity' => 'high'],
    ['pattern' => 'daftar\s*sekarang', 'keyword' => 'Daftar Sekarang', 'category' => 'Promo', 'severity' => 'medium'],
    ['pattern' => 'withdraw', 'keyword' => 'Withdraw', 'category' => 'Gambling', 'severity' => 'medium'],
    ['pattern' => 'depo\s*pulsa', 'keyword' => 'Depo Pulsa', 'category' => 'Gambling', 'severity' => 'high'],
    ['pattern' => 'deposit\s*pulsa', 'keyword' => 'Deposit Pulsa', 'category' => 'Gambling', 'severity' => 'high'],
];

$result = [
    'id' => $id,
    'url' => $url,
    'status' => 'unknown',
    'http_code' => 0,
    'response_time' => 0,
    'is_infected' => false,
    'infection_status' => 'Aman',
    'detections' => [],
    'detection_count' => 0,
    'detected_keywords' => [],
    'scan_time' => date('Y-m-d H:i:s'),
    'content_length' => 0,
    'error' => null,
    'scan_mode' => 'realtime'
];

// Fetch website content REALTIME with multiple attempts
$html_content = '';
$fetch_success = false;

// Try multiple user agents for better compatibility
$user_agents = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
    'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
];

foreach ($user_agents as $ua) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_CONNECTTIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => $ua,
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
            'Accept-Encoding: gzip, deflate',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
        ],
        CURLOPT_ENCODING => '',
        CURLOPT_FRESH_CONNECT => true,
        CURLOPT_FORBID_REUSE => true,
    ]);

    $start_time = microtime(true);
    $html_content = curl_exec($ch);
    $end_time = microtime(true);

    $result['response_time'] = round(($end_time - $start_time) * 1000);
    $result['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $result['content_length'] = strlen($html_content);

    $curl_error = curl_error($ch);
    $curl_errno = curl_errno($ch);
    curl_close($ch);

    if (!$curl_errno && $result['http_code'] >= 200 && $result['http_code'] < 400 && !empty($html_content)) {
        $fetch_success = true;
        break;
    }
}

// Handle fetch errors
if (!$fetch_success) {
    if ($curl_errno) {
        $result['status'] = 'offline';
        $result['error'] = "Connection failed: $curl_error";
    } elseif ($result['http_code'] == 0) {
        $result['status'] = 'offline';
        $result['error'] = 'Could not connect to server';
    } elseif ($result['http_code'] >= 400) {
        $result['status'] = 'error';
        $result['error'] = 'HTTP Error: ' . $result['http_code'];
    } else {
        $result['status'] = 'error';
        $result['error'] = 'Empty response from server';
    }
    echo json_encode($result);
    exit();
}

// Website is online
$result['status'] = 'online';

// Decode content if gzipped
if (substr($html_content, 0, 2) === "\x1f\x8b") {
    $html_content = gzdecode($html_content);
}

// Analyze content for malware patterns - COMPREHENSIVE SCAN
$detections = [];

// Get text content (visible text)
$text_content = strip_tags($html_content);
$text_content = html_entity_decode($text_content, ENT_QUOTES, 'UTF-8');
$text_content = preg_replace('/\s+/', ' ', $text_content);

// Also check raw HTML for hidden content
$html_lower = strtolower($html_content);
$text_lower = strtolower($text_content);

foreach ($malware_patterns as $pattern_info) {
    $regex = '/' . $pattern_info['pattern'] . '/iu';
    
    // Check in visible text content
    if (preg_match_all($regex, $text_content, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[0] as $match) {
            $matched_text = $match[0];
            $position = $match[1];
            
            // Get context around the match
            $start = max(0, $position - 50);
            $length = min(150, strlen($text_content) - $start);
            $context = substr($text_content, $start, $length);
            $context = trim(preg_replace('/\s+/', ' ', $context));
            
            $detections[] = [
                'type' => 'Text Content',
                'keyword' => $pattern_info['keyword'],
                'category' => $pattern_info['category'],
                'severity' => $pattern_info['severity'],
                'content' => $matched_text,
                'context' => '...' . $context . '...',
                'location' => 'Body Text'
            ];
        }
    }
    
    // Check in HTML title
    if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html_content, $title_match)) {
        if (preg_match($regex, $title_match[1], $m)) {
            $detections[] = [
                'type' => 'Title Tag',
                'keyword' => $pattern_info['keyword'],
                'category' => $pattern_info['category'],
                'severity' => 'critical',
                'content' => $m[0],
                'context' => $title_match[1],
                'location' => 'Page Title'
            ];
        }
    }
    
    // Check in meta description
    if (preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/is', $html_content, $meta_match)) {
        if (preg_match($regex, $meta_match[1], $m)) {
            $detections[] = [
                'type' => 'Meta Description',
                'keyword' => $pattern_info['keyword'],
                'category' => $pattern_info['category'],
                'severity' => 'critical',
                'content' => $m[0],
                'context' => substr($meta_match[1], 0, 100),
                'location' => 'Meta Tag'
            ];
        }
    }
    
    // Check in meta keywords
    if (preg_match('/<meta[^>]*name=["\']keywords["\'][^>]*content=["\']([^"\']*)["\'][^>]*>/is', $html_content, $kw_match)) {
        if (preg_match($regex, $kw_match[1], $m)) {
            $detections[] = [
                'type' => 'Meta Keywords',
                'keyword' => $pattern_info['keyword'],
                'category' => $pattern_info['category'],
                'severity' => 'critical',
                'content' => $m[0],
                'context' => substr($kw_match[1], 0, 100),
                'location' => 'Meta Keywords'
            ];
        }
    }
    
    // Check in links (href)
    if (preg_match_all('/<a[^>]*href=["\']([^"\']*)["\'][^>]*>/i', $html_content, $link_matches)) {
        foreach ($link_matches[1] as $href) {
            if (preg_match($regex, $href, $m)) {
                $detections[] = [
                    'type' => 'Suspicious Link',
                    'keyword' => $pattern_info['keyword'],
                    'category' => $pattern_info['category'],
                    'severity' => 'critical',
                    'content' => $m[0],
                    'context' => substr($href, 0, 100),
                    'location' => 'Anchor Link'
                ];
            }
        }
    }
    
    // Check in anchor text
    if (preg_match_all('/<a[^>]*>(.*?)<\/a>/is', $html_content, $anchor_matches)) {
        foreach ($anchor_matches[1] as $anchor_text) {
            $clean_anchor = strip_tags($anchor_text);
            if (preg_match($regex, $clean_anchor, $m)) {
                $detections[] = [
                    'type' => 'Anchor Text',
                    'keyword' => $pattern_info['keyword'],
                    'category' => $pattern_info['category'],
                    'severity' => 'high',
                    'content' => $m[0],
                    'context' => substr($clean_anchor, 0, 100),
                    'location' => 'Link Text'
                ];
            }
        }
    }
    
    // Check in hidden elements (display:none, visibility:hidden)
    if (preg_match_all('/<[^>]*(display\s*:\s*none|visibility\s*:\s*hidden)[^>]*>(.*?)<\/[^>]+>/is', $html_content, $hidden_matches)) {
        foreach ($hidden_matches[2] as $hidden_content) {
            if (preg_match($regex, $hidden_content, $m)) {
                $detections[] = [
                    'type' => 'Hidden Content',
                    'keyword' => $pattern_info['keyword'],
                    'category' => $pattern_info['category'],
                    'severity' => 'critical',
                    'content' => $m[0],
                    'context' => 'Hidden element detected',
                    'location' => 'Hidden Element'
                ];
            }
        }
    }
    
    // Check in iframes
    if (preg_match_all('/<iframe[^>]*src=["\']([^"\']*)["\'][^>]*>/i', $html_content, $iframe_matches)) {
        foreach ($iframe_matches[1] as $iframe_src) {
            if (preg_match($regex, $iframe_src, $m)) {
                $detections[] = [
                    'type' => 'Suspicious Iframe',
                    'keyword' => $pattern_info['keyword'],
                    'category' => $pattern_info['category'],
                    'severity' => 'critical',
                    'content' => $m[0],
                    'context' => substr($iframe_src, 0, 100),
                    'location' => 'Iframe Source'
                ];
            }
        }
    }
    
    // Check in JavaScript
    if (preg_match_all('/<script[^>]*>(.*?)<\/script>/is', $html_content, $script_matches)) {
        foreach ($script_matches[1] as $script_content) {
            if (preg_match($regex, $script_content, $m)) {
                $detections[] = [
                    'type' => 'JavaScript Injection',
                    'keyword' => $pattern_info['keyword'],
                    'category' => $pattern_info['category'],
                    'severity' => 'critical',
                    'content' => $m[0],
                    'context' => 'Found in JavaScript code',
                    'location' => 'Script Tag'
                ];
            }
        }
    }
    
    // Check in comments (hackers sometimes hide content in comments)
    if (preg_match_all('/<!--(.*?)-->/is', $html_content, $comment_matches)) {
        foreach ($comment_matches[1] as $comment) {
            if (preg_match($regex, $comment, $m)) {
                $detections[] = [
                    'type' => 'Hidden Comment',
                    'keyword' => $pattern_info['keyword'],
                    'category' => $pattern_info['category'],
                    'severity' => 'high',
                    'content' => $m[0],
                    'context' => 'Found in HTML comment',
                    'location' => 'HTML Comment'
                ];
            }
        }
    }
}

// Remove duplicates based on keyword + type + location
$unique_detections = [];
$seen = [];
foreach ($detections as $d) {
    $key = $d['keyword'] . '|' . $d['type'] . '|' . $d['location'];
    if (!isset($seen[$key])) {
        $seen[$key] = true;
        $unique_detections[] = $d;
    }
}

// Sort by severity
usort($unique_detections, function($a, $b) {
    $severity_order = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
    return ($severity_order[$a['severity']] ?? 4) - ($severity_order[$b['severity']] ?? 4);
});

$result['detections'] = $unique_detections;
$result['detection_count'] = count($unique_detections);
$result['is_infected'] = count($unique_detections) > 0;
$result['detected_keywords'] = array_values(array_unique(array_column($unique_detections, 'keyword')));

// Determine infection status based on severity and count
$critical_count = count(array_filter($unique_detections, fn($d) => $d['severity'] === 'critical'));
$high_count = count(array_filter($unique_detections, fn($d) => $d['severity'] === 'high'));

if ($critical_count > 0 || count($unique_detections) > 10) {
    $result['infection_status'] = 'Terinfeksi Parah';
} elseif ($high_count > 0 || count($unique_detections) > 5) {
    $result['infection_status'] = 'Terinfeksi';
} elseif (count($unique_detections) > 0) {
    $result['infection_status'] = 'Terdeteksi';
} else {
    $result['infection_status'] = 'Aman';
}

// Save results to database
if ($id > 0) {
    $save_query = "INSERT INTO content_scan_results 
        (website_id, url, status, http_code, response_time, content_length, is_infected, infection_status, detection_count, detected_keywords, detections_json, scan_time) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
        url = VALUES(url),
        status = VALUES(status),
        http_code = VALUES(http_code),
        response_time = VALUES(response_time),
        content_length = VALUES(content_length),
        is_infected = VALUES(is_infected),
        infection_status = VALUES(infection_status),
        detection_count = VALUES(detection_count),
        detected_keywords = VALUES(detected_keywords),
        detections_json = VALUES(detections_json),
        scan_time = NOW()";
    
    $stmt = mysqli_prepare($conn, $save_query);
    if ($stmt) {
        $is_infected_int = $result['is_infected'] ? 1 : 0;
        $keywords_str = implode(', ', $result['detected_keywords']);
        $detections_json = json_encode($result['detections']);
        
        mysqli_stmt_bind_param($stmt, 'issiiiisiss', 
            $id, 
            $url, 
            $result['status'], 
            $result['http_code'], 
            $result['response_time'], 
            $result['content_length'],
            $is_infected_int, 
            $result['infection_status'], 
            $result['detection_count'], 
            $keywords_str,
            $detections_json
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

echo json_encode($result);
?>
