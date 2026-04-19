<?php
require_once 'config/init.php';

// Create cloudflare_cdn table
$create_table = "CREATE TABLE IF NOT EXISTS cloudflare_cdn (
    id INT AUTO_INCREMENT PRIMARY KEY,
    website_id INT NOT NULL,
    cdn_status ENUM('Cloudflare', 'Cloudflare 1', 'Cloudflare 2', 'Cloudflare 3', 'Cloudflare 4', 'Bunny', 'Niagahoster', 'Jagoan Hosting') DEFAULT 'Cloudflare',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_website (website_id),
    FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($conn, $create_table)) {
    echo "✓ Tabel cloudflare_cdn berhasil dibuat!<br>";
    echo "<a href='cloudflare'>Buka Cloudflare CDN Management</a>";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>