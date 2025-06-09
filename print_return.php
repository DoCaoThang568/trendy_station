<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Không tìm thấy ID phiếu trả hàng.");
}

$return_id = (int)$_GET['id'];

try {
    // Fetch return information
    $stmt_return = $pdo->prepare("
        SELECT r.*, s.sale_code, c.name as customer_name
        FROM returns r
        JOIN sales s ON r.sale_id = s.id
        LEFT JOIN customers c ON s.customer_id = c.id
        WHERE r.id = ?
    ");
    $stmt_return->execute([$return_id]);
    $return_info = $stmt_return->fetch(PDO::FETCH_ASSOC);

    if (!$return_info) {
        die("Không tìm thấy thông tin phiếu trả hàng.");
    }

    $staff_name = 'N/A';
    if (!empty($return_info['sale_user_id'])) {
        $stmt_user = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt_user->execute([$return_info['sale_user_id']]);
        $user_info = $stmt_user->fetch(PDO::FETCH_ASSOC);
        if ($user_info) {
            $staff_name = $user_info['username'];
        }
    }


    // Fetch return items
    $stmt_items = $pdo->prepare("
        SELECT rd.*, p.name as product_name, p.product_code
        FROM return_details rd
        JOIN products p ON rd.product_id = p.id
        WHERE rd.return_id = ?
    ");
    $stmt_items->execute([$return_id]);
    $return_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Lỗi truy vấn cơ sở dữ liệu: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phiếu Trả Hàng - <?= htmlspecialchars($return_info['return_code']) ?></title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #fff;
            color: #333;
            font-size: 12px;
        }
        .container {
            width: 100%;
            max-width: 600px; 
            margin: 0 auto;
            border: 1px solid #ccc;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 1.5em;
        }
        .header p {
            margin: 5px 0;
        }
        .info-section {
            margin-bottom: 15px;
            border-bottom: 1px dashed #eee;
            padding-bottom: 10px;
        }
        .info-section:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .info-section h2 {
            font-size: 1.1em;
            margin-top: 0;
            margin-bottom: 8px;
            color: #555;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: auto 1fr; /* Adjusted for better label alignment */
            gap: 5px 10px; /* Row gap and column gap */
        }
        .info-grid strong {
            font-weight: bold;
            white-space: nowrap; /* Prevent labels from wrapping */
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .items-table th, .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .items-table th {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        .items-table td.number {
            text-align: right;
        }
        .total-section {
            text-align: right;
            margin-top: 20px;
        }
        .total-section p {
            font-size: 1.1em;
            font-weight: bold;
            margin: 5px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 0.9em;
            color: #777;
        }
        @media print {
            body {
                margin: 0;
                padding: 0;
                background-color: #fff;
            }
            .container {
                width: 100%;
                max-width: none;
                border: none;
                box-shadow: none;
                margin: 0;
                padding: 10px; /* Add some padding for print */
            }
            .no-print {
                display: none;
            }
            * {
                color: #000 !important; 
                background-color: transparent !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>PHIẾU TRẢ HÀNG</h1>
            <p><strong>Cửa hàng Trendy Station</strong></p>
            <p>Địa chỉ: 123 Đường ABC, Quận XYZ, TP HCM</p>
            <p>Điện thoại: 0123 456 789</p>
        </div>

        <div class="info-section">
            <h2>Thông tin Phiếu Trả</h2>
            <div class="info-grid">
                <strong>Mã phiếu trả:</strong>       <span><?= htmlspecialchars($return_info['return_code']) ?></span>
                <strong>Ngày trả:</strong>         <span><?= date('d/m/Y H:i:s', strtotime($return_info['created_at'])) ?></span>
                <strong>Hóa đơn gốc:</strong>      <span><?= htmlspecialchars($return_info['sale_code']) ?></span>
                <strong>Khách hàng:</strong>       <span><?= htmlspecialchars($return_info['customer_name'] ?? 'Khách lẻ') ?></span>
                <strong>Nhân viên:</strong>        <span><?= htmlspecialchars($staff_name) ?></span>
                <strong>Lý do trả:</strong>        <span><?= htmlspecialchars($return_info['reason']) ?></span>
            </div>
        </div>

        <div class="info-section">
            <h2>Sản phẩm trả lại</h2>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Mã SP</th>
                        <th>Tên sản phẩm</th>
                        <th class="number">SL</th>
                        <th class="number">Đơn giá</th>
                        <th class="number">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $stt = 1;
                    foreach ($return_items as $item): 
                        $line_total = $item['quantity'] * $item['unit_price'];
                    ?>
                    <tr>
                        <td><?= $stt++ ?></td>
                        <td><?= htmlspecialchars($item['product_code']) ?></td>
                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                        <td class="number"><?= htmlspecialchars($item['quantity']) ?></td>
                        <td class="number"><?= number_format($item['unit_price'], 0, ',', '.') ?>đ</td>
                        <td class="number"><?= number_format($line_total, 0, ',', '.') ?>đ</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="total-section">
            <p>Tổng tiền hoàn trả: <?= number_format($return_info['total_refund'], 0, ',', '.') ?>đ</p>
        </div>

        <div class="footer">
            <p>Cảm ơn quý khách!</p>
            <p>Vui lòng giữ phiếu này cho các giao dịch sau.</p>
        </div>
    </div>

    <?php if (isset($_GET['auto_print']) && $_GET['auto_print'] == '1'): ?>
    <script type="text/javascript">
        window.onload = function() {
            window.print();
            // Optionally, close the window after printing
            // setTimeout(function(){ window.close(); }, 2000); // Close after 2 seconds
        }
    </script>
    <?php endif; ?>
</body>
</html>
