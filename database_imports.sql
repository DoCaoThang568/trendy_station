-- ===================================
-- TẠO BẢNG CHO TÍNH NĂNG NHẬP HÀNG
-- ===================================

-- 1. Bảng Nhà cung cấp (Suppliers)
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(255),
    address TEXT,
    contact_person VARCHAR(255),
    tax_code VARCHAR(50),
    status ENUM('active', 'inactive') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Bảng Phiếu nhập hàng (Imports)
CREATE TABLE IF NOT EXISTS imports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    import_code VARCHAR(20) UNIQUE NOT NULL,
    supplier_id INT,
    supplier_name VARCHAR(255) NOT NULL,
    supplier_phone VARCHAR(20),
    total_amount DECIMAL(15,2) DEFAULT 0,
    payment_status ENUM('pending', 'partial', 'paid') DEFAULT 'pending',
    import_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    created_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
);

-- 3. Bảng Chi tiết phiếu nhập (Import Details)
CREATE TABLE IF NOT EXISTS import_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    import_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    unit_cost DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (import_id) REFERENCES imports(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ===================================
-- THÊM DỮ LIỆU MẪU
-- ===================================

-- Thêm nhà cung cấp mẫu
INSERT INTO suppliers (code, name, phone, email, address, contact_person, status) VALUES
('NCC001', 'Công ty TNHH Thời Trang Việt', '0901234567', 'sales@thoitrangviet.vn', '123 Nguyễn Huệ, Q.1, TP.HCM', 'Nguyễn Văn A', 'active'),
('NCC002', 'Fashion Import Co., Ltd', '0907654321', 'import@fashionco.vn', '456 Lê Lợi, Q.1, TP.HCM', 'Trần Thị B', 'active'),
('NCC003', 'Xưởng May Đông Á', '0912345678', 'dongamay@gmail.com', '789 Phan Xích Long, Q.PN, TP.HCM', 'Lê Văn C', 'active'),
('NCC004', 'Nhà phân phối Adidas Việt Nam', '0938765432', 'adidas@vietnam.vn', '321 Trần Hưng Đạo, Q.1, TP.HCM', 'Phạm Thị D', 'active'),
('NCC005', 'Nike Distribution Center', '0945123789', 'nike@center.vn', '654 Hai Bà Trưng, Q.3, TP.HCM', 'Hoàng Văn E', 'active');

-- Thêm một vài phiếu nhập mẫu
INSERT INTO imports (import_code, supplier_id, supplier_name, supplier_phone, total_amount, payment_status, notes, created_by) VALUES
('NH001', 1, 'Công ty TNHH Thời Trang Việt', '0901234567', 15000000, 'paid', 'Nhập hàng đầu mùa thu', 'admin'),
('NH002', 2, 'Fashion Import Co., Ltd', '0907654321', 8500000, 'partial', 'Nhập bổ sung hàng bán chạy', 'admin'),
('NH003', 3, 'Xưởng May Đông Á', '0912345678', 12000000, 'pending', 'Nhập hàng mới cho mùa đông', 'admin');

-- Thêm chi tiết phiếu nhập mẫu (giả sử đã có sản phẩm với id 1-10)
INSERT INTO import_details (import_id, product_id, product_name, quantity, unit_cost) VALUES
-- Phiếu NH001
(1, 1, 'Áo Thun Nam Basic', 50, 120000),
(1, 2, 'Quần Jean Nam Slim', 30, 180000),
(1, 3, 'Áo Sơ Mi Nữ Trắng', 40, 150000),

-- Phiếu NH002  
(2, 4, 'Váy Maxi Hoa', 25, 200000),
(2, 5, 'Áo Khoác Bomber', 20, 250000),

-- Phiếu NH003
(3, 6, 'Quần Jogger Unisex', 60, 140000),
(3, 7, 'Áo Hoodie Oversize', 35, 220000),
(3, 8, 'Giày Sneaker Canvas', 40, 180000);

-- ===================================
-- CẬP NHẬT STOCK CHO SẢN PHẨM
-- ===================================

-- Cập nhật số lượng tồn kho cho các sản phẩm đã nhập
UPDATE products SET stock_quantity = stock_quantity + 50, import_price = 120000 WHERE id = 1;
UPDATE products SET stock_quantity = stock_quantity + 30, import_price = 180000 WHERE id = 2;
UPDATE products SET stock_quantity = stock_quantity + 40, import_price = 150000 WHERE id = 3;
UPDATE products SET stock_quantity = stock_quantity + 25, import_price = 200000 WHERE id = 4;
UPDATE products SET stock_quantity = stock_quantity + 20, import_price = 250000 WHERE id = 5;
UPDATE products SET stock_quantity = stock_quantity + 60, import_price = 140000 WHERE id = 6;
UPDATE products SET stock_quantity = stock_quantity + 35, import_price = 220000 WHERE id = 7;
UPDATE products SET stock_quantity = stock_quantity + 40, import_price = 180000 WHERE id = 8;

-- ===================================
-- THÊM INDEXES ĐỂ TỐI ƯU HIỆU SUẤT
-- ===================================

-- Index cho tìm kiếm nhanh
CREATE INDEX idx_suppliers_name ON suppliers(name);
CREATE INDEX idx_suppliers_code ON suppliers(code);
CREATE INDEX idx_imports_code ON imports(import_code);
CREATE INDEX idx_imports_date ON imports(import_date);
CREATE INDEX idx_imports_supplier ON imports(supplier_id);
CREATE INDEX idx_import_details_import ON import_details(import_id);
CREATE INDEX idx_import_details_product ON import_details(product_id);

-- ===================================
-- VIEW BÁO CÁO NHẬP HÀNG
-- ===================================

-- View tổng hợp nhập hàng theo nhà cung cấp
CREATE OR REPLACE VIEW supplier_import_summary AS
SELECT 
    s.id as supplier_id,
    s.code as supplier_code,
    s.name as supplier_name,
    COUNT(i.id) as total_imports,
    SUM(i.total_amount) as total_amount,
    SUM(CASE WHEN i.payment_status = 'paid' THEN i.total_amount ELSE 0 END) as paid_amount,
    SUM(CASE WHEN i.payment_status = 'pending' THEN i.total_amount ELSE 0 END) as pending_amount,
    AVG(i.total_amount) as avg_import_amount,
    MAX(i.import_date) as last_import_date,
    MIN(i.import_date) as first_import_date
FROM suppliers s
LEFT JOIN imports i ON s.id = i.supplier_id
WHERE s.status = 'active'
GROUP BY s.id, s.code, s.name
ORDER BY total_amount DESC;

-- View chi tiết sản phẩm nhập gần đây
CREATE OR REPLACE VIEW recent_imports_detail AS
SELECT 
    i.import_code,
    i.import_date,
    i.supplier_name,
    i.payment_status,
    id.product_name,
    id.quantity,
    id.unit_cost,
    (id.quantity * id.unit_cost) as line_total,
    p.code as product_code,
    p.selling_price,
    (p.selling_price - id.unit_cost) as profit_margin
FROM imports i
JOIN import_details id ON i.id = id.import_id
LEFT JOIN products p ON id.product_id = p.id
ORDER BY i.import_date DESC, i.id DESC;

-- ===================================
-- TRIGGER CẬP NHẬT TỰ ĐỘNG
-- ===================================

-- Trigger cập nhật tổng tiền phiếu nhập khi thêm/sửa/xóa chi tiết
DELIMITER //

CREATE TRIGGER update_import_total_after_detail_insert
AFTER INSERT ON import_details
FOR EACH ROW
BEGIN
    UPDATE imports 
    SET total_amount = (
        SELECT SUM(quantity * unit_cost) 
        FROM import_details 
        WHERE import_id = NEW.import_id
    )
    WHERE id = NEW.import_id;
END//

CREATE TRIGGER update_import_total_after_detail_update
AFTER UPDATE ON import_details
FOR EACH ROW
BEGIN
    UPDATE imports 
    SET total_amount = (
        SELECT SUM(quantity * unit_cost) 
        FROM import_details 
        WHERE import_id = NEW.import_id
    )
    WHERE id = NEW.import_id;
END//

CREATE TRIGGER update_import_total_after_detail_delete
AFTER DELETE ON import_details
FOR EACH ROW
BEGIN
    UPDATE imports 
    SET total_amount = (
        SELECT COALESCE(SUM(quantity * unit_cost), 0) 
        FROM import_details 
        WHERE import_id = OLD.import_id
    )
    WHERE id = OLD.import_id;
END//

DELIMITER ;

-- ===================================
-- STORED PROCEDURES
-- ===================================

-- Procedure lấy báo cáo nhập hàng theo khoảng thời gian
DELIMITER //

CREATE PROCEDURE GetImportReport(
    IN start_date DATE,
    IN end_date DATE,
    IN supplier_id_filter INT
)
BEGIN
    SELECT 
        i.import_code,
        i.import_date,
        s.name as supplier_name,
        i.total_amount,
        i.payment_status,
        COUNT(id.id) as total_items,
        SUM(id.quantity) as total_quantity
    FROM imports i
    LEFT JOIN suppliers s ON i.supplier_id = s.id
    LEFT JOIN import_details id ON i.id = id.import_id
    WHERE DATE(i.import_date) BETWEEN start_date AND end_date
    AND (supplier_id_filter IS NULL OR i.supplier_id = supplier_id_filter)
    GROUP BY i.id
    ORDER BY i.import_date DESC;
END//

DELIMITER ;

-- ===================================
-- HOÀN THÀNH THIẾT LẬP DATABASE
-- ===================================

SELECT 'Database setup for Imports module completed successfully!' as status;
