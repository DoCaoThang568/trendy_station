-- ========================================
-- DATABASE UPDATE: KHÁCH HÀNG (CUSTOMERS)
-- Bảng customers + dữ liệu mẫu
-- ========================================

USE trendy_station;

-- Tạo bảng customers nếu chưa có
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(15) UNIQUE,
    email VARCHAR(100),
    address TEXT,
    gender ENUM('Nam', 'Nữ', 'Khác') DEFAULT 'Khác',
    birth_date DATE NULL,
    membership_level ENUM('Thông thường', 'VIP', 'VVIP') DEFAULT 'Thông thường',
    total_spent DECIMAL(12,2) DEFAULT 0,
    total_orders INT DEFAULT 0,
    last_order_date DATE NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Thêm chỉ mục để tăng tốc tìm kiếm
CREATE INDEX idx_customers_phone ON customers(phone);
CREATE INDEX idx_customers_name ON customers(name);
CREATE INDEX idx_customers_code ON customers(customer_code);

-- Dữ liệu mẫu cho bảng customers
INSERT INTO customers (customer_code, name, phone, email, address, gender, birth_date, membership_level, total_spent, total_orders, last_order_date, notes) VALUES
('KH001', 'Nguyễn Văn An', '0901234567', 'an.nguyen@email.com', '123 Đường Lê Lợi, Q1, TP.HCM', 'Nam', '1990-05-15', 'VIP', 2500000, 8, '2024-01-15', 'Khách hàng thân thiết, thường mua đồ nam'),
('KH002', 'Trần Thị Bình', '0912345678', 'binh.tran@email.com', '456 Đường Nguyễn Huệ, Q1, TP.HCM', 'Nữ', '1992-08-22', 'VVIP', 5200000, 15, '2024-01-20', 'VIP khách hàng, ưa thích thời trang cao cấp'),
('KH003', 'Lê Minh Cường', '0923456789', 'cuong.le@email.com', '789 Đường Hai Bà Trưng, Q3, TP.HCM', 'Nam', '1988-03-10', 'Thông thường', 850000, 3, '2024-01-10', ''),
('KH004', 'Phạm Thu Dung', '0934567890', 'dung.pham@email.com', '321 Đường Pasteur, Q3, TP.HCM', 'Nữ', '1995-12-05', 'VIP', 1800000, 6, '2024-01-18', 'Thích mua đồ trẻ trung, hiện đại'),
('KH005', 'Hoàng Văn Em', '0945678901', 'em.hoang@email.com', '654 Đường Cống Quỳnh, Q1, TP.HCM', 'Nam', '1993-07-18', 'Thông thường', 650000, 2, '2024-01-12', ''),
('KH006', 'Vũ Thị Phương', '0956789012', 'phuong.vu@email.com', '987 Đường Võ Văn Tần, Q3, TP.HCM', 'Nữ', '1991-11-30', 'VIP', 2100000, 7, '2024-01-16', 'Khách hàng ổn định, thường order online'),
('KH007', 'Đặng Quốc Gia', '0967890123', 'gia.dang@email.com', '147 Đường Điện Biên Phủ, Q1, TP.HCM', 'Nam', '1987-04-25', 'Thông thường', 420000, 1, '2024-01-08', 'Khách hàng mới'),
('KH008', 'Bùi Thị Hạnh', '0978901234', 'hanh.bui@email.com', '258 Đường Nam Kỳ Khởi Nghĩa, Q3, TP.HCM', 'Nữ', '1994-09-14', 'VIP', 3200000, 11, '2024-01-19', 'Khách VIP, ưa thích thương hiệu cao cấp'),
('KH009', 'Ngô Văn Ích', '0989012345', 'ich.ngo@email.com', '369 Đường Lý Tự Trọng, Q1, TP.HCM', 'Nam', '1989-06-03', 'Thông thường', 780000, 3, '2024-01-14', ''),
('KH010', 'Lý Thị Kim', '0990123456', 'kim.ly@email.com', '741 Đường Trần Hưng Đạo, Q1, TP.HCM', 'Nữ', '1996-01-20', 'VVIP', 4500000, 13, '2024-01-21', 'Khách VIP cao cấp, thường mua số lượng lớn');

-- View: Thống kê khách hàng theo membership
CREATE OR REPLACE VIEW customer_stats AS
SELECT 
    membership_level,
    COUNT(*) as total_customers,
    AVG(total_spent) as avg_spent,
    SUM(total_spent) as total_revenue,
    AVG(total_orders) as avg_orders
FROM customers 
WHERE status = 'active'
GROUP BY membership_level;

-- View: Top khách hàng theo doanh số
CREATE OR REPLACE VIEW top_customers AS
SELECT 
    customer_code,
    name,
    phone,
    membership_level,
    total_spent,
    total_orders,
    last_order_date,
    CASE 
        WHEN total_spent >= 5000000 THEN 'VVIP'
        WHEN total_spent >= 2000000 THEN 'VIP'
        ELSE 'Thông thường'
    END as suggested_level
FROM customers 
WHERE status = 'active'
ORDER BY total_spent DESC;

-- Trigger: Tự động cập nhật membership level dựa trên tổng chi tiêu
DELIMITER //
CREATE TRIGGER update_customer_membership
    BEFORE UPDATE ON customers
    FOR EACH ROW
BEGIN
    IF NEW.total_spent >= 5000000 THEN
        SET NEW.membership_level = 'VVIP';
    ELSEIF NEW.total_spent >= 2000000 THEN
        SET NEW.membership_level = 'VIP';
    ELSE
        SET NEW.membership_level = 'Thông thường';
    END IF;
END//
DELIMITER ;

-- Trigger: Tự động tạo mã khách hàng
DELIMITER //
CREATE TRIGGER auto_customer_code
    BEFORE INSERT ON customers
    FOR EACH ROW
BEGIN
    IF NEW.customer_code IS NULL OR NEW.customer_code = '' THEN
        SET NEW.customer_code = CONCAT('KH', LPAD((SELECT COALESCE(MAX(CAST(SUBSTRING(customer_code, 3) AS UNSIGNED)), 0) + 1 FROM customers), 3, '0'));
    END IF;
END//
DELIMITER ;

-- Hiển thị thông tin đã tạo
SELECT 'Customers table created successfully!' as message;
SELECT COUNT(*) as total_customers FROM customers;
SELECT * FROM customer_stats;
