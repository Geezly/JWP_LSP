<?php
session_start();

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'username' => 'Ghea Ananda',
        'bio'      => 'Sesi coding hari ini, file lengkap ada di sini! #webdev',
        'photo'    => 'https://ui-avatars.com/api/?name=Ghea+Ananda&background=1d4e89&color=fff&size=128'
    ];
}

$notif = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $_SESSION['user']['username'] = htmlspecialchars($_POST['username']);
    $_SESSION['user']['bio']      = htmlspecialchars($_POST['bio']);
    $notif = "Profil berhasil diperbarui secara lokal!";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile Edit - MicroSocial</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        {
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .profile-card {
            background: white;
            width: 100%;
            max-width: 400px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .card-header {
            background-color: #1d4e89; /* Warna Biru MicroSocial */
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }

        .card-header h2 {
            margin: 0;
            font-size: 18px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .card-body {
            padding: 30px 25px;
        }

        /* Foto Profil & Ikon Plus */
        .avatar-section {
            position: relative;
            width: 110px;
            margin: 0 auto 30px;
        }

        .avatar-img {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #f0f2f5;
        }

        .btn-add-photo {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: #1d4e89;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 18px;
            border: 2px solid white;
            cursor: pointer;
        }

        /* Form Styling */
        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 600;
            font-size: 13px;
            color: #555;
            margin-bottom: 8px;
        }

        input[type="text"], textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 14px;
            transition: border 0.3s;
        }

        input[type="text"]:focus, textarea:focus {
            outline: none;
            border-color: #1d4e89;
        }

        .char-count {
            text-align: right;
            font-size: 11px;
            color: #999;
            margin-top: 5px;
        }

        /* Buttons */
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 12px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: opacity 0.2s;
        }

        .btn-save {
            background-color: #1d4e89;
            color: white;
        }

        .btn-cancel {
            background-color: #e4e6eb;
            color: #4b4b4b;
            text-decoration: none;
            text-align: center;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 8px;
            font-size: 13px;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<div class="profile-card">
    <div class="card-header">
        <h2>Profile</h2>
    </div>

    <div class="card-body">
        <?php if($notif): ?>
            <div class="alert-success"><?= $notif ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="avatar-section">
                <img src="<?= $_SESSION['user']['photo'] ?>" alt="Profile Photo" class="avatar-img">
                <div class="btn-add-photo">+</div>
            </div>

            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?= $_SESSION['user']['username'] ?>" placeholder="Username" required>
            </div>

            <div class="form-group">
                <label>Bio</label>
                <textarea name="bio" rows="4" maxlength="250" placeholder="Tulis bio kamu..."><?= $_SESSION['user']['bio'] ?></textarea>
                <div class="char-count">(250 char)</div>
            </div>

            <div class="button-group">
                <button type="submit" class="btn btn-save">Save</button>
                <a href="index.php" class="btn btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>