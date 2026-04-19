<?php
require_once 'config/init.php';

// List of holdings
$holdings = [
    'RIN', 'GP', 'PI', 'IJL', 'AL-MAKKI', 'RIVIERA', 'TADCENT', 
    'EDC', 'LPK', 'GB', 'STAIKU', 'POLTEK', 'SCI', 'PUBLIKASIKU',
    'EBISKRAF', 'MSDM', 'LPK-STC', 'FOUNDATION', 'IB-FOUNDATION', 'SBS'
];

$password = 'holdingku12345';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// First, add 'holding' column to users table if not exists
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'holding'");
if (mysqli_num_rows($check_column) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN holding VARCHAR(50) DEFAULT NULL AFTER role");
    echo "<p>✅ Added 'holding' column to users table</p>";
}

// Check if role column exists
$check_role = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
if (mysqli_num_rows($check_role) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user' AFTER full_name");
    echo "<p>✅ Added 'role' column to users table</p>";
}

echo "<h2>🏢 Setup Holding Users</h2>";
echo "<p>Password untuk semua user: <strong>$password</strong></p>";
echo "<hr>";

$created = 0;
$exists = 0;

foreach ($holdings as $holding) {
    // Create username from holding (lowercase, replace special chars)
    $username = strtolower(str_replace(['-', ' '], '_', $holding));
    $email = $username . '@holding.local';
    $full_name = 'User ' . $holding;
    $role = 'user_holding';
    
    // Check if user already exists
    $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    
    if (mysqli_num_rows($check) > 0) {
        // Update existing user
        $stmt = $conn->prepare("UPDATE users SET password = ?, role = ?, holding = ? WHERE username = ?");
        $stmt->bind_param("ssss", $hashed_password, $role, $holding, $username);
        $stmt->execute();
        echo "<p>🔄 Updated: <strong>$username</strong> (Holding: $holding)</p>";
        $exists++;
    } else {
        // Create new user
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, full_name, role, holding) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $username, $email, $hashed_password, $full_name, $role, $holding);
        
        if ($stmt->execute()) {
            echo "<p>✅ Created: <strong>$username</strong> (Holding: $holding)</p>";
            $created++;
        } else {
            echo "<p>❌ Failed: $username - " . $conn->error . "</p>";
        }
    }
}

echo "<hr>";
echo "<h3>📊 Summary</h3>";
echo "<p>New users created: <strong>$created</strong></p>";
echo "<p>Existing users updated: <strong>$exists</strong></p>";

echo "<h3>📋 User List</h3>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #6366f1; color: white;'><th>No</th><th>Username</th><th>Holding</th><th>Role</th><th>Password</th></tr>";

$no = 1;
foreach ($holdings as $holding) {
    $username = strtolower(str_replace(['-', ' '], '_', $holding));
    echo "<tr>";
    echo "<td>$no</td>";
    echo "<td><strong>$username</strong></td>";
    echo "<td>$holding</td>";
    echo "<td>user_holding</td>";
    echo "<td>$password</td>";
    echo "</tr>";
    $no++;
}
echo "</table>";

echo "<br><p><a href='login.php' style='padding: 10px 20px; background: #6366f1; color: white; text-decoration: none; border-radius: 8px;'>🔐 Go to Login</a></p>";
?>
