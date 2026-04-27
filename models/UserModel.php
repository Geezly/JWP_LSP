<?php
class UserModel {
    private $db;

    public function __construct($db_conn) {
        $this->db = $db_conn;
    }

    // Registrasi user baru
    public function register($username, $password) {
        // Cek apakah username sudah ada
        $check = mysqli_prepare($this->db, "SELECT id FROM users WHERE username = ?");
        mysqli_stmt_bind_param($check, "s", $username);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);
        if (mysqli_stmt_num_rows($check) > 0) {
            mysqli_stmt_close($check);
            return false; // Username sudah dipakai
        }
        mysqli_stmt_close($check);

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($this->db, "INSERT INTO users (username, password) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ss", $username, $hashed);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }

    // Ambil user berdasarkan username
    public function getUserByUsername($username) {
        $stmt = mysqli_prepare($this->db, "SELECT * FROM users WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $user;
    }
}
?>