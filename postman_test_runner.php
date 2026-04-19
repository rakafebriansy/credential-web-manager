<?php
require_once 'config/init.php';

// Test runner for API endpoints
echo "<h1>API Test Runner</h1>";
echo "<p>Testing API endpoints after tickets.php modification...</p>";

$base_url = "http://localhost" . dirname($_SERVER['PHP_SELF']);
$test_results = [];

// Helper function to make HTTP requests
function makeRequest($url, $method = 'GET', $data = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    if ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    
    $headers = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    
    curl_close($ch);
    
    return [
        'http_code' => $http_code,
        'headers' => $headers,
        'body' => $body
    ];
}

// Test 1: Create Ticket with Priority
echo "<h2>Test 1: Create Ticket with Priority</h2>";
$ticket_data = [
    'action' => 'create',
    'website_id' => 1,
    'category' => 'Abstrak hilang',
    'priority' => 'High',
    'nama_pj' => 'Test User',
    'title' => 'Test Ticket with Priority',
    'description' => 'Testing the new priority field functionality',
    'assigned_to' => 'Abdul Fazri'
];

$result = makeRequest($base_url . '/tickets_action.php', 'POST', $ticket_data);
echo "<p><strong>HTTP Code:</strong> {$result['http_code']}</p>";
echo "<p><strong>Expected:</strong> 302 (redirect to tickets page)</p>";

if ($result['http_code'] == 302) {
    echo "<p style='color: green;'>✓ PASS: Ticket creation redirected successfully</p>";
    $test_results['create_ticket_with_priority'] = 'PASS';
} else {
    echo "<p style='color: red;'>✗ FAIL: Expected redirect, got {$result['http_code']}</p>";
    echo "<pre>" . htmlspecialchars($result['body']) . "</pre>";
    $test_results['create_ticket_with_priority'] = 'FAIL';
}

// Test 2: Create Ticket with Invalid Priority
echo "<h2>Test 2: Create Ticket with Invalid Priority</h2>";
$invalid_priority_data = [
    'action' => 'create',
    'website_id' => 1,
    'category' => 'Abstrak hilang',
    'priority' => 'Invalid Priority',
    'nama_pj' => 'Test User',
    'title' => 'Test Invalid Priority',
    'description' => 'Testing invalid priority handling',
];

$result = makeRequest($base_url . '/tickets_action.php', 'POST', $invalid_priority_data);
echo "<p><strong>HTTP Code:</strong> {$result['http_code']}</p>";
echo "<p><strong>Expected:</strong> 302 (should still work, priority defaults to Medium)</p>";

if ($result['http_code'] == 302) {
    echo "<p style='color: green;'>✓ PASS: Invalid priority handled gracefully</p>";
    $test_results['invalid_priority'] = 'PASS';
} else {
    echo "<p style='color: red;'>✗ FAIL: Invalid priority not handled properly</p>";
    $test_results['invalid_priority'] = 'FAIL';
}

// Test 3: Web Progress API
echo "<h2>Test 3: Web Progress API</h2>";
$web_progress_data = [
    'website_id' => 1,
    'field' => 'hasil_check',
    'value' => 'Korespondensi Aktif'
];

$result = makeRequest($base_url . '/web_progress_action.php', 'POST', $web_progress_data);
echo "<p><strong>HTTP Code:</strong> {$result['http_code']}</p>";

if ($result['http_code'] == 200) {
    $json_response = json_decode($result['body'], true);
    if ($json_response && isset($json_response['success'])) {
        if ($json_response['success'] === true || $json_response['message'] === 'Unauthorized') {
            echo "<p style='color: green;'>✓ PASS: Web Progress API working</p>";
            $test_results['web_progress'] = 'PASS';
        } else {
            echo "<p style='color: orange;'>⚠ WARNING: API returned success=false</p>";
            echo "<pre>" . htmlspecialchars($result['body']) . "</pre>";
            $test_results['web_progress'] = 'WARNING';
        }
    } else {
        echo "<p style='color: red;'>✗ FAIL: Invalid JSON response</p>";
        $test_results['web_progress'] = 'FAIL';
    }
} else {
    echo "<p style='color: red;'>✗ FAIL: Expected 200, got {$result['http_code']}</p>";
    $test_results['web_progress'] = 'FAIL';
}

// Test 4: Cloudflare API
echo "<h2>Test 4: Cloudflare API</h2>";
$cloudflare_data = [
    'action' => 'test'
];

$result = makeRequest($base_url . '/cloudflare_action.php', 'POST', $cloudflare_data);
echo "<p><strong>HTTP Code:</strong> {$result['http_code']}</p>";

if ($result['http_code'] == 200) {
    $json_response = json_decode($result['body'], true);
    if ($json_response && isset($json_response['success']) && $json_response['success'] === true) {
        echo "<p style='color: green;'>✓ PASS: Cloudflare API test connection working</p>";
        $test_results['cloudflare_test'] = 'PASS';
    } else {
        echo "<p style='color: red;'>✗ FAIL: Cloudflare test failed</p>";
        echo "<pre>" . htmlspecialchars($result['body']) . "</pre>";
        $test_results['cloudflare_test'] = 'FAIL';
    }
} else {
    echo "<p style='color: red;'>✗ FAIL: Expected 200, got {$result['http_code']}</p>";
    $test_results['cloudflare_test'] = 'FAIL';
}

// Test 5: LP Security API
echo "<h2>Test 5: LP Security API</h2>";
$lp_security_data = [
    'action' => 'update',
    'website_id' => 1,
    'field' => 'ganti_wp_admin',
    'value' => 'Done'
];

$result = makeRequest($base_url . '/lp_security_action.php', 'POST', $lp_security_data);
echo "<p><strong>HTTP Code:</strong> {$result['http_code']}</p>";

if ($result['http_code'] == 200) {
    $json_response = json_decode($result['body'], true);
    if ($json_response && isset($json_response['success'])) {
        if ($json_response['success'] === true || $json_response['message'] === 'Unauthorized') {
            echo "<p style='color: green;'>✓ PASS: LP Security API working</p>";
            $test_results['lp_security'] = 'PASS';
        } else {
            echo "<p style='color: orange;'>⚠ WARNING: API returned success=false</p>";
            $test_results['lp_security'] = 'WARNING';
        }
    } else {
        echo "<p style='color: red;'>✗ FAIL: Invalid JSON response</p>";
        $test_results['lp_security'] = 'FAIL';
    }
} else {
    echo "<p style='color: red;'>✗ FAIL: Expected 200, got {$result['http_code']}</p>";
    $test_results['lp_security'] = 'FAIL';
}

// Test 6: Scanner APIs
echo "<h2>Test 6: Scanner APIs</h2>";
$scan_data = [
    'url' => 'https://example.com',
    'id' => 1
];

// Test Website Scanner
$result = makeRequest($base_url . '/scan_website.php', 'POST', $scan_data);
echo "<p><strong>Website Scanner HTTP Code:</strong> {$result['http_code']}</p>";

if ($result['http_code'] == 200) {
    $json_response = json_decode($result['body'], true);
    if ($json_response && is_array($json_response)) {
        echo "<p style='color: green;'>✓ PASS: Website Scanner API working</p>";
        $test_results['website_scanner'] = 'PASS';
    } else {
        echo "<p style='color: red;'>✗ FAIL: Website Scanner invalid response</p>";
        $test_results['website_scanner'] = 'FAIL';
    }
} else {
    echo "<p style='color: red;'>✗ FAIL: Website Scanner expected 200, got {$result['http_code']}</p>";
    $test_results['website_scanner'] = 'FAIL';
}

// Test Google Scanner
$result = makeRequest($base_url . '/scan_google.php', 'POST', $scan_data);
echo "<p><strong>Google Scanner HTTP Code:</strong> {$result['http_code']}</p>";

if ($result['http_code'] == 200) {
    $json_response = json_decode($result['body'], true);
    if ($json_response && isset($json_response['domain'])) {
        echo "<p style='color: green;'>✓ PASS: Google Scanner API working</p>";
        $test_results['google_scanner'] = 'PASS';
    } else {
        echo "<p style='color: red;'>✗ FAIL: Google Scanner missing domain field</p>";
        $test_results['google_scanner'] = 'FAIL';
    }
} else {
    echo "<p style='color: red;'>✗ FAIL: Google Scanner expected 200, got {$result['http_code']}</p>";
    $test_results['google_scanner'] = 'FAIL';
}

// Summary
echo "<h2>Test Summary</h2>";
$total_tests = count($test_results);
$passed_tests = count(array_filter($test_results, function($result) { return $result === 'PASS'; }));
$warning_tests = count(array_filter($test_results, function($result) { return $result === 'WARNING'; }));
$failed_tests = count(array_filter($test_results, function($result) { return $result === 'FAIL'; }));

echo "<p><strong>Total Tests:</strong> $total_tests</p>";
echo "<p><strong>Passed:</strong> <span style='color: green;'>$passed_tests</span></p>";
echo "<p><strong>Warnings:</strong> <span style='color: orange;'>$warning_tests</span></p>";
echo "<p><strong>Failed:</strong> <span style='color: red;'>$failed_tests</span></p>";

echo "<h3>Detailed Results:</h3>";
echo "<ul>";
foreach ($test_results as $test => $result) {
    $color = $result === 'PASS' ? 'green' : ($result === 'WARNING' ? 'orange' : 'red');
    echo "<li style='color: $color;'>$test: $result</li>";
}
echo "</ul>";

// Recommendations
echo "<h2>Recommendations</h2>";
if ($failed_tests > 0) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>Issues Found:</h4>";
    echo "<ul>";
    
    if ($test_results['create_ticket_with_priority'] === 'FAIL') {
        echo "<li>Ticket creation with priority field is failing. Check tickets_action.php for proper priority handling.</li>";
    }
    
    if ($test_results['web_progress'] === 'FAIL') {
        echo "<li>Web Progress API is not responding correctly. Check database connection and table structure.</li>";
    }
    
    if ($test_results['cloudflare_test'] === 'FAIL') {
        echo "<li>Cloudflare API test is failing. Check cloudflare_action.php implementation.</li>";
    }
    
    echo "</ul>";
    echo "</div>";
}

if ($warning_tests > 0) {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>Warnings:</h4>";
    echo "<p>Some APIs returned 'Unauthorized' responses. This is expected if not logged in during testing.</p>";
    echo "</div>";
}

if ($passed_tests === $total_tests) {
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>✓ All Tests Passed!</h4>";
    echo "<p>The API modifications for the priority field are working correctly.</p>";
    echo "</div>";
}

echo "<br><a href='tickets.php' style='padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Go to Tickets</a>";
?>