<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>The Trendy Station</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="logo">
            <span style="font-size: 2rem;">👑</span>
            The Trendy Station
        </div>
        <nav class="nav-menu">            <div class="nav-item <?php echo (isset($current_page) && $current_page == 'dashboard') ? 'active' : ''; ?>" onclick="location.href='index.php?page=dashboard'">
                🏠 Tổng quan
            </div>
            <div class="nav-item <?php echo (isset($current_page) && $current_page == 'products') ? 'active' : ''; ?>" onclick="location.href='index.php?page=products'">
                📦 Sản phẩm
            </div>
            <div class="nav-item <?php echo (isset($current_page) && ($current_page == 'sales' || $current_page == 'all_sales')) ? 'active' : ''; ?>" onclick="location.href='index.php?page=sales'">
                💰 Bán hàng
            </div>
            <div class="nav-item <?php echo (isset($current_page) && ($current_page == 'imports' || $current_page == 'all_imports')) ? 'active' : ''; ?>" onclick="location.href='index.php?page=imports'">
                📥 Nhập hàng
            </div>
            <div class="nav-item <?php echo (isset($current_page) && $current_page == 'customers') ? 'active' : ''; ?>" onclick="location.href='index.php?page=customers'">
                👥 Khách hàng
            </div>
            <div class="nav-item <?php echo (isset($current_page) && $current_page == 'returns') ? 'active' : ''; ?>" onclick="location.href='index.php?page=returns'">
                ↩️ Trả hàng
            </div>
            <div class="nav-item <?php echo (isset($current_page) && $current_page == 'reports') ? 'active' : ''; ?>" onclick="location.href='index.php?page=reports'">
                📊 Báo cáo
            </div>
            <div class="nav-item" style="background: var(--danger-gradient); color: white;" onclick="logout()">
                🚪 Thoát
            </div>
        </nav>
    </div>    <!-- Main Content -->
    <div class="main-content">

<script>
// Logout function
function logout() {
    if (confirm('Bạn có chắc chắn muốn thoát không?')) {
        window.location.href = 'index.php?action=logout';
    }
}
</script>
