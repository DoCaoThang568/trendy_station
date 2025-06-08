<?php
require_once '../config/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID không hợp lệ']);
    exit;
}

$sale_id = (int)$_GET['id'];

try {
    // Get sale info
    $stmt = $pdo->prepare("
        SELECT s.*, c.name as customer_name, c.phone as customer_phone,
               c.email as customer_email, c.address as customer_address
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
        WHERE s.id = ?
    ");
    $stmt->execute([$sale_id]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sale) {
        http_response_code(404);
        echo json_encode(['error' => 'Không tìm thấy hóa đơn']);
        exit;
    }
    
    // Get sale details
    $stmt = $pdo->prepare("
        SELECT sd.*, p.name as product_name, p.product_code
        FROM sale_details sd
        JOIN products p ON sd.product_id = p.id
        WHERE sd.sale_id = ?
        ORDER BY p.name
    ");
    $stmt->execute([$sale_id]);
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format data
    $sale['created_at_formatted'] = date('d/m/Y H:i', strtotime($sale['created_at']));
    $sale['total_amount_formatted'] = number_format($sale['total_amount'] ?? 0, 0, ',', '.');
    $sale['final_amount_formatted'] = number_format($sale['final_amount'] ?? 0, 0, ',', '.');
    $sale['discount_amount_formatted'] = number_format(($sale['discount_amount'] ?? 0), 0, ',', '.');
    
    foreach ($details as &$detail) {
        $unit_price_val = isset($detail['unit_price']) ? (float)$detail['unit_price'] : 0;
        $quantity_val = isset($detail['quantity']) ? (int)$detail['quantity'] : 0;
        $total_price_val = isset($detail['total_price']) ? (float)$detail['total_price'] : ($unit_price_val * $quantity_val);

        $detail['unit_price_formatted'] = number_format($unit_price_val, 0, ',', '.');
        $detail['total_price_formatted'] = number_format($total_price_val, 0, ',', '.');
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'sale' => $sale,
        'details' => $details
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Lỗi server: ' . $e->getMessage()]);
}
?>
