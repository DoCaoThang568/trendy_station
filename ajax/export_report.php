<?php
require_once '../config/database.php';

// Get parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$report_type = $_GET['report_type'] ?? 'sales';

// Set content type for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="bao_cao_' . $report_type . '_' . $start_date . '_' . $end_date . '.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

try {
    switch ($report_type) {
        case 'sales':
            exportSalesReport($output, $start_date, $end_date);
            break;
        case 'inventory':
            exportInventoryReport($output);
            break;
        case 'profit':
            exportProfitReport($output, $start_date, $end_date);
            break;
        case 'overview':
            exportOverviewReport($output, $start_date, $end_date);
            break;
        default:
            exportSalesReport($output, $start_date, $end_date);
    }
} catch (Exception $e) {
    fputcsv($output, ['Lỗi', $e->getMessage()]);
}

fclose($output);

function exportSalesReport($output, $start_date, $end_date) {
    global $pdo;
    
    // Header
    fputcsv($output, ['BÁO CÁO BÁN HÀNG']);
    fputcsv($output, ['Từ ngày', $start_date, 'Đến ngày', $end_date]);
    fputcsv($output, []); // Empty row
    
    // Sales summary
    $sales_stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_sales,
            SUM(total_amount) as total_revenue,
            AVG(total_amount) as avg_sale_amount
        FROM sales 
        WHERE DATE(created_at) BETWEEN ? AND ?
    ");
    $sales_stmt->execute([$start_date, $end_date]);
    $summary = $sales_stmt->fetch();
    
    fputcsv($output, ['TỔNG QUAN']);
    fputcsv($output, ['Tổng số đơn hàng', $summary['total_sales']]);
    fputcsv($output, ['Tổng doanh thu', number_format($summary['total_revenue'], 0, ',', '.')]);
    fputcsv($output, ['Giá trị đơn hàng trung bình', number_format($summary['avg_sale_amount'], 0, ',', '.')]);
    fputcsv($output, []); // Empty row
    
    // Daily sales
    fputcsv($output, ['DOANH THU THEO NGÀY']);
    fputcsv($output, ['Ngày', 'Số đơn hàng', 'Doanh thu']);
    
    $daily_stmt = $pdo->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as sales_count,
            SUM(total_amount) as daily_revenue
        FROM sales 
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date
    ");
    $daily_stmt->execute([$start_date, $end_date]);
    
    while ($row = $daily_stmt->fetch()) {
        fputcsv($output, [
            $row['date'],
            $row['sales_count'],
            number_format($row['daily_revenue'], 0, ',', '.')
        ]);
    }
    
    fputcsv($output, []); // Empty row
    
    // Top products
    fputcsv($output, ['TOP SẢN PHẨM BÁN CHẠY']);
    fputcsv($output, ['STT', 'Mã sản phẩm', 'Tên sản phẩm', 'Số lượng bán', 'Doanh thu']);
    
    $products_stmt = $pdo->prepare("
        SELECT 
            p.code as product_code,
            p.name as product_name,
            SUM(sd.quantity) as total_sold,
            SUM(sd.total_price) as total_revenue
        FROM sale_details sd
        JOIN products p ON sd.product_id = p.id
        JOIN sales s ON sd.sale_id = s.id
        WHERE DATE(s.created_at) BETWEEN ? AND ?
        GROUP BY p.id, p.name, p.code
        ORDER BY total_sold DESC
        LIMIT 20
    ");
    $products_stmt->execute([$start_date, $end_date]);
    
    $stt = 1;
    while ($row = $products_stmt->fetch()) {
        fputcsv($output, [
            $stt++,
            $row['product_code'],
            $row['product_name'],
            $row['total_sold'],
            number_format($row['total_revenue'], 0, ',', '.')
        ]);
    }
}

function exportInventoryReport($output) {
    global $pdo;
    
    fputcsv($output, ['BÁO CÁO TỒN KHO']);
    fputcsv($output, ['Ngày xuất', date('Y-m-d H:i:s')]);
    fputcsv($output, []); // Empty row
    
    // Low stock products
    fputcsv($output, ['SẢN PHẨM TỒN KHO THẤP']);
    fputcsv($output, ['Mã sản phẩm', 'Tên sản phẩm', 'Số lượng tồn', 'Trạng thái']);
    
    $stock_stmt = $pdo->prepare("
        SELECT 
            code, name, stock_quantity,
            CASE 
                WHEN stock_quantity = 0 THEN 'Hết hàng'
                WHEN stock_quantity <= 5 THEN 'Rất ít'
                WHEN stock_quantity <= 10 THEN 'Sắp hết'
                ELSE 'Bình thường'
            END as stock_status
        FROM products 
        WHERE stock_quantity <= 20
        ORDER BY stock_quantity ASC, name ASC
    ");
    $stock_stmt->execute();
    
    while ($row = $stock_stmt->fetch()) {
        fputcsv($output, [
            $row['code'],
            $row['name'],
            $row['stock_quantity'],
            $row['stock_status']
        ]);
    }
    
    fputcsv($output, []); // Empty row
    
    // All products inventory
    fputcsv($output, ['TẤT CẢ SẢN PHẨM']);
    fputcsv($output, ['Mã sản phẩm', 'Tên sản phẩm', 'Giá bán', 'Số lượng tồn', 'Giá trị tồn kho']);
    
    $all_products_stmt = $pdo->prepare("
        SELECT code, name, price, stock_quantity, (price * stock_quantity) as inventory_value
        FROM products 
        ORDER BY name
    ");
    $all_products_stmt->execute();
    
    while ($row = $all_products_stmt->fetch()) {
        fputcsv($output, [
            $row['code'],
            $row['name'],
            number_format($row['price'], 0, ',', '.'),
            $row['stock_quantity'],
            number_format($row['inventory_value'], 0, ',', '.')
        ]);
    }
}

function exportProfitReport($output, $start_date, $end_date) {
    global $pdo;
    
    fputcsv($output, ['BÁO CÁO LỢI NHUẬN']);
    fputcsv($output, ['Từ ngày', $start_date, 'Đến ngày', $end_date]);
    fputcsv($output, []); // Empty row
    
    // Profit analysis
    $profit_stmt = $pdo->prepare("
        SELECT 
            SUM(sd.total_price) as total_sales,
            SUM(sd.quantity * p.purchase_price) as total_cost,
            (SUM(sd.total_price) - SUM(sd.quantity * p.purchase_price)) as total_profit
        FROM sale_details sd
        JOIN products p ON sd.product_id = p.id
        JOIN sales s ON sd.sale_id = s.id
        WHERE DATE(s.created_at) BETWEEN ? AND ?
    ");
    $profit_stmt->execute([$start_date, $end_date]);
    $profit = $profit_stmt->fetch();
    
    $profit_margin = $profit['total_sales'] > 0 ? ($profit['total_profit'] / $profit['total_sales']) * 100 : 0;
    
    fputcsv($output, ['TỔNG QUAN LỢI NHUẬN']);
    fputcsv($output, ['Tổng doanh thu', number_format($profit['total_sales'], 0, ',', '.')]);
    fputcsv($output, ['Tổng chi phí hàng hóa', number_format($profit['total_cost'], 0, ',', '.')]);
    fputcsv($output, ['Lợi nhuận', number_format($profit['total_profit'], 0, ',', '.')]);
    fputcsv($output, ['Tỷ suất lợi nhuận (%)', number_format($profit_margin, 2)]);
    fputcsv($output, []); // Empty row
    
    // Profit by product
    fputcsv($output, ['LỢI NHUẬN THEO SẢN PHẨM']);
    fputcsv($output, ['Mã SP', 'Tên sản phẩm', 'Số lượng bán', 'Doanh thu', 'Chi phí', 'Lợi nhuận', 'Tỷ suất LN (%)']);
    
    $product_profit_stmt = $pdo->prepare("
        SELECT 
            p.code,
            p.name,
            SUM(sd.quantity) as quantity_sold,
            SUM(sd.total_price) as revenue,
            SUM(sd.quantity * p.purchase_price) as cost,
            (SUM(sd.total_price) - SUM(sd.quantity * p.purchase_price)) as profit
        FROM sale_details sd
        JOIN products p ON sd.product_id = p.id
        JOIN sales s ON sd.sale_id = s.id
        WHERE DATE(s.created_at) BETWEEN ? AND ?
        GROUP BY p.id, p.code, p.name
        ORDER BY profit DESC
    ");
    $product_profit_stmt->execute([$start_date, $end_date]);
    
    while ($row = $product_profit_stmt->fetch()) {
        $product_margin = $row['revenue'] > 0 ? ($row['profit'] / $row['revenue']) * 100 : 0;
        fputcsv($output, [
            $row['code'],
            $row['name'],
            $row['quantity_sold'],
            number_format($row['revenue'], 0, ',', '.'),
            number_format($row['cost'], 0, ',', '.'),
            number_format($row['profit'], 0, ',', '.'),
            number_format($product_margin, 2)
        ]);
    }
}

function exportOverviewReport($output, $start_date, $end_date) {
    global $pdo;
    
    fputcsv($output, ['BÁO CÁO TỔNG QUAN']);
    fputcsv($output, ['Từ ngày', $start_date, 'Đến ngày', $end_date]);
    fputcsv($output, []); // Empty row
    
    // Overall stats
    $overview_stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM sales WHERE DATE(created_at) BETWEEN ? AND ?) as total_sales,
            (SELECT SUM(total_amount) FROM sales WHERE DATE(created_at) BETWEEN ? AND ?) as total_revenue,
            (SELECT COUNT(*) FROM imports WHERE DATE(created_at) BETWEEN ? AND ?) as total_imports,
            (SELECT SUM(total_amount) FROM imports WHERE DATE(created_at) BETWEEN ? AND ?) as total_import_cost,
            (SELECT COUNT(*) FROM customers) as total_customers,
            (SELECT COUNT(*) FROM products) as total_products,
            (SELECT COUNT(*) FROM products WHERE stock_quantity <= 10) as low_stock_products
    ");
    $overview_stmt->execute([$start_date, $end_date, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);
    $overview = $overview_stmt->fetch();
    
    fputcsv($output, ['CHỈ SỐ TỔNG QUAN']);
    fputcsv($output, ['Tổng số đơn bán hàng', $overview['total_sales']]);
    fputcsv($output, ['Tổng doanh thu', number_format($overview['total_revenue'], 0, ',', '.')]);
    fputcsv($output, ['Tổng số phiếu nhập', $overview['total_imports']]);
    fputcsv($output, ['Tổng chi phí nhập hàng', number_format($overview['total_import_cost'], 0, ',', '.')]);
    fputcsv($output, ['Tổng số khách hàng', $overview['total_customers']]);
    fputcsv($output, ['Tổng số sản phẩm', $overview['total_products']]);
    fputcsv($output, ['Sản phẩm sắp hết hàng', $overview['low_stock_products']]);
}
?>
