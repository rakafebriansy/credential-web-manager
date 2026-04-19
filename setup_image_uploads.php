<?php
require_once 'config/init.php';

echo "<h2>Setup Image Upload System</h2>";

// Create uploads directory if not exists
$upload_dirs = ['uploads/editor', 'uploads/tickets'];
foreach ($upload_dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
        echo "<p style='color: green;'>✓ Created directory: $dir</p>";
    } else {
        echo "<p style='color: blue;'>✓ Directory exists: $dir</p>";
    }
}

// Create uploaded_images table
$sql = "CREATE TABLE IF NOT EXISTS uploaded_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    uploaded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_uploaded_by (uploaded_by),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($conn, $sql)) {
    echo "<p style='color: green;'>✓ Table 'uploaded_images' created successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating table: " . mysqli_error($conn) . "</p>";
}

echo "<h3>Image Upload Features:</h3>";
echo "<ul>";
echo "<li>✓ Copy & paste images directly from clipboard</li>";
echo "<li>✓ Drag & drop multiple images</li>";
echo "<li>✓ File picker for selecting images</li>";
echo "<li>✓ Automatic image resizing (max 1200x800)</li>";
echo "<li>✓ Support for JPEG, PNG, GIF, WebP</li>";
echo "<li>✓ Maximum file size: 5MB per image</li>";
echo "<li>✓ Rich text editor with formatting tools</li>";
echo "<li>✓ Image preview and management</li>";
echo "</ul>";

echo "<h3>Usage Instructions:</h3>";
echo "<ol>";
echo "<li><strong>Copy & Paste:</strong> Copy any image (Ctrl+C) and paste it directly in the editor (Ctrl+V)</li>";
echo "<li><strong>Drag & Drop:</strong> Drag images from your computer directly into the editor</li>";
echo "<li><strong>File Picker:</strong> Click the image button in the toolbar to select files</li>";
echo "<li><strong>Multiple Images:</strong> You can add as many images as needed</li>";
echo "</ol>";

echo "<h3>Security Features:</h3>";
echo "<ul>";
echo "<li>✓ File type validation</li>";
echo "<li>✓ File size limits</li>";
echo "<li>✓ User authentication required</li>";
echo "<li>✓ Unique filename generation</li>";
echo "<li>✓ Database tracking of all uploads</li>";
echo "</ul>";

echo "<br><p><a href='tickets' style='padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Test in Tickets</a></p>";

?>