<?php
require_once __DIR__ . '/../controllers/AuthController.php';

$auth   = new AuthController($conn);
$result = $auth->handleLogin();
$error  = $result['error'];

// Redirect ke home.php jika login berhasil
if (empty($error) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: home.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk</title>
    <style>
        :root {
            --accent-blue: #1d9bf0;
            --soft-blue: #85a3db;
            --navy: #1a2a6c;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e3f2fd 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .card {
            background: white;
            padding: 40px;
            border-radius: 32px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.07);
        }
        h2 { color: var(--navy); margin-bottom: 8px; }
        p.sub { color: #888; font-size: 14px; margin-bottom: 28px; }
        input {
            width: 100%;
            padding: 14px 16px;
            margin-bottom: 14px;
            border-radius: 12px;
            border: 1px solid #e8e8e8;
            background: #f8faff;
            font-size: 15px;
            outline: none;
            transition: border-color 0.2s;
        }
        input:focus { border-color: var(--soft-blue); background: #fff; }
        .btn {
            width: 100%;
            padding: 14px;
            background: var(--navy);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.2s;
        }
        .btn:hover { opacity: 0.88; transform: translateY(-1px); }
        .error {
            background: #fff0f0;
            color: #c0392b;
            border: 1px solid #f5c6cb;
            border-radius: 10px;
            padding: 10px 14px;
            margin-bottom: 16px;
            font-size: 13px;
        }
        .link-reg { margin-top: 22px; font-size: 13px; color: #666; }
        .link-reg a { color: var(--accent-blue); text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
<div class="card">
    <h2>Selamat Datang</h2>
    <p class="sub">Masuk untuk melanjutkan</p>

    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="text" name="username" placeholder="Username" 
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" class="btn">Masuk Sekarang</button>
    </form>

    <p class="link-reg">
        Belum punya akun? <a href="register.php">Daftar di sini</a>
    </p>
</div>
</body>
</html>