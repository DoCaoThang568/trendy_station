<?php
require_once '../config/database.php';

// Get date range for reports
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-t'); // Last day of current month
$report_type = $_GET['report_type'] ?? 'sales';

// Get sales summary
$sales_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_sales,
        SUM(total_amount) as total_revenue,
        AVG(total_amount) as avg_sale_amount
    FROM sales 
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$sales_stmt->execute([$start_date, $end_date]);
$sales_summary = $sales_stmt->fetch();

// Get top selling products
$top_products_stmt = $pdo->prepare("
    SELECT 
        p.name as product_name,
        p.product_code as product_code, //Sửa p.code thành p.product_code
        SUM(sd.quantity) as total_sold,
        SUM(sd.total_price) as total_revenue
    FROM sale_details sd
    JOIN products p ON sd.product_id = p.id
    JOIN sales s ON sd.sale_id = s.id
    WHERE DATE(s.created_at) BETWEEN ? AND ?
    GROUP BY p.id, p.name, p.product_code //Sửa p.code thành p.product_code
    ORDER BY total_sold DESC
    LIMIT 10
");
$top_products_stmt->execute([$start_date, $end_date]);
$top_products = $top_products_stmt->fetchAll();

// Get daily sales
$daily_sales_stmt = $pdo->prepare("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as sales_count,
        SUM(total_amount) as daily_revenue
    FROM sales 
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date DESC
");
$daily_sales_stmt->execute([$start_date, $end_date]);
$daily_sales = $daily_sales_stmt->fetchAll();

// Get import summary
$import_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_imports,
        SUM(total_amount) as total_import_cost,
        AVG(total_amount) as avg_import_cost
    FROM imports 
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$import_stmt->execute([$start_date, $end_date]);
$import_summary = $import_stmt->fetch();

// Get low stock products
$low_stock_stmt = $pdo->prepare("
    SELECT 
        id, name, product_code, stock_quantity, //Sửa code thành product_code
        CASE 
            WHEN stock_quantity = 0 THEN 'out_of_stock'
            WHEN stock_quantity <= 5 THEN 'very_low'
            WHEN stock_quantity <= 10 THEN 'low'
            ELSE 'normal'
        END as stock_level
    FROM products 
    WHERE stock_quantity <= 20
    ORDER BY stock_quantity ASC, name ASC
");
$low_stock_stmt->execute();
$low_stock_products = $low_stock_stmt->fetchAll();

// Get customer stats
$customer_stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT c.id) as total_customers,
        COUNT(s.id) as total_purchases,
        COALESCE(AVG(s.total_amount), 0) as avg_purchase_amount
    FROM customers c
    LEFT JOIN sales s ON c.id = s.customer_id 
        AND DATE(s.created_at) BETWEEN ? AND ?
");
$customer_stats_stmt->execute([$start_date, $end_date]);
$customer_stats = $customer_stats_stmt->fetch();

// Get profit analysis (estimated)
$profit_stmt = $pdo->prepare("
    SELECT 
        SUM(sd.total_price) as total_sales,
        SUM(sd.quantity * p.cost_price) as estimated_cost, //Sửa p.purchase_price thành p.cost_price
        (SUM(sd.total_price) - SUM(sd.quantity * p.cost_price)) as estimated_profit //Sửa p.purchase_price thành p.cost_price
    FROM sale_details sd
    JOIN products p ON sd.product_id = p.id
    JOIN sales s ON sd.sale_id = s.id
    WHERE DATE(s.created_at) BETWEEN ? AND ?
");
$profit_stmt->execute([$start_date, $end_date]);
$profit_analysis = $profit_stmt->fetch();

$page_title = "📊 Báo cáo & Thống kê";
include '../includes/header.php';
?>

<div class="main-content">
    <div class="content-header">
        <div class="header-left">
            <h1>📊 Báo cáo & Thống kê</h1>
            <p>Theo dõi hiệu suất kinh doanh của shop</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="exportReport()" title="Xuất báo cáo (Ctrl+E)">
                📥 Xuất báo cáo
            </button>
            <button class="btn btn-secondary" onclick="printReport()" title="In báo cáo (Ctrl+P)">
                🖨️ In báo cáo  
            </button>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" class="filter-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="start_date">📅 Từ ngày:</label>
                    <input type="date" id="start_date" name="start_date" value="<?= $start_date ?>" required>
                </div>
                <div class="form-group">
                    <label for="end_date">📅 Đến ngày:</label>
                    <input type="date" id="end_date" name="end_date" value="<?= $end_date ?>" required>
                </div>
                <div class="form-group">
                    <label for="report_type">📊 Loại báo cáo:</label>
                    <select id="report_type" name="report_type">
                        <option value="sales" <?= $report_type === 'sales' ? 'selected' : '' ?>>Báo cáo bán hàng</option>
                        <option value="inventory" <?= $report_type === 'inventory' ? 'selected' : '' ?>>Báo cáo tồn kho</option>
                        <option value="profit" <?= $report_type === 'profit' ? 'selected' : '' ?>>Báo cáo lợi nhuận</option>
                        <option value="overview" <?= $report_type === 'overview' ? 'selected' : '' ?>>Tổng quan</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">🔍 Xem báo cáo</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Quick Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card sales">
            <div class="stat-icon">💰</div>
            <div class="stat-info">
                <h3><?= number_format($sales_summary['total_revenue'] ?? 0, 0, ',', '.') ?>đ</h3>
                <p>Doanh thu (<?= $sales_summary['total_sales'] ?? 0 ?> đơn)</p>
            </div>
        </div>
        
        <div class="stat-card imports">
            <div class="stat-icon">📦</div>
            <div class="stat-info">
                <h3><?= number_format($import_summary['total_import_cost'] ?? 0, 0, ',', '.') ?>đ</h3>
                <p>Chi phí nhập (<?= $import_summary['total_imports'] ?? 0 ?> phiếu)</p>
            </div>
        </div>
        
        <div class="stat-card profit">
            <div class="stat-icon">📈</div>
            <div class="stat-info">
                <h3><?= number_format($profit_analysis['estimated_profit'] ?? 0, 0, ',', '.') ?>đ</h3>
                <p>Lợi nhuận ước tính</p>
            </div>
        </div>
        
        <div class="stat-card customers">
            <div class="stat-icon">👥</div>
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
            <h2>📊 Báo cáo bán hàng</h2>
            
            <!-- Daily Sales Chart -->
            <div class="chart-container">
                <h3>📈 Doanh thu theo ngày</h3>
                <div class="daily-sales-chart">
                    <?php if (!empty($daily_sales)): ?>
                        <?php 
                        $max_revenue = max(array_column($daily_sales, 'daily_revenue'));
                        foreach ($daily_sales as $day): 
                            $height = $max_revenue > 0 ? ($day['daily_revenue'] / $max_revenue) * 100 : 0;
                        ?>
                        <div class="chart-bar" style="height: <?= $height ?>%" 
                             title="<?= date('d/m', strtotime($day['date'])) ?>: <?= number_format($day['daily_revenue'], 0, ',', '.') ?>đ">
                            <div class="bar-value"><?= number_format($day['daily_revenue'], 0, ',', '.') ?>đ</div>
                            <div class="bar-date"><?= date('d/m', strtotime($day['date'])) ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-data">Không có dữ liệu bán hàng trong khoảng thời gian này</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top Products -->
            <div class="table-container">
                <h3>🏆 Top sản phẩm bán chạy</h3>
                <?php if (!empty($top_products)): ?>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Mã SP</th>
                            <th>Tên sản phẩm</th>
                            <th>Số lượng bán</th>
                            <th>Doanh thu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_products as $index => $product): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($product['product_code']) ?></td>
                            <td><?= htmlspecialchars($product['product_name']) ?></td>
                            <td class="text-center"><?= $product['total_sold'] ?></td>
                            <td class="text-right"><?= number_format($product['total_revenue'], 0, ',', '.') ?>đ</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="no-data">Chưa có sản phẩm nào được bán trong kho thời gian này</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($report_type === 'inventory' || $report_type === 'overview'): ?>
        <!-- Inventory Report -->
        <div class="report-section">
            <h2>📦 Báo cáo tồn kho</h2>
            
            <div class="inventory-alerts">
                <h3>⚠️ Cảnh báo tồn kho</h3>
                <?php if (!empty($low_stock_products)): ?>
                <div class="stock-alerts">
                    <?php foreach ($low_stock_products as $product): ?>
                    <div class="stock-alert <?= $product['stock_level'] ?>">
                        <div class="product-info">
                            <strong><?= htmlspecialchars($product['name']) ?></strong>
                            <span class="product-code">(<?= htmlspecialchars($product['product_code']) ?>)</span>
                        </div>
                        <div class="stock-info">
                            <span class="stock-quantity"><?= $product['stock_quantity'] ?></span>
                            <span class="stock-status">
                                <?php 
                                switch($product['stock_level']) {
                                    case 'out_of_stock': echo '🔴 Hết hàng'; break;
                                    case 'very_low': echo '🟠 Rất ít'; break;
                                    case 'low': echo '🟡 Sắp hết'; break;
                                    default: echo '🟢 Bình thường';
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="no-data">Tất cả sản phẩm đều có tồn kho tốt</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($report_type === 'profit' || $report_type === 'overview'): ?>
        <!-- Profit Analysis -->
        <div class="report-section">
            <h2>💹 Phân tích lợi nhuận</h2>
            
            <div class="profit-breakdown">
                <div class="profit-item">
                    <span class="label">💰 Tổng doanh thu:</span>
                    <span class="value positive"><?= number_format($profit_analysis['total_sales'] ?? 0, 0, ',', '.') ?>đ</span>
                </div>
                <div class="profit-item">
                    <span class="label">📦 Chi phí hàng hóa:</span>
                    <span class="value negative"><?= number_format($profit_analysis['estimated_cost'] ?? 0, 0, ',', '.') ?>đ</span>
                </div>
                <div class="profit-item total">
                    <span class="label">📈 Lợi nhuận ước tính:</span>
                    <span class="value <?= ($profit_analysis['estimated_profit'] ?? 0) >= 0 ? 'positive' : 'negative' ?>">
                        <?= number_format($profit_analysis['estimated_profit'] ?? 0, 0, ',', '.') ?>đ
                    </span>
                </div>
                <div class="profit-item">
                    <span class="label">📊 Tỷ suất lợi nhuận:</span>
                    <span class="value">
                        <?php 
                        $profit_margin = ($profit_analysis['total_sales'] ?? 0) > 0 
                            ? (($profit_analysis['estimated_profit'] ?? 0) / $profit_analysis['total_sales']) * 100 
                            : 0;
                        echo number_format($profit_margin, 1) . '%';
                        ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
    color: white;
    padding: 1.5rem;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: transform 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-card.sales { background: linear-gradient(135deg, #10b981, #34d399); }
.stat-card.imports { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
.stat-card.profit { background: linear-gradient(135deg, #8b5cf6, #a78bfa); }
.stat-card.customers { background: linear-gradient(135deg, #f59e0b, #fbbf24); }

.stat-icon {
    font-size: 2.5rem;
    opacity: 0.9;
}

.stat-info h3 {
    font-size: 1.8rem;
    font-weight: bold;
    margin: 0;
}

.stat-info p {
    margin: 0;
    opacity: 0.9;
    font-size: 0.9rem;
}

.filter-section {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    margin-bottom: 2rem;
}

.filter-form .form-row {
    display: flex;
    gap: 1rem;
    align-items: end;
    flex-wrap: wrap;
}

.report-content {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.report-section {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.report-section h2 {
    color: var(--primary-color);
    margin-bottom: 1.5rem;
    border-bottom: 2px solid var(--primary-light);
    padding-bottom: 0.5rem;
}

.chart-container {
    margin-bottom: 2rem;
}

.daily-sales-chart {
    display: flex;
    gap: 4px;
    align-items: end;
    height: 200px;
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    overflow-x: auto;
}

.chart-bar {
    min-width: 40px;
    background: linear-gradient(to top, var(--primary-color), var(--primary-light));
    border-radius: 4px 4px 0 0;
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: end;
    align-items: center;
    cursor: pointer;
    transition: opacity 0.2s ease;
}

.chart-bar:hover {
    opacity: 0.8;
}

.bar-value {
    position: absolute;
    top: -25px;
    font-size: 0.7rem;
    font-weight: bold;
    color: var(--primary-color);
    white-space: nowrap;
}

.bar-date {
    margin-top: 5px;
    font-size: 0.7rem;
    color: #666;
}

.report-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}

.report-table th,
.report-table td {
    border: 1px solid #e5e7eb;
    padding: 0.75rem;
    text-align: left;
}

.report-table th {
    background-color: #f9fafb;
    font-weight: 600;
    color: var(--primary-color);
}

.report-table .text-center { text-align: center; }
.report-table .text-right { text-align: right; }

.stock-alerts {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.stock-alert {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    border-radius: 8px;
    border-left: 4px solid;
}

.stock-alert.out_of_stock {
    background-color: #fef2f2;
    border-left-color: #ef4444;
}

.stock-alert.very_low {
    background-color: #fff7ed;
    border-left-color: #f97316;
}

.stock-alert.low {
    background-color: #fefce8;
    border-left-color: #eab308;
}

.product-code {
    color: #666;
    font-size: 0.9rem;
}

.stock-quantity {
    font-weight: bold;
    font-size: 1.1rem;
}

.stock-status {
    margin-left: 0.5rem;
    font-size: 0.9rem;
}

.profit-breakdown {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.profit-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background-color: #f9fafb;
    border-radius: 8px;
}

.profit-item.total {
    background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
    color: white;
    font-weight: bold;
    font-size: 1.1rem;
}

.profit-item .label {
    font-weight: 500;
}

.profit-item .value.positive {
    color: #10b981;
    font-weight: bold;
}

.profit-item .value.negative {
    color: #ef4444;
    font-weight: bold;
}

.no-data {
    text-align: center;
    color: #666;
    font-style: italic;
    padding: 2rem;
}

@media (max-width: 768px) {
    .filter-form .form-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .daily-sales-chart {
        height: 150px;
    }
    
    .profit-item {
        flex-direction: column;
        align-items: start;
        gap: 0.5rem;
    }
}
</style>

<script>
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

// Export report
function exportReport() {
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const reportType = document.getElementById('report_type').value;
    
    const exportUrl = `ajax/export_report.php?start_date=${startDate}&end_date=${endDate}&report_type=${reportType}`;
    
    showToast('Đang xuất báo cáo...', 'info');
    
    // Create download link
    const link = document.createElement('a');
    link.href = exportUrl;
    link.download = `bao_cao_${reportType}_${startDate}_${endDate}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    setTimeout(() => {
        showToast('Đã xuất báo cáo thành công!', 'success');
    }, 1000);
}

// Print report
function printReport() {
    window.print();
}

// Auto refresh data every 5 minutes
setInterval(function() {
    if (document.visibilityState === 'visible') {
        location.reload();
    }
}, 300000); // 5 minutes
</script>

<?php include '../includes/footer.php'; ?>
