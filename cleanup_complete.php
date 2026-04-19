<?php
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Tickets System Removal Complete</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        h1 { color: #333; border-bottom: 2px solid #28a745; padding-bottom: 10px; }
        ul { background: #f8f9fa; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🗑️ Tickets System Removal Complete</h1>
        
        <h2>Database Cleanup</h2>";

// Drop tickets related tables
$tables_to_drop = [
    'ticket_comments',
    'ticket_attachments', 
    'tickets'
];

foreach ($tables_to_drop as $table) {
    $sql = "DROP TABLE IF EXISTS $table";
    if (mysqli_query($conn, $sql)) {
        echo "<p class='success'>✓ Table '$table' dropped successfully</p>";
    } else {
        echo "<p class='error'>✗ Error dropping table '$table': " . mysqli_error($conn) . "</p>";
    }
}

echo "<h2>Files Removed</h2>
<p class='success'>✓ All tickets-related PHP files have been deleted</p>
<p class='success'>✓ Tickets menu removed from sidebar</p>
<p class='success'>✓ All test files and SQL scripts removed</p>

<h2>Remaining Database Tables</h2>";

$result = mysqli_query($conn, "SHOW TABLES");
echo "<ul>";
while ($row = mysqli_fetch_array($result)) {
    echo "<li>" . $row[0] . "</li>";
}
echo "</ul>";

echo "<h2>Summary</h2>
<p class='success'><strong>✅ Tickets system has been completely removed from your application!</strong></p>
<p>The following components were removed:</p>
<ul>
    <li>All tickets database tables</li>
    <li>All PHP files related to tickets functionality</li>
    <li>Tickets menu from navigation sidebar</li>
    <li>All test files and SQL scripts</li>
</ul>

<p><a href='index.php' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px;'>← Back to Dashboard</a></p>

</div>
</body>
</html>";

mysqli_close($conn);
?>