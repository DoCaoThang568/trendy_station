<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Khách hàng</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --purple-color: #6f42c1;
            --card-shadow: 0 4px 15px rgba(0,0,0,0.08);
            --hover-shadow: 0 8px 25px rgba(0,0,0,0.15);
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .customers-page {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        /* Header Section */
        .page-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 0;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .stat-card {
            background: var(--primary-gradient);
            color: white;
            padding: 1.8rem;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            gap: 1.2rem;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 100%);
            pointer-events: none;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .stat-icon {
            font-size: 2.5rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 70px;
            height: 70px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.3rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .stat-label {
            font-size: 0.95rem;
            font-weight: 600;
            opacity: 0.95;
            text-shadow: 0 1px 2px rgba(0,0,0,0.2);
        }

        /* Filters Section */
        .filters-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .search-input-wrapper {
            position: relative;
        }

        .search-input-wrapper .form-control {
            padding-left: 3rem;
            border-radius: 25px;
            border: 2px solid #e9ecef;
            transition: var(--transition);
            height: 45px;
        }

        .search-input-wrapper .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .search-input-wrapper .input-group-text {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            z-index: 5;
            color: #6c757d;
        }

        .filter-select {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            transition: var(--transition);
            height: 45px;
        }

        .filter-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-filter {
            height: 45px;
            padding: 0 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        /* Customer Cards Grid */
        .customers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .customer-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-left: 4px solid transparent;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .customer-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }

        .customer-card[data-membership="vip"] {
            border-left-color: var(--warning-color);
        }

        .customer-card[data-membership="vvip"] {
            border-left-color: var(--purple-color);
        }

        .customer-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .customer-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }

        .customer-code {
            font-size: 0.85rem;
            color: #6c757d;
            font-weight: 500;
        }

        .customer-actions .dropdown-toggle {
            border: none;
            background: rgba(108, 117, 125, 0.1);
            color: #6c757d;
            border-radius: 8px;
            padding: 0.5rem;
            transition: var(--transition);
        }

        .customer-actions .dropdown-toggle:hover {
            background: rgba(108, 117, 125, 0.2);
            transform: scale(1.1);
        }

        .customer-details {
            margin-bottom: 1rem;
            flex: 1;
        }

        .detail-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            color: #495057;
        }

        .detail-item i {
            width: 20px;
            margin-right: 0.5rem;
            opacity: 0.7;
        }

        .detail-item .badge {
            font-size: 0.75rem;
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
        }

        .bg-purple {
            background-color: var(--purple-color) !important;
            color: white;
        }

        .customer-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent 0%, #e9ecef 50%, transparent 100%);
            margin: 1rem 0;
        }

        .customer-finance {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .finance-item {
            text-align: center;
            padding: 0.75rem;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 8px;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }

        .finance-label {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
            font-weight: 600;
        }

        .finance-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: #2c3e50;
        }

        .last-order {
            font-size: 0.85rem;
            color: #6c757d;
            text-align: center;
            padding: 0.5rem;
            background: rgba(23, 162, 184, 0.05);
            border-radius: 6px;
            border: 1px solid rgba(23, 162, 184, 0.1);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
        }

        .empty-icon {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        .empty-text {
            color: #6c757d;
            margin-bottom: 1.5rem;
        }

        /* Pagination */
        .pagination-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .pagination .page-link {
            border: none;
            padding: 0.75rem 1rem;
            margin: 0 0.25rem;
            border-radius: 8px;
            color: #6c757d;
            transition: var(--transition);
        }

        .pagination .page-link:hover {
            background: var(--primary-gradient);
            color: white;
            transform: translateY(-2px);
        }

        .pagination .page-item.active .page-link {
            background: var(--primary-gradient);
            border: none;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .pagination-info {
            text-align: center;
            margin-top: 1rem;
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* Modal Improvements */
        .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: var(--hover-shadow);
        }

        .modal-header {
            background: var(--primary-gradient);
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .modal-title {
            font-weight: 700;
        }

        .btn-close {
            filter: brightness(0) invert(1);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .customers-page {
                padding: 1rem 0.5rem;
            }
            
            .page-header {
                padding: 1.5rem;
            }
            
            .page-title {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .stat-card {
                padding: 1.5rem;
            }
            
            .customers-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .filters-section .row {
                gap: 1rem;
            }
            
            .detail-row {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
        }

        /* Animations */
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

        .customer-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .customer-card:nth-child(even) {
            animation-delay: 0.1s;
        }

        .customer-card:nth-child(3n) {
            animation-delay: 0.2s;
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        .toast {
            border-radius: 8px;
            border: none;
            box-shadow: var(--card-shadow);
        }
    </style>
</head>
<body>
    <div class="customers-page">
        <!-- Header với thống kê -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="page-title">👥 Quản Lý Khách Hàng</h1>
                    <p class="page-subtitle">Quản lý thông tin và theo dõi hoạt động khách hàng</p>
                </div>
                <button class="btn btn-primary btn-lg" onclick="openAddCustomerModal()">
                    <i class="fas fa-plus me-2"></i>Thêm Khách Hàng
                    <kbd class="ms-2">F1</kbd>
                </button>
            </div>

            <!-- Thống kê nhanh -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <div class="stat-number">1,247</div>
                        <div class="stat-label">Tổng khách hàng</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✨</div>
                    <div class="stat-info">
                        <div class="stat-number">156</div>
                        <div class="stat-label">VIP + VVIP</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <div class="stat-number">2,450,000đ</div>
                        <div class="stat-label">Tổng doanh thu</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-info">
                        <div class="stat-number">185,000đ</div>
                        <div class="stat-label">Chi tiêu trung bình</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bộ lọc và tìm kiếm -->
        <div class="filters-section">
            <form class="row g-3 align-items-end" id="filterForm">
                <div class="col-md-4">
                    <div class="search-input-wrapper">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="searchInput" class="form-control" 
                               placeholder="Tìm kiếm khách hàng... (F2)">
                    </div>
                </div>
                <div class="col-md-2">
                    <select id="statusFilter" class="form-select filter-select">
                        <option value="all">Tất cả trạng thái</option>
                        <option value="active">Hoạt động</option>
                        <option value="inactive">Không hoạt động</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="membershipFilter" class="form-select filter-select">
                        <option value="all">Tất cả hạng</option>
                        <option value="Thông thường">Thông thường</option>
                        <option value="VIP">VIP</option>
                        <option value="VVIP">VVIP</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary btn-filter w-100" onclick="applyFilters()">
                        <i class="fas fa-filter me-2"></i>Lọc
                    </button>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-secondary btn-filter w-100" onclick="resetFilters()">
                        <i class="fas fa-undo me-2"></i>Reset
                    </button>
                </div>
            </form>
        </div>

        <!-- Danh sách khách hàng -->
        <div class="customers-grid" id="customersGrid">
            <!-- Customer Card 1 -->
            <div class="customer-card" data-membership="vvip">
                <div class="customer-header">
                    <div>
                        <h5 class="customer-name">Nguyễn Văn An</h5>
                        <small class="customer-code">KH001</small>
                    </div>
                    <div class="customer-actions">
                        <div class="dropdown">
                            <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#"><i class="fas fa-edit me-2"></i>Sửa</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-eye me-2"></i>Xem chi tiết</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#"><i class="fas fa-trash me-2"></i>Xóa</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="customer-details">
                    <div class="detail-row">
                        <div class="detail-item">
                            <i class="fas fa-phone text-primary"></i>
                            <span>0901234567</span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-envelope text-success"></i>
                            <span>an@email.com</span>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-item">
                            <i class="fas fa-birthday-cake text-warning"></i>
                            <span>28 tuổi</span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-user-shield text-info"></i>
                            <span class="badge bg-purple">VVIP</span>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-item">
                            <i class="fas fa-toggle-on text-success"></i>
                            <span class="badge bg-success">Hoạt động</span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-history text-primary"></i>
                            <span class="badge bg-light text-dark border">Hoạt động</span>
                        </div>
                    </div>
                </div>

                <div class="customer-divider"></div>

                <div class="customer-finance">
                    <div class="finance-item">
                        <div class="finance-label"><i class="fas fa-wallet me-1"></i>Tổng chi</div>
                        <div class="finance-value">2,500,000đ</div>
                    </div>
                    <div class="finance-item">
                        <div class="finance-label"><i class="fas fa-box-open me-1"></i>Đơn hàng</div>
                        <div class="finance-value">45</div>
                    </div>
                </div>

                <div class="last-order">
                    <i class="fas fa-stopwatch me-1"></i>Mua cuối: 15/12/2024 (5 ngày trước)
                </div>
            </div>

            <!-- Customer Card 2 -->
            <div class="customer-card" data-membership="vip">
                <div class="customer-header">
                    <div>
                        <h5 class="customer-name">Trần Thị Bình</h5>
                        <small class="customer-code">KH002</small>
                    </div>
                    <div class="customer-actions">
                        <div class="dropdown">
                            <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#"><i class="fas fa-edit me-2"></i>Sửa</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-eye me-2"></i>Xem chi tiết</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#"><i class="fas fa-trash me-2"></i>Xóa</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="customer-details">
                    <div class="detail-row">
                        <div class="detail-item">
                            <i class="fas fa-phone text-primary"></i>
                            <span>0987654321</span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-envelope text-success"></i>
                            <span>binh@email.com</span>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-item">
                            <i class="fas fa-birthday-cake text-warning"></i>
                            <span>35 tuổi</span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-user-shield text-info"></i>
                            <span class="badge bg-warning text-dark">VIP</span>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-item">
                            <i class="fas fa-toggle-on text-success"></i>
                            <span class="badge bg-success">Hoạt động</span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-history text-primary"></i>
                            <span class="badge bg-light text-dark border">Ít hoạt động</span>
                        </div>
                    </div>
                </div>

                <div class="customer-divider"></div>

                <div class="customer-finance">
                    <div class="finance-item">
                        <div class="finance-label"><i class="fas fa-wallet me-1"></i>Tổng chi</div>
                        <div class="finance-value">1,200,000đ</div>
                    </div>
                    <div class="finance-item">
                        <div class="finance-label"><i class="fas fa-box-open me-1"></i>Đơn hàng</div>
                        <div class="finance-value">28</div>
                    </div>
                </div>

                <div class="last-order">
                    <i class="fas fa-stopwatch me-1"></i>Mua cuối: 01/12/2024 (19 ngày trước)
                </div>
            </div>

            <!-- Customer Card 3 -->
            <div class="customer-card">
                <div class="customer-header">
                    <div>
                        <h5 class="customer-name">Lê Văn Cường</h5>
                        <small class="customer-code">KH003</small>
                    </div>
                    <div class="customer-actions">
                        <div class="dropdown">
                            <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#"><i class="fas fa-edit me-2"></i>Sửa</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-eye me-2"></i>Xem chi tiết</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#"><i class="fas fa-trash me-2"></i>Xóa</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="customer-details">
                    <div class="detail-row">
                        <div class="detail-item">
                            <i class="fas fa-phone text-primary"></i>
                            <span>0912345678</span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-envelope text-muted"></i>
                            <span>N/A</span>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-item">
                            <i class="fas fa-birthday-cake text-warning"></i>
                            <span>42 tuổi</span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-user-shield text-info"></i>
                            <span class="badge bg-secondary">Thông thường</span>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-item">
                            <i class="fas fa-toggle-on text-success"></i>
                            <span class="badge bg-success">Hoạt động</span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-history text-primary"></i>
                            <span class="badge bg-light text-dark border">Lâu không mua</span>
                        </div>
                    </div>
                </div>

                <div class="customer-divider"></div>

                <div class="customer-finance">
                    <div class="finance-item">
                        <div class="finance-label"><i class="fas fa-wallet me-1"></i>Tổng chi</div>
                        <div class="finance-value">450,000