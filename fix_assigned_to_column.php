<?php
require_once 'config/database.php';

echo "<h1>Fix assigned_to Column</h1>";

// Check current column type
$result = mysqli_query($conn, "DESCRIBE tickets assigned_to");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo "<p>Current column type: " . $row['Type'] . "</p>";
    
    // If it's INT, change it to VARCHAR
    if (strpos(strtolower($row['Type']), 'int') !== false) {
        echo "<p>Column is INT, changing to VARCHAR(100)...</p>";
        
        $alter_query = "ALTER TABLE tickets MODIFY COLUMN assigned_to VARCHAR(100) DEFAULT NULL";
        if (mysqli_query($conn, $alter_query)) {
            echo "<p style='color: green;'>SUCCESS: Column changed to VARCHAR(100)</p>";
        } else {
            echo "<p style='color: red;'>ERROR: " . mysqli_error($conn) . "</p>";
        }
    } else {
        echo "<p style='color: green;'>Column is already VARCHAR, no change needed.</p>";
    }
} else {
    echo "<p style='color: red;'>Could not describe column: " . mysqli_error($conn) . "</p>";
}

// Verify the change
echo "<h2>Verification</h2>";
$result = mysqli_query($conn, "DESCRIBE tickets assigned_to");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    echo "<p>Column type after fix: " . $row['Type'] . "</p>";
}

echo "<p><a href='tickets.php'>Back to Tickets</a></p>";
?>