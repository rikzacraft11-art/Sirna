<?php
// admin/api_update_order_item.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'koki') {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit();
}

require_once '../core/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$itemId = $input['item_id'] ?? null;
$status = $input['status'] ?? null;

if (!$itemId || !in_array($status, ['completed', 'rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Input tidak valid.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Update status item di tabel detail_pesanan
    $sql_update = "UPDATE detail_pesanan SET status = ? WHERE id_detail = ?";
    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->execute([$status, $itemId]);

    // 2. Dapatkan id_pesanan dari item yang diupdate
    $stmt_get_order = $pdo->prepare("SELECT id_pesanan FROM detail_pesanan WHERE id_detail = ?");
    $stmt_get_order->execute([$itemId]);
    $order = $stmt_get_order->fetch();
    
    if (!$order) {
        throw new Exception("Item pesanan tidak ditemukan.");
    }
    $order_id = $order['id_pesanan'];

    // 3. Cek apakah semua item dalam pesanan ini sudah selesai (completed/rejected)
    $stmt_check_all = $pdo->prepare("SELECT COUNT(*) FROM detail_pesanan WHERE id_pesanan = ? AND status = 'pending'");
    $stmt_check_all->execute([$order_id]);
    $pending_items_count = $stmt_check_all->fetchColumn();

    if ($pending_items_count == 0) {
        // Jika tidak ada lagi item yang pending, update status pesanan utama
        $sql_update_order = "UPDATE pesanan SET status = 'completed' WHERE id_pesanan = ?";
        $stmt_update_order = $pdo->prepare($sql_update_order);
        $stmt_update_order->execute([$order_id]);

        // Status meja akan diubah menjadi 'available' oleh kasir setelah pembayaran
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Status berhasil diperbarui.', 'order_id' => $order_id]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
