<?php
// admin/api_get_kasir_data.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'kasir') {
    echo json_encode(['error' => 'Akses ditolak']);
    exit();
}

require_once '../core/database.php';

try {
    // Mengambil pesanan yang sudah selesai dimasak (completed)
    $sql = "
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
    $stmt = $pdo->query($sql);
    $completed_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mengambil detail item untuk setiap pesanan
    if (!empty($completed_orders)) {
        $order_ids = array_column($completed_orders, 'id_pesanan');
        $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
        
        $sql_items = "
            SELECT dp.id_pesanan, m.nama_menu, m.harga_menu, dp.kuantitas, dp.catatan
            FROM detail_pesanan dp
            JOIN menu m ON dp.kode_menu = m.kode_menu
            WHERE dp.id_pesanan IN ($placeholders)
        ";
        $stmt_items = $pdo->prepare($sql_items);
        $stmt_items->execute($order_ids);
        $items_raw = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

        // Mengelompokkan item ke pesanan masing-masing
        $items_by_order = [];
        foreach ($items_raw as $item) {
            $items_by_order[$item['id_pesanan']][] = $item;
        }

        foreach ($completed_orders as &$order) {
            $order['items'] = $items_by_order[$order['id_pesanan']] ?? [];
        }
    }

    echo json_encode(['completed_orders' => $completed_orders]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
