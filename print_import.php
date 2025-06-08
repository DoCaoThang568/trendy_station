<?php
require_once 'config/database.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID phiếu nhập không hợp lệ');
}

$import_id = (int)$_GET['id'];

try {
    // Get import info
    $stmt = $pdo->prepare("
        SELECT i.*, s.name as supplier_name, s.phone as supplier_phone,
               s.address as supplier_address, s.email as supplier_email
        FROM imports i
        LEFT JOIN suppliers s ON i.supplier_id = s.id
        WHERE i.id = ?
    ");
    $stmt->execute([$import_id]);
    $import = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$import) {
        die('Không tìm thấy phiếu nhập');
    }
    
    // Get import details
    $stmt = $pdo->prepare("
        SELECT id.*, p.name as product_name, p.product_code as product_code
        FROM import_details id
        JOIN products p ON id.product_id = p.id
        WHERE id.import_id = ?
        ORDER BY p.name
    ");
    $stmt->execute([$import_id]);
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    die('Lỗi: ' . $e->getMessage());
}

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
    <title>Phiếu Nhập Hàng #<?= $import['id'] ?> - The Trendy Station</title>
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
        
        .import-invoice {
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
            background: linear-gradient(45deg, #e74c3c, #c0392b);
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
            color: #e74c3c;
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
            content: '📦';
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
            border-bottom: 2px solid #e74c3c;
            display: inline-block;
        }
        
        .meta-item {
            display: flex;
            margin-bottom: 0.5rem;
            align-items: flex-start;
        }
        
        .meta-label {
            font-weight: 600;
            color: #34495e;
            min-width: 110px;
            margin-right: 10px;
        }
        
        .meta-value {
            color: #2c3e50;
            flex: 1;
        }
        
        .import-code {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.9rem;
        }
        
        .products-section {
            margin: 2rem 0;
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1rem;
            padding-left: 1rem;
            border-left: 4px solid #e74c3c;
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
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
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
        
        .stt-cell {
            background: #ecf0f1;
            font-weight: 600;
            color: #2c3e50;
            text-align: center;
            width: 60px;
        }
        
        .code-cell {
            background: #fff3cd;
            font-weight: 600;
            color: #856404;
            text-align: center;
            font-family: 'Courier New', monospace;
        }
        
        .quantity-cell {
            background: #d1ecf1;
            font-weight: 600;
            text-align: center;
            color: #0c5460;
        }
        
        .price-cell {
            font-weight: 600;
            color: #27ae60;
            text-align: right;
        }
        
        .total-cell {
            font-weight: 700;
            color: #e74c3c;
            font-size: 1.05rem;
            text-align: right;
        }
        
        .summary-section {
            display: flex;
            justify-content: flex-end;
            margin: 2rem 0;
        }
        
        .total-summary {
            width: 400px;
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
        
        .quantity-total {
            background: #e8f5e8;
            color: #27ae60;
        }
        
        .quantity-total .summary-label,
        .quantity-total .summary-value {
            color: #27ae60;
        }
        
        .final-total {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
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
        
        .signatures-section {
            margin-top: 3rem;
            padding: 2rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .signatures-title {
            text-align: center;
            color: #2c3e50;
            font-weight: 700;
            font-size: 1.2rem;
            margin-bottom: 2rem;
        }
        
        .signatures-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            text-align: center;
        }
        
        .signature-box {
            padding: 1rem;
            background: white;
            border-radius: 8px;
            border: 2px dashed #bdc3c7;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .signature-title {
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1rem;
        }
        
        .signature-line {
            border-top: 1px solid #34495e;
            margin-top: 2rem;
            padding-top: 0.5rem;
            font-style: italic;
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .invoice-footer {
            margin-top: 3rem;
            text-align: center;
            padding: 2rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-top: 3px solid #e74c3c;
        }
        
        .footer-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .footer-note {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .footer-date {
            color: #e74c3c;
            font-weight: 600;
            font-size: 0.95rem;
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
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin: 0 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
        }
        
        .print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
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
            
            .import-invoice {
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
                background: #e74c3c !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
            }
            
            .final-total {
                background: #e74c3c !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
            }
            
            .notes-section {
                background: #fff9e6 !important;
                -webkit-print-color-adjust: exact;
            }
            
            .signatures-section {
                background: #f8f9fa !important;
                -webkit-print-color-adjust: exact;
            }
            
            .invoice-footer {
                background: #f8f9fa !important;
                -webkit-print-color-adjust: exact;
            }
            
            .invoice-header::after {
                background: #e74c3c !important;
            }
        }
        
        @media screen and (max-width: 768px) {
            .import-invoice {
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
            
            .signatures-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
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
            🖨️ In phiếu nhập
        </button>
        <button class="print-btn close-btn" onclick="window.close()">
            ❌ Đóng
        </button>
    </div>

    <div class="import-invoice">
        <div class="invoice-content">
            <!-- Header -->
            <div class="invoice-header">
                <div class="company-name">THE TRENDY STATION</div>
                <div class="company-tagline">Fashion Excellence</div>
                <div class="company-info">
                    📍 123 Đường Thời Trang, Quận 1, TP.HCM<br>
                    ☎️ 0901.234.567 | 📧 info@trendystation.com<br>
                    🌐 www.trendystation.vn
                </div>
            </div>

            <!-- Invoice Title -->
            <div class="invoice-title">Phiếu nhập hàng</div>

            <!-- Invoice Meta Information -->
            <div class="invoice-meta">
                <div class="meta-section">
                    <h4>📋 Thông tin phiếu nhập</h4>
                    <div class="meta-item">
                        <span class="meta-label">Số phiếu:</span>
                        <span class="meta-value">
                            <span class="import-code">#<?= $import['id'] ?></span>
                        </span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Ngày nhập:</span>
                        <span class="meta-value"><strong><?= formatVietnameseDate($import['created_at']) ?></strong></span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Trạng thái:</span>
                        <span class="meta-value">
                            <span style="background: #27ae60; color: white; padding: 4px 12px; border-radius: 15px; font-weight: 600; font-size: 0.85rem;">
                                ✅ Đã nhập kho
                            </span>
                        </span>
                    </div>
                </div>
                
                <div class="meta-section">
                    <h4>🏢 Thông tin nhà cung cấp</h4>
                    <div class="meta-item">
                        <span class="meta-label">Tên NCC:</span>
                        <span class="meta-value"><strong><?= htmlspecialchars($import['supplier_name'] ?? 'Không xác định') ?></strong></span>
                    </div>
                    <?php if (!empty($import['supplier_phone'])): ?>
                        <div class="meta-item">
                            <span class="meta-label">Điện thoại:</span>
                            <span class="meta-value"><?= htmlspecialchars($import['supplier_phone']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($import['supplier_address'])): ?>
                        <div class="meta-item">
                            <span class="meta-label">Địa chỉ:</span>
                            <span class="meta-value"><?= htmlspecialchars($import['supplier_address']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($import['supplier_email'])): ?>
                        <div class="meta-item">
                            <span class="meta-label">Email:</span>
                            <span class="meta-value"><?= htmlspecialchars($import['supplier_email']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Products Section -->
            <div class="products-section">
                <h3 class="section-title">Chi tiết sản phẩm nhập</h3>
                <table class="products-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">STT</th>
                            <th style="width: 100px;">Mã SP</th>
                            <th style="width: 35%;">Tên sản phẩm</th>
                            <th style="width: 100px;">Số lượng</th>
                            <th style="width: 120px;">Đơn giá</th>
                            <th style="width: 140px;">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $stt = 1;
                        $total_quantity = 0;
                        foreach ($details as $detail): 
                            $total_quantity += $detail['quantity'];
                        ?>
                        <tr>
                            <td class="stt-cell"><?= $stt++ ?></td>
                            <td class="code-cell"><?= htmlspecialchars($detail['product_code']) ?></td>
                            <td>
                                <div class="product-name"><?= htmlspecialchars($detail['product_name']) ?></div>
                            </td>
                            <td class="quantity-cell"><?= number_format($detail['quantity']) ?></td>
                            <td class="price-cell"><?= number_format($detail['unit_cost'], 0, ',', '.') ?>₫</td>
                            <td class="total-cell"><?= number_format($detail['total_cost'], 0, ',', '.') ?>₫</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Summary Section -->
            <div class="summary-section">
                <div class="total-summary">
                    <div class="summary-row quantity-total">
                        <span class="summary-label">📦 Tổng số lượng:</span>
                        <span class="summary-value"><?= number_format($total_quantity) ?> sản phẩm</span>
                    </div>
                    
                    <div class="summary-row final-total">
                        <span class="summary-label">💰 TỔNG TIỀN NHẬP:</span>
                        <span class="summary-value"><?= number_format($import['total_amount'], 0, ',', '.') ?>₫</span>
                    </div>
                </div>
            </div>

            <!-- Notes Section -->
            <?php if (!empty($import['notes']) && $import['notes'] !== 'Không có ghi chú'): ?>
                <div class="notes-section">
                    <div class="notes-title">📝 Ghi chú:</div>
                    <div class="notes-content"><?= htmlspecialchars($import['notes']) ?></div>
                </div>
            <?php endif; ?>

            <!-- Signatures Section -->
            <div class="signatures-section">
                <div class="signatures-title">🖊️ XÁC NHẬN VÀ KÝ DUYỆT</div>
                <div class="signatures-grid">
                    <div class="signature-box">
                        <div class="signature-title">👤 Người lập phiếu</div>
                        <div class="signature-line">(Ký và ghi rõ họ tên)</div>
                    </div>
                    <div class="signature-box">
                        <div class="signature-title">📦 Thủ kho</div>
                        <div class="signature-line">(Ký và ghi rõ họ tên)</div>
                    </div>
                    <div class="signature-box">
                        <div class="signature-title">👨‍💼 Quản lý</div>
                        <div class="signature-line">(Ký và ghi rõ họ tên)</div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="invoice-footer">
                <div class="footer-title">📄 The Trendy Station - Phiếu nhập hàng tự động</div>
                <div class="footer-note">Phiếu này được tạo tự động bởi hệ thống quản lý kho</div>
                <div class="footer-date">🕒 Ngày in: <?= date('d/m/Y H:i:s') ?></div>
            </div>
        </div>
    </div>

    <script>
        // Auto print when opened with auto_print parameter
        if (window.location.search.includes('auto_print=1')) {
            window.onload = function() {
                setTimeout(() => {
                    window.print();
                }, 500);
            };
        }
        
        // Handle after print
        window.onafterprint = function() {
            if (window.opener) {
                window.close();
            }
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
        
        // Auto focus for better user experience
        document.body.focus();
    </script>
</body>
</html>