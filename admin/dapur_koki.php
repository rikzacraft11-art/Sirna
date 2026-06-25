<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'koki') {
    header('Location: index.php?error=unauthorized');
    exit();
}

require_once '../core/database.php';

try {
    // Query untuk mengambil item pesanan yang masih perlu dimasak
    $sql = "
        SELECT 
            p.id_pesanan, 
            p.tgl_pesanan, 
            p.nomor_meja,
            m.nama_menu,
            m.harga_menu,
            dp.id_detail,
            dp.kuantitas,
            dp.catatan,
            dp.status AS item_status
        FROM pesanan p
        JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
        JOIN menu m ON dp.kode_menu = m.kode_menu
        WHERE p.status = 'pending'
        ORDER BY p.tgl_pesanan ASC, dp.id_detail ASC
    ";
    $stmt = $pdo->query($sql);
    $order_items_raw = $stmt->fetchAll();

    $orders = [];
    foreach ($order_items_raw as $item) {
        $orders[$item['id_pesanan']]['details']['tgl_pesanan'] = $item['tgl_pesanan'];
        $orders[$item['id_pesanan']]['details']['nomor_meja'] = $item['nomor_meja'];
        $orders[$item['id_pesanan']]['items'][] = $item;
    }

} catch (PDOException $e) {
    $orders = [];
    $error_message = "Gagal mengambil data pesanan: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dapur Koki - SIRNA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Raleway:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Raleway', sans-serif; background-color: #1a1a1a; }
        .font-playfair { font-family: 'Playfair Display', serif; }
        .sidebar { background-color: #1c1c1c; }
        .main-content { background-color: #111111; }
        .order-card { background-color: #2a2a2a; border: 1px solid #393C49; transition: all 0.5s ease; }
        .item-action-btn { transition: all 0.2s ease-in-out; }
    </style>
</head>
<body class="flex h-screen text-white">

    <!-- Sidebar -->
    <aside class="w-64 flex-shrink-0 bg-[#1C1C1C] p-6 flex flex-col justify-between">
        <div>
            <div class="mb-12">
                <img src="../assets/images/sirna.logo.png" alt="Logo SIRNA" class="w-full h-12 object-contain" onerror="this.onerror=null;this.src='https://placehold.co/320x80/1F1D2B/ffffff?text=SIRNA&font=playfairdisplay';">
            </div>
            <nav>
                <ul>
                    <li>
                        <a href="#" class="flex items-center p-3 rounded-lg bg-orange-600/20 border border-orange-500 text-orange-400">
                            <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                            Dapur (Koki)
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <div>
            <div class="flex items-center mb-4"><span class="h-2 w-2 bg-green-400 rounded-full mr-2"></span><span>Restaurant Open</span></div>
            <a href="logout.php" class="text-red-500 hover:underline">Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content flex-1 p-8 overflow-y-auto">
        <header class="flex justify-between items-center mb-8">
            <h1 class="font-playfair text-4xl">ORDER LIST</h1>
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center font-bold text-white mr-4"><?= strtoupper(substr($_SESSION['username'], 0, 1)); ?></div>
                <div><span class="font-semibold"><?= htmlspecialchars($_SESSION['username']); ?></span></div>
            </div>
        </header>

        <!-- Order Cards -->
        <div id="order-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php if (empty($orders)): ?>
                <p class="col-span-full text-center text-gray-400 mt-10">Tidak ada pesanan yang sedang diproses.</p>
            <?php else: ?>
                <?php foreach ($orders as $order_id => $order_data): ?>
                    <div class="order-card p-6 rounded-lg flex flex-col space-y-4" data-order-id="<?= $order_id ?>">
                        <div class="flex justify-between items-center">
                            <div>
                                <h2 class="text-xl font-bold text-orange-400">Order #<?= $order_id ?></h2>
                                <p class="text-xs text-gray-400"><?= date('d M Y, H:i', strtotime($order_data['details']['tgl_pesanan'])) ?></p>
                            </div>
                            <span class="bg-gray-700 text-white font-bold text-lg rounded-full w-10 h-10 flex items-center justify-center"><?= $order_data['details']['nomor_meja'] ?></span>
                        </div>

                        <!-- Items -->
                        <div class="flex-1 space-y-3">
                            <?php 
                                $total_items = 0;
                                $total_price = 0;
                            ?>
                            <?php foreach ($order_data['items'] as $item): ?>
                                <?php 
                                    $total_items += $item['kuantitas'];
                                    $total_price += $item['harga_menu'] * $item['kuantitas'];
                                ?>
                                <div class="item-card border-t border-gray-700 pt-3" data-item-id="<?= $item['id_detail'] ?>">
                                    <div class="flex justify-between">
                                        <div>
                                            <h3 class="font-bold"><?= htmlspecialchars($item['nama_menu']) ?></h3>
                                            <p class="text-xs text-gray-400"><?= htmlspecialchars($item['catatan'] ?: 'Tanpa catatan') ?></p>
                                            <p class="text-xs text-gray-500">Rp. <?= number_format($item['harga_menu'], 0, ',', '.') ?></p>
                                        </div>
                                        <p class="text-gray-300">Qty: <?= $item['kuantitas'] ?></p>
                                    </div>
                                    <div class="flex justify-end space-x-2 mt-2">
                                        <button class="item-action-btn bg-red-500/20 text-red-400 hover:bg-red-500 hover:text-white p-2 rounded-lg" onclick="updateItemStatus(this, <?= $item['id_detail'] ?>, 'rejected')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                        <button class="item-action-btn bg-blue-500/20 text-blue-400 hover:bg-blue-500 hover:text-white p-2 rounded-lg" onclick="updateItemStatus(this, <?= $item['id_detail'] ?>, 'completed')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Footer Kartu -->
                        <div class="border-t border-gray-700 pt-3 text-xs text-gray-400 flex justify-between">
                            <span><?= $total_items ?> Items</span>
                            <span>Rp. <?= number_format($total_price, 0, ',', '.') ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script>
        async function updateItemStatus(button, itemId, status) {
            const itemCard = button.closest('.item-card');
            if (!itemCard) return;

            // Menonaktifkan tombol untuk mencegah klik ganda
            button.disabled = true;
            itemCard.style.opacity = '0.5';

            try {
                const response = await fetch('api_update_order_item.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ item_id: itemId, status: status })
                });
                const result = await response.json();

                if (result.success) {
                    // Beri efek visual dan hapus item dari kartu
                    itemCard.style.transition = 'all 0.5s ease';
                    itemCard.style.transform = 'translateX(20px)';
                    itemCard.style.opacity = '0';
                    setTimeout(() => {
                        itemCard.remove();
                        // Cek apakah kartu pesanan utama masih memiliki item lain
                        const orderCard = document.querySelector(`.order-card[data-order-id='${result.order_id}']`);
                        if (orderCard && !orderCard.querySelector('.item-card')) {
                            orderCard.classList.add('opacity-0', 'scale-95');
                            setTimeout(() => orderCard.remove(), 500);
                        }
                    }, 500);
                } else {
                    alert('Gagal memperbarui status: ' + result.message);
                    button.disabled = false; // Aktifkan kembali jika gagal
                    itemCard.style.opacity = '1';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan koneksi.');
                button.disabled = false;
                itemCard.style.opacity = '1';
            }
        }
    </script>
</body>
</html>
