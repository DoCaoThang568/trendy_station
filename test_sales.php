<?php
require_once 'config/database.php';
try {
    // Test get sale info
    $stmt = $pdo->prepare('SELECT * FROM sales LIMIT 1');
    $stmt->execute();
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($sale) {
        echo 'Tìm thấy hóa đơn: ' . $sale['sale_code'] . ' (ID: ' . $sale['id'] . ')' . PHP_EOL;
        
        // Test get sale details
        $stmt = $pdo->prepare('SELECT sd.*, p.name as product_name, p.product_code FROM sale_details sd JOIN products p ON sd.product_id = p.id WHERE sd.sale_id = ?');
        $stmt->execute([$sale['id']]);
        $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo 'Chi tiết hóa đơn: ' . count($details) . ' sản phẩm' . PHP_EOL;
        if (!empty($details)) {
            foreach ($details as $detail) {
                echo "- " . $detail['product_name'] . " (Code: " . $detail['product_code'] . "), SL: " . $detail['quantity'] . ", Giá: " . $detail['unit_price'] . PHP_EOL;
            }
        }
    } else {
        echo 'Không tìm thấy hóa đơn nào' . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Lỗi: ' . $e->getMessage() . PHP_EOL;
}
?>
