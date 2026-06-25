<?php
// proses_reservasi.php
header('Content-Type: application/json');
require_once 'core/database.php';

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['name']) || empty($input['party_size']) || empty($input['phone']) || empty($input['date']) || empty($input['time']) || empty($input['tableId'])) {
    echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi.']);
    exit;
}

$nama_pelanggan = filter_var($input['name'], FILTER_SANITIZE_STRING);
$kontak_pelanggan = filter_var($input['phone'], FILTER_SANITIZE_STRING);
$jumlah_orang = filter_var($input['party_size'], FILTER_SANITIZE_NUMBER_INT);
$tgl_reservasi = $input['date'];
$waktu_reservasi = $input['time'];
$nomor_meja = filter_var($input['tableId'], FILTER_SANITIZE_NUMBER_INT);

try {
    // PERBAIKAN: Menggunakan nama tabel 'reservasi' dan kolom yang sesuai
    $sql = "INSERT INTO reservasi (nama_pelanggan, kontak_pelanggan, jumlah_orang, tgl_reservasi, waktu_reservasi, nomor_meja, status) VALUES (?, ?, ?, ?, ?, ?, 'confirmed')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nama_pelanggan, $kontak_pelanggan, $jumlah_orang, $tgl_reservasi, $waktu_reservasi, $nomor_meja]);

    // PERBAIKAN: Update status meja di tabel 'meja' menjadi 'reserved'
    $sql_update_meja = "UPDATE meja SET status = 'reserved' WHERE nomor_meja = ?";
    $stmt_update_meja = $pdo->prepare($sql_update_meja);
    $stmt_update_meja->execute([$nomor_meja]);

    echo json_encode(['success' => true, 'message' => 'Reservasi berhasil disimpan.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan pada server: ' . $e->getMessage()]);
}
?>
