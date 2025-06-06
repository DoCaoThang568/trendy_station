<?php
/**
 * Products Page - Quản lý sản phẩm
 */

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_product':
            $code = $_POST['code'];
            $name = $_POST['name'];
            $category_id = $_POST['category_id'];
            $size = $_POST['size'];
            $color = $_POST['color'];
            $purchase_price = $_POST['purchase_price'];
            $selling_price = $_POST['selling_price'];
            $stock_quantity = $_POST['stock_quantity'];
            $description = $_POST['description'];
            
            try {
                $sql = "INSERT INTO products (code, name, category_id, size, color, purchase_price, selling_price, stock_quantity, description) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                executeQuery($sql, [$code, $name, $category_id, $size, $color, $purchase_price, $selling_price, $stock_quantity, $description]);
                $_SESSION['success_message'] = 'Thêm sản phẩm thành công!';
            } catch (Exception $e) {
                $_SESSION['error_message'] = 'Lỗi: ' . $e->getMessage();
            }
            break;
            
        case 'delete_product':
            $id = $_POST['id'];
            try {
                executeQuery("DELETE FROM products WHERE id = ?", [$id]);
                $_SESSION['success_message'] = 'Xóa sản phẩm thành công!';
            } catch (Exception $e) {
                $_SESSION['error_message'] = 'Lỗi: ' . $e->getMessage();
            }
            break;
    }
    
    header('Location: index.php?page=products');
    exit;
}

// Get search term
$search = $_GET['search'] ?? '';

// Get products
$where = "1=1";
$params = [];

if ($search) {
    $where .= " AND (p.name LIKE ? OR p.code LIKE ? OR c.name LIKE ?)";
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
$newProductCode = generateCode('SP', 'products', 'code');
?>

<h1 class="page-title">📦 Quản lý Sản phẩm</h1>

<div class="toolbar">
    <div class="search-box">
        <input type="text" id="productSearch" placeholder="Tìm kiếm sản phẩm..." value="<?php echo htmlspecialchars($search); ?>" onkeyup="handleSearch(this.value)">
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
                </tr>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($product['code']); ?></strong></td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td>
                            <span style="background: var(--primary-color); color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8rem;">
                                <?php echo htmlspecialchars($product['category_name'] ?? 'Chưa phân loại'); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($product['size']); ?></td>
                        <td><?php echo htmlspecialchars($product['color']); ?></td>
                        <td><strong style="color: var(--success-color);"><?php echo number_format($product['selling_price']); ?>đ</strong></td>
                        <td>
                            <span style="background: <?php echo $product['stock_quantity'] <= $product['min_stock'] ? 'var(--danger-color)' : 'var(--success-color)'; ?>; color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8rem;">
                                <?php echo $product['stock_quantity']; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($product['status'] === 'active'): ?>
                                <span style="background: var(--success-gradient); color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8rem;">✅ Hoạt động</span>
                            <?php else: ?>
                                <span style="background: var(--danger-gradient); color: white; padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.8rem;">❌ Ngừng bán</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-small btn-secondary" onclick="editProduct(<?php echo $product['id']; ?>)" title="Sửa">
                                ✏️
                            </button>
                            <button class="btn btn-small btn-danger" onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')" title="Xóa">
                                🗑️
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
                        <label for="code">Mã sản phẩm <span class="required">*</span></label>
                        <input type="text" name="code" id="code" value="<?php echo $newProductCode; ?>" required>
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
                        <label for="purchase_price">Giá nhập</label>
                        <input type="number" name="purchase_price" id="purchase_price" placeholder="0" min="0" step="1000">
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
    document.getElementById('code').value = '<?php echo $newProductCode; ?>';
    document.getElementById('productModal').style.display = 'block';
}

// Edit product
function editProduct(id) {
    // This would typically fetch product data via AJAX
    showToast('Chức năng sửa sản phẩm đang phát triển', 'warning');
}

// Delete product
function deleteProduct(id, name) {
    if (confirm(`Bạn có chắc chắn muốn xóa sản phẩm "${name}"?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_product">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
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
</script>
