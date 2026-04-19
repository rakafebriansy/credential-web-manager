<?php
require_once 'config/database.php';

echo "<h1>Fix Database and Test</h1>";

// Fix assigned_to column
echo "<h2>1. Fix assigned_to Column</h2>";
$result = mysqli_query($conn, "DESCRIBE tickets assigned_to");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo "<p>Current: " . $row['Type'] . "</p>";
    
    if (strpos(strtolower($row['Type']), 'int') !== false) {
        $alter = "ALTER TABLE tickets MODIFY COLUMN assigned_to VARCHAR(100) DEFAULT NULL";
        if (mysqli_query($conn, $alter)) {
            echo "<p style='color: green;'>Changed to VARCHAR(100)</p>";
        } else {
            echo "<p style='color: red;'>Error: " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p style='color: green;'>Already VARCHAR</p>";
    }
}

// Test create
echo "<h2>2. Test Create Ticket</h2>";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    session_start();
    $_SESSION['user_id'] = 1;
    
    $website = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM websites LIMIT 1"));
    
    if ($website) {
        $ticket_number = "TKT-" . date('Ym') . "-" . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $query = "INSERT INTO tickets (ticket_number, website_id, category, nama_pj, title, description, priority, status, created_by, assigned_to) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, 'Open', ?, ?)";
        
        $stmt = mysqli_prepare($conn, $query);
        $wid = $website['id'];
        $cat = 'Test';
        $pj = 'Test PJ';
        $title = 'Test ' . date('H:i:s');
        $desc = 'Test description';
        $pri = 'Medium';
        $uid = 1;
        $assign = 'Abdul Fazri';
        
        mysqli_stmt_bind_param($stmt, "sisssssis", $ticket_number, $wid, $cat, $pj, $title, $desc, $pri, $uid, $assign);
        
        if (mysqli_stmt_execute($stmt)) {
            echo "<p style='color: green;'>SUCCESS! Ticket: $ticket_number</p>";
        } else {
            echo "<p style='color: red;'>Error: " . mysqli_stmt_error($stmt) . "</p>";
        }
    }
}
?>
<form method="POST">
    <button type="submit">Test Create</button>
</form>
<p><a href="tickets.php">Go to Tickets</a></p>