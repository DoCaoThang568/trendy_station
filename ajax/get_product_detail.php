<?php
/**
 * AJAX - Get Product Details
 */

// Prevent direct access
if (!isset($_POST['action']) || $_POST['action'] !== 'get_product_details') {
    http_response_code(404);
    exit('Access denied');
}

// Start output buffering to catch any unwanted output
ob_start();

// Suppress error display for clean JSON output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set JSON header immediately
header('Content-Type: application/json; charset=utf-8');

try {
    // Include database connection
    require_once '../config/database.php';
    
    $id = $_POST['id'] ?? 0;
    
    if (!$id || !is_numeric($id)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'ID sản phẩm không hợp lệ']);
        exit;
    }
      // Get product details with category name
    $product = fetchOne("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = ?
    ", [(int)$id]);
    
    if ($product) {
        // Clear any unwanted output
        ob_clean();
        echo json_encode(['success' => true, 'data' => $product]);
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
    }
    
} catch (Exception $e) {
    // Log error for debugging
    error_log("Error in get_product_detail.php: " . $e->getMessage());
    
    // Clear any unwanted output
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ khi lấy thông tin sản phẩm']);
}

// End output buffering
ob_end_flush();
?>
