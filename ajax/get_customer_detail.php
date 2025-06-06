<?php
// ========================================
// AJAX: LẤY CHI TIẾT KHÁCH HÀNG
// ajax/get_customer_detail.php
// ========================================

header('Content-Type: application/json');
require_once '../config/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'ID không hợp lệ']);
    exit;
}

$customerId = (int)$_GET['id'];

try {
    // Lấy thông tin khách hàng
    $stmt = $pdo->prepare("
        SELECT *, 
               TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) as age,
               CASE 
                   WHEN DATEDIFF(CURDATE(), last_order_date) > 90 THEN 'Lâu không mua'
                   WHEN DATEDIFF(CURDATE(), last_order_date) > 30 THEN 'Ít hoạt động'
                   ELSE 'Hoạt động'
               END as activity_status
        FROM customers 
        WHERE id = ?
    ");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();
    
    if (!$customer) {
        echo json_encode(['error' => 'Không tìm thấy khách hàng']);
        exit;
    }
    
    // Lấy lịch sử mua hàng (5 đơn gần nhất)
    $ordersStmt = $pdo->prepare("
        SELECT s.*, 
               DATE_FORMAT(s.sale_date, '%d/%m/%Y %H:%i') as formatted_date,
               (SELECT COUNT(*) FROM sale_details sd WHERE sd.sale_id = s.id) as item_count
        FROM sales s 
        WHERE s.customer_id = ? 
        ORDER BY s.sale_date DESC 
        LIMIT 5
    ");
    $ordersStmt->execute([$customerId]);
    $orders = $ordersStmt->fetchAll();
    
    // Thống kê khách hàng
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(total_amount) as total_spent,
            AVG(total_amount) as avg_order_value,
            MAX(sale_date) as last_order_date,
            MIN(sale_date) as first_order_date
        FROM sales 
        WHERE customer_id = ?
    ");
    $statsStmt->execute([$customerId]);
    $stats = $statsStmt->fetch();
    
    // Sản phẩm mua nhiều nhất
    $topProductsStmt = $pdo->prepare("
        SELECT p.name, 
               SUM(sd.quantity) as total_quantity,
               SUM(sd.quantity * sd.unit_price) as total_value
        FROM sale_details sd
        JOIN sales s ON sd.sale_id = s.id
        JOIN products p ON sd.product_id = p.id
        WHERE s.customer_id = ?
        GROUP BY p.id, p.name
        ORDER BY total_quantity DESC
        LIMIT 3
    ");
    $topProductsStmt->execute([$customerId]);
    $topProducts = $topProductsStmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'customer' => $customer,
        'orders' => $orders,
        'stats' => $stats,
        'topProducts' => $topProducts
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Lỗi database: ' . $e->getMessage()]);
}
?>
