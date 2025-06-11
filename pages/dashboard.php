<?php
require_once __DIR__ . '/../includes/functions.php';
/**
 * Dashboard - Trang tổng quan hệ thống quản lý bán hàng
 */

// Lấy thống kê tổng quan
try {
    // Debug: Kiểm tra kết nối database
    if (!$pdo) {
        throw new Exception("Không thể kết nối database");
    }
    
    // Thống kê sản phẩm
    $stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products WHERE is_active = 1");
    $total_products = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as low_stock FROM products WHERE stock_quantity <= 5 AND is_active = 1");
    $low_stock_products = $stmt->fetch(PDO::FETCH_ASSOC)['low_stock'];
    
    // Thống kê bán hàng hôm nay
    $stmt = $pdo->prepare("SELECT COUNT(*) as today_sales, COALESCE(SUM(final_amount), 0) as today_revenue FROM sales WHERE DATE(sale_date) = CURDATE()");
    $stmt->execute();
    $today_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Thống kê tháng này
    $stmt = $pdo->prepare("SELECT COUNT(*) as month_sales, COALESCE(SUM(final_amount), 0) as month_revenue FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())");
    $stmt->execute();
    $month_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Thống kê khách hàng
    $stmt = $pdo->query("SELECT COUNT(*) as total_customers FROM customers WHERE is_active = 1");
    $total_customers = $stmt->fetch(PDO::FETCH_ASSOC)['total_customers'];
    
    // Thống kê nhà cung cấp
    $stmt = $pdo->query("SELECT COUNT(*) as total_suppliers FROM suppliers WHERE is_active = 1");
    $total_suppliers = $stmt->fetch(PDO::FETCH_ASSOC)['total_suppliers'];
    
    // Doanh thu 7 ngày gần đây
    // Fetches data for the last 6 days PLUS today (total 7 days)
    // Example: If CURDATE() is 2025-06-12, this covers June 6th to June 12th.
    $stmt = $pdo->query("
        SELECT DATE(sale_date) as date, COALESCE(SUM(final_amount), 0) as revenue
        FROM sales
        WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND sale_date < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
        GROUP BY DATE(sale_date)
        ORDER BY date ASC
    ");
    $raw_weekly_revenue_from_db = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Dashboard - Raw Weekly Revenue from DB: " . json_encode($raw_weekly_revenue_from_db));

    // Prepare a complete 7-day array, filling missing days with 0 revenue
    $final_weekly_revenue_for_js = [];
    for ($i = 6; $i >= 0; $i--) {
        $current_date_key = date('Y-m-d', strtotime("-$i days"));
        $revenue_for_this_date = 0.00; // Default to 0

        foreach ($raw_weekly_revenue_from_db as $db_row) {
            if ($db_row['date'] === $current_date_key) {
                $revenue_for_this_date = (float)$db_row['revenue'];
                break;
            }
        }
        $final_weekly_revenue_for_js[] = [
            'date' => $current_date_key,
            'revenue' => $revenue_for_this_date
        ];
    }
    $weekly_revenue = $final_weekly_revenue_for_js; // This is passed to JavaScript
    error_log("Dashboard - Processed Weekly Revenue for JS: " . json_encode($weekly_revenue));
    
    // Top sản phẩm bán chạy (30 ngày)
    $stmt = $pdo->query("
        SELECT p.name as product_name, p.product_code, SUM(sd.quantity) as total_sold, 
               SUM(sd.quantity * sd.unit_price) as revenue, p.stock_quantity
        FROM sale_details sd
        JOIN products p ON sd.product_id = p.id
        JOIN sales s ON sd.sale_id = s.id
        WHERE s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY p.id, p.name, p.product_code
        ORDER BY total_sold DESC
        LIMIT 5
    ");
    $top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Hóa đơn gần đây
    $stmt = $pdo->query("
        SELECT s.id, s.sale_code, s.sale_date, s.customer_name, c.name as customer_name_db, 
               s.final_amount, s.payment_method, s.payment_status
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id 
        ORDER BY s.sale_date DESC
        LIMIT 8
    ");
    $recent_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Phiếu nhập gần đây
    $stmt = $pdo->query("
        SELECT i.id, i.import_code, i.import_date, i.supplier_name, s.name as supplier_name_db, 
               i.total_amount, i.status
        FROM imports i
        LEFT JOIN suppliers s ON i.supplier_id = s.id 
        ORDER BY i.import_date DESC
        LIMIT 5
    ");
    $recent_imports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Sản phẩm sắp hết hàng
    $stmt = $pdo->query("
        SELECT name, product_code, stock_quantity, min_stock_level
        FROM products 
        WHERE stock_quantity <= COALESCE(min_stock_level, 5) AND is_active = 1
        ORDER BY stock_quantity ASC
        LIMIT 5
    ");
    $low_stock_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    // Debug: Thêm echo để debug trong development
    echo "<!-- Debug: Dashboard Error: " . htmlspecialchars($e->getMessage()) . " -->";
    
    $total_products = $low_stock_products = $total_customers = $total_suppliers = 0;
    $today_stats = ['today_sales' => 0, 'today_revenue' => 0];
    $month_stats = ['month_sales' => 0, 'month_revenue' => 0];
    $top_products = $recent_sales = $recent_imports = $low_stock_items = [];

    // Ensure $weekly_revenue is an array with 7 zero-revenue days on error
    $weekly_revenue = [];
    for ($i = 6; $i >= 0; $i--) {
        $weekly_revenue[] = [
            'date' => date('Y-m-d', strtotime("-$i days")),
            'revenue' => 0.00
        ];
    }
}

// Debug: Log dữ liệu để kiểm tra
error_log("Dashboard Debug - Total products: " . $total_products);
error_log("Dashboard Debug - Today sales: " . $today_stats['today_sales']);
error_log("Dashboard Debug - Recent sales count: " . count($recent_sales));
error_log("Dashboard Debug - Recent imports count: " . count($recent_imports));
error_log("Dashboard Debug - Final Weekly revenue for JS: " . json_encode($weekly_revenue)); // Added this log

// Debug output cho development
echo "<!-- 
Dashboard Debug Info:
- Total products: " . $total_products . "
- Today sales: " . $today_stats['today_sales'] . " 
- Today revenue: " . $today_stats['today_revenue'] . "
- Recent sales: " . count($recent_sales) . "
- Recent imports: " . count($recent_imports) . "
- Weekly revenue data (PHP processed): " . htmlspecialchars(json_encode($weekly_revenue)) . "
-->"; // Updated this debug output
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Tổng quan hệ thống</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            --purple-color: #8b5cf6;
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
            --border-radius: 12px;
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--gray-800);
            line-height: 1.6;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Header Section */
        .page-header {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header-left h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-800);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .header-left .subtitle {
            color: var(--gray-600);
            font-size: 1rem;
        }

        .shortcut-hints {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .shortcut-badge {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: var(--white);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            white-space: nowrap;
            transition: var(--transition);
        }

        .shortcut-badge:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-lg);
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
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--success-color));
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
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

        .stat-revenue .stat-icon { background: linear-gradient(135deg, var(--success-color), #059669); }
        .stat-sales .stat-icon { background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); }
        .stat-products .stat-icon { background: linear-gradient(135deg, var(--purple-color), #7c3aed); }
        .stat-customers .stat-icon { background: linear-gradient(135deg, var(--info-color), #0891b2); }

        .stat-content h3 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 0.5rem;
        }

        .stat-content p {
            color: var(--gray-600);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .stat-content small {
            font-size: 0.875rem;
            color: var(--gray-500);
        }

        .stat-content small.warning {
            color: var(--danger-color);
            font-weight: 600;
        }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .stat-trend.positive {
            color: var(--success-color);
        }

        .stat-trend.negative {
            color: var(--danger-color);
        }

        /* Quick Actions */
        .quick-actions {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-lg);
            margin-bottom: 2rem;
        }

        .quick-actions h3 {
            color: var(--gray-800);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: var(--white);
            text-decoration: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            transition: var(--transition);
            border: none;
            cursor: pointer;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .action-btn.success {
            background: linear-gradient(135deg, var(--success-color), #059669);
        }

        .action-btn.warning {
            background: linear-gradient(135deg, var(--warning-color), #d97706);
        }

        .action-btn.info {
            background: linear-gradient(135deg, var(--info-color), #0891b2);
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr; /* Default for chart/top products */
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .recent-transactions-grid { /* New rule for equal columns */
            grid-template-columns: 1fr 1fr;
        }

        .dashboard-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .dashboard-card.full-width {
            grid-column: 1 / -1;
        }

        .card-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, var(--gray-100), var(--gray-200));
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header h4 {
            margin: 0;
            color: var(--gray-800);
            font-size: 1.125rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 300px;
        }

        /* Top Products */
        .top-products-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .top-product-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--gray-50);
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .top-product-item:hover {
            background: var(--gray-100);
            transform: translateX(4px);
        }

        .product-rank {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.125rem;
        }

        .product-info {
            flex: 1;
        }

        .product-name {
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 0.25rem;
        }

        .product-code {
            font-size: 0.875rem;
            color: var(--gray-500);
            font-family: 'Courier New', monospace;
        }

        .product-stats {
            display: flex;
            gap: 1rem;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        .product-stats .sold {
            color: var(--gray-600);
            font-weight: 600;
        }

        .product-stats .revenue {
            color: var(--success-color);
            font-weight: 600;
        }

        /* Tables */
        .table-responsive {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: var(--gray-50);
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            color: var(--gray-700);
            border-bottom: 2px solid var(--gray-200);
            white-space: nowrap;
        }

        .data-table td {
            padding: 0.75rem;
            border-bottom: 1px solid var(--gray-200);
            vertical-align: middle;
        }

        .data-table tr:hover {
            background: var(--gray-50);
        }

        .invoice-code {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: var(--primary-color);
        }

        .amount {
            font-weight: 600;
            color: var(--success-color);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--white);
        }

        .status-completed { background: var(--success-color); }
        .status-processing { background: var(--warning-color); }
        .status-cancelled { background: var(--danger-color); }

        .payment-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .payment-cash { background: var(--success-color); color: var(--white); }
        .payment-card { background: var(--info-color); color: var(--white); }
        .payment-transfer { background: var(--purple-color); color: var(--white); }

        /* Action Buttons */
        .btn-action {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.875rem;
            margin: 0 0.25rem;
        }

        .btn-view {
            background: var(--info-color);
            color: var(--white);
        }

        .btn-print {
            background: var(--gray-500);
            color: var(--white);
        }

        .btn-action:hover {
            transform: scale(1.1);
            box-shadow: var(--shadow-md);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--gray-500);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--gray-300);
        }

        .empty-state h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--gray-700);
        }

        /* Low Stock Alert */
        .low-stock-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: var(--gray-50);
            border-radius: var(--border-radius);
            margin-bottom: 0.5rem;
            border-left: 4px solid var(--danger-color);
        }

        .low-stock-item:last-child {
            margin-bottom: 0;
        }

        .stock-info .product-name {
            font-weight: 600;
            color: var(--gray-800);
        }

        .stock-info .product-code {
            font-size: 0.875rem;
            color: var(--gray-500);
        }

        .stock-level {
            font-weight: 700;
            color: var(--danger-color);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 1rem;
            }
            
            .page-header {
                flex-direction: column;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
            }
            
            .shortcut-hints {
                justify-content: center;
            }
        }

        /* Animation */
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

        .dashboard-container > * {
            animation: fadeInUp 0.6s ease-out;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <!-- Header Section -->
    <div class="page-header">
        <div class="header-left">
            <h1>
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </h1>
            <p class="subtitle">Tổng quan hệ thống quản lý bán hàng</p>
        </div>
        <div class="shortcut-hints">
            <span class="shortcut-badge" onclick="location.href='?page=products'">
                <i class="fas fa-box"></i> F1: Sản phẩm
            </span>
            <span class="shortcut-badge" onclick="location.href='?page=sales'">
                <i class="fas fa-cash-register"></i> F2: Bán hàng
            </span>
            <span class="shortcut-badge" onclick="location.href='?page=imports'">
                <i class="fas fa-truck"></i> F3: Nhập hàng
            </span>
            <span class="shortcut-badge" onclick="location.href='?page=customers'">
                <i class="fas fa-users"></i> F4: Khách hàng
            </span>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card stat-revenue">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-trend positive">
                    <i class="fas fa-arrow-up"></i>
                    <span>+12%</span>
                </div>
            </div>
            <div class="stat-content">
                <h3><?= number_format($today_stats['today_revenue']) ?>₫</h3>
                <p>Doanh thu hôm nay</p>
                <small><?= $today_stats['today_sales'] ?> hóa đơn • Tháng này: <?= number_format($month_stats['month_revenue']) ?>₫</small>
            </div>
        </div>

        <div class="stat-card stat-sales">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="stat-trend positive">
                    <i class="fas fa-arrow-up"></i>
                    <span>+8%</span>
                </div>
            </div>
            <div class="stat-content">
                <h3><?= $today_stats['today_sales'] ?></h3>
                <p>Hóa đơn hôm nay</p>
                <small>Tháng này: <?= $month_stats['month_sales'] ?> hóa đơn</small>
            </div>
        </div>

        <div class="stat-card stat-products">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-box"></i>
                </div>
            </div>
            <div class="stat-content">
                <h3><?= $total_products ?></h3>
                <p>Tổng sản phẩm</p>
                <?php if ($low_stock_products > 0): ?>
                    <small class="warning">⚠️ <?= $low_stock_products ?> sản phẩm sắp hết hàng</small>
                <?php else: ?>
                    <small>✅ Tồn kho ổn định</small>
                <?php endif; ?>
            </div>
        </div>

        <div class="stat-card stat-customers">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div class="stat-content">
                <h3><?= $total_customers ?></h3>
                <p>Khách hàng</p>
                <small><?= $total_suppliers ?> nhà cung cấp</small>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h3><i class="fas fa-bolt"></i> Thao tác nhanh</h3>
        <div class="action-buttons">
            <button class="action-btn success" onclick="location.href='?page=sales'">
                <i class="fas fa-cash-register"></i>
                Tạo hóa đơn mới
            </button>
            <button class="action-btn info" onclick="location.href='?page=imports'">
                <i class="fas fa-truck"></i>
                Nhập hàng
            </button>
            <button class="action-btn" onclick="location.href='?page=products'">
                <i class="fas fa-plus"></i>
                Thêm sản phẩm
            </button>
            <button class="action-btn warning" onclick="location.href='?page=customers'">
                <i class="fas fa-user-plus"></i>
                Thêm khách hàng
            </button>
        </div>
    </div>

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">
        <!-- Revenue Chart -->
        <div class="dashboard-card">
            <div class="card-header">
                <h4><i class="fas fa-chart-line"></i> Doanh thu 7 ngày gần đây</h4>
                <button class="btn-action btn-view" onclick="refreshChart()" title="Làm mới">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Products -->
        <div class="dashboard-card">
            <div class="card-header">
                <h4><i class="fas fa-star"></i> Top sản phẩm (30 ngày)</h4>
            </div>
            <div class="card-body">
                <?php if (empty($top_products)): ?>
                    <div class="empty-state">
                        <i class="fas fa-chart-bar"></i>
                        <h3>Chưa có dữ liệu</h3>
                        <p>Chưa có sản phẩm nào được bán</p>
                    </div>
                <?php else: ?>
                    <div class="top-products-list">
                        <?php foreach ($top_products as $index => $product): ?>
                            <div class="top-product-item">
                                <div class="product-rank"><?= $index + 1 ?></div>
                                <div class="product-info">
                                    <div class="product-name"><?= htmlspecialchars($product['product_name']) ?></div>
                                    <div class="product-code"><?= htmlspecialchars($product['product_code']) ?></div>
                                    <div class="product-stats">
                                        <span class="sold">📦 <?= $product['total_sold'] ?> đã bán</span>
                                        <span class="revenue">💰 <?= number_format($product['revenue']) ?>₫</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Low Stock Alert -->
    <?php if (!empty($low_stock_items)): ?>
    <div class="dashboard-card full-width">
        <div class="card-header">
            <h4><i class="fas fa-exclamation-triangle"></i> Cảnh báo tồn kho thấp</h4>
            <a href="?page=products&filter=low_stock" class="btn-action btn-view" title="Xem tất cả">
                <i class="fas fa-external-link-alt"></i>
            </a>
        </div>
        <div class="card-body">
            <?php foreach ($low_stock_items as $item): ?>
                <div class="low-stock-item">
                    <div class="stock-info">
                        <div class="product-name"><?= htmlspecialchars($item['name']) ?></div>
                        <div class="product-code"><?= htmlspecialchars($item['product_code']) ?></div>
                    </div>
                    <div class="stock-level">
                        Còn <?= $item['stock_quantity'] ?> sản phẩm
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Transactions -->
    <div class="dashboard-grid recent-transactions-grid">
        <!-- Recent Sales -->
        <div class="dashboard-card">
            <div class="card-header">
                <h4><i class="fas fa-receipt"></i> Hóa đơn gần đây</h4>
                <a href="?page=all_sales" class="btn-action btn-view" title="Xem tất cả">
                    <i class="fas fa-external-link-alt"></i>
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_sales)): ?>
                    <div class="empty-state">
                        <i class="fas fa-receipt"></i>
                        <h3>Chưa có hóa đơn</h3>
                        <button class="action-btn success" onclick="location.href='?page=sales'">
                            Tạo hóa đơn đầu tiên
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Mã HĐ</th>
                                    <th>Khách hàng</th>
                                    <th>Tổng tiền</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($recent_sales, 0, 5) as $sale): ?>
                                    <tr>
                                        <td>
                                            <div class="invoice-code"><?= htmlspecialchars($sale['sale_code']) ?></div>
                                            <small><?= date('d/m H:i', strtotime($sale['sale_date'])) ?></small>
                                        </td>
                                        <td style="max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?= htmlspecialchars($sale['customer_name'] ?: ($sale['customer_name_db'] ?: 'Khách lẻ')) ?>
                                        </td>
                                        <td>
                                            <div class="amount"><?= number_format($sale['final_amount']) ?>₫</div>
                                        </td>
                                        <td>
                                            <button class="btn-action btn-view" onclick="viewSaleDetail(<?= $sale['id'] ?>)" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-action btn-print" onclick="printInvoice(<?= $sale['id'] ?>)" title="In hóa đơn">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Imports -->
        <div class="dashboard-card">
            <div class="card-header">
                <h4><i class="fas fa-truck"></i> Phiếu nhập gần đây</h4>
                <a href="?page=all_imports" class="btn-action btn-view" title="Xem tất cả">
                    <i class="fas fa-external-link-alt"></i>
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_imports)): ?>
                    <div class="empty-state">
                        <i class="fas fa-truck"></i>
                        <h3>Chưa có phiếu nhập</h3>
                        <button class="action-btn info" onclick="location.href='?page=imports'">
                            Tạo phiếu nhập đầu tiên
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Mã PN</th>
                                    <th>Nhà cung cấp</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_imports as $import): ?>
                                    <tr>
                                        <td>
                                            <div class="invoice-code"><?= htmlspecialchars($import['import_code']) ?></div>
                                            <small><?= date('d/m H:i', strtotime($import['import_date'])) ?></small>
                                        </td>
                                        <td style="word-break: break-word; min-width: 150px;">
                                            <?= htmlspecialchars($import['supplier_name'] ?: ($import['supplier_name_db'] ?: 'N/A')) ?>
                                        </td>
                                        <td>
                                            <div class="amount"><?= number_format($import['total_amount']) ?>₫</div>
                                        </td>
                                        <td>
                                            <?php
                                                $statusClass = '';
                                                $statusText = $import['status'] ?: 'Đang xử lý';
                                                switch($statusText) {
                                                    case 'Hoàn thành': $statusClass = 'status-completed'; break;
                                                    case 'Đang xử lý': $statusClass = 'status-processing'; break;
                                                    case 'Đã hủy': $statusClass = 'status-cancelled'; break;
                                                    default: $statusClass = 'status-processing'; break;
                                                }
                                            ?>
                                            <span class="status-badge <?= $statusClass ?>">
                                                <?= htmlspecialchars($statusText) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Revenue Chart
const revenueDataFromPHP = <?= json_encode($weekly_revenue) ?>; // Renamed for clarity
console.log('Revenue Data from PHP (processed):', revenueDataFromPHP); // Log the data received by JS

const labels = [];
const data = [];

// Tạo labels và data cho 7 ngày gần đây
for (let i = 6; i >= 0; i--) {
    const dateLoop = new Date(); // Current date/time in client's timezone
    dateLoop.setDate(dateLoop.getDate() - i); // Go back i days

    // Format dateLoop to YYYY-MM-DD in client's local timezone
    const yyyy = dateLoop.getFullYear();
    const mm = String(dateLoop.getMonth() + 1).padStart(2, '0'); // Month is 0-indexed
    const dd = String(dateLoop.getDate()).padStart(2, '0');
    const localDateStr_JS = `${yyyy}-${mm}-${dd}`; // Date string in client's local timezone

    const dayStr_JS_Label = dateLoop.toLocaleDateString('vi-VN', { weekday: 'short', day: 'numeric', month: 'numeric' });
    
    labels.push(dayStr_JS_Label);
    
    // Match using the localDateStr_JS
    const foundDataRow = revenueDataFromPHP.find(item => item.date === localDateStr_JS);
    const revenueForDay = foundDataRow ? parseFloat(foundDataRow.revenue) : 0;
    data.push(revenueForDay);

    // DETAILED CONSOLE LOG FOR DEBUGGING:
    console.log(
        `JS Loop (Day ${6-i+1}/7): ` +
        `Targeting Date (JS Local): ${localDateStr_JS} | ` +
        `Label: ${dayStr_JS_Label} | ` +
        `Found in PHP data: ${foundDataRow ? `Yes, Revenue: ${foundDataRow.revenue}` : 'No Match'} | ` +
        `Pushed to Chart Data: ${revenueForDay}`
    );
}

console.log('Final Chart Labels:', labels);
console.log('Final Chart Data:', data);

const ctx = document.getElementById('revenueChart').getContext('2d');
const maxValue = Math.max(...data);
const hasData = maxValue > 0;

console.log('Max value for chart:', maxValue, '| Has data:', hasData); // Debug max value

const revenueChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Doanh thu (VNĐ)',
            data: data,
            borderColor: hasData ? '#10b981' : '#94a3b8',
            backgroundColor: hasData ? 'rgba(16, 185, 129, 0.1)' : 'rgba(148, 163, 184, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: hasData ? '#10b981' : '#94a3b8',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Doanh thu: ' + new Intl.NumberFormat('vi-VN').format(context.raw) + 'đ';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: '#f3f4f6'
                },
                ticks: {
                    callback: function(value) {
                        if (!hasData && value === 0) return '0đ'; 
                        if (value === 0 && data.every(val => val === 0)) return '0đ'; 
                        return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
                    },
                    color: '#6b7280',
                    max: hasData ? undefined : 1000000 
                }
            },
            x: {
                grid: {
                    color: '#f3f4f6'
                },
                ticks: {
                    color: '#6b7280'
                }
            }
        },
        animation: {
            duration: 1000,
            easing: 'easeInOutQuart'
        }
    }
});

// Functions
function viewSaleDetail(saleId) {
    showToast('Đang tải chi tiết hóa đơn...', 'info');
    // Implement modal or redirect
    window.open(`?page=sale_detail&id=${saleId}`, '_blank');
}

function printInvoice(saleId) {
    showToast('Đang chuẩn bị in hóa đơn...', 'info');
    window.open(`print_invoice.php?id=${saleId}`, '_blank');
}

function refreshChart() {
    showToast('Đang làm mới dữ liệu...', 'info');
    setTimeout(() => {
        location.reload();
    }, 1000);
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#06b6d4'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        font-weight: 600;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.transform = 'translateX(0)';
    }, 100);
    
    setTimeout(() => {
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(event) {
    if (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA') {
        return;
    }
    
    switch(event.key) {
        case 'F1':
            event.preventDefault();
            location.href = '?page=products';
            break;
        case 'F2':
            event.preventDefault();
            location.href = '?page=sales';
            break;
        case 'F3':
            event.preventDefault();
            location.href = '?page=imports';
            break;
        case 'F4':
            event.preventDefault();
            location.href = '?page=customers';
            break;
        case 'F5':
            event.preventDefault();
            location.reload();
            break;
    }
});

// Auto refresh every 5 minutes
setTimeout(() => {
    location.reload();
}, 5 * 60 * 1000);

console.log('📊 Dashboard loaded successfully!');
console.log('💡 Keyboard shortcuts: F1-Products, F2-Sales, F3-Imports, F4-Customers, F5-Refresh');
</script>

</body>
</html>
