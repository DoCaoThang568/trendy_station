<?php
/**
 * AJAX - Get Sale Detail
 */

require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$saleId = $input['sale_id'] ?? 0;

if (!$saleId) {
    echo json_encode(['success' => false, 'message' => 'Missing sale ID']);
    exit;
}

try {
    // Get sale info
    $sale = fetchOne("
        SELECT s.*, c.name as customer_name_db, c.phone as customer_phone_db 
        FROM sales s 
        LEFT JOIN customers c ON s.customer_id = c.id 
        WHERE s.id = ?
    ", [$saleId]);
    
    if (!$sale) {
        echo json_encode(['success' => false, 'message' => 'Sale not found']);
        exit;
    }
    
    // Get sale details
    $saleDetails = fetchAll("
        SELECT sd.*, p.code as product_code, p.unit 
        FROM sale_details sd 
        LEFT JOIN products p ON sd.product_id = p.id 
        WHERE sd.sale_id = ? 
        ORDER BY sd.id
    ", [$saleId]);
    
    // Format date helper
    function formatVietnameseDate($dateString) {
        return date('d/m/Y H:i:s', strtotime($dateString));
    }
    
    // Generate HTML
    $html = '
    <div style="line-height: 1.6;">
        <!-- Sale Info -->
        <div style="background: var(--bg-tertiary); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <strong>📅 Ngày bán:</strong><br>
                    ' . formatVietnameseDate($sale['sale_date']) . '
                </div>
                <div>
                    <strong>👤 Khách hàng:</strong><br>
                    ' . htmlspecialchars($sale['customer_name'] ?: $sale['customer_name_db']) . '
                    ' . ($sale['customer_phone'] ? '<br>📞 ' . htmlspecialchars($sale['customer_phone']) : '') . '
                </div>
                <div>
                    <strong>💳 Thanh toán:</strong><br>';
    
    switch($sale['payment_method']) {
        case 'cash': $html .= '💵 Tiền mặt'; break;
        case 'card': $html .= '💳 Thẻ'; break;
        case 'transfer': $html .= '🏦 Chuyển khoản'; break;
        default: $html .= $sale['payment_method'];
    }
    
    $html .= '</div>
                <div>
                    <strong>👨‍💼 Nhân viên:</strong><br>
                    ' . htmlspecialchars($sale['created_by']) . '
                </div>
            </div>';
    
    if ($sale['notes']) {
        $html .= '
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(102, 126, 234, 0.1);">
                <strong>📝 Ghi chú:</strong><br>
                ' . htmlspecialchars($sale['notes']) . '
            </div>';
    }
    
    $html .= '</div>';
    
    // Products table
    $html .= '
        <div style="margin-bottom: 1.5rem;">
            <h4 style="margin-bottom: 1rem; color: var(--primary-color);">📦 Danh sách sản phẩm</h4>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                    <thead>
                        <tr style="background: var(--bg-tertiary);">
                            <th style="padding: 0.75rem; text-align: left; border: 1px solid rgba(102, 126, 234, 0.1);">Sản phẩm</th>
                            <th style="padding: 0.75rem; text-align: center; border: 1px solid rgba(102, 126, 234, 0.1);">SL</th>
                            <th style="padding: 0.75rem; text-align: right; border: 1px solid rgba(102, 126, 234, 0.1);">Đơn giá</th>
                            <th style="padding: 0.75rem; text-align: right; border: 1px solid rgba(102, 126, 234, 0.1);">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    foreach ($saleDetails as $detail) {
        $subtotal = $detail['quantity'] * $detail['unit_price'];
        $html .= '
                        <tr>
                            <td style="padding: 0.75rem; border: 1px solid rgba(102, 126, 234, 0.1);">
                                <div style="font-weight: 600;">' . htmlspecialchars($detail['product_name']) . '</div>
                                ' . ($detail['product_code'] ? '<div style="font-size: 0.8rem; color: var(--text-secondary);">Mã: ' . htmlspecialchars($detail['product_code']) . '</div>' : '') . '
                            </td>
                            <td style="padding: 0.75rem; text-align: center; border: 1px solid rgba(102, 126, 234, 0.1);">
                                ' . number_format($detail['quantity']) . '' . ($detail['unit'] ? ' ' . $detail['unit'] : '') . '
                            </td>
                            <td style="padding: 0.75rem; text-align: right; border: 1px solid rgba(102, 126, 234, 0.1);">
                                ' . number_format($detail['unit_price']) . '₫
                            </td>
                            <td style="padding: 0.75rem; text-align: right; border: 1px solid rgba(102, 126, 234, 0.1); font-weight: 600;">
                                ' . number_format($subtotal) . '₫
                            </td>
                        </tr>';
    }
    
    $html .= '
                    </tbody>
                </table>
            </div>
        </div>';
    
    // Total calculation
    $html .= '
        <div style="background: var(--bg-tertiary); padding: 1rem; border-radius: 8px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span>Tạm tính:</span>
                <span>' . number_format($sale['subtotal']) . '₫</span>
            </div>';
    
    if ($sale['discount_percent'] > 0) {
        $html .= '
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; color: var(--danger-color);">
                <span>Giảm giá (' . $sale['discount_percent'] . '%):</span>
                <span>-' . number_format($sale['discount_amount']) . '₫</span>
            </div>';
    }
    
    $html .= '
            <div style="display: flex; justify-content: space-between; padding-top: 0.5rem; border-top: 2px solid var(--primary-color); font-size: 1.2rem; font-weight: 800; color: var(--primary-color);">
                <span>Tổng cộng:</span>
                <span>' . number_format($sale['total_amount']) . '₫</span>
            </div>
        </div>';
    
    $html .= '</div>';
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'sale' => $sale
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
