<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

// UptimeRobot API Configuration - Multiple API Keys
define('UPTIMEROBOT_API_URL', 'https://api.uptimerobot.com/v2/');

// All API Keys with account names
$UPTIMEROBOT_ACCOUNTS = [
    ['name' => 'Account 1', 'main_key' => 'u2174807-01f4af1eebef7bbdeb739f97', 'read_key' => 'ur2174807-6199155959e0010a6a0658cf'],
    ['name' => 'Account 2', 'main_key' => 'u2174447-488abe940ea4a933588884fe', 'read_key' => 'ur2174447-61dc1973737add9d07243709'],
    ['name' => 'Account 3', 'main_key' => 'u2176004-725fa75d18a8b442ccf0b1c6', 'read_key' => 'ur2176004-390a6f4ace34595b05f99916'],
    ['name' => 'Account 4', 'main_key' => 'u2177358-a954628cadedbd414ec70fc6', 'read_key' => 'ur2177358-02ad4df0f4a833d2b55a5c32'],
    ['name' => 'Account 5', 'main_key' => 'u2256552-e7ffc43fdbc25a77e0ad75f9', 'read_key' => 'ur2256552-1161a20345d65c743ea917a6'],
    ['name' => 'Account 6', 'main_key' => 'u2174803-8e25f0e3c6b118ae7274071b', 'read_key' => 'ur2174803-ad8bd365106d6ae653468fad'],
    ['name' => 'Account 7', 'main_key' => 'u2171559-b31b01382804bd47533bbec6', 'read_key' => 'ur2171559-235355acdff920618eff7cf9'],
    ['name' => 'Account 8', 'main_key' => 'u2694006-53eff73d194720d23cd6e13c', 'read_key' => 'ur2694006-6a3a355f55f40dc406697e16'],
    ['name' => 'Account 9', 'main_key' => 'u2837259-1654137bbe71e56746152af5', 'read_key' => 'ur2837259-350ccd754fc6d48c529476dc'],
    ['name' => 'Account 10', 'main_key' => 'u2185833-8172f2fe66686ff103a9c5c0', 'read_key' => 'ur2185833-4ef3442aa4c9f7acfb472f99'],
];

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_monitors':
        $result = getAllMonitors($UPTIMEROBOT_ACCOUNTS);
        echo json_encode($result);
        break;
        
    case 'get_logs':
        $id = intval($_GET['id'] ?? 0);
        $apiKey = $_GET['api_key'] ?? '';
        $result = getMonitorLogs($id, $apiKey, $UPTIMEROBOT_ACCOUNTS);
        echo json_encode($result);
        break;
        
    case 'get_monitor':
        $id = intval($_GET['id'] ?? 0);
        $apiKey = $_GET['api_key'] ?? '';
        $result = getMonitorDetail($id, $apiKey, $UPTIMEROBOT_ACCOUNTS);
        echo json_encode($result);
        break;
    
    case 'get_accounts':
        $accounts = [];
        foreach ($UPTIMEROBOT_ACCOUNTS as $index => $account) {
            $accounts[] = [
                'index' => $index,
                'name' => $account['name']
            ];
        }
        echo json_encode(['success' => true, 'accounts' => $accounts]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Get all monitors from all UptimeRobot accounts using parallel requests
 */
function getAllMonitors($accounts) {
    $allMonitors = [];
    $errors = [];
    
    // Prepare curl multi handle
    $multiHandle = curl_multi_init();
    $curlHandles = [];
    
    // Create all curl handles
    foreach ($accounts as $index => $account) {
        $params = [
            'api_key' => $account['read_key'],
            'format' => 'json',
            'response_times' => 1,
            'response_times_limit' => 1,
            'custom_uptime_ratios' => '30'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => UPTIMEROBOT_API_URL . 'getMonitors',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        curl_multi_add_handle($multiHandle, $ch);
        $curlHandles[$index] = $ch;
    }
    
    // Execute all requests in parallel
    $running = null;
    do {
        curl_multi_exec($multiHandle, $running);
        curl_multi_select($multiHandle);
    } while ($running > 0);
    
    // Process responses
    foreach ($accounts as $index => $account) {
        $ch = $curlHandles[$index];
        $response = curl_multi_getcontent($ch);
        $data = json_decode($response, true);
        
        if ($data && isset($data['stat']) && $data['stat'] === 'ok') {
            $monitors = $data['monitors'] ?? [];
            
            foreach ($monitors as &$monitor) {
                $monitor['account_name'] = $account['name'];
                $monitor['account_index'] = $index;
                $monitor['api_key'] = $account['read_key'];
                $monitor['average_response_time'] = $monitor['response_times'][0]['value'] ?? 0;
                $monitor['custom_uptime_ratio'] = $monitor['custom_uptime_ratio'] ?? '0';
            }
            
            $allMonitors = array_merge($allMonitors, $monitors);
        } else {
            $errors[] = $account['name'] . ': ' . ($data['error']['message'] ?? 'Failed');
        }
        
        curl_multi_remove_handle($multiHandle, $ch);
        curl_close($ch);
    }
    
    curl_multi_close($multiHandle);
    
    // Sort: down first
    usort($allMonitors, function($a, $b) {
        $order = [9 => 0, 8 => 1, 2 => 2, 1 => 3, 0 => 4];
        return ($order[$a['status']] ?? 5) - ($order[$b['status']] ?? 5);
    });
    
    return [
        'success' => true,
        'monitors' => $allMonitors,
        'total' => count($allMonitors),
        'accounts_count' => count($accounts),
        'errors' => $errors
    ];
}

/**
 * Get monitor logs/events
 */
function getMonitorLogs($monitorId, $apiKey, $accounts) {
    // Find the correct API key if not provided
    if (empty($apiKey)) {
        $apiKey = $accounts[0]['read_key'];
    }
    
    $params = [
        'api_key' => $apiKey,
        'format' => 'json',
        'monitors' => $monitorId,
        'logs' => 1,
        'logs_limit' => 50
    ];
    
    $response = callUptimeRobotAPI('getMonitors', $params);
    
    if ($response && isset($response['stat']) && $response['stat'] === 'ok') {
        $monitors = $response['monitors'] ?? [];
        $logs = [];
        
        if (count($monitors) > 0 && isset($monitors[0]['logs'])) {
            $logs = $monitors[0]['logs'];
        }
        
        return [
            'success' => true,
            'logs' => $logs
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Failed to fetch logs'
    ];
}

/**
 * Get single monitor detail
 */
function getMonitorDetail($monitorId, $apiKey, $accounts) {
    if (empty($apiKey)) {
        $apiKey = $accounts[0]['read_key'];
    }
    
    $params = [
        'api_key' => $apiKey,
        'format' => 'json',
        'monitors' => $monitorId,
        'response_times' => 1,
        'response_times_limit' => 24,
        'logs' => 1,
        'logs_limit' => 10,
        'custom_uptime_ratios' => '1-7-30-365',
        'all_time_uptime_ratio' => 1
    ];
    
    $response = callUptimeRobotAPI('getMonitors', $params);
    
    if ($response && isset($response['stat']) && $response['stat'] === 'ok') {
        $monitors = $response['monitors'] ?? [];
        
        if (count($monitors) > 0) {
            return [
                'success' => true,
                'monitor' => $monitors[0]
            ];
        }
    }
    
    return [
        'success' => false,
        'message' => 'Monitor not found'
    ];
}

/**
 * Call UptimeRobot API
 */
function callUptimeRobotAPI($endpoint, $params) {
    $url = UPTIMEROBOT_API_URL . $endpoint;
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'Cache-Control: no-cache'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return [
            'stat' => 'fail',
            'error' => ['message' => 'cURL Error: ' . $error]
        ];
    }
    
    return json_decode($response, true);
}
?>
