<?php
require_once __DIR__ . '/../includes/functions.php';
/**
 * All Sales Page - Xem tất cả hóa đơn với phân trang và tìm kiếm
 */

// Get filter parameters
$search = $_GET['search'] ?? '';
$payment_method = $_GET['payment_method'] ?? '';
$payment_status = $_GET['payment_status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'sale_date';
$sort_order = $_GET['sort_order'] ?? 'DESC';
$page = max(1, (int)($_GET['pg'] ?? 1));
$per_page = 20; // 20 records per page

// Build WHERE clause
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(s.sale_code LIKE ? OR s.customer_name LIKE ? OR c.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($payment_method)) {
    $where_conditions[] = "s.payment_method = ?";
    $params[] = $payment_method;
}

if (!empty($payment_status)) {
    $where_conditions[] = "s.payment_status = ?";
    $params[] = $payment_status;
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(s.sale_date) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(s.sale_date) <= ?";
    $params[] = $date_to;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Validate sort parameters
$allowed_sort_columns = ['sale_code', 'sale_date', 'customer_name', 'final_amount', 'payment_method', 'payment_status'];
if (!in_array($sort_by, $allowed_sort_columns)) {
    $sort_by = 'sale_date';
}
$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

// Get total count
$count_sql = "
    SELECT COUNT(*) 
    FROM sales s 
    LEFT JOIN customers c ON s.customer_id = c.id 
    $where_clause
";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Get paginated sales
$offset = ($page - 1) * $per_page;
$sales_sql = "
    SELECT 
        s.*,
        c.name as customer_name_db,
        COUNT(sd.id) as item_count
    FROM sales s 
    LEFT JOIN customers c ON s.customer_id = c.id 
    LEFT JOIN sale_details sd ON s.id = sd.sale_id
    $where_clause 
    GROUP BY s.id
    ORDER BY s.$sort_by $sort_order
    LIMIT $per_page OFFSET $offset
";
$sales_stmt = $pdo->prepare($sales_sql);
$sales_stmt->execute($params);
$sales = $sales_stmt->fetchAll();

// Get summary statistics
$stats_sql = "
    SELECT 
        COUNT(*) as total_sales,
        SUM(s.final_amount) as total_revenue,
        AVG(s.final_amount) as avg_sale_amount
    FROM sales s 
    LEFT JOIN customers c ON s.customer_id = c.id 
    $where_clause
";
$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute($params);
$stats = $stats_stmt->fetch();
?>

<h1 class="page-title">📋 Tất cả hóa đơn</h1>

<!-- Filter Form -->
<div class="form-container" style="margin-bottom: 2rem;">
    <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
        <input type="hidden" name="page" value="all_sales">
        
        <div class="form-group">
            <label for="search">🔍 Tìm kiếm</label>
            <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Mã hóa đơn, tên khách hàng...">
        </div>
        
        <div class="form-group">
            <label for="payment_method">💳 Phương thức thanh toán</label>
            <select name="payment_method" id="payment_method">
                <option value="">-- Tất cả --</option>
                <option value="Tiền mặt" <?php echo $payment_method === 'Tiền mặt' ? 'selected' : ''; ?>>💵 Tiền mặt</option>
                <option value="Thẻ tín dụng" <?php echo $payment_method === 'Thẻ tín dụng' ? 'selected' : ''; ?>>💳 Thẻ tín dụng</option>
                <option value="Chuyển khoản" <?php echo $payment_method === 'Chuyển khoản' ? 'selected' : ''; ?>>🏦 Chuyển khoản</option>
                <option value="Ví điện tử" <?php echo $payment_method === 'Ví điện tử' ? 'selected' : ''; ?>>📱 Ví điện tử</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="date_from">📅 Từ ngày</label>
            <input type="date" name="date_from" id="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
        </div>
        
        <div class="form-group">
            <label for="date_to">📅 Đến ngày</label>
            <input type="date" name="date_to" id="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
        </div>
        
        <div class="form-group">
            <label for="sort_by">📊 Sắp xếp theo</label>
            <select name="sort_by" id="sort_by">
                <option value="sale_date" <?php echo $sort_by === 'sale_date' ? 'selected' : ''; ?>>Ngày tạo</option>
                <option value="sale_code" <?php echo $sort_by === 'sale_code' ? 'selected' : ''; ?>>Mã hóa đơn</option>
                <option value="customer_name" <?php echo $sort_by === 'customer_name' ? 'selected' : ''; ?>>Tên khách hàng</option>
                <option value="final_amount" <?php echo $sort_by === 'final_amount' ? 'selected' : ''; ?>>Tổng tiền</option>
                <option value="payment_method" <?php echo $sort_by === 'payment_method' ? 'selected' : ''; ?>>Phương thức thanh toán</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="sort_order">🔄 Thứ tự</label>
            <select name="sort_order" id="sort_order">
                <option value="DESC" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Giảm dần</option>
                <option value="ASC" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Tăng dần</option>
            </select>
        </div>
        
        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary">🔍 Lọc</button>
            <a href="?page=all_sales" class="btn btn-secondary">🔄 Reset</a>
        </div>
    </form>
</div>

<!-- Statistics Summary -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
    <div class="stat-card" style="background: var(--primary-gradient); color: white; padding: 1.5rem; border-radius: 12px; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold; margin-bottom: 0.5rem;">
            <?php echo number_format($stats['total_sales'] ?? 0); ?>
        </div>
        <div style="opacity: 0.9;">📋 Tổng hóa đơn</div>
    </div>
    <div class="stat-card" style="background: var(--success-gradient); color: white; padding: 1.5rem; border-radius: 12px; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold; margin-bottom: 0.5rem;">
            <?php echo number_format($stats['total_revenue'] ?? 0); ?>₫
        </div>
        <div style="opacity: 0.9;">💰 Tổng doanh thu</div>
    </div>
    <div class="stat-card" style="background: var(--info-gradient); color: white; padding: 1.5rem; border-radius: 12px; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold; margin-bottom: 0.5rem;">
            <?php echo number_format($stats['avg_sale_amount'] ?? 0); ?>₫
        </div>
        <div style="opacity: 0.9;">📊 Trung bình/hóa đơn</div>
    </div>
    <div class="stat-card" style="background: var(--warning-gradient); color: white; padding: 1.5rem; border-radius: 12px; text-align: center;">
        <div style="font-size: 1.5rem; font-weight: bold; margin-bottom: 0.5rem;">
            Trang <?php echo $page; ?>/<?php echo $total_pages; ?>
        </div>
        <div style="opacity: 0.9;">📄 Phân trang</div>
    </div>
</div>

<!-- Sales Table -->
<div class="data-table">
    <div style="background: var(--primary-gradient); color: white; padding: 1rem 1.5rem; font-weight: 600; display: flex; justify-content: space-between; align-items: center;">
        <span>📋 Danh sách hóa đơn (<?php echo number_format($total_records); ?> kết quả)</span>
        <div style="display: flex; gap: 0.5rem;">
            <button onclick="exportToExcel()" class="btn btn-small" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3);">
                📊 Xuất Excel
            </button>
            <a href="?page=sales" class="btn btn-small" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); text-decoration: none; color: white;">
                ➕ Tạo hóa đơn mới
            </a>
        </div>
    </div>
    
    <div style="overflow-x: auto;">
        <?php if (empty($sales)): ?>
            <div style="padding: 3rem; text-align: center; color: var(--text-secondary);">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📄</div>
                <div style="font-size: 1.2rem; margin-bottom: 0.5rem;">Không tìm thấy hóa đơn nào</div>
                <div>Thử thay đổi bộ lọc hoặc <a href="?page=sales">tạo hóa đơn mới</a></div>
            </div>
        <?php else: ?>
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: var(--bg-secondary); font-weight: 600;">
                    <tr>
                        <th style="padding: 1rem; text-align: left; border-bottom: 2px solid var(--border-color);">Mã HĐ</th>
                        <th style="padding: 1rem; text-align: left; border-bottom: 2px solid var(--border-color);">Ngày</th>
                        <th style="padding: 1rem; text-align: left; border-bottom: 2px solid var(--border-color);">Khách hàng</th>
                        <th style="padding: 1rem; text-align: right; border-bottom: 2px solid var(--border-color);">Số SP</th>
                        <th style="padding: 1rem; text-align: right; border-bottom: 2px solid var(--border-color);">Tổng tiền</th>
                        <th style="padding: 1rem; text-align: center; border-bottom: 2px solid var(--border-color);">PT thanh toán</th>
                        <th style="padding: 1rem; text-align: center; border-bottom: 2px solid var(--border-color);">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $sale): ?>
                        <tr style="border-bottom: 1px solid var(--border-color); transition: var(--transition);" 
                            onmouseover="this.style.background='var(--bg-tertiary)'" 
                            onmouseout="this.style.background='transparent'">
                            <td style="padding: 1rem;">
                                <strong style="color: var(--primary-color);"><?php echo $sale['sale_code']; ?></strong>
                            </td>
                            <td style="padding: 1rem;">
                                <div style="font-size: 0.9rem;"><?php echo formatDate($sale['sale_date']); ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-secondary);">
                                    bởi <?php echo htmlspecialchars($sale['cashier_name'] ?? 'N/A'); ?>
                                </div>
                            </td>
                            <td style="padding: 1rem;">
                                <div style="font-weight: 500;">
                                    <?php echo htmlspecialchars($sale['customer_name'] ?: ($sale['customer_name_db'] ?? 'Khách vãng lai')); ?>
                                </div>
                                <?php if ($sale['customer_phone']): ?>
                                    <div style="font-size: 0.85rem; color: var(--text-secondary);">
                                        📞 <?php echo htmlspecialchars($sale['customer_phone']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1rem; text-align: right;">
                                <span style="background: var(--bg-tertiary); padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 0.85rem;">
                                    <?php echo $sale['item_count']; ?> SP
                                </span>
                            </td>
                            <td style="padding: 1rem; text-align: right;">
                                <div style="font-weight: 600; color: var(--success-color); font-size: 1.1rem;">
                                    <?php echo number_format($sale['final_amount']); ?>₫
                                </div>
                                <?php if ($sale['discount_amount'] > 0): ?>
                                    <div style="font-size: 0.8rem; color: var(--text-secondary);">
                                        Giảm: <?php echo number_format($sale['discount_amount']); ?>₫
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <?php
                                    $paymentMethodValue = $sale['payment_method'];
                                    $paymentMethodDisplay = '-';
                                    $paymentMethodBgColor = 'var(--secondary-color)';

                                    switch ($paymentMethodValue) {
                                        case 'Tiền mặt':
                                            $paymentMethodDisplay = '💵';
                                            $paymentMethodBgColor = 'var(--success-color, #28a745)';
                                            break;
                                        case 'Thẻ tín dụng':
                                            $paymentMethodDisplay = '💳';
                                            $paymentMethodBgColor = 'var(--info-color, #17a2b8)';
                                            break;
                                        case 'Chuyển khoản':
                                            $paymentMethodDisplay = '🏦';
                                            $paymentMethodBgColor = 'var(--purple-color, #6f42c1)';
                                            break;
                                        case 'Ví điện tử':
                                            $paymentMethodDisplay = '📱';
                                            $paymentMethodBgColor = 'var(--warning-color, #ffc107)';
                                            break;
                                    }
                                ?>
                                <span style="background: <?php echo $paymentMethodBgColor; ?>; color: white; padding: 0.4rem 0.6rem; border-radius: 8px; font-size: 1.2rem;" 
                                      title="<?php echo htmlspecialchars($paymentMethodValue); ?>">
                                    <?php echo $paymentMethodDisplay; ?>
                                </span>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <div style="display: flex; gap: 0.3rem; justify-content: center;">
                                    <button class="btn btn-small btn-primary" onclick="viewSaleDetail('<?php echo $sale['sale_code']; ?>', <?php echo $sale['id']; ?>)" title="Xem chi tiết">
                                        👁️
                                    </button>
                                    <button class="btn btn-small btn-secondary" onclick="printInvoice(<?php echo $sale['id']; ?>)" title="In hóa đơn">
                                        🖨️
                                    </button>
                                    <?php if ($sale['notes']): ?>
                                        <button class="btn btn-small btn-info" onclick="showNotes('<?php echo htmlspecialchars(addslashes($sale['notes'])); ?>')" title="Xem ghi chú">
                                            📝
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
    <div style="display: flex; justify-content: center; align-items: center; gap: 1rem; margin-top: 2rem;">
        <?php
        $current_params = $_GET;
        unset($current_params['page']);
        $base_url = '?' . http_build_query($current_params) . '&page=' . urlencode($current_params['page'] ?? 'all_sales') . '&pg=';
        ?>
        
        <?php if ($page > 1): ?>
            <a href="<?php echo $base_url . '1'; ?>" class="btn btn-secondary">⏮️ Đầu</a>
            <a href="<?php echo $base_url . ($page - 1); ?>" class="btn btn-secondary">⬅️ Trước</a>
        <?php endif; ?>
        
        <span style="padding: 0.5rem 1rem; background: var(--bg-tertiary); border-radius: 8px; font-weight: 600;">
            Trang <?php echo $page; ?> / <?php echo $total_pages; ?>
        </span>
        
        <?php if ($page < $total_pages): ?>
            <a href="<?php echo $base_url . ($page + 1); ?>" class="btn btn-secondary">Tiếp ➡️</a>
            <a href="<?php echo $base_url . $total_pages; ?>" class="btn btn-secondary">Cuối ⏭️</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<script>
// View sale detail function
function viewSaleDetail(saleCode, saleId) {
    // You can implement a modal or redirect to detail page
    window.open(`ajax/get_sale_detail.php?id=${saleId}`, '_blank', 'width=800,height=600,scrollbars=yes');
}

// Print invoice function
function printInvoice(saleId) {
    window.open(`print_invoice.php?id=${saleId}`, '_blank');
}

// Show notes function
function showNotes(notes) {
    alert('Ghi chú:\n' + notes);
}

// Export to Excel function
function exportToExcel() {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('export', 'excel');
    window.location.href = currentUrl.toString();
}

// Auto-submit form on select change
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const selects = form.querySelectorAll('select');
    
    selects.forEach(select => {
        select.addEventListener('change', function() {
            form.submit();
        });
    });
});
</script>
