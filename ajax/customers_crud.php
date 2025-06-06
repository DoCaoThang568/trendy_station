<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Chỉ chấp nhận POST request']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create':
            createCustomer();
            break;
        case 'update':
            updateCustomer();
            break;
        case 'delete':
            deleteCustomer();
            break;
        default:
            throw new Exception('Action không hợp lệ');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function createCustomer() {
    global $pdo;
    
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    // Validation
    if (empty($name)) {
        throw new Exception('Tên khách hàng không được để trống');
    }
    
    if (empty($phone)) {
        throw new Exception('Số điện thoại không được để trống');
    }
    
    // Validate phone format (10-11 digits)
    if (!preg_match('/^[0-9]{10,11}$/', $phone)) {
        throw new Exception('Số điện thoại không hợp lệ (10-11 chữ số)');
    }
    
    // Validate email if provided
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email không hợp lệ');
    }
    
    // Check if phone already exists
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE phone = ?");
    $stmt->execute([$phone]);
    if ($stmt->fetch()) {
        throw new Exception('Số điện thoại đã tồn tại');
    }
    
    // Check if email already exists (if provided)
    if (!empty($email)) {
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception('Email đã tồn tại');
        }
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO customers (name, phone, email, address, notes) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([$name, $phone, $email, $address, $notes]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Thêm khách hàng thành công',
        'customer_id' => $pdo->lastInsertId()
    ]);
}

function updateCustomer() {
    global $pdo;
    
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    if ($id <= 0) {
        throw new Exception('ID khách hàng không hợp lệ');
    }
    
    // Validation
    if (empty($name)) {
        throw new Exception('Tên khách hàng không được để trống');
    }
    
    if (empty($phone)) {
        throw new Exception('Số điện thoại không được để trống');
    }
    
    if (!preg_match('/^[0-9]{10,11}$/', $phone)) {
        throw new Exception('Số điện thoại không hợp lệ (10-11 chữ số)');
    }
    
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email không hợp lệ');
    }
    
    // Check if customer exists
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        throw new Exception('Khách hàng không tồn tại');
    }
    
    // Check if phone already exists for other customers
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE phone = ? AND id != ?");
    $stmt->execute([$phone, $id]);
    if ($stmt->fetch()) {
        throw new Exception('Số điện thoại đã được sử dụng bởi khách hàng khác');
    }
    
    // Check if email already exists for other customers
    if (!empty($email)) {
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) {
            throw new Exception('Email đã được sử dụng bởi khách hàng khác');
        }
    }
    
    $stmt = $pdo->prepare("
        UPDATE customers 
        SET name = ?, phone = ?, email = ?, address = ?, notes = ?, updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([$name, $phone, $email, $address, $notes, $id]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Cập nhật khách hàng thành công'
    ]);
}

function deleteCustomer() {
    global $pdo;
    
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        throw new Exception('ID khách hàng không hợp lệ');
    }
    
    // Check if customer exists
    $stmt = $pdo->prepare("SELECT name FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    $customer = $stmt->fetch();
    
    if (!$customer) {
        throw new Exception('Khách hàng không tồn tại');
    }
    
    // Check if customer has any sales
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM sales WHERE customer_id = ?");
    $stmt->execute([$id]);
    $salesCount = $stmt->fetch()['count'];
    
    if ($salesCount > 0) {
        throw new Exception('Không thể xóa khách hàng đã có giao dịch mua hàng');
    }
    
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode([
        'success' => true, 
        'message' => "Đã xóa khách hàng '{$customer['name']}'"
    ]);
}
?>
