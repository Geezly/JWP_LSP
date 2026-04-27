<?php
// views/login.php

// Pastikan jalur require sesuai dengan struktur folder kamu
require_once '../config/database.php';
require_once '../controllers/AuthController.php';

// Inisialisasi AuthController
// (Pastikan AuthController kamu sudah punya metode handleLogin)
$auth = new AuthController($conn);
$error = $auth->handleLogin(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - X Clone</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <div class="login-container">
        <div class="logo">X</div>
        
        <form action="" method="POST">
            <?php if (isset($error)): ?>
                <p style="color: #ff4d4d; font-size: 13px;"><?php echo $error; ?></p>
            <?php endif; ?>

            <div class="input-group">
                <i class="fa-solid fa-envelope"></i>
                <input type="text" name="username" placeholder="Username atau Email" required>
            </div>

            <div class="input-group">
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>

            <button type="submit" name="login" class="btn-login">Masuk</button>
        </form>

        <div class="footer-links">
            Belum punya akun? <a href="register.php">Daftar sekarang</a>
        </div>
    </div>

</body>
</html>