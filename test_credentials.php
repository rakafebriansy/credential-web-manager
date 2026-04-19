<?php
require_once 'config/init.php';

echo "<h2>Testing Website Credentials</h2>";

// Test 1: Check if logged in
echo "<h3>1. Login Status</h3>";
if (isLoggedIn()) {
    echo "✓ User is logged in<br>";
} else {
    echo "✗ User is NOT logged in<br>";
    exit;
}

// Test 2: Check session access
echo "<h3>2. Session Access</h3>";
$_SESSION['credentials_access'] = true;
echo "✓ Session access granted<br>";

// Test 3: Check if table exists
echo "<h3>3. Table Check</h3>";
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'website_credentials'");
if (mysqli_num_rows($check_table) == 0) {
    echo "✗ Table does not exist. Creating...<br>";
    $create_table = "CREATE TABLE IF NOT EXISTS website_credentials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        website_id INT NOT NULL,
        holding VARCHAR(50) NOT NULL,
        nama_website VARCHAR(255) NOT NULL,
        username_admin VARCHAR(100) NOT NULL,
        password_admin TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_website_id (website_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (mysqli_query($conn, $create_table)) {
        echo "✓ Table created successfully<br>";
    } else {
        echo "✗ Error creating table: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "✓ Table exists<br>";
}

// Test 4: Check websites table
echo "<h3>4. Websites Data</h3>";
$websites_query = "SELECT id, holding, link_url FROM websites ORDER BY holding ASC LIMIT 5";
$websites_result = mysqli_query($conn, $websites_query);
if ($websites_result) {
    $count = mysqli_num_rows($websites_result);
    echo "✓ Found $count websites<br>";
    while ($web = mysqli_fetch_assoc($websites_result)) {
        echo "- ID: {$web['id']}, Holding: {$web['holding']}, URL: {$web['link_url']}<br>";
    }
} else {
    echo "✗ Error: " . mysqli_error($conn) . "<br>";
}

// Test 5: Try to insert test data
echo "<h3>5. Insert Test</h3>";
if (isset($_POST['test_insert'])) {
    $website_id = 1; // Use first website
    $holding = "TEST";
    $nama_website = "Test Website";
    $username_admin = "testuser";
    $password_admin = "testpass123";
    
    $query = "INSERT INTO website_credentials (website_id, holding, nama_website, username_admin, password_admin) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'issss', $website_id, $holding, $nama_website, $username_admin, $password_admin);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "✓ Test data inserted successfully!<br>";
        } else {
            echo "✗ Error inserting: " . mysqli_stmt_error($stmt) . "<br>";
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "✗ Prepare error: " . mysqli_error($conn) . "<br>";
    }
}

echo '<form method="POST"><button type="submit" name="test_insert">Test Insert Data</button></form>';

// Test 6: Show existing data
echo "<h3>6. Existing Credentials</h3>";
$cred_query = "SELECT * FROM website_credentials";
$cred_result = mysqli_query($conn, $cred_query);
if ($cred_result) {
    $count = mysqli_num_rows($cred_result);
    echo "✓ Found $count credentials<br>";
    while ($cred = mysqli_fetch_assoc($cred_result)) {
        echo "- ID: {$cred['id']}, Holding: {$cred['holding']}, Website: {$cred['nama_website']}<br>";
    }
} else {
    echo "✗ Error: " . mysqli_error($conn) . "<br>";
}

echo "<br><a href='website_credentials'>Go to Website Credentials</a>";
?>
