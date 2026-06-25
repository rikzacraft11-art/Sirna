<?php
// core/database.php

$host = 'localhost';    // Biasanya 'localhost'
$dbname = 'db_sirna';   // NAMA DATABASE DISESUAIKAN
$user = 'root';         // Username database Anda
$pass = '';             // Password database Anda

try {
    // Membuat koneksi PDO (PHP Data Objects)
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    
    // Mengatur mode error untuk menampilkan exception jika terjadi kesalahan
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Mengatur mode fetch default ke associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Jika koneksi gagal, hentikan skrip dan tampilkan pesan error
    // Di lingkungan produksi, sebaiknya log error ini dan tampilkan pesan umum
    die("Koneksi ke database gagal: " . $e->getMessage());
}
?>
