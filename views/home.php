<?php
/**
 * Project: Premium X-Style (Light Blue Theme)
 * Deskripsi: Desain Modern, Clean, dan High-End untuk LSP.
 */

$posts = [
    [
        'id' => 1,
        'nama' => 'Fahra Claudia',
        'username' => '@fahra_claudia',
        'isi' => 'Membangun ekosistem digital yang bersih dan minimalis. Desain ini menggunakan skema warna Blue Sky. 🚀 #Creative #WebDev',
        'waktu' => 'Sekarang',
        'komentar' => [
            ['user' => '@ui_ux_designer', 'teks' => 'Visualnya sangat nyaman di mata!']
        ]
    ]
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda / Premium Interaction</title>
    <style>
        /* 1. VARIABEL WARNA (Agar gampang dijelaskan) */
        :root {
            --primary-blue: #e3f2fd; /* Biru sangat muda untuk bg */
            --accent-blue: #1d9bf0;  /* Biru X untuk tombol */
            --soft-blue: #85a3db;    /* Biru muda pilihanmu */
            --glass: rgba(255, 255, 255, 0.8);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Inter', -apple-system, sans-serif; 
            background: linear-gradient(135deg, #f5f7fa 0%, #e3f2fd 100%);
            color: #1a1a1a;
            min-height: 100vh;
            display: flex;
            justify-content: center;
        }

        .container {
            width: 100%;
            max-width: 650px;
            background: var(--glass);
            backdrop-filter: blur(10px);
            border-left: 1px solid rgba(255,255,255,0.3);
            border-right: 1px solid rgba(255,255,255,0.3);
            box-shadow: 0 8px 32px rgba(0,0,0,0.05);
        }

        /* 2. HEADER MODEREN */
        header {
            padding: 20px;
            background: rgba(255, 255, 255, 0.9);
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid #f0f0f0;
        }
        header h2 { font-weight: 900; letter-spacing: -0.5px; color: var(--soft-blue); }

        /* 3. COMPOSER (Input Area) */
        .composer {
            padding: 25px;
            background: white;
            margin: 15px;
            border-radius: 24px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
        }
        .avatar {
            width: 50px; height: 50px;
            border-radius: 30px; /* Sudut kotak tumpul (squircle) */
            background: var(--soft-blue);
            flex-shrink: 0;
        }
        textarea {
            width: 100%; border: none; outline: none;
            font-size: 18px; resize: none; padding: 10px;
            font-family: inherit; margin-bottom: 15px;
        }
        .btn-post {
            background: var(--accent-blue);
            color: white; border: none;
            padding: 12px 28px; border-radius: 14px;
            font-weight: bold; cursor: pointer;
            box-shadow: 0 4px 12px rgba(29, 155, 240, 0.3);
            transition: 0.3s;
        }
        .btn-post:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(29, 155, 240, 0.4); }

        /* 4. FEED CARD */
        .post-card {
            background: white;
            margin: 0 15px 15px 15px;
            padding: 20px;
            border-radius: 24px;
            display: flex; gap: 15px;
            transition: 0.3s;
            border: 1px solid transparent;
        }
        .post-card:hover { 
            border-color: var(--soft-blue);
            transform: scale(1.01);
        }
        
        .post-user b { font-size: 16px; color: #111; }
        .post-user span { color: #888; font-size: 14px; }
        .post-text { margin: 10px 0; line-height: 1.6; font-size: 15px; }

        /* 5. INTERACTION (Reply) */
        .reply-box {
            background: #f8faff;
            padding: 15px;
            border-radius: 18px;
            margin-top: 15px;
        }
        .reply-item { font-size: 13px; margin-bottom: 8px; border-left: 3px solid var(--soft-blue); padding-left: 10px; }
        
        .input-reply {
            width: 100%; background: white; border: 1px solid #eee;
            padding: 10px 15px; border-radius: 12px; font-size: 13px;
            outline: none;
        }

    </style>
</head>
<body>

<div class="container">
    <header>
        <h2>Home</h2>
    </header>

    <div class="composer">
        <div style="display: flex; gap: 15px;">
            <div class="avatar"></div>
            <div style="flex: 1;">
                <form action="#" method="POST">
                    <textarea maxlength="250" placeholder="Apa cerita hari ini?"></textarea>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <input type="file" style="font-size: 12px;">
                        <button type="submit" class="btn-post">Post</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php foreach ($posts as $p): ?>
    <div class="post-card">
        <div class="avatar" style="background: #e0e0e0; width: 45px; height: 45px;"></div>
        <div style="flex: 1;">
            <div style="display: flex; justify-content: space-between;">
                <div class="post-user">
                    <b><?php echo $p['nama']; ?></b> 
                    <span><?php echo $p['username']; ?></span>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="#" style="font-size: 12px; color: var(--soft-blue); text-decoration: none;">Ubah</a>
                    <a href="#" style="font-size: 12px; color: #ff6b6b; text-decoration: none;">Hapus</a>
                </div>
            </div>
            
            <p class="post-text"><?php echo $p['isi']; ?></p>

            <div class="reply-box">
                <?php foreach ($p['komentar'] as $k): ?>
                    <div class="reply-item">
                        <b style="color: var(--accent-blue);"><?php echo $k['user']; ?></b>: <?php echo $k['teks']; ?>
                    </div>
                <?php endforeach; ?>
                <input type="text" class="input-reply" placeholder="Balas postingan ini...">
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

</body>
</html>