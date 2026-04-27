<?php
/**
 * Project: Personalized Social Media (Desktop Mode - Feed Only)
 * Tema: Soft Blue & Navy (Clean Aesthetic)
 */

session_start();

// Jika belum login, redirect ke halaman login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require_once '../config/database.php';

$logged_in_id       = $_SESSION['user_id'];
$logged_in_username = $_SESSION['username'];

// ── AKSI: LIKE / UNLIKE ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'like') {
    $post_id = (int)$_POST['post_id'];

    // Cek apakah sudah pernah like
    $cek = $conn->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
    $cek->bind_param("ii", $post_id, $logged_in_id);
    $cek->execute();
    $cek->store_result();

    if ($cek->num_rows > 0) {
        // Sudah like → unlike
        $del = $conn->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
        $del->bind_param("ii", $post_id, $logged_in_id);
        $del->execute();
    } else {
        // Belum like → like
        $ins = $conn->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
        $ins->bind_param("ii", $post_id, $logged_in_id);
        $ins->execute();
    }

    header("Location: home.php");
    exit;
}

// ── AKSI: TAMBAH KOMENTAR ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'comment') {
    $post_id = (int)$_POST['post_id'];
    $content = trim($conn->real_escape_string($_POST['content']));

    if ($content !== '') {
        $ins = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $ins->bind_param("iis", $post_id, $logged_in_id, $content);
        $ins->execute();
    }

    header("Location: home.php");
    exit;
}

// ── AMBIL SEMUA POST BESERTA DATA USER, JUMLAH LIKE, DAN KOMENTAR ─────────────
$sql_posts = "
    SELECT 
        p.id,
        p.content,
        p.image,
        p.file,
        p.created_at,
        u.username,
        u.nama_lengkap,
        u.foto,
        (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS jumlah_like,
        (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id AND l.user_id = ?) AS sudah_like
    FROM posts p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
";
$stmt_posts = $conn->prepare($sql_posts);
$stmt_posts->bind_param("i", $logged_in_id);
$stmt_posts->execute();
$result_posts = $stmt_posts->get_result();

$posts = [];
while ($row = $result_posts->fetch_assoc()) {
    // Ambil komentar untuk post ini
    $sql_kom = "
        SELECT c.content, c.created_at, u.username, u.nama_lengkap, u.foto
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ?
        ORDER BY c.created_at ASC
    ";
    $stmt_kom = $conn->prepare($sql_kom);
    $stmt_kom->bind_param("i", $row['id']);
    $stmt_kom->execute();
    $result_kom = $stmt_kom->get_result();
    $row['komentar'] = $result_kom->fetch_all(MYSQLI_ASSOC);

    $posts[] = $row;
}

// ── TRENDING HASHTAG (dari konten post) ───────────────────────────────────────
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

// Helper: format waktu relatif
function time_ago($datetime) {
    $now  = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);

    if ($diff->d >= 1)  return $diff->d . 'h yang lalu';
    if ($diff->h >= 1)  return $diff->h . 'j yang lalu';
    if ($diff->i >= 1)  return $diff->i . 'm yang lalu';
    return 'Baru saja';
}

// Helper: foto profil user
function foto_url($foto) {
    if (empty($foto) || $foto === 'default.png') {
        return 'https://ui-avatars.com/api/?name=U&background=85a3db&color=fff&size=100';
    }
    return 'uploads/' . htmlspecialchars($foto);
}

// Helper: nama tampil
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
            --border: #eff3f4;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--bg-gradient);
            color: #333;
            min-height: 100vh;
        }

        /* ── LAYOUT ─────────────────────────────────────── */
        .desktop-wrapper {
            display: grid;
            grid-template-columns: 280px 1fr 320px;
            max-width: 1300px;
            margin: 0 auto;
            gap: 20px;
            padding: 0 20px;
        }

        /* ── SIDEBAR KIRI ───────────────────────────────── */
        .side-nav {
            height: 100vh;
            position: sticky;
            top: 0;
            padding-top: 30px;
            display: flex;
            flex-direction: column;
        }
        .brand-logo {
            font-size: 26px;
            font-weight: 900;
            color: var(--navy);
            margin-bottom: 40px;
            padding-left: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .nav-link {
            display: block;
            padding: 15px 20px;
            text-decoration: none;
            color: #555;
            font-weight: 600;
            border-radius: 16px;
            transition: 0.3s;
            margin-bottom: 8px;
        }
        .nav-link:hover {
            background: var(--sidebar-blue);
            color: var(--accent);
            transform: translateX(8px);
        }
        .nav-link.active { background: var(--main-blue); color: white; }
        .btn-create-post {
            display: block;
            margin-top: 20px;
            padding: 16px;
            background: var(--navy);
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 18px;
            font-weight: bold;
            box-shadow: 0 8px 20px rgba(26,42,108,0.2);
            transition: 0.3s;
        }
        .btn-create-post:hover {
            transform: scale(1.03);
            box-shadow: 0 10px 25px rgba(26,42,108,0.3);
        }

        /* ── FEED TENGAH ─────────────────────────────────── */
        .feed-container { padding-top: 20px; }
        .top-bar {
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(12px);
            padding: 20px 25px;
            border-radius: 24px;
            margin-bottom: 25px;
            border: 1px solid rgba(255,255,255,0.4);
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }
        .post-card {
            background: var(--white);
            padding: 25px;
            border-radius: 28px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            border: 1px solid rgba(0,0,0,0.02);
            transition: 0.3s;
        }
        .post-card:hover { transform: translateY(-3px); }

        /* Avatar */
        .avatar-img {
            width: 50px; height: 50px;
            border-radius: 18px;
            object-fit: cover;
            flex-shrink: 0;
            background: var(--sidebar-blue);
        }
        .avatar-sm {
            width: 32px; height: 32px;
            border-radius: 10px;
            object-fit: cover;
            background: var(--sidebar-blue);
        }

        .user-info b  { color: var(--navy); font-size: 16px; }
        .user-info span { color: #aaa; font-size: 13px; }
        .post-body { margin: 15px 0; line-height: 1.7; color: #444; font-size: 15px; }

        /* Post image */
        .post-image {
            width: 100%; max-height: 400px;
            object-fit: cover;
            border-radius: 16px;
            margin-bottom: 15px;
        }

        /* Tombol aksi */
        .action-bar {
            display: flex;
            gap: 25px;
            border-top: 1px solid #f8f8f8;
            padding-top: 15px;
            align-items: center;
        }
        .action-btn {
            border: none;
            background: none;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 6px 14px;
            border-radius: 10px;
            transition: 0.2s;
        }
        .btn-like      { color: #bbb; }
        .btn-like.liked { color: #e0245e; }
        .btn-like:hover { background: #fff0f3; color: #e0245e; }
        .btn-reply     { color: var(--accent); }
        .btn-reply:hover { background: #e8f5ff; }

        /* Komentar */
        .comment-section { margin-top: 15px; padding-top: 15px; border-top: 1px dashed #eee; }
        .comment-item {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
            align-items: flex-start;
        }
        .comment-bubble {
            background: #f7faff;
            border-radius: 14px;
            padding: 10px 14px;
            flex: 1;
            font-size: 13px;
            line-height: 1.5;
        }
        .comment-bubble .kom-user { font-weight: 700; color: var(--navy); font-size: 12px; }
        .comment-bubble .kom-text { color: #555; margin-top: 2px; }

        /* Form komentar */
        .comment-form {
            display: flex;
            gap: 10px;
            margin-top: 12px;
            align-items: center;
        }
        .comment-input {
            flex: 1;
            padding: 10px 16px;
            border: 1px solid #eee;
            border-radius: 20px;
            outline: none;
            font-size: 13px;
            background: #fafafa;
            transition: 0.2s;
        }
        .comment-input:focus { border-color: var(--accent); background: white; }
        .btn-send {
            padding: 10px 18px;
            background: var(--navy);
            color: white;
            border: none;
            border-radius: 16px;
            font-weight: bold;
            font-size: 13px;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-send:hover { background: var(--accent); }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #aaa;
        }
        .empty-state p { font-size: 15px; margin-top: 10px; }

        /* ── PANEL KANAN ─────────────────────────────────── */
        .right-panel { padding-top: 20px; }
        .search-box {
            width: 100%; padding: 16px 20px;
            border-radius: 20px; border: 1px solid #eee;
            background: white; outline: none; margin-bottom: 25px;
            font-size: 14px;
        }
        .trend-card {
            background: var(--white);
            padding: 25px;
            border-radius: 24px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.02);
            margin-bottom: 20px;
        }
        .hashtag-item {
            padding: 12px 0;
            border-bottom: 1px solid #f5f5f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .hashtag-item:last-child { border-bottom: none; }
        .hashtag-badge {
            background: #f0f7ff;
            color: var(--accent);
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 600;
        }

        /* User card di sidebar kanan */
        .user-card {
            background: var(--white);
            padding: 20px 25px;
            border-radius: 24px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.02);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .user-card img { width: 48px; height: 48px; border-radius: 14px; object-fit: cover; }
        .user-card .uc-name { font-weight: 700; color: var(--navy); font-size: 14px; }
        .user-card .uc-user { color: #aaa; font-size: 12px; }
    </style>
</head>
<body>

<div class="desktop-wrapper">

    <!-- ── SIDEBAR KIRI ──────────────────────────────────────────── -->
    <aside class="side-nav">
        <div class="brand-logo">
            <div style="width:12px;height:35px;background:var(--main-blue);border-radius:6px;"></div>
            Interaksi
        </div>

        <nav>
            <a href="home.php"    class="nav-link active">Eksplorasi</a>
            <a href="#"           class="nav-link">Pesan Terkirim</a>
            <a href="#"           class="nav-link">Notifikasi</a>
            <a href="profile.php" class="nav-link">Profil Saya</a>
            <a href="post_create.php" class="btn-create-post">+ Buat Postingan</a>
        </nav>

        <div style="margin-top:auto;padding-bottom:30px;">
            <a href="logout.php" class="nav-link" style="color:#ff6b6b;">Keluar</a>
        </div>
    </aside>

    <!-- ── FEED TENGAH ───────────────────────────────────────────── -->
    <main class="feed-container">
        <div class="top-bar">
            <h2 style="font-size:20px;color:var(--navy);font-weight:800;">Feed Terbaru</h2>
        </div>

        <?php if (empty($posts)): ?>
        <div class="empty-state">
            <div style="font-size:48px;">📭</div>
            <p>Belum ada postingan. Jadilah yang pertama!</p>
            <a href="post_create.php" style="display:inline-block;margin-top:15px;padding:12px 24px;background:var(--navy);color:white;text-decoration:none;border-radius:14px;font-weight:bold;">+ Buat Postingan</a>
        </div>
        <?php else: ?>

        <?php foreach ($posts as $p): ?>
        <article class="post-card">
            <div style="display:flex;gap:18px;">
                <img src="<?= foto_url($p['foto']) ?>"
                     class="avatar-img"
                     alt="<?= htmlspecialchars($p['username']) ?>">

                <div style="flex:1;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                        <div class="user-info">
                            <b><?= nama_tampil($p['nama_lengkap'], $p['username']) ?></b><br>
                            <span>@<?= htmlspecialchars($p['username']) ?> • <?= time_ago($p['created_at']) ?></span>
                        </div>
                    </div>

                    <p class="post-body"><?= nl2br(htmlspecialchars($p['content'])) ?></p>

                    <?php if (!empty($p['image'])): ?>
                    <img src="uploads/<?= htmlspecialchars($p['image']) ?>"
                         class="post-image" alt="Gambar postingan">
                    <?php endif; ?>

                    <?php if (!empty($p['file'])): ?>
                    <a href="uploads/<?= htmlspecialchars($p['file']) ?>"
                       style="font-size:13px;color:var(--accent);display:block;margin-bottom:12px;"
                       target="_blank">📎 Unduh lampiran</a>
                    <?php endif; ?>

                    <!-- Tombol Aksi -->
                    <div class="action-bar">
                        <!-- Tombol Like -->
                        <form method="POST" style="margin:0;">
                            <input type="hidden" name="action"  value="like">
                            <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
                            <button type="submit"
                                class="action-btn btn-like <?= $p['sudah_like'] ? 'liked' : '' ?>">
                                <?= $p['sudah_like'] ? '❤️' : '🤍' ?>
                                <?= $p['jumlah_like'] ?>
                                <?= $p['sudah_like'] ? 'Disukai' : 'Suka' ?>
                            </button>
                        </form>

                        <!-- Toggle komentar -->
                        <button class="action-btn btn-reply"
                                onclick="toggleKomentar(<?= $p['id'] ?>)">
                            💬 <?= count($p['komentar']) ?> Balas
                        </button>
                    </div>

                    <!-- Seksi Komentar -->
                    <div class="comment-section" id="kom-<?= $p['id'] ?>" style="display:none;">
                        <?php foreach ($p['komentar'] as $k): ?>
                        <div class="comment-item">
                            <img src="<?= foto_url($k['foto']) ?>" class="avatar-sm" alt="">
                            <div class="comment-bubble">
                                <div class="kom-user">
                                    <?= nama_tampil($k['nama_lengkap'], $k['username']) ?>
                                    <span style="color:#ccc;font-weight:400;"> • <?= time_ago($k['created_at']) ?></span>
                                </div>
                                <div class="kom-text"><?= nl2br(htmlspecialchars($k['content'])) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <!-- Form kirim komentar -->
                        <form method="POST" class="comment-form">
                            <input type="hidden" name="action"  value="comment">
                            <input type="hidden" name="post_id" value="<?= $p['id'] ?>">
                            <input type="text"
                                   name="content"
                                   class="comment-input"
                                   placeholder="Tulis balasan..."
                                   required>
                            <button type="submit" class="btn-send">Kirim</button>
                        </form>
                    </div>

                </div>
            </div>
        </article>
        <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <!-- ── PANEL KANAN ───────────────────────────────────────────── -->
    <aside class="right-panel">
        <input type="text" class="search-box" placeholder="Cari topik...">

        <!-- User yang sedang login -->
        <div class="user-card" style="margin-bottom:20px;">
            <?php
            $sql_me = "SELECT username, nama_lengkap, foto FROM users WHERE id = ?";
            $st = $conn->prepare($sql_me);
            $st->bind_param("i", $logged_in_id);
            $st->execute();
            $me = $st->get_result()->fetch_assoc();
            ?>
            <img src="<?= foto_url($me['foto']) ?>" alt="Profil saya">
            <div>
                <div class="uc-name"><?= nama_tampil($me['nama_lengkap'], $me['username']) ?></div>
                <div class="uc-user">@<?= htmlspecialchars($me['username']) ?></div>
            </div>
            <a href="profile.php" style="margin-left:auto;font-size:12px;color:var(--accent);font-weight:600;text-decoration:none;">Edit</a>
        </div>

        <!-- Trending hashtag -->
        <div class="trend-card">
            <h3 style="font-size:17px;margin-bottom:20px;color:var(--navy);font-weight:800;">Lagi Hangat 🔥</h3>

            <?php if (empty($top_hashtags)): ?>
            <p style="color:#aaa;font-size:13px;">Belum ada hashtag populer.</p>
            <?php else: ?>
            <?php foreach ($top_hashtags as $tag => $count): ?>
            <div class="hashtag-item">
                <span style="font-weight:700;color:var(--navy);font-size:14px;"><?= htmlspecialchars($tag) ?></span>
                <span class="hashtag-badge"><?= $count ?> post</span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <a href="#" style="color:var(--accent);text-decoration:none;font-size:13px;font-weight:600;display:block;margin-top:15px;">Tampilkan lainnya</a>
        </div>
    </aside>

</div>

<script>
function toggleKomentar(postId) {
    const el = document.getElementById('kom-' + postId);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>

</body>
</html>