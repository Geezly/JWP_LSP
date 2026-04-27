<?php
// index.php
require_once 'config/database.php';
require_once 'controllers/AuthController.php';

$auth = new AuthController($conn);
$auth->handleRegister();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daftar ke X - Clone</title>
    <link rel="stylesheet" href="assets/css/style.css"> 
    <style>
        body { font-family: Arial, sans-serif; display: flex; justify-content: center; padding-top: 50px; }
        .form-container { width: 300px; padding: 20px; border: 1px solid #ccc; border-radius: 10px; }
        input { width: 100%; margin-bottom: 10px; padding: 8px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: #1DA1F2; color: white; border: none; border-radius: 20px; cursor: pointer; }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Buat Akun Baru</h2>
    <form method="POST" action="">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Daftar</button>
    </form>
</div>

</body>
</html>