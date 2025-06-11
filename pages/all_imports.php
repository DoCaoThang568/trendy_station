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

<h1 class="page-title">📦 Tất cả phiếu nhập</h1>

<!-- Filter Form -->
<div class="form-container" style="margin-bottom: 2rem;">
    <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
        <input type="hidden" name="page" value="all_imports">
        
        <div class="form-group">
            <label for="search">🔍 Tìm kiếm</label>
            <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Mã phiếu nhập, nhà cung cấp...">
        </div>
        
        <div class="form-group">
            <label for="status">📋 Trạng thái nhập</label>
            <select name="status" id="status">
                <option value="">-- Tất cả --</option>
                <option value="Hoàn thành" <?php echo $status === 'Hoàn thành' ? 'selected' : ''; ?>>✅ Hoàn thành</option>
                <option value="Đang xử lý" <?php echo $status === 'Đang xử lý' ? 'selected' : ''; ?>>⏳ Đang xử lý</option>
                <option value="Đã hủy" <?php echo $status === 'Đã hủy' ? 'selected' : ''; ?>>❌ Đã hủy</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="payment_status">💰 Trạng thái thanh toán</label>
            <select name="payment_status" id="payment_status">
                <option value="">-- Tất cả --</option>
                <option value="paid" <?php echo $payment_status === 'paid' ? 'selected' : ''; ?>>💰 Đã thanh toán</option>
                <option value="partial" <?php echo $payment_status === 'partial' ? 'selected' : ''; ?>>💸 Thanh toán một phần</option>
                <option value="pending" <?php echo $payment_status === 'pending' ? 'selected' : ''; ?>>⏳ Chưa thanh toán</option>
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
                <option value="import_date" <?php echo $sort_by === 'import_date' ? 'selected' : ''; ?>>Ngày nhập</option>
                <option value="import_code" <?php echo $sort_by === 'import_code' ? 'selected' : ''; ?>>Mã phiếu nhập</option>
                <option value="supplier_name" <?php echo $sort_by === 'supplier_name' ? 'selected' : ''; ?>>Nhà cung cấp</option>
                <option value="total_amount" <?php echo $sort_by === 'total_amount' ? 'selected' : ''; ?>>Tổng tiền</option>
                <option value="status" <?php echo $sort_by === 'status' ? 'selected' : ''; ?>>Trạng thái</option>
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
            <a href="?page=all_imports" class="btn btn-secondary">🔄 Reset</a>
        </div>
    </form>
</div>

<!-- Statistics Summary -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
    <div class="stat-card" style="background: var(--success-gradient); color: white; padding: 1.5rem; border-radius: 12px; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold; margin-bottom: 0.5rem;">
            <?php echo number_format($stats['total_imports'] ?? 0); ?>
        </div>
        <div style="opacity: 0.9;">📦 Tổng phiếu nhập</div>
    </div>
    <div class="stat-card" style="background: var(--warning-gradient); color: white; padding: 1.5rem; border-radius: 12px; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold; margin-bottom: 0.5rem;">
            <?php echo number_format($stats['total_cost'] ?? 0); ?>₫
        </div>
        <div style="opacity: 0.9;">💸 Tổng chi phí</div>
    </div>
    <div class="stat-card" style="background: var(--info-gradient); color: white; padding: 1.5rem; border-radius: 12px; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold; margin-bottom: 0.5rem;">
            <?php echo number_format($stats['avg_import_amount'] ?? 0); ?>₫
        </div>
        <div style="opacity: 0.9;">📊 Trung bình/phiếu nhập</div>
    </div>
    <div class="stat-card" style="background: var(--primary-gradient); color: white; padding: 1.5rem; border-radius: 12px; text-align: center;">
        <div style="font-size: 1.5rem; font-weight: bold; margin-bottom: 0.5rem;">
            Trang <?php echo $page; ?>/<?php echo $total_pages; ?>
        </div>
        <div style="opacity: 0.9;">📄 Phân trang</div>
    </div>
</div>

<!-- Imports Table -->
<div class="data-table">
    <div style="background: var(--success-gradient); color: white; padding: 1rem 1.5rem; font-weight: 600; display: flex; justify-content: space-between; align-items: center;">
        <span>📦 Danh sách phiếu nhập (<?php echo number_format($total_records); ?> kết quả)</span>
        <div style="display: flex; gap: 0.5rem;">
            <button onclick="exportToExcel()" class="btn btn-small" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3);">
                📊 Xuất Excel
            </button>
            <a href="?page=imports" class="btn btn-small" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); text-decoration: none; color: white;">
                ➕ Tạo phiếu nhập mới
            </a>
        </div>
    </div>
    
    <div style="overflow-x: auto;">
        <?php if (empty($imports)): ?>
            <div style="padding: 3rem; text-align: center; color: var(--text-secondary);">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
                <div style="font-size: 1.2rem; margin-bottom: 0.5rem;">Không tìm thấy phiếu nhập nào</div>
                <div>Thử thay đổi bộ lọc hoặc <a href="?page=imports">tạo phiếu nhập mới</a></div>
            </div>
        <?php else: ?>
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: var(--bg-secondary); font-weight: 600;">
                    <tr>
                        <th style="padding: 1rem; text-align: left; border-bottom: 2px solid var(--border-color);">Mã PN</th>
                        <th style="padding: 1rem; text-align: left; border-bottom: 2px solid var(--border-color);">Ngày nhập</th>
                        <th style="padding: 1rem; text-align: left; border-bottom: 2px solid var(--border-color);">Nhà cung cấp</th>
                        <th style="padding: 1rem; text-align: right; border-bottom: 2px solid var(--border-color);">Số SP</th>
                        <th style="padding: 1rem; text-align: right; border-bottom: 2px solid var(--border-color);">Tổng tiền</th>
                        <th style="padding: 1rem; text-align: center; border-bottom: 2px solid var(--border-color);">Trạng thái</th>
                        <th style="padding: 1rem; text-align: center; border-bottom: 2px solid var(--border-color);">Thanh toán</th>
                        <th style="padding: 1rem; text-align: center; border-bottom: 2px solid var(--border-color);">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($imports as $import): ?>
                        <tr style="border-bottom: 1px solid var(--border-color); transition: var(--transition);" 
                            onmouseover="this.style.background='var(--bg-tertiary)'" 
                            onmouseout="this.style.background='transparent'">
                            <td style="padding: 1rem;">
                                <strong style="color: var(--success-color);"><?php echo $import['import_code']; ?></strong>
                            </td>
                            <td style="padding: 1rem;">
                                <div style="font-size: 0.9rem;"><?php echo formatDate($import['import_date']); ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-secondary);">
                                    bởi <?php echo htmlspecialchars($import['created_by'] ?? 'N/A'); ?>
                                </div>
                            </td>
                            <td style="padding: 1rem;">
                                <div style="font-weight: 500;">
                                    <?php echo htmlspecialchars($import['supplier_name'] ?: ($import['supplier_name_db'] ?? 'N/A')); ?>
                                </div>
                                <?php if ($import['supplier_phone_db']): ?>
                                    <div style="font-size: 0.85rem; color: var(--text-secondary);">
                                        📞 <?php echo htmlspecialchars($import['supplier_phone_db']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1rem; text-align: right;">
                                <span style="background: var(--bg-tertiary); padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 0.85rem;">
                                    <?php echo $import['item_count']; ?> SP
                                </span>
                            </td>
                            <td style="padding: 1rem; text-align: right;">
                                <div style="font-weight: 600; color: var(--success-color); font-size: 1.1rem;">
                                    <?php echo number_format($import['total_amount']); ?>₫
                                </div>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <?php
                                    $statusBgColor = 'var(--secondary-color)';
                                    $statusDisplay = htmlspecialchars($import['status']);
                                    switch($import['status']) {
                                        case 'Hoàn thành': 
                                            $statusDisplay = '✅';
                                            $statusBgColor = 'var(--success-color)'; 
                                            break;
                                        case 'Đang xử lý': 
                                            $statusDisplay = '⏳';
                                            $statusBgColor = 'var(--warning-color)'; 
                                            break;
                                        case 'Đã hủy': 
                                            $statusDisplay = '❌';
                                            $statusBgColor = 'var(--danger-color)'; 
                                            break;
                                    }
                                ?>
                                <span style="background: <?php echo $statusBgColor; ?>; color: white; padding: 0.4rem 0.6rem; border-radius: 8px; font-size: 1.2rem;" 
                                      title="<?php echo htmlspecialchars($import['status']); ?>">
                                    <?php echo $statusDisplay; ?>
                                </span>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <?php
                                    $paymentBgColor = 'var(--secondary-color)';
                                    $paymentDisplay = '?';
                                    $paymentTitle = 'Không rõ';
                                    switch($import['payment_status'] ?? 'pending') {
                                        case 'paid': 
                                            $paymentDisplay = '💰';
                                            $paymentBgColor = 'var(--success-color)';
                                            $paymentTitle = 'Đã thanh toán';
                                            break;
                                        case 'partial': 
                                            $paymentDisplay = '💸';
                                            $paymentBgColor = 'var(--warning-color)';
                                            $paymentTitle = 'Thanh toán một phần';
                                            break;
                                        case 'pending': 
                                            $paymentDisplay = '⏳';
                                            $paymentBgColor = 'var(--danger-color)';
                                            $paymentTitle = 'Chưa thanh toán';
                                            break;
                                    }
                                ?>
                                <span style="background: <?php echo $paymentBgColor; ?>; color: white; padding: 0.4rem 0.6rem; border-radius: 8px; font-size: 1.2rem;" 
                                      title="<?php echo $paymentTitle; ?>">
                                    <?php echo $paymentDisplay; ?>
                                </span>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <div style="display: flex; gap: 0.3rem; justify-content: center;">
                                    <button class="btn btn-small btn-primary" onclick="viewImportDetail(<?php echo $import['id']; ?>)" title="Xem chi tiết">
                                        👁️
                                    </button>
                                    <button class="btn btn-small btn-secondary" onclick="printImport(<?php echo $import['id']; ?>)" title="In phiếu nhập">
                                        🖨️
                                    </button>
                                    <?php if ($import['notes']): ?>
                                        <button class="btn btn-small btn-info" onclick="showNotes('<?php echo htmlspecialchars(addslashes($import['notes'])); ?>')" title="Xem ghi chú">
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
        unset($current_params['pg']);
        $base_url = '?' . http_build_query($current_params) . '&pg=';
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
// View import detail function
function viewImportDetail(importId) {
    // You can implement a modal or redirect to detail page
    window.open(`ajax/get_import_detail.php?id=${importId}`, '_blank', 'width=800,height=600,scrollbars=yes');
}

// Print import function
function printImport(importId) {
    window.open(`print_import.php?id=${importId}`, '_blank');
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
