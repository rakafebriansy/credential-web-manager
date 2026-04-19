<?php
require_once 'config/database.php';

echo "<h2>Database Update Script</h2>";
echo "<p>Updating database structure...</p>";

// Check if columns exist
$check_query = "SHOW COLUMNS FROM websites LIKE 'jenis_web'";
$result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($result) == 0) {
    echo "<p>Adding missing columns...</p>";
    
    // Add jenis_web column
    $alter1 = "ALTER TABLE websites ADD COLUMN jenis_web VARCHAR(100) DEFAULT NULL AFTER link_url";
    if (mysqli_query($conn, $alter1)) {
        echo "<p style='color:green;'>✓ Added column: jenis_web</p>";
    } else {
        echo "<p style='color:red;'>✗ Error adding jenis_web: " . mysqli_error($conn) . "</p>";
    }
    
    // Add letak_server column
    $alter2 = "ALTER TABLE websites ADD COLUMN letak_server VARCHAR(100) DEFAULT NULL AFTER jenis_web";
    if (mysqli_query($conn, $alter2)) {
        echo "<p style='color:green;'>✓ Added column: letak_server</p>";
    } else {
        echo "<p style='color:red;'>✗ Error adding letak_server: " . mysqli_error($conn) . "</p>";
    }
    
    // Add pic column
    $alter3 = "ALTER TABLE websites ADD COLUMN pic VARCHAR(100) DEFAULT NULL AFTER letak_server";
    if (mysqli_query($conn, $alter3)) {
        echo "<p style='color:green;'>✓ Added column: pic</p>";
    } else {
        echo "<p style='color:red;'>✗ Error adding pic: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color:blue;'>ℹ Columns already exist</p>";
}

// Increase column sizes
echo "<p>Increasing column sizes...</p>";

$modify1 = "ALTER TABLE websites MODIFY COLUMN holding VARCHAR(255) NOT NULL";
if (mysqli_query($conn, $modify1)) {
    echo "<p style='color:green;'>✓ Modified column: holding (VARCHAR 255)</p>";
} else {
    echo "<p style='color:red;'>✗ Error modifying holding: " . mysqli_error($conn) . "</p>";
}

$modify2 = "ALTER TABLE websites MODIFY COLUMN link_url VARCHAR(500) NOT NULL";
if (mysqli_query($conn, $modify2)) {
    echo "<p style='color:green;'>✓ Modified column: link_url (VARCHAR 500)</p>";
} else {
    echo "<p style='color:red;'>✗ Error modifying link_url: " . mysqli_error($conn) . "</p>";
}

// Show final structure
echo "<h3>Final Table Structure:</h3>";
$desc_query = "DESCRIBE websites";
$desc_result = mysqli_query($conn, $desc_query);

echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = mysqli_fetch_assoc($desc_result)) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3 style='color:green;'>✓ Database update completed!</h3>";
echo "<p><a href='websites'>Go to Websites Page</a></p>";

mysqli_close($conn);
?>
