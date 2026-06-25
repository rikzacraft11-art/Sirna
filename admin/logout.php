<?php
// admin/logout.php

// Selalu mulai sesi di awal
session_start();

// Hapus semua variabel sesi
$_SESSION = array();

// Hancurkan sesi
session_destroy();

// Arahkan kembali ke halaman login dengan pesan sukses logout
header("location: index.php?success=loggedout");
exit;
?>
