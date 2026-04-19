<?php
require_once 'config/init.php';

echo "<h2>Fix Cloudflare CDN Table</h2>";

// Drop and recreate the table with correct ENUM values
$drop_table = "DROP TABLE IF EXISTS cloudflare_cdn";
if (mysqli_query($conn, $drop_table)) {
    echo "<p style='color: green;'>✓ Old table dropped</p>";
} else {
    echo "<p style='color: red;'>✗ Error dropping table: " . mysqli_error($conn) . "</p>";
}

// Create table with correct ENUM values
$create_table = "CREATE TABLE cloudflare_cdn (
    id INT AUTO_INCREMENT PRIMARY KEY,
    website_id INT NOT NULL,
    cdn_status VARCHAR(50) DEFAULT 'Cloudflare',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_website (website_id),
    FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($conn, $create_table)) {
    echo "<p style='color: green;'>✓ New table created successfully!</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating table: " . mysqli_error($conn) . "</p>";
}

// Show table structure
echo "<h3>Table Structure:</h3>";
$describe = mysqli_query($conn, "DESCRIBE cloudflare_cdn");
if ($describe) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = mysqli_fetch_assoc($describe)) {
        echo "<tr>";
        echo "<td style='padding: 5px;'>" . $row['Field'] . "</td>";
        echo "<td style='padding: 5px;'>" . $row['Type'] . "</td>";
        echo "<td style='padding: 5px;'>" . $row['Null'] . "</td>";
        echo "<td style='padding: 5px;'>" . $row['Key'] . "</td>";
        echo "<td style='padding: 5px;'>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<br><a href='cloudflare.php' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Go to Cloudflare Management</a>";
?>