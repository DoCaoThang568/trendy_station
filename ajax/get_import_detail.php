<?php
require_once '../config/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID không hợp lệ']);
    exit;
}

$import_id = (int)$_GET['id'];

try {
    // Get import info
    $stmt = $pdo->prepare("
        SELECT i.*, s.name as supplier_name, s.phone as supplier_phone,
               s.address as supplier_address
        FROM imports i
        LEFT JOIN suppliers s ON i.supplier_id = s.id
        WHERE i.id = ?
    ");
    $stmt->execute([$import_id]);
    $import = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$import) {
        http_response_code(404);
        echo json_encode(['error' => 'Không tìm thấy phiếu nhập']);
        exit;
    }
    
    // Get import details
    $stmt = $pdo->prepare("
        SELECT id.*, p.name as product_name, p.product_code as product_code
        FROM import_details id
        JOIN products p ON id.product_id = p.id
        WHERE id.import_id = ?
        ORDER BY p.name
    ");
    $stmt->execute([$import_id]);
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format data
    $import['created_at_formatted'] = date('d/m/Y H:i', strtotime($import['created_at']));
    $import['total_amount_formatted'] = number_format($import['total_amount'], 0, ',', '.');
    
    foreach ($details as &$detail) {
        $unit_cost_val = isset($detail['unit_cost']) ? (float)$detail['unit_cost'] : 0;
        $quantity_val = isset($detail['quantity']) ? (int)$detail['quantity'] : 0;
        $total_cost_val = isset($detail['total_cost']) ? (float)$detail['total_cost'] : ($unit_cost_val * $quantity_val);

        $detail['unit_price_formatted'] = number_format($unit_cost_val, 0, ',', '.');
        $detail['total_price_formatted'] = number_format($total_cost_val, 0, ',', '.');
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'import' => $import,
        'details' => $details
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Lỗi server: ' . $e->getMessage()]);
}
?>
