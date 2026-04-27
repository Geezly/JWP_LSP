<?php
// controllers/AuthController.php
require_once 'config/database.php';
require_once 'models/UserModel.php';

class AuthController {
    private $userModel;

    public function __construct($conn) {
        $this->userModel = new UserModel($conn);
    }

    public function handleRegister() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'];
            $password = $_POST['password'];

            if ($this->userModel->register($username, $password)) {
                echo "<script>alert('Berhasil daftar! Silakan login.');</script>";
            } else {
                echo "<script>alert('Gagal daftar.');</script>";
            }
        }
    }
}