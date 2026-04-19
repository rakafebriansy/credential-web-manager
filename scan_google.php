<?php
require_once 'config/init.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$url = $_POST['url'] ?? $_GET['url'] ?? '';
$id = $_POST['id'] ?? $_GET['id'] ?? 0;
$action = $_POST['action'] ?? 'scan';

if (empty($url)) {
    echo json_encode(['error' => 'URL is required']);
    exit();
}

// Extract domain from URL
function extractDomain($url) {
    $parsed = parse_url($url);
    $host = $parsed['host'] ?? $url;
    $host = preg_replace('/^www\./', '', $host);
    return $host;
}

$domain = extractDomain($url);

// Malware/Gambling search queries dengan kategori
$search_queries = [
    ['keyword' => 'slot gacor', 'category' => 'Slot Gambling', 'severity' => 'high'],
    ['keyword' => 'slot online', 'category' => 'Slot Gambling', 'severity' => 'high'],
    ['keyword' => 'togel', 'category' => 'Togel', 'severity' => 'high'],
    ['keyword' => 'judi online', 'category' => 'Online Gambling', 'severity' => 'high'],
    ['keyword' => 'casino online', 'category' => 'Casino', 'severity' => 'high'],
    ['keyword' => 'sbobet', 'category' => 'Sports Betting', 'severity' => 'high'],
    ['keyword' => 'poker online', 'category' => 'Poker', 'severity' => 'high'],
    ['keyword' => 'slot88', 'category' => 'Slot Site', 'severity' => 'high'],
    ['keyword' => 'slot777', 'category' => 'Slot Site', 'severity' => 'high'],
    ['keyword' => 'pragmatic', 'category' => 'Slot Provider', 'severity' => 'medium'],
    ['keyword' => 'maxwin', 'category' => 'Slot Terms', 'severity' => 'medium'],
    ['keyword' => 'data sgp', 'category' => 'Togel Data', 'severity' => 'high'],
    ['keyword' => 'data hk', 'category' => 'Togel Data', 'severity' => 'high'],
    ['keyword' => 'bandar togel', 'category' => 'Togel', 'severity' => 'high'],
    ['keyword' => 'rtp slot', 'category' => 'Slot Terms', 'severity' => 'medium'],
    ['keyword' => 'akun pro', 'category' => 'Slot Terms', 'severity' => 'medium'],
    ['keyword' => 'scatter', 'category' => 'Slot Terms', 'severity' => 'low'],
    ['keyword' => 'bonus deposit', 'category' => 'Gambling Promo', 'severity' => 'medium'],
    ['keyword' => 'server thailand', 'category' => 'Slot Server', 'severity' => 'medium'],
    ['keyword' => 'gacor hari ini', 'category' => 'Slot Terms', 'severity' => 'high'],
];

// Return search queries for client-side scanning
if ($action === 'get_queries') {
    $queries = [];
    foreach ($search_queries as $q) {
        $queries[] = [
            'keyword' => $q['keyword'],
            'category' => $q['category'],
            'severity' => $q['severity'],
            'search_url' => "https://www.google.com/search?q=" . urlencode("site:{$domain} {$q['keyword']}"),
            'api_url' => "https://www.googleapis.com/customsearch/v1?q=" . urlencode("site:{$domain} {$q['keyword']}")
        ];
    }
    
    echo json_encode([
        'domain' => $domain,
        'queries' => $queries,
        'total_queries' => count($queries)
    ]);
    exit();
}

// Save results from client-side scan
if ($action === 'save_results') {
    $results = json_decode($_POST['results'] ?? '[]', true);
    $infection_level = $_POST['infection_level'] ?? 'safe';
    $total_results = intval($_POST['total_results'] ?? 0);
    $detection_count = intval($_POST['detection_count'] ?? 0);
    $is_infected = $detection_count > 0 ? 1 : 0;
    
    $save_query = "INSERT INTO google_scan_results (website_id, domain, is_infected, infection_level, total_results, detection_count, detections_json, scan_time) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                   ON DUPLICATE KEY UPDATE 
                   is_infected = VALUES(is_infected),
                   infection_level = VALUES(infection_level),
                   total_results = VALUES(total_results),
                   detection_count = VALUES(detection_count),
                   detections_json = VALUES(detections_json),
                   scan_time = NOW()";
    
    $stmt = mysqli_prepare($conn, $save_query);
    if ($stmt) {
        $results_json = json_encode($results);
        mysqli_stmt_bind_param($stmt, 'isissss', $id, $domain, $is_infected, $infection_level, $total_results, $detection_count, $results_json);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        echo json_encode(['success' => $success, 'message' => 'Results saved']);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
    exit();
}

// Generate all Google search links for manual/client verification
$google_links = [];
foreach ($search_queries as $q) {
    $google_links[] = [
        'keyword' => $q['keyword'],
        'category' => $q['category'],
        'severity' => $q['severity'],
        'url' => "https://www.google.com/search?q=" . urlencode("site:{$domain} {$q['keyword']}")
    ];
}

echo json_encode([
    'id' => $id,
    'url' => $url,
    'domain' => $domain,
    'google_links' => $google_links,
    'quick_links' => [
        'all' => "https://www.google.com/search?q=" . urlencode("site:{$domain} toto slot gacor"),
        'slot' => "https://www.google.com/search?q=" . urlencode("site:{$domain} slot online slot88"),
        'togel' => "https://www.google.com/search?q=" . urlencode("site:{$domain} togel 4d"),
        'casino' => "https://www.google.com/search?q=" . urlencode("site:{$domain} casino poker sbobet"),
    ],
    'scan_time' => date('Y-m-d H:i:s')
]);
?>
