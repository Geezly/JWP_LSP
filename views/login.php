<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar / Akun Baru</title>
    <style>
        :root {
            --primary-blue: #e3f2fd;
            --accent-blue: #1d9bf0;
            --soft-blue: #85a3db;
        }
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #f5f7fa 0%, #e3f2fd 100%);
            height: 100vh; display: flex; justify-content: center; align-items: center;
            margin: 0;
        }
        .register-card {
            background: white; padding: 40px; border-radius: 32px;
            width: 100%; max-width: 400px; text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.05);
        }
        input {
            width: 100%; padding: 15px; margin-bottom: 15px;
            border-radius: 12px; border: 1px solid #f0f0f0; background: #f8faff;
            box-sizing: border-box; /* Agar padding tidak merusak lebar */
        }
        .btn-reg {
            width: 100%; padding: 15px; background: var(--soft-blue);
            color: white; border: none; border-radius: 12px; font-weight: bold;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <div class="register-card">
        <h2>Buat Akun Baru</h2>
        <p style="color: #888; font-size: 14px; margin-bottom: 20px;">Daftar untuk mulai berbagi cerita</p>

        <form action="../controllers/RegisterController.php" method="POST">
            <input type="text" name="nama_lengkap" placeholder="Nama Lengkap" required>
            <input type="email" name="email" placeholder="Email Baru" required>
            <input type="password" name="password" placeholder="Buat Password" required>
            
            <button type="submit" class="btn-reg">Daftar Sekarang</button>
        </form>

        <p style="margin-top: 20px; font-size: 13px;">
            Sudah punya akun? <a href="login.php" style="color: var(--accent-blue); text-decoration: none;">Login di sini</a>
        </p>
    </div>

</body>
</html>