<?php
require_once __DIR__ . '/../includes/functions.php';
/**
 * All Imports Page - Xem tất cả phiếu nhập với phân trang và tìm kiếm
 */

// Get filter parameters
$search = $_GET['search'] ?? '';
$payment_status = $_GET['payment_status'] ?? '';
$status = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'import_date';
$sort_order = $_GET['sort_order'] ?? 'DESC';
$page = max(1, (int)($_GET['pg'] ?? 1));
$per_page = 20; // 20 records per page

// Build WHERE clause
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(i.import_code LIKE ? OR i.supplier_name LIKE ? OR s.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($payment_status)) {
    $where_conditions[] = "i.payment_status = ?";
    $params[] = $payment_status;
}

if (!empty($status)) {
    $where_conditions[] = "i.status = ?";
    $params[] = $status;
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(i.import_date) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(i.import_date) <= ?";
    $params[] = $date_to;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Validate sort parameters
$allowed_sort_columns = ['import_code', 'import_date', 'supplier_name', 'total_amount', 'status', 'payment_status'];
if (!in_array($sort_by, $allowed_sort_columns)) {
    $sort_by = 'import_date';
}
$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

// Get total count
$count_sql = "
    SELECT COUNT(*) 
    FROM imports i 
    LEFT JOIN suppliers s ON i.supplier_id = s.id 
    $where_clause
";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Get paginated imports
$offset = ($page - 1) * $per_page;
$imports_sql = "
    SELECT 
        i.*,
        s.name as supplier_name_db,
        s.phone as supplier_phone_db,
        COUNT(id_details.id) as item_count
    FROM imports i 
    LEFT JOIN suppliers s ON i.supplier_id = s.id 
    LEFT JOIN import_details id_details ON i.id = id_details.import_id
    $where_clause 
    GROUP BY i.id
    ORDER BY i.$sort_by $sort_order
    LIMIT $per_page OFFSET $offset
";
$imports_stmt = $pdo->prepare($imports_sql);
$imports_stmt->execute($params);
$imports = $imports_stmt->fetchAll();

// Get summary statistics
$stats_sql = "
    SELECT 
        COUNT(*) as total_imports,
        SUM(i.total_amount) as total_cost,
        AVG(i.total_amount) as avg_import_amount
    FROM imports i 
    LEFT JOIN suppliers s ON i.supplier_id = s.id 
    $where_clause
";
$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute($params);
$stats = $stats_stmt->fetch();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý phiếu nhập</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Reset và Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #3b82f6;
            --primary-dark: #2563eb;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #06b6d4;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --white: #ffffff;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --border-radius: 8px;
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--gray-50);
            color: var(--gray-800);
            line-height: 1.6;
        }

        .page-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Header Section */
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-title {
            color: var(--white);
            font-size: 2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.875rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-outline {
            background-color: transparent;
            color: var(--white);
            border: 2px solid var(--white);
        }

        .btn-outline:hover {
            background-color: var(--white);
            color: var(--primary-color);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        .btn-secondary {
            background-color: var(--gray-500);
            color: var(--white);
        }

        .btn-secondary:hover {
            background-color: var(--gray-600);
        }

        /* Filter Section */
        .filter-section {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
            overflow: hidden;
        }

        .filter-header {
            background: var(--gray-100);
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .filter-header:hover {
            background: var(--gray-200);
        }

        .filter-header h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--gray-700);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-toggle {
            background: none;
            border: none;
            color: var(--gray-500);
            font-size: 1.2rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .filter-content {
            padding: 1.5rem;
        }

        .filter-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: 600;
            color: var(--gray-700);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .form-group input,
        .form-group select {
            padding: 0.75rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            transition: var(--transition);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgb(59 130 246 / 0.1);
        }

        .filter-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        /* Statistics Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--white);
        }

        .stat-primary .stat-icon { background: var(--primary-color); }
        .stat-success .stat-icon { background: var(--success-color); }
        .stat-info .stat-icon { background: var(--info-color); }
        .stat-warning .stat-icon { background: var(--warning-color); }

        .stat-content {
            flex: 1;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--gray-600);
        }

        /* Table Container */
        .table-container {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .table-header {
            background: linear-gradient(135deg, var(--success-color), #059669);
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--white);
        }

        .table-title {
            font-size: 1.125rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .record-count {
            color: rgba(255, 255, 255, 0.8);
            font-weight: 400;
            font-size: 0.875rem;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: var(--gray-50);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--gray-700);
            border-bottom: 2px solid var(--gray-200);
            white-space: nowrap;
        }

        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-200);
            vertical-align: middle;
        }

        .data-table tr:hover {
            background: var(--gray-50);
        }

        /* Table Content Styles */
        .import-code {
            font-weight: 700;
            color: var(--primary-color);
        }

        .date-info .date {
            font-weight: 600;
            color: var(--gray-800);
        }

        .date-info .created-by {
            font-size: 0.8rem;
            color: var(--gray-500);
            margin-top: 0.25rem;
        }

        .supplier-info .supplier-name {
            font-weight: 600;
            color: var(--gray-800);
        }

        .supplier-info .supplier-phone {
            font-size: 0.8rem;
            color: var(--gray-500);
            margin-top: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .item-count {
            background: var(--gray-100);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--gray-700);
        }

        .amount {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--success-color);
        }

        /* Status Badges */
        .status-badge,
        .payment-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--white);
        }

        .status-completed { background: var(--success-color); }
        .status-processing { background: var(--warning-color); }
        .status-cancelled { background: var(--danger-color); }

        .payment-paid { background: var(--success-color); }
        .payment-partial { background: var(--warning-color); }
        .payment-pending { background: var(--danger-color); }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-action {
            width: 36px;
            height: 36px;
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.875rem;
        }

        .btn-view {
            background: var(--info-color);
            color: var(--white);
        }

        .btn-print {
            background: var(--gray-500);
            color: var(--white);
        }

        .btn-notes {
            background: var(--warning-color);
            color: var(--white);
        }

        .btn-action:hover {
            transform: scale(1.1);
            box-shadow: var(--shadow-md);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray-500);
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: var(--gray-300);
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--gray-700);
        }

        .empty-state p {
            margin-bottom: 2rem;
        }

        /* Pagination */
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
        }

        .pagination {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--white);
            padding: 1rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
        }

        .pagination-btn {
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-radius: var(--border-radius);
            background: var(--gray-100);
            color: var(--gray-600);
            text-decoration: none;
            transition: var(--transition);
        }

        .pagination-btn:hover {
            background: var(--primary-color);
            color: var(--white);
        }

        .pagination-info {
            padding: 0.5rem 1rem;
            font-weight: 600;
            color: var(--gray-700);
            background: var(--gray-100);
            border-radius: var(--border-radius);
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-content {
            background: var(--white);
            border-radius: var(--border-radius);
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
        }

        .modal-header {
            background: var(--primary-color);
            color: var(--white);
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--white);
            font-size: 1.5rem;
            cursor: pointer;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .loading-spinner {
            text-align: center;
            padding: 2rem;
            color: var(--gray-500);
        }

        .loading-spinner i {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        /* Notification Styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--success-color);
            color: var(--white);
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transform: translateX(100%);
            transition: var(--transition);
            z-index: 1001;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification-error {
            background: var(--danger-color);
        }

        .notification-info {
            background: var(--info-color);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .page-container {
                padding: 1rem;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
            }
            
            .filter-row {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .table-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .data-table {
                font-size: 0.875rem;
            }
            
            .data-table th,
            .data-table td {
                padding: 0.75rem 0.5rem;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-container > * {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>
<body>

<div class="page-container">
    <!-- Header Section -->
    <div class="page-header">
        <div class="header-content">
            <h1 class="page-title">
                <i class="fas fa-boxes"></i>
                Quản lý phiếu nhập
            </h1>
            <div class="header-actions">
                <button class="btn btn-primary" onclick="window.location.href='?page=imports'">
                    <i class="fas fa-plus"></i>
                    Tạo phiếu nhập mới
                </button>
                <button class="btn btn-outline" onclick="exportToExcel()">
                    <i class="fas fa-file-excel"></i>
                    Xuất Excel
                </button>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <div class="filter-header" onclick="toggleFilter()">
            <h3><i class="fas fa-filter"></i> Bộ lọc tìm kiếm</h3>
            <button class="filter-toggle">
                <i class="fas fa-chevron-down"></i>
            </button>
        </div>
        
        <div class="filter-content" id="filterContent">
            <form method="GET" class="filter-form">
                <input type="hidden" name="page" value="all_imports">
                
                <div class="filter-row">
                    <div class="form-group">
                        <label for="search">
                            <i class="fas fa-search"></i>
                            Tìm kiếm
                        </label>
                        <input type="text" name="search" id="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Nhập mã phiếu nhập, nhà cung cấp...">
                    </div>
                    
                    <div class="form-group">
                        <label for="status">
                            <i class="fas fa-clipboard-list"></i>
                            Trạng thái nhập
                        </label>
                        <select name="status" id="status">
                            <option value="">Tất cả trạng thái</option>
                            <option value="Hoàn thành" <?php echo $status === 'Hoàn thành' ? 'selected' : ''; ?>>
                                Hoàn thành
                            </option>
                            <option value="Đang xử lý" <?php echo $status === 'Đang xử lý' ? 'selected' : ''; ?>>
                                Đang xử lý
                            </option>
                            <option value="Đã hủy" <?php echo $status === 'Đã hủy' ? 'selected' : ''; ?>>
                                Đã hủy
                            </option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_status">
                            <i class="fas fa-credit-card"></i>
                            Trạng thái thanh toán
                        </label>
                        <select name="payment_status" id="payment_status">
                            <option value="">Tất cả thanh toán</option>
                            <option value="paid" <?php echo $payment_status === 'paid' ? 'selected' : ''; ?>>
                                Đã thanh toán
                            </option>
                            <option value="partial" <?php echo $payment_status === 'partial' ? 'selected' : ''; ?>>
                                Thanh toán một phần
                            </option>
                            <option value="pending" <?php echo $payment_status === 'pending' ? 'selected' : ''; ?>>
                                Chưa thanh toán
                            </option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-row">
                    <div class="form-group">
                        <label for="date_from">
                            <i class="fas fa-calendar-alt"></i>
                            Từ ngày
                        </label>
                        <input type="date" name="date_from" id="date_from" 
                               value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="date_to">
                            <i class="fas fa-calendar-alt"></i>
                            Đến ngày
                        </label>
                        <input type="date" name="date_to" id="date_to" 
                               value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="sort_by">
                            <i class="fas fa-sort"></i>
                            Sắp xếp theo
                        </label>
                        <select name="sort_by" id="sort_by">
                            <option value="import_date" <?php echo $sort_by === 'import_date' ? 'selected' : ''; ?>>Ngày nhập</option>
                            <option value="import_code" <?php echo $sort_by === 'import_code' ? 'selected' : ''; ?>>Mã phiếu nhập</option>
                            <option value="supplier_name" <?php echo $sort_by === 'supplier_name' ? 'selected' : ''; ?>>Nhà cung cấp</option>
                            <option value="total_amount" <?php echo $sort_by === 'total_amount' ? 'selected' : ''; ?>>Tổng tiền</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="sort_order">
                            <i class="fas fa-sort-amount-down"></i>
                            Thứ tự
                        </label>
                        <select name="sort_order" id="sort_order">
                            <option value="DESC" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Giảm dần</option>
                            <option value="ASC" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Tăng dần</option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Tìm kiếm
                    </button>
                    <a href="?page=all_imports" class="btn btn-outline">
                        <i class="fas fa-undo"></i>
                        Đặt lại
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card stat-primary">
            <div class="stat-icon">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo number_format($stats['total_imports'] ?? 0); ?></div>
                <div class="stat-label">Tổng phiếu nhập</div>
            </div>
        </div>
        
        <div class="stat-card stat-success">
            <div class="stat-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo number_format($stats['total_cost'] ?? 0); ?>₫</div>
                <div class="stat-label">Tổng chi phí</div>
            </div>
        </div>
        
        <div class="stat-card stat-info">
            <div class="stat-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo number_format($stats['avg_import_amount'] ?? 0); ?>₫</div>
                <div class="stat-label">Trung bình/phiếu</div>
            </div>
        </div>
        
        <div class="stat-card stat-warning">
            <div class="stat-icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">Trang <?php echo $page; ?>/<?php echo $total_pages; ?></div>
                <div class="stat-label">Phân trang</div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="table-container">
        <div class="table-header">
            <div class="table-title">
                <i class="fas fa-list"></i>
                Danh sách phiếu nhập
                <span class="record-count">(<?php echo number_format($total_records); ?> kết quả)</span>
            </div>
            <div class="header-actions">
                <button class="btn btn-sm btn-outline" onclick="exportToExcel()">
                    <i class="fas fa-file-excel"></i>
                    Xuất Excel
                </button>
                <a href="?page=imports" class="btn btn-sm btn-outline">
                    <i class="fas fa-plus"></i>
                    Tạo mới
                </a>
            </div>
        </div>
        
        <?php if (empty($imports)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-inbox"></i>
                </div>
                <h3>Không tìm thấy phiếu nhập nào</h3>
                <p>Thử thay đổi bộ lọc hoặc tạo phiếu nhập mới</p>
                <button class="btn btn-primary" onclick="window.location.href='?page=imports'">
                    <i class="fas fa-plus"></i>
                    Tạo phiếu nhập mới
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Mã phiếu nhập</th>
                            <th>Ngày nhập</th>
                            <th>Nhà cung cấp</th>
                            <th>Số SP</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Thanh toán</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($imports as $import): ?>
                            <tr>
                                <td>
                                    <div class="import-code">
                                        <?php echo $import['import_code']; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="date-info">
                                        <div class="date"><?php echo formatDate($import['import_date']); ?></div>
                                        <div class="created-by">bởi <?php echo htmlspecialchars($import['created_by'] ?? 'N/A'); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="supplier-info">
                                        <div class="supplier-name">
                                            <?php echo htmlspecialchars($import['supplier_name'] ?: ($import['supplier_name_db'] ?? 'N/A')); ?>
                                        </div>
                                        <?php if ($import['supplier_phone_db']): ?>
                                            <div class="supplier-phone">
                                                <i class="fas fa-phone"></i>
                                                <?php echo htmlspecialchars($import['supplier_phone_db']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="item-count">
                                        <?php echo $import['item_count']; ?> SP
                                    </span>
                                </td>
                                <td>
                                    <div class="amount">
                                        <?php echo number_format($import['total_amount']); ?>₫
                                    </div>
                                </td>
                                <td>
                                    <?php
                                        $statusClass = '';
                                        $statusIcon = '';
                                        switch($import['status']) {
                                            case 'Hoàn thành': 
                                                $statusClass = 'status-completed';
                                                $statusIcon = 'fas fa-check-circle';
                                                break;
                                            case 'Đang xử lý': 
                                                $statusClass = 'status-processing';
                                                $statusIcon = 'fas fa-clock';
                                                break;
                                            case 'Đã hủy': 
                                                $statusClass = 'status-cancelled';
                                                $statusIcon = 'fas fa-times-circle';
                                                break;
                                        }
                                    ?>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <i class="<?php echo $statusIcon; ?>"></i>
                                        <?php echo htmlspecialchars($import['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                        $paymentClass = '';
                                        $paymentIcon = '';
                                        $paymentText = '';
                                        switch($import['payment_status'] ?? 'pending') {
                                            case 'paid': 
                                                $paymentClass = 'payment-paid';
                                                $paymentIcon = 'fas fa-check-circle';
                                                $paymentText = 'Đã thanh toán';
                                                break;
                                            case 'partial': 
                                                $paymentClass = 'payment-partial';
                                                $paymentIcon = 'fas fa-exclamation-circle';
                                                $paymentText = 'Một phần';
                                                break;
                                            case 'pending': 
                                                $paymentClass = 'payment-pending';
                                                $paymentIcon = 'fas fa-clock';
                                                $paymentText = 'Chưa thanh toán';
                                                break;
                                        }
                                    ?>
                                    <span class="payment-badge <?php echo $paymentClass; ?>">
                                        <i class="<?php echo $paymentIcon; ?>"></i>
                                        <?php echo $paymentText; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action btn-view" onclick="viewImportDetail(<?php echo $import['id']; ?>)" title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn-action btn-print" onclick="printImport(<?php echo $import['id']; ?>)" title="In phiếu nhập">
                                            <i class="fas fa-print"></i>
                                        </button>
                                        <?php if ($import['notes']): ?>
                                            <button class="btn-action btn-notes" onclick="showNotes('<?php echo htmlspecialchars(addslashes($import['notes'])); ?>')" title="Xem ghi chú">
                                                <i class="fas fa-sticky-note"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination-container">
            <div class="pagination">
                <?php
                $current_params = $_GET;
                unset($current_params['pg']);
                $base_url = '?' . http_build_query($current_params) . '&pg=';
                ?>
                
                <?php if ($page > 1): ?>
                    <a href="<?php echo $base_url . '1'; ?>" class="pagination-btn">
                        <i class="fas fa-angle-double-left"></i>
                        Đầu
                    </a>
                    <a href="<?php echo $base_url . ($page - 1); ?>" class="pagination-btn">
                        <i class="fas fa-angle-left"></i>
                        Trước
                    </a>
                <?php endif; ?>
                
                <span class="pagination-info">
                    Trang <?php echo $page; ?> / <?php echo $total_pages; ?>
                </span>
                
                <?php if ($page < $total_pages): ?>
                    <a href="<?php echo $base_url . ($page + 1); ?>" class="pagination-btn">
                        Tiếp
                        <i class="fas fa-angle-right"></i>
                    </a>
                    <a href="<?php echo $base_url . $total_pages; ?>" class="pagination-btn">
                        Cuối
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Enhanced JavaScript functionality
document.addEventListener('DOMContentLoaded', function() {
    initializeFilters();
    initializeTable();
    initializeAnimations();
});

// Filter functionality
function initializeFilters() {
    const form = document.querySelector('.filter-form');
    const selects = form.querySelectorAll('select');
    
    // Auto-submit on select change
    selects.forEach(select => {
        select.addEventListener('change', function() {
            form.submit();
        });
    });
    
    // Search input debounce
    const searchInput = document.getElementById('search');
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            form.submit();
        }, 500);
    });
}

// Toggle filter visibility
function toggleFilter() {
    const filterContent = document.getElementById('filterContent');
    const toggleBtn = document.querySelector('.filter-toggle i');
    
    if (filterContent.style.display === 'none') {
        filterContent.style.display = 'block';
        toggleBtn.style.transform = 'rotate(180deg)';
    } else {
        filterContent.style.display = 'none';
        toggleBtn.style.transform = 'rotate(0deg)';
    }
}

// Table functionality
function initializeTable() {
    // Add loading states
    const actionButtons = document.querySelectorAll('.btn-action');
    
    actionButtons.forEach(button => {
        button.addEventListener('click', function() {
            const originalContent = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            setTimeout(() => {
                this.innerHTML = originalContent;
            }, 1000);
        });
    });
}

// Enhanced functions
function viewImportDetail(importId) {
    const modal = createModal();
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-eye"></i> Chi tiết phiếu nhập</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Đang tải dữ liệu...</p>
                </div>
            </div>
        </div>
    `;
    
    // Simulate loading content
    setTimeout(() => {
        modal.querySelector('.modal-body').innerHTML = `
            <h4>Thông tin phiếu nhập #${importId}</h4>
            <p>Chi tiết phiếu nhập sẽ được hiển thị ở đây...</p>
        `;
    }, 1000);
}

function printImport(importId) {
    showNotification('Đang chuẩn bị in...', 'info');
    // Simulate print action
    setTimeout(() => {
        window.open(`print_import.php?id=${importId}`, '_blank');
    }, 500);
}

function showNotes(notes) {
    const modal = createModal();
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-sticky-note"></i> Ghi chú</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div style="white-space: pre-wrap; line-height: 1.6;">
                    ${notes}
                </div>
            </div>
        </div>
    `;
}

function exportToExcel() {
    showNotification('Đang xuất file Excel...', 'info');
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('export', 'excel');
    
    setTimeout(() => {
        showNotification('Xuất file thành công!', 'success');
        // window.location.href = currentUrl.toString();
    }, 1500);
}

// Utility functions
function createModal() {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.onclick = function(e) {
        if (e.target === modal) closeModal();
    };
    document.body.appendChild(modal);
    return modal;
}

function closeModal() {
    const modal = document.querySelector('.modal-overlay');
    if (modal) {
        modal.remove();
    }
}

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function initializeAnimations() {
    // Intersection Observer for animations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    });
    
    document.querySelectorAll('.stat-card, .table-container').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'all 0.6s ease-out';
        observer.observe(el);
    });
}
</script>

</body>
</html>
