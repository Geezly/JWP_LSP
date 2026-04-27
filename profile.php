<?php
/**
 * Case Study: MicroSocial - Profile Module (Website Version)
 */
session_start();

// Simulasi data user
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'username' => 'Fahra',
        'bio'      => 'Sesi coding hari ini, file lengkap ada di sini! #webdev',
        'photo'    => 'https://ui-avatars.com/api/?name=Fahra&background=1d4e89&color=fff&size=200'
    ];
}

$notif = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $_SESSION['user']['username'] = htmlspecialchars($_POST['username']);
    $_SESSION['user']['bio']      = htmlspecialchars($_POST['bio']);
    $notif = "Profil berhasil disimpan!";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - MicroSocial Web</title>
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;600&display=swap" rel="stylesheet">
    <style>
        /* CSS UNTUK TAMPILAN WEBSITE DESKTOP */
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Navbar Sederhana agar terasa seperti Website */
        .navbar {
            width: 100%;
            background-color: #1d4e89;
            padding: 10px 0;
            color: white;
            text-align: center;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 40px;
        }

        .main-container {
            width: 100%;
            max-width: 800px; /* Jauh lebih lebar dari versi mobile */
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .profile-header {
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .profile-header h2 {
            margin: 0;
            color: #1d4e89;
            font-size: 24px;
        }

        .content-body {
            padding: 40px;
            display: flex; /* Menggunakan Flexbox untuk membagi Foto dan Form */
            gap: 40px;
        }

        /* Sisi Kiri: Foto Profil */
        .photo-section {
            flex: 1;
            text-align: center;
        }

        .avatar-container {
            position: relative;
            display: inline-block;
        }

        .avatar-img {
            width: 180px; /* Ukuran lebih besar untuk desktop */
            height: 180px;
            border-radius: 50%;
            border: 5px solid #1d4e89;
            object-fit: cover;
        }

        .btn-plus {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: #1d4e89;
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            border: 3px solid white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            cursor: pointer;
        }

        /* Sisi Kanan: Input Form */
        .form-section {
            flex: 2;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        input[type="text"], textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            background: #fafafa;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: #1d4e89;
            background: white;
        }

        .footer-actions {
            margin-top: 30px;
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 12px 30px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            transition: 0.3s;
        }

        .btn-save {
            background-color: #1d4e89;
            color: white;
        }

        .btn-cancel {
            background-color: #e4e6eb;
            color: #4b4b4b;
            text-decoration: none;
        }

        .btn:hover {
            filter: brightness(1.2);
        }

        .alert {
            background: #d4edda;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #155724;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="navbar">MICROSOCIAL WEB</div>

<div class="main-container">
    <div class="profile-header">
        <h2>Edit Profile</h2>
        <?php if($notif): ?>
            <span style="color: green; font-weight: bold;"><?= $notif ?></span>
        <?php endif; ?>
    </div>

    <form action="" method="POST">
        <div class="content-body">
            <div class="photo-section">
                <div class="avatar-container">
                    <img src="<?= $_SESSION['user']['photo'] ?>" class="avatar-img" alt="Profile">
                    <div class="btn-plus">+</div>
                </div>
                <p style="font-size: 12px; color: #777; mt-2">Klik ikon + untuk ganti foto</p>
            </div>

            <div class="form-section">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" value="<?= $_SESSION['user']['username'] ?>" required>
                </div>

                <div class="form-group">
                    <label>Bio</label>
                    <textarea name="bio" rows="5" maxlength="250"><?= $_SESSION['user']['bio'] ?></textarea>
                    <div style="text-align:right; font-size: 12px; color: #999;">(250 char)</div>
                </div>

                <div class="footer-actions">
                    <button type="submit" class="btn btn-save">Save Changes</button>
                    <a href="index.php" class="btn btn-cancel">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>

</body>
</html>