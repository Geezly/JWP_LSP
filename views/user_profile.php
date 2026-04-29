<?php
/**
 * user_profile.php
 * Halaman profil pengguna bergaya X/Twitter.
 * Akses: user_profile.php?id=USER_ID
 * Jika tidak ada ?id=, tampilkan profil yang sedang login.
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../config/database.php';

$logged_in_id = $_SESSION['user_id'];

// ── Tentukan user yang ditampilkan ────────────────────────────────────────────
$profile_id = isset($_GET['id']) ? (int)$_GET['id'] : $logged_in_id;
$is_own_profile = ($profile_id === $logged_in_id);

// ── Ambil data profil ─────────────────────────────────────────────────────────
$st = $conn->prepare("SELECT id, username, nama_lengkap, bio, foto, cover, created_at FROM users WHERE id = ?");
$st->bind_param("i", $profile_id);
$st->execute();
$profile = $st->get_result()->fetch_assoc();

if (!$profile) {
    // User tidak ditemukan
    header("Location: home.php");
    exit;
}

// ── Statistik ─────────────────────────────────────────────────────────────────
$st_post_count = $conn->prepare("SELECT COUNT(*) as total FROM posts WHERE user_id = ?");
$st_post_count->bind_param("i", $profile_id);
$st_post_count->execute();
$post_count = $st_post_count->get_result()->fetch_assoc()['total'];

$st_like_count = $conn->prepare("SELECT COUNT(*) as total FROM likes l JOIN posts p ON l.post_id = p.id WHERE p.user_id = ?");
$st_like_count->bind_param("i", $profile_id);
$st_like_count->execute();
$total_likes_received = $st_like_count->get_result()->fetch_assoc()['total'];

// ── Ambil postingan user ───────────────────────────────────────────────────────
$st_posts = $conn->prepare(
    "SELECT p.*,
            (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS like_count,
            (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count,
            (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) AS user_liked
     FROM posts p
     WHERE p.user_id = ?
     ORDER BY p.created_at DESC"
);
$st_posts->bind_param("ii", $logged_in_id, $profile_id);
$st_posts->execute();
$user_posts = $st_posts->get_result();

// ── Proses DELETE postingan (hanya milik sendiri) ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post']) && $is_own_profile) {
    $del_id = (int)$_POST['delete_post'];
    $st_del = $conn->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
    $st_del->bind_param("ii", $del_id, $logged_in_id);
    $st_del->execute();
    header("Location: user_profile.php");
    exit;
}

// ── Proses LIKE / UNLIKE ──────────────────────────────────────────────────────
if (isset($_GET['like'])) {
    $post_id = (int)$_GET['like'];
    $chk = $conn->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
    $chk->bind_param("ii", $post_id, $logged_in_id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        $conn->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?")->bind_param("ii", $post_id, $logged_in_id);
        $conn->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?")->execute();
        // Shorthand execute
        $st_unlike = $conn->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
        $st_unlike->bind_param("ii", $post_id, $logged_in_id);
        $st_unlike->execute();
    } else {
        $st_like = $conn->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
        $st_like->bind_param("ii", $post_id, $logged_in_id);
        $st_like->execute();
    }
    header("Location: user_profile.php" . ($is_own_profile ? "" : "?id=$profile_id"));
    exit;
}

// ── Helper functions ──────────────────────────────────────────────────────────
function foto_url($foto, $name = 'U') {
    if (empty($foto) || $foto === 'default.png') {
        $enc = urlencode($name);
        return "https://ui-avatars.com/api/?name={$enc}&background=6b3f26&color=ede0d4&size=200";
    }
    return '../uploads/' . htmlspecialchars($foto);
}

function cover_url($cover) {
    if (empty($cover)) return null;
    return '../uploads/' . htmlspecialchars($cover);
}

function nama_tampil($n, $u) {
    return !empty($n) ? htmlspecialchars($n) : htmlspecialchars($u);
}

function time_elapsed($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return $diff . ' detik lalu';
    if ($diff < 3600)   return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400)  return floor($diff / 3600) . ' jam lalu';
    if ($diff < 604800) return floor($diff / 86400) . ' hari lalu';
    return date('d M Y', strtotime($datetime));
}

function highlight_hashtags($text) {
    if (empty($text)) return '';
    return preg_replace('/#([a-zA-Z0-9_]+)/', '<span class="hashtag" data-tag="$1">#$1</span>', htmlspecialchars($text));
}

$display_name = nama_tampil($profile['nama_lengkap'], $profile['username']);
$joined       = date('F Y', strtotime($profile['created_at']));
$cover        = cover_url($profile['cover']);
$avatar       = foto_url($profile['foto'], $display_name);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $display_name ?> — Interaksi</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        /* ── RESET & VARIABLES ── */
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
            --right-w:      300px;
            --danger:       #c0392b;
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; overflow: hidden; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--cream);
            color: var(--ink);
            display: flex;
        }

        /* ── SIDEBAR ── */
        .icon-sidebar {
            width: var(--sidebar-w);
            height: 100vh;
            background: var(--brown-deep);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 28px 0;
            flex-shrink: 0;
            z-index: 10;
        }

        .logo-mark {
            width: 36px; height: 36px;
            border: 1.5px solid rgba(201,164,138,0.4);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 44px; flex-shrink: 0;
            text-decoration: none;
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
            border: none; background: transparent; cursor: pointer;
        }

        .icon-btn:hover  { background: rgba(255,255,255,0.07); color: var(--brown-light); }
        .icon-btn.active { background: rgba(201,164,138,0.15); color: var(--brown-pale); }
        .icon-btn svg    { width: 20px; height: 20px; }

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
            z-index: 100;
        }
        .icon-btn:hover::after { opacity: 1; }

        .icon-nav-bottom {
            margin-top: auto;
            display: flex; flex-direction: column;
            align-items: center; gap: 6px;
        }

        .icon-btn.logout { color: rgba(192,100,80,0.6); }
        .icon-btn.logout:hover { background: rgba(192,80,60,0.12); color: #e07060; }

        .sidebar-avatar {
            width: 34px; height: 34px;
            border-radius: 50%; object-fit: cover;
            border: 2px solid rgba(201,164,138,0.3);
            margin-top: 10px;
        }

        /* ── LAYOUT ── */
        .main-container {
            flex: 1;
            display: flex;
            overflow: hidden;
        }

        .profile-feed {
            flex: 1;
            overflow-y: auto;
            min-width: 0;
        }

        .right-panel {
            width: var(--right-w);
            flex-shrink: 0;
            overflow-y: auto;
            padding: 20px 20px 0 0;
            border-left: 1px solid var(--border);
        }

        /* ── PROFILE AVATAR (no cover) ── */
        .profile-avatar {
            width: 80px; height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--brown-pale);
            background: var(--brown-pale);
            flex-shrink: 0;
        }

        /* ── PROFILE INFO SECTION ── */
        .profile-info-section {
            padding: 24px 28px 0 28px;
        }

        .profile-top-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 14px;
        }

        .profile-left {
            display: flex;
            align-items: center;
            gap: 16px;
            flex: 1;
            min-width: 0;
        }

        .profile-names { flex: 1; min-width: 0; }

        .profile-display-name {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 700;
            color: var(--brown-deep);
            line-height: 1.2;
        }

        .profile-username {
            font-size: 13px;
            color: var(--muted);
            margin-top: 3px;
        }

        .profile-bio {
            font-size: 14px;
            color: var(--ink);
            line-height: 1.6;
            margin-bottom: 14px;
            white-space: pre-line;
        }

        .profile-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: var(--muted);
        }

        .meta-item svg { width: 14px; height: 14px; flex-shrink: 0; }

        /* ── STATS ROW ── */
        .profile-stats {
            display: flex;
            gap: 28px;
            padding: 14px 0;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            margin-bottom: 0;
        }

        .stat-item { text-align: center; }

        .stat-value {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            font-weight: 700;
            color: var(--brown-deep);
        }

        .stat-label {
            font-size: 11px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-top: 2px;
        }

        /* ── BUTTONS ── */
        .profile-actions {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 18px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            border: none;
            white-space: nowrap;
        }

        .btn svg { width: 15px; height: 15px; }

        .btn-edit {
            background: transparent;
            border: 1.5px solid var(--brown-mid);
            color: var(--brown-mid);
        }

        .btn-edit:hover {
            background: var(--brown-mid);
            color: var(--white);
        }

        .btn-back {
            background: var(--brown-deep);
            color: var(--white);
        }

        .btn-back:hover { background: var(--brown-mid); }

        /* ── TAB ── */
        .profile-tab-bar {
            display: flex;
            border-bottom: 1px solid var(--border);
            padding: 0 28px;
            background: var(--white);
            position: sticky;
            top: 0;
            z-index: 5;
        }

        .tab-btn {
            padding: 14px 20px;
            font-size: 14px;
            font-weight: 500;
            color: var(--muted);
            cursor: pointer;
            border: none;
            background: transparent;
            border-bottom: 2px solid transparent;
            transition: color 0.2s, border-color 0.2s;
            font-family: 'DM Sans', sans-serif;
        }

        .tab-btn.active {
            color: var(--brown-deep);
            border-bottom-color: var(--brown-mid);
            font-weight: 600;
        }

        .tab-btn:hover:not(.active) { color: var(--brown-warm); }

        /* ── TAB CONTENT ── */
        .tab-content { display: none; padding: 20px 28px 60px; }
        .tab-content.active { display: block; }

        /* ── POST CARDS ── */
        .post-card {
            background: var(--white);
            border-radius: 20px;
            margin-bottom: 16px;
            border: 1px solid var(--border);
            overflow: hidden;
            transition: box-shadow 0.2s;
        }

        .post-card:hover { box-shadow: 0 4px 20px rgba(107,63,38,0.08); }

        .post-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px 8px;
        }

        .post-avatar {
            width: 40px; height: 40px;
            border-radius: 50%; object-fit: cover;
            flex-shrink: 0;
        }

        .post-author { flex: 1; min-width: 0; }

        .post-author-name {
            font-weight: 600;
            font-size: 14px;
            color: var(--brown-deep);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }

        .post-author-username {
            font-size: 12px;
            color: var(--muted);
        }

        .post-time {
            font-size: 12px;
            color: var(--muted);
            flex-shrink: 0;
        }

        .post-content {
            padding: 0 20px 12px;
            font-size: 14px;
            line-height: 1.6;
            color: var(--ink);
        }

        .hashtag {
            color: var(--brown-warm);
            font-weight: 500;
            cursor: pointer;
        }

        .hashtag:hover { text-decoration: underline; }

        .post-image-wrapper {
            padding: 0 20px 12px;
        }

        .post-image {
            width: 100%; max-height: 420px;
            object-fit: cover;
            border-radius: 14px;
            cursor: zoom-in;
        }

        .post-actions {
            display: flex;
            gap: 6px;
            padding: 10px 20px 14px;
            border-top: 1px solid var(--border);
            align-items: center;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 13px;
            color: var(--muted);
            background: transparent;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s, color 0.2s;
            font-family: 'DM Sans', sans-serif;
        }

        .action-btn:hover { background: var(--parchment); color: var(--brown-warm); }
        .action-btn.liked { color: #e74c3c; }
        .action-btn svg { width: 16px; height: 16px; }

        .action-btn-delete {
            margin-left: auto;
            color: rgba(192,57,43,0.5);
        }

        .action-btn-delete:hover { background: rgba(192,57,43,0.08); color: var(--danger); }

        /* ── KOMENTAR ── */
        .comments-section { padding: 0 20px 12px; }

        .comment {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
        }

        .comment-avatar {
            width: 32px; height: 32px;
            border-radius: 50%; object-fit: cover;
            flex-shrink: 0;
        }

        .comment-body { flex: 1; min-width: 0; }

        .comment-author {
            font-weight: 600;
            font-size: 13px;
            color: var(--brown-deep);
        }

        .comment-text {
            font-size: 13px;
            color: var(--ink);
            line-height: 1.5;
            margin-top: 2px;
        }

        .comment-image { margin-top: 8px; }
        .comment-image img {
            max-width: 220px;
            border-radius: 10px;
            cursor: zoom-in;
        }

        .comment-time {
            font-size: 11px;
            color: var(--muted);
            margin-top: 4px;
        }

        .comment-form {
            display: none;
            gap: 10px;
            align-items: flex-start;
            margin-top: 10px;
            border-top: 1px solid var(--border);
            padding-top: 12px;
        }

        .comment-form textarea {
            flex: 1;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 10px 14px;
            font-size: 13px;
            font-family: 'DM Sans', sans-serif;
            resize: none;
            outline: none;
            background: var(--parchment);
        }

        .comment-form textarea:focus { border-color: var(--brown-warm); }

        .comment-upload {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .upload-btn {
            font-size: 12px;
            color: var(--brown-warm);
            cursor: pointer;
            background: var(--parchment);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 5px 12px;
        }

        .comment-submit {
            padding: 8px 18px;
            background: var(--brown-mid);
            color: var(--white);
            border: none;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
        }

        .comment-submit:hover { background: var(--brown-deep); }

        /* ── EMPTY STATE ── */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
        }

        .empty-state svg {
            width: 48px; height: 48px;
            stroke: var(--brown-light);
            margin-bottom: 16px;
        }

        .empty-state p {
            font-size: 15px;
            font-family: 'Playfair Display', serif;
            color: var(--brown-light);
        }

        /* ── RIGHT PANEL ── */
        .search-box {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 30px;
            padding: 10px 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-box input {
            flex: 1;
            border: none;
            outline: none;
            background: transparent;
            font-size: 13px;
            font-family: 'DM Sans', sans-serif;
        }

        .search-box button {
            background: none;
            border: none;
            cursor: pointer;
            color: var(--brown-warm);
        }

        .panel-card {
            background: var(--white);
            border-radius: 20px;
            border: 1px solid var(--border);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .panel-card-header {
            padding: 14px 18px;
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 15px;
            color: var(--brown-deep);
            border-bottom: 1px solid var(--border);
        }

        .panel-card-body { padding: 16px 18px; }

        .panel-stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
        }

        .panel-stat-row:last-child { border-bottom: none; }

        .panel-stat-label { color: var(--muted); }

        .panel-stat-value {
            font-weight: 600;
            color: var(--brown-deep);
        }

        /* ── IMAGE MODAL ── */
        .image-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(20,10,5,0.92);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .image-modal img {
            max-width: 90vw;
            max-height: 90vh;
            border-radius: 12px;
            object-fit: contain;
        }

        .close-modal {
            position: absolute;
            top: 20px; right: 28px;
            color: var(--brown-light);
            font-size: 32px;
            cursor: pointer;
            line-height: 1;
        }

        /* ── CONFIRM DELETE MODAL ── */
        .confirm-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(20,10,5,0.6);
            z-index: 500;
            align-items: center;
            justify-content: center;
        }

        .confirm-box {
            background: var(--white);
            border-radius: 20px;
            padding: 32px 28px;
            max-width: 360px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }

        .confirm-box h3 {
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            color: var(--brown-deep);
            margin-bottom: 10px;
        }

        .confirm-box p {
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 24px;
        }

        .confirm-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .confirm-actions button {
            padding: 10px 24px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            border: none;
        }

        .btn-confirm-delete {
            background: var(--danger);
            color: var(--white);
        }

        .btn-confirm-cancel {
            background: var(--parchment);
            color: var(--brown-mid);
        }
    </style>
</head>
<body>

<!-- SIDEBAR (sama persis dengan home.php) -->
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
        <img src="<?= foto_url($profile['foto'], $display_name) ?>" class="sidebar-avatar" alt="me">
    </div>
</aside>

<!-- ── MAIN CONTAINER ── -->
<div class="main-container">

    <!-- ── PROFILE FEED ── -->
    <div class="profile-feed">

        <!-- PROFILE INFO -->
        <div class="profile-info-section">
            <div class="profile-top-row">
                <div class="profile-left">
                    <img src="<?= $avatar ?>" class="profile-avatar" alt="<?= $display_name ?>">
                    <div class="profile-names">
                        <div class="profile-display-name"><?= $display_name ?></div>
                        <div class="profile-username">@<?= htmlspecialchars($profile['username']) ?></div>
                    </div>
                </div>

                <div class="profile-actions">
                    <?php if ($is_own_profile): ?>
                        <a href="profile.php" class="btn btn-edit">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                            Edit Profil
                        </a>
                    <?php else: ?>
                        <a href="home.php" class="btn btn-back">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="15 18 9 12 15 6"/>
                            </svg>
                            Kembali
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($profile['bio'])): ?>
            <div class="profile-bio"><?= htmlspecialchars($profile['bio']) ?></div>
            <?php endif; ?>

            <div class="profile-meta">
                <div class="meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    Bergabung <?= $joined ?>
                </div>
            </div>

            <!-- STATS -->
            <div class="profile-stats">
                <div class="stat-item">
                    <div class="stat-value"><?= number_format($post_count) ?></div>
                    <div class="stat-label">Postingan</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= number_format($total_likes_received) ?></div>
                    <div class="stat-label">Suka Diterima</div>
                </div>
            </div>
        </div>

        <!-- TAB BAR -->
        <div class="profile-tab-bar">
            <button class="tab-btn active" onclick="switchTab('posts', this)">
                Postingan
            </button>
            <button class="tab-btn" onclick="switchTab('likes', this)">
                Disukai
            </button>
        </div>

        <!-- TAB: POSTINGAN -->
        <div id="tab-posts" class="tab-content active">
            <?php
            if ($user_posts->num_rows === 0):
            ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
                <p>Belum ada postingan</p>
            </div>
            <?php
            else:
            while ($post = $user_posts->fetch_assoc()):
                // Ambil komentar
                $st_cmt = $conn->prepare(
                    "SELECT c.*, u.username, u.nama_lengkap, u.foto
                     FROM comments c
                     JOIN users u ON c.user_id = u.id
                     WHERE c.post_id = ?
                     ORDER BY c.created_at ASC"
                );
                $st_cmt->bind_param("i", $post['id']);
                $st_cmt->execute();
                $comments = $st_cmt->get_result();
            ?>
            <div class="post-card">
                <div class="post-header">
                    <img src="<?= $avatar ?>" class="post-avatar" alt="">
                    <div class="post-author">
                        <div class="post-author-name"><?= $display_name ?></div>
                        <div class="post-author-username">@<?= htmlspecialchars($profile['username']) ?></div>
                    </div>
                    <div class="post-time"><?= time_elapsed($post['created_at']) ?></div>
                </div>

                <div class="post-content">
                    <?= nl2br(highlight_hashtags($post['content'])) ?>
                </div>

                <?php if (!empty($post['image'])): ?>
                <div class="post-image-wrapper">
                    <img src="../uploads/<?= htmlspecialchars($post['image']) ?>"
                         class="post-image"
                         onclick="openImageModal(this.src)"
                         alt="Gambar postingan">
                </div>
                <?php endif; ?>

                <div class="post-actions">
                    <!-- Like -->
                    <a href="?<?= $is_own_profile ? '' : 'id='.$profile_id.'&' ?>like=<?= $post['id'] ?>"
                       class="action-btn <?= $post['user_liked'] ? 'liked' : '' ?>">
                        <svg viewBox="0 0 24 24"
                             fill="<?= $post['user_liked'] ? '#e74c3c' : 'none' ?>"
                             stroke="currentColor" stroke-width="2">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                        </svg>
                        <span><?= $post['like_count'] ?></span>
                    </a>

                    <!-- Komentar -->
                    <button class="action-btn" onclick="toggleCommentForm(<?= $post['id'] ?>)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                        <span><?= $post['comment_count'] ?></span>
                    </button>

                    <!-- Hapus (hanya milik sendiri) -->
                    <?php if ($is_own_profile): ?>
                    <button class="action-btn action-btn-delete"
                            onclick="confirmDelete(<?= $post['id'] ?>)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                            <path d="M10 11v6M14 11v6"/>
                            <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                        </svg>
                        Hapus
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Komentar -->
                <div class="comments-section" id="comments-<?= $post['id'] ?>">
                    <?php while ($cmt = $comments->fetch_assoc()): ?>
                    <div class="comment">
                        <img src="<?= foto_url($cmt['foto'], nama_tampil($cmt['nama_lengkap'], $cmt['username'])) ?>"
                             class="comment-avatar" alt="">
                        <div class="comment-body">
                            <div class="comment-author"><?= nama_tampil($cmt['nama_lengkap'], $cmt['username']) ?></div>
                            <div class="comment-text"><?= nl2br(htmlspecialchars($cmt['content'])) ?></div>
                            <?php if (!empty($cmt['gambar'])): ?>
                            <div class="comment-image">
                                <img src="../uploads/<?= htmlspecialchars($cmt['gambar']) ?>"
                                     onclick="openImageModal(this.src)" alt="">
                            </div>
                            <?php endif; ?>
                            <div class="comment-time"><?= time_elapsed($cmt['created_at']) ?></div>
                        </div>
                    </div>
                    <?php endwhile; ?>

                    <form method="POST" enctype="multipart/form-data"
                          class="comment-form" id="comment-form-<?= $post['id'] ?>">
                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                        <textarea name="comment_content" placeholder="Tulis komentar..." rows="2" required></textarea>
                        <div style="display:flex; flex-direction:column; gap:6px;">
                            <div class="comment-upload">
                                <label for="ci_<?= $post['id'] ?>" class="upload-btn">📷 Foto</label>
                                <input type="file" name="comment_image" id="ci_<?= $post['id'] ?>"
                                       accept="image/*" style="display:none;"
                                       onchange="showFileName(this,'fn_<?= $post['id'] ?>')">
                                <span id="fn_<?= $post['id'] ?>" style="font-size:11px;color:var(--muted)"></span>
                            </div>
                            <button type="submit" name="submit_comment" class="comment-submit">Kirim</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php
            endwhile;
            endif;
            ?>
        </div>

        <!-- TAB: DISUKAI -->
        <div id="tab-likes" class="tab-content">
            <?php
            $st_liked = $conn->prepare(
                "SELECT p.*, u.username, u.nama_lengkap, u.foto AS user_foto,
                        (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS like_count,
                        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count,
                        1 AS user_liked
                 FROM likes lk
                 JOIN posts p ON lk.post_id = p.id
                 JOIN users u ON p.user_id = u.id
                 WHERE lk.user_id = ?
                 ORDER BY lk.created_at DESC"
            );
            $st_liked->bind_param("i", $profile_id);
            $st_liked->execute();
            $liked_posts = $st_liked->get_result();

            if ($liked_posts->num_rows === 0):
            ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                </svg>
                <p>Belum ada postingan yang disukai</p>
            </div>
            <?php
            else:
            while ($lpost = $liked_posts->fetch_assoc()):
                $lname = nama_tampil($lpost['nama_lengkap'], $lpost['username']);
                $lavatar = foto_url($lpost['user_foto'], $lname);
            ?>
            <div class="post-card">
                <div class="post-header">
                    <img src="<?= $lavatar ?>" class="post-avatar" alt="">
                    <div class="post-author">
                        <div class="post-author-name"><?= $lname ?></div>
                        <div class="post-author-username">@<?= htmlspecialchars($lpost['username']) ?></div>
                    </div>
                    <div class="post-time"><?= time_elapsed($lpost['created_at']) ?></div>
                </div>

                <div class="post-content">
                    <?= nl2br(highlight_hashtags($lpost['content'])) ?>
                </div>

                <?php if (!empty($lpost['image'])): ?>
                <div class="post-image-wrapper">
                    <img src="../uploads/<?= htmlspecialchars($lpost['image']) ?>"
                         class="post-image" onclick="openImageModal(this.src)" alt="">
                </div>
                <?php endif; ?>

                <div class="post-actions">
                    <a href="?<?= $is_own_profile ? '' : 'id='.$profile_id.'&' ?>like=<?= $lpost['id'] ?>"
                       class="action-btn liked">
                        <svg viewBox="0 0 24 24" fill="#e74c3c" stroke="currentColor" stroke-width="2">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                        </svg>
                        <span><?= $lpost['like_count'] ?></span>
                    </a>
                    <button class="action-btn" onclick="toggleCommentForm('l<?= $lpost['id'] ?>')">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                        <span><?= $lpost['comment_count'] ?></span>
                    </button>
                </div>
            </div>
            <?php
            endwhile;
            endif;
            ?>
        </div>

    </div><!-- /profile-feed -->

    <!-- ── RIGHT PANEL ── -->
    <div class="right-panel">

        <!-- Search -->
        <div class="search-box">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--brown-warm)" stroke-width="2">
                <circle cx="11" cy="11" r="8"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <form method="GET" action="home.php" style="flex:1; display:flex; gap:8px;">
                <input type="text" name="search" placeholder="Cari hashtag...">
                <button type="submit">Cari</button>
            </form>
        </div>

        <!-- Ringkasan Profil -->
        <div class="panel-card">
            <div class="panel-card-header">Ringkasan</div>
            <div class="panel-card-body">
                <div class="panel-stat-row">
                    <span class="panel-stat-label">Postingan</span>
                    <span class="panel-stat-value"><?= number_format($post_count) ?></span>
                </div>
                <div class="panel-stat-row">
                    <span class="panel-stat-label">Suka Diterima</span>
                    <span class="panel-stat-value"><?= number_format($total_likes_received) ?></span>
                </div>
                <div class="panel-stat-row">
                    <span class="panel-stat-label">Bergabung</span>
                    <span class="panel-stat-value"><?= $joined ?></span>
                </div>
            </div>
        </div>

    </div>

</div><!-- /main-container -->

<!-- IMAGE MODAL -->
<div id="imageModal" class="image-modal" onclick="closeImageModal()">
    <span class="close-modal">&times;</span>
    <img id="modalImage" src="" alt="">
</div>

<!-- CONFIRM DELETE MODAL -->
<div id="confirmOverlay" class="confirm-overlay" onclick="cancelDelete(event)">
    <div class="confirm-box">
        <h3>Hapus Postingan?</h3>
        <p>Postingan ini akan dihapus permanen dan tidak bisa dikembalikan.</p>
        <div class="confirm-actions">
            <button class="btn-confirm-cancel" onclick="cancelDelete()">Batal</button>
            <button class="btn-confirm-delete" onclick="submitDelete()">Hapus</button>
        </div>
    </div>
</div>
<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="delete_post" id="deletePostId">
</form>

<script>
// ── TAB ──
function switchTab(tab, el) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    el.classList.add('active');
}

// ── HASHTAG ──
document.addEventListener('click', function(e) {
    const h = e.target.closest('.hashtag');
    if (h) {
        e.preventDefault();
        const tag = h.getAttribute('data-tag') || h.innerText.replace('#','');
        window.location.href = 'home.php?search=' + encodeURIComponent(tag);
    }
});

// ── KOMENTAR ──
function toggleCommentForm(id) {
    const f = document.getElementById('comment-form-' + id);
    if (f) f.style.display = f.style.display === 'flex' ? 'none' : 'flex';
}

function showFileName(input, spanId) {
    const s = document.getElementById(spanId);
    s.textContent = input.files && input.files[0] ? input.files[0].name : '';
}

// ── IMAGE MODAL ──
function openImageModal(src) {
    document.getElementById('modalImage').src = src;
    document.getElementById('imageModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    document.getElementById('imageModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// ── DELETE ──
function confirmDelete(id) {
    document.getElementById('deletePostId').value = id;
    document.getElementById('confirmOverlay').style.display = 'flex';
}

function cancelDelete(e) {
    if (!e || e.target === document.getElementById('confirmOverlay')) {
        document.getElementById('confirmOverlay').style.display = 'none';
    }
}

function submitDelete() {
    document.getElementById('deleteForm').submit();
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeImageModal();
        document.getElementById('confirmOverlay').style.display = 'none';
    }
});
</script>

</body>
</html>