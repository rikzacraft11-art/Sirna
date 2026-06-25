<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'kasir') {
    header('Location: index.php?error=unauthorized');
    exit();
}

require_once '../core/database.php';
$username = $_SESSION['username'];
$user_initial = strtoupper(substr($username, 0, 1));

// Ambil data pesanan yang siap bayar (status 'completed')
try {
    $sql_completed = "
        SELECT 
            p.id_pesanan, p.tgl_pesanan, p.nomor_meja,
            SUM(m.harga_menu * dp.kuantitas) as total_harga
        FROM pesanan p
        JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
        JOIN menu m ON dp.kode_menu = m.kode_menu
        WHERE p.status = 'completed'
        GROUP BY p.id_pesanan
        ORDER BY p.tgl_pesanan ASC
    ";
    $completed_orders = $pdo->query($sql_completed)->fetchAll(PDO::FETCH_ASSOC);

    // Ambil detail item untuk setiap pesanan yang siap bayar
    if (!empty($completed_orders)) {
        $order_ids = array_column($completed_orders, 'id_pesanan');
        $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
        
        $sql_items = "
            SELECT dp.id_pesanan, m.nama_menu, dp.kuantitas, dp.catatan
            FROM detail_pesanan dp
            JOIN menu m ON dp.kode_menu = m.kode_menu
            WHERE dp.id_pesanan IN ($placeholders)
        ";
        $stmt_items = $pdo->prepare($sql_items);
        $stmt_items->execute($order_ids);
        $items_raw = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

        $items_by_order = [];
        foreach ($items_raw as $item) {
            $items_by_order[$item['id_pesanan']][] = $item;
        }

        foreach ($completed_orders as &$order) {
            $order['items'] = $items_by_order[$order['id_pesanan']] ?? [];
        }
    }

    // Ambil data riwayat transaksi (status 'paid' atau 'cancelled')
    $sql_history = "
        SELECT 
            t.id_transaksi, p.id_pesanan, p.nomor_meja, t.tgl_transaksi, t.total_bayar, t.metode_bayar,
            (SELECT GROUP_CONCAT(m.nama_menu SEPARATOR ', ') FROM detail_pesanan dp JOIN menu m ON dp.kode_menu = m.kode_menu WHERE dp.id_pesanan = p.id_pesanan) as menu_items,
            'PAID' as status
        FROM transaksi t
        JOIN pesanan p ON t.id_pesanan = p.id_pesanan
        ORDER BY t.tgl_transaksi DESC
        LIMIT 10
    ";
    $history_orders = $pdo->query($sql_history)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Kasir - SIRNA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Raleway:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Raleway', sans-serif; background-color: #111111; }
        .font-playfair { font-family: 'Playfair Display', serif; }
        .btn-gradient { background-image: linear-gradient(to right, #FFA114, #F6421A); transition: all 0.3s ease-in-out; color: #ffffff; }
        .btn-gradient:hover:not(:disabled) { transform: scale(1.05); box-shadow: 0 10px 20px rgba(246, 66, 26, 0.3); }
        @media print {
            body * { visibility: hidden; }
            #receipt-printable-area, #receipt-printable-area * { visibility: visible; }
            #receipt-printable-area { position: absolute; left: 0; top: 0; width: 100%; background-color: white !important; color: black !important; }
            .no-print { display: none; }
        }
    </style>
</head>
<body class="bg-[#111111]">
    <div class="flex h-screen text-gray-200">
        <!-- Sidebar -->
        <aside class="w-64 flex-shrink-0 bg-[#1C1C1C] p-6 flex flex-col justify-between">
            <div>
                <div class="mb-12">
                     <img src="../assets/images/sirna.logo.png" alt="Logo SIRNA" class="w-full h-12 object-contain" onerror="this.onerror=null;this.src='https://placehold.co/320x80/1C1C1C/ffffff?text=SIRNA&font=playfairdisplay';">
                </div>
                <nav>
                    <ul>
                        <li class="mb-4">
                            <a href="#" class="flex items-center p-3 rounded-lg bg-gradient-to-r from-orange-500 to-red-500 text-white font-bold shadow-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                KASIR
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            <div>
                <div class="flex items-center mb-4"><span class="h-3 w-3 bg-green-500 rounded-full mr-3 animate-pulse"></span><span>Restaurant Open</span></div>
                <a href="logout.php" class="text-red-500 hover:underline">Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8 overflow-y-auto">
            <header class="flex justify-between items-center mb-8">
                <div class="relative w-full max-w-md">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" /></svg></span>
                    <input type="text" placeholder="Cari pesanan atau pelanggan..." class="w-full bg-[#1C1C1C] border border-gray-700 rounded-lg py-2 pl-12 pr-4 focus:outline-none focus:ring-2 focus:ring-[#FFA114]">
                </div>
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center font-bold text-white mr-4"><?= $user_initial ?></div>
                    <div><span class="font-semibold"><?= htmlspecialchars($username) ?></span></div>
                </div>
            </header>

            <!-- Kasir Section -->
            <section id="kasir-section" class="mb-8">
                <h2 class="font-playfair text-2xl font-bold text-white mb-4">Pesanan Siap Dibayar</h2>
                <div id="completed-orders-list" class="space-y-4">
                    <?php if (empty($completed_orders)): ?>
                        <p class="text-gray-400">Tidak ada pesanan yang siap dibayar saat ini.</p>
                    <?php else: ?>
                        <?php foreach ($completed_orders as $order): ?>
                        <div class="bg-[#1C1C1C] p-6 rounded-lg border border-gray-800">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <p class="font-bold text-white">Meja <?= htmlspecialchars($order['nomor_meja']) ?></p>
                                    <p class="text-sm text-gray-400">#<?= $order['id_pesanan'] ?></p>
                                </div>
                                <p class="text-sm text-gray-400"><?= date('d M Y, H:i', strtotime($order['tgl_pesanan'])) ?></p>
                            </div>
                            <div class="space-y-3 border-t border-b border-gray-700 py-3">
                                <?php foreach ($order['items'] as $item): ?>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-semibold text-white"><?= htmlspecialchars($item['nama_menu']) ?></p>
                                        <p class="text-sm text-gray-500"><?= htmlspecialchars($item['catatan'] ?: 'Tanpa catatan') ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-gray-400">Qty: <?= $item['kuantitas'] ?></p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-6 flex justify-between items-center">
                                <div class="flex items-center">
                                    <label for="payment-method-<?= $order['id_pesanan'] ?>" class="mr-4 text-white">Metode Bayar:</label>
                                    <select id="payment-method-<?= $order['id_pesanan'] ?>" class="bg-gray-700/50 border border-gray-600 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#FFA114]">
                                        <option value="cash">Cash</option>
                                        <option value="card">Debit Card</option>
                                        <option value="qris">QRIS</option>
                                    </select>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-lg text-white mr-6">TOTAL:</span>
                                    <span class="text-2xl font-bold text-orange-400 mr-8">Rp. <?= number_format($order['total_harga'], 0, ',', '.') ?></span>
                                    <button class="pay-button btn-gradient font-bold py-3 px-8 rounded-lg" data-order-id="<?= $order['id_pesanan'] ?>" data-total="<?= $order['total_harga'] ?>">Bayar</button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Order History -->
            <section>
                <h2 class="font-playfair text-2xl font-bold text-white mb-4">Riwayat Pesanan</h2>
                <div class="bg-[#1C1C1C] rounded-lg overflow-hidden border border-gray-800">
                    <table class="w-full text-left">
                        <thead class="bg-black/50">
                            <tr>
                                <th class="p-4 font-semibold">ID Pesanan</th>
                                <th class="p-4 font-semibold">Meja</th>
                                <th class="p-4 font-semibold">Menu</th>
                                <th class="p-4 font-semibold">Total Bayar</th>
                                <th class="p-4 font-semibold">Tanggal</th>
                                <th class="p-4 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($history_orders)): ?>
                                <tr><td colspan="6" class="p-4 text-center text-gray-500">Belum ada riwayat transaksi.</td></tr>
                            <?php else: ?>
                                <?php foreach ($history_orders as $history): ?>
                                <tr class="border-b border-gray-800">
                                    <td class="p-4">#<?= $history['id_pesanan'] ?></td>
                                    <td class="p-4"><?= htmlspecialchars($history['nomor_meja']) ?></td>
                                    <td class="p-4"><?= htmlspecialchars($history['menu_items']) ?></td>
                                    <td class="p-4">Rp. <?= number_format($history['total_bayar'], 0, ',', '.') ?></td>
                                    <td class="p-4"><?= date('d M Y, H:i', strtotime($history['tgl_transaksi'])) ?></td>
                                    <td class="p-4"><span class="px-3 py-1 text-sm font-semibold rounded-full bg-green-500/20 text-green-400">PAID</span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <!-- Receipt Modal -->
    <div id="receipt-modal" class="fixed inset-0 bg-black bg-opacity-80 flex justify-center items-center hidden z-50">
        <div class="bg-[#1C1C1C] text-gray-200 p-1 rounded-lg shadow-2xl w-full max-w-sm border border-gray-700">
            <div id="receipt-printable-area" class="bg-white text-gray-800 p-8">
                <!-- Konten struk akan diisi oleh JavaScript -->
            </div>
            <div class="mt-4 p-4 flex justify-between no-print">
                <button id="close-modal-button" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-6 rounded-lg transition-colors">Tutup</button>
                <button id="print-button" class="btn-gradient font-bold py-2 px-6 rounded-lg">Cetak Struk</button>
            </div>
        </div>
    </div>

    <script>
        const completedOrdersList = document.getElementById('completed-orders-list');
        const receiptModal = document.getElementById('receipt-modal');
        const closeModalButton = document.getElementById('close-modal-button');
        const printButton = document.getElementById('print-button');
        const receiptPrintableArea = document.getElementById('receipt-printable-area');

        completedOrdersList.addEventListener('click', async (e) => {
            if (e.target.classList.contains('pay-button')) {
                const orderId = e.target.dataset.orderId;
                const total = e.target.dataset.total;
                const method = document.getElementById(`payment-method-${orderId}`).value;
                
                e.target.textContent = 'Memproses...';
                e.target.disabled = true;

                try {
                    const response = await fetch('api_process_payment.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id_pesanan: orderId, total_bayar: total, metode_bayar: method })
                    });
                    
                    if (!response.ok) {
                        const errorText = await response.text();
                        throw new Error(`Server Error: ${response.status}. Pesan: ${errorText}`);
                    }

                    const result = await response.json();
                    if (result.success) {
                        showReceipt(result.transaction_details);
                        // PERBAIKAN: Hapus kartu pesanan dari tampilan setelah berhasil bayar
                        e.target.closest('.bg-\\[\\#1C1C1C\\]').remove();
                    } else {
                        alert('Gagal memproses pembayaran: ' + result.message);
                    }
                } catch (error) {
                    alert('Terjadi kesalahan. Silakan cek konsol untuk detail.\n\n' + error.message);
                    console.error("Detail Error:", error);
                } finally {
                     e.target.textContent = 'Bayar';
                     e.target.disabled = false;
                }
            }
        });

        function showReceipt(details) {
            receiptPrintableArea.innerHTML = `
                <div class="text-center">
                    <h2 class="text-2xl font-bold font-playfair">SIRNA RESTO</h2>
                    <p class="text-sm">Jl. Kenangan Indah No. 42, Jakarta</p>
                </div>
                <hr class="my-4 border-gray-300 border-dashed">
                <div>
                    <p class="text-sm">No. Struk: #${details.id_transaksi}</p>
                    <p class="text-sm">Kasir: ${details.nama_kasir}</p>
                    <p class="text-sm">Tanggal: ${new Date(details.tgl_transaksi).toLocaleString('id-ID')}</p>
                    <p class="text-sm">Meja: ${details.nomor_meja}</p>
                </div>
                <hr class="my-4 border-gray-300 border-dashed">
                <div>
                    ${details.items.map(item => `
                        <div class="flex justify-between text-sm mb-1">
                            <span>${item.nama_menu} (x${item.kuantitas})</span>
                            <span>${parseInt(item.harga_menu * item.kuantitas).toLocaleString('id-ID')}</span>
                        </div>
                    `).join('')}
                </div>
                <hr class="my-4 border-gray-300 border-dashed">
                <div>
                    <div class="flex justify-between font-bold text-md mt-2">
                        <span>TOTAL</span>
                        <span>Rp. ${parseInt(details.total_bayar).toLocaleString('id-ID')}</span>
                    </div>
                </div>
                <hr class="my-4 border-gray-300 border-dashed">
                <div class="text-center text-sm">
                    <p>Terima kasih atas kunjungan Anda!</p>
                </div>
            `;
            receiptModal.classList.remove('hidden');
        }

        const closeModal = () => {
            receiptModal.classList.add('hidden');
            // PERBAIKAN: Reload halaman setelah menutup struk untuk memperbarui riwayat
            location.reload();
        };
        closeModalButton.addEventListener('click', closeModal);
        receiptModal.addEventListener('click', (e) => { if (e.target === receiptModal) closeModal(); });
        printButton.addEventListener('click', () => window.print());
    </script>
</body>
</html>
