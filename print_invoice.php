<?php
/**
 * Print Invoice Page
 */

require_once 'config/database.php';

$saleId = $_GET['sale_id'] ?? 0;

if (!$saleId) {
    die('❌ Không tìm thấy hóa đơn');
}

// Get sale info
$sale = fetchOne("
    SELECT s.*, c.name as customer_name_db, c.phone as customer_phone_db, c.address as customer_address 
    FROM sales s 
    LEFT JOIN customers c ON s.customer_id = c.id 
    WHERE s.id = ?
", [$saleId]);

if (!$sale) {
    die('❌ Không tìm thấy hóa đơn');
}

// Get sale details
$saleDetails = fetchAll("
    SELECT sd.*, p.product_code as product_code, p.unit 
    FROM sale_details sd 
    LEFT JOIN products p ON sd.product_id = p.id 
    WHERE sd.sale_id = ? 
    ORDER BY sd.id
", [$saleId]);

// Format date helper
function formatVietnameseDate($dateString) {
    return date('d/m/Y H:i', strtotime($dateString));
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn <?php echo $sale['sale_code']; ?> - The Trendy Station</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.4;
            color: #333;
            background: white;
        }
        
        .invoice {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            background: white;
        }
        
        .invoice-header {
            text-align: center;
            margin-bottom: 2rem;
            border-bottom: 3px solid #667eea;
            padding-bottom: 1rem;
        }
        
        .company-name {
            font-size: 2rem;
            font-weight: 800;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .company-info {
            color: #666;
            font-size: 0.9rem;
        }
        
        .invoice-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #333;
            margin: 1.5rem 0 1rem 0;
        }
        
        .invoice-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .info-section h4 {
            color: #667eea;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }
        
        .info-section p {
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }
        
        .products-table th,
        .products-table td {
            border: 1px solid #ddd;
            padding: 0.75rem;
            text-align: left;
        }
        
        .products-table th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }
        
        .products-table td:nth-child(2),
        .products-table td:nth-child(3),
        .products-table td:nth-child(4) {
            text-align: right;
        }
        
        .products-table th:nth-child(2),
        .products-table th:nth-child(3),
        .products-table th:nth-child(4) {
            text-align: right;
        }
        
        .total-section {
            margin-left: auto;
            width: 300px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .total-row.final {
            border-bottom: none;
            border-top: 2px solid #667eea;
            padding-top: 1rem;
            font-weight: 700;
            font-size: 1.2rem;
            color: #667eea;
        }
        
        .payment-info {
            margin-top: 2rem;
            padding: 1rem;
            background: #f8f9ff;
            border-left: 4px solid #667eea;
        }
        
        .footer {
            margin-top: 3rem;
            text-align: center;
            color: #666;
            font-size: 0.9rem;
            border-top: 1px solid #eee;
            padding-top: 2rem;
        }
        
        .no-print {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .print-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            margin-right: 1rem;
        }
        
        .print-btn:hover {
            background: #5a6fd8;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .invoice {
                margin: 0;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="print-btn" onclick="window.print()">🖨️ In hóa đơn</button>
        <button class="print-btn" style="background: #6c757d;" onclick="window.close()">❌ Đóng</button>
    </div>

    <div class="invoice">
        <!-- Header -->
        <div class="invoice-header">
            <div class="company-name">THE TRENDY STATION</div>
            <div class="company-info">
                Shop Thời Trang Cao Cấp<br>
                📍 123 Nguyễn Huệ, Q.1, TP.HCM | ☎️ 0901.234.567 | 📧 info@trendystation.vn
            </div>
        </div>

        <!-- Invoice Title -->
        <div class="invoice-title">HÓA ĐƠN BÁN HÀNG</div>

        <!-- Invoice Info -->
        <div class="invoice-info">
            <div class="info-section">
                <h4>📋 Thông tin hóa đơn</h4>
                <p><strong>Số hóa đơn:</strong> <?php echo $sale['sale_code']; ?></p>
                <p><strong>Ngày bán:</strong> <?php echo formatVietnameseDate($sale['sale_date']); ?></p>
                <p><strong>Nhân viên:</strong> <?php echo htmlspecialchars($sale['created_by']); ?></p>
                <p><strong>Thanh toán:</strong> 
                    <?php 
                    switch($sale['payment_method']) {
                        case 'cash': echo '💵 Tiền mặt'; break;
                        case 'card': echo '💳 Thẻ'; break;
                        case 'transfer': echo '🏦 Chuyển khoản'; break;
                        default: echo $sale['payment_method'];
                    }
                    ?>
                </p>
            </div>
            
            <div class="info-section">
                <h4>👤 Thông tin khách hàng</h4>
                <p><strong>Tên:</strong> <?php echo htmlspecialchars($sale['customer_name'] ?: $sale['customer_name_db']); ?></p>
                <?php if ($sale['customer_phone'] || $sale['customer_phone_db']): ?>
                    <p><strong>Điện thoại:</strong> <?php echo htmlspecialchars($sale['customer_phone'] ?: $sale['customer_phone_db']); ?></p>
                <?php endif; ?>
                <?php if ($sale['customer_address']): ?>
                    <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($sale['customer_address']); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Products Table -->
        <table class="products-table">
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>SL</th>
                    <th>Đơn giá</th>
                    <th>Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($saleDetails as $detail): ?>
                    <?php $subtotal = $detail['quantity'] * $detail['unit_price']; ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($detail['product_name']); ?></strong>
                            <?php if ($detail['product_code']): ?>
                                <br><small style="color: #666;">Mã: <?php echo htmlspecialchars($detail['product_code']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo number_format($detail['quantity']); ?><?php echo $detail['unit'] ? ' ' . $detail['unit'] : ''; ?></td>
                        <td><?php echo number_format($detail['unit_price']); ?>₫</td>
                        <td><strong><?php echo number_format($subtotal); ?>₫</strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Total Section -->
        <div class="total-section">
            <div class="total-row">
                <span>Tạm tính:</span>
                <span><?php echo number_format($sale['subtotal']); ?>₫</span>
            </div>
            
            <?php if ($sale['discount_percent'] > 0): ?>
                <div class="total-row">
                    <span>Giảm giá (<?php echo $sale['discount_percent']; ?>%):</span>
                    <span style="color: #dc3545;">-<?php echo number_format($sale['discount_amount']); ?>₫</span>
                </div>
            <?php endif; ?>
            
            <div class="total-row final">
                <span>TỔNG CỘNG:</span>
                <span><?php echo number_format($sale['total_amount']); ?>₫</span>
            </div>
        </div>

        <!-- Payment Info -->
        <?php if ($sale['notes']): ?>
            <div class="payment-info">
                <h4 style="margin-bottom: 0.5rem; color: #667eea;">📝 Ghi chú:</h4>
                <p><?php echo htmlspecialchars($sale['notes']); ?></p>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <p><strong>CẢM ƠN QUÝ KHÁCH ĐÃ MUA HÀNG!</strong></p>
            <p>Vui lòng giữ hóa đơn để được bảo hành và đổi trả sản phẩm</p>
            <p style="margin-top: 1rem;">
                🌐 www.trendystation.vn | 📱 Facebook: The Trendy Station | 📷 Instagram: @trendystation
            </p>
        </div>
    </div>

    <script>
        // Auto print when page loads (optional)
        // window.onload = function() {
        //     setTimeout(() => {
        //         window.print();
        //     }, 1000);
        // };
        
        // Close window after print
        window.onafterprint = function() {
            // window.close();
        };
    </script>
</body>
</html>
