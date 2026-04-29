<?php
/**
 * Post Create Module — elegantly matching profile.php aesthetic
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

// ── AMBIL DATA USER ───────────────────────────────────────────────────────────
$sql_user = "SELECT username, nama_lengkap, foto FROM users WHERE id = ?";
$st_user  = $conn->prepare($sql_user);
$st_user->bind_param("i", $logged_in_id);
$st_user->execute();
$user = $st_user->get_result()->fetch_assoc();

// ── PROSES BUAT POST ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $judul       = trim($_POST['judul']);
    $konten      = trim($_POST['konten']);
    $gambar_baru = null;

    if (empty($judul)) {
        $error = "Judul post tidak boleh kosong.";
    } elseif (empty($konten)) {
        $error = "Konten post tidak boleh kosong.";
    } else {
        if (!empty($_FILES['gambar']['name'])) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $ftype   = $_FILES['gambar']['type'];
            $fsize   = $_FILES['gambar']['size'];

            if (!in_array($ftype, $allowed)) {
                $error = "Format gambar tidak didukung. Gunakan JPG, PNG, WEBP, atau GIF.";
            } elseif ($fsize > 5 * 1024 * 1024) {
                $error = "Ukuran gambar maksimal 5MB.";
            } else {
                $ext         = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
                $gambar_baru = 'post_' . $logged_in_id . '_' . time() . '.' . $ext;
              $dest = '../uploads/' . $gambar_baru;
                if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $dest)) {
                    $error       = "Gagal menyimpan gambar. Pastikan folder uploads/ dapat ditulis.";
                    $gambar_baru = null;
                }
            }
        }

        if (empty($error)) {
           $sql = "INSERT INTO posts (user_id, judul, content, image, created_at)
            VALUES (?, ?, ?, ?, NOW())";
            $st  = $conn->prepare($sql);
            $st->bind_param("isss", $logged_in_id, $judul, $konten, $gambar_baru);
            $st->execute();
            $notif = "Post berhasil dipublikasikan!";
            // reset form after success? optional: redirect with success
            // header("Location: home.php?notif=post_success");
            // exit;
        }
    }
}

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
    <title>Interaksi — Buat Postingan Baru</title>
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

        /* ── POST CARD LAYOUT ── */
        .post-editor {
            max-width: 680px;
            margin: 0 auto;
        }

        /* AUTHOR ROW like profile preview card */
        .author-row {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--border);
        }

        .author-avatar img {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--brown-pale);
        }

        .author-info {
            flex: 1;
        }

        .author-name {
            font-weight: 600;
            font-size: 15px;
            color: var(--brown-deep);
            margin-bottom: 2px;
        }

        .author-handle {
            font-size: 13px;
            font-weight: 300;
            color: var(--muted);
        }

        .visibility-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--parchment);
            color: var(--brown-warm);
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 0.03em;
        }

        /* ── FORM FIELDS ── */
        .form-group { margin-bottom: 28px; }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--brown-deep);
            margin-bottom: 8px;
            letter-spacing: 0.01em;
        }

        .field-required { color: var(--brown-warm); margin-left: 2px; }

        .form-input,
        .form-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            font-weight: 300;
            color: var(--ink);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: var(--white);
        }

        .form-input:focus,
        .form-textarea:focus {
            border-color: var(--brown-warm);
            box-shadow: 0 0 0 3px rgba(160,103,74,0.1);
        }

        .form-input::placeholder,
        .form-textarea::placeholder { color: var(--brown-light); }

        .form-textarea {
            resize: vertical;
            min-height: 200px;
            line-height: 1.7;
        }

        .char-row {
            display: flex;
            justify-content: flex-end;
            font-size: 11.5px;
            color: var(--brown-light);
            margin-top: 6px;
        }

        .char-row .count { font-weight: 500; color: var(--muted); }

        /* ── IMAGE UPLOAD (matching profile photo style) ── */
        .image-upload-area {
            border: 2px dashed var(--border);
            border-radius: 16px;
            padding: 28px 20px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
            position: relative;
            background: var(--parchment);
        }

        .image-upload-area:hover {
            border-color: var(--brown-warm);
            background: rgba(250,246,241,0.8);
        }

        .image-upload-area.has-image {
            padding: 0;
            border-style: solid;
            border-color: var(--brown-pale);
            background: transparent;
            overflow: hidden;
        }

        .upload-icon {
            font-size: 32px;
            margin-bottom: 12px;
            opacity: 0.7;
        }

        .upload-label {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.5;
        }

        .upload-label strong {
            color: var(--brown-warm);
            font-weight: 500;
        }

        #preview-gambar {
            width: 100%;
            max-height: 320px;
            object-fit: cover;
            border-radius: 14px;
            display: none;
        }

        .remove-image-btn {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(107,63,38,0.9);
            color: white;
            border: none;
            border-radius: 30px;
            padding: 5px 12px;
            font-size: 11px;
            font-weight: 500;
            cursor: pointer;
            display: none;
            backdrop-filter: blur(4px);
            transition: background 0.2s;
        }

        .remove-image-btn:hover { background: var(--error-fg); }

        #input-gambar { display: none; }

        /* ── FOOTER ── */
        .form-footer {
            margin-top: 36px;
            padding-top: 28px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .btn-cancel {
            padding: 12px 24px;
            background: var(--parchment);
            color: var(--muted);
            border: 1px solid var(--border);
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            text-decoration: none;
            cursor: pointer;
            transition: border-color 0.2s, color 0.2s;
        }

        .btn-cancel:hover { border-color: var(--brown-warm); color: var(--brown-warm); }

        .btn-publish {
            padding: 12px 32px;
            background: var(--brown-deep);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-publish:hover { background: var(--brown-mid); }

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
            .content-scroll { padding: 28px 24px; }
        }
        @media (max-width: 600px) {
            :root { --sidebar-w: 56px; }
            .content-scroll { padding: 20px 16px; }
            .page-header { padding: 0 16px; }
        }
    </style>
</head>
<body>

<!-- ICON SIDEBAR (sama persis dengan profile.php) -->
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
        <a href="#" class="icon-btn" data-tip="Jelajahi">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
        </a>
        <a href="#" class="icon-btn" data-tip="Notifikasi">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
        </a>
        <a href="profile.php" class="icon-btn" data-tip="Profil Saya">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
        </a>
    </nav>

    <div class="icon-nav-bottom">
        <a href="post_create.php" class="create-btn active">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
        </a>
        <a href="../views/logout.php" class="icon-btn logout" data-tip="Keluar">
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
            <h1 class="page-title">Buat <em>Postingan</em></h1>
            <a href="home.php" class="back-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
                Kembali ke Beranda
            </a>
        </div>

        <!-- SCROLL AREA -->
        <div class="content-scroll">

            <div class="post-editor">

                <?php if (!empty($notif)): ?>
                <div class="alert alert-success">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    <?= htmlspecialchars($notif) ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">

                    <!-- AUTHOR ROW (mirip profile preview) -->
                    <div class="author-row">
                        <div class="author-avatar">
                            <img src="<?= foto_url($user['foto']) ?>" alt="Foto Profil">
                        </div>
                        <div class="author-info">
                            <div class="author-name">
                                <?= nama_tampil($user['nama_lengkap'], $user['username']) ?>
                            </div>
                            <div class="author-handle">
                                @<?= htmlspecialchars($user['username']) ?>
                            </div>
                        </div>
                        <div class="visibility-badge">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M22 12c0 5.52-4.48 10-10 10S2 17.52 2 12 6.48 2 12 2s10 4.48 10 10z"/>
                            </svg>
                            Publik
                        </div>
                    </div>

                    <!-- JUDUL -->
                    <div class="form-group">
                        <label for="inp-judul">Judul Postingan<span class="field-required">*</span></label>
                        <input type="text"
                               id="inp-judul"
                               name="judul"
                               class="form-input"
                               placeholder="Tulis judul yang menarik..."
                               maxlength="150"
                               required
                               oninput="updateJudulCount(this)">
                        <div class="char-row">
                            <span class="count" id="judul-count">0</span>&nbsp;/ 150
                        </div>
                    </div>

                    <!-- KONTEN -->
                    <div class="form-group">
                        <label for="inp-konten">Isi Postingan<span class="field-required">*</span></label>
                        <textarea id="inp-konten"
                                  name="konten"
                                  class="form-textarea"
                                  maxlength="5000"
                                  placeholder="Tulis isi postmu di sini..."
                                  required
                                  oninput="updateKontenCount(this)"></textarea>
                        <div class="char-row">
                            <span class="count" id="konten-count">0</span>&nbsp;/ 5000
                        </div>
                    </div>

                    <!-- GAMBAR (opsional, gaya upload area yg lebih lembut) -->
                    <div class="form-group">
                        <label>Gambar <span style="font-weight:300; color:var(--muted);">(opsional, maks. 5MB)</span></label>
                        <div class="image-upload-area" id="upload-area"
                             onclick="document.getElementById('input-gambar').click()">
                            <div class="upload-icon">🖼️</div>
                            <div class="upload-label">
                                Klik atau seret gambar ke sini<br>
                                <strong>JPG, PNG, WEBP, GIF</strong>
                            </div>
                            <img id="preview-gambar" src="" alt="Preview Gambar">
                            <button type="button" class="remove-image-btn" id="remove-btn"
                                    onclick="removeImage(event)">✕ Hapus</button>
                        </div>
                        <input type="file" name="gambar" id="input-gambar"
                               accept="image/jpeg,image/png,image/webp,image/gif"
                               onchange="previewGambar(this)">
                    </div>

                    <!-- FOOTER -->
                    <div class="form-footer">
                        <a href="home.php" class="btn-cancel">Batal</a>
                        <button type="submit" class="btn-publish">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="22" y1="2" x2="11" y2="13"/>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                            </svg>
                            Publikasikan
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <!-- RIGHT PANEL (tips membuat postingan, sama seperti profile) -->
    <aside class="right-panel">
        <div class="panel-eyebrow">
            <span>Panduan Menulis</span>
        </div>

        <div class="panel-block">
            <div class="panel-block-title">Tips Postingan Menarik</div>
            <div class="info-row">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 12v-1a5 5 0 0 0-5-5H9a5 5 0 0 0-5 5v1"/>
                    <rect x="3" y="12" width="18" height="8" rx="2"/>
                </svg>
                <p>Judul yang jelas dan menarik membuat orang penasaran untuk membaca.</p>
            </div>
            <div class="info-row">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                </svg>
                <p>Gunakan paragraf pendek agar mudah dibaca di layar.</p>
            </div>
            <div class="info-row">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="2" width="20" height="20" rx="2.18"/>
                    <circle cx="8.5" cy="8.5" r="1.5"/>
                    <circle cx="15.5" cy="8.5" r="1.5"/>
                    <circle cx="8.5" cy="15.5" r="1.5"/>
                    <circle cx="15.5" cy="15.5" r="1.5"/>
                </svg>
                <p>Gambar bisa membuat postinganmu lebih hidup dan menarik perhatian.</p>
            </div>
        </div>

        <div class="panel-block">
            <div class="panel-block-title">Etika Berbagi</div>
            <div class="tip-list">
                <div class="tip-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    <p>Hormati sesama pengguna — jangan posting konten negatif atau SARA.</p>
                </div>
                <div class="tip-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    <p>Pastikan konten yang kamu bagikan bermanfaat atau menginspirasi.</p>
                </div>
                <div class="tip-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    <p>Jika menggunakan gambar dari sumber lain, cantumkan kreditnya.</p>
                </div>
            </div>
        </div>
    </aside>

</div>

<script>
function previewGambar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('preview-gambar');
            const area    = document.getElementById('upload-area');
            const removeBtn = document.getElementById('remove-btn');
            preview.src = e.target.result;
            preview.style.display = 'block';
            area.classList.add('has-image');
            removeBtn.style.display = 'block';
            area.querySelector('.upload-icon').style.display = 'none';
            area.querySelector('.upload-label').style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function removeImage(e) {
    e.stopPropagation();
    const input   = document.getElementById('input-gambar');
    const preview = document.getElementById('preview-gambar');
    const area    = document.getElementById('upload-area');
    const removeBtn = document.getElementById('remove-btn');
    input.value = '';
    preview.src = '';
    preview.style.display = 'none';
    area.classList.remove('has-image');
    removeBtn.style.display = 'none';
    area.querySelector('.upload-icon').style.display = 'block';
    area.querySelector('.upload-label').style.display = 'block';
}

function updateJudulCount(el) {
    document.getElementById('judul-count').textContent = el.value.length;
}

function updateKontenCount(el) {
    document.getElementById('konten-count').textContent = el.value.length;
}
</script>

</body>
</html>