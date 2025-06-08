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
            font-family: 'Times New Roman', serif;
            font-size: 14px;
            line-height: 1.4;
            color: #333;
            background: white;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .shop-name {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }
        
        .shop-info {
            font-size: 12px;
            color: #666;
        }
        
        .import-title {
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }
        
        .import-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .info-section h3 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #2563eb;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .info-section p {
            margin-bottom: 5px;
        }
        
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .products-table th,
        .products-table td {
            border: 1px solid #333;
            padding: 8px;
            text-align: left;
        }
        
        .products-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: center;
        }
        
        .products-table .text-center {
            text-align: center;
        }
        
        .products-table .text-right {
            text-align: right;
        }
        
        .total-section {
            text-align: right;
            margin-bottom: 30px;
        }
        
        .total-row {
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .total-final {
            font-size: 18px;
            font-weight: bold;
            border-top: 2px solid #333;
            padding-top: 10px;
            color: #2563eb;
        }
        
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 30px;
            margin-top: 50px;
            text-align: center;
        }
        
        .signature-box {
            border-top: 1px solid #333;
            padding-top: 5px;
        }
        
        .signature-title {
            font-weight: bold;
            margin-bottom: 40px;
        }
        
        .signature-name {
            font-style: italic;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        
        @media print {
            body {
                font-size: 12px;
            }
            
            .container {
                padding: 0;
                margin: 0;
                max-width: none;
            }
            
            .no-print {
                display: none !important;
            }
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #2563eb;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .print-button:hover {
            background: #1d4ed8;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">🖨️ In Phiếu</button>
    
    <div class="container">
        <div class="header">
            <div class="shop-name">THE TRENDY STATION</div>
            <div class="shop-info">
                Địa chỉ: 123 Đường Thời Trang, Quận 1, TP.HCM<br>
                Điện thoại: 0901.234.567 | Email: info@trendystation.com
            </div>
        </div>
        
        <div class="import-title">PHIẾU NHẬP HÀNG</div>
        
        <div class="import-info">
            <div class="info-section">
                <h3>Thông tin phiếu nhập</h3>
                <p><span class="info-label">Số phiếu:</span> #<?= $import['id'] ?></p>
                <p><span class="info-label">Ngày nhập:</span> <?= date('d/m/Y H:i', strtotime($import['created_at'])) ?></p>
                <p><span class="info-label">Ghi chú:</span> <?= htmlspecialchars($import['notes'] ?? 'Không có ghi chú') ?></p>
            </div>
            
            <div class="info-section">
                <h3>Thông tin nhà cung cấp</h3>
                <p><span class="info-label">Tên NCC:</span> <?= htmlspecialchars($import['supplier_name'] ?? 'Không xác định') ?></p>
                <p><span class="info-label">Điện thoại:</span> <?= htmlspecialchars($import['supplier_phone'] ?? 'Không có') ?></p>
                <p><span class="info-label">Địa chỉ:</span> <?= htmlspecialchars($import['supplier_address'] ?? 'Không có') ?></p>
                <?php if (!empty($import['supplier_email'])): ?>
                <p><span class="info-label">Email:</span> <?= htmlspecialchars($import['supplier_email']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <table class="products-table">
            <thead>
                <tr>
                    <th style="width: 40px;">STT</th>
                    <th style="width: 80px;">Mã SP</th>
                    <th>Tên sản phẩm</th>
                    <th style="width: 80px;">Số lượng</th>
                    <th style="width: 100px;">Đơn giá</th>
                    <th style="width: 120px;">Thành tiền</th>
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
                    <td class="text-center"><?= $stt++ ?></td>
                    <td class="text-center"><?= htmlspecialchars($detail['product_code']) ?></td>
                    <td><?= htmlspecialchars($detail['product_name']) ?></td>
                    <td class="text-center"><?= number_format($detail['quantity']) ?></td>
                    <td class="text-right"><?= number_format($detail['unit_price'], 0, ',', '.') ?>đ</td>
                    <td class="text-right"><?= number_format($detail['total_price'], 0, ',', '.') ?>đ</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="total-section">
            <div class="total-row">
                <strong>Tổng số lượng: <?= number_format($total_quantity) ?> sản phẩm</strong>
            </div>
            <div class="total-row total-final">
                <strong>Tổng tiền: <?= number_format($import['total_amount'], 0, ',', '.') ?>đ</strong>
            </div>
        </div>
        
        <div class="signatures">
            <div class="signature-box">
                <div class="signature-title">Người lập phiếu</div>
                <div class="signature-name">(Ký tên)</div>
            </div>
            <div class="signature-box">
                <div class="signature-title">Thủ kho</div>
                <div class="signature-name">(Ký tên)</div>
            </div>
            <div class="signature-box">
                <div class="signature-title">Quản lý</div>
                <div class="signature-name">(Ký tên)</div>
            </div>
        </div>
        
        <div class="footer">
            Phiếu nhập được tạo tự động bởi hệ thống The Trendy Station<br>
            Ngày in: <?= date('d/m/Y H:i:s') ?>
        </div>
    </div>
    
    <script>
        // Auto print when opened in new window
        if (window.location.search.includes('auto_print=1')) {
            window.onload = function() {
                setTimeout(() => {
                    window.print();
                }, 500);
            };
        }
        
        // Close window after printing (if opened in popup)
        window.onafterprint = function() {
            if (window.opener) {
                window.close();
            }
        };
    </script>
</body>
</html>
