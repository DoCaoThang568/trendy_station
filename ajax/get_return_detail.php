<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID phiếu trả hàng không được cung cấp.']);
    exit;
}

$return_id = (int)$_GET['id'];

try {
    // Fetch main return information
    $stmt_return = $pdo->prepare("
        SELECT r.id as return_id, r.return_code, r.reason, r.total_refund, r.created_at as return_date,
               s.sale_code,
               c.name as customer_name, c.phone as customer_phone
        FROM returns r
        JOIN sales s ON r.sale_id = s.id
        LEFT JOIN customers c ON s.customer_id = c.id
        WHERE r.id = ?
    ");
    $stmt_return->execute([$return_id]);
    $return_info = $stmt_return->fetch(PDO::FETCH_ASSOC);

    if (!$return_info) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy phiếu trả hàng.']);
        exit;
    }

    // Fetch return items
    $stmt_items = $pdo->prepare("
        SELECT rd.quantity, rd.unit_price,
               p.name as product_name, p.product_code
        FROM return_details rd
        JOIN products p ON rd.product_id = p.id
        WHERE rd.return_id = ?
    ");
    $stmt_items->execute([$return_id]);
    $return_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'return_info' => $return_info,
        'return_items' => $return_items
    ]);

} catch (Exception $e) {
    error_log("Error in get_return_detail.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
exit;
?>
