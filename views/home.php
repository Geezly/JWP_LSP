<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../config/database.php';

$logged_in_id = $_SESSION['user_id'];

// Ambil data user yang login
$sql_user = "SELECT id, username, nama_lengkap, foto FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $logged_in_id);
$stmt_user->execute();
$current_user = $stmt_user->get_result()->fetch_assoc();

// ============================================================
// PROSES SEARCH HASHTAG
// ============================================================
$search_hashtag = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_hashtag = trim($_GET['search']);
    $search_hashtag = ltrim($search_hashtag, '#');
}

// ============================================================
// PROSES KOMENTAR DENGAN GAMBAR
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    $post_id = $_POST['post_id'];
    $content = trim($_POST['comment_content']);
    $gambar_komentar = null;
    
    if (!empty($content)) {
        if (!empty($_FILES['comment_image']['name'])) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $ftype = $_FILES['comment_image']['type'];
            $fsize = $_FILES['comment_image']['size'];
            
            if (in_array($ftype, $allowed) && $fsize <= 5 * 1024 * 1024) {
                $ext = pathinfo($_FILES['comment_image']['name'], PATHINFO_EXTENSION);
                $gambar_komentar = 'comment_' . $logged_in_id . '_' . time() . '.' . $ext;
                $dest = '../uploads/' . $gambar_komentar;
                move_uploaded_file($_FILES['comment_image']['tmp_name'], $dest);
            }
        }
        
        $sql = "INSERT INTO comments (post_id, user_id, content, gambar, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiss", $post_id, $logged_in_id, $content, $gambar_komentar);
        $stmt->execute();
        
        header("Location: home.php" . ($search_hashtag ? "?search=" . urlencode($search_hashtag) : ""));
        exit;
    }
}

// ============================================================
// PROSES LIKE / UNLIKE
// ============================================================
if (isset($_GET['like'])) {
    $post_id = $_GET['like'];
    
    $check = $conn->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
    $check->bind_param("ii", $post_id, $logged_in_id);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;
    
    if ($exists) {
        $del = $conn->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
        $del->bind_param("ii", $post_id, $logged_in_id);
        $del->execute();
    } else {
        $ins = $conn->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
        $ins->bind_param("ii", $post_id, $logged_in_id);
        $ins->execute();
    }
    
    header("Location: home.php" . ($search_hashtag ? "?search=" . urlencode($search_hashtag) : ""));
    exit;
}

// ============================================================
// AMBIL POSTINGAN (FILTER HASHTAG)
// ============================================================
if (!empty($search_hashtag)) {
    $sql_posts = "SELECT p.*, u.username, u.nama_lengkap, u.foto as user_foto,
                  (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                  (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
                  (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked
                  FROM posts p
                  JOIN users u ON p.user_id = u.id
                  WHERE p.content LIKE ?
                  ORDER BY p.created_at DESC";
    $stmt_posts = $conn->prepare($sql_posts);
    $like_param = "%#" . $search_hashtag . "%";
    $stmt_posts->bind_param("is", $logged_in_id, $like_param);
} else {
    $sql_posts = "SELECT p.*, u.username, u.nama_lengkap, u.foto as user_foto,
                  (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as like_count,
                  (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comment_count,
                  (SELECT COUNT(*) FROM likes WHERE post_id = p.id AND user_id = ?) as user_liked
                  FROM posts p
                  JOIN users u ON p.user_id = u.id
                  ORDER BY p.created_at DESC";
    $stmt_posts = $conn->prepare($sql_posts);
    $stmt_posts->bind_param("i", $logged_in_id);
}
$stmt_posts->execute();
$posts = $stmt_posts->get_result();

// ============================================================
// AMBIL TRENDING HASHTAG
// ============================================================
$sql_trending = "SELECT hashtag, COUNT(*) as total FROM (
    SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(content, '#', -1), ' ', 1) as hashtag
    FROM posts
    WHERE content LIKE '%#%'
    UNION ALL
    SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(content, '#', -2), ' ', 1)
    FROM posts
    WHERE content LIKE '%#%#%'
    UNION ALL
    SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(content, '#', -3), ' ', 1)
    FROM posts
    WHERE content LIKE '%#%#%#%'
) as all_tags
WHERE hashtag IS NOT NULL AND hashtag != '' AND hashtag NOT REGEXP '[^a-zA-Z0-9_]'
GROUP BY hashtag
ORDER BY total DESC
LIMIT 5";
$trending = $conn->query($sql_trending);

// ============================================================
// FUNGSI BANTUAN
// ============================================================
function foto_url($foto) {
    if (empty($foto) || $foto === 'default.png')
        return 'https://ui-avatars.com/api/?name=U&background=6b3f26&color=ede0d4&size=200';
    return '../uploads/' . htmlspecialchars($foto);
}

function nama_tampil($n, $u) {
    return !empty($n) ? htmlspecialchars($n) : htmlspecialchars($u);
}

function time_elapsed($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return $diff . ' detik lalu';
    if ($diff < 3600) return floor($diff/60) . ' menit lalu';
    if ($diff < 86400) return floor($diff/3600) . ' jam lalu';
    if ($diff < 604800) return floor($diff/86400) . ' hari lalu';
    return date('d M Y', $time);
}

// Fungsi highlight hashtag
function highlight_hashtags($text) {
    if (empty($text)) return '';
    return preg_replace('/#([a-zA-Z0-9_]+)/', '<span class="hashtag" data-tag="$1">#$1</span>', htmlspecialchars($text));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda — Interaksi</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #1e1008;
            --brown-deep: #3b2314;
            --brown-mid: #6b3f26;
            --brown-warm: #a0674a;
            --brown-light: #c9a48a;
            --brown-pale: #e8d5c4;
            --parchment: #f5ede3;
            --cream: #faf6f1;
            --white: #ffffff;
            --muted: #967560;
            --border: rgba(107,63,38,0.14);
            --sidebar-w: 72px;
            --right-panel-w: 320px;
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; overflow: hidden; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--cream);
            color: var(--ink);
            display: flex;
        }

        /* ========== SIDEBAR ========== */
        .icon-sidebar {
            width: var(--sidebar-w);
            height: 100vh;
            background: var(--brown-deep);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 28px 0;
            flex-shrink: 0;
        }

        .logo-mark {
            width: 36px; height: 36px;
            border: 1.5px solid rgba(201,164,138,0.4);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 44px;
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
            opacity: 0;
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

        /* ========== MAIN CONTENT ========== */
        .main-container {
            flex: 1;
            display: flex;
            overflow: hidden;
        }

        .feed {
            flex: 1;
            overflow-y: auto;
            padding: 0 24px 40px 24px;
            min-width: 0;
        }

        .right-panel {
            width: var(--right-panel-w);
            flex-shrink: 0;
            overflow-y: auto;
            padding: 20px 20px 0 0;
            border-left: 1px solid var(--border);
        }

        /* Search Box */
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

        /* Trending Card */
        .trending-card {
            background: var(--white);
            border-radius: 20px;
            border: 1px solid var(--border);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .trending-header {
            padding: 16px 20px;
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 16px;
            border-bottom: 1px solid var(--border);
            color: var(--brown-deep);
        }

        .trending-item {
            padding: 12px 20px;
            border-bottom: 1px solid var(--border);
            cursor: pointer;
            transition: background 0.2s;
        }

        .trending-item:hover {
            background: var(--parchment);
        }

        .trending-tag {
            font-weight: 600;
            color: var(--brown-mid);
            font-size: 14px;
        }

        .trending-count {
            font-size: 11px;
            color: var(--muted);
            margin-top: 4px;
        }

        /* Post Card */
        .post-card {
            background: var(--white);
            border-radius: 20px;
            margin-bottom: 20px;
            border: 1px solid var(--border);
            overflow: hidden;
            transition: box-shadow 0.2s;
        }

        .post-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 20px;
        }

        .post-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
        }

        .post-author {
            flex: 1;
        }

        .post-author-name {
            font-weight: 600;
            font-size: 14px;
            color: var(--brown-deep);
        }

        .post-author-username {
            font-size: 12px;
            color: var(--muted);
        }

        .post-time {
            font-size: 11px;
            color: var(--brown-light);
        }

        /* POST CONTENT - UTAMA */
        .post-content {
            padding: 0 20px 12px 20px;
            font-size: 15px;
            line-height: 1.6;
            color: var(--ink);
        }

        /* HASHTAG STYLE */
        .hashtag {
            color: var(--brown-warm);
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-block;
        }

        .hashtag:hover {
            text-decoration: underline;
            color: var(--brown-deep);
            background: rgba(107,63,38,0.08);
            border-radius: 4px;
            padding: 0 2px;
        }

        /* GAMBAR */
        .post-image-wrapper {
            display: flex;
            justify-content: center;
            padding: 0 20px 16px 20px;
        }

        .post-image {
            max-width: 85%;
            max-height: 400px;
            width: auto;
            height: auto;
            border-radius: 16px;
            cursor: pointer;
            object-fit: contain;
            transition: opacity 0.2s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .post-image:hover {
            opacity: 0.95;
        }

        .post-actions {
            display: flex;
            gap: 24px;
            padding: 12px 20px;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            background: none;
            border: none;
            font-size: 13px;
            font-weight: 500;
            color: var(--muted);
            cursor: pointer;
            text-decoration: none;
        }

        .action-btn.liked {
            color: #e74c3c;
        }

        .action-btn:hover {
            color: var(--brown-warm);
        }

        .action-btn svg {
            width: 18px;
            height: 18px;
        }

        /* Comments */
        .comments-section {
            padding: 16px 20px;
            background: var(--parchment);
        }

        .comment {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }

        .comment-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }

        .comment-body {
            flex: 1;
        }

        .comment-author {
            font-weight: 600;
            font-size: 12px;
            color: var(--brown-deep);
        }

        .comment-text {
            font-size: 13px;
            color: var(--ink);
            margin-top: 4px;
        }

        .comment-image {
            margin-top: 8px;
        }

        .comment-image img {
            max-width: 120px;
            max-height: 120px;
            border-radius: 8px;
            cursor: pointer;
        }

        .comment-time {
            font-size: 10px;
            color: var(--brown-light);
            margin-top: 4px;
        }

        .comment-form {
            margin-top: 16px;
            display: none;
            flex-direction: column;
            gap: 10px;
        }

        .comment-form textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            resize: vertical;
        }

        .comment-form textarea:focus {
            border-color: var(--brown-warm);
            outline: none;
        }

        .comment-upload {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .upload-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--white);
            border: 1px solid var(--border);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
            color: var(--brown-mid);
        }

        .upload-btn:hover {
            background: var(--brown-pale);
        }

        .file-name {
            font-size: 11px;
            color: var(--muted);
        }

        .comment-submit {
            align-self: flex-end;
            background: var(--brown-deep);
            color: var(--white);
            border: none;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
        }

        .comment-submit:hover {
            background: var(--brown-mid);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
        }

        .search-result-info {
            margin-bottom: 16px;
            padding: 10px 0;
            font-size: 13px;
            color: var(--muted);
            border-bottom: 1px solid var(--border);
        }

        /* Modal */
        .image-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            cursor: pointer;
        }

        .image-modal img {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
        }

        .image-modal .close-modal {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 40px;
            cursor: pointer;
        }

        @media (max-width: 900px) {
            .right-panel { display: none; }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="icon-sidebar">
    <div class="logo-mark">
        <svg viewBox="0 0 24 24"><path d="M12 2L2 7v10l10 5 10-5V7L12 2zm0 2.18L20 8.5v7L12 19.82 4 15.5v-7L12 4.18z"/></svg>
    </div>
    <nav class="icon-nav">
        <a href="home.php" class="icon-btn active" data-tip="Beranda">
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
        <a href="user_profile.php" class="icon-btn" data-tip="Profil Saya">
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
        <img src="<?= foto_url($current_user['foto']) ?>" class="sidebar-avatar" alt="me">
    </div>
</aside>

<!-- MAIN CONTAINER -->
<div class="main-container">
    <!-- FEED -->
    <div class="feed">
        <div class="home-header" style="position: sticky; top: 0; background: rgba(250,246,241,0.94); backdrop-filter: blur(16px); padding: 16px 0; border-bottom: 1px solid var(--border); margin-bottom: 20px;">
            <h1 style="font-family: 'Playfair Display', serif; font-size: 24px; font-weight: 700; color: var(--brown-deep);">Beranda <em style="font-style: italic; color: var(--brown-warm);">Interaksi</em></h1>
        </div>

        <?php if (!empty($search_hashtag)): ?>
            <div class="search-result-info">
                🔍 Hasil pencarian untuk: <strong>#<?= htmlspecialchars($search_hashtag) ?></strong>
                <a href="home.php" style="float: right; color: var(--brown-warm); text-decoration: none;">✕ Hapus filter</a>
            </div>
        <?php endif; ?>

        <?php if ($posts->num_rows === 0): ?>
            <div class="empty-state">
                <p><?= !empty($search_hashtag) ? 'Tidak ada postingan dengan hashtag #' . htmlspecialchars($search_hashtag) : 'Belum ada postingan. Buat postingan pertama!' ?></p>
                <?php if (!empty($search_hashtag)): ?>
                    <a href="home.php" style="color: var(--brown-warm);">Kembali ke beranda</a>
                <?php else: ?>
                    <a href="post_create.php" style="color: var(--brown-warm);">Buat postingan</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php while ($post = $posts->fetch_assoc()): ?>
            <?php
            $sql_comments = "SELECT c.*, u.username, u.nama_lengkap, u.foto 
                             FROM comments c 
                             JOIN users u ON c.user_id = u.id 
                             WHERE c.post_id = ? 
                             ORDER BY c.created_at ASC";
            $stmt_comments = $conn->prepare($sql_comments);
            $stmt_comments->bind_param("i", $post['id']);
            $stmt_comments->execute();
            $comments = $stmt_comments->get_result();
            ?>
            
            <div class="post-card">
                <div class="post-header">
                    <img src="<?= foto_url($post['user_foto']) ?>" class="post-avatar">
                    <div class="post-author">
                        <div class="post-author-name"><?= nama_tampil($post['nama_lengkap'], $post['username']) ?></div>
                        <div class="post-author-username">@<?= htmlspecialchars($post['username']) ?></div>
                    </div>
                    <div class="post-time"><?= time_elapsed($post['created_at']) ?></div>
                </div>

                <!-- TAMPILKAN CONTENT SAJA (TANPA JUDUL) -->
                <div class="post-content">
                    <?= nl2br(highlight_hashtags($post['content'])) ?>
                </div>

                <?php if (!empty($post['image'])): ?>
                    <div class="post-image-wrapper">
                        <img src="../uploads/<?= htmlspecialchars($post['image']) ?>" 
                             class="post-image" 
                             onclick="openImageModal(this.src)"
                             alt="Post image">
                    </div>
                <?php endif; ?>

                <div class="post-actions">
                    <a href="?like=<?= $post['id'] . (!empty($search_hashtag) ? '&search=' . urlencode($search_hashtag) : '') ?>" class="action-btn <?= $post['user_liked'] ? 'liked' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="<?= $post['user_liked'] ? '#e74c3c' : 'none' ?>" stroke="currentColor" stroke-width="2">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                        </svg>
                        <span><?= $post['like_count'] ?></span>
                    </a>
                    <button class="action-btn" onclick="toggleCommentForm(<?= $post['id'] ?>)">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                        <span><?= $post['comment_count'] ?></span>
                    </button>
                </div>

                <div class="comments-section" id="comments-<?= $post['id'] ?>">
                    <?php while ($comment = $comments->fetch_assoc()): ?>
                        <div class="comment">
                            <img src="<?= foto_url($comment['foto']) ?>" class="comment-avatar">
                            <div class="comment-body">
                                <div class="comment-author"><?= nama_tampil($comment['nama_lengkap'], $comment['username']) ?></div>
                                <div class="comment-text"><?= nl2br(htmlspecialchars($comment['content'])) ?></div>
                                <?php if (!empty($comment['gambar'])): ?>
                                    <div class="comment-image">
                                        <img src="../uploads/<?= htmlspecialchars($comment['gambar']) ?>" 
                                             onclick="openImageModal(this.src)"
                                             alt="Comment image">
                                    </div>
                                <?php endif; ?>
                                <div class="comment-time"><?= time_elapsed($comment['created_at']) ?></div>
                            </div>
                        </div>
                    <?php endwhile; ?>

                    <form method="POST" enctype="multipart/form-data" class="comment-form" id="comment-form-<?= $post['id'] ?>">
                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                        <textarea name="comment_content" placeholder="Tulis komentar..." rows="2" required></textarea>
                        <div class="comment-upload">
                            <label for="comment_image_<?= $post['id'] ?>" class="upload-btn">📷 Tambah Foto</label>
                            <input type="file" name="comment_image" id="comment_image_<?= $post['id'] ?>" accept="image/*" style="display: none;" onchange="showFileName(this, 'file_name_<?= $post['id'] ?>')">
                            <span class="file-name" id="file_name_<?= $post['id'] ?>"></span>
                        </div>
                        <button type="submit" name="submit_comment" class="comment-submit">Kirim</button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <!-- RIGHT PANEL -->
    <div class="right-panel">
        <div class="search-box">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--brown-warm)" stroke-width="2">
                <circle cx="11" cy="11" r="8"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <form method="GET" action="home.php" style="flex: 1; display: flex; gap: 8px;">
                <input type="text" name="search" placeholder="Cari hashtag... (contoh: furab)" value="<?= htmlspecialchars($search_hashtag) ?>">
                <button type="submit">Cari</button>
            </form>
        </div>

        <div class="trending-card">
            <div class="trending-header">🔥 Tren untuk Anda</div>
            <?php if ($trending && $trending->num_rows > 0): ?>
                <?php while ($row = $trending->fetch_assoc()): ?>
                    <div class="trending-item" onclick="searchHashtag('<?= htmlspecialchars($row['hashtag']) ?>')">
                        <div class="trending-tag">#<?= htmlspecialchars($row['hashtag']) ?></div>
                        <div class="trending-count"><?= $row['total'] ?> postingan</div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="trending-item">
                    <div class="trending-tag">Belum ada hashtag trending</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MODAL GAMBAR -->
<div id="imageModal" class="image-modal" onclick="closeImageModal()">
    <span class="close-modal">&times;</span>
    <img id="modalImage" src="">
</div>

<script>
// FUNGSI HASHTAG
function searchHashtag(hashtag) {
    window.location.href = 'home.php?search=' + encodeURIComponent(hashtag);
}

// Event delegation untuk semua hashtag
document.addEventListener('click', function(e) {
    let hashtagEl = e.target.closest('.hashtag');
    if (hashtagEl) {
        e.preventDefault();
        e.stopPropagation();
        let tag = hashtagEl.getAttribute('data-tag');
        if (!tag) {
            tag = hashtagEl.innerText.replace('#', '');
        }
        searchHashtag(tag);
    }
});

function toggleCommentForm(postId) {
    const form = document.getElementById('comment-form-' + postId);
    if (form) {
        form.style.display = form.style.display === 'flex' ? 'none' : 'flex';
    }
}

function showFileName(input, spanId) {
    const span = document.getElementById(spanId);
    if (input.files && input.files[0]) {
        span.textContent = input.files[0].name;
    } else {
        span.textContent = '';
    }
}

function openImageModal(src) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    if (modal && modalImg) {
        modal.style.display = 'flex';
        modalImg.src = src;
        document.body.style.overflow = 'hidden';
    }
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeImageModal();
});
</script>

</body>
</html>