<?php
require_once 'config/database.php';

// Xử lý AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_customer':
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $gender = $_POST['gender'] ?? 'Khác';
            $birth_date = $_POST['birth_date'] ?? null;
            $is_active_val = 1; // Mặc định là active khi thêm mới
            $notes = trim($_POST['notes'] ?? '');
            
            // Validation
            if (empty($name) || empty($phone)) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ tên và số điện thoại!']);
                exit;
            }
            
            // Check phone exists
            $check_sql = "SELECT id FROM customers WHERE phone = ?";
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute([$phone]);
            if ($check_stmt->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'Số điện thoại đã tồn tại!']);
                exit;
            }
            
            // Generate customer code
            $code_sql = "SELECT MAX(CAST(SUBSTRING(customer_code, 3) AS UNSIGNED)) as max_num FROM customers WHERE customer_code LIKE 'KH%'";
            $code_result = $pdo->query($code_sql);
            $max_num = $code_result->fetch()['max_num'] ?? 0;
            $customer_code = 'KH' . str_pad($max_num + 1, 3, '0', STR_PAD_LEFT);
            
            // Insert customer
            $sql = "INSERT INTO customers (customer_code, name, phone, email, address, gender, birth_date, notes, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            
            try {
                $stmt->execute([$customer_code, $name, $phone, $email, $address, $gender, $birth_date, $notes, $is_active_val]);
                echo json_encode(['success' => true, 'message' => "Thêm khách hàng $customer_code thành công!"]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
            }
            exit;
            
        case 'update_customer':
            $id = $_POST['id'] ?? 0;
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $gender = $_POST['gender'] ?? 'Khác';
            $birth_date = $_POST['birth_date'] ?? null;
            $status_from_post = $_POST['status'] ?? 'active'; // Giữ nguyên cách nhận từ form
            $is_active_val = ($status_from_post === 'active') ? 1 : 0; // Chuyển đổi sang 0/1
            $notes = trim($_POST['notes'] ?? '');
            
            if (empty($name) || empty($phone) || !$id) {
                echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ!']);
                exit;
            }
            
            // Check phone exists (except current customer)
            $check_sql = "SELECT id FROM customers WHERE phone = ? AND id != ?";
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute([$phone, $id]);
            if ($check_stmt->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'Số điện thoại đã tồn tại!']);
                exit;
            }
            
            $sql = "UPDATE customers SET name=?, phone=?, email=?, address=?, gender=?, birth_date=?, is_active=?, notes=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            
            try {
                $stmt->execute([$name, $phone, $email, $address, $gender, $birth_date, $is_active_val, $notes, $id]);
                echo json_encode(['success' => true, 'message' => 'Cập nhật khách hàng thành công!']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
            }
            exit;
            
        case 'delete_customer':
            $id = $_POST['id'] ?? 0;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID khách hàng không hợp lệ!']);
                exit;
            }
            
            try {
                $sql = "DELETE FROM customers WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'Xóa khách hàng thành công!']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
            }
            exit;
            
        case 'get_customer':
            $id = $_POST['id'] ?? 0;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID không hợp lệ!']);
                exit;
            }
            
            $sql = "SELECT * FROM customers WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($customer) {
                echo json_encode(['success' => true, 'data' => $customer]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy khách hàng!']);
            }
            exit;
    }
}

// Lấy danh sách khách hàng với tìm kiếm và phân trang
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$membership_filter = $_GET['membership'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build query
$where_clauses = [];
$params = [];

if ($search) {
    $where_clauses[] = "(name LIKE ? OR phone LIKE ? OR email LIKE ? OR customer_code LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($status_filter && $status_filter !== 'all') {
    $where_clauses[] = "is_active = ?"; //Sửa status thành is_active
    $params[] = ($status_filter === 'active') ? 1 : 0; // Chuyển đổi active/inactive thành 1/0
}

if ($membership_filter && $membership_filter !== 'all') {
    $where_clauses[] = "membership_level = ?";
    $params[] = $membership_filter;
}

$where_sql = $where_clauses ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Count total
$count_sql = "SELECT COUNT(*) FROM customers $where_sql";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_customers = $count_stmt->fetchColumn();
$total_pages = ceil($total_customers / $per_page);

// Get customers
$sql = "SELECT c.*, 
        s.latest_sale_date as last_order_date, /* Get last order date from subquery */
        CASE 
            WHEN s.latest_sale_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 'Hoạt động'
            WHEN s.latest_sale_date >= DATE_SUB(NOW(), INTERVAL 90 DAY) THEN 'Ít hoạt động'
            ELSE 'Lâu không mua'
        END as activity_status,
        DATEDIFF(NOW(), s.latest_sale_date) as days_since_last_order,
        YEAR(NOW()) - YEAR(c.birth_date) as age
        FROM customers c
        LEFT JOIN (
            SELECT customer_id, MAX(sale_date) as latest_sale_date 
            FROM sales 
            GROUP BY customer_id
        ) s ON c.id = s.customer_id
        $where_sql 
        ORDER BY c.total_spent DESC, c.created_at DESC 
        LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thống kê nhanh
$stats_sql = "SELECT 
    COUNT(*) as total_customers,
    COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_customers,
    COUNT(CASE WHEN membership_level = 'VIP' THEN 1 END) as vip_customers,
    COUNT(CASE WHEN membership_level = 'VVIP' THEN 1 END) as vvip_customers,
    SUM(total_spent) as total_revenue,
    AVG(total_spent) as avg_spent
    FROM customers";
$stats_stmt = $pdo->query($stats_sql);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<style>
/* Customers Page Styles */
.customers-page {
    padding: 0;
}

.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem; /* Reduced padding */
    border-radius: 10px; /* Slightly smaller border radius */
    display: flex;
    align-items: center;
    gap: 0.75rem; /* Reduced gap */
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-icon {
    font-size: 1.8rem; /* Increased icon size */
    background-color: rgba(255, 255, 255, 0.15); /* Slightly more opaque background */
    border-radius: 8px;
    padding: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    text-shadow: 0px 0px 4px rgba(0, 0, 0, 0.5); /* Added text shadow for icon */
}

.stat-info {
    /* Added for better structure if needed, or can be merged */
}

.stat-number {
    font-size: 1.4rem; /* Increased font size */
    font-weight: bold;
    margin-bottom: 0.15rem;
    color: #FFFFFF; /* Explicit white color */
    text-shadow: 0px 1px 3px rgba(0, 0, 0, 0.4); /* Added text shadow */
}

.stat-label {
    opacity: 1; /* Fully opaque */
    font-size: 0.85rem; /* Increased font size */
    font-weight: 700;   /* Bold font weight */
    color: #FFFFFF !important; /* Forced white color for diagnosis */
    text-shadow: 0px 1px 1px rgba(0,0,0,0.7); /* Sharper, slightly offset dark shadow for contrast */
}

.search-box {
    position: relative;
}

.search-box input {
    padding-left: 2.5rem;
    border-radius: 25px;
    border: 2px solid #e9ecef;
    transition: all 0.3s;
}

.search-box input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.customers-grid .empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #6c757d;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.customer-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s;
    overflow: hidden;
    border: 2px solid transparent;
}

.customer-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
}

.customer-card[data-membership="VIP"] {
    border-color: #f59e0b;
}

.customer-card[data-membership="VVIP"] {
    border-color: #8b5cf6;
    background: linear-gradient(145deg, #fafafa 0%, #f3f0ff 100%);
}

.customer-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-bottom: 1px solid #e5e7eb;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
}

.header-right .btn {
    border: none;
    background: transparent;
    color: #6b7280;
    padding: 0.5rem;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.header-right .btn:hover {
    background: #f3f4f6;
    color: #374151;
}

.customer-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1.1rem;
}

.customer-basic {
    flex: 1;
}

.customer-name {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #2d3748;
}

.customer-code {
    color: #6c757d;
    font-size: 0.9rem;
}

.customer-contact {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #f3f4f6;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
    font-size: 0.9rem;
}

.contact-item:last-child {
    margin-bottom: 0;
}

.contact-item i {
    width: 16px;
    font-size: 0.9rem;
}

.customer-stats {
    padding: 1rem 1.5rem;
    display: flex;
    gap: 1rem;
    border-bottom: 1px solid #f3f4f6;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.stat-icon {
    width: 32px;
    height: 32px;
    background: white;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    flex-shrink: 0;
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-weight: 600;
    color: #1f2937;
    font-size: 0.95rem;
    line-height: 1;
}

.stat-label {
    font-size: 0.7rem;
    color: #6b7280;
    margin-top: 0.25rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.customer-badges {
    padding: 1rem 1.5rem;
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    border-bottom: 1px solid #f3f4f6;
}

.badge {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
}

.membership-badge.membership-vvip {
    background: linear-gradient(135deg, #8b5cf6, #a855f7);
    color: white;
}

.membership-badge.membership-vip {
    background: linear-gradient(135deg, #f59e0b, #f97316);
    color: white;
}

.membership-badge.membership-thông-thường {
    background: #6b7280;
    color: white;
}

.activity-badge.activity-hoạt-động {
    background: #10b981;
    color: white;
}

.activity-badge.activity-ít-hoạt-động {
    background: #f59e0b;
    color: white;
}

.activity-badge.activity-lâu-không-mua {
    background: #ef4444;
    color: white;
}

.last-order {
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: #f9fafb;
}

.last-order-icon {
    width: 24px;
    height: 24px;
    background: white;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #e5e7eb;
    flex-shrink: 0;
}

.last-order-content {
    flex: 1;
}

.last-order-date {
    font-size: 0.85rem;
    color: #374151;
    font-weight: 500;
    line-height: 1;
}

.last-order-days {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.pagination-nav {
    margin-top: 2rem;
}

.pagination-info {
    text-align: center;
    margin-top: 1rem;
    color: #6c757d;
    font-size: 0.9rem;
}

/* Modal improvements */
.modal-content {
    border-radius: 15px;
    border: none;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
}

.modal-header {
    border-bottom: 2px solid #f8f9fa;
    padding: 1.5rem;
}

.form-control, .form-select {
    border-radius: 8px;
    border: 2px solid #e9ecef;
    transition: all 0.3s;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

/* Responsive */
@media (max-width: 768px) {
    .stat-card {
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .customer-card {
        margin-bottom: 1rem;
    }
    
    .filters-section .row > div {
        margin-bottom: 0.5rem;
    }
}
</style>

<div class="customers-page">
    <!-- Header với thống kê -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>👥 Quản Lý Khách Hàng</h2>
                <p class="text-muted mb-0">Quản lý thông tin và theo dõi hoạt động khách hàng</p>
            </div>
            <button class="btn btn-primary" onclick="openAddCustomerModal()">
                <i class="fas fa-plus"></i> Thêm Khách Hàng <kbd>F1</kbd>
            </button>
        </div>

        <!-- Thống kê nhanh -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <div class="stat-number"><?php echo number_format($stats['total_customers']); ?></div>
                        <div class="stat-label">Tổng khách hàng</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">✨</div>
                    <div class="stat-info">
                        <div class="stat-number"><?php echo number_format($stats['vip_customers'] + $stats['vvip_customers']); ?></div>
                        <div class="stat-label">VIP + VVIP</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <div class="stat-number"><?php echo number_format($stats['total_revenue']); ?>đ</div>
                        <div class="stat-label">Tổng doanh thu</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-info">
                        <div class="stat-number"><?php echo number_format($stats['avg_spent']); ?>đ</div>
                        <div class="stat-label">Chi tiêu trung bình</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bộ lọc và tìm kiếm -->
    <div class="filters-section mb-4">
        <div class="row">
            <div class="col-md-4">
                <div class="search-box">
                    <input type="text" id="searchInput" class="form-control" 
                           placeholder="🔍 Tìm kiếm khách hàng... (F2)" value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select id="statusFilter" class="form-select">
                    <option value="all">Tất cả trạng thái</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Hoạt động</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Không hoạt động</option>
                </select>
            </div>
            <div class="col-md-3">
                <select id="membershipFilter" class="form-select">
                    <option value="all">Tất cả hạng thành viên</option>
                    <option value="Thông thường" <?php echo $membership_filter === 'Thông thường' ? 'selected' : ''; ?>>Thông thường</option>
                    <option value="VIP" <?php echo $membership_filter === 'VIP' ? 'selected' : ''; ?>>VIP</option>
                    <option value="VVIP" <?php echo $membership_filter === 'VVIP' ? 'selected' : ''; ?>>VVIP</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                    <i class="fas fa-undo"></i> Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Danh sách khách hàng -->
    <div class="customers-grid">
        <?php if (empty($customers)): ?>
            <div class="empty-state">
                <div class="empty-icon">👥</div>
                <h4>Chưa có khách hàng nào</h4>
                <p>Hãy thêm khách hàng đầu tiên để bắt đầu!</p>
                <button class="btn btn-primary" onclick="openAddCustomerModal()">
                    <i class="fas fa-plus"></i> Thêm Khách Hàng
                </button>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($customers as $customer): ?>
                    <div class="col-md-6 col-lg-4 mb-4">                        <div class="customer-card" data-membership="<?php echo $customer['membership_level']; ?>">
                            <!-- Header với avatar và thông tin cơ bản -->
                            <div class="customer-header">
                                <div class="header-left">
                                    <div class="customer-avatar">
                                        <?php echo strtoupper(substr($customer['name'], 0, 2)); ?>
                                    </div>
                                    <div class="customer-basic">
                                        <h5 class="customer-name"><?php echo htmlspecialchars($customer['name']); ?></h5>
                                        <span class="customer-code"><?php echo $customer['customer_code']; ?></span>
                                    </div>
                                </div>
                                <div class="header-right">
                                    <div class="dropdown">
                                        <button class="btn btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-h"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="#" onclick="editCustomer(<?php echo $customer['id']; ?>)">
                                                <i class="fas fa-edit"></i> Sửa thông tin</a></li>
                                            <li><a class="dropdown-item" href="#" onclick="viewCustomerDetail(<?php echo $customer['id']; ?>)">
                                                <i class="fas fa-eye"></i> Xem chi tiết</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteCustomer(<?php echo $customer['id']; ?>, '<?php echo htmlspecialchars($customer['name']); ?>')">
                                                <i class="fas fa-trash"></i> Xóa khách hàng</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Thông tin liên lạc -->
                            <div class="customer-contact">
                                <div class="contact-item">
                                    <i class="fas fa-phone text-primary"></i>
                                    <span><?php echo $customer['phone']; ?></span>
                                </div>
                                <?php if ($customer['email']): ?>
                                <div class="contact-item">
                                    <i class="fas fa-envelope text-success"></i>
                                    <span><?php echo htmlspecialchars($customer['email']); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="contact-item">
                                    <i class="fas fa-birthday-cake text-warning"></i>
                                    <span><?php echo $customer['age'] ? $customer['age'] . ' tuổi' : 'Chưa có'; ?></span>
                                </div>
                            </div>

                            <!-- Badges hạng thành viên và trạng thái -->
                            <div class="customer-badges">
                                <span class="badge membership-badge membership-<?php echo strtolower($customer['membership_level']); ?>">
                                    <?php 
                                    echo $customer['membership_level'] === 'VVIP' ? '👑 VVIP' : 
                                         ($customer['membership_level'] === 'VIP' ? '⭐ VIP' : '👤 Thông thường'); 
                                    ?>
                                </span>
                                <span class="badge activity-badge activity-<?php echo str_replace(' ', '-', strtolower($customer['activity_status'])); ?>">
                                    <?php echo $customer['activity_status']; ?>
                                </span>
                                <?php if (isset($customer['is_active']) && $customer['is_active'] == 0): ?>
                                    <span class="badge bg-secondary">Không hoạt động</span>
                                <?php endif; ?>
                            </div>

                            <!-- Thống kê mua hàng -->
                            <div class="customer-stats">
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i class="fas fa-money-bill-wave text-success"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-value"><?php echo number_format($customer['total_spent']); ?>đ</div>
                                        <div class="stat-label">Tổng chi tiêu</div>
                                    </div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i class="fas fa-shopping-bag text-info"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-value"><?php echo $customer['total_orders']; ?></div>
                                        <div class="stat-label">Đơn hàng</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Lần mua cuối -->
                            <?php if ($customer['last_order_date']): ?>
                            <div class="last-order">
                                <div class="last-order-icon">
                                    <i class="fas fa-clock text-muted"></i>
                                </div>
                                <div class="last-order-content">
                                    <div class="last-order-date">
                                        <?php echo date('d/m/Y', strtotime($customer['last_order_date'])); ?>
                                    </div>
                                    <div class="last-order-days">
                                        <?php echo $customer['days_since_last_order']; ?> ngày trước
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav class="pagination-nav">
        <ul class="pagination justify-content-center">
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&membership=<?php echo $membership_filter; ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
            
            <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&membership=<?php echo $membership_filter; ?>">
                    <?php echo $i; ?>
                </a>
            </li>
            <?php endfor; ?>
            
            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status_filter; ?>&membership=<?php echo $membership_filter; ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        </ul>
        <div class="pagination-info">
            Hiển thị <?php echo ($page-1)*$per_page + 1; ?>-<?php echo min($page*$per_page, $total_customers); ?> 
            trong tổng số <?php echo $total_customers; ?> khách hàng
        </div>
    </nav>
    <?php endif; ?>
</div>

<!-- Modal thêm/sửa khách hàng -->
<div class="modal fade" id="customerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="customerModalTitle">👥 Thêm Khách Hàng Mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="customerForm">
                <div class="modal-body">
                    <input type="hidden" id="customerId" name="id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Họ tên <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="customerName" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="customerPhone" name="phone" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" id="customerEmail" name="email">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Giới tính</label>
                                <select class="form-select" id="customerGender" name="gender">
                                    <option value="Nam">Nam</option>
                                    <option value="Nữ">Nữ</option>
                                    <option value="Khác">Khác</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Ngày sinh</label>
                                <input type="date" class="form-control" id="customerBirthDate" name="birth_date">
                            </div>
                        </div>
                        <div class="col-md-6" id="statusField" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Trạng thái</label>
                                <select class="form-select" id="customerStatus" name="status">
                                    <option value="active">Hoạt động</option>
                                    <option value="inactive">Không hoạt động</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Địa chỉ</label>
                        <textarea class="form-control" id="customerAddress" name="address" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ghi chú</label>
                        <textarea class="form-control" id="customerNotes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu <kbd>Ctrl+Enter</kbd>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal xem chi tiết khách hàng -->
<div class="modal fade" id="customerDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">👁️ Chi Tiết Khách Hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="customerDetailContent">
                <div class="text-center">
                    <div class="spinner-border" role="status"></div>
                    <p>Đang tải dữ liệu...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Customers page JavaScript
let isEditMode = false;

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // F1 - Thêm khách hàng mới
    if (e.key === 'F1') {
        e.preventDefault();
        openAddCustomerModal();
        return;
    }
    
    // F2 - Focus vào ô tìm kiếm
    if (e.key === 'F2') {
        e.preventDefault();
        document.getElementById('searchInput').focus();
        return;
    }
    
    // Ctrl + Enter - Submit form trong modal
    if (e.ctrlKey && e.key === 'Enter') {
        e.preventDefault();
        if (document.getElementById('customerModal').classList.contains('show')) {
            document.getElementById('customerForm').dispatchEvent(new Event('submit'));
        }
        return;
    }
    
    // ESC - Đóng modal
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(modal => {
            bootstrap.Modal.getInstance(modal).hide();
        });
        return;
    }
});

// Auto search với debounce
let searchTimeout;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
});

// Filter change handlers
document.getElementById('statusFilter').addEventListener('change', applyFilters);
document.getElementById('membershipFilter').addEventListener('change', applyFilters);

function applyFilters() {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    const membership = document.getElementById('membershipFilter').value;
    
    const params = new URLSearchParams({
        search: search,
        status: status,
        membership: membership,
        page: 1
    });
    
    window.location.href = '?' + params.toString();
}

function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('statusFilter').value = 'all';
    document.getElementById('membershipFilter').value = 'all';
    window.location.href = '?';
}

function openAddCustomerModal() {
    isEditMode = false;
    document.getElementById('customerModalTitle').textContent = '👥 Thêm Khách Hàng Mới';
    document.getElementById('customerForm').reset();
    document.getElementById('customerId').value = '';
    document.getElementById('statusField').style.display = 'none';
    
    const modal = new bootstrap.Modal(document.getElementById('customerModal'));
    modal.show();
    
    // Focus vào tên khách hàng
    setTimeout(() => {
        document.getElementById('customerName').focus();
    }, 500);
}

function editCustomer(id) {
    isEditMode = true;
    document.getElementById('customerModalTitle').textContent = '✏️ Sửa Thông Tin Khách Hàng';
    document.getElementById('statusField').style.display = 'block';
    
    // Load customer data
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=get_customer&id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const customer = data.data;
            document.getElementById('customerId').value = customer.id;
            document.getElementById('customerName').value = customer.name;
            document.getElementById('customerPhone').value = customer.phone;
            document.getElementById('customerEmail').value = customer.email || '';
            document.getElementById('customerAddress').value = customer.address || '';
            document.getElementById('customerGender').value = customer.gender;
            document.getElementById('customerBirthDate').value = customer.birth_date || '';
            document.getElementById('customerStatus').value = customer.status;
            document.getElementById('customerNotes').value = customer.notes || '';
            
            const modal = new bootstrap.Modal(document.getElementById('customerModal'));
            modal.show();
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        showToast('Lỗi khi tải dữ liệu khách hàng!', 'error');
        console.error('Error:', error);
    });
}

function deleteCustomer(id, name) {
    if (!confirm(`Bạn có chắc chắn muốn xóa khách hàng "${name}"?\n\nHành động này không thể hoàn tác!`)) {
        return;
    }
    
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=delete_customer&id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        showToast('Lỗi khi xóa khách hàng!', 'error');
        console.error('Error:', error);
    });
}

function viewCustomerDetail(id) {
    const modal = new bootstrap.Modal(document.getElementById('customerDetailModal'));
    modal.show();
    
    // TODO: Load customer detail with purchase history
    document.getElementById('customerDetailContent').innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status"></div>
            <p>Đang tải dữ liệu...</p>
        </div>
    `;
    
    // Simulate loading (replace with actual AJAX call)
    setTimeout(() => {
        document.getElementById('customerDetailContent').innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Tính năng xem chi tiết sẽ được phát triển trong phiên bản tiếp theo!
                <br>Sẽ bao gồm: Lịch sử mua hàng, thống kê chi tiêu, sản phẩm yêu thích...
            </div>
        `;
    }, 1000);
}

// Handle form submission
document.getElementById('customerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const action = isEditMode ? 'update_customer' : 'add_customer';
    formData.append('action', action);
    
    // Convert FormData to URLSearchParams
    const params = new URLSearchParams();
    for (let [key, value] of formData.entries()) {
        params.append(key, value);
    }
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';
    submitBtn.disabled = true;
    
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: params.toString()
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('customerModal')).hide();
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        showToast('Lỗi khi lưu thông tin khách hàng!', 'error');
        console.error('Error:', error);
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});

// Show tooltip for keyboard shortcuts on page load
setTimeout(() => {
    showToast('💡 Sử dụng F1 để thêm khách hàng, F2 để tìm kiếm nhanh!', 'info', 4000);
}, 1000);
</script>
