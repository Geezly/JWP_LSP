<?php
/**
 * Project: Interaksi - Logout Script
 */

// 1. Mulai session agar kita bisa mengakses data session yang sedang aktif
session_start();

// 2. Kosongkan semua data di dalam array $_SESSION
$_SESSION = array();

// 3. Jika menggunakan cookies untuk session (default PHP), hapus cookie-nya juga
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Hancurkan session secara total di sisi server
session_destroy();

// 5. Redirect user kembali ke halaman login
header("Location: login.php");
exit;
?>