<?php
require_once 'config/init.php';

if (!isLoggedIn()) {
    redirect('login');
}

// Check access
if (!isset($_SESSION['credentials_access']) || $_SESSION['credentials_access'] !== true) {
    redirect('website_credentials');
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// CREATE
if ($action == 'create') {
    // Ensure table exists
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'website_credentials'");
    if (mysqli_num_rows($check_table) == 0) {
        $create_table = "CREATE TABLE IF NOT EXISTS website_credentials (
            id INT AUTO_INCREMENT PRIMARY KEY,
            website_id INT NOT NULL,
            holding VARCHAR(50) NOT NULL,
            nama_website VARCHAR(255) NOT NULL,
            username_admin VARCHAR(100) NOT NULL,
            password_admin TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_website_id (website_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        mysqli_query($conn, $create_table);
    }
    
    $website_id = (int)$_POST['website_id'];
    $holding = strtoupper(trim($_POST['holding']));
    $username_admin = trim($_POST['username_admin']);
    $password_admin = trim($_POST['password_admin']);
    
    if (empty($website_id) || empty($holding) || empty($username_admin) || empty($password_admin)) {
        $_SESSION['error'] = 'Semua field wajib diisi!';
        redirect('website_credentials');
    }
    
    $query = "INSERT INTO website_credentials (website_id, holding, username_admin, password_admin) 
              VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'isss', $website_id, $holding, $username_admin, $password_admin);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = 'Credentials berhasil ditambahkan!';
        } else {
            $_SESSION['error'] = 'Gagal menambahkan credentials: ' . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error'] = 'Database error: ' . mysqli_error($conn);
    }
    
    redirect('website_credentials');
}

// UPDATE
if ($action == 'update') {
    $id = (int)$_POST['id'];
    $website_id = (int)$_POST['website_id'];
    $holding = strtoupper(trim($_POST['holding']));
    $username_admin = trim($_POST['username_admin']);
    $password_admin = trim($_POST['password_admin']);
    
    if (empty($id) || empty($website_id) || empty($holding) || empty($username_admin) || empty($password_admin)) {
        $_SESSION['error'] = 'Data tidak valid!';
        redirect('website_credentials');
    }
    
    $query = "UPDATE website_credentials 
              SET website_id = ?, holding = ?, username_admin = ?, password_admin = ? 
              WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'isssi', $website_id, $holding, $username_admin, $password_admin, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = 'Credentials berhasil diupdate!';
        } else {
            $_SESSION['error'] = 'Gagal mengupdate credentials: ' . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error'] = 'Database error: ' . mysqli_error($conn);
    }
    
    redirect('website_credentials');
}

// DELETE
if ($action == 'delete') {
    $id = (int)$_GET['id'];
    
    if ($id <= 0) {
        $_SESSION['error'] = 'ID tidak valid!';
        redirect('website_credentials');
    }
    
    $query = "DELETE FROM website_credentials WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = 'Credentials berhasil dihapus!';
        } else {
            $_SESSION['error'] = 'Gagal menghapus credentials: ' . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error'] = 'Database error: ' . mysqli_error($conn);
    }
    
    redirect('website_credentials');
}

redirect('website_credentials');
?>
