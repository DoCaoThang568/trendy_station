<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to browser, might interfere with JSON
ini_set('log_errors', 1);     // Log errors to server's error log

// THIS BLOCK MUST BE AT THE VERY TOP OF THE FILE, BEFORE ANY HTML OUTPUT
require_once __DIR__ . '/../config/database.php'; // Use __DIR__ for robustness
require_once __DIR__ . '/../includes/functions.php'; // Use __DIR__ for robustness

// --- AJAX HANDLERS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Ensure no prior output has started that could interfere with JSON
    if (ob_get_level() > 0) {
        ob_end_clean(); // Clean any existing output buffers
    }
    // Start a new buffer just for this AJAX response
    ob_start();
    header('Content-Type: application/json');
    
    global $pdo; // Make $pdo available

    // If $pdo is not set, try to include database.php again.
    // This assumes database.php sets $pdo or provides connectDB()
    if (!$pdo) {
        require_once __DIR__ . '/../config/database.php'; 
        if (!$pdo && function_exists('connectDB')) { // If connectDB function exists
            $pdo = connectDB();
        }
    }

    // If $pdo is still not available after trying to load/connect, send error and exit.
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Lỗi CSDL: Không thể khởi tạo kết nối PDO cho AJAX.']);
        if(ob_get_length() > 0) { // Check if buffer has content
            ob_end_flush(); // Send buffer
        }
        exit;
    }

    $action = $_POST['action'];
    $response = ['success' => false, 'message' => 'Hành động không hợp lệ hoặc không được xử lý.'];

    try {
        switch ($action) {
            case 'add_customer':
            case 'update_customer':
                $id = $_POST['id'] ?? null;
                $name = trim($_POST['name'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $address = trim($_POST['address'] ?? '');
                $gender = $_POST['gender'] ?? 'Khác';
                $birth_date = !empty($_POST['birth_date']) ? $_POST['birth_date'] : null;
                $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : ($action === 'add_customer' ? 1 : null);
                
                if ($action === 'update_customer' && $is_active === null && isset($_POST['id'])) {
                    $current_customer_stmt = $pdo->prepare("SELECT is_active FROM customers WHERE id = ?");
                    $current_customer_stmt->execute([$id]);
                    $current_is_active = $current_customer_stmt->fetchColumn();
                    $is_active = $current_is_active !== false ? $current_is_active : 1;
                } elseif ($is_active === null) {
                    $is_active = 1; 
                }

                $notes = trim($_POST['notes'] ?? '');

                if (empty($name) || empty($phone)) {
                    throw new Exception('Tên và số điện thoại là bắt buộc.');
                }

                // Phone validation (check for duplicates)
                $check_phone_sql = "SELECT id FROM customers WHERE phone = ? AND id != ?";
                $check_phone_stmt = $pdo->prepare($check_phone_sql);
                $check_phone_stmt->execute([$phone, $id ?? 0]); // Use 0 if id is null for new customer
                if ($check_phone_stmt->fetch()) {
                    throw new Exception('Số điện thoại ' . htmlspecialchars($phone) . ' đã tồn tại.');
                }
                
                if ($action === 'add_customer') {
                    $code_sql = "SELECT MAX(CAST(SUBSTRING(customer_code, 3) AS UNSIGNED)) as max_num FROM customers WHERE customer_code LIKE 'KH%'";
                    $code_stmt = $pdo->query($code_sql);
                    $max_num_row = $code_stmt->fetch(PDO::FETCH_ASSOC);
                    $max_num = $max_num_row ? ($max_num_row['max_num'] ?? 0) : 0;
                    $customer_code = 'KH' . str_pad($max_num + 1, 3, '0', STR_PAD_LEFT);

                    $sql = "INSERT INTO customers (customer_code, name, phone, email, address, gender, birth_date, is_active, notes, created_at, updated_at, membership_level, total_spent, total_orders) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), 'Thông thường', 0, 0)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$customer_code, $name, $phone, $email, $address, $gender, $birth_date, $is_active, $notes]);
                    $response = ['success' => true, 'message' => 'Thêm khách hàng ' . htmlspecialchars($name) . ' thành công!'];
                } else { // update_customer
                    if (empty($id)) throw new Exception('ID khách hàng không hợp lệ.');
                    $sql = "UPDATE customers SET name=?, phone=?, email=?, address=?, gender=?, birth_date=?, is_active=?, notes=?, updated_at=NOW() WHERE id=?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$name, $phone, $email, $address, $gender, $birth_date, $is_active, $notes, $id]);
                    $response = ['success' => true, 'message' => 'Cập nhật khách hàng ' . htmlspecialchars($name) . ' thành công!'];
                }
                break;

            case 'get_customer': // For populating edit form
                $id = $_POST['id'] ?? 0;
                if (!$id) throw new Exception('ID không hợp lệ để lấy thông tin.');
                $sql = "SELECT *, DATE_FORMAT(birth_date, '%Y-%m-%d') as birth_date FROM customers WHERE id = ?"; // Format date
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $customer = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($customer) {
                    $response = ['success' => true, 'data' => $customer];
                } else {
                    throw new Exception('Không tìm thấy khách hàng với ID ' . htmlspecialchars($id) . '.');
                }
                break;

            case 'delete_customer':
                $id = $_POST['id'] ?? 0;
                if (!$id) throw new Exception('ID không hợp lệ để xóa.');
                
                // Check for related sales records
                $check_sales_sql = "SELECT COUNT(*) FROM sales WHERE customer_id = ?";
                $check_sales_stmt = $pdo->prepare($check_sales_sql);
                $check_sales_stmt->execute([$id]);
                $sales_count = $check_sales_stmt->fetchColumn();

                if ($sales_count > 0) {
                    throw new Exception('Không thể xóa khách hàng này vì đã có ' . $sales_count . ' hóa đơn liên quan. Vui lòng xóa các hóa đơn trước hoặc vô hiệu hóa khách hàng.');
                }

                $sql = "DELETE FROM customers WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                if ($stmt->rowCount() > 0) {
                    $response = ['success' => true, 'message' => 'Xóa khách hàng thành công!'];
                } else {
                    throw new Exception('Không thể xóa khách hàng hoặc khách hàng không tồn tại (ID: ' . htmlspecialchars($id) . ').');
                }
                break;
            
            case 'get_customer_details_for_modal': // For view detail modal
                $id = $_POST['id'] ?? 0;
                if (!$id) {
                    throw new Exception('ID khách hàng không hợp lệ để xem chi tiết.');
                }

                // Fetch customer basic details
                $sql_customer = "SELECT c.*, DATE_FORMAT(c.birth_date, '%d/%m/%Y') as formatted_birth_date, DATE_FORMAT(c.created_at, '%d/%m/%Y %H:%i') as formatted_created_at FROM customers c WHERE c.id = ?";
                $stmt_customer = $pdo->prepare($sql_customer);
                $stmt_customer->execute([$id]);
                $customer = $stmt_customer->fetch(PDO::FETCH_ASSOC);

                if (!$customer) {
                    throw new Exception('Không tìm thấy khách hàng với ID ' . htmlspecialchars($id) . '.');
                }

                // Fetch sales history for the customer
                $sql_sales = "SELECT s.id, s.sale_code, s.sale_date, s.total_amount, s.payment_status 
                              FROM sales s 
                              WHERE s.customer_id = ? 
                              ORDER BY s.sale_date DESC LIMIT 10";
                $stmt_sales = $pdo->prepare($sql_sales);
                $stmt_sales->execute([$id]);
                $sales_history = $stmt_sales->fetchAll(PDO::FETCH_ASSOC);
                // Add alias for consistency in JS if needed, or handle payment_status directly
                foreach ($sales_history as $key => $sale) {
                    $sales_history[$key]['status'] = $sale['payment_status'];
                }
                $customer['sales_history'] = $sales_history;
                
                // Fetch return history for the customer
                $sql_returns = "SELECT r.id, r.return_code, r.return_date, r.total_refund, s.sale_code as original_sale_code, r.status as return_status
                                FROM returns r
                                JOIN sales s ON r.sale_id = s.id
                                WHERE s.customer_id = ?
                                ORDER BY r.return_date DESC LIMIT 5";
                $stmt_returns = $pdo->prepare($sql_returns);
                $stmt_returns->execute([$id]);
                $return_history = $stmt_returns->fetchAll(PDO::FETCH_ASSOC);
                // Ensure 'status' key exists for consistency if JS expects it for returns too
                // Also, ensure the key for refund amount is consistent if JS expects a specific one, e.g., 'total_refund_amount'
                foreach ($return_history as $key => $return_item) {
                    $return_history[$key]['status'] = $return_item['return_status'];
                    // If JS expects 'total_refund_amount', create an alias:
                    // $return_history[$key]['total_refund_amount'] = $return_item['total_refund']; 
                }
                $customer['return_history'] = $return_history;

                $response = ['success' => true, 'data' => $customer];
                break;
            
            default:
                $response = ['success' => false, 'message' => 'Hành động AJAX không xác định: ' . htmlspecialchars($action)];
                break;
        }
    } catch (PDOException $e) {
        // Determine which statement object might exist to log its query string
        $problematic_stmt = null;
        if (isset($stmt_customer) && $stmt_customer instanceof PDOStatement) {
            $problematic_stmt = $stmt_customer;
        } elseif (isset($stmt_sales) && $stmt_sales instanceof PDOStatement) {
            $problematic_stmt = $stmt_sales;
        } elseif (isset($stmt_returns) && $stmt_returns instanceof PDOStatement) {
            $problematic_stmt = $stmt_returns;
        } elseif (isset($stmt_gc) && $stmt_gc instanceof PDOStatement) { // Assuming $stmt_gc is for get_customer
            $problematic_stmt = $stmt_gc;
        } elseif (isset($stmt_dc) && $stmt_dc instanceof PDOStatement) { // Assuming $stmt_dc is for delete_customer
            $problematic_stmt = $stmt_dc;
        }
        // Add other statement variables if they exist in other AJAX actions
        // else if (isset($stmt_add) && $stmt_add instanceof PDOStatement) { $problematic_stmt = $stmt_add; }
        // else if (isset($stmt_update) && $stmt_update instanceof PDOStatement) { $problematic_stmt = $stmt_update; }

        $sql_query_string = $problematic_stmt ? $problematic_stmt->queryString : 'N/A';
        error_log("AJAX PDOException in customers.php (action: " . htmlspecialchars($action) . "): " . $e->getMessage() . " SQL: " . $sql_query_string);
        $response = ['success' => false, 'message' => 'Lỗi cơ sở dữ liệu. Vui lòng thử lại sau. Chi tiết: ' . $e->getMessage()];
    } catch (Exception $e) {
        error_log("AJAX Exception in customers.php (action: " . htmlspecialchars($action) . "): " . $e->getMessage());
        $response = ['success' => false, 'message' => 'Lỗi xử lý máy chủ. Vui lòng thử lại sau. Chi tiết: ' . $e->getMessage()];
    }
    
    // Clean the buffer just before sending JSON, in case of any stray output within try-catch.
    if (ob_get_length() > 0) { // Check if buffer has content
       ob_clean(); 
    }
    echo json_encode($response);
    if(ob_get_length() > 0) { // Check if buffer has content (json_encode output)
      ob_end_flush(); // Send the output
    }
    exit; // Terminate script execution
}

// --- END AJAX HANDLERS ---

// --- PHP LOGIC FOR PAGE DISPLAY (NON-AJAX) ---
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all'; // 'all', 'active', 'inactive'
$membership_filter = $_GET['membership'] ?? 'all'; // 'all', 'Thông thường', 'VIP', 'VVIP'
$page_num = max(1, intval($_GET['page'] ?? 1));
$per_page = 9; // Number of customers per page (3x3 grid)

// Base query parts
$sql_select_fields = "SELECT c.*, 
                        COALESCE(s.total_spent_val, c.total_spent, 0) as total_spent, 
                        COALESCE(s.total_orders_val, c.total_orders, 0) as total_orders,
                        s.latest_sale_date as last_order_date,
                        CASE 
                            WHEN s.latest_sale_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 'Hoạt động gần đây'
                            WHEN s.latest_sale_date >= DATE_SUB(NOW(), INTERVAL 90 DAY) THEN 'Ít hoạt động'
                            WHEN s.latest_sale_date IS NULL AND c.created_at < DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 'Chưa mua hàng'
                            WHEN s.latest_sale_date IS NULL AND c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 'Khách hàng mới'
                            ELSE 'Lâu không mua'
                        END as activity_status,
                        DATEDIFF(NOW(), s.latest_sale_date) as days_since_last_order,
                        (YEAR(CURDATE()) - YEAR(c.birth_date)) - (RIGHT(CURDATE(), 5) < RIGHT(c.birth_date, 5)) as age";
$sql_from_join = "FROM customers c 
                  LEFT JOIN (
                      SELECT customer_id, 
                             MAX(sale_date) as latest_sale_date, 
                             COUNT(id) as total_orders_val, 
                             SUM(total_amount) as total_spent_val 
                      FROM sales 
                      GROUP BY customer_id
                  ) s ON c.id = s.customer_id";

$where_clauses = [];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(c.name LIKE ? OR c.phone LIKE ? OR c.email LIKE ? OR c.customer_code LIKE ?)";
    $search_param = "%" . $search . "%";
    array_push($params, $search_param, $search_param, $search_param, $search_param);
}
if ($status_filter !== 'all') {
    $where_clauses[] = "c.is_active = ?";
    $params[] = ($status_filter === 'active') ? 1 : 0;
}
if ($membership_filter !== 'all') {
    $where_clauses[] = "c.membership_level = ?";
    $params[] = $membership_filter;
}
$where_sql = count($where_clauses) > 0 ? ' WHERE ' . implode(' AND ', $where_clauses) : '';

// Count total customers for pagination
$count_sql = "SELECT COUNT(c.id) $sql_from_join $where_sql";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_customers = $count_stmt->fetchColumn();
$total_pages = ceil($total_customers / $per_page);
$offset = ($page_num - 1) * $per_page;

// Fetch customers for the current page
$customers_sql = "$sql_select_fields $sql_from_join $where_sql ORDER BY c.created_at DESC LIMIT $per_page OFFSET $offset";
$customers_stmt = $pdo->prepare($customers_sql);
$customers_stmt->execute($params);
$customers_on_page = $customers_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch overall stats for the header cards
$stats = [];
$stats_queries = [
    'total_customers_stat' => "SELECT COUNT(*) FROM customers",
    'vip_vvip_customers_stat' => "SELECT COUNT(*) FROM customers WHERE membership_level = 'VIP' OR membership_level = 'VVIP'",
    'total_revenue_stat' => "SELECT SUM(total_amount) FROM sales", // This is total sales revenue, not just from current customers
    'avg_spent_stat' => "SELECT AVG(total_spent) FROM customers WHERE total_orders > 0" // Avg spent per customer who made orders
];
foreach ($stats_queries as $key => $query) {
    $stmt = $pdo->query($query);
    $stats[$key] = $stmt->fetchColumn();
}
if ($stats['total_customers_stat'] > 0 && isset($stats['total_revenue_stat'])) {
     $active_customers_with_spending_query = "SELECT COUNT(DISTINCT id) FROM customers WHERE total_spent > 0";
     $active_customers_count = $pdo->query($active_customers_with_spending_query)->fetchColumn();
     $stats['avg_spent_per_active_customer_stat'] = $active_customers_count > 0 ? ($stats['total_revenue_stat'] / $active_customers_count) : 0;
} else {
    $stats['avg_spent_per_active_customer_stat'] = 0;
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Khách hàng</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --purple-color: #6f42c1;
            --card-shadow: 0 4px 15px rgba(0,0,0,0.08);
            --hover-shadow: 0 8px 25px rgba(0,0,0,0.15);
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .customers-page {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        /* Header Section */
        .page-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 0;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .stat-card {
            background: var(--primary-gradient);
            color: white;
            padding: 1.8rem;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            gap: 1.2rem;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 100%);
            pointer-events: none;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .stat-icon {
            font-size: 2.5rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 70px;
            height: 70px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.3rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .stat-label {
            font-size: 0.95rem;
            font-weight: 600;
            opacity: 0.95;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }

        /* Filters Section */
        .filters-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .search-input-wrapper {
            position: relative;
        }

        .search-input-wrapper .form-control {
            padding-left: 3rem;
            border-radius: 25px;
            border: 2px solid #e9ecef;
            transition: var(--transition);
            height: 45px;
        }

        .search-input-wrapper .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .search-input-wrapper .input-group-text {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            z-index: 5;
            color: #6c757d;
        }

        .filter-select {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            transition: var(--transition);
            height: 45px;
        }

        .filter-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-filter {
            height: 45px;
            padding: 0 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        /* Customer Cards Grid */
        .customers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .customer-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-left: 4px solid transparent;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .customer-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .customer-card[data-membership="vip"] {
            border-left-color: var(--warning-color);
        }

        .customer-card[data-membership="vvip"] {
            border-left-color: var(--purple-color);
        }

        .customer-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .customer-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }

        .customer-code {
            font-size: 0.85rem;
            color: #6c757d;
            font-weight: 500;
        }

        .customer-actions .dropdown-toggle {
            border: none;
            background: rgba(108, 117, 125, 0.1);
            color: #6c757d;
            border-radius: 8px;
            padding: 0.5rem;
            transition: var(--transition);
        }

        .customer-actions .dropdown-toggle:hover {
            background: rgba(108, 117, 125, 0.2);
            transform: scale(1.1);
        }

        .customer-details {
            margin-bottom: 1rem;
            flex: 1;
        }

        .detail-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            color: #495057;
        }

        .detail-item i {
            width: 20px;
            margin-right: 0.5rem;
            opacity: 0.7;
        }

        .detail-item .badge {
            font-size: 0.75rem;
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
        }

        .bg-purple {
            background-color: var(--purple-color) !important;
            color: white;
        }

        .customer-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, #e9ecef 50%, transparent 100%);
            margin: 1rem 0;
        }

        .customer-finance {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .finance-item {
            text-align: center;
            padding: 0.75rem;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 8px;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }

        .finance-label {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
            font-weight: 600;
        }

        .finance-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: #2c3e50;
        }

        .last-order {
            font-size: 0.85rem;
            color: #6c757d;
            text-align: center;
            padding: 0.5rem;
            background: rgba(23, 162, 184, 0.05);
            border-radius: 6px;
            border: 1px solid rgba(23, 162, 184, 0.1);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
        }

        .empty-icon {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        .empty-text {
            color: #6c757d;
            margin-bottom: 1.5rem;
        }

        /* Pagination */
        .pagination-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .pagination .page-link {
            border: none;
            padding: 0.75rem 1rem;
            margin: 0 0.25rem;
            border-radius: 8px;
            color: #6c757d;
            transition: var(--transition);
        }

        .pagination .page-link:hover {
            background: var(--primary-gradient);
            color: white;
            transform: translateY(-2px);
        }

        .pagination .page-item.active .page-link {
            background: var(--primary-gradient);
            border: none;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .pagination-info {
            text-align: center;
            margin-top: 1rem;
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* Modal Improvements */
        .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--hover-shadow);
        }

        .modal-header {
            background: var(--primary-gradient);
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .modal-title {
            font-weight: 700;
        }

        .btn-close {
            filter: brightness(0) invert(1);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .customers-page {
                padding: 1rem 0.5rem;
            }
            
            .page-header {
                padding: 1.5rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .stat-card {
                padding: 1.5rem;
            }
            
            .customers-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .filters-section .row {
                gap: 1rem;
            }
            
            .detail-row {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .customer-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .customer-card:nth-child(even) {
            animation-delay: 0.1s;
        }

        .customer-card:nth-child(3n) {
            animation-delay: 0.2s;
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        .toast {
            border-radius: 8px;
            border: none;
            box-shadow: var(--card-shadow);
        }
    </style>
</head>
<body>
    <div class="customers-page">
        <!-- Header với thống kê -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="page-title">👥 Quản Lý Khách Hàng</h1>
                    <p class="page-subtitle">Quản lý thông tin và theo dõi hoạt động khách hàng</p>
                </div>
                <button class="btn btn-primary btn-lg" onclick="openAddCustomerModal()">
                    <i class="fas fa-plus me-2"></i>Thêm Khách Hàng
                    <kbd class="ms-2">F1</kbd>
                </button>
            </div>

            <!-- Thống kê nhanh -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <div class="stat-number"><?php echo number_format($stats['total_customers_stat'] ?? 0); ?></div>
                        <div class="stat-label">Tổng khách hàng</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✨</div>
                    <div class="stat-info">
                        <div class="stat-number"><?php echo number_format($stats['vip_vvip_customers_stat'] ?? 0); ?></div>
                        <div class="stat-label">VIP + VVIP</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <div class="stat-number"><?php echo number_format($stats['total_revenue_stat'] ?? 0); ?>đ</div>
                        <div class="stat-label">Tổng doanh thu</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-info">
                        <div class="stat-number"><?php echo number_format($stats['avg_spent_per_active_customer_stat'] ?? 0); ?>đ</div>
                        <div class="stat-label">Chi tiêu TB / khách</div>
                    </div>
                </div>
            </div>
        </div>        <!-- Bộ lọc và tìm kiếm -->
        <div class="filters-section">
            <div class="row g-3 align-items-end" id="filterForm">
                <div class="col-md-4">
                    <div class="search-input-wrapper">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="searchInput" name="search" class="form-control" 
                               placeholder="Tìm kiếm khách hàng... (F2)" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <select id="statusFilter" name="status" class="form-select filter-select">
                        <option value="all" <?php if ($status_filter === 'all') echo 'selected'; ?>>Tất cả trạng thái</option>
                        <option value="active" <?php if ($status_filter === 'active') echo 'selected'; ?>>Hoạt động</option>
                        <option value="inactive" <?php if ($status_filter === 'inactive') echo 'selected'; ?>>Không hoạt động</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="membershipFilter" name="membership" class="form-select filter-select">
                        <option value="all" <?php if ($membership_filter === 'all') echo 'selected'; ?>>Tất cả hạng</option>
                        <option value="Thông thường" <?php if ($membership_filter === 'Thông thường') echo 'selected'; ?>>Thông thường</option>
                        <option value="VIP" <?php if ($membership_filter === 'VIP') echo 'selected'; ?>>VIP</option>
                        <option value="VVIP" <?php if ($membership_filter === 'VVIP') echo 'selected'; ?>>VVIP</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary btn-filter w-100" onclick="applyFilters()">
                        <i class="fas fa-filter me-2"></i>Lọc
                    </button>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-secondary btn-filter w-100" onclick="resetFilters()">
                        <i class="fas fa-undo me-2"></i>Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- Danh sách khách hàng -->
        <div class="customers-grid" id="customersGrid">
            <?php if (empty($customers_on_page)): ?>
                <div class="col-12"> <!-- Make empty state span full width if grid expects direct children as columns -->
                    <div class="empty-state" style="grid-column: 1 / -1;"> <!-- Ensure it spans all columns if grid is defined on parent -->
                        <div class="empty-icon">👥</div>
                        <h4 class="empty-title">Không tìm thấy khách hàng</h4>
                        <p class="empty-text">Không có khách hàng nào phù hợp với tiêu chí tìm kiếm của bạn. <br>Hãy thử điều chỉnh bộ lọc hoặc thêm khách hàng mới.</p>
                        <button class="btn btn-primary btn-lg" onclick="openAddCustomerModal()">
                            <i class="fas fa-plus me-2"></i>Thêm Khách Hàng Mới
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($customers_on_page as $customer): ?>
                <div class="customer-card" data-membership="<?php echo htmlspecialchars(strtolower($customer['membership_level'] ?? 'thông thường')); ?>">
                    <div class="customer-header">
                        <div>
                            <h5 class="customer-name"><?php echo htmlspecialchars($customer['name']); ?></h5>
                            <small class="customer-code"><?php echo htmlspecialchars($customer['customer_code']); ?></small>
                        </div>
                        <div class="customer-actions">
                            <div class="dropdown">
                                <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#" onclick="editCustomer(<?php echo $customer['id']; ?>)"><i class="fas fa-edit me-2"></i>Sửa thông tin</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="viewCustomerDetail(<?php echo $customer['id']; ?>)"><i class="fas fa-eye me-2"></i>Xem chi tiết</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="deleteCustomer(<?php echo $customer['id']; ?>, '<?php echo htmlspecialchars(addslashes($customer['name'])); ?>')"><i class="fas fa-trash me-2"></i>Xóa khách hàng</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="customer-details">
                        <div class="detail-row">
                            <div class="detail-item">
                                <i class="fas fa-phone text-primary"></i>
                                <span><?php echo htmlspecialchars($customer['phone']); ?></span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-envelope <?php echo empty($customer['email']) ? 'text-muted' : 'text-success'; ?>"></i>
                                <span class="text-truncate" title="<?php echo htmlspecialchars($customer['email'] ?? 'N/A'); ?>"><?php echo htmlspecialchars($customer['email'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-item">
                                <i class="fas fa-birthday-cake text-warning"></i>
                                <span><?php echo $customer['age'] !== null ? htmlspecialchars($customer['age']) . ' tuổi' : 'N/A'; ?></span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-user-shield text-info"></i>
                                <span class="badge bg-<?php 
                                    $level = strtolower($customer['membership_level'] ?? 'thong thuong');
                                    if ($level === 'vvip') echo 'purple';
                                    elseif ($level === 'vip') echo 'warning text-dark';
                                    else echo 'secondary'; 
                                ?>"><?php echo htmlspecialchars($customer['membership_level'] ?? 'Thông thường'); ?></span>
                            </div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-item">
                                <i class="fas fa-toggle-on <?php echo $customer['is_active'] ? 'text-success' : 'text-danger'; ?>"></i>
                                <span class="badge bg-<?php echo $customer['is_active'] ? 'success' : 'danger'; ?>"><?php echo $customer['is_active'] ? 'Hoạt động' : 'Ngừng HĐ'; ?></span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-history text-primary"></i>
                                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($customer['activity_status'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="customer-divider"></div>

                    <div class="customer-finance">
                        <div class="finance-item">
                            <div class="finance-label"><i class="fas fa-wallet me-1"></i>Tổng chi</div>
                            <div class="finance-value"><?php echo number_format($customer['total_spent'] ?? 0); ?>đ</div>
                        </div>
                        <div class="finance-item">
                            <div class="finance-label"><i class="fas fa-box-open me-1"></i>Đơn hàng</div>
                            <div class="finance-value"><?php echo number_format($customer['total_orders'] ?? 0); ?></div>
                        </div>
                    </div>

                    <?php if ($customer['last_order_date']): ?>
                    <div class="last-order mt-auto"> <!-- mt-auto to push to bottom if card flex height varies -->
                        <i class="fas fa-stopwatch me-1"></i>Mua cuối: <?php echo date('d/m/Y', strtotime($customer['last_order_date'])); ?>
                        <?php if ($customer['days_since_last_order'] !== null): ?>
                            (<?php echo htmlspecialchars($customer['days_since_last_order']); ?> ngày trước)
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="last-order mt-auto text-muted">
                        <i class="fas fa-hourglass-start me-1"></i>Chưa có giao dịch
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div> <!-- End customers-grid -->

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination-section mt-4">
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page_num <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page_num - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&membership=<?php echo $membership_filter; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php 
                        // Determine pagination range
                        $start_page = max(1, $page_num - 2);
                        $end_page = min($total_pages, $page_num + 2);

                        if ($page_num > 3) {
                            echo '<li class="page-item"><a class="page-link" href="?page=1&search='.urlencode($search).'&status='.$status_filter.'&membership='.$membership_filter.'">1</a></li>';
                            if ($page_num > 4) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }

                        for ($i = $start_page; $i <= $end_page; $i++): 
                    ?>
                        <li class="page-item <?php echo ($page_num == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&membership=<?php echo $membership_filter; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php
                        if ($page_num < $total_pages - 2) {
                            if ($page_num < $total_pages - 3) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="?page='.$total_pages.'&search='.urlencode($search).'&status='.$status_filter.'&membership='.$membership_filter.'">'.$total_pages.'</a></li>';
                        }
                    ?>
                    <li class="page-item <?php echo ($page_num >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page_num + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&membership=<?php echo $membership_filter; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="pagination-info">
                Hiển thị <?php echo min($offset + 1, $total_customers); ?>-<?php echo min($offset + $per_page, $total_customers); ?> trên tổng số <?php echo $total_customers; ?> khách hàng
            </div>
        </div>
        <?php endif; ?>

    </div> <!-- End .customers-page -->

    <!-- Add/Edit Customer Modal -->
    <div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="customerModalLabel"><i class="fas fa-user-plus me-2"></i>Thêm Khách Hàng Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="customerForm">
                    <div class="modal-body">
                        <input type="hidden" id="customerId" name="id">
                        <input type="hidden" name="action" id="formAction" value="add_customer">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="customerNameInput" class="form-label">Họ tên <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="customerNameInput" name="name" required placeholder="VD: Nguyễn Văn A">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="customerPhoneInput" class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="customerPhoneInput" name="phone" required placeholder="VD: 0901234567">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="customerEmailInput" class="form-label">Email</label>
                                <input type="email" class="form-control" id="customerEmailInput" name="email" placeholder="VD: email@example.com">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="customerGenderInput" class="form-label">Giới tính</label>
                                <select class="form-select" id="customerGenderInput" name="gender">
                                    <option value="Nam">Nam</option>
                                    <option value="Nữ">Nữ</option>
                                    <option value="Khác" selected>Khác</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="customerBirthDateInput" class="form-label">Ngày sinh</label>
                                <input type="date" class="form-control" id="customerBirthDateInput" name="birth_date">
                            </div>
                            <div class="col-md-6 mb-3" id="customerStatusFieldContainer" style="display: none;"> <!-- Initially hidden for add mode -->
                                <label for="customerStatusInput" class="form-label">Trạng thái</label>
                                <select class="form-select" id="customerStatusInput" name="is_active">
                                    <option value="1">Hoạt động</option>
                                    <option value="0">Không hoạt động</option>
                                </select>
                            </div>
                        </div>
                         <div class="mb-3">
                            <label for="customerAddressInput" class="form-label">Địa chỉ</label>
                            <textarea class="form-control" id="customerAddressInput" name="address" rows="2" placeholder="VD: 123 Đường ABC, Quận XYZ, TP HCM"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="customerNotesInput" class="form-label">Ghi chú</label>
                            <textarea class="form-control" id="customerNotesInput" name="notes" rows="2" placeholder="Thêm ghi chú về khách hàng (nếu có)"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-2"></i>Hủy</button>
                        <button type="submit" class="btn btn-primary" id="saveCustomerButton"><i class="fas fa-save me-2"></i>Lưu Khách Hàng</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Customer Detail Modal -->
    <div class="modal fade" id="customerDetailModal" tabindex="-1" aria-labelledby="customerDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="customerDetailModalLabel"><i class="fas fa-user-tag me-2"></i>Chi Tiết Khách Hàng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="customerDetailContent">
                    <div class="text-center p-5">
                        <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 text-muted">Đang tải dữ liệu chi tiết...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="printCustomerDetail()"><i class="fas fa-print me-2"></i>In Thông Tin</button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="fas fa-times me-2"></i>Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
      <!-- Toasts will be appended here -->
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
    // --- Global Variables & Modals ---
    let customerModalInstance;
    let customerDetailModalInstance;
    const customerForm = document.getElementById('customerForm');
    const customerModalEl = document.getElementById('customerModal');
    const customerDetailModalEl = document.getElementById('customerDetailModal');

    document.addEventListener('DOMContentLoaded', function () {
        customerModalInstance = new bootstrap.Modal(customerModalEl);
        customerDetailModalInstance = new bootstrap.Modal(customerDetailModalEl);

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'F1') {
                e.preventDefault();
                openAddCustomerModal();
            } else if (e.key === 'F2') {
                e.preventDefault();
                document.getElementById('searchInput').focus();
            } else if (e.key === 'Escape') {
                if (customerModalInstance._isShown) customerModalInstance.hide();
                if (customerDetailModalInstance._isShown) customerDetailModalInstance.hide();
            } else if (e.ctrlKey && e.key === 'Enter') {
                 if (customerModalInstance._isShown && customerForm) {
                    e.preventDefault();
                    customerForm.requestSubmit(); // Modern way to submit form
                }
            }
        });
        
        // Debounced search - REMOVED to prevent focus loss
        // let searchTimeout;
        const searchInput = document.getElementById('searchInput');
        if(searchInput) {
            // searchInput.addEventListener('input', function() { // REMOVED
            //     clearTimeout(searchTimeout);
            //     searchTimeout = setTimeout(() => {
            //         applyFilters();
            //     }, 500);
            // });
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault(); // Prevent default form submission if it were in a form
                    applyFilters();
                }
            });
        }
        
        // Auto-apply filters on select change
        ['statusFilter', 'membershipFilter'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('change', applyFilters);
        });


        // Customer Form Submission (Add/Edit)
        if (customerForm) {
            customerForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(customerForm);
                const action = document.getElementById('formAction').value; // 'add_customer' or 'update_customer'
                // formData.append('action', action); // Already set by hidden input

                const submitButton = document.getElementById('saveCustomerButton');
                const originalButtonText = submitButton.innerHTML;
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang lưu...';

                fetch('', { // Post to the same page
                    method: 'POST',
                    body: new URLSearchParams(formData) // Send as x-www-form-urlencoded
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message || 'Thao tác thành công!', 'success');
                        customerModalInstance.hide();
                        // Consider a more targeted update than full reload if possible
                        setTimeout(() => { window.location.reload(); }, 1500);
                    } else {
                        showToast(data.message || 'Có lỗi xảy ra.', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Form submission error:', error);
                    showToast('Lỗi kết nối hoặc xử lý. Vui lòng thử lại.', 'danger');
                })
                .finally(() => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                });
            });
        }
    });

    // --- Filter Functions ---
    function applyFilters() {
        const search = document.getElementById('searchInput').value;
        const status = document.getElementById('statusFilter').value;
        const membership = document.getElementById('membershipFilter').value;
        
        const params = new URLSearchParams(); // Start with a clean slate
        params.set('page', 'customers'); // Direct to customers page via index.php
        params.set('search', search);
        params.set('status', status);
        params.set('membership', membership);
        // params.set('page', '1'); // Page number for pagination, not the route page
        
        const baseUrl = window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1) + 'index.php';
        window.location.href = baseUrl + '?' + params.toString() + '&page_num=1'; // Add page_num for pagination reset
    }

    function resetFilters() {
        const params = new URLSearchParams();
        params.set('page', 'customers'); // Direct to customers page via index.php
        // params.set('page', '1'); // Page number for pagination, not the route page
        const baseUrl = window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1) + 'index.php';
        window.location.href = baseUrl + '?' + params.toString() + '&page_num=1'; // Add page_num for pagination reset
    }

    // --- Modal Control Functions ---
    function openAddCustomerModal() {
        customerForm.reset();
        document.getElementById('customerModalLabel').innerHTML = '<i class="fas fa-user-plus me-2"></i>Thêm Khách Hàng Mới';
        document.getElementById('formAction').value = 'add_customer';
        document.getElementById('customerId').value = '';
        document.getElementById('customerStatusFieldContainer').style.display = 'none'; // Hide status for new customer
        customerModalInstance.show();
        setTimeout(() => document.getElementById('customerNameInput').focus(), 500); // Focus after modal animation
    }

    function editCustomer(id) {
        customerForm.reset();
        document.getElementById('customerModalLabel').innerHTML = '<i class="fas fa-user-edit me-2"></i>Sửa Thông Tin Khách Hàng';
        document.getElementById('formAction').value = 'update_customer';
        document.getElementById('customerId').value = id;
        document.getElementById('customerStatusFieldContainer').style.display = 'block'; // Show status for editing

        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({ action: 'get_customer', id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const cust = data.data;
                document.getElementById('customerNameInput').value = cust.name || '';
                document.getElementById('customerPhoneInput').value = cust.phone || '';
                document.getElementById('customerEmailInput').value = cust.email || '';
                document.getElementById('customerGenderInput').value = cust.gender || 'Khác';
                document.getElementById('customerBirthDateInput').value = cust.birth_date || '';
                document.getElementById('customerAddressInput').value = cust.address || '';
                document.getElementById('customerNotesInput').value = cust.notes || '';
                document.getElementById('customerStatusInput').value = cust.is_active !== null ? cust.is_active.toString() : '1';
                customerModalInstance.show();
            } else {
                showToast(data.message || 'Không thể tải dữ liệu khách hàng.', 'danger');
            }
        })
        .catch(error => {
            console.error('Error fetching customer for edit:', error);
            showToast('Lỗi khi tải dữ liệu để sửa.', 'danger');
        });
    }    function deleteCustomer(id, name) {
        if (!confirm(`Bạn có chắc chắn muốn xóa khách hàng "${name}" (ID: ${id})?\nHành động này không thể hoàn tác!`)) {
            return;
        }
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({ action: 'delete_customer', id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message || 'Xóa khách hàng thành công!', 'success');
                setTimeout(() => { window.location.reload(); }, 1500);
            } else {
                showToast(data.message || 'Xóa thất bại.', 'danger');
            }
        })
        .catch(error => {
            console.error('Error deleting customer:', error);
            showToast('Lỗi khi xóa khách hàng.', 'danger');
        });
    }

    function viewCustomerDetail(id) {
        const contentArea = document.getElementById('customerDetailContent');
        contentArea.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"></div><p class="mt-3 text-muted">Đang tải dữ liệu...</p></div>';
        customerDetailModalInstance.show();

        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({ action: 'get_customer_details_for_modal', id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                renderCustomerDetailModal(data.data);
            } else {
                contentArea.innerHTML = '<div class="alert alert-danger m-3">Lỗi: ' + (data.message || 'Không thể tải chi tiết khách hàng.') + '</div>';
            }
        })
        .catch(error => {
            console.error('Error fetching customer detail:', error);
            contentArea.innerHTML = '<div class="alert alert-danger m-3">Lỗi kết nối hoặc xử lý khi tải chi tiết.</div>';
        });
    }
    
    function renderCustomerDetailModal(customer) {
        const contentArea = document.getElementById('customerDetailContent');
        let salesHistoryHtml = '<p class="text-muted">Chưa có lịch sử mua hàng.</p>';
        if (customer.sales_history && customer.sales_history.length > 0) {
            salesHistoryHtml = `
                <ul class="list-group list-group-flush">
                    ${customer.sales_history.map(sale => `                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <a href="all_sales.php?view_sale=${sale.id}" target="_blank">${sale.sale_code}</a> 
                                <small class="text-muted">(${new Date(sale.sale_date).toLocaleDateString('vi-VN')})</small>
                            </div>
                            <div>
                                <span class="fw-bold me-2">${Number(sale.total_amount).toLocaleString('vi-VN')}đ</span>
                                <span class="badge bg-${getSaleStatusClass(sale.status)}">${sale.status || 'N/A'}</span>
                            </div>
                        </li>
                    `).join('')}
                </ul>`;
        }

        let returnHistoryHtml = '<p class="text-muted">Chưa có lịch sử trả hàng.</p>';
        if (customer.return_history && customer.return_history.length > 0) {
            returnHistoryHtml = `
                <ul class="list-group list-group-flush">
                    ${customer.return_history.map(returnItem => `                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <a href="returns.php?view_return=${returnItem.id}" target="_blank">${returnItem.return_code}</a> 
                                <small class="text-muted">(${new Date(returnItem.return_date).toLocaleDateString('vi-VN')})</small>
                            </div>
                            <div>
                                <span class="fw-bold me-2">${Number(returnItem.total_refund).toLocaleString('vi-VN')}đ</span>
                                <span class="badge bg-danger">Đã trả</span>
                            </div>
                        </li>
                    `).join('')}
                </ul>`;
        }

        contentArea.innerHTML = `
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-4 border-end pe-lg-4 mb-4 mb-lg-0">
                        <div class="text-center mb-3">
                            <div style="width: 100px; height: 100px; background: var(--primary-gradient); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; font-size: 2.5rem; font-weight: bold;">
                                ${customer.name ? customer.name.substring(0, 2).toUpperCase() : 'KH'}
                            </div>
                            <h4 class="mt-3 mb-1">${customer.name || 'N/A'}</h4>
                            <p class="text-muted mb-1">${customer.customer_code || 'N/A'}</p>
                            <span class="badge bg-${customer.is_active ? 'success' : 'danger'} me-2">${customer.is_active ? 'Hoạt động' : 'Ngừng HĐ'}</span>
                            <span class="badge bg-${getMembershipBadgeClass(customer.membership_level)}">${customer.membership_level || 'Thông thường'}</span>
                        </div>
                        <hr>
                        <h6 class="text-muted mb-2"><i class="fas fa-info-circle me-2"></i>Thông tin cá nhân</h6>
                        <p><i class="fas fa-phone fa-fw me-2 text-primary"></i>${customer.phone || 'N/A'}</p>
                        <p><i class="fas fa-envelope fa-fw me-2 text-success"></i>${customer.email || 'N/A'}</p>
                        <p><i class="fas fa-birthday-cake fa-fw me-2 text-warning"></i>${customer.formatted_birth_date || 'N/A'} ${customer.age !== null ? '(' + customer.age + ' tuổi)' : ''}</p>
                        <p><i class="fas fa-venus-mars fa-fw me-2 text-info"></i>${customer.gender || 'N/A'}</p>
                        <p><i class="fas fa-map-marker-alt fa-fw me-2 text-danger"></i>${customer.address || 'N/A'}</p>
                        <p class="mt-2"><em><i class="fas fa-sticky-note fa-fw me-2 text-secondary"></i>${customer.notes || 'Không có ghi chú'}</em></p>                        <p class="small text-muted mt-3">Ngày tạo: ${customer.formatted_created_at || 'N/A'}</p>
                    </div>
                    <div class="col-lg-8 ps-lg-4">
                        <h6 class="text-muted mb-3"><i class="fas fa-chart-line me-2"></i>Thống kê & Hoạt động</h6>
                        <div class="row mb-3 g-3">
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded text-center">
                                    <div class="fs-5 fw-bold">${Number(customer.total_spent || 0).toLocaleString('vi-VN')}đ</div>
                                    <small class="text-muted">Tổng chi tiêu</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded text-center">
                                    <div class="fs-5 fw-bold">${customer.total_orders || 0}</div>
                                    <small class="text-muted">Tổng đơn hàng</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded text-center">
                                    <div class="fs-5">${customer.last_order_date ? new Date(customer.last_order_date).toLocaleDateString('vi-VN') : 'Chưa mua'}</div>
                                    <small class="text-muted">Lần mua cuối</small>
                                </div>
                            </div>
                        </div>
                        <h6 class="text-muted mb-3"><i class="fas fa-history me-2"></i>Lịch sử mua hàng gần đây (10 đơn)</h6>
                        ${salesHistoryHtml}
                        <h6 class="text-muted mb-3 mt-4"><i class="fas fa-undo me-2"></i>Lịch sử trả hàng gần đây (5 đơn)</h6>
                        ${returnHistoryHtml}
                    </div>
                </div>
            </div>
        `;
    }

    function getSaleStatusClass(status) {
        if (!status) return 'secondary';
        status = status.toLowerCase();
        if (status === 'hoàn thành') return 'success';
        if (status === 'đã hủy') return 'danger';
        if (status === 'đang xử lý') return 'warning text-dark';
        return 'info';
    }

    function getMembershipBadgeClass(level) {
        if (!level) return 'secondary';
        level = level.toLowerCase();
        if (level === 'vvip') return 'purple';
        if (level === 'vip') return 'warning text-dark';
        return 'secondary';
    }
    
    function printCustomerDetail(){
        const detailModalContent = document.getElementById('customerDetailContent').innerHTML;
        const printWindow = window.open('', '_blank', 'height=600,width=800');
        printWindow.document.write('<html><head><title>Chi Tiết Khách Hàng</title>');
        // Add Bootstrap for basic styling, or your custom print CSS
        printWindow.document.write('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">');
        printWindow.document.write('<style>body{padding:20px;font-family:sans-serif;} @media print { .modal-footer, .btn {display:none!important;} }</style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write(detailModalContent);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus(); // Necessary for some browsers
        setTimeout(() => { printWindow.print(); }, 500); // Timeout to ensure content is loaded
    }

    // --- Toast Notification Function ---
    function showToast(message, type = 'info', duration = 3000) {
        const toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            console.warn('Toast container not found. Cannot display toast.');
            alert(message); // Fallback
            return;
        }

        const toastId = 'toast-' + Date.now();        const toastHTML = `
            <div id="${toastId}" class="toast align-items-center text-white bg-${type === 'danger' ? 'danger' : (type === 'success' ? 'success' : 'primary')} border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="${duration}">
              <div class="d-flex">
                <div class="toast-body">
                  ${type === 'danger' ? '<i class="fas fa-exclamation-triangle me-2"></i>' : (type === 'success' ? '<i class="fas fa-check-circle me-2"></i>' : '<i class="fas fa-info-circle me-2"></i>')}
                  ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
              </div>
            </div>
        `;
        toastContainer.insertAdjacentHTML('beforeend', toastHTML);
        
        const toastElement = document.getElementById(toastId);
        const toastInstance = new bootstrap.Toast(toastElement);
        toastInstance.show();

        // Optional: Remove toast from DOM after it's hidden to prevent buildup
        toastElement.addEventListener('hidden.bs.toast', function () {
            toastElement.remove();
        });
    }

    // Initial toast for keyboard shortcuts (example)
    // setTimeout(() => {
    //     showToast('💡 Mẹo: Dùng F1 để thêm mới, F2 để tìm kiếm nhanh!', 'info', 5000);
    // }, 1500);

    </script>
</body>
</html>