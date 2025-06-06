-- Database schema for returns functionality
-- Add this to your existing database

-- Returns table
CREATE TABLE IF NOT EXISTS returns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    reason VARCHAR(255) NOT NULL,
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    status ENUM('pending', 'processed', 'refunded') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    INDEX idx_returns_sale_id (sale_id),
    INDEX idx_returns_created_at (created_at)
);

-- Return details table
CREATE TABLE IF NOT EXISTS return_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    return_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (return_id) REFERENCES returns(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_return_details_return_id (return_id),
    INDEX idx_return_details_product_id (product_id)
);

-- Add purchase_price column to products table if not exists
ALTER TABLE products 
ADD COLUMN IF NOT EXISTS purchase_price DECIMAL(10,2) DEFAULT 0 AFTER price;

-- Update existing products with estimated purchase price (70% of selling price)
UPDATE products 
SET purchase_price = ROUND(price * 0.7, 0) 
WHERE purchase_price = 0 OR purchase_price IS NULL;

-- Trigger to update return total when details change
DELIMITER //
CREATE TRIGGER IF NOT EXISTS tr_return_details_update_total 
    AFTER INSERT ON return_details
    FOR EACH ROW
BEGIN
    UPDATE returns 
    SET total_amount = (
        SELECT COALESCE(SUM(total_price), 0)
        FROM return_details 
        WHERE return_id = NEW.return_id
    )
    WHERE id = NEW.return_id;
END//

CREATE TRIGGER IF NOT EXISTS tr_return_details_update_total_on_delete
    AFTER DELETE ON return_details
    FOR EACH ROW
BEGIN
    UPDATE returns 
    SET total_amount = (
        SELECT COALESCE(SUM(total_price), 0)
        FROM return_details 
        WHERE return_id = OLD.return_id
    )
    WHERE id = OLD.return_id;
END//
DELIMITER ;

-- Create view for return summary
CREATE OR REPLACE VIEW return_summary AS
SELECT 
    r.id,
    r.sale_id,
    s.invoice_code,
    c.name as customer_name,
    c.phone as customer_phone,
    r.reason,
    r.total_amount,
    r.status,
    r.created_at,
    COUNT(rd.id) as item_count,
    SUM(rd.quantity) as total_quantity
FROM returns r
LEFT JOIN sales s ON r.sale_id = s.id
LEFT JOIN customers c ON s.customer_id = c.id
LEFT JOIN return_details rd ON r.id = rd.return_id
GROUP BY r.id, r.sale_id, s.invoice_code, c.name, c.phone, r.reason, r.total_amount, r.status, r.created_at;

-- Sample data (uncomment if needed)
/*
INSERT INTO returns (sale_id, reason, total_amount, status) VALUES
(1, 'Sản phẩm lỗi', 150000, 'processed'),
(2, 'Không vừa size', 200000, 'pending');

INSERT INTO return_details (return_id, product_id, quantity, unit_price, total_price) VALUES
(1, 1, 1, 150000, 150000),
(2, 2, 1, 200000, 200000);
*/
