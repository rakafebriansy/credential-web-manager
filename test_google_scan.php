<?php
require_once 'config/init.php';

if (!isLoggedIn()) {
    redirect('login');
}

$page_title = 'Test Google Scan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Google Scan</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { padding: 40px; background: #f5f5f5; }
        .test-container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { margin-bottom: 20px; }
        .test-buttons { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .result-box { background: #f9f9f9; padding: 20px; border-radius: 8px; margin-top: 20px; }
        pre { background: #1e1e1e; color: #fff; padding: 15px; border-radius: 8px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="test-container">
        <h1><i class="fab fa-google"></i> Test Google Scan</h1>
        
        <div class="test-buttons">
            <button class="btn btn-primary" onclick="testGoogleScan()">
                <i class="fab fa-google"></i> Test Google Scan
            </button>
            <button class="btn btn-secondary" onclick="testFetch()">
                <i class="fas fa-server"></i> Test Fetch API
            </button>
            <button class="btn btn-warning" onclick="openGoogleDirect()">
                <i class="fas fa-external-link-alt"></i> Open Google Direct
            </button>
        </div>
        
        <div class="form-group">
            <label>Test URL:</label>
            <input type="text" id="testUrl" value="https://example.com" class="form-control" style="width:100%; padding:10px;">
        </div>
        
        <div class="result-box">
            <h3>Result:</h3>
            <pre id="resultOutput">Click a button to test...</pre>
        </div>
    </div>
    
    <script>
        function log(msg) {
            document.getElementById('resultOutput').textContent = typeof msg === 'object' ? JSON.stringify(msg, null, 2) : msg;
            console.log(msg);
        }
        
        async function testFetch() {
            const url = document.getElementById('testUrl').value;
            log('Testing fetch to scan_google...');
            
            try {
                const formData = new FormData();
                formData.append('id', 1);
                formData.append('url', url);
                
                const response = await fetch('scan_google', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                log(result);
            } catch (error) {
                log('Error: ' + error.message);
            }
        }
        
        function testGoogleScan() {
            const url = document.getElementById('testUrl').value;
            log('Google Scan function called with URL: ' + url);
            
            // Extract domain
            try {
                const urlObj = new URL(url);
                const domain = urlObj.hostname.replace('www.', '');
                
                const keywords = ['slot gacor', 'togel', 'judi online'];
                const links = keywords.map(k => ({
                    keyword: k,
                    url: `https://www.google.com/search?q=site:${domain}+${encodeURIComponent(k)}`
                }));
                
                log({
                    message: 'Google search links generated',
                    domain: domain,
                    links: links
                });
                
            } catch (e) {
                log('Error parsing URL: ' + e.message);
            }
        }
        
        function openGoogleDirect() {
            const url = document.getElementById('testUrl').value;
            try {
                const urlObj = new URL(url);
                const domain = urlObj.hostname.replace('www.', '');
                const googleUrl = `https://www.google.com/search?q=site:${domain}+slot+gacor`;
                
                log('Opening: ' + googleUrl);
                window.open(googleUrl, '_blank');
            } catch (e) {
                log('Error: ' + e.message);
            }
        }
    </script>
</body>
</html>
