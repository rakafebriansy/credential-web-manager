<?php
// Safe database check without output before headers
require_once 'config/database.php';

// Check database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if tickets table exists
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'tickets'");
$table_exists = mysqli_num_rows($check_table) > 0;

// Check websites table
$websites_count = 0;
$websites_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM websites");
if ($websites_result) {
    $row = mysqli_fetch_assoc($websites_result);
    $websites_count = $row['count'];
}

// Check users table
$users_count = 0;
$users_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
if ($users_result) {
    $row = mysqli_fetch_assoc($users_result);
    $users_count = $row['count'];
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Check</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .ok { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <h1>Database Status Check</h1>
    
    <div class="status <?php echo $conn ? 'ok' : 'error'; ?>">
        Database Connection: <?php echo $conn ? 'OK' : 'FAILED'; ?>
    </div>
    
    <div class="status <?php echo $table_exists ? 'ok' : 'error'; ?>">
        Tickets Table: <?php echo $table_exists ? 'EXISTS' : 'NOT FOUND'; ?>
    </div>
    
    <div class="status ok">
        Websites Count: <?php echo $websites_count; ?>
    </div>
    
    <div class="status ok">
        Users Count: <?php echo $users_count; ?>
    </div>
    
    <?php if ($table_exists): ?>
    <h2>Tickets Table Structure</h2>
    <table border="1" style="border-collapse: collapse; width: 100%;">
        <tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>
        <?php
        $result = mysqli_query($conn, "DESCRIBE tickets");
        while ($row = mysqli_fetch_assoc($result)):
        ?>
        <tr>
            <td><?php echo $row['Field']; ?></td>
            <td><?php echo $row['Type']; ?></td>
            <td><?php echo $row['Null']; ?></td>
            <td><?php echo $row['Key']; ?></td>
            <td><?php echo $row['Default']; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php endif; ?>
    
    <h2>Test Links</h2>
    <ul>
        <li><a href="test_simple_create.php">Test Simple Create (No Rich Editor)</a></li>
        <li><a href="debug_form_data.php">Debug Form Data</a></li>
        <li><a href="tickets.php">Back to Tickets</a></li>
    </ul>
</body>
</html>