<?php
/**
 * Print Invoice Page - Improved UI
 */

require_once 'config/database.php';

$saleId = $_GET['id'] ?? 0;

if (!$saleId) {
    die('❌ Không tìm thấy hóa đơn');
}

// Get sale info
$sale = fetchOne("
    SELECT s.*, c.name as customer_name_db, c.phone as customer_phone_db, c.address as customer_address, 
           s.cashier_name as created_by,
           s.total_amount as subtotal
    FROM sales s 
    LEFT JOIN customers c ON s.customer_id = c.id 
    WHERE s.id = ?
", [$saleId]);

if (!$sale) {
    die('❌ Không tìm thấy hóa đơn');
}

// Get sale details
$saleDetails = fetchAll("
    SELECT sd.*, p.product_code as product_code 
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
            font-family: 'Arial', 'Times New Roman', serif;
            line-height: 1.5;
            color: #2c3e50;
            background: #f8f9fa;
        }
        
        .invoice {
            max-width: 21cm;
            margin: 1rem auto;
            padding: 0;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.15);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .invoice-content {
            padding: 2rem;
        }
        
        .invoice-header {
            text-align: center;
            margin-bottom: 2.5rem;
            position: relative;
        }
        
        .invoice-header::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: linear-gradient(45deg, #3498db, #2980b9);
            border-radius: 2px;
        }
        
        .company-name {
            font-size: 2.2rem;
            font-weight: 900;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            letter-spacing: 1px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        
        .company-tagline {
            font-size: 1rem;
            color: #3498db;
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .company-info {
            color: #7f8c8d;
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        .invoice-title {
            text-align: center;
            font-size: 1.8rem;
            font-weight: 700;
            color: #e74c3c;
            margin: 2rem 0 1.5rem 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
        }
        
        .invoice-title::before {
            content: '📋';
            margin-right: 10px;
        }
        
        .invoice-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .meta-section {
            position: relative;
        }
        
        .meta-section h4 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            font-weight: 700;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #3498db;
            display: inline-block;
        }
        
        .meta-item {
            display: flex;
            margin-bottom: 0.5rem;
            align-items: center;
        }
        
        .meta-label {
            font-weight: 600;
            color: #34495e;
            min-width: 100px;
            margin-right: 10px;
        }
        
        .meta-value {
            color: #2c3e50;
            flex: 1;
        }
        
        .payment-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: white;
        }
          .payment-cash { background: #27ae60; }
        .payment-card { background: #3498db; }
        .payment-bank { background: #9b59b6; }
        .payment-ewallet { background: #f39c12; }
        .payment-default { background: #7f8c8d; }
        
        .products-section {
            margin: 2rem 0;
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1rem;
            padding-left: 1rem;
            border-left: 4px solid #3498db;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .products-table th {
            background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
            color: white;
            padding: 1rem 0.75rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .products-table td {
            padding: 1rem 0.75rem;
            border-bottom: 1px solid #ecf0f1;
            vertical-align: top;
        }
        
        .products-table tbody tr {
            transition: background-color 0.2s ease;
        }
        
        .products-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .products-table tbody tr:nth-child(even) {
            background-color: #fdfdfd;
        }
        
        .product-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }
        
        .product-code {
            font-size: 0.85rem;
            color: #7f8c8d;
            font-style: italic;
        }
        
        .products-table .text-right {
            text-align: right;
        }
        
        .products-table .text-center {
            text-align: center;
        }
        
        .quantity-cell {
            background: #ecf0f1;
            font-weight: 600;
            border-radius: 4px;
            color: #2c3e50;
        }
        
        .price-cell {
            font-weight: 600;
            color: #27ae60;
        }
        
        .total-cell {
            font-weight: 700;
            color: #e74c3c;
            font-size: 1.05rem;
        }
        
        .summary-section {
            display: flex;
            justify-content: flex-end;
            margin: 2rem 0;
        }
        
        .total-summary {
            width: 350px;
            background: white;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1.25rem;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .summary-row:last-child {
            border-bottom: none;
        }
        
        .summary-label {
            font-weight: 600;
            color: #34495e;
        }
        
        .summary-value {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .discount-row {
            background: #fff5f5;
        }
        
        .discount-row .summary-value {
            color: #e74c3c;
        }
        
        .final-total {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white !important;
            font-size: 1.2rem;
            font-weight: 700;
        }
        
        .final-total .summary-label,
        .final-total .summary-value {
            color: white;
        }
        
        .notes-section {
            margin-top: 2rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, #fff9e6 0%, #fff3cd 100%);
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            border-left: 4px solid #f39c12;
        }
        
        .notes-title {
            font-weight: 700;
            color: #e67e22;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        .notes-content {
            color: #8b4513;
            line-height: 1.6;
        }
        
        .invoice-footer {
            margin-top: 3rem;
            text-align: center;
            padding: 2rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-top: 3px solid #3498db;
        }
        
        .footer-thanks {
            font-size: 1.3rem;
            font-weight: 700;
            color: #e74c3c;
            margin-bottom: 1rem;
        }
        
        .footer-note {
            color: #7f8c8d;
            margin-bottom: 1rem;
            font-style: italic;
        }
        
        .footer-contact {
            color: #3498db;
            font-weight: 600;
        }
        
        .print-actions {
            text-align: center;
            margin: 2rem 0;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .print-btn {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin: 0 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        
        .print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }
        
        .close-btn {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            box-shadow: 0 4px 15px rgba(149, 165, 166, 0.3);
        }
        
        .close-btn:hover {
            box-shadow: 0 6px 20px rgba(149, 165, 166, 0.4);
        }
        
        @media print {
            body {
                background: white;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .print-actions {
                display: none !important;
            }
            
            .invoice {
                box-shadow: none;
                margin: 0;
                border-radius: 0;
                max-width: none;
            }
            
            .invoice-content {
                padding: 1rem;
            }
            
            .invoice-meta {
                background: #f8f9fa !important;
                -webkit-print-color-adjust: exact;
            }
            
            .products-table th {
                background: #34495e !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
            }
            
            .final-total {
                background: #2c3e50 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
            }
            
            .notes-section {
                background: #fff9e6 !important;
                -webkit-print-color-adjust: exact;
            }
            
            .invoice-footer {
                background: #f8f9fa !important;
                -webkit-print-color-adjust: exact;
            }
            
            .invoice-header::after {
                background: #3498db !important;
            }
        }
        
        @media screen and (max-width: 768px) {
            .invoice {
                margin: 0.5rem;
                border-radius: 0;
            }
            
            .invoice-content {
                padding: 1rem;
            }
            
            .invoice-meta {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .products-table {
                font-size: 0.85rem;
            }
            
            .products-table th,
            .products-table td {
                padding: 0.5rem 0.4rem;
            }
            
            .total-summary {
                width: 100%;
            }
            
            .company-name {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <!-- Print Actions -->
    <div class="print-actions">
        <button class="print-btn" onclick="window.print()">
            🖨️ In hóa đơn
        </button>
        <button class="print-btn close-btn" onclick="window.close()">
            ❌ Đóng
        </button>
    </div>

    <div class="invoice">
        <div class="invoice-content">
            <!-- Header -->
            <div class="invoice-header">
                <div class="company-name">THE TRENDY STATION</div>
                <div class="company-tagline">Fashion Excellence</div>
                <div class="company-info">
                    📍 123 Nguyễn Huệ, Quận 1, TP.HCM<br>
                    ☎️ 0901.234.567 | 📧 info@trendystation.vn<br>
                    🌐 www.trendystation.vn
                </div>
            </div>

            <!-- Invoice Title -->
            <div class="invoice-title">Hóa đơn bán hàng</div>

            <!-- Invoice Meta Information -->
            <div class="invoice-meta">
                <div class="meta-section">
                    <h4>📋 Thông tin hóa đơn</h4>
                    <div class="meta-item">
                        <span class="meta-label">Số hóa đơn:</span>
                        <span class="meta-value"><strong><?php echo htmlspecialchars($sale['sale_code']); ?></strong></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Ngày bán:</span>
                        <span class="meta-value"><?php echo formatVietnameseDate($sale['sale_date']); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Nhân viên:</span>
                        <span class="meta-value"><?php echo htmlspecialchars($sale['created_by'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Thanh toán:</span>
                        <span class="meta-value">                            <span class="payment-badge <?php 
                                switch(strtolower($sale['payment_method'])) {
                                    case 'cash':
                                    case 'tiền mặt': 
                                        echo 'payment-cash'; break;
                                    case 'card':
                                    case 'thẻ tín dụng': 
                                        echo 'payment-card'; break;
                                    case 'transfer':
                                    case 'chuyển khoản':
                                    case 'bank_transfer': 
                                        echo 'payment-bank'; break;
                                    case 'e-wallet':
                                    case 'ví điện tử':
                                        echo 'payment-ewallet'; break;
                                    default: 
                                        echo 'payment-default';
                                }
                            ?>">
                                <?php 
                                switch(strtolower($sale['payment_method'])) {
                                    case 'cash':
                                    case 'tiền mặt': 
                                        echo '💵 Tiền mặt'; break;
                                    case 'card':
                                    case 'thẻ tín dụng': 
                                        echo '💳 Thẻ tín dụng'; break;
                                    case 'transfer':
                                    case 'chuyển khoản':
                                    case 'bank_transfer': 
                                        echo '🏦 Chuyển khoản'; break;
                                    case 'e-wallet':
                                    case 'ví điện tử':
                                        echo '📱 Ví điện tử'; break;
                                    default: 
                                        echo htmlspecialchars($sale['payment_method']);
                                }
                                ?>
                            </span>
                        </span>
                    </div>
                </div>
                
                <div class="meta-section">
                    <h4>👤 Thông tin khách hàng</h4>
                    <div class="meta-item">
                        <span class="meta-label">Tên khách:</span>
                        <span class="meta-value"><strong><?php echo htmlspecialchars($sale['customer_name'] ?: ($sale['customer_name_db'] ?? 'Khách lẻ')); ?></strong></span>
                    </div>
                    <?php if (!empty($sale['customer_phone']) || !empty($sale['customer_phone_db'])): ?>
                        <div class="meta-item">
                            <span class="meta-label">Điện thoại:</span>
                            <span class="meta-value"><?php echo htmlspecialchars($sale['customer_phone'] ?: ($sale['customer_phone_db'] ?? 'N/A')); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($sale['customer_address'])): ?>
                        <div class="meta-item">
                            <span class="meta-label">Địa chỉ:</span>
                            <span class="meta-value"><?php echo htmlspecialchars($sale['customer_address']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Products Section -->
            <div class="products-section">
                <h3 class="section-title">Chi tiết sản phẩm</h3>
                <table class="products-table">
                    <thead>
                        <tr>
                            <th style="width: 45%;">Sản phẩm</th>
                            <th style="width: 15%;" class="text-center">Số lượng</th>
                            <th style="width: 20%;" class="text-right">Đơn giá</th>
                            <th style="width: 20%;" class="text-right">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($saleDetails as $detail): ?>
                            <?php $item_total = $detail['quantity'] * $detail['unit_price']; ?>
                            <tr>
                                <td>
                                    <div class="product-name"><?php echo htmlspecialchars($detail['product_name']); ?></div>
                                    <?php if (!empty($detail['product_code'])): ?>
                                        <div class="product-code">Mã: <?php echo htmlspecialchars($detail['product_code']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center quantity-cell"><?php echo number_format($detail['quantity']); ?></td>
                                <td class="text-right price-cell"><?php echo number_format($detail['unit_price']); ?>₫</td>
                                <td class="text-right total-cell"><?php echo number_format($item_total); ?>₫</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Summary Section -->
            <div class="summary-section">
                <div class="total-summary">
                    <div class="summary-row">
                        <span class="summary-label">Tạm tính:</span>
                        <span class="summary-value"><?php echo number_format($sale['subtotal'] ?? 0); ?>₫</span>
                    </div>
                    
                    <?php if (!empty($sale['discount_amount']) && $sale['discount_amount'] > 0): ?>
                        <div class="summary-row discount-row">
                            <span class="summary-label">
                                Giảm giá<?php echo ($sale['discount_percent'] > 0 ? ' (' . $sale['discount_percent'] . '%)' : ''); ?>:
                            </span>
                            <span class="summary-value">-<?php echo number_format($sale['discount_amount']); ?>₫</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="summary-row final-total">
                        <span class="summary-label">TỔNG CỘNG:</span>
                        <span class="summary-value"><?php echo number_format($sale['final_amount'] ?? 0); ?>₫</span>
                    </div>
                </div>
            </div>

            <!-- Notes Section -->
            <?php if ($sale['notes']): ?>
                <div class="notes-section">
                    <div class="notes-title">📝 Ghi chú:</div>
                    <div class="notes-content"><?php echo htmlspecialchars($sale['notes']); ?></div>
                </div>
            <?php endif; ?>

            <!-- Footer -->
            <div class="invoice-footer">
                <div class="footer-thanks">🙏 CẢM ƠN QUÝ KHÁCH ĐÃ MUA HÀNG!</div>
                <div class="footer-note">Vui lòng giữ hóa đơn để được bảo hành và đổi trả sản phẩm</div>
                <div class="footer-contact">
                    🌐 www.trendystation.vn | 📱 Facebook: The Trendy Station | 📷 Instagram: @trendystation
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto focus for better print experience
        window.onload = function() {
            document.body.focus();
        };
        
        // Optional auto print
        // setTimeout(() => {
        //     window.print();
        // }, 1000);
        
        // Handle after print
        window.onafterprint = function() {
            // Uncomment to auto close after print
            // window.close();
        };
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
            if (e.key === 'Escape') {
                window.close();
            }
        });
    </script>
</body>
</html>