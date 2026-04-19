<?php
require_once 'config/init.php';

// Update user 'seh' to have role 'user_ojs'
$username = 'seh';
$new_role = 'user_ojs';

// Check if role column exists
$check_column = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
if (mysqli_num_rows($check_column) == 0) {
    // Add role column if not exists
    $add_column = "ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT 'admin'";
    if (mysqli_query($conn, $add_column)) {
        echo "<p style='color:green'>✅ Kolom 'role' berhasil ditambahkan ke tabel users</p>";
    } else {
        echo "<p style='color:red'>❌ Gagal menambahkan kolom: " . mysqli_error($conn) . "</p>";
    }
}

// Update user role
$update = mysqli_query($conn, "UPDATE users SET role = '$new_role' WHERE username = '$username'");

if ($update && mysqli_affected_rows($conn) > 0) {
    echo "<h2 style='color:green'>✅ User '$username' berhasil diupdate ke role '$new_role'</h2>";
    echo "<p>Sekarang user '$username' akan diarahkan ke halaman OJS Progress saat login.</p>";
} else {
    // Check if user exists
    $check_user = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");
    if (mysqli_num_rows($check_user) == 0) {
        echo "<h2 style='color:orange'>⚠️ User '$username' tidak ditemukan</h2>";
        echo "<p>Daftar user yang ada:</p>";
        $all_users = mysqli_query($conn, "SELECT id, username, role FROM users");
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Username</th><th>Role</th></tr>";
        while ($user = mysqli_fetch_assoc($all_users)) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . $user['username'] . "</td>";
            echo "<td>" . ($user['role'] ?? 'admin') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        $user = mysqli_fetch_assoc($check_user);
        echo "<h2 style='color:blue'>ℹ️ User '$username' sudah memiliki role: " . ($user['role'] ?? 'admin') . "</h2>";
    }
}

echo "<br><br><a href='login.php'>← Kembali ke Login</a>";
?>
