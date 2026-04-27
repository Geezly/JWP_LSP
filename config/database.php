<?php

// Konfigurasi Database
$host = "localhost";
$user = "root";     $pass = "";         
$db   = "db_sosmed"; 

// Membuat koneksi
$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

?>