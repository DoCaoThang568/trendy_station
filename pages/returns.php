<?php
require_once __DIR__ . '/../config/database.php';

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
            
            // Generate unique return code
            $return_code = 'RTN-' . strtoupper(uniqid());

            // Create return record
            $stmt = $pdo->prepare("
                INSERT INTO returns (return_code, sale_id, reason, total_refund, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $total_return_amount = 0;
            foreach ($return_items as $item) {
                $total_return_amount += $item['return_quantity'] * $item['unit_price'];
            }
            
            $stmt->execute([$return_code, $sale_id, $reason, $total_return_amount]);
            $return_id = $pdo->lastInsertId();
            
            // Create return details and update stock
            foreach ($return_items as $item) {
                // Insert return detail
                $stmt = $pdo->prepare("
                    INSERT INTO return_details (return_id, product_id, quantity, unit_price) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$return_id, $item['product_id'], $item['return_quantity'], $item['unit_price']]);
                
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
    SELECT r.*, s.sale_code, c.name as customer_name
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
                            <p class="invoice-ref">Từ HĐ: <?= htmlspecialchars($return['sale_code']) ?></p>
                        </div>
                        <div class="return-amount">
                            <span class="amount"><?= number_format($return['total_refund'], 0, ',', '.') ?>đ</span>
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
                            HĐ #<?= $sale['id'] ?> - <?= $sale['sale_code'] ?> 
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

<!-- View Return Detail Modal -->
<div id="viewReturnDetailModal" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h2>Chi tiết Phiếu Trả Hàng</h2>
            <button class="modal-close-btn" onclick="closeViewReturnDetailModal()">×</button>
        </div>
        <div class="modal-body" id="returnDetailContent">
            <!-- Details will be loaded here -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeViewReturnDetailModal()">Đóng</button>
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
    color:rgb(7, 7, 7); 
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

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    max-width: 600px;
    width: 90%;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    position: relative;
}

.modal-header {
    background: var(--primary-color);
    color: white;
    padding: 1rem;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.2rem;
}

.modal-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    padding: 1rem;
    background: #f1f1f1;
    border-top: 1px solid #e5e7eb;
}

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.9rem;
    transition: background 0.2s ease;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark);
}

.btn-secondary {
    background: #e5e7eb;
    color: #333;
}

.btn-secondary:hover {
    background: #d1d5db;
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
    console.log("showCreateReturnModal called");
    const modal = document.getElementById('createReturnModal');

    modal.style.display = 'flex';
    console.log("Modal display style set to flex");

    setTimeout(() => {
        modal.classList.add('show');
        console.log("Modal 'show' class added, transitions should start");
    }, 20); 
}

function closeCreateReturnModal() {
    const modal = document.getElementById('createReturnModal');
    
    modal.classList.remove('show');
    console.log("Modal 'show' class removed, fade-out transition should start");

    const afterTransition = (event) => {
        if (event.target === modal && event.propertyName === 'opacity') {
            modal.style.display = 'none'; 
            console.log("Modal display set to none after transition.");
            modal.removeEventListener('transitionend', afterTransition);
        }
    };

    const computedStyle = window.getComputedStyle(modal);
    const transitionDuration = parseFloat(computedStyle.transitionDuration.replace('s', '')) * 1000; 

    if (transitionDuration > 0) {
        modal.addEventListener('transitionend', afterTransition);
    } else {
        modal.style.display = 'none';
        console.log("Modal display set to none (no transition or instant).");
    }

    document.getElementById('returnForm').reset();
    document.getElementById('saleDetailsSection').style.display = 'none';
    document.getElementById('returnSummary').style.display = 'none';
    currentSaleDetails = [];
    returnItems = [];
}

function showViewReturnDetailModal() {
    document.getElementById('viewReturnDetailModal').style.display = 'flex';
}

function closeViewReturnDetailModal() {
    document.getElementById('viewReturnDetailModal').style.display = 'none';
    document.getElementById('returnDetailContent').innerHTML = ''; // Clear content for next use
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

// Updated viewReturnDetail function and new helper functions for the modal
function viewReturnDetail(returnId) {
    fetch(`../ajax/get_return_detail.php?id=${returnId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.return_info) {
                const returnInfo = data.return_info;
                const returnItems = data.return_items;

                let html = `
                    <div class="return-detail-section">
                        <h4>Thông tin chung</h4>
                        <p><strong>Mã phiếu trả:</strong> ${returnInfo.return_code || 'N/A'}</p>
                        <p><strong>Mã hóa đơn gốc:</strong> ${returnInfo.sale_code || 'N/A'}</p>
                        <p><strong>Khách hàng:</strong> ${returnInfo.customer_name || 'N/A'} (${returnInfo.customer_phone || 'N/A'})</p>
                        <p><strong>Ngày trả:</strong> ${returnInfo.return_date ? new Date(returnInfo.return_date).toLocaleDateString('vi-VN') : 'N/A'}</p>
                        <p><strong>Lý do:</strong> ${returnInfo.reason || 'N/A'}</p>
                        <p><strong>Tổng tiền hoàn:</strong> ${parseFloat(returnInfo.total_refund || 0).toLocaleString('vi-VN')} VNĐ</p>
                    </div>
                    <div class="return-detail-section">
                        <h4>Chi tiết sản phẩm trả</h4>
                `;

                if (returnItems && returnItems.length > 0) {
                    html += `
                        <table class="table table-sm table-bordered" style="width: 100%; margin-top: 10px;">
                            <thead>
                                <tr>
                                    <th>Mã SP</th>
                                    <th>Tên sản phẩm</th>
                                    <th style="text-align: right;">Số lượng</th>
                                    <th style="text-align: right;">Đơn giá</th>
                                    <th style="text-align: right;">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;
                    returnItems.forEach(item => {
                        const unitPrice = parseFloat(item.unit_price || 0);
                        const quantity = parseInt(item.quantity || 0);
                        const totalPrice = unitPrice * quantity;
                        html += `
                            <tr>
                                <td>${item.product_code || 'N/A'}</td>
                                <td>${item.product_name || 'N/A'}</td>
                                <td style="text-align: right;">${quantity}</td>
                                <td style="text-align: right;">${unitPrice.toLocaleString('vi-VN')} VNĐ</td>
                                <td style="text-align: right;">${totalPrice.toLocaleString('vi-VN')} VNĐ</td>
                            </tr>
                        `;
                    });
                    html += `
                            </tbody>
                        </table>
                    `;
                } else {
                    html += '<p>Không có sản phẩm nào trong phiếu trả này.</p>';
                }
                html += '</div>';

                document.getElementById('returnDetailContent').innerHTML = html;
                showViewReturnDetailModal();
            } else {
                showToast('Lỗi: ' + (data.message || 'Không thể tải chi tiết phiếu trả.'), 'error');
            }
        })
        .catch(error => {
            console.error('Error fetching return details:', error);
            showToast('Lỗi kết nối hoặc xử lý dữ liệu khi tải chi tiết phiếu trả. ' + error.message, 'error');
        });
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
