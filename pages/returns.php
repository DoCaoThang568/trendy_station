<?php
require_once '../config/database.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        if ($_POST['action'] === 'create_return') {
            $sale_id = (int)$_POST['sale_id'];
            $reason = trim($_POST['reason']);
            $return_items = json_decode($_POST['return_items'], true);
            
            if (empty($return_items)) {
                throw new Exception('Vui lòng chọn ít nhất một sản phẩm để trả');
            }
            
            $pdo->beginTransaction();
            
            // Create return record
            $stmt = $pdo->prepare("
                INSERT INTO returns (sale_id, reason, total_amount, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            
            $total_return_amount = 0;
            foreach ($return_items as $item) {
                $total_return_amount += $item['return_quantity'] * $item['unit_price'];
            }
            
            $stmt->execute([$sale_id, $reason, $total_return_amount]);
            $return_id = $pdo->lastInsertId();
            
            // Create return details and update stock
            foreach ($return_items as $item) {
                // Insert return detail
                $stmt = $pdo->prepare("
                    INSERT INTO return_details (return_id, product_id, quantity, unit_price, total_price) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $total_price = $item['return_quantity'] * $item['unit_price'];
                $stmt->execute([$return_id, $item['product_id'], $item['return_quantity'], $item['unit_price'], $total_price]);
                
                // Update product stock
                $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
                $stmt->execute([$item['return_quantity'], $item['product_id']]);
            }
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Tạo phiếu trả hàng thành công']);
            
        } else {
            throw new Exception('Action không hợp lệ');
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Get recent sales for return
$recent_sales_stmt = $pdo->prepare("
    SELECT s.*, c.name as customer_name 
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    WHERE s.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ORDER BY s.created_at DESC
    LIMIT 20
");
$recent_sales_stmt->execute();
$recent_sales = $recent_sales_stmt->fetchAll();

// Get returns
$returns_stmt = $pdo->prepare("
    SELECT r.*, s.invoice_code, c.name as customer_name
    FROM returns r
    JOIN sales s ON r.sale_id = s.id
    LEFT JOIN customers c ON s.customer_id = c.id
    ORDER BY r.created_at DESC
    LIMIT 50
");
$returns_stmt->execute();
$returns = $returns_stmt->fetchAll();

$page_title = "↩️ Quản lý Trả hàng";
$current_page = "returns";
include '../includes/header.php';
?>

<div class="main-content">
    <div class="content-header">
        <div class="header-left">
            <h1>↩️ Quản lý Trả hàng</h1>
            <p>Xử lý các yêu cầu trả hàng từ khách hàng</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="showCreateReturnModal()" title="Tạo phiếu trả hàng mới (F1)">
                ➕ Tạo phiếu trả hàng
            </button>
        </div>
    </div>

    <!-- Returns List -->
    <div class="returns-section">
        <div class="section-header">
            <h2>📋 Danh sách phiếu trả hàng</h2>
            <div class="search-box">
                <input type="text" id="returnSearch" placeholder="🔍 Tìm phiếu trả hàng..." 
                       onkeyup="searchReturns(this.value)">
            </div>
        </div>
        
        <div class="returns-grid" id="returnsGrid">
            <?php if (!empty($returns)): ?>
                <?php foreach ($returns as $return): ?>
                <div class="return-card" data-return-id="<?= $return['id'] ?>">
                    <div class="return-header">
                        <div class="return-info">
                            <h3>↩️ Phiếu trả #<?= $return['id'] ?></h3>
                            <p class="invoice-ref">Từ HĐ: <?= htmlspecialchars($return['invoice_code']) ?></p>
                        </div>
                        <div class="return-amount">
                            <span class="amount"><?= number_format($return['total_amount'], 0, ',', '.') ?>đ</span>
                        </div>
                    </div>
                    
                    <div class="return-details">
                        <p><strong>Khách hàng:</strong> <?= htmlspecialchars($return['customer_name'] ?? 'Khách lẻ') ?></p>
                        <p><strong>Lý do:</strong> <?= htmlspecialchars($return['reason']) ?></p>
                        <p><strong>Ngày trả:</strong> <?= date('d/m/Y H:i', strtotime($return['created_at'])) ?></p>
                    </div>
                    
                    <div class="return-actions">
                        <button class="btn btn-small btn-primary" onclick="viewReturnDetail(<?= $return['id'] ?>)" title="Xem chi tiết">
                            👁️ Chi tiết
                        </button>
                        <button class="btn btn-small btn-secondary" onclick="printReturn(<?= $return['id'] ?>)" title="In phiếu trả">
                            🖨️ In phiếu
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data">
                    <div class="no-data-icon">📋</div>
                    <h3>Chưa có phiếu trả hàng nào</h3>
                    <p>Tạo phiếu trả hàng đầu tiên bằng cách nhấn nút "Tạo phiếu trả hàng" ở trên</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create Return Modal -->
<div id="createReturnModal" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="max-width: 1000px;">
        <div class="modal-header">
            <h3>↩️ Tạo phiếu trả hàng</h3>
            <button class="modal-close" onclick="closeCreateReturnModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="returnForm">
                <!-- Sale Selection -->
                <div class="form-group">
                    <label for="sale_select">📋 Chọn hóa đơn cần trả hàng:</label>
                    <select id="sale_select" name="sale_id" required onchange="loadSaleDetails(this.value)">
                        <option value="">-- Chọn hóa đơn --</option>
                        <?php foreach ($recent_sales as $sale): ?>
                        <option value="<?= $sale['id'] ?>" 
                                data-customer="<?= htmlspecialchars($sale['customer_name'] ?? 'Khách lẻ') ?>"
                                data-total="<?= $sale['total_amount'] ?>">
                            HĐ #<?= $sale['id'] ?> - <?= $sale['invoice_code'] ?> 
                            (<?= date('d/m/Y', strtotime($sale['created_at'])) ?>) 
                            - <?= htmlspecialchars($sale['customer_name'] ?? 'Khách lẻ') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Return Reason -->
                <div class="form-group">
                    <label for="return_reason">📝 Lý do trả hàng:</label>
                    <select id="return_reason" name="reason" required>
                        <option value="">-- Chọn lý do --</option>
                        <option value="Sản phẩm lỗi">🔧 Sản phẩm lỗi</option>
                        <option value="Không vừa size">📏 Không vừa size</option>
                        <option value="Không đúng màu sắc">🎨 Không đúng màu sắc</option>
                        <option value="Khách đổi ý">💭 Khách đổi ý</option>
                        <option value="Sai sản phẩm">❌ Sai sản phẩm</option>
                        <option value="Khác">📄 Lý do khác</option>
                    </select>
                </div>
                
                <!-- Sale Details -->
                <div id="saleDetailsSection" style="display: none;">
                    <h4>📦 Sản phẩm trong hóa đơn</h4>
                    <div id="saleDetailsList"></div>
                </div>
                
                <!-- Return Summary -->
                <div id="returnSummary" style="display: none;">
                    <div class="return-summary-box">
                        <h4>💰 Tổng kết trả hàng</h4>
                        <div class="summary-row">
                            <span>Tổng số lượng trả:</span>
                            <span id="totalReturnQuantity">0</span>
                        </div>
                        <div class="summary-row total">
                            <span>Tổng tiền hoàn trả:</span>
                            <span id="totalReturnAmount">0đ</span>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeCreateReturnModal()">Hủy</button>
            <button type="button" class="btn btn-primary" onclick="submitReturn()" id="submitReturnBtn" disabled>
                ✅ Tạo phiếu trả
            </button>
        </div>
    </div>
</div>

<style>
.returns-section {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--primary-light);
}

.search-box input {
    width: 300px;
    padding: 0.5rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 25px;
    font-size: 0.9rem;
    transition: border-color 0.2s ease;
}

.search-box input:focus {
    border-color: var(--primary-color);
    outline: none;
}

.returns-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.return-card {
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.5rem;
    background: white;
    transition: all 0.2s ease;
    cursor: pointer;
}

.return-card:hover {
    border-color: var(--primary-color);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
    transform: translateY(-2px);
}

.return-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 1rem;
}

.return-info h3 {
    margin: 0;
    color: var(--primary-color);
    font-size: 1.1rem;
}

.invoice-ref {
    color: #666;
    font-size: 0.9rem;
    margin: 0.25rem 0 0 0;
}

.return-amount .amount {
    font-size: 1.2rem;
    font-weight: bold;
    color: #ef4444;
}

.return-details {
    margin-bottom: 1rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 8px;
}

.return-details p {
    margin: 0.25rem 0;
    font-size: 0.9rem;
}

.return-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.sale-detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 0.5rem;
    background: #f9fafb;
}

.product-info {
    flex: 1;
}

.product-info h4 {
    margin: 0 0 0.25rem 0;
    font-size: 1rem;
    color: var(--primary-color);
}

.product-info p {
    margin: 0;
    font-size: 0.9rem;
    color: #666;
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.quantity-input {
    width: 80px;
    text-align: center;
    padding: 0.25rem;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.return-summary-box {
    background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
    color: white;
    padding: 1.5rem;
    border-radius: 8px;
    margin-top: 1rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.summary-row.total {
    font-weight: bold;
    font-size: 1.1rem;
    border-top: 1px solid rgba(255,255,255,0.3);
    padding-top: 0.5rem;
    margin-top: 0.5rem;
}

@media (max-width: 768px) {
    .returns-grid {
        grid-template-columns: 1fr;
    }
    
    .search-box input {
        width: 100%;
    }
    
    .section-header {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
}
</style>

<script>
let currentSaleDetails = [];
let returnItems = [];

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.key === 'F1') {
        e.preventDefault();
        showCreateReturnModal();
    }
    
    if (e.key === 'Escape') {
        closeCreateReturnModal();
    }
});

function showCreateReturnModal() {
    document.getElementById('createReturnModal').style.display = 'flex';
    document.getElementById('sale_select').focus();
}

function closeCreateReturnModal() {
    document.getElementById('createReturnModal').style.display = 'none';
    document.getElementById('returnForm').reset();
    document.getElementById('saleDetailsSection').style.display = 'none';
    document.getElementById('returnSummary').style.display = 'none';
    currentSaleDetails = [];
    returnItems = [];
}

function loadSaleDetails(saleId) {
    if (!saleId) {
        document.getElementById('saleDetailsSection').style.display = 'none';
        document.getElementById('returnSummary').style.display = 'none';
        return;
    }
    
    fetch(`ajax/get_sale_detail.php?id=${saleId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                showToast(data.error, 'error');
                return;
            }
            
            currentSaleDetails = data.details;
            displaySaleDetails(data.details);
            document.getElementById('saleDetailsSection').style.display = 'block';
            updateReturnSummary();
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Lỗi khi tải chi tiết hóa đơn', 'error');
        });
}

function displaySaleDetails(details) {
    const container = document.getElementById('saleDetailsList');
    container.innerHTML = '';
    
    returnItems = []; // Reset return items
    
    details.forEach(detail => {
        const item = document.createElement('div');
        item.className = 'sale-detail-item';
        item.innerHTML = `
            <div class="product-info">
                <h4>${detail.product_name}</h4>
                <p>Mã: ${detail.product_code} | Đã bán: ${detail.quantity} | Giá: ${detail.unit_price_formatted}đ</p>
            </div>
            <div class="quantity-controls">
                <label>Số lượng trả:</label>
                <input type="number" 
                       class="quantity-input" 
                       min="0" 
                       max="${detail.quantity}" 
                       value="0"
                       onchange="updateReturnQuantity(${detail.product_id}, this.value, ${detail.unit_price})"
                       data-product-id="${detail.product_id}">
            </div>
        `;
        container.appendChild(item);
        
        // Initialize return item
        returnItems.push({
            product_id: detail.product_id,
            product_name: detail.product_name,
            max_quantity: detail.quantity,
            unit_price: detail.unit_price,
            return_quantity: 0
        });
    });
}

function updateReturnQuantity(productId, quantity, unitPrice) {
    const qty = parseInt(quantity) || 0;
    const item = returnItems.find(item => item.product_id == productId);
    
    if (item) {
        item.return_quantity = qty;
    }
    
    updateReturnSummary();
}

function updateReturnSummary() {
    const totalQuantity = returnItems.reduce((sum, item) => sum + item.return_quantity, 0);
    const totalAmount = returnItems.reduce((sum, item) => sum + (item.return_quantity * item.unit_price), 0);
    
    document.getElementById('totalReturnQuantity').textContent = totalQuantity;
    document.getElementById('totalReturnAmount').textContent = totalAmount.toLocaleString('vi-VN') + 'đ';
    
    // Show/hide summary and enable/disable submit button
    const summarySection = document.getElementById('returnSummary');
    const submitBtn = document.getElementById('submitReturnBtn');
    
    if (totalQuantity > 0) {
        summarySection.style.display = 'block';
        submitBtn.disabled = false;
    } else {
        summarySection.style.display = 'none';
        submitBtn.disabled = true;
    }
}

function submitReturn() {
    const saleId = document.getElementById('sale_select').value;
    const reason = document.getElementById('return_reason').value;
    
    if (!saleId || !reason) {
        showToast('Vui lòng điền đầy đủ thông tin', 'error');
        return;
    }
    
    const validReturnItems = returnItems.filter(item => item.return_quantity > 0);
    
    if (validReturnItems.length === 0) {
        showToast('Vui lòng chọn ít nhất một sản phẩm để trả', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'create_return');
    formData.append('sale_id', saleId);
    formData.append('reason', reason);
    formData.append('return_items', JSON.stringify(validReturnItems));
    
    document.getElementById('submitReturnBtn').disabled = true;
    
    fetch('pages/returns.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            closeCreateReturnModal();
            location.reload(); // Refresh to show new return
        } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Lỗi khi tạo phiếu trả hàng', 'error');
    })
    .finally(() => {
        document.getElementById('submitReturnBtn').disabled = false;
    });
}

function searchReturns(query) {
    const cards = document.querySelectorAll('.return-card');
    const searchTerm = query.toLowerCase();
    
    cards.forEach(card => {
        const text = card.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function viewReturnDetail(returnId) {
    // Implement view return detail functionality
    showToast('Chức năng xem chi tiết đang được phát triển', 'info');
}

function printReturn(returnId) {
    // Open print window
    const printWindow = window.open(`print_return.php?id=${returnId}&auto_print=1`, '_blank', 
        'width=800,height=900,scrollbars=yes,resizable=yes');
    
    if (!printWindow) {
        showToast('Không thể mở cửa số in. Vui lòng cho phép popup.', 'error');
    }
}
</script>

<?php include '../includes/footer.php'; ?>
