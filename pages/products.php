<?php
/**
 * Products Page - Quản lý sản phẩm
 */

// Add some debugging
error_log("POST data received: " . print_r($_POST, true));

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $isAjax = isset($_POST['ajax']) || isset($_SERVER['HTTP_X_REQUESTED_WITH']);
    
    switch ($action) {        case 'add_product':
            $product_code = $_POST['product_code'] ?? '';
            $name = $_POST['name'] ?? '';
            $category_id = $_POST['category_id'] ?? '';
            $size = $_POST['size'] ?? '';
            $color = $_POST['color'] ?? '';
            $cost_price = $_POST['cost_price'] ?? 0; // Fixed field name
            $selling_price = $_POST['selling_price'] ?? 0;
            $stock_quantity = $_POST['stock_quantity'] ?? 0;
            $description = $_POST['description'] ?? '';
            
            try {
                $sql = "INSERT INTO products (product_code, name, category_id, size, color, cost_price, selling_price, stock_quantity, description) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                executeQuery($sql, [$product_code, $name, $category_id, $size, $color, $cost_price, $selling_price, $stock_quantity, $description]);
                
                // Get the ID of the newly inserted product
                $newProductId = getLastInsertId();
                
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Thêm sản phẩm thành công!',
                        'product_id' => $newProductId
                    ]);
                    exit;
                } else {
                    $_SESSION['success_message'] = 'Thêm sản phẩm thành công!';
                }
            } catch (Exception $e) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
                    exit;
                } else {
                    $_SESSION['error_message'] = 'Lỗi: ' . $e->getMessage();
                }            }
            break;

        case 'edit_product':
            $id = $_POST['id'] ?? '';
            $product_code = $_POST['product_code'] ?? '';
            $name = $_POST['name'] ?? '';
            $category_id = $_POST['category_id'] ?? '';
            $size = $_POST['size'] ?? '';
            $color = $_POST['color'] ?? '';
            $cost_price = $_POST['cost_price'] ?? 0; // Fixed field name
            $selling_price = $_POST['selling_price'] ?? 0;
            $stock_quantity = $_POST['stock_quantity'] ?? 0;
            $description = $_POST['description'] ?? '';
            $is_active = isset($_POST['is_active']) ? 1 : 0; // Handle is_active

            try {
                $sql = "UPDATE products SET 
                            product_code = ?, 
                            name = ?, 
                            category_id = ?, 
                            size = ?, 
                            color = ?, 
                            cost_price = ?, 
                            selling_price = ?, 
                            stock_quantity = ?, 
                            description = ?,
                            is_active = ?
                        WHERE id = ?";
                executeQuery($sql, [$product_code, $name, $category_id, $size, $color, $cost_price, $selling_price, $stock_quantity, $description, $is_active, $id]);
                
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Cập nhật sản phẩm thành công!']);
                    exit;
                } else {
                    $_SESSION['success_message'] = 'Cập nhật sản phẩm thành công!';
                }
            } catch (Exception $e) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
                    exit;
                } else {
                    $_SESSION['error_message'] = 'Lỗi: ' . $e->getMessage();
                }
            }
            break;
              case 'delete_product':
            $id = $_POST['id'];
            
            try {
                executeQuery("DELETE FROM products WHERE id = ?", [$id]);
                
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Xóa sản phẩm thành công!']);
                    exit;
                } else {
                    $_SESSION['success_message'] = 'Xóa sản phẩm thành công!';
                }
            } catch (Exception $e) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
                    exit;
                } else {
                    $_SESSION['error_message'] = 'Lỗi: ' . $e->getMessage();
                }            }
            break;
    }
    
    // Only redirect if not AJAX
    if (!$isAjax) {
        header('Location: index.php?page=products');
        exit;
    }
}

// Get search term
$search = $_GET['search'] ?? '';

// Get products
$where = "1=1";
$params = [];

if ($search) {
    $where .= " AND (p.name LIKE ? OR p.product_code LIKE ? OR c.name LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
}

$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE $where 
        ORDER BY p.created_at DESC";

$products = fetchAll($sql, $params);

// Get categories for dropdown
$categories = fetchAll("SELECT * FROM categories ORDER BY name");

// Generate new product code
$newProductCode = generateCode('SP', 'products', 'product_code');
?>

<h1 class="page-title">📦 Quản lý Sản phẩm</h1>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success">
        <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger">
        <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
    </div>
<?php endif; ?>

<div class="toolbar">
    <div class="search-box">
        <input type="text" id="productSearch" placeholder="Tìm kiếm sản phẩm..." value="<?php echo htmlspecialchars($search); ?>" onkeyup="debouncedSearch(this.value)">
    </div>
    <button class="btn btn-primary" onclick="openProductModal()">
        ➕ Thêm sản phẩm
    </button>
</div>

<div class="data-table">
    <table id="productsTable">
        <thead>
            <tr>
                <th>Mã SP</th>
                <th>Tên sản phẩm</th>
                <th>Danh mục</th>
                <th>Size</th>
                <th>Màu sắc</th>
                <th>Giá bán</th>
                <th>Tồn kho</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                        📦 Chưa có sản phẩm nào. Hãy thêm sản phẩm đầu tiên!
                    </td>
                </tr>            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <tr data-id="<?php echo $product['id']; ?>">
                        <td><strong><?php echo htmlspecialchars($product['product_code']); ?></strong></td>
                        <td>
                            <div class="product-info">
                                <span class="product-name"><?php echo htmlspecialchars($product['name']); ?></span>
                                <span class="product-code">Mã: <?php echo htmlspecialchars($product['product_code']); ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="category-badge">
                                <?php echo htmlspecialchars($product['category_name'] ?? 'Chưa phân loại'); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($product['size']); ?></td>
                        <td><?php echo htmlspecialchars($product['color']); ?></td>
                        <td><strong style="color: var(--success-color);"><?php echo number_format($product['selling_price']); ?>đ</strong></td>
                        <td>
                            <span class="stock-badge <?php echo $product['stock_quantity'] <= (isset($product['min_stock']) ? $product['min_stock'] : 10) ? 'stock-low' : 'stock-ok'; ?>">
                                <?php echo $product['stock_quantity']; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($product['is_active']): ?>
                                <span class="status-badge status-active">✅ Hoạt động</span>
                            <?php else: ?>
                                <span class="status-badge status-inactive">❌ Ngừng bán</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-small btn-secondary" onclick="editProduct(<?php echo $product['id']; ?>)" title="Sửa">
                                <i class="fas fa-edit"></i> Sửa
                            </button>
                            <button class="btn btn-small btn-danger" onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')" title="Xóa">
                                <i class="fas fa-trash"></i> Xóa
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Thêm/Sửa sản phẩm -->
<div id="productModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">➕ Thêm sản phẩm mới</h3>
            <span class="close" onclick="closeModal('productModal')">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" id="productForm">
                <input type="hidden" name="action" value="add_product">
                <input type="hidden" name="id" id="productId">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="product_code">Mã sản phẩm <span class="required">*</span></label>
                        <input type="text" name="product_code" id="product_code" value="<?php echo $newProductCode; ?>" readonly required>
                    </div>
                    
                    <div class="form-group">
                        <label for="name">Tên sản phẩm <span class="required">*</span></label>
                        <input type="text" name="name" id="name" placeholder="Nhập tên sản phẩm" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">Danh mục</label>
                        <select name="category_id" id="category_id">
                            <option value="">-- Chọn danh mục --</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="size">Kích thước</label>
                        <select name="size" id="size">
                            <option value="">-- Chọn size --</option>
                            <option value="XS">XS</option>
                            <option value="S">S</option>
                            <option value="M">M</option>
                            <option value="L">L</option>
                            <option value="XL">XL</option>
                            <option value="XXL">XXL</option>
                            <option value="XXXL">XXXL</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="color">Màu sắc</label>
                        <input type="text" name="color" id="color" placeholder="Màu sắc">
                    </div>
                      <div class="form-group">
                        <label for="cost_price">Giá nhập</label>
                        <input type="number" name="cost_price" id="cost_price" placeholder="0" min="0" step="1000">
                    </div>
                    
                    <div class="form-group">
                        <label for="selling_price">Giá bán <span class="required">*</span></label>
                        <input type="number" name="selling_price" id="selling_price" placeholder="0" min="0" step="1000" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="stock_quantity">Số lượng tồn</label>
                        <input type="number" name="stock_quantity" id="stock_quantity" value="0" min="0">
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="description">Mô tả</label>
                        <textarea name="description" id="description" placeholder="Mô tả chi tiết sản phẩm"></textarea>
                    </div>

                    <div class="form-group" id="is_active_label" style="display: flex; align-items: center;"> 
                        <input type="checkbox" name="is_active" id="is_active" value="1" style="width: auto; margin-right: 10px;">
                        <label for="is_active" style="margin-bottom: 0;">Đang hoạt động</label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('productModal')">Hủy</button>
                    <button type="submit" class="btn btn-primary">💾 Lưu sản phẩm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Debounce function definition
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Search functionality
function handleSearch(searchTerm) {
    if (searchTerm.length >= 2 || searchTerm.length === 0) {
        window.location.href = `index.php?page=products&search=${encodeURIComponent(searchTerm)}`;
    }
}

// Debounced search
const debouncedSearch = debounce(handleSearch, 500);

document.getElementById('productSearch').addEventListener('input', function(e) {
    debouncedSearch(e.target.value);
});

// Open product modal
function openProductModal() {
    document.getElementById('modalTitle').textContent = '➕ Thêm sản phẩm mới';
    document.getElementById('productForm').reset();
    document.querySelector('[name="action"]').value = 'add_product';
    document.getElementById('productId').value = '';
    document.getElementById('product_code').value = '<?php echo $newProductCode; ?>'; // Ensure new product code is set
    // Ensure is_active checkbox is present and set to a default (e.g., checked for new products)
    const isActiveCheckbox = document.getElementById('is_active');
    if (isActiveCheckbox) {
        isActiveCheckbox.checked = true;
        document.getElementById('is_active_label').style.display = 'none'; // Hide for new products
    }
    document.getElementById('productModal').style.display = 'block';
    document.getElementById('name').focus(); // Focus on the first input field
}

// Edit product
async function editProduct(id) {
    try {
        const formData = new FormData();
        formData.append('action', 'get_product_details');
        formData.append('id', id);

        console.log('Sending request to get product details for ID:', id);

        const response = await fetch('ajax/get_product_detail.php', {
            method: 'POST',
            body: formData
        });

        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers.get('content-type'));

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const responseText = await response.text();
        console.log('Raw response:', responseText);

        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            console.error('Response text that failed to parse:', responseText);
            throw new Error('Server returned invalid JSON: ' + responseText.substring(0, 100));
        }

        if (result.success && result.data) {
            const product = result.data;
            document.getElementById('modalTitle').textContent = '📝 Sửa sản phẩm';
            document.querySelector('[name="action"]').value = 'edit_product';
            document.getElementById('productId').value = product.id;
            document.getElementById('product_code').value = product.product_code;
            document.getElementById('name').value = product.name;
            document.getElementById('category_id').value = product.category_id;
            document.getElementById('size').value = product.size;
            document.getElementById('color').value = product.color;
            document.getElementById('cost_price').value = product.cost_price; // Fixed field name
            document.getElementById('selling_price').value = product.selling_price;
            document.getElementById('stock_quantity').value = product.stock_quantity;
            document.getElementById('description').value = product.description;
            
            // Handle is_active checkbox
            const isActiveCheckbox = document.getElementById('is_active');
            if (isActiveCheckbox) {
                isActiveCheckbox.checked = !!parseInt(product.is_active);
                document.getElementById('is_active_label').style.display = 'flex'; // Show for editing
            }

            document.getElementById('productModal').style.display = 'block';
            document.getElementById('name').focus();
        } else {
            showToast(result.message || 'Không thể tải thông tin sản phẩm.', 'error');
        }
    } catch (error) {
        console.error('Error fetching product details:', error);
        showToast('Lỗi kết nối hoặc xử lý: ' + error.message, 'error');
    }
}

// Delete product with AJAX
async function deleteProduct(id, name) {
    if (!confirm(`Bạn có chắc chắn muốn xóa sản phẩm "${name}"?`)) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete_product');
        formData.append('id', id);
        formData.append('ajax', '1');
        
        const response = await fetch('index.php?page=products', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            const result = await response.json();
            
            if (result.success) {
                showToast(result.message, 'success');
                // Remove the row from table
                removeProductRow(id);
            } else {
                showToast(result.message, 'error');
            }
        } else {
            throw new Error('HTTP error! status: ' + response.status);
        }
        
    } catch (error) {
        console.error('Error deleting product:', error);
        showToast('Có lỗi xảy ra khi xóa sản phẩm: ' + error.message, 'error');
    }
}

// Close modal
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('productModal');
    if (event.target === modal) {
        closeModal('productModal');
    }
}

// Keyboard shortcuts for Products page
document.addEventListener('keydown', function(e) {
    // Skip if typing in input fields (except specific shortcuts)
    const isInputElement = e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT';
    
    // F-key shortcuts work regardless of focus
    if (e.key === 'F2') {
        e.preventDefault();
        openProductModal();
        showToast('Mở form thêm sản phẩm (F2)', 'info');
        return;
    }
    
    if (e.key === 'F3') {
        e.preventDefault();
        document.getElementById('productSearch').focus();
        showToast('Focus vào tìm kiếm sản phẩm (F3)', 'info');
        return;
    }
    
    // Escape to close modal
    if (e.key === 'Escape') {
        const modal = document.getElementById('productModal');
        if (modal && modal.style.display === 'block') {
            closeModal('productModal');
            showToast('Đóng modal (Esc)', 'info');
        }
        return;
    }
    
    // Ctrl/Cmd combinations
    if (e.ctrlKey || e.metaKey) {
        switch(e.key) {
            case 'n':
                e.preventDefault();
                openProductModal();
                showToast('Thêm sản phẩm mới (Ctrl+N)', 'info');
                break;
            case 'f':
                e.preventDefault();
                document.getElementById('productSearch').focus();
                showToast('Tìm kiếm sản phẩm (Ctrl+F)', 'info');
                break;
            case 's':
                e.preventDefault();
                const form = document.getElementById('productForm');
                if (form && document.getElementById('productModal').style.display === 'block') {
                    form.dispatchEvent(new Event('submit'));
                    showToast('Lưu sản phẩm (Ctrl+S)', 'info');
                }
                break;
        }
    }
});

// Function to update a single product row in the table
async function updateProductRow(productId) {
    try {
        const formData = new FormData();
        formData.append('action', 'get_product_details');
        formData.append('id', productId);

        const response = await fetch('ajax/get_product_detail.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();

        if (result.success && result.data) {
            const product = result.data;
            const tableBody = document.querySelector('#productsTable tbody');
            
            // Find existing row or create new one
            let existingRow = document.querySelector(`#productsTable tbody tr[data-id="${productId}"]`);
            
            if (!existingRow) {
                // Create new row for newly added product
                existingRow = document.createElement('tr');
                existingRow.setAttribute('data-id', productId);
                // Insert at the beginning (since products are ordered by created_at DESC)
                tableBody.insertBefore(existingRow, tableBody.firstChild);
                
                // Remove "no products" row if it exists
                const noProductsRow = tableBody.querySelector('td[colspan="9"]');
                if (noProductsRow) {
                    noProductsRow.closest('tr').remove();
                }
            }
            
            // Create the updated row HTML
            const stockClass = product.stock_quantity <= 10 ? 'stock-low' : 'stock-ok';
            const statusClass = product.is_active ? 'status-active' : 'status-inactive';
            const statusText = product.is_active ? '✅ Hoạt động' : '❌ Ngừng bán';
            
            existingRow.innerHTML = `
                <td><strong>${escapeHtml(product.product_code)}</strong></td>
                <td>
                    <div class="product-info">
                        <span class="product-name">${escapeHtml(product.name)}</span>
                        <span class="product-code">Mã: ${escapeHtml(product.product_code)}</span>
                    </div>
                </td>
                <td>
                    <span class="category-badge">
                        ${escapeHtml(product.category_name || 'Chưa phân loại')}
                    </span>
                </td>
                <td>${escapeHtml(product.size || '')}</td>
                <td>${escapeHtml(product.color || '')}</td>
                <td><strong style="color: var(--success-color);">${formatNumber(product.selling_price)}đ</strong></td>
                <td>
                    <span class="stock-badge ${stockClass}">
                        ${product.stock_quantity}
                    </span>
                </td>
                <td>
                    <span class="status-badge ${statusClass}">${statusText}</span>
                </td>
                <td>
                    <button class="btn btn-small btn-secondary" onclick="editProduct(${product.id})" title="Sửa">
                        <i class="fas fa-edit"></i> Sửa
                    </button>
                    <button class="btn btn-small btn-danger" onclick="deleteProduct(${product.id}, '${escapeHtml(product.name)}')" title="Xóa">
                        <i class="fas fa-trash"></i> Xóa
                    </button>
                </td>
            `;
            
            // Add a subtle flash effect to highlight the change
            existingRow.style.backgroundColor = '#e8f5e8';
            setTimeout(() => {
                existingRow.style.backgroundColor = '';
            }, 2000);
            
            return true;
        } else {
            console.error('Failed to get product details:', result.message);
            return false;
        }
    } catch (error) {
        console.error('Error updating product row:', error);
        return false;
    }
}

// Function to remove a product row from the table
function removeProductRow(productId) {
    const existingRow = document.querySelector(`#productsTable tbody tr[data-id="${productId}"]`);
    if (existingRow) {
        existingRow.remove();
        
        // Check if table is now empty
        const tableBody = document.querySelector('#productsTable tbody');
        if (tableBody.children.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="9" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                        📦 Chưa có sản phẩm nào. Hãy thêm sản phẩm đầu tiên!
                    </td>
                </tr>
            `;
        }
    }
}

// Helper functions
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatNumber(num) {
    return new Intl.NumberFormat('vi-VN').format(num);
}

// Handle form submission with AJAX
document.getElementById('productForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    const action = document.querySelector('[name="action"]').value;
    const productId = document.getElementById('productId').value;
    
    // Show loading state
    submitBtn.disabled = true;
    submitBtn.textContent = '💾 Đang lưu...';
    
    try {
        const formData = new FormData(this);
        formData.append('ajax', '1'); // Add AJAX flag
        
        const response = await fetch('index.php?page=products', {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            const result = await response.json();
            
            if (result.success) {
                showToast(result.message, 'success');
                closeModal('productModal');
                  // Update the table instead of reloading the page
                if (action === 'add_product') {
                    // For new products, use the returned product ID to update the table
                    if (result.product_id) {
                        const updated = await updateProductRow(result.product_id);
                        if (!updated) {
                            // Fallback to reload if update failed
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        }
                    } else {
                        // Fallback to reload if no product ID returned
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }
                } else if (action === 'edit_product' && productId) {
                    // For edited products, update the specific row
                    const updated = await updateProductRow(productId);
                    if (!updated) {
                        // Fallback to reload if update failed
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    }
                }
            } else {
                showToast(result.message, 'error');
            }
        } else {
            throw new Error('HTTP error! status: ' + response.status);
        }
        
    } catch (error) {
        console.error('Error submitting form:', error);
        showToast('Có lỗi xảy ra khi lưu dữ liệu: ' + error.message, 'error');
    } finally {
        // Restore button state
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
});
</script>
