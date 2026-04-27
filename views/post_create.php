<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../config/database.php';

$user_id = $_SESSION['user_id'];

// Ambil data user yang login
$sql_user = "SELECT username, nama_lengkap, foto FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
$user_data = $result_user->fetch_assoc();

$error_message = '';

// Proses simpan postingan jika ada POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isi_postingan = trim($_POST['isi_postingan'] ?? '');
    
    if (empty($isi_postingan)) {
        $error_message = "Postingan tidak boleh kosong!";
    } else {
        // Proses upload gambar
        $gambar_name = null;
        
        if (isset($_FILES['gambar_post']) && $_FILES['gambar_post']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            $max_size = 5 * 1024 * 1024;
            
            $file_type = $_FILES['gambar_post']['type'];
            $file_size = $_FILES['gambar_post']['size'];
            $file_tmp = $_FILES['gambar_post']['tmp_name'];
            
            if (!in_array($file_type, $allowed_types)) {
                $error_message = "Format gambar tidak didukung. Gunakan JPG, PNG, atau GIF.";
            } elseif ($file_size > $max_size) {
                $error_message = "Ukuran gambar maksimal 5MB.";
            } else {
                $extension = pathinfo($_FILES['gambar_post']['name'], PATHINFO_EXTENSION);
                $gambar_name = time() . '_' . uniqid() . '.' . $extension;
                $upload_path = '../uploads/' . $gambar_name;
                
                if (!is_dir('../uploads')) {
                    mkdir('../uploads', 0777, true);
                }
                
                if (!move_uploaded_file($file_tmp, $upload_path)) {
                    $error_message = "Gagal mengupload gambar.";
                    $gambar_name = null;
                }
            }
        }
        
        // Simpan ke database jika tidak ada error
        if (empty($error_message)) {
            $sql_insert = "INSERT INTO posts (user_id, content, image, created_at) VALUES (?, ?, ?, NOW())";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("iss", $user_id, $isi_postingan, $gambar_name);
            
            if ($stmt_insert->execute()) {
                // Redirect ke home
                header("Location: home.php");
                exit;
            } else {
                $error_message = "Gagal menyimpan: " . $conn->error;
                // Hapus gambar jika gagal simpan
                if ($gambar_name && file_exists('../uploads/' . $gambar_name)) {
                    unlink('../uploads/' . $gambar_name);
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Postingan Baru / Interaksi</title>
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #fdfbfb 0%, #e3f2fd 100%);
            --navy: #1a2a6c;
            --soft-blue: #85a3db;
            --white: #ffffff;
            --error-red: #ff6b6b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Inter', -apple-system, sans-serif; 
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .post-card {
            background: var(--white);
            width: 100%;
            max-width: 600px;
            padding: 35px;
            border-radius: 32px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.06);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .header h2 {
            font-size: 22px;
            color: var(--navy);
            font-weight: 800;
        }

        .close-btn {
            text-decoration: none;
            color: #ccc;
            font-size: 28px;
            font-weight: bold;
            transition: 0.3s;
        }
        .close-btn:hover { color: #ff6b6b; }

        .user-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .avatar-preview {
            width: 40px;
            height: 40px;
            background: #f0f7ff;
            border-radius: 12px;
        }

        textarea {
            width: 100%;
            height: 200px;
            border: 1px solid #e0e0e0;
            outline: none;
            font-size: 16px;
            font-family: inherit;
            color: #333;
            resize: vertical;
            line-height: 1.6;
            padding: 12px;
            border-radius: 12px;
        }

        textarea:focus {
            border-color: var(--soft-blue);
        }

        .footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-upload {
            background: #f0f7ff;
            color: var(--soft-blue);
            padding: 10px 20px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            display: inline-block;
        }
        .btn-upload:hover { background: #e1efff; }

        .btn-post {
            background: var(--navy);
            color: white;
            border: none;
            padding: 12px 35px;
            border-radius: 16px;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(26, 42, 108, 0.2);
            transition: 0.3s;
        }

        .btn-post:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(26, 42, 108, 0.3);
        }

        .hint {
            font-size: 12px;
            color: #bbb;
            margin-top: 5px;
        }

        .alert-error {
            background: #ffe3e3;
            color: #c92a2a;
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid #c92a2a;
            font-size: 14px;
        }
    </style>
</head>
<body>

    <div class="post-card">
        <div class="header">
            <h2>Buat Postingan</h2>
            <a href="home.php" class="close-btn">&times;</a>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert-error">
                ⚠️ <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="user-row">
                <div class="avatar-preview"></div>
                <span style="font-weight: 600; color: #555;">
                    <?= htmlspecialchars($user_data['nama_lengkap'] ?? $user_data['username']) ?>
                </span>
            </div>

            <textarea name="isi_postingan" placeholder="Apa yang sedang hangat hari ini?" required autofocus></textarea>
            
            <p class="hint">Maksimal 250 karakter</p>

            <div class="footer">
                <label class="btn-upload">
                    📷 Tambah Gambar
                    <input type="file" name="gambar_post" accept="image/*" style="display: none;">
                </label>

                <button type="submit" class="btn-post">Posting</button>
            </div>
        </form>
    </div>

    <script>
        // Optional: Tampilkan nama file yang dipilih
        const fileInput = document.querySelector('input[type="file"]');
        const uploadLabel = document.querySelector('.btn-upload');
        
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                uploadLabel.innerHTML = `📷 ${this.files[0].name.substring(0, 20)}`;
            } else {
                uploadLabel.innerHTML = '📷 Tambah Gambar';
            }
        });
    </script>

</body>
</html>