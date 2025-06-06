-- ========================================
-- DATABASE: THE TRENDY STATION
-- File database chính - Import đầu tiên
-- ========================================

CREATE DATABASE IF NOT EXISTS trendy_station;
USE trendy_station;

-- Xóa bảng cũ nếu có (theo thứ tự dependency)
DROP TABLE IF EXISTS sale_details;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;

-- ========================================
-- BẢNG DANH MỤC SẢN PHẨM
-- ========================================
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Dữ liệu mẫu categories
INSERT INTO categories (name, description) VALUES
('Áo Thun', 'Các loại áo thun nam, nữ, unisex'),
('Áo Sơ Mi', 'Áo sơ mi công sở, casual, formal'),
('Quần Jean', 'Quần jean nam, nữ các kiểu dáng'),
('Quần Tây', 'Quần tây công sở, dự tiệc'),
('Váy Đầm', 'Váy đầm dự tiệc, casual, công sở'),
('Áo Khoác', 'Áo khoác mùa đông, jacket, blazer'),
('Phụ Kiện', 'Túi xách, giày dép, trang sức'),
('Đồ Thể Thao', 'Quần áo thể thao, gym, yoga');

-- ========================================
-- BẢNG SẢN PHẨM  
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
    size VARCHAR(50),
    color VARCHAR(50),
    brand VARCHAR(100),
    image_url VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Chỉ mục để tăng tốc tìm kiếm
CREATE INDEX idx_products_code ON products(product_code);
CREATE INDEX idx_products_name ON products(name);
CREATE INDEX idx_products_category ON products(category_id);

-- Dữ liệu mẫu products
INSERT INTO products (product_code, name, category_id, description, cost_price, selling_price, stock_quantity, min_stock_level, size, color, brand) VALUES
('SP001', 'Áo Thun Basic Nam', 1, 'Áo thun cotton 100% thoáng mát', 80000, 150000, 50, 10, 'M,L,XL', 'Trắng', 'Local Brand'),
('SP002', 'Áo Sơ Mi Công Sở Nam', 2, 'Áo sơ mi cotton premium', 150000, 280000, 30, 5, 'M,L,XL', 'Xanh Navy', 'Business Line'),
('SP003', 'Quần Jean Skinny Nữ', 3, 'Quần jean co giãn, ôm dáng', 200000, 350000, 25, 5, 'S,M,L', 'Xanh Đậm', 'Denim Co'),
('SP004', 'Váy Đầm Dự Tiệc', 5, 'Váy đầm lụa cao cấp', 300000, 550000, 15, 3, 'S,M,L', 'Đỏ', 'Elegant'),
('SP005', 'Áo Khoác Blazer Nữ', 6, 'Blazer công sở sang trọng', 400000, 750000, 12, 3, 'S,M,L', 'Đen', 'Professional'),
('SP006', 'Quần Tây Nam Slimfit', 4, 'Quần tây công sở cao cấp', 250000, 450000, 20, 5, 'M,L,XL', 'Xám', 'Formal Wear'),
('SP007', 'Áo Thun Nữ Form Rộng', 1, 'Áo thun cotton oversized', 75000, 140000, 40, 8, 'S,M,L', 'Hồng', 'Casual Style'),
('SP008', 'Túi Xách Da Nữ', 7, 'Túi xách da thật cao cấp', 500000, 980000, 8, 2, 'One Size', 'Nâu', 'Luxury Bags'),
('SP009', 'Giày Sneaker Unisex', 7, 'Giày thể thao đa năng', 350000, 650000, 18, 5, '38,39,40,41,42', 'Trắng', 'Sport Life'),
('SP010', 'Đầm Maxi Boho', 5, 'Đầm dài họa tiết boho', 180000, 320000, 22, 5, 'S,M,L', 'Họa tiết', 'Boho Chic');

-- ========================================
-- BẢNG BÁN HÀNG (HÓA ĐƠN)
-- ========================================
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(15),
    sale_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    discount_amount DECIMAL(12,2) DEFAULT 0,
    total_amount DECIMAL(12,2) NOT NULL,
    payment_method ENUM('Tiền mặt', 'Chuyển khoản', 'Thẻ') DEFAULT 'Tiền mặt',
    payment_status ENUM('Đã thanh toán', 'Chưa thanh toán') DEFAULT 'Đã thanh toán',
    notes TEXT,
    served_by VARCHAR(100) DEFAULT 'Admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Chỉ mục
CREATE INDEX idx_sales_invoice ON sales(invoice_number);
CREATE INDEX idx_sales_customer ON sales(customer_phone);
CREATE INDEX idx_sales_date ON sales(sale_date);

-- ========================================
-- BẢNG CHI TIẾT BÁN HÀNG
-- ========================================
CREATE TABLE sale_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    line_total DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ========================================
-- VIEW: TỔNG QUAN BÁN HÀNG
-- ========================================
CREATE OR REPLACE VIEW sales_overview AS
SELECT 
    s.id,
    s.invoice_number,
    s.customer_name,
    s.customer_phone,
    s.sale_date,
    s.total_amount,
    s.payment_method,
    s.payment_status,
    COUNT(sd.id) as total_items,
    SUM(sd.quantity) as total_quantity
FROM sales s
LEFT JOIN sale_details sd ON s.id = sd.sale_id
GROUP BY s.id
ORDER BY s.sale_date DESC;

-- ========================================
-- VIEW: SẢN PHẨM VỚI THÔNG TIN CATEGORY
-- ========================================
CREATE OR REPLACE VIEW products_with_category AS
SELECT 
    p.*,
    c.name as category_name,
    CASE 
        WHEN p.stock_quantity <= 0 THEN 'Hết hàng'
        WHEN p.stock_quantity <= p.min_stock_level THEN 'Sắp hết'
        ELSE 'Còn hàng'
    END as stock_status
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
WHERE p.is_active = TRUE;

-- ========================================
-- TRIGGER: TỰ ĐỘNG TẠO MÃ SẢN PHẨM
-- ========================================
DELIMITER //
CREATE TRIGGER auto_product_code
    BEFORE INSERT ON products
    FOR EACH ROW
BEGIN
    IF NEW.product_code IS NULL OR NEW.product_code = '' THEN
        SET NEW.product_code = CONCAT('SP', LPAD((SELECT COALESCE(MAX(CAST(SUBSTRING(product_code, 3) AS UNSIGNED)), 0) + 1 FROM products), 3, '0'));
    END IF;
END//
DELIMITER ;

-- ========================================
-- TRIGGER: TỰ ĐỘNG TẠO SỐ HÓA ĐƠN
-- ========================================
DELIMITER //
CREATE TRIGGER auto_invoice_number
    BEFORE INSERT ON sales
    FOR EACH ROW
BEGIN
    IF NEW.invoice_number IS NULL OR NEW.invoice_number = '' THEN
        SET NEW.invoice_number = CONCAT('HD', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD((SELECT COUNT(*) + 1 FROM sales WHERE DATE(sale_date) = CURDATE()), 3, '0'));
    END IF;
END//
DELIMITER ;

-- ========================================
-- TRIGGER: CẬP NHẬT TỒN KHO KHI BÁN
-- ========================================
DELIMITER //
CREATE TRIGGER update_stock_after_sale
    AFTER INSERT ON sale_details
    FOR EACH ROW
BEGIN
    UPDATE products 
    SET stock_quantity = stock_quantity - NEW.quantity,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.product_id;
END//
DELIMITER ;

-- ========================================
-- DỊCH MẪU BÁN HÀNG
-- ========================================
INSERT INTO sales (customer_name, customer_phone, subtotal, discount_percent, discount_amount, total_amount, payment_method, notes) VALUES
('Nguyễn Văn An', '0901234567', 430000, 5, 21500, 408500, 'Tiền mặt', 'Khách hàng VIP'),
('Trần Thị Bình', '0912345678', 750000, 10, 75000, 675000, 'Chuyển khoản', 'Mua nhiều, giảm giá'),
('Lê Minh Cường', '0923456789', 150000, 0, 0, 150000, 'Tiền mặt', ''),
('Phạm Thu Dung', '0934567890', 900000, 8, 72000, 828000, 'Thẻ', 'Khách hàng thân thiết');

-- Chi tiết hóa đơn mẫu
INSERT INTO sale_details (sale_id, product_id, product_name, quantity, unit_price, line_total) VALUES
-- Hóa đơn 1
(1, 1, 'Áo Thun Basic Nam', 2, 150000, 300000),
(1, 7, 'Áo Thun Nữ Form Rộng', 1, 140000, 140000),
-- Hóa đơn 2  
(2, 4, 'Váy Đầm Dự Tiệc', 1, 550000, 550000),
(2, 8, 'Túi Xách Da Nữ', 1, 200000, 200000),
-- Hóa đơn 3
(3, 1, 'Áo Thun Basic Nam', 1, 150000, 150000),
-- Hóa đơn 4
(4, 5, 'Áo Khoác Blazer Nữ', 1, 750000, 750000),
(4, 6, 'Quần Tây Nam Slimfit', 1, 150000, 150000);

-- ========================================
-- HIỂN thị kết quả
-- ========================================
SELECT 'Database created successfully!' as message;
SELECT COUNT(*) as total_categories FROM categories;
SELECT COUNT(*) as total_products FROM products;
SELECT COUNT(*) as total_sales FROM sales;

-- Hiển thị tình trạng tồn kho
SELECT 
    product_code,
    name,
    stock_quantity,
    CASE 
        WHEN stock_quantity <= 0 THEN 'Hết hàng' 
        WHEN stock_quantity <= min_stock_level THEN 'Sắp hết'
        ELSE 'Còn hàng'
    END as status
FROM products 
ORDER BY stock_quantity ASC;
