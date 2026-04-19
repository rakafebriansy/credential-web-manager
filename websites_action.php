<?php
require_once 'config/init.php';

if (!isLoggedIn()) {
    redirect('login');
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// CREATE
if ($action == 'create') {
    $holding = trim($_POST['holding'] ?? '');
    $link_url = trim($_POST['link_url'] ?? '');
    $jenis_web = trim($_POST['jenis_web'] ?? '');
    $letak_server = trim($_POST['letak_server'] ?? '');
    $pic = trim($_POST['pic'] ?? '');
    
    // Validate required fields
    if (empty($holding) || empty($link_url)) {
        $_SESSION['error'] = 'Holding dan Link URL wajib diisi!';
        redirect('websites');
    }
    
    // Use prepared statement to prevent SQL injection and handle special characters
    $query = "INSERT INTO websites (holding, link_url, jenis_web, letak_server, pic) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'sssss', $holding, $link_url, $jenis_web, $letak_server, $pic);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = 'Website berhasil ditambahkan!';
        } else {
            $_SESSION['error'] = 'Gagal menambahkan website: ' . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error'] = 'Database error: ' . mysqli_error($conn);
    }
    
    redirect('websites');
}

// UPDATE
if ($action == 'update') {
    $id = (int)$_POST['id'];
    $holding = trim($_POST['holding'] ?? '');
    $link_url = trim($_POST['link_url'] ?? '');
    $jenis_web = trim($_POST['jenis_web'] ?? '');
    $letak_server = trim($_POST['letak_server'] ?? '');
    $pic = trim($_POST['pic'] ?? '');
    
    // Validate
    if (empty($holding) || empty($link_url) || $id <= 0) {
        $_SESSION['error'] = 'Data tidak valid!';
        redirect('websites');
    }
    
    $query = "UPDATE websites SET holding = ?, link_url = ?, jenis_web = ?, letak_server = ?, pic = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'sssssi', $holding, $link_url, $jenis_web, $letak_server, $pic, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = 'Website berhasil diupdate!';
        } else {
            $_SESSION['error'] = 'Gagal mengupdate website: ' . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error'] = 'Database error: ' . mysqli_error($conn);
    }
    
    redirect('websites');
}

// DELETE
if ($action == 'delete') {
    $id = (int)$_GET['id'];
    
    if ($id <= 0) {
        $_SESSION['error'] = 'ID tidak valid!';
        redirect('websites');
    }
    
    $query = "DELETE FROM websites WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = 'Website berhasil dihapus!';
        } else {
            $_SESSION['error'] = 'Gagal menghapus website: ' . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error'] = 'Database error: ' . mysqli_error($conn);
    }
    
    redirect('websites');
}

redirect('websites');
?>
