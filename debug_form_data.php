<?php
// Simple debug to see what's being sent
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "<h2>POST Data Received:</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    echo "<h2>Action Value:</h2>";
    $action = $_POST['action'] ?? 'NOT SET';
    echo "Action: '$action'<br>";
    echo "Action length: " . strlen($action) . "<br>";
    echo "Action type: " . gettype($action) . "<br>";
    
    if (empty($action)) {
        echo "<p style='color: red;'>ACTION IS EMPTY!</p>";
    }
    
    echo "<h2>All Required Fields:</h2>";
    $required = ['action', 'website_id', 'priority', 'category', 'nama_pj', 'title', 'description'];
    foreach ($required as $field) {
        $value = $_POST[$field] ?? 'NOT SET';
        $status = empty($value) ? 'EMPTY' : 'OK';
        echo "$field: $status - '$value'<br>";
    }
    
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Form Data</title>
</head>
<body>
    <h1>Debug Form Data</h1>
    <p>This page will show what data is being sent from the form.</p>
    
    <form method="POST" action="">
        <input type="hidden" name="action" value="create">
        <input type="text" name="website_id" value="1" placeholder="Website ID">
        <input type="text" name="priority" value="Medium" placeholder="Priority">
        <input type="text" name="category" value="Test" placeholder="Category">
        <input type="text" name="nama_pj" value="Test PJ" placeholder="Nama PJ">
        <input type="text" name="title" value="Test Title" placeholder="Title">
        <textarea name="description" placeholder="Description">Test Description</textarea>
        <button type="submit">Test Submit</button>
    </form>
</body>
</html>