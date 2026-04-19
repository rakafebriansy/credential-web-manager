<?php
require_once 'config/init.php';

if (isLoggedIn()) {
    redirect('index');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    $query = "SELECT * FROM users WHERE username = '$username' OR email = '$username'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = isset($user['role']) ? $user['role'] : 'admin';
            $_SESSION['holding'] = isset($user['holding']) ? $user['holding'] : null;
            
            // Redirect based on role
            if ($_SESSION['role'] == 'user_ojs') {
                redirect('ojs_progress');
            } elseif ($_SESSION['role'] == 'user_holding') {
                redirect('tiket_kunjungan');
            } else {
                redirect('index');
            }
        } else {
            $error = 'Username atau password salah!';
        }
    } else {
        $error = 'Username atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <div class="logo">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h1>Selamat Datang</h1>
                <p>Silakan login untuk melanjutkan</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i>
                        Username atau Email
                    </label>
                    <input type="text" id="username" name="username" required 
                           placeholder="Masukkan username atau email">
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Masukkan password">
                </div>
                
                <div class="form-options">
                    <label class="checkbox">
                        <input type="checkbox" name="remember">
                        <span>Ingat saya</span>
                    </label>
                    <a href="#" class="forgot-password">Lupa password?</a>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i>
                    Login
                </button>
            </form>
            
            <!-- <div class="login-footer">
                <p>Belum punya akun? <a href="register">Daftar sekarang</a></p>
            </div> -->
        </div>
        
        <div class="login-info">
            <h2>Dashboard Modern</h2>
            <p>Sistem manajemen dengan antarmuka yang intuitif dan responsif</p>
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
