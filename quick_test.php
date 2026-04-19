<?php
require_once 'config/init.php';

if (!isLoggedIn()) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
}

echo "<h1>Quick Test - Create Ticket</h1>";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "<h2>Processing...</h2>";
    
    // Show what we received
    echo "<h3>POST Data:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // Try to create ticket
    include_once 'tickets_action.php';
    
    try {
        createTicket();
        
        if (isset($_SESSION['success'])) {
            echo "<p style='color: green; font-weight: bold;'>✓ " . $_SESSION['success'] . "</p>";
            unset($_SESSION['success']);
        }
        
        if (isset($_SESSION['error'])) {
            echo "<p style='color: red; font-weight: bold;'>✗ " . $_SESSION['error'] . "</p>";
            unset($_SESSION['error']);
        }
        
        // Check if ticket was created
        $check = mysqli_query($conn, "SELECT * FROM tickets ORDER BY id DESC LIMIT 1");
        if ($check && $ticket = mysqli_fetch_assoc($check)) {
            echo "<h3>Last Ticket Created:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            foreach ($ticket as $key => $value) {
                echo "<tr><td>$key</td><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
            }
            echo "</table>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
    }
    
    echo "<p><a href='tickets.php'>→ Go to Tickets Page</a></p>";
    exit;
}

// Get first website for testing
$website_query = "SELECT * FROM websites LIMIT 1";
$website_result = mysqli_query($conn, $website_query);
$website = mysqli_fetch_assoc($website_result);

if (!$website) {
    echo "<p style='color: red;'>No websites found! Please add a website first.</p>";
    exit;
}
?>

<form method="POST" style="max-width: 500px;">
    <input type="hidden" name="action" value="create">
    
    <p><strong>Website:</strong> <?php echo htmlspecialchars($website['holding'] ?? 'Test Website'); ?></p>
    <input type="hidden" name="website_id" value="<?php echo $website['id']; ?>">
    
    <p><strong>Priority:</strong></p>
    <select name="priority" required style="width: 100%; padding: 8px;">
        <option value="Medium" selected>Medium</option>
        <option value="High">High</option>
        <option value="Low">Low</option>
    </select>
    
    <p><strong>Category:</strong></p>
    <input type="text" name="category" value="Test Category" required style="width: 100%; padding: 8px;">
    
    <p><strong>Nama PJ:</strong></p>
    <input type="text" name="nama_pj" value="Test PJ" required style="width: 100%; padding: 8px;">
    
    <p><strong>Title:</strong></p>
    <input type="text" name="title" value="Quick Test Ticket - <?php echo date('H:i:s'); ?>" required style="width: 100%; padding: 8px;">
    
    <p><strong>Description:</strong></p>
    <textarea name="description" required style="width: 100%; padding: 8px; height: 80px;">This is a quick test ticket created at <?php echo date('Y-m-d H:i:s'); ?></textarea>
    
    <p><strong>Assigned To:</strong></p>
    <select name="assigned_to" style="width: 100%; padding: 8px;">
        <option value="">-- Unassigned --</option>
        <option value="Abdul Fazri" selected>Abdul Fazri</option>
    </select>
    
    <p><button type="submit" style="background: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer;">Create Test Ticket</button></p>
</form>

<p><a href="tickets.php">← Back to Tickets</a></p>