<?php
session_start();

// Define base path
define('BASE_PATH', dirname(__DIR__) . '/');

// Include database
require_once BASE_PATH . 'config/database.php';

// Helper function for redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Helper function for checking login
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function for getting current user
function getCurrentUser($conn) {
    if (!isLoggedIn()) return null;
    
    $user_id = $_SESSION['user_id'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
    
    if (!$stmt) return null;
    
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    return $user;
}
?>
