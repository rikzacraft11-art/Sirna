<?php
// admin/api_update_order_status.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'koki') {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit();
}

require_once '../core/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$order_id = $input['order_id'] ?? null;
$status = $input['status'] ?? null;

if (!$order_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Input tidak valid.']);
    exit();
}

try {
    $pdo->beginTransaction();

    // Update status pesanan
    $sql_order = "UPDATE pesanan SET status = ? WHERE id_pesanan = ?";
    $stmt_order = $pdo->prepare($sql_order);
    $stmt_order->execute([$status, $order_id]);

    // Jika pesanan selesai, ubah status meja kembali menjadi 'available'
    if ($status === 'completed') {
        $stmt_get_table = $pdo->prepare("SELECT nomor_meja FROM pesanan WHERE id_pesanan = ?");
        $stmt_get_table->execute([$order_id]);
        $table = $stmt_get_table->fetch();
        if ($table) {
            $sql_table = "UPDATE meja SET status = 'available' WHERE nomor_meja = ?";
            $stmt_table = $pdo->prepare($sql_table);
            $stmt_table->execute([$table['nomor_meja']]);
        }
    }
    
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Status berhasil diperbarui.']);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
