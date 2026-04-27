<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UserModel.php';

class AuthController {
    private $userModel;

    public function __construct($conn) {
        $this->userModel = new UserModel($conn);
    }

    public function handleRegister() {
        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                $error = "Username dan password wajib diisi.";
            } elseif (strlen($password) < 6) {
                $error = "Password minimal 6 karakter.";
            } else {
                if ($this->userModel->register($username, $password)) {
                    $success = "Akun berhasil dibuat! Silakan login.";
                } else {
                    $error = "Username sudah digunakan. Coba yang lain.";
                }
            }
        }

        return ['error' => $error, 'success' => $success];
    }

    public function handleLogin() {
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                $error = "Username dan password wajib diisi.";
            } else {
                $user = $this->userModel->getUserByUsername($username);

                if ($user && password_verify($password, $user['password'])) {
                    if (session_status() === PHP_SESSION_NONE) session_start();
                    $_SESSION['user_id']  = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    header("Location: ../views/home.php");
                    exit();
                } else {
                    $error = "Username atau password salah.";
                }
            }
        }

        return ['error' => $error];
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_destroy();
        header("Location: ../views/login.php");
        exit();
    }
}
?>