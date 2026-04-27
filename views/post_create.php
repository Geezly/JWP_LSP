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
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Inter', -apple-system, sans-serif; 
            background: var(--bg-gradient);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        /* CONTAINER KARTU */
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

        /* AREA INPUT */
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
            border: none;
            outline: none;
            font-size: 19px;
            font-family: inherit;
            color: #333;
            resize: none;
            line-height: 1.6;
        }

        /* BAGIAN BAWAH (FILE & BUTTON) */
        .footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            margin-top: 10px;
        }
    </style>
</head>
<body>

    <div class="post-card">
        <div class="header">
            <h2>Buat Postingan</h2>
            <a href="home.php" class="close-btn">&times;</a>
        </div>

        <form action="../controllers/PostController.php" method="POST" enctype="multipart/form-data">
            <div class="user-row">
                <div class="avatar-preview"></div>
                <span style="font-weight: 600; color: #555;">Fahra Claudia Avina</span>
            </div>

            <textarea name="isi_postingan" placeholder="Apa yang sedang hangat hari ini?" maxlength="250" required autofocus></textarea>
            
            <p class="hint">Maksimal 250 karakter</p>

            <div class="footer">
                <label class="btn-upload">
                    📷 Tambah Gambar
                    <input type="file" name="gambar_post" style="display: none;">
                </label>

                <button type="submit" class="btn-post">Posting</button>
            </div>
        </form>
    </div>

</body>
</html>