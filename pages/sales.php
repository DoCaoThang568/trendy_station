<?php
require_once __DIR__ . '/../includes/functions.php'; // Include the functions file
/**
 * Sales Page - Bán hàng & Lập hóa đơn
 */

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_sale':
            try {
                // Start transaction
                $pdo->beginTransaction();
                
                // Generate sale code
                $saleCode = generateCode('HD', 'sales', 'sale_code');
                
                // Get form data
                $customer_id = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
                $customer_name = trim($_POST['customer_name'] ?? 'Khách vãng lai');
                $customer_phone = trim($_POST['customer_phone'] ?? '');
                
                // Amounts from hidden inputs, should be calculated client-side and validated server-side if necessary
                $subtotal_from_form = floatval($_POST['subtotal'] ?? 0); // This is the sum of (item.price * item.quantity)
                $discount_percent_from_form = floatval($_POST['discount_percent'] ?? 0);
                $discount_amount_from_form = floatval($_POST['discount_amount'] ?? 0); // This is the overall discount amount
                $final_total_from_form = floatval($_POST['total_amount'] ?? 0); // This is the final amount after overall discount

                $payment_method = $_POST['payment_method'] ?? 'Tiền mặt';
                $notes = trim($_POST['notes'] ?? '');
                $payment_status = 'Đã thanh toán'; // Default or from form if available
                $cashier_name = 'Admin'; // Or from logged-in user session

                // Insert sale record
                // Note: The sales table has total_amount AND final_amount.
                // Assuming total_amount is pre-discount and final_amount is post-discount.
                // The form sends 'subtotal' (pre-discount sum of items) and 'total_amount' (final post-discount).
                $sql_sale = "INSERT INTO sales (sale_code, customer_id, customer_name, customer_phone, 
                               total_amount, discount_percent, discount_amount, final_amount, 
                               payment_method, payment_status, notes, cashier_name, sale_date, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                
                executeQuery($sql_sale, [
                    $saleCode, 
                    $customer_id, 
                    $customer_name, 
                    $customer_phone,
                    $subtotal_from_form,         // total_amount (sum of item totals before overall discount)
                    $discount_percent_from_form, // discount_percent (overall discount %)
                    $discount_amount_from_form,  // discount_amount (overall discount value)
                    $final_total_from_form,      // final_amount (amount after overall discount)
                    $payment_method, 
                    $payment_status,
                    $notes,
                    $cashier_name
                ]);
                
                $saleId = $pdo->lastInsertId();
                
                // Insert sale details
                $products_json = $_POST['products'] ?? '[]';
                $products = json_decode($products_json, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception('Lỗi giải mã JSON sản phẩm: ' . json_last_error_msg());
                }

                if (empty($products)) {
                    throw new Exception('Không có sản phẩm nào trong hóa đơn.');
                }

                foreach ($products as $product) {
                    if (!empty($product['product_id']) && isset($product['quantity']) && $product['quantity'] > 0 && isset($product['unit_price'])) {
                        $product_id = (int)$product['product_id'];
                        $quantity = (int)$product['quantity'];
                        $unit_price = floatval($product['unit_price']);
                        $product_name = trim($product['product_name'] ?? ''); // Get product name from submitted data
                        
                        // Fetch product_code from DB to ensure accuracy
                        $stmt_product_code = $pdo->prepare("SELECT product_code FROM products WHERE id = ?");
                        $stmt_product_code->execute([$product_id]);
                        $product_db_data = $stmt_product_code->fetch(PDO::FETCH_ASSOC);
                        $product_code = $product_db_data ? $product_db_data['product_code'] : 'N/A';

                        if (empty($product_name) && $product_db_data) {
                             // Fallback if product_name wasn't in JSON, try to get from DB (though it should be in JSON)
                            $stmt_p_name = $pdo->prepare("SELECT name FROM products WHERE id = ?");
                            $stmt_p_name->execute([$product_id]);
                            $p_name_row = $stmt_p_name->fetch(PDO::FETCH_ASSOC);
                            if($p_name_row) $product_name = $p_name_row['name'];
                        }

                        $total_price = $quantity * $unit_price;
                        // Assuming no per-item discount from the form structure provided
                        $item_discount_percent = 0;
                        $item_discount_amount = 0;
                        $item_final_price = $total_price; 

                        $sql_details = "INSERT INTO sale_details (sale_id, product_id, product_code, product_name, quantity, unit_price, total_price, discount_percent, discount_amount, final_price) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        executeQuery($sql_details, [
                            $saleId, 
                            $product_id,
                            $product_code,
                            $product_name,
                            $quantity,
                            $unit_price,
                            $total_price, // total_price for the item line
                            $item_discount_percent, // per-item discount_percent
                            $item_discount_amount,  // per-item discount_amount
                            $item_final_price       // final_price for the item line
                        ]);
                        
                        // Update stock
                        $sql_update_stock = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?";
                        $stmt_stock = executeQuery($sql_update_stock, [$quantity, $product_id, $quantity]);
                        if ($stmt_stock->rowCount() == 0) {
                            // Stock was not enough or product_id invalid, throw error to rollback
                            throw new Exception("Không đủ tồn kho cho sản phẩm '${product_name}' (ID: ${product_id}) hoặc sản phẩm không tồn tại.");
                        }
                        
                        // Record stock movement (IF stock_movements table is used)
                        /* 
                        $sql_stock_movement = "INSERT INTO stock_movements (product_id, movement_type, reference_id, reference_code, 
                                       quantity_change, stock_before, stock_after, notes, created_by) 
                                SELECT ?, 'sale', ?, ?, ?, p.stock_quantity + ?, p.stock_quantity, ?, ?
                                FROM products p WHERE p.id = ?";
                        executeQuery($sql_stock_movement, [
                            $product_id, 
                            $saleId, 
                            $saleCode, // reference_code
                            -$quantity, // quantity_change (negative for sale)
                            $quantity, // for stock_before calculation (stock_after + quantity_sold)
                            "Bán hàng - HĐ: $saleCode", // notes
                            $cashier_name, // created_by
                            $product_id
                        ]);
                        */
                    } else {
                        // Optional: Log or handle invalid product entries in the products JSON
                        // For now, we skip them if they lack essential data.
                    }
                }
                
                // Update customer total_spent and total_orders if customer_id is valid
                if ($customer_id) {
                    $sql_update_customer = "UPDATE customers SET total_spent = total_spent + ?, total_orders = total_orders + 1 WHERE id = ?";
                    executeQuery($sql_update_customer, [$final_total_from_form, $customer_id]);
                }
                  $pdo->commit();
                
                // Check if this is an AJAX request
                $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                         strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                
                if ($isAjax) {
                    // Return JSON response for AJAX
                    
                    // Clean any existing output buffers to prevent HTML leakage
                    while (ob_get_level() > 0) {
                        ob_end_clean();
                    }
                    
                    header('Content-Type: application/json');
                    
                    // Prepare additional data for dynamic update
                    $formatted_sale_date = date('d/m/Y H:i'); // Current time as sale date
                    
                    // Translate payment method for display
                    $payment_method_display = translatePaymentMethod($payment_method);
                    $status_color = getPaymentMethodStatusColor($payment_method); // Use a more general color function if needed

                    echo json_encode([
                        'success' => true,
                        'message' => "Tạo hóa đơn $saleCode thành công!",
                        'sale' => [
                            'sale_code' => $saleCode,
                            'id' => $saleId,
                            'customer_name' => $customer_name, // Customer name from form/default
                            'sale_date' => $formatted_sale_date,
                            'final_amount' => $final_total_from_form,
                            'formatted_total' => number_format($final_total_from_form) . "đ",
                            'payment_method' => $payment_method, // Original payment method code
                            'payment_method_display' => $payment_method_display, // User-friendly payment method
                            'status_color' => $status_color // Color based on payment method/status
                        ]
                    ]);
                    exit;
                } else {
                    // Traditional form submission - redirect
                    $_SESSION['success_message'] = "Tạo hóa đơn $saleCode thành công! Tổng tiền: " . number_format($final_total_from_form) . "đ. ID hóa đơn: $saleId";
                    header('Location: index.php?page=sales');
                    exit;
                }

            } catch (Exception $e) {
                if ($pdo && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                
                // Check if this is an AJAX request
                $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                         strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                
                if ($isAjax) {
                    // Clean any existing output buffers
                    while (ob_get_level() > 0) {
                        ob_end_clean();
                    }
                
                    // Return JSON error response for AJAX
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'message' => 'Lỗi khi tạo hóa đơn: ' . $e->getMessage()
                    ]);
                    exit;
                } else {
                    // Traditional form submission - redirect with error
                    $_SESSION['error_message'] = 'Lỗi khi tạo hóa đơn: ' . $e->getMessage();
                    header('Location: index.php?page=sales');
                    exit;
                }
            }
            break;
        case 'delete_sale': // Make sure this case exists and is functional if needed
            // ... (implementation for deleting a sale, including reversing stock, etc.)
            // try {
            //     $saleId = $_POST['sale_id'];
            //     $pdo->beginTransaction();

            //     // Fetch sale details to revert stock
            //     $details = fetchAll("SELECT product_id, quantity FROM sale_details WHERE sale_id = ?", [$saleId]);
            //     foreach ($details as $detail) {
            //         executeQuery("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?", [$detail['quantity'], $detail['product_id']]);
            //         // Optional: Record stock movement for sale_cancel
            //     }

            //     // Delete sale details and sale
            //     executeQuery("DELETE FROM sale_details WHERE sale_id = ?", [$saleId]);
            //     executeQuery("DELETE FROM sales WHERE id = ?", [$saleId]);
                
            //     // Optional: Update customer total_spent and total_orders

            //     $pdo->commit();
            //     $_SESSION['success_message'] = 'Xóa hóa đơn thành công!';
            // } catch (Exception $e) {
            //     $pdo->rollBack();
            //     $_SESSION['error_message'] = 'Lỗi xóa hóa đơn: ' . $e->getMessage();
            // }
            // header('Location: index.php?page=sales');
            // exit;
            break;
    }
    
    // Fallback redirect if action not handled or if create_sale didn't exit (e.g. if AJAX part is added and exit is conditional)
    // However, create_sale above has its own redirect/exit.
    // This line might be redundant if all cases handle their own exit/redirect.
    // header('Location: index.php?page=sales'); 
    // exit;
}

// Get recent sales
$recentSales = fetchAll("
    SELECT s.*, c.name as customer_name_db 
    FROM sales s 
    LEFT JOIN customers c ON s.customer_id = c.id 
    ORDER BY s.sale_date DESC 
    LIMIT 10
");

// Get products for selection
$products_stmt = $pdo->query("
    SELECT 
        p.id, 
        p.name, 
        p.product_code, 
        p.stock_quantity, 
        p.selling_price, 
        p.cost_price as import_price,
        c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.is_active = 1 AND p.stock_quantity > 0
    ORDER BY p.name ASC
");
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get customers for selection
$customers = fetchAll("SELECT * FROM customers WHERE is_active = 1 ORDER BY name");

// Generate new sale code
$newSaleCode = generateCode('HD', 'sales', 'sale_code');
?>

<h1 class="page-title">💰 Bán hàng - Lập hóa đơn</h1>

<div style="display: grid; grid-template-columns: 1fr 400px; gap: 2rem; align-items: start;">
    <!-- Form tạo hóa đơn -->
    <div class="form-container">
        <form method="POST" id="saleForm">
            <input type="hidden" name="action" value="create_sale">
            <input type="hidden" name="products" id="productsData">
            <input type="hidden" name="subtotal" id="subtotalInput">
            <input type="hidden" name="discount_amount" id="discountAmountInput">
            <input type="hidden" name="total_amount" id="totalAmountInput">
            
            <!-- Thông tin hóa đơn -->
            <div class="invoice-header">
                <div class="form-group">
                    <label>Số hóa đơn</label>
                    <input type="text" value="<?php echo $newSaleCode; ?>" readonly style="background: var(--bg-tertiary); font-weight: bold;">
                </div>
                
                <div class="form-group">
                    <label>Ngày bán</label>
                    <input type="text" value="<?php echo date('d/m/Y H:i'); ?>" readonly style="background: var(--bg-tertiary);">
                </div>
                  <div class="form-group">
                    <label for="customer_id">Khách hàng</label>
                    <select name="customer_id" id="customer_id" onchange="selectCustomer(this)" title="Chọn khách hàng có sẵn hoặc để trống cho khách vãng lai">
                        <option value="">-- Khách vãng lai --</option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?php echo $customer['id']; ?>" 
                                    data-name="<?php echo htmlspecialchars($customer['name']); ?>"
                                    data-phone="<?php echo htmlspecialchars($customer['phone']); ?>">
                                <?php echo htmlspecialchars($customer['name']); ?> - <?php echo htmlspecialchars($customer['phone']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="customer_name">Tên khách hàng <span class="required">*</span></label>
                    <input type="text" name="customer_name" id="customer_name" placeholder="Nhập tên khách hàng" required>
                </div>
                  <div class="form-group">
                    <label for="customer_phone">Số điện thoại</label>
                    <input type="tel" name="customer_phone" id="customer_phone" placeholder="Số điện thoại" 
                           title="Nhập số điện thoại để tự động tìm khách hàng có sẵn">
                    <div class="quick-add-hint">💡 Nhập từ 4 số để tự động tìm khách hàng</div>
                </div>
                
                <div class="form-group">
                    <label for="payment_method">Phương thức thanh toán</label>
                    <select name="payment_method" id="payment_method">
                        <option value="cash">💵 Tiền mặt</option>
                        <option value="card">💳 Thẻ</option>
                        <option value="transfer">🏦 Chuyển khoản</option>
                    </select>
                </div>
            </div>
              <!-- Danh sách sản phẩm -->
            <div class="invoice-items">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3>📦 Danh sách sản phẩm</h3>
                    <div style="display: flex; gap: 0.5rem;">                        <input type="text" id="productSearch" placeholder="🔍 Tìm sản phẩm... (F2)" 
                               style="width: 200px;" onkeyup="searchProducts(this.value)"
                               title="Nhập mã hoặc tên sản phẩm để tìm kiếm. Nhấn Enter để thêm nhanh sản phẩm theo mã.">
                        <button type="button" class="btn btn-secondary" onclick="addItemRow()" title="Thêm dòng sản phẩm mới (F3)">>
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
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div>
                        <label for="discount_percent">Giảm giá (%)</label>
                        <input type="number" name="discount_percent" id="discount_percent" value="0" min="0" max="100" step="0.1" onchange="calculateTotal()">
                    </div>
                    <div>
                        <label>Giảm giá (VND)</label>
                        <input type="text" id="discountAmount" readonly style="background: var(--bg-tertiary);">
                    </div>
                </div>
                
                <div style="font-size: 1.2rem; margin-bottom: 1rem;">
                    <div>Tạm tính: <span id="subtotal">0₫</span></div>
                    <div>Giảm giá: <span id="discountDisplay">0₫</span></div>
                </div>
                
                <div style="font-size: 1.8rem; font-weight: 800; color: var(--primary-color); border-top: 2px solid var(--primary-color); padding-top: 1rem;">
                    Tổng cộng: <span id="totalAmount">0₫</span>
                </div>
            </div>
            
            <!-- Ghi chú -->
            <div class="form-group full-width">
                <label for="notes">Ghi chú</label>
                <textarea name="notes" id="notes" placeholder="Ghi chú đơn hàng (không bắt buộc)"></textarea>
            </div>
            
            <!-- Actions -->
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="resetForm()">🔄 Làm mới</button>
                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                    💾 Tạo hóa đơn
                </button>
            </div>
        </form>
    </div>
    
    <!-- Danh sách hóa đơn gần đây -->
    <div class="data-table" style="height: fit-content;">
        <div style="background: var(--primary-gradient); color: white; padding: 1rem 1.5rem; font-weight: 600;">
            📋 Hóa đơn gần đây
        </div>
        <div style="max-height: 500px; overflow-y: auto;">
            <?php if (empty($recentSales)): ?>
                <div style="padding: 2rem; text-align: center; color: var(--text-secondary);">
                    📄 Chưa có hóa đơn nào
                </div>
            <?php else: ?>
                <?php foreach ($recentSales as $sale): ?>
                    <div style="padding: 1rem 1.5rem; border-bottom: 1px solid rgba(102, 126, 234, 0.1); cursor: pointer; transition: var(--transition);" 
                         onclick="viewSaleDetail('<?php echo $sale['sale_code']; ?>', <?php echo $sale['id']; ?>)"
                         onmouseover="this.style.background='var(--bg-tertiary)'"
                         onmouseout="this.style.background='transparent'">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <strong style="color: var(--primary-color);"><?php echo $sale['sale_code']; ?></strong>
                            <span style="font-size: 0.85rem; color: var(--text-secondary);">
                                <?php echo formatDate($sale['sale_date']); ?>
                            </span>
                        </div>
                        <div style="font-size: 0.9rem; margin-bottom: 0.25rem;">
                            👤 <?php echo htmlspecialchars($sale['customer_name'] ?: ($sale['customer_name_db'] ?? 'Khách vãng lai')); ?>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="display: flex; gap: 0.5rem; align-items: center;">
                                <span style="font-weight: 600; color: var(--success-color);">
                                    <?php echo number_format($sale['final_amount']); ?>₫
                                </span>
                                <button class="btn btn-small btn-primary" onclick="event.stopPropagation(); printInvoice(<?php echo $sale['id']; ?>)" title="In hóa đơn">
                                    🖨️
                                </button>
                            </span>
                            <?php
                                $paymentMethodValue = $sale['payment_method'];
                                $paymentMethodDisplay = '-'; // Default to dash
                                $paymentMethodBgColor = 'var(--primary-color)'; // Default background for dash or unknown

                                switch ($paymentMethodValue) {
                                    case 'Tiền mặt':
                                        $paymentMethodDisplay = '💵 Tiền mặt';
                                        $paymentMethodBgColor = 'var(--success-color, #28a745)';
                                        break;
                                    case 'Thẻ tín dụng': // Matching ENUM value
                                        $paymentMethodDisplay = '💳 Thẻ';
                                        $paymentMethodBgColor = 'var(--info-color, #17a2b8)'; // Example color for card
                                        break;
                                    case 'Chuyển khoản': // Matching ENUM value
                                        $paymentMethodDisplay = '🏦 Chuyển khoản';
                                        $paymentMethodBgColor = 'var(--purple-color, #6f42c1)';
                                        break;
                                    case 'Ví điện tử': // Matching ENUM value
                                        $paymentMethodDisplay = '📱 Ví điện tử';
                                        $paymentMethodBgColor = 'var(--warning-color, #ffc107)'; // Example color for e-wallet
                                        break;
                                    // No default case needed if $paymentMethodDisplay and $paymentMethodBgColor are pre-set
                                }
                            ?>
                            <span style="background: <?php echo $paymentMethodBgColor; ?>; color: white; padding: 0.15rem 0.5rem; border-radius: 8px; font-size: 0.75rem;">
                                <?php echo $paymentMethodDisplay; ?>
                            </span>
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
let allProducts = [...products]; // Backup for search
let itemCount = 0;
let cartItems = [];

// Format currency helper
function formatCurrency(amount) {
    // Ensure amount is a number, default to 0 if not (e.g. NaN, undefined)
    const numAmount = parseFloat(amount);
    return new Intl.NumberFormat('vi-VN').format(isNaN(numAmount) ? 0 : numAmount) + '₫';
}

// Search products
function searchProducts(query) {
    query = query.toLowerCase().trim();
    if (query === '') {
        products.splice(0, products.length, ...allProducts);
    } else {
        const filtered = allProducts.filter(p => 
            p.name.toLowerCase().includes(query) || 
            p.product_code.toLowerCase().includes(query) || // Changed p.code to p.product_code
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
                        data-price="${p.selling_price}"
                        data-stock="${p.stock_quantity}"
                        ${p.id == currentValue ? 'selected' : ''}>
                    ${p.product_code} - ${p.name} (${p.stock_quantity} còn lại)
                </option>
            `).join('')}
        `;
    });
}

// Select customer
function selectCustomer(select) {
    const option = select.selectedOptions[0];
    if (option.value) {
        document.getElementById('customer_name').value = option.dataset.name || '';
        document.getElementById('customer_phone').value = option.dataset.phone || '';
    } else {
        document.getElementById('customer_name').value = '';
        document.getElementById('customer_phone').value = '';
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
            ${products.map(p => {
                const stockClass = p.stock_quantity <= 5 ? (p.stock_quantity <= 2 ? 'stock-danger' : 'stock-warning') : '';
                return `
                    <option value="${p.id}" 
                            data-name="${p.name}" 
                            data-price="${p.selling_price}"
                            data-stock="${p.stock_quantity}"
                            class="${stockClass}">
                        ${p.product_code} - ${p.name} (${p.stock_quantity} còn lại)
                    </option>
                `;
            }).join('')}
        </select>
        
        <input type="number" placeholder="SL" min="1" value="1" 
               onchange="updateQuantity(this, ${itemCount})" 
               onkeydown="handleQuantityKeydown(event, ${itemCount})"
               style="grid-column: 2;">
        
        <input type="text" placeholder="Đơn giá" readonly 
               id="price_${itemCount}" style="grid-column: 3; background: var(--bg-tertiary);">
        
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

// Handle quantity keydown (Enter to add new row)
function handleQuantityKeydown(event, itemId) {
    if (event.key === 'Enter') {
        event.preventDefault();
        const row = document.getElementById(`item_${itemId}`);
        const select = row.querySelector('select');
        
        if (select.value) {
            addItemRow();
            // Focus on new product select
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
    const priceInput = document.getElementById(`price_${itemId}`);
    const quantityInput = select.parentElement.querySelector('input[type="number"]');

    if (option.value) {
        let price = parseFloat(option.dataset.price);
        if (isNaN(price) || typeof option.dataset.price === 'undefined') {
            price = 0;
            console.warn(`Product ID ${option.value} (${option.dataset.name || 'N/A'}) has an invalid or missing price. Defaulting to 0₫.`);
            showToast(`Giá sản phẩm "${option.dataset.name || 'N/A'}" không hợp lệ hoặc bị thiếu. Đặt tạm là 0₫.`, 'warning');
        }

        const stock = parseInt(option.dataset.stock);
        const productName = option.dataset.name || 'Sản phẩm không tên';

        priceInput.value = formatCurrency(price);
        quantityInput.max = stock;
        if (parseInt(quantityInput.value) > stock) {
            quantityInput.value = stock > 0 ? 1 : 0; // Default to 1 if stock available, else 0
        }


        updateCartItem(itemId, {
            product_id: option.value,
            product_name: productName,
            unit_price: price,
            quantity: parseInt(quantityInput.value) || 0,
            stock: stock
        });
    } else {
        priceInput.value = '';
        quantityInput.max = '';
        removeCartItem(itemId);
    }
    // updateQuantity will be called, which also calls calculateTotal
    // Trigger updateQuantity to ensure total is calculated and cart is updated correctly
    updateQuantity(quantityInput, itemId);
}

// Update quantity
function updateQuantity(input, itemId) {
    const row = document.getElementById(`item_${itemId}`);
    const select = row.querySelector('select');
    const option = select.selectedOptions[0];
    let currentQuantity = parseInt(input.value) || 0;

    if (option.value) {
        let price = parseFloat(option.dataset.price);
        if (isNaN(price) || typeof option.dataset.price === 'undefined') {
            price = 0;
            // Warning already shown in selectProduct, ensure price is safe
        }

        const stock = parseInt(option.dataset.stock);
        const productName = option.dataset.name || 'Sản phẩm không tên';

        // Validate and correct quantity
        if (currentQuantity < 0) {
            currentQuantity = 0;
            input.value = 0;
        }

        if (stock <= 0 && currentQuantity > 0) { // Product is out of stock
            showToast(`Sản phẩm "${productName}" đã hết hàng! Không thể thêm.`, 'danger');
            currentQuantity = 0;
            input.value = 0;
        } else if (currentQuantity > stock) {
            showToast(`Số lượng "${productName}" (${currentQuantity}) vượt quá tồn kho (${stock}). Đã điều chỉnh.`, 'warning');
            currentQuantity = stock;
            input.value = stock;
        }

        // Stock level warnings (only if quantity > 0 and stock > 0)
        if (currentQuantity > 0 && stock > 0) {
            if (stock <= 2) {
                showToast(`🚨 Sản phẩm "${productName}" sắp hết hàng! Chỉ còn ${stock}.`, 'danger');
            } else if (stock <= 5) {
                showToast(`⚠️ Sản phẩm "${productName}" sắp hết hàng! Chỉ còn ${stock}.`, 'warning');
            }
        }
        
        const total = price * currentQuantity;
        document.getElementById(`total_${itemId}`).value = formatCurrency(total);

        updateCartItem(itemId, {
            product_id: option.value,
            product_name: productName,
            unit_price: price,
            quantity: currentQuantity,
            stock: stock
        });
    } else {
        // If no product is selected in the row, ensure its total is zeroed out
        document.getElementById(`total_${itemId}`).value = formatCurrency(0);
        removeCartItem(itemId); // Ensure it's removed from cart if product is deselected
    }

    calculateTotal();
}

// Update cart item
function updateCartItem(itemId, item) {
    const existingIndex = cartItems.findIndex(i => i.itemId === itemId);
    
    if (existingIndex >= 0) {
        if (item.quantity > 0) {
            cartItems[existingIndex] = { ...item, itemId };
        } else {
            cartItems.splice(existingIndex, 1);
        }
    } else if (item.quantity > 0) {
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
    const subtotal = cartItems.reduce((sum, item) => sum + (item.unit_price * item.quantity), 0);
    const discountPercent = parseFloat(document.getElementById('discount_percent').value) || 0;
    const discountAmount = subtotal * (discountPercent / 100);
    const total = subtotal - discountAmount;
    
    // Update display
    document.getElementById('subtotal').textContent = formatCurrency(subtotal);
    document.getElementById('discountAmount').value = formatCurrency(discountAmount);
    document.getElementById('discountDisplay').textContent = formatCurrency(discountAmount);
    document.getElementById('totalAmount').textContent = formatCurrency(total);
    
    // Update hidden inputs
    document.getElementById('subtotalInput').value = subtotal;
    document.getElementById('discountAmountInput').value = discountAmount;
    document.getElementById('totalAmountInput').value = total;
    document.getElementById('productsData').value = JSON.stringify(cartItems);
    
    // Enable/disable submit button
    const submitBtn = document.getElementById('submitBtn');
    const customerName = document.getElementById('customer_name').value.trim();
    
    if (cartItems.length > 0 && customerName && total > 0) {
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
    } else {
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.5';
    }
}

// Reset form
function resetForm() {
    if (confirm('Bạn có chắc chắn muốn làm mới form?')) {
        document.getElementById('saleForm').reset();
        document.getElementById('itemsContainer').innerHTML = '';
        cartItems = [];
        itemCount = 0;
        calculateTotal();
        showToast('Đã làm mới form', 'success');
    }
}

// View sale detail
function viewSaleDetail(saleCode, saleId) {
    console.log(`[viewSaleDetail] Called for saleId: ${saleId}`);
    showToast('Đang tải chi tiết hóa đơn...', 'info');
    fetch(`ajax/get_sale_detail.php?id=${saleId}`)
        .then(response => {
            console.log('[viewSaleDetail] Fetch response received:', response);
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('[viewSaleDetail] HTTP error response text:', text);
                    throw new Error(`Lỗi HTTP ${response.status}: ${text || 'Không có thông tin lỗi chi tiết'}`);
                });
            }
            return response.text(); 
        })
        .then(text => {
            console.log('[viewSaleDetail] Response text received:', text);
            try {
                const data = JSON.parse(text);
                console.log('[viewSaleDetail] Parsed JSON data:', data);
                if (data.error) {
                    showToast(`Lỗi tải chi tiết: ${data.error}`, 'error');
                    console.error('[viewSaleDetail] Server error in JSON:', data.error);
                    return;
                }
                // Check if sale and details data are present
                if (data.sale && data.details) {
                    console.log('[viewSaleDetail] Data is valid, calling showSaleDetailModal.');
                    showSaleDetailModal(data.sale, data.details);
                } else {
                    console.error('[viewSaleDetail] Invalid data structure received:', data);
                    showToast('Lỗi: Dữ liệu chi tiết hóa đơn không đầy đủ.', 'error');
                }
            } catch (e) {
                console.error('[viewSaleDetail] Lỗi phân tích JSON:', e);
                console.error('[viewSaleDetail] Dữ liệu nhận được không phải JSON:', text);
                showToast('Lỗi: Dữ liệu trả về không hợp lệ. Vui lòng kiểm tra console (F12).', 'error');
            }
        })
        .catch(error => {
            console.error('[viewSaleDetail] Lỗi khi tải chi tiết hóa đơn:', error);
            showToast(`Lỗi: ${error.message}`, 'error');
        });
}

// Print invoice
function printInvoice(saleId) {
    window.open(`print_invoice.php?id=${saleId}`, '_blank', 'width=800,height=600');
}

// Show sale detail modal
function showSaleDetailModal(saleData, details) {
    console.log('[showSaleDetailModal] Called with saleData:', saleData, 'and details:', details);
    
    const existingModal = document.querySelector('.modal-overlay.sale-detail-modal');
    if (existingModal) {
        console.log('[showSaleDetailModal] Removing existing sale detail modal.');
        existingModal.remove();
    }

    const modalId = 'saleDetailModal_' + Date.now();
    const modal = document.createElement('div');
    modal.id = modalId;
    modal.className = 'modal-overlay sale-detail-modal';
    
    // Force display with inline styles to ensure modal shows
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        opacity: 1;
    `;

    modal.innerHTML = `
        <div class="modal-content" style="max-width: 900px; width: 90%; background: white; border-radius: 12px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2); max-height: 90vh; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 1.1rem;">🧾 Chi tiết hóa đơn #${saleData.sale_code || saleData.id}</h3>
                <button class="modal-close" onclick="closeDynamicModal('${modalId}')" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0.25rem 0.5rem;">&times;</button>
            </div>
            <div class="modal-body" style="padding: 1.5rem; max-height: 60vh; overflow-y: auto;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div class="sale-info">
                        <h4 style="color: #667eea; margin-bottom: 0.5rem;">📝 Thông tin hóa đơn</h4>
                        <p><strong>Mã hóa đơn:</strong> ${saleData.sale_code}</p>
                        <p><strong>Ngày bán:</strong> ${saleData.created_at_formatted}</p>
                        <p><strong>Nhân viên:</strong> ${saleData.cashier_name || 'Admin'}</p>
                        <p><strong>Trạng thái:</strong> 
                            <span style="background: #4facfe; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">Hoàn thành</span>
                        </p>
                    </div>
                    <div class="customer-info">
                        <h4 style="color: #667eea; margin-bottom: 0.5rem;">👤 Thông tin khách hàng</h4>
                        <p><strong>Tên khách:</strong> ${saleData.customer_name || 'Khách lẻ'}</p>
                        <p><strong>Điện thoại:</strong> ${saleData.customer_phone || 'Không có'}</p>
                        <p><strong>Email:</strong> ${saleData.customer_email || 'Không có'}</p>
                        <p><strong>Địa chỉ:</strong> ${saleData.customer_address || 'Không có'}</p>
                    </div>
                </div>

                <h4 style="color: #667eea; margin-bottom: 0.5rem;">🛒 Danh sách sản phẩm</h4>
                <div class="table-responsive">
                    <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
                        <thead>
                            <tr style="background: #f8f9ff;">
                                <th style="padding: 0.75rem; text-align: left; border: 1px solid #e1e5e9;">Mã SP</th>
                                <th style="padding: 0.75rem; text-align: left; border: 1px solid #e1e5e9;">Tên sản phẩm</th>
                                <th style="padding: 0.75rem; text-align: center; border: 1px solid #e1e5e9;">Số lượng</th>
                                <th style="padding: 0.75rem; text-align: right; border: 1px solid #e1e5e9;">Đơn giá</th>
                                <th style="padding: 0.75rem; text-align: right; border: 1px solid #e1e5e9;">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${details.map(item => `
                                <tr>
                                    <td style="padding: 0.75rem; border: 1px solid #e1e5e9;">${item.product_code}</td>
                                    <td style="padding: 0.75rem; border: 1px solid #e1e5e9;">${item.product_name}</td>
                                    <td style="padding: 0.75rem; text-align: center; border: 1px solid #e1e5e9;">${item.quantity}</td>
                                    <td style="padding: 0.75rem; text-align: right; border: 1px solid #e1e5e9;">${item.unit_price_formatted}đ</td>
                                    <td style="padding: 0.75rem; text-align: right; border: 1px solid #e1e5e9;"><strong>${item.total_price_formatted}đ</strong></td>
                                </tr>
                            `).join('')}
                        </tbody>
                        <tfoot>
                            <tr style="background: #f8f9ff;">
                                <td colspan="4" style="padding: 0.75rem; border: 1px solid #e1e5e9;"><strong>Tạm tính:</strong></td>
                                <td style="padding: 0.75rem; text-align: right; border: 1px solid #e1e5e9;"><strong>${saleData.total_amount_formatted}đ</strong></td>
                            </tr>
                            ${saleData.discount_amount && parseFloat(saleData.discount_amount) > 0 ? `
                            <tr style="background: #f8f9ff;">
                                <td colspan="4" style="padding: 0.75rem; border: 1px solid #e1e5e9;"><strong>Giảm giá:</strong></td>
                                <td style="padding: 0.75rem; text-align: right; border: 1px solid #e1e5e9;"><strong>-${saleData.discount_amount_formatted}đ</strong></td>
                            </tr>
                            ` : ''}
                            <tr style="background: #667eea; color: white;">
                                <td colspan="4" style="padding: 0.75rem; border: 1px solid #e1e5e9;"><strong>Tổng cộng:</strong></td>
                                <td style="padding: 0.75rem; text-align: right; border: 1px solid #e1e5e9;"><strong>${saleData.final_amount_formatted}đ</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 1rem; padding: 1rem 1.5rem; border-top: 1px solid #e1e5e9; background: #f8f9ff;">
                <button type="button" onclick="closeDynamicModal('${modalId}')" style="background: #6c757d; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer;">Đóng</button>
                <button type="button" onclick="printInvoice(${saleData.id})" style="background: #667eea; color: white; border: none; padding: 0.5rem 1rem; border-radius: 4px; cursor: pointer;">🖨️ In hóa đơn</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    console.log('[showSaleDetailModal] Modal appended to body. ID:', modalId, 'Element:', modal);
}

// Close dynamic modal function (shared with imports)
function closeDynamicModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.remove();
        console.log('[closeDynamicModal] Modal removed:', modalId);
    }
}

// Form validation
document.getElementById('customer_name').addEventListener('input', function() {
    this.value = this.value.replace(/[^a-zA-ZÀ-ÿ\s]/g, ''); // Chỉ cho phép chữ và khoảng trắng
    calculateTotal();
});

document.getElementById('customer_phone').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, ''); // Chỉ cho phép số
    if (this.value.length > 11) {
        this.value = this.value.substring(0, 11);
    }
});

document.getElementById('discount_percent').addEventListener('input', function() {
    if (this.value < 0) this.value = 0;
    if (this.value > 100) this.value = 100;
    calculateTotal();
});

// Quick add product by barcode/code
document.getElementById('productSearch').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const query = this.value.trim();
        
        // Try to find exact match by code
        const exactMatch = allProducts.find(p => 
            p.product_code.toLowerCase() === query.toLowerCase() // Changed p.code to p.product_code
        );
        
        if (exactMatch && exactMatch.stock_quantity > 0) {
            // Add product directly
            addItemRow();
            const newRow = document.querySelector(`#item_${itemCount}`);
            const select = newRow.querySelector('select');
            select.value = exactMatch.id;
            selectProduct(select, itemCount);
            
            this.value = '';
            showToast(`Đã thêm ${exactMatch.name}`, 'success');
        }
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Skip if typing in input fields (except specific shortcuts)
    const isInputElement = e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT';
    
    // F-key shortcuts work regardless of focus
    if (e.key === 'F2') {
        e.preventDefault();
        document.getElementById('productSearch').focus();
        showToast('Focus vào tìm kiếm sản phẩm (F2)', 'info');
        return;
    }
    
    if (e.key === 'F3') {
        e.preventDefault();
        document.getElementById('customer_name').focus();
        showToast('Focus vào tên khách hàng (F3)', 'info');
        return;
    }
    
    if (e.key === 'F4') {
        e.preventDefault();
        const submitBtn = document.getElementById('submitBtn');
        if (!submitBtn.disabled) {
            submitBtn.click();
            showToast('Thực hiện thanh toán (F4)', 'info');
        }
        return;
    }
    
    if (e.key === 'F5') {
        e.preventDefault();
        // Print last invoice if available
        if (window.lastInvoiceId) {
            window.open(`print_invoice.php?id=${window.lastInvoiceId}`, '_blank');
            showToast('In hóa đơn cuối (F5)', 'info');
        } else {
            showToast('Chưa có hóa đơn để in', 'warning');
        }
        return;
    }
    
    // Ctrl/Cmd combinations
    if (e.ctrlKey || e.metaKey) {
        switch(e.key) {
            case 'Enter':
                e.preventDefault();
                const submitBtn = document.getElementById('submitBtn');
                if (!submitBtn.disabled) {
                    submitBtn.click();
                }
                break;            case 'r':
                e.preventDefault();
                resetForm();
                showToast('Đặt lại form (Ctrl+R)', 'info');
                break;
            case 'n':
                e.preventDefault();
                addItemRow();
                showToast('Thêm dòng sản phẩm (Ctrl+N)', 'info');
                break;
        }
    }
});


// Customer quick select by phone
document.getElementById('customer_phone').addEventListener('input', function() {
    const phone = this.value;
    if (phone.length >= 4) {
        const customer = <?php echo json_encode($customers); ?>.find(c => c.phone.includes(phone));
        if (customer) {
            document.getElementById('customer_id').value = customer.id;
            document.getElementById('customer_name').value = customer.name;
            showToast(`Tìm thấy khách hàng: ${customer.name}`, 'info');
        }
    }
});

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    addItemRow();
    
    // Show keyboard shortcuts hint  
    showToast('💡 Phím tắt: F2 (Tìm SP), F3 (Khách hàng), F4 (Thanh toán), Ctrl+R (Reset)', 'info');
});

// Format date helper
function formatDate(dateString) {
    return new Date(dateString).toLocaleString('vi-VN');
}
</script>
