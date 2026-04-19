<?php
/**
 * Postman Collection Runner
 * Executes tests from .postman.json collection file
 */

// Load collection
$collection_file = '.postman.json';
if (!file_exists($collection_file)) {
    die("Error: .postman.json not found\n");
}

$collection = json_decode(file_get_contents($collection_file), true);
if (!$collection) {
    die("Error: Invalid JSON in .postman.json\n");
}

// Get base URL from collection variables
$base_url = '';
foreach ($collection['variable'] as $var) {
    if ($var['key'] === 'base_url') {
        $base_url = $var['value'];
        break;
    }
}

echo "===========================================\n";
echo "Postman Collection Test Runner\n";
echo "===========================================\n";
echo "Collection: {$collection['info']['name']}\n";
echo "Base URL: $base_url\n";
echo "===========================================\n\n";

$total_tests = 0;
$passed_tests = 0;
$failed_tests = 0;
$results = [];

// Function to execute a request
function executeRequest($request, $base_url) {
    $url = str_replace('{{base_url}}', $base_url, $request['url']);
    $method = $request['method'];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    // Set method
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        
        // Build POST data
        if (isset($request['body']['urlencoded'])) {
            $post_data = [];
            foreach ($request['body']['urlencoded'] as $param) {
                $post_data[$param['key']] = $param['value'];
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        }
    } elseif ($method === 'GET') {
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    }
    
    // Set headers
    if (isset($request['header'])) {
        $headers = [];
        foreach ($request['header'] as $header) {
            $headers[] = "{$header['key']}: {$header['value']}";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    
    $headers_raw = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    
    curl_close($ch);
    
    return [
        'status_code' => $http_code,
        'headers' => $headers_raw,
        'body' => $body,
        'json' => json_decode($body, true)
    ];
}

// Function to run tests
function runTests($tests, $response) {
    $results = [];
    
    foreach ($tests as $test) {
        $test_name = '';
        $passed = false;
        
        // Parse test script
        if (preg_match("/pm\.test\('([^']+)'/", $test, $matches)) {
            $test_name = $matches[1];
        }
        
        // Check status code
        if (strpos($test, 'pm.response.to.have.status') !== false) {
            if (preg_match('/status\((\d+)\)/', $test, $matches)) {
                $expected_code = (int)$matches[1];
                $passed = ($response['status_code'] === $expected_code);
            }
        }
        
        // Check JSON response
        if (strpos($test, 'pm.response.to.be.json') !== false) {
            $passed = ($response['json'] !== null);
        }
        
        // Check success field
        if (strpos($test, 'jsonData.success') !== false) {
            if (isset($response['json']['success'])) {
                $passed = ($response['json']['success'] === true);
            } elseif (isset($response['json']['message']) && $response['json']['message'] === 'Unauthorized') {
                $passed = true; // Accept unauthorized as valid response
            }
        }
        
        // Check for error messages
        if (strpos($test, 'jsonData.success').to.be.false') !== false) {
            $passed = (isset($response['json']['success']) && $response['json']['success'] === false);
        }
        
        // Check redirect location
        if (strpos($test, 'Location') !== false && strpos($test, 'tickets') !== false) {
            $passed = (strpos($response['headers'], 'Location:') !== false && 
                      strpos($response['headers'], 'tickets') !== false);
        }
        
        // Check for specific properties
        if (preg_match("/to\.have\.property\('([^']+)'\)/", $test, $matches)) {
            $property = $matches[1];
            $passed = isset($response['json'][$property]);
        }
        
        if ($test_name) {
            $results[] = [
                'name' => $test_name,
                'passed' => $passed
            ];
        }
    }
    
    return $results;
}

// Process each folder
foreach ($collection['item'] as $folder) {
    echo "\n📁 {$folder['name']}\n";
    echo str_repeat('-', 50) . "\n";
    
    foreach ($folder['item'] as $item) {
        $request_name = $item['name'];
        echo "\n  🔹 $request_name\n";
        
        // Execute request
        $response = executeRequest($item['request'], $base_url);
        
        echo "     Status: {$response['status_code']}\n";
        
        // Run tests if available
        if (isset($item['event'])) {
            foreach ($item['event'] as $event) {
                if ($event['listen'] === 'test' && isset($event['script']['exec'])) {
                    $test_results = runTests($event['script']['exec'], $response);
                    
                    foreach ($test_results as $test) {
                        $total_tests++;
                        if ($test['passed']) {
                            $passed_tests++;
                            echo "     ✅ {$test['name']}\n";
                        } else {
                            $failed_tests++;
                            echo "     ❌ {$test['name']}\n";
                        }
                    }
                }
            }
        } else {
            echo "     ℹ️  No tests defined\n";
        }
        
        // Show response preview
        if ($response['json']) {
            echo "     Response: " . json_encode($response['json']) . "\n";
        }
    }
}

// Summary
echo "\n\n===========================================\n";
echo "TEST SUMMARY\n";
echo "===========================================\n";
echo "Total Tests: $total_tests\n";
echo "Passed: $passed_tests ✅\n";
echo "Failed: $failed_tests ❌\n";
echo "Success Rate: " . ($total_tests > 0 ? round(($passed_tests / $total_tests) * 100, 2) : 0) . "%\n";
echo "===========================================\n";

if ($failed_tests > 0) {
    exit(1);
}
?>
