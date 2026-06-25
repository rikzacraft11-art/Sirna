<?php
// admin/api_get_orders.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'koki') {
    echo json_encode([]); // Kembalikan array kosong jika tidak berhak
    exit();
}

require_once '../core/database.php';

try {
    $sql = "
        SELECT 
            p.id_pesanan, p.tgl_pesanan, p.nomor_meja,
            m.nama_menu,
            dp.kuantitas, dp.catatan
        FROM pesanan p
        JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
        JOIN menu m ON dp.kode_menu = m.kode_menu
        WHERE p.status = 'pending'
        ORDER BY p.tgl_pesanan ASC
    ";
    $stmt = $pdo->query($sql);
    $raw_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $orders = [];
    foreach ($raw_items as $item) {
        $orders[$item['id_pesanan']]['details']['tgl_pesanan'] = $item['tgl_pesanan'];
        $orders[$item['id_pesanan']]['details']['nomor_meja'] = $item['nomor_meja'];
        $orders[$item['id_pesanan']]['items'][] = $item;
    }

    echo json_encode($orders);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
