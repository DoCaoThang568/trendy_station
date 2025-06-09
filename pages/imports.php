<?php
require_once __DIR__ . '/../includes/functions.php'; // Include the new functions file
/**
 * Imports Page - Nhập hàng & Quản lý kho
 */

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_import':
            try {
                // Start transaction
                $pdo->beginTransaction();
                
                // Generate import code
                $importCode = generateCode('PN', 'imports', 'import_code');
                
                // Get form data
                // $supplier_id from form's 'id' field, should be inserted into imports.supplier_id
                $supplier_id_from_form = !empty($_POST['id']) ? (int)$_POST['id'] : null; 
                
                // Lines 22 and 23 ($supplier_name = $_POST['name']; $supplier_phone = $_POST['phone'];) are removed
                // as 'name' and 'phone' are not columns in the 'imports' table and were causing warnings/SQL errors.
                // If supplier name/phone are needed for other purposes (e.g. creating a new supplier),
                // that logic should be handled separately and ensure $_POST keys exist.

                $total_amount = floatval($_POST['total_amount'] ?? 0);
                // $payment_status = $_POST['payment_status']; // This line is kept to avoid breaking POST data expectations, but $payment_status is not used below
                $notes = $_POST['notes'] ?? '';
                
                // Insert import record
                // SQL assumes 'imports' table has: id (PK,AI), import_code, supplier_id, import_date, total_amount, notes, created_by, status
                // Corrected SQL:
                // - Uses 'supplier_id' column for the supplier's ID.
                // - Removed 'name', 'phone' columns.
                // - Added 'import_date' column, set to NOW().
                // - 'created_by' and 'status' are hardcoded as in the original query.
                $sql = "INSERT INTO imports (import_code, supplier_id, import_date, total_amount, notes, created_by, status) 
                        VALUES (?, ?, NOW(), ?, ?, ?, ?)"; // 6 placeholders for 6 values + NOW()
                
                $params = [
                    $importCode, 
                    $supplier_id_from_form, 
                    // NOW() is in SQL string for import_date
                    $total_amount, 
                    $notes,
                    'admin',        // created_by
                    'Hoàn thành'    // status
                ];
                
                $stmt = executeQuery($sql, $params);
                
                $importId = $pdo->lastInsertId();
                
                // Insert import details
                $products = json_decode($_POST['products'] ?? '[]', true); // Added null coalescing for products
                foreach ($products as $product) {
                    if (!empty($product['product_id']) && $product['quantity'] > 0) {
                        // Calculate total cost
                        $total_cost = $product['quantity'] * $product['unit_cost'];
                        
                        // Get product code
                        $product_code = '';
                        $stmt = executeQuery("SELECT product_code FROM products WHERE id = ?", [$product['product_id']]);
                        if ($row = $stmt->fetch()) {
                            $product_code = $row['product_code'];
                        }
                        
                        // Insert import detail
                        $sql = "INSERT INTO import_details (import_id, product_id, product_code, product_name, quantity, unit_cost, total_cost) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)";
                        executeQuery($sql, [
                            $importId, 
                            $product['product_id'],
                            $product_code,
                            $product['product_name'],
                            $product['quantity'],
                            $product['unit_cost'],
                            $total_cost
                        ]);
                        
                        // Update stock
                        $sql = "UPDATE products SET stock_quantity = stock_quantity + ?, cost_price = ? WHERE id = ?";
                        executeQuery($sql, [$product['quantity'], $product['unit_cost'], $product['product_id']]);
                        
                        // Record stock movement (COMMENTED OUT)
                        /*
                        $sql_stock_movement = "INSERT INTO stock_movements (product_id, movement_type, reference_id, reference_code,
                                       quantity_change, stock_before, stock_after, notes, created_by) 
                                SELECT ?, 'import', ?, ?, ?, p.stock_quantity - ?, p.stock_quantity, ?, ?
                                FROM products p WHERE p.id = ?";
                        executeQuery($sql_stock_movement, [
                            $product['product_id'], 
                            $importId, 
                            $importCode, // reference_code
                            $product['quantity'], // quantity_change
                            $product['quantity'], // for stock_before calculation
                            "Nhập hàng - PN: $importCode", // notes
                            'admin', // created_by (hoặc lấy từ session user)
                            $product['product_id']
                        ]);
                        */
                    }
                }
                
                $pdo->commit();
                $_SESSION['success_message'] = "Tạo phiếu nhập $importCode thành công! Tổng tiền: " . number_format($total_amount) . "đ";
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['error_message'] = 'Lỗi tạo phiếu nhập: ' . $e->getMessage();
            }
            break;
            
        case 'delete_import':
            try {
                $importId = $_POST['import_id'];
                
                // Start transaction
                $pdo->beginTransaction();
                
                // Get import details to reverse stock
                $importDetails = fetchAll("SELECT * FROM import_details WHERE import_id = ?", [$importId]);
                
                // Reverse stock quantities
                foreach ($importDetails as $detail) {
                    $sql = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
                    executeQuery($sql, [$detail['quantity'], $detail['product_id']]);
                    
                    // Record stock movement (COMMENTED OUT)
                    /*
                    $sql_stock_movement_cancel = "INSERT INTO stock_movements (product_id, movement_type, reference_id, 
                                   quantity_change, stock_before, stock_after, notes, created_by) 
                            SELECT ?, 'import_cancel', ?, ?, p.stock_quantity + ?, p.stock_quantity, ?, ?
                            FROM products p WHERE p.id = ?";
                    executeQuery($sql_stock_movement_cancel, [
                        $detail['product_id'], 
                        $importId, 
                        -$detail['quantity'], // quantity_change
                        $detail['quantity'], // for stock_before calculation
                        "Hủy phiếu nhập ID: $importId", // notes
                        'admin', // created_by
                        $detail['product_id']
                    ]);
                    */
                }
                
                // Delete import details
                executeQuery("DELETE FROM import_details WHERE import_id = ?", [$importId]);
                
                // Delete import
                executeQuery("DELETE FROM imports WHERE id = ?", [$importId]);
                
                $pdo->commit();
                $_SESSION['success_message'] = 'Xóa phiếu nhập thành công!';
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['error_message'] = 'Lỗi xóa phiếu nhập: ' . $e->getMessage();
            }
            break;
    }
    
    header('Location: index.php?page=imports');
    exit;
}

// Get recent imports
$recentImports = fetchAll("
    SELECT i.*, s.name as supplier_name_db 
    FROM imports i 
    LEFT JOIN suppliers s ON i.supplier_id = s.id 
    ORDER BY i.import_date DESC 
    LIMIT 10
");

// Get products for selection
$products_stmt = $pdo->query("
    SELECT 
        p.id, 
        p.name, 
        p.product_code, 
        p.stock_quantity, 
        p.cost_price as import_price, /* Use cost_price and alias it as import_price */
        p.selling_price,
        c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.is_active = 1
    ORDER BY p.name ASC
");
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get suppliers for selection
$suppliers = fetchAll("SELECT * FROM suppliers WHERE is_active = 1 ORDER BY name");

// Generate new import code
$newImportCode = generateCode('PN', 'imports', 'import_code');
?>

<h1 class="page-title">📦 Nhập hàng - Quản lý kho</h1>

<div style="display: grid; grid-template-columns: 1fr 400px; gap: 2rem; align-items: start;">
    <!-- Form tạo phiếu nhập -->
    <div class="form-container">
        <form method="POST" id="importForm">
            <input type="hidden" name="action" value="create_import">
            <input type="hidden" name="products" id="productsData">
            <input type="hidden" name="total_amount" id="totalAmountInput">
            
            <!-- Thông tin phiếu nhập -->
            <div class="invoice-header">
                <div class="form-group">
                    <label>Số phiếu nhập</label>
                    <input type="text" value="<?php echo $newImportCode; ?>" readonly style="background: var(--bg-tertiary); font-weight: bold;">
                </div>
                
                <div class="form-group">
                    <label>Ngày nhập</label>
                    <input type="text" value="<?php echo date('d/m/Y H:i'); ?>" readonly style="background: var(--bg-tertiary);">
                </div>
                
                <div class="form-group">
                    <label for="supplier_id">Nhà cung cấp</label>
                    <select name="supplier_id" id="supplier_id" onchange="selectSupplier(this)">
                        <option value="">-- Chọn nhà cung cấp --</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?php echo $supplier['id']; ?>" 
                                    data-name="<?php echo htmlspecialchars($supplier['name']); ?>"
                                    data-phone="<?php echo htmlspecialchars($supplier['phone']); ?>">
                                <?php echo htmlspecialchars($supplier['name']); ?> - <?php echo htmlspecialchars($supplier['phone']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="supplier_name">Tên nhà cung cấp <span class="required">*</span></label>
                    <input type="text" name="supplier_name" id="supplier_name" placeholder="Nhập tên nhà cung cấp" required>
                </div>
                
                <div class="form-group">
                    <label for="supplier_phone">Số điện thoại</label>
                    <input type="tel" name="supplier_phone" id="supplier_phone" placeholder="Số điện thoại">
                </div>
                
                <div class="form-group">
                    <label for="payment_status">Trạng thái thanh toán</label>
                    <select name="payment_status" id="payment_status">
                        <option value="pending">⏳ Chưa thanh toán</option>
                        <option value="partial">💰 Thanh toán một phần</option>
                        <option value="paid">✅ Đã thanh toán</option>
                    </select>
                </div>
            </div>
            
            <!-- Danh sách sản phẩm nhập -->
            <div class="invoice-items">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
                    <h3>📋 Danh sách sản phẩm nhập</h3>
                    <div style="display: flex; gap: 0.5rem; flex-shrink: 0;">
                        <input type="text" id="productSearch" placeholder="🔍 Tìm sản phẩm... (F2)" 
                               style="width: 200px;" onkeyup="searchProducts(this.value)"
                               title="Nhập mã hoặc tên sản phẩm để tìm kiếm">
                        <button type="button" class="btn btn-secondary" onclick="addItemRow()" title="Thêm dòng sản phẩm mới (F3)">
                            ➕ Thêm sản phẩm
                        </button>
                    </div>
                </div>
                <div id="itemsContainer">
                    <!-- Items will be added here dynamically -->
                </div>
            </div>
            
            <!-- Tính toán tổng tiền -->
            <div class="total-section">
                <div style="font-size: 1.8rem; font-weight: 800; color: var(--primary-color); border-top: 2px solid var(--primary-color); padding-top: 1rem;">
                    Tổng tiền nhập: <span id="totalAmount">0₫</span>
                </div>
            </div>
            
            <!-- Ghi chú -->
            <div class="form-group full-width">
                <label for="notes">Ghi chú</label>
                <textarea name="notes" id="notes" placeholder="Ghi chú phiếu nhập (không bắt buộc)"></textarea>
            </div>
            
            <!-- Actions -->
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="resetForm()">🔄 Làm mới</button>
                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                    💾 Tạo phiếu nhập
                </button>
            </div>
        </form>
    </div>
    
    <!-- Danh sách phiếu nhập gần đây -->
    <div class="data-table" style="height: fit-content;">
        <div style="background: var(--success-gradient); color: white; padding: 1rem 1.5rem; font-weight: 600;">
            📋 Phiếu nhập gần đây
        </div>
        <div style="max-height: 500px; overflow-y: auto;">
            <?php if (empty($recentImports)): ?>
                <div style="padding: 2rem; text-align: center; color: var(--text-secondary);">
                    📄 Chưa có phiếu nhập nào
                </div>
            <?php else: ?>
                <?php foreach ($recentImports as $import): ?>
                    <div style="padding: 1rem 1.5rem; border-bottom: 1px solid rgba(40, 167, 69, 0.1); cursor: pointer; transition: var(--transition);" 
                         onclick="viewImportDetail(<?php echo $import['id']; ?>)" // MODIFIED: Pass only importId
                         onmouseover="this.style.background='var(--bg-tertiary)'"
                         onmouseout="this.style.background='transparent'">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <strong style="color: var(--success-color);"><?php echo $import['import_code']; ?></strong>
                            <span style="font-size: 0.85rem; color: var(--text-secondary);">
                                <?php echo formatDate($import['import_date']); ?>
                            </span>
                        </div>
                        <div style="font-size: 0.9rem; margin-bottom: 0.25rem;">
                            🏢 <?php echo htmlspecialchars($import['supplier_name'] ?: $import['supplier_name_db']); ?>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-weight: 600; color: var(--success-color);">
                                <?php echo number_format($import['total_amount']); ?>₫
                            </span>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <span style="background: 
                                    <?php 
                                    // Use $import['status'] which exists
                                    switch($import['status']) {
                                        case 'Hoàn thành': echo 'var(--success-gradient)'; break;
                                        case 'Đang xử lý': echo 'var(--warning-gradient)'; break;
                                        case 'Đã hủy': echo 'var(--danger-gradient)'; break;
                                        default: echo 'var(--secondary-gradient)'; // Fallback
                                    }
                                    ?>; color: white; padding: 0.15rem 0.5rem; border-radius: 8px; font-size: 0.75rem;">
                                    <?php 
                                    // Use $import['status'] for display text
                                    switch($import['status']) {
                                        case 'Hoàn thành': echo '✅ Hoàn thành'; break;
                                        case 'Đang xử lý': echo '⏳ Đang xử lý'; break;
                                        case 'Đã hủy': echo '❌ Đã hủy'; break;
                                        default: echo htmlspecialchars($import['status']); // Fallback
                                    }
                                    ?>
                                </span>                                <div style="display: flex; gap: 0.3rem;">
                                    <button class="btn btn-small btn-primary" onclick="event.stopPropagation(); viewImportDetail(<?php echo $import['id']; ?>)" title="Xem chi tiết (Enter)">
                                        👁️
                                    </button>
                                    <button class="btn btn-small btn-secondary" onclick="event.stopPropagation(); printImport(<?php echo $import['id']; ?>)" title="In phiếu nhập (Ctrl+P)">
                                        🖨️
                                    </button>
                                    <button class="btn btn-small btn-danger" onclick="event.stopPropagation(); deleteImport(<?php echo $import['id']; ?>, '<?php echo $import['import_code']; ?>')" title="Xóa phiếu nhập">
                                        🗑️
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Product data
const products = <?php echo json_encode($products); ?>;
let allProducts = [...products];
let itemCount = 0;
let cartItems = [];

// Format currency helper
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount) + '₫';
}

// Search products
function searchProducts(query) {
    query = query.toLowerCase().trim();
    if (query === '') {
        products.splice(0, products.length, ...allProducts);
    } else {
        const filtered = allProducts.filter(p => 
            p.name.toLowerCase().includes(query) || 
            p.product_code.toLowerCase().includes(query) ||
            (p.category_name && p.category_name.toLowerCase().includes(query))
        );
        products.splice(0, products.length, ...filtered);
    }
    
    // Update all existing selects
    document.querySelectorAll('.item-row select').forEach(select => {
        const currentValue = select.value;
        select.innerHTML = `
            <option value="">-- Chọn sản phẩm --</option>
            ${products.map(p => `
                <option value="${p.id}" 
                        data-name="${p.name}" 
                        data-import-price="${p.import_price || 0}"
                        ${p.id == currentValue ? 'selected' : ''}>
                    ${p.product_code} - ${p.name} (Tồn: ${p.stock_quantity})
                </option>
            `).join('')}
        `;
    });
}

// Select supplier
function selectSupplier(select) {
    const option = select.selectedOptions[0];
    if (option.value) {
        document.getElementById('supplier_name').value = option.dataset.name || '';
        document.getElementById('supplier_phone').value = option.dataset.phone || '';
    } else {
        document.getElementById('supplier_name').value = '';
        document.getElementById('supplier_phone').value = '';
    }
}

// Add item row
function addItemRow() {
    itemCount++;
    const container = document.getElementById('itemsContainer');
    
    const itemRow = document.createElement('div');
    itemRow.className = 'item-row';
    itemRow.id = `item_${itemCount}`;
    itemRow.innerHTML = `
        <select onchange="selectProduct(this, ${itemCount})" style="grid-column: 1;">
            <option value="">-- Chọn sản phẩm --</option>
            ${products.map(p => `
                <option value="${p.id}" 
                        data-name="${p.name}" 
                        data-import-price="${p.import_price || 0}">
                    ${p.product_code} - ${p.name} (Tồn: ${p.stock_quantity})
                </option>
            `).join('')}
        </select>
        
        <input type="number" placeholder="SL nhập" min="1" value="1" 
               onchange="updateQuantity(this, ${itemCount})" 
               onkeydown="handleQuantityKeydown(event, ${itemCount})"
               style="grid-column: 2;">
        
        <input type="number" placeholder="Giá nhập" min="0" step="1000"
               onchange="updateCost(this, ${itemCount})" 
               id="cost_${itemCount}" style="grid-column: 3;">
        
        <input type="text" placeholder="Thành tiền" readonly 
               id="total_${itemCount}" style="grid-column: 4; background: var(--bg-tertiary); font-weight: bold;">
        
        <button type="button" class="btn btn-small btn-danger" 
                onclick="removeItemRow(${itemCount})" style="grid-column: 5;">
            ✕
        </button>
    `;
    
    container.appendChild(itemRow);
    
    // Animation
    itemRow.style.opacity = '0';
    itemRow.style.transform = 'translateY(-20px)';
    setTimeout(() => {
        itemRow.style.opacity = '1';
        itemRow.style.transform = 'translateY(0)';
    }, 10);
}

// Handle quantity keydown
function handleQuantityKeydown(event, itemId) {
    if (event.key === 'Enter') {
        event.preventDefault();
        const row = document.getElementById(`item_${itemId}`);
        const select = row.querySelector('select');
        
        if (select.value) {
            addItemRow();
            setTimeout(() => {
                const newRow = document.getElementById(`item_${itemCount}`);
                if (newRow) {
                    newRow.querySelector('select').focus();
                }
            }, 100);
        }
    }
}

// Select product
function selectProduct(select, itemId) {
    const option = select.selectedOptions[0];
    const costInput = document.getElementById(`cost_${itemId}`);
    const quantityInput = select.parentElement.querySelector('input[type="number"]');
    const totalInput = document.getElementById(`total_${itemId}`);
    
    if (option.value) {
        const importPrice = parseFloat(option.dataset.importPrice) || 0;
        costInput.value = importPrice;
        
        const quantity = parseInt(quantityInput.value) || 1;
        const total = importPrice * quantity;
        totalInput.value = formatCurrency(total);
        
        updateCartItem(itemId, {
            product_id: option.value,
            product_name: option.dataset.name,
            unit_cost: importPrice,
            quantity: quantity
        });
    } else {
        costInput.value = '';
        totalInput.value = '';
        removeCartItem(itemId);
    }
    
    calculateTotal();
}

// Update quantity
function updateQuantity(input, itemId) {
    const row = document.getElementById(`item_${itemId}`);
    const select = row.querySelector('select');
    const option = select.selectedOptions[0];
    const costInput = document.getElementById(`cost_${itemId}`);
    
    if (option.value) {
        const cost = parseFloat(costInput.value) || 0;
        const quantity = parseInt(input.value) || 0;
        const total = cost * quantity;
        
        document.getElementById(`total_${itemId}`).value = formatCurrency(total);
        
        updateCartItem(itemId, {
            product_id: option.value,
            product_name: option.dataset.name,
            unit_cost: cost,
            quantity: quantity
        });
    }
    
    calculateTotal();
}

// Update cost
function updateCost(input, itemId) {
    const row = document.getElementById(`item_${itemId}`);
    const select = row.querySelector('select');
    const option = select.selectedOptions[0];
    const quantityInput = row.querySelector('input[type="number"]');
    
    if (option.value) {
        const cost = parseFloat(input.value) || 0;
        const quantity = parseInt(quantityInput.value) || 0;
        const total = cost * quantity;
        
        document.getElementById(`total_${itemId}`).value = formatCurrency(total);
        
        updateCartItem(itemId, {
            product_id: option.value,
            product_name: option.dataset.name,
            unit_cost: cost,
            quantity: quantity
        });
    }
    
    calculateTotal();
}

// Update cart item
function updateCartItem(itemId, item) {
    const existingIndex = cartItems.findIndex(i => i.itemId === itemId);
    
    if (existingIndex >= 0) {
        if (item.quantity > 0 && item.unit_cost > 0) {
            cartItems[existingIndex] = { ...item, itemId };
        } else {
            cartItems.splice(existingIndex, 1);
        }
    } else if (item.quantity > 0 && item.unit_cost > 0) {
        cartItems.push({ ...item, itemId });
    }
}

// Remove cart item
function removeCartItem(itemId) {
    const index = cartItems.findIndex(i => i.itemId === itemId);
    if (index >= 0) {
        cartItems.splice(index, 1);
    }
}

// Remove item row
function removeItemRow(itemId) {
    const row = document.getElementById(`item_${itemId}`);
    row.style.opacity = '0';
    row.style.transform = 'translateX(-100%)';
    
    setTimeout(() => {
        row.remove();
        removeCartItem(itemId);
        calculateTotal();
    }, 300);
}

// Calculate total
function calculateTotal() {
    const total = cartItems.reduce((sum, item) => sum + (item.unit_cost * item.quantity), 0);
    
    document.getElementById('totalAmount').textContent = formatCurrency(total);
    document.getElementById('totalAmountInput').value = total;
    document.getElementById('productsData').value = JSON.stringify(cartItems);
    
    // Enable/disable submit button
    const submitBtn = document.getElementById('submitBtn');
    const supplierName = document.getElementById('supplier_name').value.trim();
    
    if (cartItems.length > 0 && supplierName && total > 0) {
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        submitBtn.title = 'Tạo phiếu nhập';
    } else {
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.5';
        
        // Show appropriate message based on what's missing
        if (!supplierName) {
            submitBtn.title = 'Vui lòng nhập tên nhà cung cấp';
        } else if (cartItems.length === 0) {
            submitBtn.title = 'Vui lòng thêm ít nhất một sản phẩm';
        } else if (total <= 0) {
            submitBtn.title = 'Tổng tiền phải lớn hơn 0';
        }
    }
}

// Reset form
function resetForm() {
    if (confirm('Bạn có chắc chắn muốn làm mới form?')) {
        document.getElementById('importForm').reset();
        document.getElementById('itemsContainer').innerHTML = '';
        cartItems = [];
        itemCount = 0;
        calculateTotal();
        showToast('Đã làm mới form', 'success');
    }
}

// View import detail
function viewImportDetail(importId) {
    console.log(`[viewImportDetail] Called for importId: ${importId}`);
    showToast('Đang tải chi tiết phiếu nhập...', 'info');
    fetch(`ajax/get_import_detail.php?id=${importId}`)
        .then(response => {
            console.log('[viewImportDetail] Fetch response received:', response);
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('[viewImportDetail] HTTP error response text:', text);
                    throw new Error(`Lỗi HTTP ${response.status}: ${text || 'Không có thông tin lỗi chi tiết'}`);
                });
            }
            return response.text(); 
        })
        .then(text => {
            console.log('[viewImportDetail] Response text received:', text);
            try {
                const data = JSON.parse(text);
                console.log('[viewImportDetail] Parsed JSON data:', data);
                if (data.error) {
                    showToast(`Lỗi tải chi tiết: ${data.error}`, 'error');
                    console.error('[viewImportDetail] Server error in JSON:', data.error);
                    return;
                }
                // Check if import and details data are present
                if (data.import && data.details) {
                    console.log('[viewImportDetail] Data is valid, calling showImportDetailModal.');
                    showImportDetailModal(data.import, data.details);
                } else {
                    console.error('[viewImportDetail] Invalid data structure received:', data);
                    showToast('Lỗi: Dữ liệu chi tiết phiếu nhập không đầy đủ.', 'error');
                }
            } catch (e) {
                console.error('[viewImportDetail] Lỗi phân tích JSON:', e);
                console.error('[viewImportDetail] Dữ liệu nhận được không phải JSON:', text);
                showToast('Lỗi: Dữ liệu trả về không hợp lệ. Vui lòng kiểm tra console (F12).', 'error');
            }
        })
        .catch(error => {
            console.error('[viewImportDetail] Lỗi khi tải chi tiết phiếu nhập:', error);
            showToast(`Lỗi: ${error.message}`, 'error');
        });
}

// Show import detail modal
function showImportDetailModal(importData, details) {
    console.log('[showImportDetailModal] Called with importData:', importData, 'and details:', details);
    
    const existingModal = document.querySelector('.modal-overlay.import-detail-modal');
    if (existingModal) {
        console.log('[showImportDetailModal] Removing existing import detail modal.');
        existingModal.remove();
    }

    const modalId = 'importDetailModal_' + Date.now();
    const modal = document.createElement('div');
    modal.id = modalId;
    modal.className = 'modal-overlay import-detail-modal'; // Added a specific class for easier targeting
    // Ensure the modal is displayed; CSS for .modal-overlay should handle visibility
    // If not, uncomment and adjust: modal.style.display = 'flex'; 

    modal.innerHTML = `
        <div class="modal-content" style="max-width: 900px;">
            <div class="modal-header">
                <h3>📋 Chi tiết phiếu nhập #${importData.import_code || importData.id}</h3>
                <button class="modal-close" onclick="closeDynamicModal('${modalId}')">&times;</button>
            </div>
            <div class="modal-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div class="import-info">
                        <h4>📝 Thông tin phiếu nhập</h4>
                        <p><strong>Mã phiếu:</strong> ${importData.import_code}</p>
                        <p><strong>Ngày nhập:</strong> ${importData.created_at_formatted}</p>
                        <p><strong>Tình trạng:</strong> 
                            <span class="badge ${importData.status === 'Hoàn thành' ? 'success' : importData.status === 'Đang xử lý' ? 'warning' : 'danger'}">
                                ${importData.status || 'Không rõ'}
                            </span>
                        </p>
                        <p><strong>Ghi chú:</strong> ${importData.notes || 'Không có ghi chú'}</p>
                    </div>
                    <div class="supplier-info">
                        <h4>🏢 Thông tin nhà cung cấp</h4>
                        <p><strong>Tên NCC:</strong> ${importData.supplier_name || 'Không xác định'}</p>
                        <p><strong>Điện thoại:</strong> ${importData.supplier_phone || 'Không có'}</p>
                        <p><strong>Địa chỉ:</strong> ${importData.supplier_address || 'Không có'}</p>
                    </div>
                </div>

                <h4>📦 Danh sách sản phẩm</h4>
                <div class="table-responsive">
                    <table class="import-detail-table">
                        <thead>
                            <tr>
                                <th>Mã SP</th>
                                <th>Tên sản phẩm</th>
                                <th>Số lượng</th>
                                <th>Đơn giá</th>
                                <th>Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${details.map(item => `
                                <tr>
                                    <td>${item.product_code}</td>
                                    <td>${item.product_name}</td>
                                    <td class="text-center">${item.quantity}</td>
                                    <td class="text-right">${item.unit_price_formatted}đ</td>
                                    <td class="text-right"><strong>${item.total_price_formatted}đ</strong></td>
                                </tr>
                            `).join('')}
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="4"><strong>Tổng cộng:</strong></td>
                                <td class="text-right"><strong class="total-amount">${importData.total_amount_formatted}đ</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDynamicModal('${modalId}')">Đóng</button>
                <button type="button" class="btn btn-primary" onclick="printImport(${importData.id})">🖨️ In phiếu</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    console.log('[showImportDetailModal] Modal appended to body. ID:', modalId, 'Element:', modal);
    
    // Force a reflow to ensure display style is applied before opacity transition (if any)
    void modal.offsetWidth;
    modal.style.opacity = '1'; // Assuming you have CSS for fade-in

    // Add styles if not exists (this part seems fine)
    if (!document.querySelector('#import-detail-styles')) {
        const styles = document.createElement('style');
        styles.id = 'import-detail-styles';
        styles.textContent = `
            .modal-overlay.import-detail-modal {
                /* Ensure it's visible - adjust as per your existing .modal-overlay */
                display: flex; /* or block */
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.5);
                justify-content: center;
                align-items: center;
                z-index: 1000; /* Ensure it's on top */
                opacity: 0; /* For fade-in effect */
                transition: opacity 0.3s ease-in-out;
            }
            .import-detail-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 0.5rem;
            }
            
            .import-detail-table th,
            .import-detail-table td {
                border: 1px solid #ddd;
                padding: 0.5rem;
                text-align: left;
            }
            
            .import-detail-table th {
                background-color: #f8f9fa;
                font-weight: 600;
            }
            
            .import-detail-table .text-center {
                text-align: center;
            }
            
            .import-detail-table .text-right {
                text-align: right;
            }
            
            .total-row {
                background-color: #f8f9fa;
                font-weight: bold;
            }
            
            .total-amount {
                color: #2563eb;
                font-size: 1.1em;
            }
            
            .import-info h4,
            .supplier-info h4 {
                color: #2563eb;
                margin-bottom: 0.5rem;
                border-bottom: 1px solid #eee;
                padding-bottom: 0.25rem;
            }
            
            .import-info p,
            .supplier-info p {
                margin-bottom: 0.25rem;
            }
        `;
        document.head.appendChild(styles);
    }
}

// Generic function to close dynamically created modals by ID
function closeDynamicModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        console.log(`[closeDynamicModal] Closing modal with ID: ${modalId}`);
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.remove();
            console.log(`[closeDynamicModal] Modal ${modalId} removed.`);
        }, 300); // Match transition time
    } else {
        console.warn(`[closeDynamicModal] Modal with ID ${modalId} not found.`);
    }
}

// Print import
function printImport(importId) {
    const printWindow = window.open(`print_import.php?id=${importId}&auto_print=1`, '_blank', 
        'width=800,height=900,scrollbars=yes,resizable=yes');
    
    if (!printWindow) {
        showToast('Không thể mở cửa sở in. Vui lòng cho phép popup.', 'error');
    }
}

// Delete import
function deleteImport(importId, importCode) {
    event.stopPropagation(); // Prevent triggering parent click event
    
    if (confirm(`Bạn có chắc chắn muốn xóa phiếu nhập ${importCode}?\nLưu ý: Hành động này sẽ giảm số lượng tồn kho tương ứng.`)) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'index.php?page=imports';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_import';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'import_id';
        idInput.value = importId;
        
        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        
        form.submit();
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    addItemRow();
    showToast('💡 Phím tắt: F2 (Tìm SP), F3 (Thêm SP), Ctrl+Enter (Lưu), Ctrl+R (Reset)', 'info');
    
    // Add form submission handler
    document.getElementById('importForm').addEventListener('submit', function(e) {
        // Prevent default submission
        e.preventDefault();
        
        // Validate form
        const supplierName = document.getElementById('supplier_name').value.trim();
        if (!supplierName) {
            showToast('Vui lòng nhập tên nhà cung cấp', 'error');
            document.getElementById('supplier_name').focus();
            return false;
        }
        
        if (cartItems.length === 0) {
            showToast('Vui lòng thêm ít nhất một sản phẩm', 'error');
            return false;
        }
        
        // Show loading state
        const submitBtn = document.getElementById('submitBtn');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '⏳ Đang xử lý...';
        
        // Submit the form
        setTimeout(() => {
            this.submit();
        }, 500);
    });
    
    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // F2 - Focus search
        if (e.key === 'F2') {
            e.preventDefault();
            document.getElementById('productSearch').focus();
        }
        
        // F3 - Add new item row
        if (e.key === 'F3') {
            e.preventDefault();
            addItemRow();
        }
        
        // Ctrl+Enter - Submit form
        if (e.key === 'Enter' && e.ctrlKey) {
            e.preventDefault();
            const submitBtn = document.getElementById('submitBtn');
            if (!submitBtn.disabled) {
                submitBtn.click();
            } else {
                showToast(submitBtn.title || 'Vui lòng điền đầy đủ thông tin', 'warning');
            }
        }
        
        // Ctrl+R - Reset form
        if (e.key === 'r' && e.ctrlKey) {
            e.preventDefault();
            resetForm();
        }
    });
});

// Format date function for JavaScript
function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', {hour: '2-digit', minute:'2-digit'});
}
</script>
