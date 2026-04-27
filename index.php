<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: views/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Beranda</title>
</head>
<body>
    <h2>Halo, <?= htmlspecialchars($_SESSION['username']) ?>! 👋</h2>
    <p>Kamu sudah berhasil login.</p>
    <a href="controllers/logout.php">Logout</a>
</body>
</html>