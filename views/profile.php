<?php
/**
 * Profile Module — redesigned to match home.php aesthetic
 */
session_start();

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

    $username     = trim($_POST['username']);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $bio          = trim($_POST['bio']);
    $foto_baru    = null;

    if (empty($username)) {
        $error = "Username tidak boleh kosong.";
    } else {
        $cek = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $cek->bind_param("si", $username, $logged_in_id);
        $cek->execute();
        $cek->store_result();

        if ($cek->num_rows > 0) {
            $error = "Username sudah dipakai orang lain, pilih yang berbeda.";
        } else {
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
                    $dest      = '../uploads/' . $foto_baru;
                    if (!move_uploaded_file($_FILES['foto']['tmp_name'], $dest)) {
                        $error     = "Gagal menyimpan foto. Pastikan folder uploads/ dapat ditulis.";
                        $foto_baru = null;
                    }
                }
            }

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
                $_SESSION['username'] = $username;
                $notif = "Profil berhasil disimpan!";
            }
        }
    }
}

// ── AMBIL DATA USER ───────────────────────────────────────────────────────────
$sql_user = "SELECT username, nama_lengkap, bio, foto FROM users WHERE id = ?";
$st_user  = $conn->prepare($sql_user);
$st_user->bind_param("i", $logged_in_id);
$st_user->execute();
$user = $st_user->get_result()->fetch_assoc();

function foto_url($foto) {
    if (empty($foto) || $foto === 'default.png')
        return 'https://ui-avatars.com/api/?name=U&background=6b3f26&color=ede0d4&size=200';
    return '../uploads/' . htmlspecialchars($foto);
}

function nama_tampil($n, $u) {
    return !empty($n) ? htmlspecialchars($n) : htmlspecialchars($u);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interaksi — Edit Profil</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink:          #1e1008;
            --brown-deep:   #3b2314;
            --brown-mid:    #6b3f26;
            --brown-warm:   #a0674a;
            --brown-light:  #c9a48a;
            --brown-pale:   #e8d5c4;
            --parchment:    #f5ede3;
            --cream:        #faf6f1;
            --white:        #ffffff;
            --muted:        #967560;
            --border:       rgba(107,63,38,0.14);
            --sidebar-w:    72px;
            --panel-w:      340px;
            --success-bg:   #f0ede6;
            --success-fg:   #4a2e14;
            --error-bg:     #fdecea;
            --error-fg:     #7a1c1c;
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; overflow: hidden; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--cream);
            color: var(--ink);
            display: flex;
        }

        /* ── ICON SIDEBAR ── */
        .icon-sidebar {
            width: var(--sidebar-w);
            height: 100vh;
            background: var(--brown-deep);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 28px 0;
            flex-shrink: 0;
            position: relative;
            z-index: 10;
        }

        .logo-mark {
            width: 36px; height: 36px;
            border: 1.5px solid rgba(201,164,138,0.4);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 44px; flex-shrink: 0;
        }

        .logo-mark svg { width: 16px; height: 16px; fill: var(--brown-light); }

        .icon-nav {
            display: flex; flex-direction: column;
            align-items: center; gap: 6px; flex: 1;
        }

        .icon-btn {
            width: 44px; height: 44px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none;
            color: rgba(201,164,138,0.5);
            transition: background 0.2s, color 0.2s;
            position: relative;
        }

        .icon-btn:hover { background: rgba(255,255,255,0.07); color: var(--brown-light); }
        .icon-btn.active { background: rgba(201,164,138,0.15); color: var(--brown-pale); }
        .icon-btn svg { width: 20px; height: 20px; }

        .icon-btn::after {
            content: attr(data-tip);
            position: absolute;
            left: calc(100% + 14px); top: 50%;
            transform: translateY(-50%);
            background: var(--ink); color: var(--cream);
            font-size: 11.5px; font-weight: 500;
            white-space: nowrap; padding: 5px 10px;
            border-radius: 4px; pointer-events: none;
            opacity: 0; transition: opacity 0.15s;
            letter-spacing: 0.04em; z-index: 100;
        }

        .icon-btn:hover::after { opacity: 1; }

        .icon-nav-bottom {
            margin-top: auto;
            display: flex; flex-direction: column;
            align-items: center; gap: 6px;
        }

        .icon-btn.logout { color: rgba(192,100,80,0.6); }
        .icon-btn.logout:hover { background: rgba(192,80,60,0.12); color: #e07060; }

        .create-btn {
            width: 44px; height: 44px;
            background: var(--brown-warm);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; color: var(--white);
            margin-bottom: 10px;
            transition: background 0.2s, transform 0.2s;
            flex-shrink: 0; position: relative;
        }

        .create-btn:hover { background: var(--brown-light); transform: scale(1.06); }
        .create-btn svg { width: 20px; height: 20px; }
        .create-btn::after {
            content: 'Buat Postingan';
            position: absolute; left: calc(100% + 14px); top: 50%;
            transform: translateY(-50%);
            background: var(--ink); color: var(--cream);
            font-size: 11.5px; font-weight: 500;
            white-space: nowrap; padding: 5px 10px;
            border-radius: 4px; pointer-events: none;
            opacity: 0; transition: opacity 0.15s;
            letter-spacing: 0.04em; z-index: 100;
        }
        .create-btn:hover::after { opacity: 1; }

        .sidebar-avatar {
            width: 34px; height: 34px;
            border-radius: 50%; object-fit: cover;
            border: 2px solid rgba(201,164,138,0.3);
            margin-top: 10px;
        }

        /* ── MAIN AREA ── */
        .main-area { flex: 1; display: flex; overflow: hidden; min-width: 0; }

        /* ── CONTENT COLUMN ── */
        .content-col {
            flex: 1; min-width: 0;
            display: flex; flex-direction: column;
            overflow: hidden;
            border-right: 1px solid var(--border);
        }

        /* ── PAGE HEADER ── */
        .page-header {
            padding: 0 44px;
            height: 64px;
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid var(--border);
            background: rgba(250,246,241,0.94);
            backdrop-filter: blur(16px);
            flex-shrink: 0;
        }

        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 22px; font-weight: 700;
            color: var(--brown-deep); letter-spacing: -0.01em;
        }

        .page-title em { font-style: italic; font-weight: 400; color: var(--brown-warm); }

        .back-link {
            display: flex; align-items: center; gap: 7px;
            text-decoration: none;
            font-size: 12.5px; font-weight: 500;
            color: var(--muted); letter-spacing: 0.04em;
            padding: 7px 14px; border-radius: 20px;
            border: 1px solid var(--border);
            background: var(--white);
            transition: all 0.2s;
        }

        .back-link:hover { border-color: var(--brown-warm); color: var(--brown-warm); }
        .back-link svg { width: 14px; height: 14px; }

        /* ── SCROLLABLE BODY ── */
        .content-scroll {
            flex: 1; overflow-y: auto;
            padding: 40px 44px;
            scrollbar-width: thin;
            scrollbar-color: var(--brown-pale) transparent;
        }

        .content-scroll::-webkit-scrollbar { width: 4px; }
        .content-scroll::-webkit-scrollbar-thumb { background: var(--brown-pale); border-radius: 4px; }

        /* ── ALERTS ── */
        .alert {
            display: flex; align-items: flex-start; gap: 12px;
            padding: 16px 20px; border-radius: 8px;
            font-size: 13.5px; font-weight: 400;
            margin-bottom: 28px; border: 1px solid;
            animation: slideIn 0.3s ease both;
        }

        .alert-success {
            background: var(--parchment);
            border-color: rgba(107,63,38,0.2);
            color: var(--brown-deep);
        }

        .alert-error {
            background: #fdecea;
            border-color: rgba(192,60,50,0.2);
            color: #7a1c1c;
        }

        .alert svg { width: 16px; height: 16px; flex-shrink: 0; margin-top: 1px; }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── PROFILE CARD LAYOUT ── */
        .profile-body {
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 48px;
            align-items: start;
        }

        /* ── LEFT: AVATAR SECTION ── */
        .avatar-section { display: flex; flex-direction: column; align-items: center; }

        .avatar-wrap {
            position: relative;
            display: inline-block;
            cursor: pointer;
            margin-bottom: 20px;
        }

        .avatar-wrap img {
            width: 160px; height: 160px;
            border-radius: 50%; object-fit: cover;
            border: 3px solid var(--brown-pale);
            display: block;
            transition: filter 0.25s;
        }

        .avatar-wrap:hover img { filter: brightness(0.75); }

        .avatar-overlay {
            position: absolute; inset: 0;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            border-radius: 50%;
            background: rgba(59,35,20,0.45);
            opacity: 0; transition: opacity 0.25s;
            color: var(--brown-pale);
            font-size: 12px; font-weight: 500;
            letter-spacing: 0.06em; gap: 6px;
        }

        .avatar-wrap:hover .avatar-overlay { opacity: 1; }
        .avatar-overlay svg { width: 22px; height: 22px; }

        #input-foto { display: none; }

        .foto-hint {
            font-size: 11.5px; color: var(--muted);
            text-align: center; line-height: 1.6;
        }

        .divider-vert {
            width: 1px; background: var(--border);
            align-self: stretch; margin: 0 0 0 0;
        }

        /* Preview card */
        .preview-card {
            margin-top: 28px; width: 100%;
            background: var(--parchment); border-radius: 10px;
            border: 1px solid var(--border);
            padding: 20px; text-align: center;
        }

        .preview-card-label {
            font-size: 10px; font-weight: 500;
            letter-spacing: 0.1em; text-transform: uppercase;
            color: var(--brown-light); margin-bottom: 14px;
        }

        .preview-card img {
            width: 48px; height: 48px;
            border-radius: 50%; object-fit: cover;
            border: 2px solid var(--brown-pale);
            margin-bottom: 10px;
        }

        .preview-name-text {
            font-weight: 600; font-size: 14px;
            color: var(--brown-deep); margin-bottom: 2px;
        }

        .preview-handle-text {
            font-size: 12px; font-weight: 300; color: var(--muted);
            margin-bottom: 8px;
        }

        .preview-bio-text {
            font-size: 12px; font-weight: 300;
            color: #5a3d2b; line-height: 1.6;
        }

        /* ── RIGHT: FORM ── */
        .form-section { min-width: 0; }

        .section-eyebrow {
            font-size: 10.5px; font-weight: 500;
            letter-spacing: 0.12em; text-transform: uppercase;
            color: var(--brown-light); margin-bottom: 24px;
        }

        .form-group { margin-bottom: 24px; }

        .form-group label {
            display: block; font-weight: 500;
            font-size: 13px; color: var(--brown-deep);
            margin-bottom: 8px; letter-spacing: 0.01em;
        }

        .field-required { color: var(--brown-warm); margin-left: 2px; }

        .form-input,
        .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--white);
            font-family: 'DM Sans', sans-serif;
            font-size: 14px; font-weight: 300;
            color: var(--ink); outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-input:focus,
        .form-textarea:focus {
            border-color: var(--brown-warm);
            box-shadow: 0 0 0 3px rgba(160,103,74,0.1);
            background: var(--white);
        }

        .form-input::placeholder,
        .form-textarea::placeholder { color: var(--brown-light); }

        .form-textarea { resize: vertical; min-height: 110px; line-height: 1.7; }

        .field-hint {
            font-size: 11.5px; font-weight: 300;
            color: var(--muted); margin-top: 6px;
        }

        .char-row {
            display: flex; justify-content: flex-end;
            font-size: 11.5px; color: var(--brown-light);
            margin-top: 5px;
        }

        .char-row .count { font-weight: 500; color: var(--muted); }

        /* ── FORM FOOTER ── */
        .form-footer {
            display: flex; align-items: center; gap: 12px;
            margin-top: 36px; padding-top: 28px;
            border-top: 1px solid var(--border);
        }

        .btn-save {
            padding: 12px 28px;
            background: var(--brown-deep); color: var(--white);
            border: none; border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 12px; font-weight: 500;
            letter-spacing: 0.1em; text-transform: uppercase;
            cursor: pointer; transition: background 0.2s;
        }

        .btn-save:hover { background: var(--brown-mid); }

        .btn-cancel {
            padding: 12px 22px;
            background: var(--parchment); color: var(--muted);
            border: 1px solid var(--border); border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 12px; font-weight: 500;
            letter-spacing: 0.08em; text-transform: uppercase;
            text-decoration: none; cursor: pointer;
            transition: border-color 0.2s, color 0.2s;
        }

        .btn-cancel:hover { border-color: var(--brown-warm); color: var(--brown-warm); }

        /* ── RIGHT PANEL ── */
        .right-panel {
            width: var(--panel-w); flex-shrink: 0;
            display: flex; flex-direction: column;
            overflow: hidden; background: var(--white);
        }

        .panel-eyebrow {
            height: 64px; padding: 0 28px;
            display: flex; align-items: center;
            border-bottom: 1px solid var(--border); flex-shrink: 0;
        }

        .panel-eyebrow span {
            font-family: 'Playfair Display', serif;
            font-size: 13px; font-style: italic;
            color: var(--muted); letter-spacing: 0.04em;
        }

        .panel-block {
            padding: 24px 28px;
            border-bottom: 1px solid var(--border);
        }

        .panel-block-title {
            font-family: 'Playfair Display', serif;
            font-size: 15px; font-weight: 700;
            color: var(--brown-deep); margin-bottom: 14px;
        }

        .info-row {
            display: flex; align-items: flex-start; gap: 10px;
            margin-bottom: 12px;
        }

        .info-row svg {
            width: 15px; height: 15px;
            color: var(--brown-warm); flex-shrink: 0; margin-top: 2px;
        }

        .info-row p {
            font-size: 13px; font-weight: 300;
            color: var(--muted); line-height: 1.6;
        }

        .tip-list { display: flex; flex-direction: column; gap: 10px; }

        .tip-item {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 10px 12px; border-radius: 7px;
            background: var(--parchment);
        }

        .tip-item svg { width: 14px; height: 14px; color: var(--brown-warm); flex-shrink: 0; margin-top: 2px; }
        .tip-item p { font-size: 12.5px; font-weight: 300; color: var(--ink); line-height: 1.6; }

        /* ── RESPONSIVE ── */
        @media (max-width: 1100px) { :root { --panel-w: 260px; } }
        @media (max-width: 900px) {
            .right-panel { display: none; }
            .profile-body { grid-template-columns: 1fr; }
            .avatar-section { align-items: flex-start; flex-direction: row; gap: 24px; }
        }
        @media (max-width: 600px) {
            :root { --sidebar-w: 56px; }
            .content-scroll { padding: 24px 16px; }
            .page-header { padding: 0 16px; }
        }
    </style>
</head>
<body>

<!-- ICON SIDEBAR -->
<aside class="icon-sidebar">
    <div class="logo-mark">
        <svg viewBox="0 0 24 24"><path d="M12 2L2 7v10l10 5 10-5V7L12 2zm0 2.18L20 8.5v7L12 19.82 4 15.5v-7L12 4.18z"/></svg>
    </div>

    <nav class="icon-nav">
        <a href="home.php" class="icon-btn" data-tip="Beranda">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
        </a>
        <a href="post_create.php" class="icon-btn" data-tip="Buat Postingan">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
        </a>
        <a href="user_profile.php" class="icon-btn active" data-tip="Profil Saya">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
        </a>
    </nav>
    <div class="icon-nav-bottom">
        <a href="logout.php" class="icon-btn logout" data-tip="Keluar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
        </a>
        <img src="<?= foto_url($user['foto']) ?>" class="sidebar-avatar" alt="me">
    </div>
</aside>

<!-- MAIN AREA -->
<div class="main-area">

    <!-- CONTENT COLUMN -->
    <div class="content-col">

        <!-- PAGE HEADER -->
        <div class="page-header">
            <h1 class="page-title">Edit <em>Profil</em></h1>
            <a href="home.php" class="back-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
                Kembali ke Beranda
            </a>
        </div>

        <!-- SCROLL AREA -->
        <div class="content-scroll">

            <?php if ($notif): ?>
            <div class="alert alert-success">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                <?= htmlspecialchars($notif) ?>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="profile-body">

                    <!-- KIRI: AVATAR + PREVIEW -->
                    <div class="avatar-section">
                        <div class="avatar-wrap" onclick="document.getElementById('input-foto').click()">
                            <img src="<?= foto_url($user['foto']) ?>"
                                 id="preview-avatar"
                                 alt="Foto Profil">
                            <div class="avatar-overlay">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                                    <circle cx="12" cy="13" r="4"/>
                                </svg>
                                Ganti Foto
                            </div>
                        </div>

                        <input type="file" name="foto" id="input-foto"
                               accept="image/jpeg,image/png,image/webp,image/gif"
                               onchange="previewFoto(this)">

                        <p class="foto-hint">Klik foto untuk mengganti<br>Maks. 2MB · JPG, PNG, WEBP, GIF</p>

                        <!-- Live Preview Card -->
                        <div class="preview-card">
                            <div class="preview-card-label">Tampilan di Feed</div>
                            <img src="<?= foto_url($user['foto']) ?>" id="card-avatar" alt="">
                            <div class="preview-name-text" id="prev-name">
                                <?= nama_tampil($user['nama_lengkap'], $user['username']) ?>
                            </div>
                            <div class="preview-handle-text" id="prev-uname">
                                @<?= htmlspecialchars($user['username']) ?>
                            </div>
                            <div class="preview-bio-text" id="prev-bio">
                                <?= htmlspecialchars($user['bio'] ?? '') ?>
                            </div>
                        </div>
                    </div>

                    <!-- KANAN: FORM -->
                    <div class="form-section">
                        <div class="section-eyebrow">Informasi Akun</div>

                        <div class="form-group">
                            <label for="inp-username">
                                Username<span class="field-required">*</span>
                            </label>
                            <input type="text"
                                   id="inp-username"
                                   name="username"
                                   class="form-input"
                                   value="<?= htmlspecialchars($user['username']) ?>"
                                   maxlength="50"
                                   required
                                   oninput="document.getElementById('prev-uname').textContent = '@' + this.value">
                            <p class="field-hint">Hanya huruf, angka, dan underscore. Maks. 50 karakter.</p>
                        </div>

                        <div class="form-group">
                            <label for="inp-nama">Nama Lengkap</label>
                            <input type="text"
                                   id="inp-nama"
                                   name="nama_lengkap"
                                   class="form-input"
                                   value="<?= htmlspecialchars($user['nama_lengkap'] ?? '') ?>"
                                   maxlength="100"
                                   oninput="document.getElementById('prev-name').textContent = this.value || document.getElementById('inp-username').value">
                            <p class="field-hint">Ditampilkan di feed jika diisi. Maks. 100 karakter.</p>
                        </div>

                        <div class="form-group">
                            <label for="inp-bio">Bio</label>
                            <textarea id="inp-bio"
                                      name="bio"
                                      class="form-textarea"
                                      maxlength="250"
                                      oninput="updateCharCount(this); document.getElementById('prev-bio').textContent = this.value"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                            <div class="char-row">
                                <span class="count" id="char-count"><?= mb_strlen($user['bio'] ?? '') ?></span>&nbsp;/ 250
                            </div>
                        </div>

                        <div class="form-footer">
                            <button type="submit" class="btn-save">Simpan Perubahan</button>
                            <a href="home.php" class="btn-cancel">Batal</a>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

    <!-- RIGHT PANEL -->
    <aside class="right-panel">
        <div class="panel-eyebrow">
            <span>Panduan</span>
        </div>

        <div class="panel-block">
            <div class="panel-block-title">Tentang Profil</div>
            <div class="info-row">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <p>Profil kamu tampil di setiap postingan yang dibuat di feed Interaksi.</p>
            </div>
            <div class="info-row">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="16" x2="12" y2="12"/>
                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                </svg>
                <p>Perubahan akan langsung berlaku setelah disimpan.</p>
            </div>
        </div>

        <div class="panel-block">
            <div class="panel-block-title">Tips Profil Bagus</div>
            <div class="tip-list">
                <div class="tip-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    <p>Gunakan foto profil yang jelas dan terang agar mudah dikenali.</p>
                </div>
                <div class="tip-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    <p>Bio singkat padat lebih menarik — ceritakan keahlian atau minat kamu.</p>
                </div>
                <div class="tip-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    <p>Username yang mudah diingat membantu orang lain menemukanmu.</p>
                </div>
            </div>
        </div>
    </aside>

</div>

<script>
function previewFoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-avatar').src = e.target.result;
            document.getElementById('card-avatar').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function updateCharCount(el) {
    document.getElementById('char-count').textContent = el.value.length;
}
</script>
</body>
</html>