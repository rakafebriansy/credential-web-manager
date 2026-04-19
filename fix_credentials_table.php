<?php
require_once 'config/init.php';

echo "<h2>Fix Website Credentials Table</h2>";

// Drop the old table and recreate without nama_website
$drop_query = "DROP TABLE IF EXISTS website_credentials";
if (mysqli_query($conn, $drop_query)) {
    echo "✓ Old table dropped<br>";
} else {
    echo "✗ Error dropping table: " . mysqli_error($conn) . "<br>";
}

// Create new table without nama_website
$create_query = "CREATE TABLE website_credentials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    website_id INT NOT NULL,
    holding VARCHAR(50) NOT NULL,
    username_admin VARCHAR(100) NOT NULL,
    password_admin TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_website_id (website_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($conn, $create_query)) {
    echo "✓ New table created successfully!<br>";
    echo "<br><strong>Table structure:</strong><br>";
    echo "- id (Primary Key)<br>";
    echo "- website_id<br>";
    echo "- holding<br>";
    echo "- username_admin<br>";
    echo "- password_admin<br>";
    echo "- created_at<br>";
    echo "- updated_at<br>";
    echo "<br><a href='website_credentials.php'>Go to Website Credentials</a>";
} else {
    echo "✗ Error creating table: " . mysqli_error($conn) . "<br>";
}

mysqli_close($conn);
?>
