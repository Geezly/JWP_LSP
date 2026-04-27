<?php
// controllers/AuthController.php
require_once 'config/database.php';
require_once 'models/UserModel.php';

class AuthController {
    private $userModel;
    private $db;

    public function __construct($conn) {
        $this->db = $conn;
        $this->userModel = new UserModel($conn);
    }

    /**
     * Logika untuk pendaftaran user baru
     */
    public function handleRegister() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
            $username = $_POST['username'];
            $password = $_POST['password'];

            if ($this->userModel->register($username, $password)) {
                echo "<script>alert('Berhasil daftar! Silakan login.'); window.location='login.php';</script>";
            } else {
                echo "<script>alert('Gagal daftar. Username mungkin sudah ada.');</script>";
            }
        }
    }

    /**
     * Logika untuk login user
     */
    public function handleLogin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
            $username = $_POST['username'];
            $password = $_POST['password'];

            // Mencari user berdasarkan username melalui model
            $user = $this->userModel->getUserByUsername($username);

            if ($user) {
                // Verifikasi password yang di-hash
                if (password_verify($password, $user['password'])) {
                    // Mulai session dan simpan data user
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];

                    // Arahkan ke halaman utama/timeline
                    header("Location: index.php");
                    exit();
                } else {
                    return "Password salah!";
                }
            } else {
                return "Username tidak ditemukan!";
            }
        }
    }

    /**
     * Logika untuk logout
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        header("Location: login.php");
        exit();
    }
}