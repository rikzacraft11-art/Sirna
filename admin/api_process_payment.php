<?php
// admin/api_process_payment.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'kasir') {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit();
}

require_once '../core/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$id_pesanan = $input['id_pesanan'] ?? null;
$total_bayar = $input['total_bayar'] ?? null;
$metode_bayar = $input['metode_bayar'] ?? null;
$id_pegawai = $_SESSION['user_id']; // ID Kasir

if (!$id_pesanan || !$total_bayar || !$metode_bayar) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Masukkan data ke tabel transaksi
    $sql_transaksi = "INSERT INTO transaksi (id_pesanan, id_pegawai, total_bayar, metode_bayar) VALUES (?, ?, ?, ?)";
    $stmt_transaksi = $pdo->prepare($sql_transaksi);
    $stmt_transaksi->execute([$id_pesanan, $id_pegawai, $total_bayar, $metode_bayar]);
    $id_transaksi = $pdo->lastInsertId();

    // 2. Update status pesanan menjadi 'paid'
    $sql_pesanan = "UPDATE pesanan SET status = 'paid' WHERE id_pesanan = ?";
    $stmt_pesanan = $pdo->prepare($sql_pesanan);
    $stmt_pesanan->execute([$id_pesanan]);
    
    // Ambil detail lengkap untuk struk
    $sql_details = "
        SELECT t.id_transaksi, t.tgl_transaksi, t.total_bayar, p.nomor_meja, pg.nama_pegawai as nama_kasir,
               m.nama_menu, dp.kuantitas, m.harga_menu
        FROM transaksi t
        JOIN pesanan p ON t.id_pesanan = p.id_pesanan
        JOIN pegawai pg ON t.id_pegawai = pg.id_pegawai
        JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
        JOIN menu m ON dp.kode_menu = m.kode_menu
        WHERE t.id_transaksi = ?
    ";
    $stmt_details = $pdo->prepare($sql_details);
    $stmt_details->execute([$id_transaksi]);
    $details_raw = $stmt_details->fetchAll(PDO::FETCH_ASSOC);

    $transaction_details = [
        'id_transaksi' => $details_raw[0]['id_transaksi'],
        'tgl_transaksi' => $details_raw[0]['tgl_transaksi'],
        'total_bayar' => $details_raw[0]['total_bayar'],
        'nomor_meja' => $details_raw[0]['nomor_meja'],
        'nama_kasir' => $details_raw[0]['nama_kasir'],
        'items' => []
    ];
    foreach($details_raw as $item) {
        $transaction_details['items'][] = [
            'nama_menu' => $item['nama_menu'],
            'kuantitas' => $item['kuantitas'],
            'harga_menu' => $item['harga_menu']
        ];
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Pembayaran berhasil.', 'transaction_details' => $transaction_details]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
