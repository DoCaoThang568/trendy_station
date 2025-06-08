<?php
// Dashboard - Trang tổng quan hệ thống
$page_title = "Tổng quan - Dashboard";

// Lấy thống kê tổng quan
try {
    // Thống kê sản phẩm
    $stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products WHERE is_active = 1"); // MODIFIED: status to is_active
    $total_products = $stmt->fetch()['total_products'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as low_stock FROM products WHERE stock_quantity <= 5 AND is_active = 1"); // MODIFIED: status to is_active
    $low_stock_products = $stmt->fetch()['low_stock'];
    
    // Thống kê bán hàng hôm nay
    $stmt = $pdo->prepare("SELECT COUNT(*) as today_sales, COALESCE(SUM(total_amount), 0) as today_revenue FROM sales WHERE DATE(sale_date) = CURDATE()");
    $stmt->execute();
    $today_stats = $stmt->fetch();
    
    // Thống kê khách hàng
    $stmt = $pdo->query("SELECT COUNT(*) as total_customers FROM customers WHERE is_active = 1");
    $total_customers = $stmt->fetch()['total_customers'];
    
    // Doanh thu 7 ngày gần đây
    $stmt = $pdo->query("
        SELECT DATE(sale_date) as date, COALESCE(SUM(total_amount), 0) as revenue 
        FROM sales 
        WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(sale_date)
        ORDER BY date ASC
    ");
    $weekly_revenue = $stmt->fetchAll();
    
    // Top sản phẩm bán chạy (30 ngày)
    $stmt = $pdo->query("
        SELECT p.name as product_name, SUM(sd.quantity) as total_sold, SUM(sd.quantity * sd.unit_price) as revenue
        FROM sale_details sd
        JOIN products p ON sd.product_id = p.id
        JOIN sales s ON sd.sale_id = s.id
        WHERE s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY p.name
        ORDER BY total_sold DESC
        LIMIT 5
    ");
    $top_products = $stmt->fetchAll();
    
    // Hóa đơn gần đây
    $stmt = $pdo->query("
        SELECT s.id as sale_pk_id, s.sale_code, s.sale_date, c.name as customer_name, s.total_amount, s.payment_method
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id 
        ORDER BY s.sale_date DESC
        LIMIT 5
    "); // MODIFIED: selected s.id as sale_pk_id, s.sale_code, c.name and join condition c.id
    $recent_sales = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $total_products = $low_stock_products = $total_customers = 0;
    $today_stats = ['today_sales' => 0, 'today_revenue' => 0];
    $weekly_revenue = $top_products = $recent_sales = [];
}
?>

<div class="dashboard-container">
    <!-- Header với shortcut hints -->
    <div class="page-header">
        <h2><i class="fas fa-tachometer-alt"></i> Dashboard - Tổng quan hệ thống</h2>
        <div class="shortcut-hints">
            <span class="shortcut-badge">F1: Sản phẩm</span>
            <span class="shortcut-badge">F2: Bán hàng</span>
            <span class="shortcut-badge">F3: Nhập hàng</span>
            <span class="shortcut-badge">F4: Khách hàng</span>
        </div>
    </div>

    <!-- Thống kê tổng quan -->
    <div class="stats-grid">
        <div class="stat-card revenue">
            <div class="stat-icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($today_stats['today_revenue']) ?>đ</h3>
                <p>Doanh thu hôm nay</p>
                <small><?= $today_stats['today_sales'] ?> hóa đơn</small>
            </div>
            <div class="stat-trend positive">
                <i class="fas fa-arrow-up"></i>
            </div>
        </div>

        <div class="stat-card products">
            <div class="stat-icon">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-info">
                <h3><?= $total_products ?></h3>
                <p>Tổng sản phẩm</p>
                <?php if ($low_stock_products > 0): ?>
                    <small class="warning"><?= $low_stock_products ?> sắp hết hàng</small>
                <?php else: ?>
                    <small>Tồn kho ổn định</small>
                <?php endif; ?>
            </div>
            <div class="stat-action">
                <a href="?page=products" class="btn-quick"><i class="fas fa-eye"></i></a>
            </div>
        </div>

        <div class="stat-card customers">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3><?= $total_customers ?></h3>
                <p>Khách hàng</p>
                <small>Đang hoạt động</small>
            </div>
            <div class="stat-action">
                <a href="?page=customers" class="btn-quick"><i class="fas fa-eye"></i></a>
            </div>
        </div>

        <div class="stat-card quick-actions">
            <div class="stat-icon">
                <i class="fas fa-bolt"></i>
            </div>
            <div class="stat-info">
                <h3>Thao tác nhanh</h3>
                <div class="quick-btn-group">
                    <button onclick="location.href='?page=sales'" class="btn btn-success btn-sm">
                        <i class="fas fa-cash-register"></i> Bán hàng
                    </button>
                    <button onclick="location.href='?page=imports'" class="btn btn-primary btn-sm">
                        <i class="fas fa-truck"></i> Nhập hàng
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Biểu đồ và bảng thống kê -->
    <div class="dashboard-grid">
        <!-- Biểu đồ doanh thu 7 ngày -->
        <div class="dashboard-card">
            <div class="card-header">
                <h4><i class="fas fa-chart-line"></i> Doanh thu 7 ngày gần đây</h4>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- Top sản phẩm bán chạy -->
        <div class="dashboard-card">
            <div class="card-header">
                <h4><i class="fas fa-star"></i> Top sản phẩm bán chạy (30 ngày)</h4>
            </div>
            <div class="card-body">
                <?php if (empty($top_products)): ?>
                    <div class="empty-state">
                        <i class="fas fa-chart-bar"></i>
                        <p>Chưa có dữ liệu bán hàng</p>
                    </div>
                <?php else: ?>
                    <div class="top-products-list">
                        <?php foreach ($top_products as $index => $product): ?>
                            <div class="top-product-item">
                                <div class="product-rank">#<?= $index + 1 ?></div>
                                <div class="product-info">
                                    <div class="product-name"><?= htmlspecialchars($product['product_name']) ?></div>
                                    <div class="product-stats">
                                        <span class="sold"><?= $product['total_sold'] ?> đã bán</span>
                                        <span class="revenue"><?= number_format($product['revenue']) ?>đ</span>
                                    </div>
                                </div>
                                <div class="product-bar">
                                    <div class="bar-fill" style="width: <?= ($product['total_sold'] / $top_products[0]['total_sold']) * 100 ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Hóa đơn gần đây -->
        <div class="dashboard-card full-width">
            <div class="card-header">
                <h4><i class="fas fa-receipt"></i> Hóa đơn gần đây</h4>
                <a href="?page=sales" class="btn btn-outline-primary btn-sm">Xem tất cả</a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_sales)): ?>
                    <div class="empty-state">
                        <i class="fas fa-receipt"></i>
                        <p>Chưa có hóa đơn nào</p>
                        <button onclick="location.href='?page=sales'" class="btn btn-primary">Tạo hóa đơn đầu tiên</button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Mã HĐ</th>
                                    <th>Thời gian</th>
                                    <th>Khách hàng</th>
                                    <th>Tổng tiền</th>
                                    <th>Thanh toán</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_sales as $sale): ?>
                                    <tr>
                                        <td>
                                            <span class="invoice-id">#<?= htmlspecialchars($sale['sale_code']) ?></span> 
                                        </td>
                                        <td>
                                            <?= date('d/m H:i', strtotime($sale['sale_date'])) ?>
                                        </td>
                                        <td>
                                            <?= $sale['customer_name'] ? htmlspecialchars($sale['customer_name']) : '<span class="text-muted">Khách lẻ</span>' ?>
                                        </td>
                                        <td>
                                            <span class="amount"><?= number_format($sale['total_amount']) ?>đ</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?= $sale['payment_method'] == 'cash' ? 'success' : 'info' ?>">
                                                <?= $sale['payment_method'] == 'cash' ? 'Tiền mặt' : ($sale['payment_method'] == 'bank_transfer' ? 'Chuyển khoản' : htmlspecialchars(ucfirst($sale['payment_method']))) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button onclick="viewSaleDetail(<?= $sale['sale_pk_id'] ?>)" class="btn btn-sm btn-outline-primary" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button onclick="printInvoice(<?= $sale['sale_pk_id'] ?>)" class="btn btn-sm btn-outline-success" title="In hóa đơn">
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
    </div>
</div>

<!-- Chart.js for revenue chart -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Biểu đồ doanh thu 7 ngày
const revenueData = <?= json_encode($weekly_revenue) ?>;
const labels = [];
const data = [];

// Tạo labels và data cho 7 ngày gần đây
for (let i = 6; i >= 0; i--) {
    const date = new Date();
    date.setDate(date.getDate() - i);
    const dateStr = date.toISOString().split('T')[0];
    const dayStr = date.toLocaleDateString('vi-VN', { weekday: 'short', day: 'numeric', month: 'numeric' });
    
    labels.push(dayStr);
    
    const foundData = revenueData.find(item => item.date === dateStr);
    data.push(foundData ? parseFloat(foundData.revenue) : 0);
}

const ctx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Doanh thu (VNĐ)',
            data: data,
            borderColor: '#4f46e5',
            backgroundColor: 'rgba(79, 70, 229, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
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
                ticks: {
                    callback: function(value) {
                        return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
                    }
                }
            }
        },
        elements: {
            point: {
                radius: 4,
                hoverRadius: 6
            }
        }
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Không xử lý nếu đang focus vào input
    if (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA') {
        return;
    }
    
    switch(e.key) {
        case 'F1':
            e.preventDefault();
            location.href = '?page=products';
            break;
        case 'F2':
            e.preventDefault();
            location.href = '?page=sales';
            break;
        case 'F3':
            e.preventDefault();
            location.href = '?page=imports';
            break;
        case 'F4':
            e.preventDefault();
            location.href = '?page=customers';
            break;
        case 'F5':
            e.preventDefault();
            location.reload();
            break;
    }
});

// Functions for recent sales actions
function viewSaleDetail(saleId) {
    // Sử dụng function từ sales.php
    if (typeof window.viewSaleDetail === 'function') {
        window.viewSaleDetail(saleId);
    } else {
        location.href = '?page=sales#sale-' + saleId;
    }
}

function printInvoice(saleId) {
    window.open('print_invoice.php?sale_id=' + saleId, '_blank');
}

// Auto refresh dashboard every 5 minutes
setTimeout(() => {
    location.reload();
}, 5 * 60 * 1000);

console.log('📊 Dashboard loaded successfully!');
console.log('💡 Keyboard shortcuts: F1-Products, F2-Sales, F3-Imports, F4-Customers, F5-Refresh');
</script>

<style>
/* Dashboard specific styles */
.dashboard-container {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.shortcut-hints {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.shortcut-badge {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    white-space: nowrap;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #667eea, #764ba2);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.stat-card.revenue .stat-icon {
    background: linear-gradient(135deg, #11998e, #38ef7d);
}

.stat-card.products .stat-icon {
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.stat-card.customers .stat-icon {
    background: linear-gradient(135deg, #f093fb, #f5576c);
}

.stat-card.quick-actions .stat-icon {
    background: linear-gradient(135deg, #4facfe, #00f2fe);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}

.stat-info h3 {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
    color: #333;
}

.stat-info p {
    margin: 5px 0 0;
    color: #666;
    font-weight: 500;
}

.stat-info small {
    font-size: 12px;
    color: #999;
}

.stat-info small.warning {
    color: #ff6b6b;
    font-weight: 600;
}

.stat-trend {
    margin-left: auto;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.stat-trend.positive {
    background: #11998e;
}

.stat-action {
    margin-left: auto;
}

.btn-quick {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-quick:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.quick-btn-group {
    display: flex;
    gap: 8px;
    margin-top: 10px;
}

/* Dashboard Grid */
.dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.dashboard-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.dashboard-card.full-width {
    grid-column: 1 / -1;
}

.card-header {
    padding: 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.card-header h4 {
    margin: 0;
    color: #333;
    font-size: 16px;
    font-weight: 600;
}

.card-body {
    padding: 20px;
}

/* Top Products */
.top-products-list {
    space-y: 12px;
}

.top-product-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}

.top-product-item:last-child {
    border-bottom: none;
}

.product-rank {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
}

.product-info {
    flex: 1;
}

.product-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 4px;
}

.product-stats {
    display: flex;
    gap: 15px;
    font-size: 12px;
}

.product-stats .sold {
    color: #666;
}

.product-stats .revenue {
    color: #11998e;
    font-weight: 600;
}

.product-bar {
    width: 60px;
    height: 6px;
    background: #f0f0f0;
    border-radius: 3px;
    overflow: hidden;
}

.bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea, #764ba2);
    border-radius: 3px;
    transition: width 0.5s ease;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #999;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
}

/* Chart container */
#revenueChart {
    max-height: 200px !important;
}

/* Table enhancements for dashboard */
.dashboard-card .table {
    margin-bottom: 0;
}

.invoice-id {
    font-family: 'Courier New', monospace;
    font-weight: 600;
    color: #667eea;
}

.amount {
    font-weight: 600;
    color: #11998e;
}

/* Responsive */
@media (max-width: 768px) {
    .dashboard-container {
        padding: 15px;
    }
    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .dashboard-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .stat-card {
        padding: 15px;
    }
    
    .shortcut-hints {
        justify-content: flex-start;
    }
    
    .shortcut-badge {
        font-size: 10px;
        padding: 3px 6px;
    }
}

@media (max-width: 480px) {
    .top-product-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
    
    .product-bar {
        width: 100%;
        align-self: stretch;
    }
    
    .card-header {
        padding: 15px;
    }
    
    .card-body {
        padding: 15px;
    }
}
</style>
