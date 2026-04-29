<?php
/**
 * Project: Interaksi - Logout Script
 */

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


session_destroy();


header("Location: login.php");
exit;
?>