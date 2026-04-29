<?php
require_once __DIR__ . '/../controllers/AuthController.php';

$auth   = new AuthController($conn);
$result = $auth->handleRegister();
$error   = $result['error'];
$success = $result['success'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --brown-deep:   #3b2314;
            --brown-mid:    #6b3f26;
            --brown-warm:   #a0674a;
            --brown-light:  #c9a48a;
            --brown-pale:   #ede0d4;
            --cream:        #faf6f1;
            --white:        #ffffff;
            --text-dark:    #2a1a0e;
            --text-muted:   #9a7b6a;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Jost', sans-serif;
            background-color: var(--cream);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        body::after {
            content: '';
            position: fixed;
            top: 32px; left: 32px; right: 32px; bottom: 32px;
            border: 1px solid rgba(107,63,38,0.12);
            pointer-events: none;
            border-radius: 2px;
        }

        .wrapper {
            display: flex;
            width: 100%;
            max-width: 920px;
            min-height: 520px;
            box-shadow: 0 30px 80px rgba(59,35,20,0.18), 0 2px 8px rgba(59,35,20,0.08);
            border-radius: 4px;
            overflow: hidden;
            animation: fadeUp 0.7s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(28px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Left panel — decorative */
        .panel-left {
            width: 42%;
            background-color: var(--brown-deep);
            padding: 56px 48px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .panel-left::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 260px; height: 260px;
            border-radius: 50%;
            background: rgba(160,103,74,0.15);
        }

        .panel-left::after {
            content: '';
            position: absolute;
            bottom: -80px; left: -40px;
            width: 220px; height: 220px;
            border-radius: 50%;
            background: rgba(107,63,38,0.25);
        }

        .brand {
            position: relative;
            z-index: 1;
        }

        .brand-mark {
            width: 44px;
            height: 44px;
            border: 1.5px solid var(--brown-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 28px;
        }

        .brand-mark svg {
            width: 20px;
            height: 20px;
            fill: var(--brown-light);
        }

        .brand-tagline {
            font-family: 'Cormorant Garamond', serif;
            font-size: 32px;
            font-weight: 300;
            color: var(--white);
            line-height: 1.3;
            letter-spacing: 0.01em;
        }

        .brand-tagline em {
            font-style: italic;
            color: var(--brown-light);
        }

        .panel-quote {
            position: relative;
            z-index: 1;
        }

        .quote-line {
            width: 28px;
            height: 1.5px;
            background: var(--brown-warm);
            margin-bottom: 16px;
        }

        .quote-text {
            font-family: 'Cormorant Garamond', serif;
            font-size: 15px;
            font-style: italic;
            font-weight: 300;
            color: var(--brown-pale);
            line-height: 1.7;
            opacity: 0.75;
        }

        /* Right panel — form */
        .panel-right {
            flex: 1;
            background: var(--white);
            padding: 56px 52px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header {
            margin-bottom: 36px;
        }

        .form-eyebrow {
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--brown-warm);
            margin-bottom: 10px;
        }

        .form-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 38px;
            font-weight: 600;
            color: var(--brown-deep);
            line-height: 1.1;
        }

        .form-subtitle {
            margin-top: 8px;
            font-size: 13.5px;
            font-weight: 300;
            color: var(--text-muted);
            letter-spacing: 0.02em;
        }

        .divider {
            width: 40px;
            height: 2px;
            background: var(--brown-light);
            margin: 20px 0 32px;
        }

        /* Notifications */
        .error {
            background: #fdf3f0;
            color: #8b2e1a;
            border-left: 3px solid #c0392b;
            padding: 11px 16px;
            margin-bottom: 22px;
            font-size: 13px;
            font-weight: 400;
            letter-spacing: 0.01em;
            border-radius: 0 4px 4px 0;
        }

        .success {
            background: #f2f9f4;
            color: #1e6641;
            border-left: 3px solid #27ae60;
            padding: 14px 18px;
            margin-bottom: 22px;
            font-size: 13.5px;
            font-weight: 400;
            letter-spacing: 0.01em;
            border-radius: 0 4px 4px 0;
            line-height: 1.6;
        }

        .success a {
            display: inline-block;
            margin-top: 10px;
            color: var(--brown-warm);
            font-weight: 500;
            font-size: 12px;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            text-decoration: none;
            border-bottom: 1px solid transparent;
            transition: border-color 0.2s;
        }

        .success a:hover {
            border-color: var(--brown-warm);
        }

        /* Inputs */
        .field {
            position: relative;
            margin-bottom: 18px;
        }

        .field label {
            display: block;
            font-size: 11px;
            font-weight: 500;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--brown-mid);
            margin-bottom: 7px;
        }

        .field input {
            width: 100%;
            padding: 13px 16px;
            border: 1px solid var(--brown-pale);
            border-radius: 2px;
            background: var(--cream);
            font-family: 'Jost', sans-serif;
            font-size: 14.5px;
            font-weight: 300;
            color: var(--text-dark);
            outline: none;
            transition: border-color 0.25s, background 0.25s, box-shadow 0.25s;
            letter-spacing: 0.02em;
        }

        .field input::placeholder {
            color: var(--brown-light);
            font-weight: 300;
        }

        .field input:focus {
            border-color: var(--brown-warm);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(160,103,74,0.1);
        }

        .field-hint {
            margin-top: 5px;
            font-size: 11.5px;
            color: var(--text-muted);
            font-weight: 300;
            letter-spacing: 0.01em;
        }

        /* Button */
        .btn {
            width: 100%;
            padding: 15px;
            margin-top: 10px;
            background: var(--brown-deep);
            color: var(--white);
            border: none;
            border-radius: 2px;
            font-family: 'Jost', sans-serif;
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            cursor: pointer;
            transition: background 0.25s, transform 0.2s, box-shadow 0.25s;
        }

        .btn:hover {
            background: var(--brown-mid);
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(59,35,20,0.22);
        }

        .btn:active {
            transform: translateY(0);
        }

        /* Footer link */
        .form-footer {
            margin-top: 28px;
            font-size: 13px;
            font-weight: 300;
            color: var(--text-muted);
            letter-spacing: 0.01em;
        }

        .form-footer a {
            color: var(--brown-warm);
            text-decoration: none;
            font-weight: 500;
            border-bottom: 1px solid transparent;
            transition: border-color 0.2s;
        }

        .form-footer a:hover {
            border-color: var(--brown-warm);
        }

        /* Responsive */
        @media (max-width: 680px) {
            body::after { display: none; }
            .wrapper { flex-direction: column; border-radius: 0; min-height: 100vh; }
            .panel-left { width: 100%; min-height: 200px; padding: 40px 36px; }
            .brand-tagline { font-size: 26px; }
            .panel-quote { display: none; }
            .panel-right { padding: 40px 36px; }
            .form-title { font-size: 30px; }
        }
    </style>
</head>
<body>

<div class="wrapper">

    <!-- Left decorative panel -->
    <div class="panel-left">
        <div class="brand">
            <div class="brand-mark">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2L2 7v10l10 5 10-5V7L12 2zm0 2.18L20 8.5v7L12 19.82 4 15.5v-7L12 4.18z"/>
                </svg>
            </div>
            <p class="brand-tagline">Mulai<br><em>Perjalanan</em><br>Anda.</p>
        </div>
        <div class="panel-quote">
            <div class="quote-line"></div>
            <p class="quote-text">Bergabunglah dan mulai<br>berbagi cerita bersama<br>komunitas kami.</p>
        </div>
    </div>

    <!-- Right form panel -->
    <div class="panel-right">
        <div class="form-header">
            <p class="form-eyebrow">Pendaftaran Akun</p>
            <h2 class="form-title">Daftar</h2>
            <p class="form-subtitle">Isi formulir berikut untuk membuat akun baru</p>
        </div>
        <div class="divider"></div>

        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success">
                <?= htmlspecialchars($success) ?>
                <br><a href="login.php">Masuk sekarang →</a>
            </div>
        <?php endif; ?>

        <?php if (empty($success)): ?>
        <form method="POST" action="">
            <div class="field">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Pilih username unik Anda"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Buat password yang kuat" required>
                <p class="field-hint">Minimal 6 karakter</p>
            </div>
            <button type="submit" class="btn">Buat Akun Sekarang</button>
        </form>
        <?php endif; ?>

        <p class="form-footer">
            Sudah punya akun? <a href="login.php">Masuk di sini</a>
        </p>
    </div>

</div>

</body>
</html>