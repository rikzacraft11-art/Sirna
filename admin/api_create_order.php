<?php
// admin/api_create_order.php (Versi Debug)

session_start();
header('Content-Type: application/json');

// ======================= BAGIAN DEBUGGING =======================
// Cek 1: Apakah sesi ada sama sekali?
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak: Sesi tidak ditemukan. Silakan coba login ulang.']);
    exit();
}

// Cek 2: Apakah peran (role) yang tersimpan di sesi adalah 'pelayan'?
if ($_SESSION['role'] !== 'pelayan') {
    // Memberi tahu peran apa yang terdeteksi, untuk membantu debugging
    $detected_role = $_SESSION['role'] ?? 'tidak ada';
    echo json_encode(['success' => false, 'message' => 'Akses ditolak: Anda tidak memiliki hak akses sebagai Pelayan. Peran terdeteksi: ' . $detected_role]);
    exit();
}
// ================================================================

require_once '../core/database.php';

$input = json_decode(file_get_contents('php://input'), true);

$nomor_meja = $input['nomor_meja'] ?? null;
$items = $input['items'] ?? [];
$id_pegawai = $_SESSION['user_id'];

if (!$nomor_meja || empty($items)) {
    echo json_encode(['success' => false, 'message' => 'Meja dan item pesanan tidak boleh kosong.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Buat pesanan baru
    $sql_order = "INSERT INTO pesanan (id_pegawai, nomor_meja, status) VALUES (?, ?, 'pending')";
    $stmt_order = $pdo->prepare($sql_order);
    $stmt_order->execute([$id_pegawai, $nomor_meja]);
    $id_pesanan = $pdo->lastInsertId();

    // 2. Masukkan item-item ke detail_pesanan
    $sql_items = "INSERT INTO detail_pesanan (id_pesanan, kode_menu, kuantitas, catatan) VALUES (?, ?, ?, ?)";
    $stmt_items = $pdo->prepare($sql_items);

    foreach ($items as $item) {
        $stmt_items->execute([
            $id_pesanan,
            $item['kode_menu'],
            $item['kuantitas'],
            $item['catatan']
        ]);
    }

    // 3. Update status meja menjadi 'occupied'
    $sql_table = "UPDATE `meja` SET status = 'occupied' WHERE nomor_meja = ?";
    $stmt_table = $pdo->prepare($sql_table);
    $stmt_table->execute([$nomor_meja]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Pesanan berhasil dibuat.']);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
