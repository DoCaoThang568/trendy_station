-- ========================================
-- DATABASE: THE TRENDY STATION v2.0
-- Fixed SQL file - No DELIMITER issues
-- ========================================

CREATE DATABASE IF NOT EXISTS trendy_station CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE trendy_station;

-- Tắt foreign key checks để import clean
SET FOREIGN_KEY_CHECKS = 0;

-- Xóa tất cả bảng cũ nếu có
DROP TABLE IF EXISTS return_details;
DROP TABLE IF EXISTS returns;
DROP TABLE IF EXISTS import_details;
DROP TABLE IF EXISTS imports;
DROP TABLE IF EXISTS sale_details;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS customer_points;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS suppliers;

-- Bật lại foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- ========================================
-- 1. BẢNG DANH MỤC SẢN PHẨM
-- ========================================
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO categories (name, description) VALUES
('Áo Thun', 'Các loại áo thun nam, nữ, unisex thời trang'),
('Áo Sơ Mi', 'Áo sơ mi công sở, casual, formal cao cấp'),
('Quần Jean', 'Quần jean nam, nữ các kiểu dáng trendy'),
('Quần Tây', 'Quần tây công sở, dự tiệc sang trọng'),
('Váy Đầm', 'Váy đầm dự tiệc, casual, công sở'),
('Áo Khoác', 'Áo khoác mùa đông, jacket, blazer'),
('Phụ Kiện', 'Túi xách, giày dép, trang sức thời trang'),
('Đồ Thể Thao', 'Quần áo thể thao, gym, yoga chất lượng cao');

-- ========================================
-- 2. BẢNG NHÀ CUNG CẤP
-- ========================================
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(200) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    tax_code VARCHAR(20),
    payment_terms VARCHAR(100) DEFAULT 'Thanh toán khi nhận hàng',
    is_active BOOLEAN DEFAULT TRUE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO suppliers (supplier_code, name, contact_person, phone, email, address, tax_code) VALUES
('NCC001', 'Công ty TNHH Thời Trang Việt', 'Nguyễn Văn A', '0901234567', 'contact@thoitrangviet.com', '123 Nguyễn Huệ, Q1, HCM', '0123456789'),
('NCC002', 'Fashion House Co.', 'Trần Thị B', '0987654321', 'sales@fashionhouse.vn', '456 Lê Lợi, Q1, HCM', '0987654321'),
('NCC003', 'Nhà phân phối Luxury Brand', 'Lê Văn C', '0912345678', 'info@luxurybrand.vn', '789 Đồng Khởi, Q1, HCM', '0112233445'),
('NCC004', 'Xưởng May Hồng Phát', 'Phạm Thị D', '0923456789', 'hongphat@gmail.com', '321 Cách Mạng Tháng 8, Q3, HCM', '0556677889');

-- ========================================
-- 3. BẢNG SẢN PHẨM
-- ========================================
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(200) NOT NULL,
    category_id INT,
    description TEXT,
    cost_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    selling_price DECIMAL(12,2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    min_stock_level INT DEFAULT 10,
    max_stock_level INT DEFAULT 100,
    size VARCHAR(50),
    color VARCHAR(50),
    material VARCHAR(100),
    brand VARCHAR(100),
    season VARCHAR(20) DEFAULT 'All Season',
    gender ENUM('Nam', 'Nữ', 'Unisex') DEFAULT 'Unisex',
    is_active BOOLEAN DEFAULT TRUE,
    image_url VARCHAR(255),
    barcode VARCHAR(100),
    weight DECIMAL(8,2) DEFAULT 0,
    supplier_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    
    INDEX idx_product_code (product_code),
    INDEX idx_category (category_id),
    INDEX idx_stock (stock_quantity),
    INDEX idx_active (is_active)
);

-- Sample products data
INSERT INTO products (product_code, name, category_id, description, cost_price, selling_price, stock_quantity, size, color, material, brand, gender, supplier_id) VALUES
('SP001', 'Áo Thun Basic Nam Cotton', 1, 'Áo thun nam chất liệu cotton 100% thoáng mát', 150000, 250000, 50, 'M,L,XL', 'Trắng,Đen,Xám', 'Cotton 100%', 'Trendy Station', 'Nam', 1),
('SP002', 'Áo Sơ Mi Công Sở Nam', 2, 'Áo sơ mi nam formal cao cấp', 200000, 350000, 30, 'S,M,L,XL', 'Trắng,Xanh,Hồng', 'Cotton/Polyester', 'Executive', 'Nam', 2),
('SP003', 'Quần Jean Skinny Nữ', 3, 'Quần jean nữ ôm dáng trendy', 250000, 450000, 25, '26,27,28,29,30', 'Xanh,Đen', 'Denim', 'Fashionista', 'Nữ', 1),
('SP004', 'Váy Đầm Dự Tiệc', 5, 'Váy đầm dự tiệc sang trọng', 300000, 550000, 15, 'S,M,L', 'Đỏ,Đen,Navy', 'Silk/Polyester', 'Elegant', 'Nữ', 3),
('SP005', 'Áo Khoác Hoodie Unisex', 6, 'Áo khoác hoodie phong cách street style', 180000, 320000, 40, 'M,L,XL,XXL', 'Đen,Xám,Navy', 'Cotton/Polyester', 'Street Style', 'Unisex', 1),
('SP006', 'Quần Tây Công Sở Nam', 4, 'Quần tây nam formal chất lượng cao', 220000, 380000, 20, '29,30,31,32,33', 'Đen,Xám,Navy', 'Wool/Polyester', 'Professional', 'Nam', 2),
('SP007', 'Túi Xách Nữ Cao Cấp', 7, 'Túi xách nữ da thật sang trọng', 400000, 700000, 12, 'One Size', 'Đen,Nâu,Beige', 'Da thật', 'Luxury', 'Nữ', 3),
('SP008', 'Giày Sneaker Thể Thao', 8, 'Giày sneaker unisex năng động', 350000, 600000, 35, '36,37,38,39,40,41,42', 'Trắng,Đen,Xám', 'Synthetic/Mesh', 'SportMax', 'Unisex', 4);

-- ========================================
-- 4. BẢNG KHÁCH HÀNG
-- ========================================
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) UNIQUE,
    email VARCHAR(100),
    address TEXT,
    birth_date DATE,
    gender ENUM('Nam', 'Nữ', 'Khác'),
    membership_level ENUM('Thông thường', 'VIP', 'VVIP') DEFAULT 'Thông thường',
    total_spent DECIMAL(15,2) DEFAULT 0,
    total_orders INT DEFAULT 0,
    loyalty_points INT DEFAULT 0,
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_membership_update TIMESTAMP NULL DEFAULT NULL, -- Thêm dòng này
    
    INDEX idx_phone (phone),
    INDEX idx_membership (membership_level),
    INDEX idx_active (is_active)
);

INSERT INTO customers (customer_code, name, phone, email, address, gender, membership_level, total_spent, total_orders, loyalty_points) VALUES
('KH001', 'Nguyễn Văn An', '0901111111', 'an@email.com', '123 Nguyễn Trãi, Q1, HCM', 'Nam', 'VIP', 5200000, 12, 520),
('KH002', 'Trần Thị Bình', '0902222222', 'binh@email.com', '456 Lê Văn Sỹ, Q3, HCM', 'Nữ', 'VVIP', 8500000, 18, 850),
('KH003', 'Lê Hoàng Cường', '0903333333', 'cuong@email.com', '789 Cách Mạng Tháng 8, Q10, HCM', 'Nam', 'Thông thường', 1200000, 4, 120),
('KH004', 'Phạm Thị Dung', '0904444444', 'dung@email.com', '321 Hoàng Văn Thụ, Tân Bình, HCM', 'Nữ', 'VIP', 3800000, 9, 380);

-- ========================================
-- 5. BẢNG HÓA ĐƠN BÁN HÀNG
-- ========================================
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_code VARCHAR(20) UNIQUE NOT NULL,
    customer_id INT,
    customer_name VARCHAR(100),
    customer_phone VARCHAR(20),
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    discount_amount DECIMAL(12,2) DEFAULT 0,
    final_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    payment_method ENUM('Tiền mặt', 'Chuyển khoản', 'Thẻ tín dụng', 'Ví điện tử') DEFAULT 'Tiền mặt',
    payment_status ENUM('Chờ thanh toán', 'Đã thanh toán', 'Hoàn tiền') DEFAULT 'Đã thanh toán',
    notes TEXT,
    cashier_name VARCHAR(100) DEFAULT 'Admin',
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    
    INDEX idx_sale_code (sale_code),
    INDEX idx_customer (customer_id),
    INDEX idx_sale_date (sale_date),
    INDEX idx_payment_status (payment_status)
);

-- ========================================
-- 6. CHI TIẾT HÓA ĐƠN BÁN HÀNG
-- ========================================
CREATE TABLE sale_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    product_code VARCHAR(50),
    product_name VARCHAR(200),
    quantity INT NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    total_price DECIMAL(12,2) NOT NULL,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    discount_amount DECIMAL(12,2) DEFAULT 0,
    final_price DECIMAL(12,2) NOT NULL,
    
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    
    INDEX idx_sale (sale_id),
    INDEX idx_product (product_id)
);

-- ========================================
-- 7. BẢNG PHIẾU NHẬP HÀNG
-- ========================================
CREATE TABLE imports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    import_code VARCHAR(20) UNIQUE NOT NULL,
    supplier_id INT,
    supplier_name VARCHAR(200),
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    notes TEXT,
    import_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(100) DEFAULT 'Admin',
    status ENUM('Đang xử lý', 'Hoàn thành', 'Đã hủy') DEFAULT 'Hoàn thành',
    payment_status ENUM('pending', 'partial', 'paid') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    
    INDEX idx_import_code (import_code),
    INDEX idx_supplier (supplier_id),
    INDEX idx_import_date (import_date)
);

-- ========================================
-- 8. CHI TIẾT PHIẾU NHẬP HÀNG
-- ========================================
CREATE TABLE import_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    import_id INT NOT NULL,
    product_id INT NOT NULL,
    product_code VARCHAR(50),
    product_name VARCHAR(200),
    quantity INT NOT NULL,
    unit_cost DECIMAL(12,2) NOT NULL,
    total_cost DECIMAL(12,2) NOT NULL,
    expiry_date DATE,
    batch_number VARCHAR(50),
    
    FOREIGN KEY (import_id) REFERENCES imports(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    
    INDEX idx_import (import_id),
    INDEX idx_product (product_id)
);

-- ========================================
-- 9. BẢNG TRẢ HÀNG
-- ========================================
CREATE TABLE returns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    return_code VARCHAR(20) UNIQUE NOT NULL,
    sale_id INT,
    customer_id INT,
    customer_name VARCHAR(100),
    reason ENUM('Lỗi sản phẩm', 'Không vừa size', 'Không đúng mô tả', 'Khách đổi ý', 'Khác') NOT NULL,
    total_refund DECIMAL(12,2) NOT NULL DEFAULT 0,
    refund_method ENUM('Tiền mặt', 'Chuyển khoản', 'Thẻ tín dụng', 'Hoàn điểm') DEFAULT 'Tiền mặt',
    status ENUM('Chờ xử lý', 'Đã duyệt', 'Đã hoàn tiền', 'Từ chối') DEFAULT 'Đã duyệt',
    notes TEXT,
    return_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_by VARCHAR(100) DEFAULT 'Admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE SET NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    
    INDEX idx_return_code (return_code),
    INDEX idx_sale (sale_id),
    INDEX idx_return_date (return_date)
);

-- ========================================
-- 10. CHI TIẾT TRẢ HÀNG
-- ========================================
CREATE TABLE return_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    return_id INT NOT NULL,
    product_id INT NOT NULL,
    product_code VARCHAR(50),
    product_name VARCHAR(200),
    quantity INT NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    total_refund DECIMAL(12,2) NOT NULL,
    condition_status ENUM('Mới', 'Đã sử dụng', 'Lỗi') DEFAULT 'Mới',
    
    FOREIGN KEY (return_id) REFERENCES returns(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    
    INDEX idx_return (return_id),
    INDEX idx_product (product_id)
);

-- ========================================
-- 11. BẢNG ĐIỂM THƯỞNG KHÁCH HÀNG
-- ========================================
CREATE TABLE customer_points (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    transaction_type ENUM('Earn', 'Redeem', 'Expire', 'Adjust') NOT NULL,
    points INT NOT NULL,
    description VARCHAR(255),
    reference_id INT,
    reference_type ENUM('Sale', 'Return', 'Manual') DEFAULT 'Sale',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    
    INDEX idx_customer (customer_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_created_at (created_at)
);

-- ========================================
-- VIEWS FOR REPORTING
-- ========================================

-- View: Thống kê sản phẩm
CREATE VIEW product_stats AS
SELECT 
    p.id,
    p.product_code,
    p.name,
    c.name as category_name,
    p.stock_quantity,
    p.selling_price,
    p.cost_price,
    (p.selling_price - p.cost_price) as profit_per_unit,
    CASE 
        WHEN p.stock_quantity <= p.min_stock_level THEN 'Low Stock'
        WHEN p.stock_quantity <= (p.min_stock_level * 1.5) THEN 'Medium Stock'
        ELSE 'Good Stock'
    END as stock_status,
    p.is_active
FROM products p
LEFT JOIN categories c ON p.category_id = c.id;

-- View: Thống kê khách hàng
CREATE VIEW customer_stats AS
SELECT 
    c.id,
    c.customer_code,
    c.name,
    c.phone,
    c.membership_level,
    c.total_spent,
    c.total_orders,
    c.loyalty_points,
    CASE 
        WHEN c.total_spent >= 10000000 OR c.total_orders >= 15 THEN 'VVIP'
        WHEN c.total_spent >= 5000000 OR c.total_orders >= 10 THEN 'VIP'
        ELSE 'Thông thường'
    END as suggested_level,
    c.is_active,
    c.last_membership_update -- Thêm dòng này
FROM customers c;

-- View: Báo cáo bán hàng
CREATE VIEW sales_report AS
SELECT 
    s.id,
    s.sale_code,
    s.customer_name,
    s.total_amount,
    s.final_amount,
    s.payment_method,
    s.payment_status,
    s.sale_date,
    COUNT(sd.id) as total_items,
    SUM(sd.quantity) as total_quantity
FROM sales s
LEFT JOIN sale_details sd ON s.id = sd.sale_id
GROUP BY s.id;

-- ========================================
-- SAMPLE DATA
-- ========================================

-- Sample Sales
INSERT INTO sales (sale_code, customer_id, customer_name, customer_phone, total_amount, final_amount, payment_method) VALUES
('HD001', 1, 'Nguyễn Văn An', '0901111111', 700000, 700000, 'Tiền mặt'),
('HD002', 2, 'Trần Thị Bình', '0902222222', 950000, 950000, 'Chuyển khoản'),
('HD003', 3, 'Lê Hoàng Cường', '0903333333', 600000, 600000, 'Tiền mặt');

-- Sample Sale Details
INSERT INTO sale_details (sale_id, product_id, product_code, product_name, quantity, unit_price, total_price, final_price) VALUES
(1, 1, 'SP001', 'Áo Thun Basic Nam Cotton', 2, 250000, 500000, 500000),
(1, 5, 'SP005', 'Áo Khoác Hoodie Unisex', 1, 320000, 320000, 320000),
(2, 4, 'SP004', 'Váy Đầm Dự Tiệc', 1, 550000, 550000, 550000),
(2, 7, 'SP007', 'Túi Xách Nữ Cao Cấp', 1, 700000, 700000, 700000),
(3, 3, 'SP003', 'Quần Jean Skinny Nữ', 1, 450000, 450000, 450000),
(3, 8, 'SP008', 'Giày Sneaker Thể Thao', 1, 600000, 600000, 600000);

-- Sample Imports
INSERT INTO imports (import_code, supplier_id, supplier_name, total_amount, notes) VALUES
('PN001', 1, 'Công ty TNHH Thời Trang Việt', 5000000, 'Nhập hàng tháng 6/2025'),
('PN002', 2, 'Fashion House Co.', 3500000, 'Nhập hàng summer collection');

-- Sample Import Details
INSERT INTO import_details (import_id, product_id, product_code, product_name, quantity, unit_cost, total_cost) VALUES
(1, 1, 'SP001', 'Áo Thun Basic Nam Cotton', 50, 150000, 7500000),
(1, 2, 'SP002', 'Áo Sơ Mi Công Sở Nam', 30, 200000, 6000000),
(2, 3, 'SP003', 'Quần Jean Skinny Nữ', 25, 250000, 6250000),
(2, 4, 'SP004', 'Váy Đầm Dự Tiệc', 15, 300000, 4500000);

-- Update product stock after import
UPDATE products SET stock_quantity = stock_quantity + 50 WHERE id = 1;
UPDATE products SET stock_quantity = stock_quantity + 30 WHERE id = 2;
UPDATE products SET stock_quantity = stock_quantity + 25 WHERE id = 3;
UPDATE products SET stock_quantity = stock_quantity + 15 WHERE id = 4;

-- Update customer totals
UPDATE customers SET 
    total_spent = (SELECT COALESCE(SUM(final_amount), 0) FROM sales WHERE customer_id = customers.id),
    total_orders = (SELECT COUNT(*) FROM sales WHERE customer_id = customers.id);

-- Sample Customer Points
INSERT INTO customer_points (customer_id, transaction_type, points, description, reference_id, reference_type) VALUES
(1, 'Earn', 70, 'Điểm từ hóa đơn HD001', 1, 'Sale'),
(2, 'Earn', 95, 'Điểm từ hóa đơn HD002', 2, 'Sale'),
(3, 'Earn', 60, 'Điểm từ hóa đơn HD003', 3, 'Sale');

-- ========================================
-- INDEXES FOR PERFORMANCE
-- ========================================
CREATE INDEX idx_products_name ON products(name);
CREATE INDEX idx_products_barcode ON products(barcode);
CREATE INDEX idx_sales_date_range ON sales(sale_date, payment_status);
CREATE INDEX idx_imports_date_range ON imports(import_date, status);
CREATE INDEX idx_customers_phone_email ON customers(phone, email);

-- ========================================
-- STORED PROCEDURES FOR MEMBERSHIP UPDATE
-- ========================================
DROP PROCEDURE IF EXISTS UpdateCustomerMembership;
DELIMITER $$
CREATE PROCEDURE UpdateCustomerMembership(IN p_customer_id INT)
BEGIN
    DECLARE v_total_spent DECIMAL(15,2);
    DECLARE v_total_orders INT;
    DECLARE v_current_membership_level VARCHAR(20);
    DECLARE v_new_membership_level VARCHAR(20) DEFAULT 'Thông thường';

    -- Membership thresholds (mirroring cron/update_customer_membership.php)
    DECLARE VVIP_MIN_SPENT_THRESH DECIMAL(15,2) DEFAULT 10000000;
    DECLARE VVIP_MIN_ORDERS_THRESH INT DEFAULT 15;
    DECLARE VIP_MIN_SPENT_THRESH DECIMAL(15,2) DEFAULT 5000000;
    DECLARE VIP_MIN_ORDERS_THRESH INT DEFAULT 10;

    -- Get current stats and membership level for the customer
    SELECT total_spent, total_orders, membership_level
    INTO v_total_spent, v_total_orders, v_current_membership_level
    FROM customers
    WHERE id = p_customer_id;

    -- Determine new membership level
    IF v_total_spent >= VVIP_MIN_SPENT_THRESH OR v_total_orders >= VVIP_MIN_ORDERS_THRESH THEN
        SET v_new_membership_level = 'VVIP';
    ELSEIF v_total_spent >= VIP_MIN_SPENT_THRESH OR v_total_orders >= VIP_MIN_ORDERS_THRESH THEN
        SET v_new_membership_level = 'VIP';
    END IF;

    -- Update customer record
    IF v_new_membership_level <> v_current_membership_level THEN
        UPDATE customers
        SET membership_level = v_new_membership_level,
            last_membership_update = NOW()
        WHERE id = p_customer_id;
    ELSE
        -- Even if level doesn't change, update the check timestamp
        UPDATE customers
        SET last_membership_update = NOW()
        WHERE id = p_customer_id;
    END IF;
END$$ 
DELIMITER ;

-- ========================================
-- TRIGGERS FOR AUTOMATIC MEMBERSHIP UPDATE
-- ========================================

-- Trigger after a new sale is inserted to update order count and membership
DROP TRIGGER IF EXISTS trg_after_sale_insert_update_customer_stats;
DELIMITER $$
CREATE TRIGGER trg_after_sale_insert_update_customer_stats
AFTER INSERT ON sales
FOR EACH ROW
BEGIN
    IF NEW.customer_id IS NOT NULL THEN
        -- Update total_orders for the customer
        UPDATE customers
        SET total_orders = total_orders + 1
        WHERE id = NEW.customer_id;

        -- Call procedure to update membership level
        CALL UpdateCustomerMembership(NEW.customer_id);
    END IF;
END$$
DELIMITER ;

-- Trigger after a new sale detail is inserted to update total spent and membership
DROP TRIGGER IF EXISTS trg_after_sale_detail_insert_update_customer_stats;
DELIMITER $$
CREATE TRIGGER trg_after_sale_detail_insert_update_customer_stats
AFTER INSERT ON sale_details
FOR EACH ROW
BEGIN
    DECLARE v_customer_id INT;

    -- Get customer_id from the sales table
    SELECT customer_id INTO v_customer_id
    FROM sales
    WHERE id = NEW.sale_id;

    IF v_customer_id IS NOT NULL THEN
        -- Update total_spent for the customer
        UPDATE customers
        SET total_spent = total_spent + NEW.final_price
        WHERE id = v_customer_id;

        -- Call procedure to update membership level
        CALL UpdateCustomerMembership(v_customer_id);
    END IF;
END$$
DELIMITER ;

-- ========================================
-- COMPLETED SUCCESSFULLY
-- ========================================
