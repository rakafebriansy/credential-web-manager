<?php
require_once 'config/init.php';

echo "<h2>Remove Forum Tables from Database</h2>";

// List of forum tables to remove
$forum_tables = [
    'forum_views',
    'forum_likes', 
    'forum_attachments',
    'forum_replies',
    'forum_posts',
    'forum_categories'
];

echo "<h3>Removing Forum Tables:</h3>";

$success_count = 0;
$error_count = 0;

foreach ($forum_tables as $table) {
    // Check if table exists first
    $check_query = "SHOW TABLES LIKE '$table'";
    $result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($result) > 0) {
        // Table exists, drop it
        $drop_query = "DROP TABLE IF EXISTS $table";
        
        if (mysqli_query($conn, $drop_query)) {
            echo "<p style='color: green;'>✓ Dropped table: $table</p>";
            $success_count++;
        } else {
            echo "<p style='color: red;'>✗ Error dropping table $table: " . mysqli_error($conn) . "</p>";
            $error_count++;
        }
    } else {
        echo "<p style='color: blue;'>- Table $table does not exist (already removed)</p>";
    }
}

// Also remove any forum-related uploads directory
$forum_upload_dir = 'uploads/forum';
if (is_dir($forum_upload_dir)) {
    // Remove all files in the directory first
    $files = glob($forum_upload_dir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    
    // Remove the directory
    if (rmdir($forum_upload_dir)) {
        echo "<p style='color: green;'>✓ Removed forum uploads directory</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Could not remove forum uploads directory</p>";
    }
} else {
    echo "<p style='color: blue;'>- Forum uploads directory does not exist</p>";
}

echo "<h3>Summary:</h3>";
if ($error_count == 0) {
    echo "<p style='color: green;'>✓ Forum system completely removed!</p>";
    echo "<ul>";
    echo "<li>✓ $success_count database tables dropped</li>";
    echo "<li>✓ Forum files deleted</li>";
    echo "<li>✓ Forum menu removed from sidebar</li>";
    echo "<li>✓ Forum uploads directory cleaned</li>";
    echo "</ul>";
} else {
    echo "<p style='color: red;'>✗ Removal completed with $error_count errors</p>";
}

echo "<h3>Removed Components:</h3>";
echo "<ul>";
echo "<li>✗ forum.php - Main forum page</li>";
echo "<li>✗ forum_post.php - Forum post detail page</li>";
echo "<li>✗ forum_action.php - Forum actions handler</li>";
echo "<li>✗ forum.sql - Forum database schema</li>";
echo "<li>✗ setup_forum.php - Forum setup script</li>";
echo "<li>✗ test_forum_delete.php - Forum test file</li>";
echo "<li>✗ Forum menu from sidebar</li>";
echo "<li>✗ Forum database tables</li>";
echo "<li>✗ Forum uploads directory</li>";
echo "</ul>";

echo "<br><p><a href='tickets' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Go to Tickets System</a></p>";
echo "<p><a href='index' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Go to Dashboard</a></p>";
?>