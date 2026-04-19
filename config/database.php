<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'testing_project');

// Create connection without database first
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
mysqli_query($conn, $sql);

// Select database
mysqli_select_db($conn, DB_NAME);

// Set charset to utf8
mysqli_set_charset($conn, "utf8");

// Create users table if not exists
$create_users = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_users);

// Create websites table if not exists
$create_websites = "CREATE TABLE IF NOT EXISTS websites (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    holding VARCHAR(50) NOT NULL,
    link_url VARCHAR(255) NOT NULL,
    jenis_web VARCHAR(50) NOT NULL,
    letak_server VARCHAR(100) NOT NULL,
    pic VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
mysqli_query($conn, $create_websites);

// Create scan_results table if not exists
$create_scan_results = "CREATE TABLE IF NOT EXISTS scan_results (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    website_id INT(11) NOT NULL,
    url VARCHAR(255) NOT NULL,
    status VARCHAR(20) NOT NULL,
    http_code INT(11) DEFAULT 0,
    response_time INT(11) DEFAULT 0,
    is_infected TINYINT(1) DEFAULT 0,
    detected_keywords TEXT,
    scan_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_website (website_id)
)";
mysqli_query($conn, $create_scan_results);

// Create content_scan_results table for protection check
$create_content_scan = "CREATE TABLE IF NOT EXISTS content_scan_results (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    website_id INT(11) NOT NULL,
    url VARCHAR(255) NOT NULL,
    status VARCHAR(20) NOT NULL,
    http_code INT(11) DEFAULT 0,
    response_time INT(11) DEFAULT 0,
    content_length INT(11) DEFAULT 0,
    is_infected TINYINT(1) DEFAULT 0,
    infection_status VARCHAR(50) DEFAULT 'Aman',
    detection_count INT(11) DEFAULT 0,
    detected_keywords TEXT,
    detections_json LONGTEXT,
    scan_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_website (website_id)
)";
mysqli_query($conn, $create_content_scan);

// Create google_scan_results table
$create_google_scan = "CREATE TABLE IF NOT EXISTS google_scan_results (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    website_id INT(11) NOT NULL,
    domain VARCHAR(255) NOT NULL,
    is_infected TINYINT(1) DEFAULT 0,
    infection_level VARCHAR(50) DEFAULT 'safe',
    total_results INT(11) DEFAULT 0,
    detection_count INT(11) DEFAULT 0,
    detections_json LONGTEXT,
    scan_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_website (website_id)
)";
mysqli_query($conn, $create_google_scan);

// Create tiket_kunjungan table
$create_tiket = "CREATE TABLE IF NOT EXISTS tiket_kunjungan (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    holding VARCHAR(50) NOT NULL,
    tanggal_kunjungan DATE NOT NULL,
    waktu_mulai TIME,
    waktu_selesai TIME,
    tujuan VARCHAR(255),
    pic VARCHAR(100),
    catatan TEXT,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    hasil_kunjungan TEXT,
    created_by INT(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_holding (holding),
    INDEX idx_tanggal (tanggal_kunjungan),
    INDEX idx_status (status)
)";
mysqli_query($conn, $create_tiket);

// Create bisnis_proposal table
$create_bisnis = "CREATE TABLE IF NOT EXISTS bisnis_proposal (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    holding VARCHAR(50) NOT NULL,
    profil_mitra TEXT,
    masalah_utama TEXT,
    solusi_jasa TEXT,
    value_prop_1 VARCHAR(255),
    value_prop_2 VARCHAR(255),
    value_prop_3 VARCHAR(255),
    skema_kerjasama TEXT,
    target_bisnis TEXT,
    opening_pitch TEXT,
    status ENUM('draft', 'review', 'approved', 'active', 'rejected') DEFAULT 'draft',
    reminder_date DATE,
    created_by INT(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_holding (holding),
    INDEX idx_status (status)
)";
mysqli_query($conn, $create_bisnis);

// Add status column if not exists (MySQL compatible way)
$check_status = mysqli_query($conn, "SHOW COLUMNS FROM bisnis_proposal LIKE 'status'");
if (mysqli_num_rows($check_status) == 0) {
    mysqli_query($conn, "ALTER TABLE bisnis_proposal ADD COLUMN status ENUM('draft', 'review', 'approved', 'active', 'rejected') DEFAULT 'draft' AFTER opening_pitch");
}
$check_reminder = mysqli_query($conn, "SHOW COLUMNS FROM bisnis_proposal LIKE 'reminder_date'");
if (mysqli_num_rows($check_reminder) == 0) {
    mysqli_query($conn, "ALTER TABLE bisnis_proposal ADD COLUMN reminder_date DATE AFTER status");
}

// Create bisnis_proposal_comments table
$create_bisnis_comments = "CREATE TABLE IF NOT EXISTS bisnis_proposal_comments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    proposal_id INT(11) NOT NULL,
    user_id INT(11),
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_proposal (proposal_id)
)";
mysqli_query($conn, $create_bisnis_comments);

// Create bisnis_proposal_history table
$create_bisnis_history = "CREATE TABLE IF NOT EXISTS bisnis_proposal_history (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    proposal_id INT(11) NOT NULL,
    user_id INT(11),
    action VARCHAR(50) NOT NULL,
    old_value TEXT,
    new_value TEXT,
    field_changed VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_proposal (proposal_id)
)";
mysqli_query($conn, $create_bisnis_history);

// Create bisnis_proposal_attachments table
$create_bisnis_attachments = "CREATE TABLE IF NOT EXISTS bisnis_proposal_attachments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    proposal_id INT(11) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_size INT(11) DEFAULT 0,
    file_type VARCHAR(100),
    uploaded_by INT(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_proposal (proposal_id)
)";
mysqli_query($conn, $create_bisnis_attachments);

// Create website_health table if not exists
$create_website_health = "CREATE TABLE IF NOT EXISTS website_health (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    website_id INT(11) NOT NULL,
    status ENUM('sehat', 'peringatan', 'bahaya') NOT NULL DEFAULT 'sehat',
    condition_text VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    http_code INT(11) DEFAULT NULL,
    response_time INT(11) DEFAULT NULL,
    last_check TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_website (website_id)
)";
mysqli_query($conn, $create_website_health);

// Check if default admin exists, if not create it
$check_admin = "SELECT * FROM users WHERE username = 'admin'";
$result = mysqli_query($conn, $check_admin);
if (mysqli_num_rows($result) == 0) {
    $default_password = password_hash('admin123', PASSWORD_DEFAULT);
    $insert_admin = "INSERT INTO users (username, email, password, full_name) VALUES 
    ('admin', 'admin@example.com', '$default_password', 'Administrator')";
    mysqli_query($conn, $insert_admin);
}
