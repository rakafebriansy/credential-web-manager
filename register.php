<?php
require_once 'config/init.php';

if (isLoggedIn()) {
    redirect('index');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $error = 'Password tidak cocok!';
    } else {
        $check_query = "SELECT * FROM users WHERE username = '$username' OR email = '$email'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = 'Username atau email sudah terdaftar!';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_query = "INSERT INTO users (username, email, password, full_name) VALUES ('$username', '$email', '$hashed_password', '$full_name')";
            
            if (mysqli_query($conn, $insert_query)) {
                $success = 'Registrasi berhasil! Silakan login.';
            } else {
                $error = 'Terjadi kesalahan. Silakan coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <div class="logo">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h1>Buat Akun Baru</h1>
                <p>Daftar untuk memulai</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="full_name">
                        <i class="fas fa-id-card"></i>
                        Nama Lengkap
                    </label>
                    <input type="text" id="full_name" name="full_name" required 
                           placeholder="Masukkan nama lengkap">
                </div>
                
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i>
                        Username
                    </label>
                    <input type="text" id="username" name="username" required 
                           placeholder="Masukkan username">
                </div>
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        Email
                    </label>
                    <input type="email" id="email" name="email" required 
                           placeholder="Masukkan email">
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Masukkan password">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i>
                        Konfirmasi Password
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           placeholder="Konfirmasi password">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-user-plus"></i>
                    Daftar
                </button>
            </form>
            
            <div class="login-footer">
                <p>Sudah punya akun? <a href="login">Login sekarang</a></p>
            </div>
        </div>
        
        <div class="login-info">
            <h2>Bergabunglah dengan Kami</h2>
            <p>Dapatkan akses ke dashboard modern dengan fitur lengkap</p>
            <div class="features">
                <div class="feature-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics Real-time</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-shield-alt"></i>
                    <span>Keamanan Terjamin</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-mobile-alt"></i>
                    <span>Responsive Design</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
