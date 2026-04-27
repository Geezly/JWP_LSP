<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../config/database.php';

$logged_in_id = $_SESSION['user_id'];
$logged_in_username = $_SESSION['username'];

// ── LOGIKA FILTER & SORTING ───────────────────────────────────────────────
$keyword = isset($_GET['cari']) ? mysqli_real_escape_string($conn, $_GET['cari']) : '';
// Menentukan urutan: DESC (Terbaru) atau ASC (Terlama)
$sort = (isset($_GET['sort']) && $_GET['sort'] === 'asc') ? 'ASC' : 'DESC';

// LIKE / UNLIKE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'like') {
    $post_id = (int)$_POST['post_id'];
    $cek = $conn->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
    $cek->bind_param("ii", $post_id, $logged_in_id);
    $cek->execute();
    $cek->store_result();

    if ($cek->num_rows > 0) {
        $del = $conn->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
        $del->bind_param("ii", $post_id, $logged_in_id);
        $del->execute();
    } else {
        $ins = $conn->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
        $ins->bind_param("ii", $post_id, $logged_in_id);
        $ins->execute();
    }
    header("Location: home.php" . ($keyword ? "?cari=" . urlencode($keyword) : ""));
    exit;
}

// TAMBAH KOMENTAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'comment') {
    $post_id = (int)$_POST['post_id'];
    $content = trim($_POST['comment_content'] ?? '');
    
    if ($content !== '') {
        $ins = $conn->prepare("INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
        $ins->bind_param("iis", $post_id, $logged_in_id, $content);
        $ins->execute();
        $ins->close();
    }
    
    header("Location: home.php" . ($keyword ? "?cari=" . urlencode($keyword) : ""));
    exit;
}

// ── AMBIL POST DENGAN FILTER & SORTING ────────────────────────────────────────
$where_clause = $keyword ? "WHERE p.content LIKE '%$keyword%'" : "";

$sql_posts = "
    SELECT 
        p.id, p.content, p.image, p.file, p.created_at,
        u.username, u.nama_lengkap, u.foto,
        (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS jumlah_like,
        (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id AND l.user_id = ?) AS sudah_like
    FROM posts p
    JOIN users u ON p.user_id = u.id
    $where_clause
    ORDER BY p.created_at $sort
";

$stmt_posts = $conn->prepare($sql_posts);
$stmt_posts->bind_param("i", $logged_in_id);
$stmt_posts->execute();
$result_posts = $stmt_posts->get_result();

$posts = [];
while ($row = $result_posts->fetch_assoc()) {
    $sql_kom = "SELECT c.id, c.content, c.created_at, u.username, u.nama_lengkap, u.foto
                FROM comments c 
                JOIN users u ON c.user_id = u.id
                WHERE c.post_id = ? 
                ORDER BY c.created_at ASC";
    $stmt_kom = $conn->prepare($sql_kom);
    $stmt_kom->bind_param("i", $row['id']);
    $stmt_kom->execute();
    $result_kom = $stmt_kom->get_result();
    $row['komentar'] = $result_kom->fetch_all(MYSQLI_ASSOC);
    $stmt_kom->close();
    $posts[] = $row;
}
$stmt_posts->close();

// ── TRENDING HASHTAG ──────────────────────────────────────────────────────────
$sql_trend = "SELECT content FROM posts ORDER BY created_at DESC LIMIT 50";
$result_trend = $conn->query($sql_trend);
$hashtags = [];
while ($r = $result_trend->fetch_assoc()) {
    preg_match_all('/#\w+/', $r['content'], $matches);
    foreach ($matches[0] as $tag) {
        $hashtags[$tag] = ($hashtags[$tag] ?? 0) + 1;
    }
}
arsort($hashtags);
$top_hashtags = array_slice($hashtags, 0, 5, true);

// ── HELPERS ───────────────────────────────────────────────────────────────────
function time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $current_time = time();
    $difference = $current_time - $timestamp;
    
    if ($difference < 60) {
        return 'Baru saja';
    } elseif ($difference < 3600) {
        $minutes = floor($difference / 60);
        return $minutes . ' menit yang lalu';
    } elseif ($difference < 86400) {
        $hours = floor($difference / 3600);
        return $hours . ' jam yang lalu';
    } elseif ($difference < 604800) {
        $days = floor($difference / 86400);
        return $days . ' hari yang lalu';
    } else {
        return date('d/m/Y H:i', $timestamp);
    }
}

function foto_url($foto) {
    if (empty($foto) || $foto === 'default.png') {
        return 'https://ui-avatars.com/api/?name=U&background=85a3db&color=fff&size=100';
    }
    return '../uploads/' . htmlspecialchars($foto);
}

function nama_tampil($nama_lengkap, $username) {
    return !empty($nama_lengkap) ? htmlspecialchars($nama_lengkap) : htmlspecialchars($username);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda – Interaksi</title>
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #fdfbfb 0%, #e3f2fd 100%);
            --sidebar-blue: #f0f7ff;
            --main-blue: #85a3db;
            --navy: #1a2a6c;
            --accent: #1d9bf0;
            --white: #ffffff;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-gradient); color: #333; min-height: 100vh; }
        .desktop-wrapper { display: grid; grid-template-columns: 280px 1fr 320px; max-width: 1300px; margin: 0 auto; gap: 20px; padding: 0 20px; }
        .side-nav { height: 100vh; position: sticky; top: 0; padding-top: 30px; display: flex; flex-direction: column; }
        .brand-logo { font-size: 26px; font-weight: 900; color: var(--navy); margin-bottom: 40px; padding-left: 15px; display: flex; align-items: center; gap: 12px; }
        .nav-link { display: block; padding: 15px 20px; text-decoration: none; color: #555; font-weight: 600; border-radius: 16px; transition: 0.3s; margin-bottom: 8px; }
        .nav-link:hover { background: var(--sidebar-blue); color: var(--accent); transform: translateX(8px); }
        .nav-link.active { background: var(--main-blue); color: white; }
        .btn-create-post { display: block; margin-top: 20px; padding: 16px; background: var(--navy); color: white; text-align: center; text-decoration: none; border-radius: 18px; font-weight: bold; }
        .feed-container { padding-top: 20px; }
        .top-bar { background: rgba(255,255,255,0.85); backdrop-filter: blur(12px); padding: 20px 25px; border-radius: 24px; margin-bottom: 25px; }
        .post-card { background: var(--white); padding: 25px; border-radius: 28px; margin-bottom: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
        .avatar-img { width: 50px; height: 50px; border-radius: 18px; object-fit: cover; }
        .avatar-sm { width: 32px; height: 32px; border-radius: 10px; object-fit: cover; }
        .user-info b { color: var(--navy); font-size: 16px; }
        .user-info span { color: #aaa; font-size: 13px; }
        .post-body { margin: 15px 0; line-height: 1.7; color: #444; font-size: 15px; }
        .post-image { width: 100%; max-height: 400px; object-fit: cover; border-radius: 16px; margin-bottom: 15px; }
        .action-bar { display: flex; gap: 25px; border-top: 1px solid #f8f8f8; padding-top: 15px; align-items: center; }
        .action-btn { border: none; background: none; font-weight: 600; cursor: pointer; font-size: 13px; display: flex; align-items: center; gap: 5px; padding: 6px 14px; border-radius: 10px; }
        .btn-like { color: #bbb; }
        .btn-like.liked { color: #e0245e; }
        .comment-section { margin-top: 15px; padding-top: 15px; border-top: 1px dashed #eee; }
        .comment-item { display: flex; gap: 10px; margin-bottom: 12px; align-items: flex-start; }
        .comment-bubble { background: #f7faff; border-radius: 14px; padding: 10px 14px; flex: 1; font-size: 13px; line-height: 1.5; }
        .comment-form { display: flex; gap: 10px; margin-top: 12px; align-items: center; }
        .comment-input { flex: 1; padding: 10px 16px; border: 1px solid #eee; border-radius: 20px; outline: none; font-size: 13px; }
        .btn-send { padding: 10px 18px; background: var(--navy); color: white; border: none; border-radius: 16px; font-weight: bold; cursor: pointer; }
        .right-panel { padding-top: 20px; }
        .search-box { width: 100%; padding: 16px 20px; border-radius: 20px; border: 1px solid #eee; background: white; outline: none; margin-bottom: 25px; font-size: 14px; }
        .trend-card { background: var(--white); padding: 25px; border-radius: 24px; margin-bottom: 20px; }
        .hashtag-item { padding: 12px 0; border-bottom: 1px solid #f5f5f5; display: flex; justify-content: space-between; align-items: center; text-decoration: none; }
        .hashtag-badge { background: #f0f7ff; color: var(--accent); font-size: 11px; padding: 3px 10px; border-radius: 20px; font-weight: 600; }
        .user-card { background: var(--white); padding: 20px 25px; border-radius: 24px; display: flex; align-items: center; gap: 15px; }
        .user-card img { width: 48px; height: 48px; border-radius: 14px; object-fit: cover; }
        /* Style baru untuk tombol sort */
        .sort-btn { text-decoration: none; font-size: 12px; padding: 8px 16px; border-radius: 20px; border: 1px solid #eee; font-weight: bold; transition: 0.3s; }
        .sort-btn.active { background: var(--navy); color: white; border-color: var(--navy); }
        .sort-btn.inactive { background: white; color: #555; }
    </style>
</head>
<body>

<div class="desktop-wrapper">

    <aside class="side-nav">
        <div class="brand-logo">
            <div style="width:12px;height:35px;background:var(--main-blue);border-radius:6px;"></div>
            Interaksi
        </div>
        <nav>
            <a href="home.php" class="nav-link <?= !$keyword ? 'active' : '' ?>">Eksplorasi</a>
            <a href="#" class="nav-link">Pesan Terkirim</a>
            <a href="#" class="nav-link">Notifikasi</a>
            <a href="profile.php" class="nav-link">Profil Saya</a>
            <a href="post_create.php" class="btn-create-post">+ Buat Postingan</a>
        </nav>
        <div style="margin-top:auto; padding-bottom:30px;">
            <a href="../views/logout.php" class="nav-link" style="color:#ff6b6b; font-weight: bold;">Keluar</a>
        </div>
    </aside>

    <main class="feed-container">
        <div class="top-bar">
            <h2 style="font-size:20px;color:var(--navy);font-weight:800;">
                <?= $keyword ? "Mencari: " . htmlspecialchars($keyword) : "Feed Terbaru" ?>
            </h2>
        </div>

        <div style="margin-bottom: 20px; display: flex; gap: 10px; align-items: center;">
            <span style="font-size: 13px; color: #888; font-weight: 600;">Urutan:</span>
            <a href="home.php?sort=desc<?= $keyword ? '&cari='.urlencode($keyword) : '' ?>" 
               class="sort-btn <?= $sort === 'DESC' ? 'active' : 'inactive' ?>">Terbaru ✨</a>
            <a href="home.php?sort=asc<?= $keyword ? '&cari='.urlencode($keyword) : '' ?>" 
               class="sort-btn <?= $sort === 'ASC' ? 'active' : 'inactive' ?>">Terlama 🕒</a>
        </div>

        <?php if (empty($posts)): ?>
            <div style="text-align:center; padding:50px; color:#aaa;">Belum ada postingan. Buat postingan pertama!</div>
        <?php else: ?>
            <?php foreach ($posts as $p): ?>
                <article class="post-card">
                    <div style="display:flex;gap:18px;">
                        <img src="<?= foto_url($p['foto']) ?>" class="avatar-img">
                        <div style="flex:1;">
                            <div class="user-info">
                                <b style="color:var(--navy);"><?= nama_tampil($p['nama_lengkap'], $p['username']) ?></b><br>
                                <span style="color:#aaa; font-size:13px;">@<?= htmlspecialchars($p['username']) ?> • <?= time_ago($p['created_at']) ?></span>
                            </div>
                            <p class="post-body"><?= nl2br(htmlspecialchars($p['content'])) ?></p>
                            
                            <?php if (!empty($p['image'])): ?>
                                <img src="../uploads/<?= htmlspecialchars($p['image']) ?>" class="post-image">
                            <?php endif; ?>

                            <div class="action-bar">
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="action" value="like">
                                    <button type="submit" class="action-btn btn-like <?= $p['sudah_like'] ? 'liked' : '' ?>">
                                        <?= $p['sudah_like'] ? '❤️' : '🤍' ?> <?= $p['jumlah_like'] ?>
                                    </button>
                                </form>
                                <button class="action-btn" style="color:var(--accent);" onclick="toggleKomentar(<?= $p['id'] ?>)">
                                    💬 <?= count($p['komentar']) ?> Balas
                                </button>
                            </div>

                            <div class="comment-section" id="kom-<?= $p['id'] ?>" style="display:none;">
                                <?php foreach ($p['komentar'] as $k): ?>
                                    <div class="comment-item">
                                        <img src="<?= foto_url($k['foto']) ?>" class="avatar-sm">
                                        <div class="comment-bubble">
                                            <b><?= htmlspecialchars($k['username']) ?></b>
                                            <small style="color:#999;"> • <?= time_ago($k['created_at']) ?></small><br>
                                            <?= htmlspecialchars($k['content']) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <form method="POST" class="comment-form">
                                    <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="action" value="comment">
                                    <input type="text" name="comment_content" class="comment-input" placeholder="Tulis komentar..." required>
                                    <button type="submit" class="btn-send">Kirim</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <aside class="right-panel">
        <form action="home.php" method="GET">
            <input type="hidden" name="sort" value="<?= strtolower($sort) ?>">
            <input type="text" name="cari" class="search-box" placeholder="Cari #hashtag atau topik..." value="<?= htmlspecialchars($keyword) ?>">
        </form>

        <div class="user-card" style="margin-bottom:20px;">
            <?php
            $sql_me = "SELECT username, nama_lengkap, foto FROM users WHERE id = ?";
            $st = $conn->prepare($sql_me);
            $st->bind_param("i", $logged_in_id);
            $st->execute();
            $me = $st->get_result()->fetch_assoc();
            ?>
            <img src="<?= foto_url($me['foto']) ?>">
            <div>
                <div style="font-weight:700; color:var(--navy); font-size:14px;"><?= nama_tampil($me['nama_lengkap'], $me['username']) ?></div>
                <div style="color:#aaa; font-size:12px;">@<?= $me['username'] ?></div>
            </div>
        </div>

        <div class="trend-card">
            <h3 style="font-size:17px;margin-bottom:20px;color:var(--navy);font-weight:800;">Lagi Hangat 🔥</h3>
            <?php foreach ($top_hashtags as $tag => $count): ?>
                <a href="home.php?cari=<?= urlencode($tag) ?>&sort=<?= strtolower($sort) ?>" class="hashtag-item">
                    <span style="font-weight:700;color:var(--navy);font-size:14px;"><?= htmlspecialchars($tag) ?></span>
                    <span class="hashtag-badge"><?= $count ?> post</span>
                </a>
            <?php endforeach; ?>
        </div>
    </aside>

</div>

<script>
function toggleKomentar(postId) {
    const el = document.getElementById('kom-' + postId);
    if (el.style.display === 'none' || el.style.display === '') {
        el.style.display = 'block';
    } else {
        el.style.display = 'none';
    }
}
</script>

</body>
</html>