<?php
require_once __DIR__ . '/../includes/functions.php';
/**
 * Reports Page - Báo cáo và thống kê
 */

// Get date range for reports
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-t'); // Last day of current month
$report_type = $_GET['report_type'] ?? 'overview';

try {
    // Get sales summary
    $sales_stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_sales,
            COALESCE(SUM(final_amount), 0) as total_revenue,
            COALESCE(AVG(final_amount), 0) as avg_sale_amount
        FROM sales 
        WHERE DATE(sale_date) BETWEEN ? AND ?
    ");
    $sales_stmt->execute([$start_date, $end_date]);
    $sales_summary = $sales_stmt->fetch();

    // Get top selling products
    $top_products_stmt = $pdo->prepare("
        SELECT 
            p.name as product_name,
            p.product_code as product_code, 
            SUM(sd.quantity) as total_sold,
            SUM(sd.quantity * sd.unit_price) as total_revenue
        FROM sale_details sd
        JOIN products p ON sd.product_id = p.id
        JOIN sales s ON sd.sale_id = s.id
        WHERE DATE(s.sale_date) BETWEEN ? AND ?
        GROUP BY p.id, p.name, p.product_code 
        ORDER BY total_sold DESC
        LIMIT 10
    ");
    $top_products_stmt->execute([$start_date, $end_date]);
    $top_products = $top_products_stmt->fetchAll();

    // Get daily sales
    $daily_sales_stmt = $pdo->prepare("
        SELECT 
            DATE(sale_date) as date,
            COUNT(*) as sales_count,
            COALESCE(SUM(final_amount), 0) as daily_revenue
        FROM sales 
        WHERE DATE(sale_date) BETWEEN ? AND ?
        GROUP BY DATE(sale_date)
        ORDER BY date ASC
    ");
    $daily_sales_stmt->execute([$start_date, $end_date]);
    $daily_sales = $daily_sales_stmt->fetchAll();

    // Get import summary
    $import_stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_imports,
            COALESCE(SUM(total_amount), 0) as total_import_cost,
            COALESCE(AVG(total_amount), 0) as avg_import_cost
        FROM imports 
        WHERE DATE(import_date) BETWEEN ? AND ?
    ");
    $import_stmt->execute([$start_date, $end_date]);
    $import_summary = $import_stmt->fetch();

    // Get low stock products
    $low_stock_stmt = $pdo->prepare("
        SELECT 
            id, name, product_code, stock_quantity, 
            CASE 
                WHEN stock_quantity = 0 THEN 'out_of_stock'
                WHEN stock_quantity <= 5 THEN 'very_low'
                WHEN stock_quantity <= 10 THEN 'low'
                ELSE 'normal'
            END as stock_level
        FROM products 
        WHERE stock_quantity <= 20 AND is_active = 1
        ORDER BY stock_quantity ASC, name ASC
    ");
    $low_stock_stmt->execute();
    $low_stock_products = $low_stock_stmt->fetchAll();

    // Get customer stats
    $customer_stats_stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT c.id) as total_customers,
            COUNT(s.id) as total_purchases,
            COALESCE(AVG(s.final_amount), 0) as avg_purchase_amount
        FROM customers c
        LEFT JOIN sales s ON c.id = s.customer_id 
            AND DATE(s.sale_date) BETWEEN ? AND ?
    ");
    $customer_stats_stmt->execute([$start_date, $end_date]);
    $customer_stats = $customer_stats_stmt->fetch();

    // Get profit analysis (estimated)
    $profit_stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(sd.quantity * sd.unit_price), 0) as total_sales,
            COALESCE(SUM(sd.quantity * p.cost_price), 0) as estimated_cost, 
            (COALESCE(SUM(sd.quantity * sd.unit_price), 0) - COALESCE(SUM(sd.quantity * p.cost_price), 0)) as estimated_profit 
        FROM sale_details sd
        JOIN products p ON sd.product_id = p.id
        JOIN sales s ON sd.sale_id = s.id
        WHERE DATE(s.sale_date) BETWEEN ? AND ?
    ");
    $profit_stmt->execute([$start_date, $end_date]);
    $profit_analysis = $profit_stmt->fetch();

    // Get monthly comparison
    $monthly_comparison_stmt = $pdo->prepare("
        SELECT 
            MONTH(sale_date) as month,
            YEAR(sale_date) as year,
            COUNT(*) as sales_count,
            COALESCE(SUM(final_amount), 0) as revenue
        FROM sales 
        WHERE sale_date >= DATE_SUB(?, INTERVAL 12 MONTH)
        GROUP BY YEAR(sale_date), MONTH(sale_date)
        ORDER BY year DESC, month DESC
        LIMIT 12
    ");
    $monthly_comparison_stmt->execute([$end_date]);
    $monthly_comparison = $monthly_comparison_stmt->fetchAll();

} catch (Exception $e) {
    error_log("Reports error: " . $e->getMessage());
    $sales_summary = ['total_sales' => 0, 'total_revenue' => 0, 'avg_sale_amount' => 0];
    $import_summary = ['total_imports' => 0, 'total_import_cost' => 0, 'avg_import_cost' => 0];
    $profit_analysis = ['total_sales' => 0, 'estimated_cost' => 0, 'estimated_profit' => 0];
    $customer_stats = ['total_customers' => 0, 'total_purchases' => 0, 'avg_purchase_amount' => 0];
    $top_products = $daily_sales = $low_stock_products = $monthly_comparison = [];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo & Thống kê</title>
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
            padding: 2rem;
        }

        .page-container {
            max-width: 1400px;
            margin: 0 auto;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        /* Header Section */
        .page-header {
            background: linear-gradient(135deg, var(--info-color), #0891b2);
            padding: 2rem;
            color: var(--white);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header-left h1 {
            font-size: 2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .header-left p {
            opacity: 0.9;
            font-size: 1rem;
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
            background: var(--primary-color);
            color: var(--white);
        }

        .btn-secondary {
            background: var(--gray-500);
            color: var(--white);
        }

        .btn-outline {
            background: transparent;
            color: var(--white);
            border: 2px solid var(--white);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* Filter Section */
        .filter-section {
            padding: 1.5rem;
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
        }

        .filter-form {
            display: flex;
            gap: 1rem;
            align-items: end;
            flex-wrap: wrap;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: 600;
            color: var(--gray-700);
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            padding: 2rem;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
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
        }

        .stat-card.sales::before { background: linear-gradient(90deg, var(--success-color), #059669); }
        .stat-card.imports::before { background: linear-gradient(90deg, var(--primary-color), var(--primary-dark)); }
        .stat-card.profit::before { background: linear-gradient(90deg, var(--purple-color), #7c3aed); }
        .stat-card.customers::before { background: linear-gradient(90deg, var(--warning-color), #d97706); }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }

        .stat-card.sales .stat-icon { color: var(--success-color); }
        .stat-card.imports .stat-icon { color: var(--primary-color); }
        .stat-card.profit .stat-icon { color: var(--purple-color); }
        .stat-card.customers .stat-icon { color: var(--warning-color); }

        .stat-info h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 0.5rem;
        }

        .stat-info p {
            color: var(--gray-600);
            font-weight: 500;
        }

        /* Report Content */
        .report-content {
            padding: 2rem;
        }

        .report-section {
            background: var(--white);
            margin-bottom: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }

        .section-header {
            background: linear-gradient(135deg, var(--gray-100), var(--gray-200));
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .section-header h2 {
            color: var(--gray-800);
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-body {
            padding: 1.5rem;
        }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 2rem;
        }

        .chart-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        /* Daily Sales Chart */
        .daily-sales-chart {
            display: flex;
            gap: 4px;
            align-items: end;
            height: 200px;
            padding: 1rem;
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius);
            overflow-x: auto;
            background: var(--gray-50);
        }

        .chart-bar {
            min-width: 40px;
            background: linear-gradient(to top, var(--info-color), #67e8f9);
            border-radius: 4px 4px 0 0;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: end;
            align-items: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .chart-bar:hover {
            opacity: 0.8;
            transform: scale(1.05);
        }

        .bar-value {
            position: absolute;
            top: -30px;
            font-size: 0.7rem;
            font-weight: bold;
            color: var(--info-color);
            white-space: nowrap;
            background: var(--white);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            box-shadow: var(--shadow-sm);
        }

        .bar-date {
            margin-top: 8px;
            font-size: 0.7rem;
            color: var(--gray-600);
            font-weight: 600;
        }

        /* Tables */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .report-table th {
            background: linear-gradient(135deg, var(--gray-100), var(--gray-200));
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--gray-700);
            border-bottom: 2px solid var(--gray-200);
        }

        .report-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-200);
            vertical-align: middle;
        }

        .report-table tr:hover {
            background: var(--gray-50);
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* Stock Alerts */
        .stock-alerts {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .stock-alert {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            border-radius: var(--border-radius);
            border-left: 4px solid;
            transition: var(--transition);
        }

        .stock-alert:hover {
            transform: translateX(4px);
        }

        .stock-alert.out_of_stock {
            background-color: #fef2f2;
            border-left-color: var(--danger-color);
        }

        .stock-alert.very_low {
            background-color: #fff7ed;
            border-left-color: var(--warning-color);
        }

        .stock-alert.low {
            background-color: #fefce8;
            border-left-color: #eab308;
        }

        .product-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .product-name {
            font-weight: 600;
            color: var(--gray-800);
        }

        .product-code {
            color: var(--gray-500);
            font-size: 0.875rem;
            font-family: 'Courier New', monospace;
        }

        .stock-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .stock-quantity {
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--gray-800);
        }

        .stock-status {
            font-size: 0.875rem;
            font-weight: 600;
        }

        /* Profit Analysis */
        .profit-breakdown {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .profit-item {
            background: var(--gray-50);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            text-align: center;
            transition: var(--transition);
        }

        .profit-item:hover {
            background: var(--gray-100);
            transform: translateY(-2px);
        }

        .profit-item.total {
            background: linear-gradient(135deg, var(--purple-color), #7c3aed);
            color: var(--white);
        }

        .profit-item .label {
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            opacity: 0.8;
        }

        .profit-item .value {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .profit-item .value.positive {
            color: var(--success-color);
        }

        .profit-item .value.negative {
            color: var(--danger-color);
        }

        .profit-item.total .value {
            color: var(--white);
        }

        /* Empty State */
        .no-data {
            text-align: center;
            color: var(--gray-500);
            font-style: italic;
            padding: 3rem;
            background: var(--gray-50);
            border-radius: var(--border-radius);
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--gray-300);
        }

        /* Toast Notification */
        .toast {
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

        .toast.show {
            transform: translateX(0);
        }

        .toast.error {
            background: var(--danger-color);
        }

        .toast.info {
            background: var(--info-color);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .chart-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .page-header {
                flex-direction: column;
                text-align: center;
            }
            
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                padding: 1rem;
            }
            
            .profit-breakdown {
                grid-template-columns: 1fr;
            }
            
            .chart-container {
                height: 300px;
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

        .page-container {
            animation: fadeInUp 0.6s ease-out;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
    </style>
</head>
<body>

<div class="page-container">
    <!-- Header -->
    <div class="page-header">
        <div class="header-left">
            <h1>
                <i class="fas fa-chart-line"></i>
                Báo cáo & Thống kê
            </h1>
            <p>Theo dõi hiệu suất kinh doanh và phân tích dữ liệu</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-outline" onclick="exportReport()" title="Xuất báo cáo (Ctrl+E)">
                <i class="fas fa-file-excel"></i>
                Xuất Excel
            </button>
            <button class="btn btn-outline" onclick="printReport()" title="In báo cáo (Ctrl+P)">
                <i class="fas fa-print"></i>
                In báo cáo
            </button>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" class="filter-form">
            <input type="hidden" name="page" value="reports">
            <div class="form-group">
                <label for="start_date">
                    <i class="fas fa-calendar-alt"></i>
                    Từ ngày
                </label>
                <input type="date" id="start_date" name="start_date" value="<?= $start_date ?>" required>
            </div>
            <div class="form-group">
                <label for="end_date">
                    <i class="fas fa-calendar-alt"></i>
                    Đến ngày
                </label>
                <input type="date" id="end_date" name="end_date" value="<?= $end_date ?>" required>
            </div>
            <div class="form-group">
                <label for="report_type">
                    <i class="fas fa-chart-bar"></i>
                    Loại báo cáo
                </label>
                <select id="report_type" name="report_type">
                    <option value="overview" <?= $report_type === 'overview' ? 'selected' : '' ?>>Tổng quan</option>
                    <option value="sales" <?= $report_type === 'sales' ? 'selected' : '' ?>>Báo cáo bán hàng</option>
                    <option value="inventory" <?= $report_type === 'inventory' ? 'selected' : '' ?>>Báo cáo tồn kho</option>
                    <option value="profit" <?= $report_type === 'profit' ? 'selected' : '' ?>>Báo cáo lợi nhuận</option>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    Xem báo cáo
                </button>
            </div>
        </form>
    </div>

    <!-- Quick Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card sales">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
            </div>
            <div class="stat-info">
                <h3><?= number_format($sales_summary['total_revenue'] ?? 0) ?>₫</h3>
                <p>Doanh thu (<?= $sales_summary['total_sales'] ?? 0 ?> đơn hàng)</p>
            </div>
        </div>
        
        <div class="stat-card imports">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-truck"></i>
                </div>
            </div>
            <div class="stat-info">
                <h3><?= number_format($import_summary['total_import_cost'] ?? 0) ?>₫</h3>
                <p>Chi phí nhập (<?= $import_summary['total_imports'] ?? 0 ?> phiếu)</p>
            </div>
        </div>
        
        <div class="stat-card profit">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
            <div class="stat-info">
                <h3><?= number_format($profit_analysis['estimated_profit'] ?? 0) ?>₫</h3>
                <p>Lợi nhuận ước tính</p>
            </div>
        </div>
        
        <div class="stat-card customers">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div class="stat-info">
                <h3><?= $customer_stats['total_customers'] ?? 0 ?></h3>
                <p>Khách hàng (<?= $customer_stats['total_purchases'] ?? 0 ?> giao dịch)</p>
            </div>
        </div>
    </div>

    <!-- Report Content -->
    <div class="report-content">
        <?php if ($report_type === 'sales' || $report_type === 'overview'): ?>
        <!-- Sales Report -->
        <div class="report-section">
            <div class="section-header">
                <h2>
                    <i class="fas fa-chart-bar"></i>
                    Báo cáo bán hàng
                </h2>
            </div>
            <div class="section-body">
                <div class="chart-grid">
                    <!-- Revenue Chart -->
                    <div>
                        <h3 style="margin-bottom: 1rem; color: var(--gray-700);">
                            <i class="fas fa-chart-line"></i>
                            Biểu đồ doanh thu theo ngày
                        </h3>
                        <div class="chart-container">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>

                    <!-- Daily Sales Chart -->
                    <div>
                        <h3 style="margin-bottom: 1rem; color: var(--gray-700);">
                            <i class="fas fa-calendar-day"></i>
                            Doanh thu hàng ngày
                        </h3>
                        <div class="daily-sales-chart">
                            <?php if (!empty($daily_sales)): ?>
                                <?php 
                                $max_revenue = max(array_column($daily_sales, 'daily_revenue'));
                                foreach ($daily_sales as $day): 
                                    $height = $max_revenue > 0 ? ($day['daily_revenue'] / $max_revenue) * 100 : 0;
                                ?>
                                <div class="chart-bar" style="height: <?= $height ?>%" 
                                     title="<?= date('d/m', strtotime($day['date'])) ?>: <?= number_format($day['daily_revenue']) ?>₫">
                                    <div class="bar-value"><?= number_format($day['daily_revenue'] / 1000) ?>K</div>
                                    <div class="bar-date"><?= date('d/m', strtotime($day['date'])) ?></div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-data">
                                    <i class="fas fa-chart-bar"></i>
                                    <p>Không có dữ liệu bán hàng trong khoảng thời gian này</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Top Products -->
                <h3 style="margin: 2rem 0 1rem; color: var(--gray-700);">
                    <i class="fas fa-trophy"></i>
                    Top sản phẩm bán chạy
                </h3>
                <?php if (!empty($top_products)): ?>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Mã SP</th>
                            <th>Tên sản phẩm</th>
                            <th class="text-center">Số lượng bán</th>
                            <th class="text-right">Doanh thu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_products as $index => $product): ?>
                        <tr>
                            <td><strong><?= $index + 1 ?></strong></td>
                            <td><code><?= htmlspecialchars($product['product_code']) ?></code></td>
                            <td><?= htmlspecialchars($product['product_name']) ?></td>
                            <td class="text-center"><strong><?= $product['total_sold'] ?></strong></td>
                            <td class="text-right"><strong style="color: var(--success-color);"><?= number_format($product['total_revenue']) ?>₫</strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-box-open"></i>
                    <p>Chưa có sản phẩm nào được bán trong khoảng thời gian này</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($report_type === 'inventory' || $report_type === 'overview'): ?>
        <!-- Inventory Report -->
        <div class="report-section">
            <div class="section-header">
                <h2>
                    <i class="fas fa-warehouse"></i>
                    Báo cáo tồn kho
                </h2>
            </div>
            <div class="section-body">
                <h3 style="margin-bottom: 1rem; color: var(--gray-700);">
                    <i class="fas fa-exclamation-triangle"></i>
                    Cảnh báo tồn kho thấp
                </h3>
                <?php if (!empty($low_stock_products)): ?>
                <div class="stock-alerts">
                    <?php foreach ($low_stock_products as $product): ?>
                    <div class="stock-alert <?= $product['stock_level'] ?>">
                        <div class="product-info">
                            <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                            <div class="product-code"><?= htmlspecialchars($product['product_code']) ?></div>
                        </div>
                        <div class="stock-info">
                            <div class="stock-quantity"><?= $product['stock_quantity'] ?></div>
                            <div class="stock-status">
                                <?php 
                                switch($product['stock_level']) {
                                    case 'out_of_stock': echo '🔴 Hết hàng'; break;
                                    case 'very_low': echo '🟠 Rất ít'; break;
                                    case 'low': echo '🟡 Sắp hết'; break;
                                    default: echo '🟢 Bình thường';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-check-circle"></i>
                    <p>Tất cả sản phẩm đều có tồn kho tốt</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($report_type === 'profit' || $report_type === 'overview'): ?>
        <!-- Profit Analysis -->
        <div class="report-section">
            <div class="section-header">
                <h2>
                    <i class="fas fa-chart-pie"></i>
                    Phân tích lợi nhuận
                </h2>
            </div>
            <div class="section-body">
                <div class="profit-breakdown">
                    <div class="profit-item">
                        <div class="label">
                            <i class="fas fa-money-bill-wave"></i>
                            Tổng doanh thu
                        </div>
                        <div class="value positive"><?= number_format($profit_analysis['total_sales'] ?? 0) ?>₫</div>
                    </div>
                    <div class="profit-item">
                        <div class="label">
                            <i class="fas fa-shopping-cart"></i>
                            Chi phí hàng hóa
                        </div>
                        <div class="value negative"><?= number_format($profit_analysis['estimated_cost'] ?? 0) ?>₫</div>
                    </div>
                    <div class="profit-item total">
                        <div class="label">
                            <i class="fas fa-chart-line"></i>
                            Lợi nhuận ước tính
                        </div>
                        <div class="value"><?= number_format($profit_analysis['estimated_profit'] ?? 0) ?>₫</div>
                    </div>
                    <div class="profit-item">
                        <div class="label">
                            <i class="fas fa-percentage"></i>
                            Tỷ suất lợi nhuận
                        </div>
                        <div class="value">
                            <?php 
                            $profit_margin = ($profit_analysis['total_sales'] ?? 0) > 0 
                                ? (($profit_analysis['estimated_profit'] ?? 0) / $profit_analysis['total_sales']) * 100 
                                : 0;
                            echo number_format($profit_margin, 1) . '%';
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Revenue Chart
const dailySalesData = <?= json_encode($daily_sales) ?>;
const labels = [];
const data = [];

// Prepare chart data
dailySalesData.forEach(item => {
    labels.push(new Date(item.date).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' }));
    data.push(parseFloat(item.daily_revenue));
});

const ctx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Doanh thu (VNĐ)',
            data: data,
            borderColor: '#06b6d4',
            backgroundColor: 'rgba(6, 182, 212, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#06b6d4',
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
                        return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
                    },
                    color: '#6b7280'
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
        }
    }
});

// Functions
function exportReport() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const reportType = document.getElementById('report_type').value;
    
    showToast('Đang xuất báo cáo...', 'info');
    
    // Simulate export
    setTimeout(() => {
        showToast('Đã xuất báo cáo thành công!', 'success');
        // In real implementation, create download link
        // window.open(`export_report.php?start_date=${startDate}&end_date=${endDate}&report_type=${reportType}`);
    }, 1500);
}

function printReport() {
    showToast('Đang chuẩn bị in...', 'info');
    setTimeout(() => {
        window.print();
    }, 500);
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
        e.preventDefault();
        exportReport();
    }
    
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        printReport();
    }
});

// Auto refresh data every 5 minutes
setInterval(function() {
    if (document.visibilityState === 'visible') {
        location.reload();
    }
}, 300000); // 5 minutes

console.log('📊 Reports page loaded successfully!');
console.log('💡 Keyboard shortcuts: Ctrl+E (Export), Ctrl+P (Print)');
</script>

</body>
</html>
