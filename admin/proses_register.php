<?php
// admin/proses_register.php

// Memasukkan file koneksi database
require_once '../core/database.php';

// Cek apakah data form sudah dikirim dengan metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Ambil data dari form
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role']; // Ini adalah 'jabatan' di database

    // 1. Validasi Input
    // Cek apakah ada field yang kosong
    if (empty($username) || empty($password) || empty($confirm_password) || empty($role)) {
        header('Location: register.php?error=emptyfields');
        exit();
    }

    // Cek apakah password dan konfirmasi password cocok
    if ($password !== $confirm_password) {
        header('Location: register.php?error=passwordmismatch');
        exit();
    }

    try {
        // 2. Cek Apakah Username Sudah Ada di tabel 'pegawai'
        $sql_check = "SELECT id_pegawai FROM pegawai WHERE username = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$username]);
        if ($stmt_check->fetch()) {
            // Jika username sudah ada, kembalikan ke halaman registrasi dengan error
            header('Location: register.php?error=usernameexists');
            exit();
        }

        // 3. Enkripsi Password
        // Menggunakan algoritma BCRYPT yang aman
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // 4. Simpan Pengguna Baru ke Database 'pegawai'
        // Menyesuaikan nama kolom: 'nama_pegawai' diisi dengan username untuk sementara
        // 'jabatan' diisi dengan role dari form
        $sql_insert = "INSERT INTO pegawai (nama_pegawai, username, password, jabatan) VALUES (?, ?, ?, ?)";
        $stmt_insert = $pdo->prepare($sql_insert);
        // Menggunakan username sebagai nama_pegawai untuk awal
        $stmt_insert->execute([$username, $username, $hashed_password, $role]);

        // 5. Arahkan ke Halaman Login dengan Pesan Sukses
        header('Location: index.php?success=registered');
        exit();

    } catch (PDOException $e) {
        // Jika terjadi error pada database, tampilkan pesan error untuk debugging
        // Di lingkungan produksi, baris ini harus dihapus atau diganti dengan log
        // die("Database error: " . $e->getMessage()); 
        header('Location: register.php?error=dberror');
        exit();
    }
} else {
    // Jika halaman diakses langsung, kembalikan ke halaman registrasi
    header('Location: register.php');
    exit();
}
?>
