# 🗄️ HƯỚNG DẪN SETUP DATABASE TỪ ĐẦU

## 🚀 CÁCH 1: SỬ DỤNG SCRIPT TỰ ĐỘNG (KHUYẾN NGHỊ)

### Bước 1: Chạy script setup đã fix
```bash
cd C:\Users\thang\Desktop\th_pttkht
php setup_fixed.php
```

### Bước 2: Nhập thông tin database
```
Database Host [localhost]: localhost
Database Username [root]: root  
Database Password []: (nhấn Enter nếu không có password)
Database Name [trendy_station]: trendy_station
```

### Bước 3: Chờ script chạy xong
```
✅ Script sẽ tự động:
- Tạo database
- Import tất cả bảng và dữ liệu mẫu
- Tạo config/database.php
- Setup thư mục cần thiết
- Tạo .htaccess
```

---

## 🗄️ CÁCH 2: MANUAL SETUP QUA PHPMYADMIN

### Bước 1: Mở phpMyAdmin
```
🌐 Truy cập: http://localhost/phpmyadmin
👤 Username: root
🔑 Password: (để trống)
```

### Bước 2: Tạo database mới
```sql
1. Click "New" ở sidebar trái
2. Database name: trendy_station
3. Collation: utf8mb4_unicode_ci
4. Click "Create"
```

### Bước 3: Import database
```
1. Chọn database "trendy_station" vừa tạo
2. Click tab "Import"
3. Choose file: database_fixed.sql
4. Click "Go"
```

### Bước 4: Kiểm tra import thành công
```sql
-- Kiểm tra có 11 bảng:
SHOW TABLES;

-- Kiểm tra dữ liệu mẫu:
SELECT COUNT(*) FROM products;    -- Kết quả: 8
SELECT COUNT(*) FROM customers;  -- Kết quả: 4  
SELECT COUNT(*) FROM categories; -- Kết quả: 8
```

---

## ⚙️ CÁCH 3: COMMAND LINE MYSQL

### Bước 1: Mở MySQL Command Line
```bash
# Windows
mysql -u root -p

# Hoặc dùng XAMPP shell
cd C:\xampp\mysql\bin
mysql -u root -p
```

### Bước 2: Tạo và import database
```sql
-- Tạo database
CREATE DATABASE trendy_station CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Sử dụng database
USE trendy_station;

-- Import file SQL (nếu dùng command line)
SOURCE C:/Users/thang/Desktop/th_pttkht/database_fixed.sql;

-- Hoặc import từ ngoài
exit;
mysql -u root -p trendy_station < C:/Users/thang/Desktop/th_pttkht/database_fixed.sql
```

---

## 🔧 TROUBLESHOOTING

### ❌ Lỗi: "DELIMITER syntax error"
```
✅ Giải pháp: Sử dụng database_fixed.sql thay vì database.sql
❌ File cũ: database.sql (có DELIMITER)
✅ File mới: database_fixed.sql (không có DELIMITER)
```

### ❌ Lỗi: "Access denied for user 'root'"
```
✅ Giải pháp:
1. Kiểm tra XAMPP MySQL đang chạy
2. Reset MySQL password:
   - Stop MySQL in XAMPP
   - Start lại MySQL
   - Thử login không password
```

### ❌ Lỗi: "Database connection failed"
```
✅ Kiểm tra:
1. MySQL service đang chạy
2. Port 3306 không bị block
3. Username/password đúng
4. Database name đúng
```

### ❌ Lỗi: "Table doesn't exist"  
```
✅ Giải pháp:
1. Chạy lại import database_fixed.sql
2. Kiểm tra SHOW TABLES; có đủ 11 bảng không
3. Xóa database và tạo lại từ đầu
```

---

## 📊 KIỂM TRA DATABASE SETUP THÀNH CÔNG

### Test connection từ PHP:
```php
<?php
// Test file: test_db.php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=trendy_station', 'root', '');
    echo "✅ Database connection successful!\n";
    
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "📊 Tables found: " . count($tables) . "\n";
    foreach($tables as $table) {
        echo "- $table\n";
    }
} catch(PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
}
?>
```

### Test queries:
```sql
-- Test data đã có chưa
SELECT 'Products' as table_name, COUNT(*) as count FROM products
UNION ALL
SELECT 'Customers', COUNT(*) FROM customers  
UNION ALL
SELECT 'Categories', COUNT(*) FROM categories
UNION ALL
SELECT 'Sales', COUNT(*) FROM sales;
```

### Expected results:
```
Products: 8 records
Customers: 4 records  
Categories: 8 records
Sales: 3 records
```

---

## 🎯 SAU KHI SETUP XONG

### 1. Copy project files vào htdocs:
```bash
# Copy từ:
C:\Users\thang\Desktop\th_pttkht\

# Đến:  
C:\xampp\htdocs\trendy_station\

# Giữ nguyên config/database.php đã tạo
```

### 2. Test website:
```
🌐 http://localhost/trendy_station
🧪 http://localhost/trendy_station/test_shortcuts.html
```

### 3. Test keyboard shortcuts:
```
Alt+1: Dashboard
Alt+2: Products  
Alt+3: Sales
Alt+4: Imports
Alt+5: Customers
Alt+6: Returns
Alt+7: Reports
```

---

## 📞 HỖ TRỢ

### Nếu vẫn gặp lỗi:
1. **Check XAMPP Control Panel:**
   - ✅ Apache: Running
   - ✅ MySQL: Running

2. **Check ports:**
   - Apache: Port 80, 443
   - MySQL: Port 3306

3. **Check logs:**
   - `C:\xampp\apache\logs\error.log`
   - `C:\xampp\mysql\data\*.err`

4. **Restart services:**
   - Stop Apache & MySQL
   - Start lại theo thứ tự: MySQL → Apache

---

## 🎉 SETUP HOÀN TẤT!

Sau khi setup thành công:
- ✅ Database: trendy_station với 11 bảng
- ✅ Data mẫu: Products, customers, sales
- ✅ Config: database.php đã tạo
- ✅ Website: localhost/trendy_station hoạt động
- ✅ Keyboard shortcuts: Alt+1-7 working
- ✅ Test tools: test_shortcuts.html available

**🚀 Chúc mừng! Bạn đã setup thành công The Trendy Station! 🎉**
