<?php
// admin/dashboard.php

// Memulai sesi dan memeriksa apakah user sudah login
session_start();
if (!isset($_SESSION['user_id'])) {
    // Jika belum login, tendang kembali ke halaman login
    header('Location: index.php');
    exit();
}

// Memasukkan file koneksi database
require_once '../core/database.php';

// Mengambil semua data reservasi untuk ditampilkan
try {
    $stmt = $pdo->query("SELECT * FROM reservations ORDER BY reservation_date DESC, reservation_time DESC");
    $reservations = $stmt->fetchAll();
} catch (PDOException $e) {
    $reservations = [];
    $error_message = "Gagal mengambil data reservasi: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SIRNA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Raleway:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Raleway', sans-serif; background-color: #181818; color: #e5e5e5; }
        .font-playfair { font-family: 'Playfair Display', serif; }
    </style>
</head>
<body>
    <div class="min-h-screen container mx-auto p-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="font-playfair text-4xl text-white">Dasbor Reservasi</h1>
            <div class="text-right">
                <p>Selamat datang, <span class="font-bold text-orange-400"><?= htmlspecialchars($_SESSION['username']); ?></span>!</p>
                <p class="text-sm text-gray-400">Peran: <?= htmlspecialchars(ucfirst($_SESSION['role'])); ?></p>
                <a href="logout.php" class="text-red-500 hover:underline text-sm">Logout</a>
            </div>
        </div>

        <!-- Tabel Reservasi -->
        <div class="bg-gray-900/50 rounded-lg p-6 overflow-x-auto">
            <?php if (isset($error_message)): ?>
                <p class="text-red-500"><?= $error_message ?></p>
            <?php elseif (empty($reservations)): ?>
                <p class="text-center text-gray-400">Belum ada data reservasi.</p>
            <?php else: ?>
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-gray-700">
                            <th class="p-4">Nama Pelanggan</th>
                            <th class="p-4">Tanggal & Waktu</th>
                            <th class="p-4">Jumlah Orang</th>
                            <th class="p-4">No. Meja</th>
                            <th class="p-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $res): ?>
                        <tr class="border-b border-gray-800 hover:bg-gray-800">
                            <td class="p-4"><?= htmlspecialchars($res['customer_name']); ?></td>
                            <td class="p-4"><?= htmlspecialchars($res['reservation_date'] . ' ' . $res['reservation_time']); ?></td>
                            <td class="p-4"><?= htmlspecialchars($res['party_size']); // DISESUAIKAN ?></td>
                            <td class="p-4"><?= htmlspecialchars($res['table_id']); ?></td>
                            <td class="p-4">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    <?= $res['status'] === 'confirmed' ? 'bg-green-500 text-green-900' : '' ?>
                                    <?= $res['status'] === 'pending' ? 'bg-yellow-500 text-yellow-900' : '' ?>
                                    <?= $res['status'] === 'cancelled' ? 'bg-red-500 text-red-900' : '' ?>
                                ">
                                    <?= htmlspecialchars(ucfirst($res['status'])); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
