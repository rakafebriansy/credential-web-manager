<?php
/**
 * Local API Test Runner for E-Report Dashboard
 * Simulates Postman collection tests
 */

require_once 'config/init.php';

// Ensure we're logged in for tests
if (!isLoggedIn()) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['role'] = 'admin';
    $_SESSION['credentials_access'] = true;
}

$base_url = 'http://localhost/ereport';

$results = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'tests' => []
];

function runTest($name, $method, $endpoint, $data = [], $expected = []) {
    global $results, $base_url;
    
    $results['total']++;
    $url = $base_url . '/' . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    // Set cookies for session
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $body = substr($response, $header_size);
    $error = curl_error($ch);
    curl_close($ch);
    
    $test_result = [
        'name' => $name,
        'endpoint' => $endpoint,
        'method' => $method,
        'http_code' => $http_code,
        'passed' => true,
        'errors' => [],
        'response_preview' => substr($body, 0, 200)
    ];
    
    // Check HTTP code
    if (isset($expected['http_code'])) {
        if ($http_code != $expected['http_code']) {
            $test_result['passed'] = false;
            $test_result['errors'][] = "Expected HTTP {$expected['http_code']}, got {$http_code}";
        }
    }
    
    // Check if response is JSON
    if (isset($expected['is_json']) && $expected['is_json']) {
        $json = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $test_result['passed'] = false;
            $test_result['errors'][] = "Expected JSON response, got: " . substr($body, 0, 100);
        } else {
            $test_result['json_response'] = $json;
            
            // Check for success field
            if (isset($expected['success'])) {
                if (!isset($json['success']) || $json['success'] !== $expected['success']) {
                    $test_result['passed'] = false;
                    $test_result['errors'][] = "Expected success={$expected['success']}, got " . ($json['success'] ?? 'null');
                }
            }
        }
    }
    
    // Check for redirect
    if (isset($expected['redirect'])) {
        if ($http_code != 302 && $http_code != 301) {
            $test_result['passed'] = false;
            $test_result['errors'][] = "Expected redirect (301/302), got {$http_code}";
        }
    }
    
    if ($test_result['passed']) {
        $results['passed']++;
    } else {
        $results['failed']++;
    }
    
    $results['tests'][] = $test_result;
    return $test_result;
}

// HTML Output
?>
<!DOCTYPE html>
<html>
<head>
    <title>API Test Runner - E-Report Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #333; }
        .summary { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .summary-stats { display: flex; gap: 20px; }
        .stat { padding: 15px 25px; border-radius: 8px; color: white; font-weight: bold; }
        .stat.total { background: #667eea; }
        .stat.passed { background: #10b981; }
        .stat.failed { background: #ef4444; }
        .test-group { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .test-group h2 { margin-top: 0; color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .test { padding: 15px; margin: 10px 0; border-radius: 6px; border-left: 4px solid; }
        .test.passed { background: #d1fae5; border-left-color: #10b981; }
        .test.failed { background: #fee2e2; border-left-color: #ef4444; }
        .test-name { font-weight: bold; margin-bottom: 5px; }
        .test-details { font-size: 13px; color: #666; }
        .test-errors { color: #ef4444; margin-top: 10px; }
        .test-response { background: #f3f4f6; padding: 10px; border-radius: 4px; margin-top: 10px; font-family: monospace; font-size: 12px; overflow-x: auto; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .badge.get { background: #3b82f6; color: white; }
        .badge.post { background: #10b981; color: white; }
    </style>
</head>
<body>
<div class="container">
    <h1>🧪 API Test Runner - E-Report Dashboard</h1>
    
    <?php
    // Run all tests
    
    // ============ Web Progress Tests ============
    echo "<div class='test-group'><h2>Web Progress API</h2>";
    
    runTest(
        'Update Web Progress - Valid Field',
        'POST',
        'web_progress_action.php',
        ['website_id' => 1, 'field' => 'hasil_check', 'value' => 'Korespondensi Aktif'],
        ['http_code' => 200, 'is_json' => true, 'success' => true]
    );
    
    runTest(
        'Update Web Progress - Invalid Field',
        'POST',
        'web_progress_action.php',
        ['website_id' => 1, 'field' => 'invalid_field', 'value' => 'test'],
        ['http_code' => 200, 'is_json' => true, 'success' => false]
    );
    
    foreach ($results['tests'] as $test) {
        if (strpos($test['name'], 'Web Progress') !== false || $test['endpoint'] === 'web_progress_action.php') {
            $class = $test['passed'] ? 'passed' : 'failed';
            echo "<div class='test {$class}'>";
            echo "<div class='test-name'><span class='badge post'>POST</span> {$test['name']}</div>";
            echo "<div class='test-details'>Endpoint: {$test['endpoint']} | HTTP: {$test['http_code']}</div>";
            if (!empty($test['errors'])) {
                echo "<div class='test-errors'>" . implode('<br>', $test['errors']) . "</div>";
            }
            echo "</div>";
        }
    }
    echo "</div>";
    
    // ============ Cloudflare CDN Tests ============
    echo "<div class='test-group'><h2>Cloudflare CDN API</h2>";
    
    runTest(
        'Cloudflare - Test Connection',
        'POST',
        'cloudflare_action.php',
        ['action' => 'test'],
        ['http_code' => 200, 'is_json' => true, 'success' => true]
    );
    
    runTest(
        'Cloudflare - Update CDN Status',
        'POST',
        'cloudflare_action.php',
        ['action' => 'update', 'website_id' => 1, 'field' => 'cdn_status', 'value' => 'Cloudflare'],
        ['http_code' => 200, 'is_json' => true, 'success' => true]
    );
    
    runTest(
        'Cloudflare - Invalid CDN Value',
        'POST',
        'cloudflare_action.php',
        ['action' => 'update', 'website_id' => 1, 'field' => 'cdn_status', 'value' => 'InvalidCDN'],
        ['http_code' => 200, 'is_json' => true, 'success' => false]
    );
    
    foreach ($results['tests'] as $test) {
        if (strpos($test['name'], 'Cloudflare') !== false) {
            $class = $test['passed'] ? 'passed' : 'failed';
            echo "<div class='test {$class}'>";
            echo "<div class='test-name'><span class='badge post'>POST</span> {$test['name']}</div>";
            echo "<div class='test-details'>Endpoint: {$test['endpoint']} | HTTP: {$test['http_code']}</div>";
            if (!empty($test['errors'])) {
                echo "<div class='test-errors'>" . implode('<br>', $test['errors']) . "</div>";
            }
            if (isset($test['json_response'])) {
                echo "<div class='test-response'>" . json_encode($test['json_response'], JSON_PRETTY_PRINT) . "</div>";
            }
            echo "</div>";
        }
    }
    echo "</div>";
    
    // ============ LP Security Tests ============
    echo "<div class='test-group'><h2>LP Security API</h2>";
    
    runTest(
        'LP Security - Update Field',
        'POST',
        'lp_security_action.php',
        ['action' => 'update', 'website_id' => 1, 'field' => 'ganti_wp_admin', 'value' => 'Done'],
        ['http_code' => 200, 'is_json' => true]
    );
    
    runTest(
        'LP Security - Invalid Field',
        'POST',
        'lp_security_action.php',
        ['action' => 'update', 'website_id' => 1, 'field' => 'invalid_field', 'value' => 'test'],
        ['http_code' => 200, 'is_json' => true, 'success' => false]
    );
    
    foreach ($results['tests'] as $test) {
        if (strpos($test['name'], 'LP Security') !== false) {
            $class = $test['passed'] ? 'passed' : 'failed';
            echo "<div class='test {$class}'>";
            echo "<div class='test-name'><span class='badge post'>POST</span> {$test['name']}</div>";
            echo "<div class='test-details'>Endpoint: {$test['endpoint']} | HTTP: {$test['http_code']}</div>";
            if (!empty($test['errors'])) {
                echo "<div class='test-errors'>" . implode('<br>', $test['errors']) . "</div>";
            }
            if (isset($test['json_response'])) {
                echo "<div class='test-response'>" . json_encode($test['json_response'], JSON_PRETTY_PRINT) . "</div>";
            }
            echo "</div>";
        }
    }
    echo "</div>";
    
    // ============ Tickets Tests ============
    echo "<div class='test-group'><h2>Tickets API</h2>";
    
    runTest(
        'Tickets - Create Ticket',
        'POST',
        'tickets_action.php',
        [
            'action' => 'create',
            'website_id' => 1,
            'category' => 'Abstrak hilang',
            'priority' => 'High',
            'nama_pj' => 'Test User',
            'title' => 'API Test Ticket ' . date('Y-m-d H:i:s'),
            'description' => 'Test ticket from API runner',
            'assigned_to' => 'Abdul Fazri'
        ],
        ['redirect' => true]
    );
    
    runTest(
        'Tickets - Update Status',
        'POST',
        'tickets_action.php',
        ['action' => 'update_status', 'ticket_id' => 1, 'status' => 'In Progress'],
        ['redirect' => true]
    );
    
    runTest(
        'Tickets - Add Comment',
        'POST',
        'tickets_action.php',
        ['action' => 'add_comment', 'ticket_id' => 1, 'comment' => 'Test comment from API'],
        ['redirect' => true]
    );
    
    foreach ($results['tests'] as $test) {
        if (strpos($test['name'], 'Tickets') !== false) {
            $class = $test['passed'] ? 'passed' : 'failed';
            echo "<div class='test {$class}'>";
            echo "<div class='test-name'><span class='badge post'>POST</span> {$test['name']}</div>";
            echo "<div class='test-details'>Endpoint: {$test['endpoint']} | HTTP: {$test['http_code']}</div>";
            if (!empty($test['errors'])) {
                echo "<div class='test-errors'>" . implode('<br>', $test['errors']) . "</div>";
            }
            echo "</div>";
        }
    }
    echo "</div>";
    
    // ============ Scanner Tests ============
    echo "<div class='test-group'><h2>Scanner API</h2>";
    
    runTest(
        'Scanner - Scan Website',
        'POST',
        'scan_website.php',
        ['url' => 'https://example.com', 'id' => 1],
        ['http_code' => 200, 'is_json' => true]
    );
    
    runTest(
        'Scanner - Google Scan',
        'POST',
        'scan_google.php',
        ['url' => 'https://example.com', 'id' => 1],
        ['http_code' => 200, 'is_json' => true]
    );
    
    runTest(
        'Scanner - Vulnerability Scan',
        'POST',
        'scan_vulnerability.php',
        ['url' => 'https://example.com', 'id' => 1],
        ['http_code' => 200, 'is_json' => true]
    );
    
    foreach ($results['tests'] as $test) {
        if (strpos($test['name'], 'Scanner') !== false) {
            $class = $test['passed'] ? 'passed' : 'failed';
            echo "<div class='test {$class}'>";
            echo "<div class='test-name'><span class='badge post'>POST</span> {$test['name']}</div>";
            echo "<div class='test-details'>Endpoint: {$test['endpoint']} | HTTP: {$test['http_code']}</div>";
            if (!empty($test['errors'])) {
                echo "<div class='test-errors'>" . implode('<br>', $test['errors']) . "</div>";
            }
            echo "</div>";
        }
    }
    echo "</div>";
    
    // Summary
    ?>
    
    <div class="summary">
        <h2>Test Summary</h2>
        <div class="summary-stats">
            <div class="stat total">Total: <?php echo $results['total']; ?></div>
            <div class="stat passed">Passed: <?php echo $results['passed']; ?></div>
            <div class="stat failed">Failed: <?php echo $results['failed']; ?></div>
        </div>
        <p style="margin-top: 15px; color: #666;">
            Pass Rate: <?php echo $results['total'] > 0 ? round(($results['passed'] / $results['total']) * 100, 1) : 0; ?>%
        </p>
    </div>
    
    <p><a href="tickets.php">← Back to Tickets</a> | <a href="index.php">Dashboard</a></p>
</div>
</body>
</html>
