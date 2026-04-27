<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk / Interaksi</title>
    <style>
        :root {
            --primary-blue: #e3f2fd;
            --accent-blue: #1d9bf0;
            --soft-blue: #85a3db;
            --navy: #1a2a6c;
        }
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #f5f7fa 0%, #e3f2fd 100%);
            height: 100vh; display: flex; justify-content: center; align-items: center;
            margin: 0;
        }
        .login-card {
            background: white; padding: 40px; border-radius: 32px;
            width: 100%; max-width: 400px; text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.05);
        }
        input {
            width: 100%; padding: 15px; margin-bottom: 15px;
            border-radius: 12px; border: 1px solid #f0f0f0; background: #f8faff;
            box-sizing: border-box;
            outline: none;
            transition: 0.3s;
        }
        input:focus {
            border-color: var(--soft-blue);
            background: #fff;
        }
        .btn-login {
            width: 100%; padding: 15px; background: var(--navy); /* Pakai Navy biar beda dikit sama regist */
            color: white; border: none; border-radius: 12px; font-weight: bold;
            cursor: pointer; transition: 0.3s;
            box-shadow: 0 4px 15px rgba(26, 42, 108, 0.2);
        }
        .btn-login:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <h2 style="color: var(--navy);">Selamat Datang</h2>
        <p style="color: #888; font-size: 14px; margin-bottom: 30px;">Silakan masuk untuk melanjutkan</p>

        <form action="../controllers/LoginController.php" method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            
            <button type="submit" class="btn-login">Masuk Sekarang</button>
        </form>

        <p style="margin-top: 25px; font-size: 13px; color: #666;">
            Belum punya akun? <a href="register.php" style="color: var(--accent-blue); text-decoration: none; font-weight: bold;">Daftar di sini</a>
        </p>
    </div>

</body>
</html>