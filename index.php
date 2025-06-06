<?php
/**
 * Trendy Station - Main Index File
 * Hệ thống quản lý shop thời trang
 */

// Start session
session_start();

// Include database connection
require_once 'config/database.php';

// Get current page
$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? '';

// Handle logout
if ($action === 'logout') {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Set current page for navigation
$current_page = $page;

// Set page title
$page_titles = [
    'dashboard' => '🏠 Tổng quan',
    'products' => '📦 Quản lý Sản phẩm',
    'sales' => '💰 Bán hàng',
    'imports' => '📥 Nhập hàng',
    'customers' => '👥 Quản lý Khách hàng',
    'returns' => '↩️ Trả hàng',
    'reports' => '📊 Báo cáo'
];

$page_title = $page_titles[$page] ?? 'Trang chủ';

// Include header
include 'includes/header.php';

// Route to appropriate page
switch ($page) {
    case 'dashboard':
        include 'pages/dashboard.php';
        break;
    case 'products':
        include 'pages/products.php';
        break;
    case 'sales':
        include 'pages/sales.php';
        break;
    case 'imports':
        include 'pages/imports.php';
        break;
    case 'customers':
        include 'pages/customers.php';
        break;
    case 'returns':
        include 'pages/returns.php';
        break;
    case 'reports':
        include 'pages/reports.php';
        break;
    default:
        include 'pages/products.php';
        break;
}

// Include footer
include 'includes/footer.php';
?>