<?php
require_once __DIR__ . '/../controllers/AuthController.php';

$auth   = new AuthController($conn);
$result = $auth->handleRegister();
$error   = $result['error'];
$success = $result['success'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun</title>
    <style>
        :root {
            --accent-blue: #1d9bf0;
            --soft-blue: #85a3db;
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
        h2 { color: #1a2a6c; margin-bottom: 8px; }
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
            background: var(--soft-blue);
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
        .success {
            background: #f0fff4;
            color: #27ae60;
            border: 1px solid #b2dfdb;
            border-radius: 10px;
            padding: 10px 14px;
            margin-bottom: 16px;
            font-size: 13px;
        }
        .link-login { margin-top: 22px; font-size: 13px; color: #666; }
        .link-login a { color: var(--accent-blue); text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
<div class="card">
    <h2>Buat Akun Baru</h2>
    <p class="sub">Daftar untuk mulai berbagi cerita</p>

    <?php if (!empty($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="success">
            <?= htmlspecialchars($success) ?>
            <br><a href="login.php">Login sekarang →</a>
        </div>
    <?php endif; ?>

    <?php if (empty($success)): ?>
    <form method="POST" action="">
        <input type="text" name="username" placeholder="Username" 
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
        <input type="password" name="password" placeholder="Buat Password (min. 6 karakter)" required>
        <button type="submit" class="btn">Daftar Sekarang</button>
    </form>
    <?php endif; ?>

    <p class="link-login">
        Sudah punya akun? <a href="login.php">Login di sini</a>
    </p>
</div>
</body>
</html>