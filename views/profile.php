<?php
/**
 * Case Study: MicroSocial - Profile Module (Website Version)
 */
session_start();

// Jika belum login, redirect ke login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../config/database.php';

$logged_in_id = $_SESSION['user_id'];
$notif        = "";
$error        = "";

// ── PROSES SIMPAN PROFIL ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username    = trim($_POST['username']);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $bio         = trim($_POST['bio']);
    $foto_baru   = null;

    // Validasi username tidak boleh kosong
    if (empty($username)) {
        $error = "Username tidak boleh kosong.";
    } else {

        // Cek apakah username sudah dipakai user lain
        $cek = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $cek->bind_param("si", $username, $logged_in_id);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            $error = "Username sudah dipakai orang lain, pilih yang berbeda.";
        } else {

            // ── Upload foto jika ada ──────────────────────────────────────────
            if (!empty($_FILES['foto']['name'])) {
                $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                $ftype   = $_FILES['foto']['type'];
                $fsize   = $_FILES['foto']['size'];

                if (!in_array($ftype, $allowed)) {
                    $error = "Format foto tidak didukung. Gunakan JPG, PNG, WEBP, atau GIF.";
                } elseif ($fsize > 2 * 1024 * 1024) {
                    $error = "Ukuran foto maksimal 2MB.";
                } else {
                    $ext       = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                    $foto_baru = 'user_' . $logged_in_id . '_' . time() . '.' . $ext;
                    $dest      = 'uploads/' . $foto_baru;

                    if (!move_uploaded_file($_FILES['foto']['tmp_name'], $dest)) {
                        $error     = "Gagal menyimpan foto. Pastikan folder uploads/ dapat ditulis.";
                        $foto_baru = null;
                    }
                }
            }

            // Simpan ke DB jika tidak ada error
            if (empty($error)) {
                if ($foto_baru) {
                    $sql = "UPDATE users SET username=?, nama_lengkap=?, bio=?, foto=? WHERE id=?";
                    $st  = $conn->prepare($sql);
                    $st->bind_param("ssssi", $username, $nama_lengkap, $bio, $foto_baru, $logged_in_id);
                } else {
                    $sql = "UPDATE users SET username=?, nama_lengkap=?, bio=? WHERE id=?";
                    $st  = $conn->prepare($sql);
                    $st->bind_param("sssi", $username, $nama_lengkap, $bio, $logged_in_id);
                }
                $st->execute();

                // Update session username
                $_SESSION['username'] = $username;
                $notif = "Profil berhasil disimpan!";
            }
        }
    }
}

// ── AMBIL DATA USER SAAT INI ──────────────────────────────────────────────────
$sql_user = "SELECT username, nama_lengkap, bio, foto FROM users WHERE id = ?";
$st_user  = $conn->prepare($sql_user);
$st_user->bind_param("i", $logged_in_id);
$st_user->execute();
$user = $st_user->get_result()->fetch_assoc();

// Helper foto
function foto_url($foto) {
    if (empty($foto) || $foto === 'default.png') {
        return 'https://ui-avatars.com/api/?name=U&background=1d4e89&color=fff&size=200';
    }
    return 'uploads/' . htmlspecialchars($foto);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile – MicroSocial</title>
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* ── NAVBAR ──────────────────────────────────────── */
        .navbar {
            width: 100%;
            background-color: #1d4e89;
            padding: 12px 0;
            color: white;
            text-align: center;
            font-weight: bold;
            font-size: 18px;
            letter-spacing: 1px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
        }
        .navbar a {
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            font-size: 14px;
            font-weight: normal;
            transition: 0.2s;
        }
        .navbar a:hover { color: white; }

        /* ── CONTAINER UTAMA ─────────────────────────────── */
        .main-container {
            width: 100%;
            max-width: 850px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 40px;
        }

        .profile-header {
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
            padding: 22px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .profile-header h2 { margin: 0; color: #1d4e89; font-size: 22px; }

        /* ── BODY KONTEN ─────────────────────────────────── */
        .content-body {
            padding: 40px;
            display: flex;
            gap: 50px;
        }

        /* ── SISI KIRI: FOTO ─────────────────────────────── */
        .photo-section { flex: 1; text-align: center; }

        .avatar-container {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }
        .avatar-img {
            width: 190px; height: 190px;
            border-radius: 50%;
            border: 5px solid #1d4e89;
            object-fit: cover;
            display: block;
            transition: 0.3s;
        }
        .avatar-container:hover .avatar-img { filter: brightness(0.8); }
        .avatar-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            opacity: 0;
            transition: 0.3s;
            background: rgba(29,78,137,0.4);
            color: white;
            font-size: 14px;
            font-weight: bold;
        }
        .avatar-container:hover .avatar-overlay { opacity: 1; }

        /* Input file tersembunyi */
        #input-foto { display: none; }

        .foto-hint {
            font-size: 12px;
            color: #999;
            margin-top: 12px;
        }

        /* Preview nama di bawah foto */
        .preview-name {
            margin-top: 20px;
            font-weight: 700;
            font-size: 17px;
            color: #1d4e89;
        }
        .preview-username {
            font-size: 13px;
            color: #aaa;
            margin-top: 4px;
        }
        .preview-bio {
            font-size: 13px;
            color: #666;
            margin-top: 8px;
            line-height: 1.5;
        }

        /* ── SISI KANAN: FORM ────────────────────────────── */
        .form-section { flex: 2; }

        .form-group { margin-bottom: 22px; }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
            font-size: 14px;
        }
        .form-group .hint {
            font-size: 11px;
            color: #aaa;
            margin-top: 5px;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 13px 15px;
            border: 1.5px solid #ddd;
            border-radius: 10px;
            font-size: 15px;
            background: #fafafa;
            transition: 0.2s;
            font-family: inherit;
        }
        input:focus, textarea:focus {
            outline: none;
            border-color: #1d4e89;
            background: white;
            box-shadow: 0 0 0 3px rgba(29,78,137,0.08);
        }

        /* Karakter sisa */
        .char-counter { text-align: right; font-size: 12px; color: #bbb; margin-top: 5px; }

        /* Tombol aksi */
        .footer-actions {
            margin-top: 30px;
            display: flex;
            gap: 15px;
        }
        .btn {
            padding: 13px 32px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            font-size: 15px;
            transition: 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-save {
            background-color: #1d4e89;
            color: white;
        }
        .btn-save:hover { background-color: #163d6e; }
        .btn-cancel {
            background-color: #e4e6eb;
            color: #4b4b4b;
        }
        .btn-cancel:hover { background-color: #d0d3db; }

        /* Alert */
        .alert-success {
            background: #d4edda;
            padding: 14px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            color: #155724;
            font-size: 14px;
            border-left: 4px solid #28a745;
        }
        .alert-error {
            background: #fdecea;
            padding: 14px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            color: #8b1a1a;
            font-size: 14px;
            border-left: 4px solid #dc3545;
        }

        /* Divider info */
        .section-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #bbb;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="navbar">
    <span>MICROSOCIAL WEB</span>
    <a href="home.php">← Kembali ke Beranda</a>
</div>

<div class="main-container">
    <div class="profile-header">
        <h2>✏️ Edit Profil</h2>
        <span style="font-size:13px;color:#aaa;">@<?= htmlspecialchars($user['username']) ?></span>
    </div>

    <?php if ($notif): ?>
    <div style="padding: 0 40px; padding-top: 20px;">
        <div class="alert-success">✅ <?= htmlspecialchars($notif) ?></div>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div style="padding: 0 40px; padding-top: 20px;">
        <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    </div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="content-body">

            <!-- ── FOTO PROFIL ────────────────────────────────────── -->
            <div class="photo-section">
                <div class="avatar-container" onclick="document.getElementById('input-foto').click()">
                    <img src="<?= foto_url($user['foto']) ?>"
                         class="avatar-img"
                         id="preview-avatar"
                         alt="Foto Profil">
                    <div class="avatar-overlay">📷 Ganti Foto</div>
                </div>

                <input type="file" name="foto" id="input-foto"
                       accept="image/jpeg,image/png,image/webp,image/gif"
                       onchange="previewFoto(this)">

                <p class="foto-hint">Klik foto untuk mengganti<br>Maks. 2MB · JPG, PNG, WEBP, GIF</p>

                <!-- Preview nama live -->
                <div class="preview-name" id="prev-name">
                    <?= htmlspecialchars(!empty($user['nama_lengkap']) ? $user['nama_lengkap'] : $user['username']) ?>
                </div>
                <div class="preview-username" id="prev-uname">
                    @<?= htmlspecialchars($user['username']) ?>
                </div>
                <div class="preview-bio" id="prev-bio">
                    <?= htmlspecialchars($user['bio'] ?? '') ?>
                </div>
            </div>

            <!-- ── FORM EDIT ──────────────────────────────────────── -->
            <div class="form-section">
                <div class="section-title">Informasi Akun</div>

                <div class="form-group">
                    <label for="inp-username">Username *</label>
                    <input type="text"
                           id="inp-username"
                           name="username"
                           value="<?= htmlspecialchars($user['username']) ?>"
                           maxlength="50"
                           required
                           oninput="document.getElementById('prev-uname').textContent = '@' + this.value">
                    <div class="hint">Hanya huruf, angka, dan underscore. Maks. 50 karakter.</div>
                </div>

                <div class="form-group">
                    <label for="inp-nama">Nama Lengkap</label>
                    <input type="text"
                           id="inp-nama"
                           name="nama_lengkap"
                           value="<?= htmlspecialchars($user['nama_lengkap'] ?? '') ?>"
                           maxlength="100"
                           oninput="document.getElementById('prev-name').textContent = this.value || document.getElementById('inp-username').value">
                    <div class="hint">Ditampilkan di feed jika diisi. Maks. 100 karakter.</div>
                </div>

                <div class="form-group">
                    <label for="inp-bio">Bio</label>
                    <textarea id="inp-bio"
                              name="bio"
                              rows="5"
                              maxlength="250"
                              oninput="updateCharCount(this); document.getElementById('prev-bio').textContent = this.value"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                    <div class="char-counter"><span id="char-count"><?= mb_strlen($user['bio'] ?? '') ?></span> / 250</div>
                </div>

                <div class="footer-actions">
                    <button type="submit" class="btn btn-save">💾 Simpan Perubahan</button>
                    <a href="home.php" class="btn btn-cancel">Batal</a>
                </div>
            </div>

        </div>
    </form>
</div>

<script>
// Preview foto sebelum upload
function previewFoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-avatar').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Hitung karakter bio
function updateCharCount(el) {
    document.getElementById('char-count').textContent = el.value.length;
}
</script>

</body>
</html>