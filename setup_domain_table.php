<?php
require_once 'config/init.php';

// Create domain_purchases table
$sql = "CREATE TABLE IF NOT EXISTS domain_purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain VARCHAR(255) NOT NULL,
    registrar VARCHAR(100),
    harga DECIMAL(15,2) DEFAULT 0,
    tanggal_beli DATE,
    tanggal_expired DATE,
    holding VARCHAR(50),
    pic VARCHAR(100),
    status ENUM('active', 'pending', 'expired') DEFAULT 'active',
    catatan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_domain (domain),
    INDEX idx_holding (holding),
    INDEX idx_status (status),
    INDEX idx_expired (tanggal_expired)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($conn, $sql)) {
    echo "<h2 style='color:green'>✅ Tabel domain_purchases berhasil dibuat!</h2>";
    echo "<p>Anda sekarang bisa menggunakan fitur Beli Domain.</p>";
    echo "<p><a href='beli_domain.php'>Klik di sini untuk membuka halaman Beli Domain</a></p>";
} else {
    echo "<h2 style='color:red'>❌ Error: " . mysqli_error($conn) . "</h2>";
}
?>
