<?php
/**
 * Project: Personalized Social Media (Desktop Mode - Feed Only)
 * Tema: Soft Blue & Navy (Clean Aesthetic)
 */

// Simulasi data dari database
$posts = [
    [
        'id' => 1,
        'nama' => 'Fahra Claudia Avina',
        'username' => '@fahra_avina',
        'isi' => 'Selamat datang di tampilan baru aplikasi saya! Sekarang area posting sudah dipisah agar lebih fokus. Klik tombol biru di kiri untuk mencoba.',
        'waktu' => '2j',
        'komentar' => [
            ['user' => '@admin', 'teks' => 'Desainnya makin keren, Fahra!']
        ]
    ],
    [
        'id' => 2,
        'nama' => 'User Testing',
        'username' => '@test_user',
        'isi' => 'Mencoba filter hashtag untuk tugas LSP nanti. #coding #webdev #LSP2026',
        'waktu' => '5j',
        'komentar' => []
    ]
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda / Interaksi</title>
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

        .desktop-wrapper {
            display: grid;
            grid-template-columns: 280px 1fr 320px;
            max-width: 1300px;
            margin: 0 auto;
            gap: 20px;
            padding: 0 20px;
        }

        /* --- SIDEBAR KIRI --- */
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
        .nav-link.active {
            background: var(--main-blue);
            color: white;
        }
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
            box-shadow: 0 8px 20px rgba(26, 42, 108, 0.2);
            transition: 0.3s;
        }
        .btn-create-post:hover {
            transform: scale(1.03);
            box-shadow: 0 10px 25px rgba(26, 42, 108, 0.3);
        }

        /* --- FEED TENGAH --- */
        .feed-container {
            padding-top: 20px;
        }
        .top-bar {
            background: rgba(255, 255, 255, 0.85);
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
        
        .avatar-circle {
            width: 50px; height: 50px;
            background: var(--sidebar-blue);
            border-radius: 18px;
            flex-shrink: 0;
        }

        .user-info b { color: var(--navy); font-size: 16px; }
        .user-info span { color: #aaa; font-size: 13px; }
        .post-body { margin: 15px 0; line-height: 1.7; color: #444; font-size: 15px; }

        /* --- PANEL KANAN --- */
        .right-panel { padding-top: 20px; }
        .search-box {
            width: 100%; padding: 16px 20px;
            border-radius: 20px; border: 1px solid #eee;
            background: white; outline: none; margin-bottom: 25px;
        }
        .trend-card {
            background: var(--white);
            padding: 25px;
            border-radius: 24px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.02);
        }
    </style>
</head>
<body>

<div class="desktop-wrapper">
    
    <aside class="side-nav">
        <div class="brand-logo">
            <div style="width:12px; height:35px; background:var(--main-blue); border-radius:6px;"></div>
            Interaksi
        </div>
        
        <nav>
            <a href="home.php" class="nav-link active">Eksplorasi</a>
            <a href="#" class="nav-link">Pesan Terkirim</a>
            <a href="#" class="nav-link">Notifikasi</a>
            <a href="profile.php" class="nav-link">Profil Saya</a>
            
            <a href="post_create.php" class="btn-create-post">+ Buat Postingan</a>
        </nav>

        <div style="margin-top: auto; padding-bottom: 30px;">
            <a href="logout.php" class="nav-link" style="color: #ff6b6b;">Keluar</a>
        </div>
    </aside>

    <main class="feed-container">
        <div class="top-bar">
            <h2 style="font-size: 20px; color: var(--navy); font-weight: 800;">Feed Terbaru</h2>
        </div>

        <?php foreach ($posts as $p): ?>
        <article class="post-card">
            <div style="display: flex; gap: 18px;">
                <div class="avatar-circle"></div>
                <div style="flex: 1;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                        <div class="user-info">
                            <b><?php echo $p['nama']; ?></b><br>
                            <span><?php echo $p['username']; ?> • <?php echo $p['waktu']; ?></span>
                        </div>
                        <div style="font-size: 12px; color: #ccc;">•••</div>
                    </div>
                    
                    <p class="post-body"><?php echo $p['isi']; ?></p>

                    <div style="display: flex; gap: 25px; border-top: 1px solid #f8f8f8; padding-top: 15px;">
                        <button style="border:none; background:none; color:var(--soft-blue); font-weight:600; cursor:pointer; font-size:13px;">Balas</button>
                        <button style="border:none; background:none; color:#bbb; font-weight:600; cursor:pointer; font-size:13px;">Suka</button>
                    </div>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </main>

    <aside class="right-panel">
        <input type="text" class="search-box" placeholder="Cari topik...">
        
        <div class="trend-card">
            <h3 style="font-size: 17px; margin-bottom: 20px; color: var(--navy); font-weight: 800;">Lagi Hangat</h3>
            
            <div style="margin-bottom: 18px;">
                <p style="font-size: 12px; color: #aaa; margin-bottom: 4px;">Paling Dicari</p>
                <p style="font-weight: bold; font-size: 14px; color: var(--navy);">#LSP_RekayasaPerangkatLunak</p>
            </div>
            
            <div style="margin-bottom: 18px;">
                <p style="font-size: 12px; color: #aaa; margin-bottom: 4px;">Sedang Tren</p>
                <p style="font-weight: bold; font-size: 14px; color: var(--navy);">#FigmaToCode</p>
            </div>

            <a href="#" style="color: var(--accent); text-decoration: none; font-size: 13px; font-weight: 600;">Tampilkan lainnya</a>
        </div>
    </aside>

</div>

</body>
</html>