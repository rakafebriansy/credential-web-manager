<?php
require_once 'config/init.php';

echo "<h2>Fixing websites table structure...</h2>";

// Fix all columns that might have ENUM or restricted types
$fixes = [
    "ALTER TABLE websites MODIFY COLUMN holding VARCHAR(255) NOT NULL",
    "ALTER TABLE websites MODIFY COLUMN jenis_web VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE websites MODIFY COLUMN letak_server VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE websites MODIFY COLUMN pic VARCHAR(100) DEFAULT NULL"
];

foreach ($fixes as $query) {
    if (mysqli_query($conn, $query)) {
        echo "<p style='color:green;'>✓ OK: $query</p>";
    } else {
        echo "<p style='color:red;'>✗ Error: " . mysqli_error($conn) . "</p>";
    }
}

// Show current structure
echo "<h3>Struktur tabel websites saat ini:</h3>";
$result = mysqli_query($conn, "DESCRIBE websites");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><a href='websites.php'>Kembali ke Websites</a>";
?>
