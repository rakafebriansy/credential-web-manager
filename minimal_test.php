<?php
session_start();

// Simulate login
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

// Include database
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "<h2>Testing Create Ticket...</h2>";
    
    // Simulate POST data
    $_POST = [
        'action' => 'create',
        'website_id' => '1',
        'priority' => 'Medium',
        'category' => 'Test Category',
        'nama_pj' => 'Test PJ',
        'title' => 'Test Title',
        'description' => 'Test Description',
        'assigned_to' => 'Abdul Fazri'
    ];
    
    echo "<p>Simulated POST data:</p>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // Include and test the function
    include_once 'tickets_action.php';
    
    try {
        createTicket();
        
        if (isset($_SESSION['success'])) {
            echo "<p style='color: green;'>SUCCESS: " . $_SESSION['success'] . "</p>";
        }
        
        if (isset($_SESSION['error'])) {
            echo "<p style='color: red;'>ERROR: " . $_SESSION['error'] . "</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>EXCEPTION: " . $e->getMessage() . "</p>";
    }
    
    // Check last ticket
    $result = mysqli_query($conn, "SELECT * FROM tickets ORDER BY id DESC LIMIT 1");
    if ($result && $row = mysqli_fetch_assoc($result)) {
        echo "<h3>Last ticket created:</h3>";
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    }
    
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><title>Minimal Test</title></head>
<body>
    <h1>Minimal Create Ticket Test</h1>
    <form method="POST">
        <button type="submit">Run Test</button>
    </form>
</body>
</html>