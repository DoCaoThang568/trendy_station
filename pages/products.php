<?php
require_once __DIR__ . '/../includes/functions.php';
/**
 * Products Page - Quản lý sản phẩm
 */

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $isAjax = isset($_POST['ajax']) || isset($_SERVER['HTTP_X_REQUESTED_WITH']);
    
    // If this is an AJAX request, clean any output buffers and set JSON header
    if ($isAjax) {
        // Clean any existing output buffers
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        // Start new buffer for clean JSON output
        ob_start();
        header('Content-Type: application/json; charset=utf-8');
    }
    
    switch ($action) {
        case 'add_product':
            global $pdo; // Make PDO available
            $product_code = $_POST['product_code'] ?? '';
            $name = $_POST['name'] ?? '';
            $category_id = $_POST['category_id'] ?? '';
            $size = $_POST['size'] ?? '';
            $color = $_POST['color'] ?? '';
            $cost_price = $_POST['cost_price'] ?? 0;
            $selling_price = $_POST['selling_price'] ?? 0;
            $stock_quantity = $_POST['stock_quantity'] ?? 0;
            $description = $_POST['description'] ?? '';
            
            try {
                $sql = "INSERT INTO products (product_code, name, category_id, size, color, cost_price, selling_price, stock_quantity, description) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                executeQuery($sql, [$product_code, $name, $category_id, $size, $color, $cost_price, $selling_price, $stock_quantity, $description]);
                
                // Get the ID of the newly inserted product
                $newProductId = $pdo->lastInsertId();
                
                if ($isAjax) {
                    // Clean any unwanted output
                    ob_clean();
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Thêm sản phẩm thành công!',
                        'product_id' => $newProductId
                    ]);
                    ob_end_flush();
                    exit;
                } else {
                    $_SESSION['success_message'] = 'Thêm sản phẩm thành công!';
                }
            } catch (Exception $e) {
                if ($isAjax) {
                    // Clean any unwanted output  
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
                    ob_end_flush();
                    exit;
                } else {
                    $_SESSION['error_message'] = 'Lỗi: ' . $e->getMessage();
                }
            }
            break;

        case 'edit_product':
            global $pdo; // Make PDO available
            $id = $_POST['id'] ?? '';
            $product_code = $_POST['product_code'] ?? '';
            $name = $_POST['name'] ?? '';
            $category_id = $_POST['category_id'] ?? '';
            $size = $_POST['size'] ?? '';
            $color = $_POST['color'] ?? '';
            $cost_price = $_POST['cost_price'] ?? 0;
            $selling_price = $_POST['selling_price'] ?? 0;
            $stock_quantity = $_POST['stock_quantity'] ?? 0;
            $description = $_POST['description'] ?? '';
            $is_active = isset($_POST['is_active']) ? 1 : 0;

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
                    // Clean any unwanted output
                    ob_clean();
                    echo json_encode(['success' => true, 'message' => 'Cập nhật sản phẩm thành công!']);
                    ob_end_flush();
                    exit;
                } else {
                    $_SESSION['success_message'] = 'Cập nhật sản phẩm thành công!';
                }
            } catch (Exception $e) {
                if ($isAjax) {
                    // Clean any unwanted output
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
                    ob_end_flush();
                    exit;
                } else {
                    $_SESSION['error_message'] = 'Lỗi: ' . $e->getMessage();
                }
            }
            break;

        case 'delete_product':
            global $pdo; // Make PDO available
            $id = $_POST['id'];
            
            try {
                executeQuery("DELETE FROM products WHERE id = ?", [$id]);
                
                if ($isAjax) {
                    // Clean any unwanted output
                    ob_clean();
                    echo json_encode(['success' => true, 'message' => 'Xóa sản phẩm thành công!']);
                    ob_end_flush();
                    exit;
                } else {
                    $_SESSION['success_message'] = 'Xóa sản phẩm thành công!';
                }
            } catch (Exception $e) {
                if ($isAjax) {
                    // Clean any unwanted output
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
                    ob_end_flush();
                    exit;
                } else {
                    $_SESSION['error_message'] = 'Lỗi: ' . $e->getMessage();
                }
            }
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

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sản phẩm</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Reset và Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #3b82f6;
            --primary-dark: #2563eb;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #06b6d4;
            --purple-color: #8b5cf6;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --white: #ffffff;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --border-radius: 12px;
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--gray-800);
            line-height: 1.6;
            padding: 2rem;
        }

        .page-container {
            max-width: 1400px;
            margin: 0 auto;
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        /* Header Section */
        .page-header {
            background: linear-gradient(135deg, var(--purple-color), #7c3aed);
            padding: 2rem;
            color: var(--white);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            opacity: 0.9;
            font-size: 1rem;
        }

        /* Toolbar */
        .toolbar {
            padding: 1.5rem;
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            max-width: 400px;
        }

        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            transition: var(--transition);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgb(59 130 246 / 0.1);
        }

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.875rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: var(--white);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary {
            background: var(--gray-500);
            color: var(--white);
        }

        .btn-danger {
            background: var(--danger-color);
            color: var(--white);
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            margin: 1rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 500;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        /* Data Table */
        .data-table {
            padding: 1.5rem;
        }

        .data-table table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }

        .data-table th {
            background: linear-gradient(135deg, var(--gray-100), var(--gray-200));
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--gray-700);
            border-bottom: 2px solid var(--gray-200);
            white-space: nowrap;
        }

        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-200);
            vertical-align: middle;
        }

        .data-table tr:hover {
            background: var(--gray-50);
        }

        .product-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .product-name {
            font-weight: 600;
            color: var(--gray-800);
        }

        .product-code {
            font-size: 0.8rem;
            color: var(--gray-500);
            font-family: 'Courier New', monospace;
        }

        .category-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            background: linear-gradient(135deg, var(--info-color), #0891b2);
            color: var(--white);
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .stock-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
            border-radius: 50%;
            font-weight: 700;
            min-width: 40px;
            height: 40px;
        }

        .stock-ok {
            background: var(--success-color);
            color: var(--white);
        }

        .stock-low {
            background: var(--danger-color);
            color: var(--white);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.5rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-active {
            background: var(--success-color);
            color: var(--white);
        }

        .status-inactive {
            background: var(--gray-400);
            color: var(--white);
        }

        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-content {
            background: var(--white);
            border-radius: var(--border-radius);
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--purple-color), #7c3aed);
            color: var(--white);
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .close {
            background: none;
            border: none;
            color: var(--white);
            font-size: 1.5rem;
            cursor: pointer;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: var(--transition);
        }

        .close:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .modal-body {
            padding: 2rem;
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.875rem;
        }

        .required {
            color: var(--danger-color);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 0.75rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            transition: var(--transition);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgb(59 130 246 / 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-200);
        }

        /* Toast Notification */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--success-color);
            color: var(--white);
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transform: translateX(100%);
            transition: var(--transition);
            z-index: 1001;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast.error {
            background: var(--danger-color);
        }

        .toast.info {
            background: var(--info-color);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .toolbar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                max-width: none;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .data-table {
                overflow-x: auto;
            }
            
            .data-table table {
                min-width: 800px;
            }
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-container {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>
<body>

<div class="page-container">
    <!-- Header -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-box"></i>
            Quản lý Sản phẩm
        </h1>
        <p class="page-subtitle">Thêm, sửa, xóa và quản lý thông tin sản phẩm</p>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <!-- Toolbar -->
    <div class="toolbar">
        <div class="search-box">
            <input type="text" id="productSearch" placeholder="🔍 Tìm theo tên, mã, danh mục, size, màu... (F3)" value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <button class="btn btn-primary" onclick="openProductModal()" id="addProductBtn">
            <i class="fas fa-plus"></i>
            Thêm sản phẩm
        </button>
    </div>

    <!-- Data Table -->
    <div class="data-table">
        <table id="productsTable">
            <thead>
                <tr>
                    <th><i class="fas fa-barcode"></i> Mã SP</th>
                    <th><i class="fas fa-tag"></i> Tên sản phẩm</th>
                    <th><i class="fas fa-folder"></i> Danh mục</th>
                    <th><i class="fas fa-ruler"></i> Size</th>
                    <th><i class="fas fa-palette"></i> Màu sắc</th>
                    <th><i class="fas fa-money-bill"></i> Giá bán</th>
                    <th><i class="fas fa-warehouse"></i> Tồn kho</th>
                    <th><i class="fas fa-toggle-on"></i> Trạng thái</th>
                    <th><i class="fas fa-cogs"></i> Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 3rem; color: var(--gray-500);">
                            <i class="fas fa-box" style="font-size: 3rem; margin-bottom: 1rem; color: var(--gray-300);"></i>
                            <br>
                            <strong>Chưa có sản phẩm nào</strong>
                            <br>
                            <small>Hãy thêm sản phẩm đầu tiên!</small>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <tr data-id="<?php echo $product['id']; ?>">
                            <td><strong style="color: var(--primary-color);"><?php echo htmlspecialchars($product['product_code']); ?></strong></td>
                            <td>
                                <div class="product-info">
                                    <span class="product-name"><?php echo htmlspecialchars($product['name']); ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="category-badge">
                                    <?php echo htmlspecialchars($product['category_name'] ?? 'Chưa phân loại'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($product['size'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($product['color'] ?? '-'); ?></td>
                            <td><strong style="color: var(--success-color);"><?php echo number_format($product['selling_price']); ?>₫</strong></td>
                            <td>
                                <span class="stock-badge <?php echo $product['stock_quantity'] <= 10 ? 'stock-low' : 'stock-ok'; ?>">
                                    <?php echo $product['stock_quantity']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($product['is_active']): ?>
                                    <span class="status-badge status-active">
                                        <i class="fas fa-check-circle"></i>
                                        Hoạt động
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">
                                        <i class="fas fa-times-circle"></i>
                                        Ngừng bán
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-small btn-secondary" onclick="editProduct(<?php echo $product['id']; ?>)" title="Sửa sản phẩm">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-small btn-danger" onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')" title="Xóa sản phẩm">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Thêm/Sửa sản phẩm -->
<div id="productModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">
                <i class="fas fa-plus"></i>
                Thêm sản phẩm mới
            </h3>
            <button class="close" onclick="closeProductModal('productModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" id="productForm">
                <input type="hidden" name="action" value="add_product">
                <input type="hidden" name="id" id="productId">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="product_code">
                            <i class="fas fa-barcode"></i>
                            Mã sản phẩm <span class="required">*</span>
                        </label>
                        <input type="text" name="product_code" id="product_code" value="<?php echo $newProductCode; ?>" readonly required>
                    </div>
                    
                    <div class="form-group">
                        <label for="name">
                            <i class="fas fa-tag"></i>
                            Tên sản phẩm <span class="required">*</span>
                        </label>
                        <input type="text" name="name" id="name" placeholder="Nhập tên sản phẩm" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">
                            <i class="fas fa-folder"></i>
                            Danh mục
                        </label>
                        <select name="category_id" id="category_id">
                            <option value="">-- Chọn danh mục --</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="size">
                            <i class="fas fa-ruler"></i>
                            Kích thước
                        </label>
                        <input type="text" name="size" id="size" placeholder="Ví dụ: S, M, L hoặc 36, 37, 38">
                    </div>
                    
                    <div class="form-group">
                        <label for="color">
                            <i class="fas fa-palette"></i>
                            Màu sắc
                        </label>
                        <input type="text" name="color" id="color" placeholder="Màu sắc">
                    </div>
                    
                    <div class="form-group">
                        <label for="cost_price">
                            <i class="fas fa-dollar-sign"></i>
                            Giá nhập
                        </label>
                        <input type="number" name="cost_price" id="cost_price" placeholder="0" min="0" step="1000">
                    </div>
                    
                    <div class="form-group">
                        <label for="selling_price">
                            <i class="fas fa-money-bill"></i>
                            Giá bán <span class="required">*</span>
                        </label>
                        <input type="number" name="selling_price" id="selling_price" placeholder="0" min="0" step="1000" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="stock_quantity">
                            <i class="fas fa-warehouse"></i>
                            Số lượng tồn
                        </label>
                        <input type="number" name="stock_quantity" id="stock_quantity" value="0" min="0">
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="description">
                            <i class="fas fa-align-left"></i>
                            Mô tả
                        </label>
                        <textarea name="description" id="description" placeholder="Mô tả chi tiết sản phẩm"></textarea>
                    </div>

                    <div class="form-group" id="is_active_group" style="display: none;">
                        <label style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" name="is_active" id="is_active" value="1" style="width: auto;">
                            <i class="fas fa-toggle-on"></i>
                            Đang hoạt động
                        </label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeProductModal('productModal')">
                        <i class="fas fa-times"></i>
                        Hủy
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Lưu sản phẩm
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Debounce function
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
    const tbody = document.querySelector('#productsTable tbody');
    
    // Remove any existing "no results" row first
    const existingNoResultsRow = tbody.querySelector('.no-results-row');
    if (existingNoResultsRow) {
        existingNoResultsRow.remove();
    }
    
    if (searchTerm.length === 0) {
        // Show all products
        const rows = tbody.querySelectorAll('tr:not(.no-results-row)');
        rows.forEach(row => {
            if (row.cells.length > 1) {
                row.style.display = '';
            }
        });
        return;
    }
    
    if (searchTerm.length >= 1) {
        // Filter existing rows
        const rows = tbody.querySelectorAll('tr:not(.no-results-row)');
        const searchLower = searchTerm.toLowerCase();
        let visibleCount = 0;
        
        rows.forEach(row => {
            if (row.cells.length === 1) {
                row.style.display = 'none';
                return;
            }
            
            const productCode = row.cells[0].textContent.toLowerCase();
            const productName = row.cells[1].textContent.toLowerCase();
            const category = row.cells[2].textContent.toLowerCase();
            const size = row.cells[3].textContent.toLowerCase();
            const color = row.cells[4].textContent.toLowerCase();
            
            const matches = productCode.includes(searchLower) || 
                          productName.includes(searchLower) || 
                          category.includes(searchLower) ||
                          size.includes(searchLower) ||
                          color.includes(searchLower);
            
            if (matches) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Show "no results" message if no matches found
        if (visibleCount === 0) {
            const noResultsRow = document.createElement('tr');
            noResultsRow.className = 'no-results-row';
            noResultsRow.innerHTML = `
                <td colspan="9" style="text-align: center; padding: 2rem; color: var(--gray-500);">
                    <i class="fas fa-search" style="font-size: 2rem; margin-bottom: 1rem; color: var(--gray-300);"></i>
                    <br>
                    Không tìm thấy sản phẩm nào với từ khóa "<strong>${escapeHtml(searchTerm)}</strong>"
                    <br><small>Hãy thử tìm kiếm bằng mã sản phẩm, tên, danh mục, size hoặc màu sắc</small>
                </td>
            `;
            tbody.appendChild(noResultsRow);
        }
    }
}

// Debounced search
const debouncedSearch = debounce(handleSearch, 300);

document.getElementById('productSearch').addEventListener('input', function(e) {
    debouncedSearch(e.target.value);
});

// Handle Enter key for search and clear search with Escape
document.getElementById('productSearch').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        handleSearch(this.value);
    } else if (e.key === 'Escape') {
        e.preventDefault();
        this.value = '';
        handleSearch('');
        showToast('Đã xóa bộ lọc tìm kiếm', 'info');
    }
});

// Open product modal
function openProductModal() {
    const modal = document.getElementById('productModal');
    const modalTitle = document.getElementById('modalTitle');
    const productForm = document.getElementById('productForm');
    const actionInput = document.querySelector('[name="action"]');
    const productId = document.getElementById('productId');
    const productCode = document.getElementById('product_code');
    
    // Reset form and set for adding new product
    if (productForm) {
        productForm.reset();
    }
    if (modalTitle) modalTitle.innerHTML = '<i class="fas fa-plus"></i> Thêm sản phẩm mới';
    if (actionInput) actionInput.value = 'add_product';
    if (productId) productId.value = '';
    if (productCode) productCode.value = '<?php echo $newProductCode; ?>';
    
    // Hide is_active checkbox for new products
    const isActiveGroup = document.getElementById('is_active_group');
    if (isActiveGroup) isActiveGroup.style.display = 'none';
    
    // Show modal
    modal.style.display = 'flex';
    
    // Focus first input
    setTimeout(() => {
        const nameField = document.getElementById('name');
        if (nameField) {
            nameField.focus();
        }
    }, 100);
}

// Edit product
async function editProduct(id) {
    try {
        const formData = new FormData();
        formData.append('action', 'get_product_details');
        formData.append('id', id);

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
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Sửa sản phẩm';
            document.querySelector('[name="action"]').value = 'edit_product';
            document.getElementById('productId').value = product.id;
            document.getElementById('product_code').value = product.product_code;
            document.getElementById('name').value = product.name;
            document.getElementById('category_id').value = product.category_id;
            document.getElementById('size').value = product.size;
            document.getElementById('color').value = product.color;
            document.getElementById('cost_price').value = product.cost_price;
            document.getElementById('selling_price').value = product.selling_price;
            document.getElementById('stock_quantity').value = product.stock_quantity;
            document.getElementById('description').value = product.description;
            
            // Handle is_active checkbox
            const isActiveCheckbox = document.getElementById('is_active');
            const isActiveGroup = document.getElementById('is_active_group');
            if (isActiveCheckbox && isActiveGroup) {
                isActiveCheckbox.checked = !!parseInt(product.is_active);
                isActiveGroup.style.display = 'block';
            }

            document.getElementById('productModal').style.display = 'flex';
            document.getElementById('name').focus();
        } else {
            showToast(result.message || 'Không thể tải thông tin sản phẩm.', 'error');
        }
    } catch (error) {
        console.error('Error fetching product details:', error);
        showToast('Lỗi kết nối hoặc xử lý: ' + error.message, 'error');
    }
}

// Delete product
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
function closeProductModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('productModal');
    if (event.target === modal) {
        closeProductModal('productModal');
    }
}

// Show toast notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Helper functions
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function removeProductRow(productId) {
    const existingRow = document.querySelector(`#productsTable tbody tr[data-id="${productId}"]`);
    if (existingRow) {
        existingRow.remove();
        
        // Check if table is now empty
        const tableBody = document.querySelector('#productsTable tbody');
        if (tableBody.children.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="9" style="text-align: center; padding: 3rem; color: var(--gray-500);">
                        <i class="fas fa-box" style="font-size: 3rem; margin-bottom: 1rem; color: var(--gray-300);"></i>
                        <br>
                        <strong>Chưa có sản phẩm nào</strong>
                        <br>
                        <small>Hãy thêm sản phẩm đầu tiên!</small>
                    </td>
                </tr>
            `;
        }
    }
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    const isInputElement = e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT';
    
    if (e.key === 'F2') {
        e.preventDefault();
        openProductModal();
        showToast('Mở form thêm sản phẩm (F2)', 'info');
        return;
    }
    
    if (e.key === 'F3') {
        e.preventDefault();
        const searchInput = document.getElementById('productSearch');
        searchInput.focus();
        searchInput.select();
        showToast('Focus vào tìm kiếm sản phẩm (F3)', 'info');
        return;
    }
    
    if (e.key === 'Escape') {
        const modal = document.getElementById('productModal');
        if (modal && modal.style.display === 'flex') {
            closeProductModal('productModal');
            showToast('Đóng modal (Esc)', 'info');
        }
        return;
    }
    
    if (e.ctrlKey || e.metaKey) {
        switch(e.key) {
            case 'n':
                e.preventDefault();
                openProductModal();
                showToast('Thêm sản phẩm mới (Ctrl+N)', 'info');
                break;
            case 'f':
                e.preventDefault();
                const searchInput = document.getElementById('productSearch');
                searchInput.focus();
                searchInput.select();
                showToast('Tìm kiếm sản phẩm (Ctrl+F)', 'info');
                break;
            case 's':
                e.preventDefault();
                const form = document.getElementById('productForm');
                if (form && document.getElementById('productModal').style.display === 'flex') {
                    form.dispatchEvent(new Event('submit'));
                    showToast('Lưu sản phẩm (Ctrl+S)', 'info');
                }
                break;
        }
    }
});

// Form submission handler
document.addEventListener('DOMContentLoaded', function() {
    const productForm = document.getElementById('productForm');
    if (productForm) {
        productForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            const action = document.querySelector('[name="action"]').value;
            const productIdValue = document.getElementById('productId').value;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang lưu...';

            try {
                const formData = new FormData(this);
                formData.append('ajax', '1');

                const response = await fetch('index.php?page=products', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
                    showToast(result.message, 'success');
                    closeProductModal('productModal');
                    
                    // Reload page to show updated data
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showToast(result.message || 'Có lỗi xảy ra.', 'error');
                }
            } catch (error) {
                console.error('Error submitting form:', error);
                showToast('Có lỗi xảy ra khi lưu dữ liệu: ' + error.message, 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    }
});
</script>

</body>
</html>
