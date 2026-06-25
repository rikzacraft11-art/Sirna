<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
    header('Location: index.php?error=unauthorized');
    exit();
}

require_once '../core/database.php';
$username = $_SESSION['username'];
$user_initial = strtoupper(substr($username, 0, 1));

// Tentukan periode laporan (bulan dan tahun)
$selected_month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

try {
    // 1. Ambil Total Pendapatan untuk periode yang dipilih
    $stmt_revenue = $pdo->prepare("SELECT SUM(total_bayar) as total FROM transaksi WHERE MONTH(tgl_transaksi) = ? AND YEAR(tgl_transaksi) = ?");
    $stmt_revenue->execute([$selected_month, $selected_year]);
    $total_revenue = $stmt_revenue->fetchColumn();

    // 2. Ambil Menu Terlaris untuk periode yang dipilih
    $top_selling_query = "
        SELECT m.nama_menu, SUM(dp.kuantitas) as total_terjual
        FROM detail_pesanan dp
        JOIN menu m ON dp.kode_menu = m.kode_menu
        JOIN pesanan p ON dp.id_pesanan = p.id_pesanan
        JOIN transaksi t ON p.id_pesanan = t.id_pesanan
        WHERE MONTH(t.tgl_transaksi) = ? AND YEAR(t.tgl_transaksi) = ?
        GROUP BY m.nama_menu
        ORDER BY total_terjual DESC
        LIMIT 8;
    ";
    $stmt_top_selling = $pdo->prepare($top_selling_query);
    $stmt_top_selling->execute([$selected_month, $selected_year]);
    $top_selling_items = $stmt_top_selling->fetchAll(PDO::FETCH_ASSOC);

    // 3. Ambil Data Pendapatan Harian untuk Grafik
    $revenue_chart_query = "
        SELECT 
            DAY(tgl_transaksi) as tanggal, 
            SUM(total_bayar) as pendapatan_harian
        FROM transaksi
        WHERE MONTH(tgl_transaksi) = ? AND YEAR(tgl_transaksi) = ?
        GROUP BY DATE(tgl_transaksi)
        ORDER BY tanggal ASC;
    ";
    $stmt_chart = $pdo->prepare($revenue_chart_query);
    $stmt_chart->execute([$selected_month, $selected_year]);
    $daily_revenues = $stmt_chart->fetchAll(PDO::FETCH_ASSOC);

    // Proses data untuk Chart.js
    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $selected_month, $selected_year);
    $chart_labels = range(1, $days_in_month);
    $chart_data = array_fill(1, $days_in_month, 0);
    foreach ($daily_revenues as $row) {
        $chart_data[(int)$row['tanggal']] = $row['pendapatan_harian'];
    }
    $chart_data = array_values($chart_data);

} catch (PDOException $e) {
    die("Error fetching report data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dasbor Laporan Owner - SIRNA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Raleway:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        body { font-family: 'Raleway', sans-serif; background-color: #111111; }
        .font-playfair { font-family: 'Playfair Display', serif; }
        .btn-export { background-color: #2a2a2a; border: 1px solid #4a4a4a; transition: all 0.2s ease-in-out; }
        .btn-export:hover { background-color: #3a3a3a; border-color: #FFA114; }
    </style>
</head>
<body class="bg-[#111111] text-gray-200">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 flex-shrink-0 bg-[#1C1C1C] p-6 flex-col justify-between hidden md:flex">
            <div>
                <div class="mb-12">
                     <img src="../assets/images/sirna.logo.png" alt="Logo SIRNA" class="w-full h-12 object-contain" onerror="this.onerror=null;this.src='https://placehold.co/320x80/1C1C1C/ffffff?text=SIRNA&font=playfairdisplay';">
                </div>
                <nav>
                    <ul>
                        <li class="mb-4">
                            <a href="#" class="flex items-center p-3 rounded-lg bg-gradient-to-r from-orange-500 to-red-500 text-white font-bold shadow-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.21 15.89A10 10 0 1 1 8.11 3.99"></path><path d="M22 12A10 10 0 0 0 12 2v10z"></path></svg>
                                LAPORAN
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
        <main class="flex-1 p-4 md:p-8 flex flex-col overflow-hidden">
            <header class="flex-shrink-0 flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
                <h1 class="font-playfair text-3xl font-bold text-white">Laporan Pendapatan</h1>
                <div class="flex items-center space-x-2 md:space-x-4">
                    <!-- Form Filter Periode -->
                    <form id="filter-form" method="GET" class="flex items-center gap-2">
                        <select name="month" class="bg-[#1C1C1C] border border-gray-700 rounded-lg py-2 px-3 focus:outline-none focus:ring-1 focus:ring-[#FFA114]">
                            <?php for ($m=1; $m<=12; $m++): ?>
                                <option value="<?= $m ?>" <?= $m == $selected_month ? 'selected' : '' ?>><?= date('F', mktime(0,0,0,$m, 1, date('Y'))) ?></option>
                            <?php endfor; ?>
                        </select>
                        <select name="year" class="bg-[#1C1C1C] border border-gray-700 rounded-lg py-2 px-3 focus:outline-none focus:ring-1 focus:ring-[#FFA114]">
                            <?php for ($y=date('Y'); $y>=date('Y')-5; $y--): ?>
                                <option value="<?= $y ?>" <?= $y == $selected_year ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                        <button type="submit" class="bg-gradient-to-r from-orange-500 to-red-500 text-white font-bold py-2 px-4 rounded-lg">Filter</button>
                    </form>
                    <button id="export-excel-btn" class="btn-export flex items-center justify-center gap-2 py-2 px-4 rounded-lg">Excel</button>
                    <div class="hidden md:flex items-center">
                        <div class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center font-bold text-white"><?= $user_initial ?></div>
                        <div class="ml-3"><span class="font-semibold"><?= htmlspecialchars($username) ?></span></div>
                    </div>
                </div>
            </header>
            
            <div id="report-section" class="flex-grow flex flex-col min-h-0 overflow-y-auto pr-2">
                <div class="flex-shrink-0">
                    <p class="text-gray-400 mb-8">Menampilkan laporan untuk: <span class="font-bold text-white"><?= date('F Y', mktime(0,0,0,$selected_month, 1, $selected_year)) ?></span></p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="bg-[#1C1C1C] p-6 rounded-lg border border-gray-800">
                            <p class="text-gray-400 text-sm mb-2">Total Pendapatan (Periode Dipilih)</p>
                            <p id="total-revenue" class="text-3xl font-bold text-white">Rp. <?= number_format($total_revenue, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-[#1C1C1C] p-6 rounded-lg border border-gray-800">
                            <p class="text-gray-400 text-sm mb-2">Menu Terlaris (Periode Dipilih)</p>
                            <p id="top-menu" class="text-3xl font-bold text-white"><?= !empty($top_selling_items) ? htmlspecialchars($top_selling_items[0]['nama_menu']) : 'N/A' ?></p>
                        </div>
                    </div>
                </div>

                <div class="flex-grow grid grid-cols-1 lg:grid-cols-3 gap-6 min-h-0">
                    <div class="col-span-1 lg:col-span-2 bg-[#1C1C1C] p-6 rounded-lg border border-gray-800 flex flex-col">
                        <h3 class="flex-shrink-0 font-playfair text-xl font-bold text-white mb-4">Grafik Pendapatan Harian</h3>
                        <div class="flex-grow relative">
                           <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                    <div class="col-span-1 bg-[#1C1C1C] p-6 rounded-lg border border-gray-800 flex flex-col">
                        <h3 class="flex-shrink-0 font-playfair text-xl font-bold text-white mb-4">Peringkat Menu Terlaris</h3>
                        <ol id="top-selling-list" class="flex-1 space-y-3 overflow-y-auto pr-2">
                            <?php if (empty($top_selling_items)): ?>
                                <p class="text-gray-500">Belum ada data penjualan pada periode ini.</p>
                            <?php else: ?>
                                <?php foreach ($top_selling_items as $index => $item): ?>
                                <li class="flex items-center justify-between text-sm">
                                    <div class="flex items-center">
                                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-gray-700 text-xs mr-3"><?= $index + 1 ?></span>
                                        <span><?= htmlspecialchars($item['nama_menu']) ?></span>
                                    </div>
                                    <span class="font-semibold"><?= $item['total_terjual'] ?> terjual</span>
                                </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ol>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // --- RENDER CHART ---
        const revenueData = {
            labels: <?= json_encode($chart_labels) ?>,
            revenues: <?= json_encode($chart_data) ?>
        };
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(ctx, {
            type: 'line', // Mengubah chart menjadi line
            data: {
                labels: revenueData.labels,
                datasets: [{
                    label: 'Pendapatan',
                    data: revenueData.revenues,
                    backgroundColor: 'rgba(246, 66, 26, 0.2)',
                    borderColor: '#FFA114',
                    borderWidth: 2,
                    pointBackgroundColor: '#FFA114',
                    tension: 0.4 // Membuat garis lebih melengkung
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#111',
                        callbacks: {
                            label: (context) => `Rp. ${context.parsed.y.toLocaleString('id-ID')}`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { 
                            color: '#9ca3af',
                            callback: (value) => {
                                if (value >= 1000000) return `Rp. ${value / 1000000} Jt`;
                                if (value >= 1000) return `Rp. ${value / 1000} Rb`;
                                return `Rp. ${value}`;
                            }
                        },
                        grid: { color: 'rgba(255, 255, 255, 0.1)' }
                    },
                    x: {
                        ticks: { color: '#9ca3af' },
                        grid: { display: false }
                    }
                }
            }
        });

        // --- EXPORT FUNCTIONS ---
        const exportExcelBtn = document.getElementById('export-excel-btn');
        
        function downloadExcel(html, filename) {
            const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
            const link = document.createElement("a");
            const url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        exportExcelBtn.addEventListener('click', async () => {
            exportExcelBtn.textContent = 'Memproses...';
            exportExcelBtn.disabled = true;
            
            // Ambil periode yang dipilih dari form
            const selectedMonth = document.querySelector('select[name="month"]').value;
            const selectedYear = document.querySelector('select[name="year"]').value;

            try {
                // Panggil API dengan parameter periode
                const response = await fetch(`api_get_full_report.php?month=${selectedMonth}&year=${selectedYear}`);
                const data = await response.json();

                if (data.error) throw new Error(data.error);
                
                let totalPendapatan = 0;
                let tableHTML = `
                    <html xmlns:x="urn:schemas-microsoft-com:office:excel">
                    <head><meta charset="UTF-8">
                    <style>
                        table, th, td { border: 1px solid black; border-collapse: collapse; padding: 5px; }
                        th { background-color: #FFA114; color: #ffffff; font-weight: bold; }
                        .total-row td { font-weight: bold; background-color: #f2f2f2; }
                    </style>
                    </head>
                    <body>
                        <h2>Laporan Penjualan SIRNA</h2>
                        <p>Periode: ${document.querySelector('select[name="month"] option:checked').text} ${selectedYear}</p>
                        <table>
                            <thead>
                                <tr><th>ID Transaksi</th><th>ID Pesanan</th><th>No Meja</th><th>Kasir</th><th>Pelayan</th><th>Nama Menu</th><th>Kuantitas</th><th>Harga Satuan</th><th>Subtotal</th><th>Total Bayar Pesanan</th><th>Metode Bayar</th><th>Tanggal Transaksi</th></tr>
                            </thead>
                            <tbody>`;
                
                data.forEach(row => {
                    totalPendapatan += parseFloat(row.subtotal);
                    tableHTML += `<tr><td>${row.id_transaksi}</td><td>${row.id_pesanan}</td><td>${row.nomor_meja}</td><td>${row.kasir}</td><td>${row.pelayan}</td><td>${row.nama_menu}</td><td>${row.kuantitas}</td><td>${row.harga_menu}</td><td>${row.subtotal}</td><td>${row.total_bayar}</td><td>${row.metode_bayar}</td><td>${row.tgl_transaksi}</td></tr>`;
                });

                tableHTML += `<tr class="total-row"><td colspan="8" style="text-align:right; font-weight:bold;">Total Pendapatan Periode Ini</td><td style="font-weight:bold;">${totalPendapatan.toFixed(2)}</td><td colspan="3"></td></tr></tbody></table></body></html>`;
                
                downloadExcel(tableHTML, `laporan_penjualan_sirna_${selectedYear}-${selectedMonth}.xls`);

            } catch (error) {
                alert('Gagal mengekspor data: ' + error.message);
            } finally {
                exportExcelBtn.textContent = 'Excel';
                exportExcelBtn.disabled = false;
            }
        });
    </script>
</body>
</html>
