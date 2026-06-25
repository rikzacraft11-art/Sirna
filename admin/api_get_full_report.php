<?php
// admin/api_get_full_report.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['owner', 'admin'])) {
    echo json_encode(['error' => 'Akses ditolak']);
    exit();
}

require_once '../core/database.php';

// Ambil parameter periode dari URL
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

try {
    // Query untuk mengambil semua detail transaksi yang sudah dibayar pada periode yang dipilih
    $sql = "
        SELECT 
            t.id_transaksi,
            p.id_pesanan,
            p.nomor_meja,
            pg_kasir.nama_pegawai AS kasir,
            pg_pelayan.nama_pegawai AS pelayan,
            m.nama_menu,
            dp.kuantitas,
            m.harga_menu,
            (dp.kuantitas * m.harga_menu) AS subtotal,
            t.total_bayar,
            t.metode_bayar,
            t.tgl_transaksi
        FROM transaksi t
        JOIN pesanan p ON t.id_pesanan = p.id_pesanan
        JOIN pegawai pg_kasir ON t.id_pegawai = pg_kasir.id_pegawai
        JOIN pegawai pg_pelayan ON p.id_pegawai = pg_pelayan.id_pegawai
        JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
        JOIN menu m ON dp.kode_menu = m.kode_menu
        WHERE MONTH(t.tgl_transaksi) = ? AND YEAR(t.tgl_transaksi) = ?
        ORDER BY t.tgl_transaksi DESC, t.id_transaksi DESC;
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$month, $year]);
    $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($report_data);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
