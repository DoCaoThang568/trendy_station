# 📚 HƯỚNG DẪN - TRENDY STATION
---

## 🚀 CÀI ĐẶT TỪ ĐẦU

### Bước 1: Chuẩn bị môi trường
```bash
✅ Cài XAMPP: https://www.apachefriends.org/
✅ Start Apache + MySQL trong XAMPP Control Panel
✅ Kiểm tra: http://localhost (hiện trang XAMPP)
✅ Kiểm tra: http://localhost/phpmyadmin (hiện phpMyAdmin)
```

### Bước 2: Copy project
```bash
# Copy toàn bộ thư mục th_pttkht vào:
C:\xampp\htdocs\trendy_station
```

### Bước 3: Setup database (CÁCH TỰ ĐỘNG - KHUYẾN NGHỊ)
```bash
# Mở terminal/cmd tại thư mục project
cd C:\xampp\htdocs\trendy_station
php setup.php

# Nhập thông tin:
Database Host [localhost]: localhost
Database Username [root]: root  
Database Password []: (nhấn Enter)
Database Name [trendy_station]: trendy_station
```

### Bước 4: Kiểm tra kết quả
```bash
✅ Truy cập: http://localhost/trendy_station
✅ Thấy giao diện dashboard với menu bên trái
✅ Test phím tắt: Alt+1, Alt+2, Alt+3...
✅ Test chức năng thêm sản phẩm, bán hàng
```

### Bước 5: Setup database thủ công (nếu bước 3 lỗi)
```bash
1. Vào phpMyAdmin: http://localhost/phpmyadmin
2. Tạo database mới: "trendy_station"
3. Import file: database.sql
4. Copy config\database_sample.php → config\database.php
5. Sửa thông tin kết nối trong database.php
```

---

## 🔄 UPDATE CODE MỚI

### 🛡️ BACKUP TRƯỚC KHI UPDATE (BẮT BUỘC!)
```bash
# 1. Backup Database
- Vào phpMyAdmin → chọn "trendy_station" → Export → Go

# 2. Backup file config (nếu đã chỉnh sửa)
- Copy: C:\xampp\htdocs\trendy_station\config\database.php

# 3. Backup uploads (nếu có file đã upload)
- Copy: C:\xampp\htdocs\trendy_station\uploads\
```

### 🚀 Cách update an toàn
```bash
# PHƯƠNG ÁN A: Copy thủ công (Khuyến nghị)
1. Copy từ th_pttkht các file:
   ✅ pages\*.php           → Copy all
   ✅ assets\css\style.css  → Copy
   ✅ assets\js\script.js   → Copy  
   ✅ ajax\*.php           → Copy all
   ✅ includes\*.php       → Copy all
   ✅ index.php            → Copy
   ✅ print_*.php          → Copy all

2. KHÔNG copy:
   ❌ config\database.php  → Giữ nguyên file cũ
   ❌ uploads\*           → Giữ nguyên thư mục cũ

# PHƯƠNG ÁN B: Copy toàn bộ rồi restore
1. Rename: trendy_station → trendy_station_backup
2. Copy: th_pttkht → trendy_station
3. Restore: database.php và uploads\ từ backup
4. Test hoạt động
```

### 🔍 Kiểm tra sau update
```bash
✅ Website vẫn hoạt động: http://localhost/trendy_station
✅ Database vẫn kết nối được
✅ Phím tắt vẫn work: Alt+1,2,3,4...
✅ Data cũ vẫn còn (sản phẩm, đơn hàng...)
```

---

## ⌨️ PHÍM TẮT

### 📋 Danh sách phím tắt
```bash
Alt + 1  →  Dashboard (Trang chủ)
Alt + 2  →  Quản lý sản phẩm  
Alt + 3  →  Bán hàng
Alt + 4  →  Nhập hàng
Alt + 5  →  Khách hàng
Alt + 6  →  Trả hàng
Alt + 7  →  Báo cáo
```

### 🔧 Xử lý lỗi phím tắt
```bash
# Nếu phím tắt không hoạt động:

1. Kiểm tra browser:
   ✅ Chrome/Edge: Support tốt
   ❌ Firefox: Có thể conflict với phím tắt hệ thống

2. Test bằng file riêng:
   - Mở: http://localhost/trendy_station/test_shortcuts.html
   - Nhấn Alt+1,2,3... để test
   - Xem console log (F12)

3. Check browser settings:
   - Tắt extensions có thể conflict
   - Reset browser về mặc định

4. Fallback solution:
   - Dùng chuột click menu bên trái
   - Hoặc type URL trực tiếp
```

### 🧪 Test phím tắt
```bash
# Các bước test:
1. Mở http://localhost/trendy_station
2. Nhấn F12 → Console tab
3. Nhấn Alt+1 → Thấy log: "Shortcut Alt+1 triggered"
4. Trang chuyển về Dashboard
5. Thử tiếp Alt+2,3,4... 

# Kết quả mong đợi:
✅ Mỗi phím tắt chuyển đúng trang
✅ Console hiện log debug
✅ Không có lỗi JavaScript
```

---

## 🧪 TEST CASES

### 📊 Test Dashboard
```bash
✅ Hiển thị đúng số liệu thống kê
✅ Biểu đồ doanh thu theo tháng
✅ Top sản phẩm bán chạy
✅ Giao diện responsive
```

### 📦 Test Quản lý sản phẩm
```bash
✅ Xem danh sách sản phẩm
✅ Thêm sản phẩm mới (có validation)
✅ Sửa thông tin sản phẩm
✅ Xóa sản phẩm (có confirm)
✅ Search + Filter theo danh mục
✅ Pagination khi nhiều sản phẩm
```

### 💰 Test Bán hàng
```bash
✅ Chọn sản phẩm từ dropdown
✅ Thêm nhiều sản phẩm vào hóa đơn
✅ Tính tổng tiền tự động
✅ Áp dụng giảm giá
✅ Chọn khách hàng
✅ In hóa đơn (PDF)
✅ Lưu hóa đơn vào database
```

### 📥 Test Nhập hàng
```bash
✅ Tạo phiếu nhập mới
✅ Chọn sản phẩm + số lượng
✅ Ghi chú nhà cung cấp
✅ Tính tổng tiền nhập
✅ In phiếu nhập
✅ Cập nhật tồn kho tự động
```

### 👥 Test Khách hàng
```bash
✅ Thêm khách hàng mới
✅ Xem lịch sử mua hàng
✅ Sửa thông tin khách hàng
✅ Search khách hàng
```

### 🔄 Test Trả hàng
```bash
✅ Chọn hóa đơn gốc
✅ Chọn sản phẩm trả + số lượng
✅ Ghi lý do trả hàng
✅ Tính tiền hoàn trả
✅ Cập nhật tồn kho
```

### 📈 Test Báo cáo
```bash
✅ Báo cáo doanh thu theo ngày/tháng
✅ Báo cáo tồn kho
✅ Báo cáo khách hàng
✅ Export Excel/PDF
```

### ⌨️ Test Phím tắt
```bash
✅ Alt+1: Chuyển Dashboard
✅ Alt+2: Chuyển Sản phẩm  
✅ Alt+3: Chuyển Bán hàng
✅ Alt+4: Chuyển Nhập hàng
✅ Alt+5: Chuyển Khách hàng
✅ Alt+6: Chuyển Trả hàng
✅ Alt+7: Chuyển Báo cáo
✅ Hoạt động trên Chrome/Edge
✅ Hiển thị debug log trong console
```

---

## 🐛 XỬ LÝ LỖI

### ❌ Lỗi kết nối database
```bash
Error: Connection failed: Access denied for user 'root'@'localhost'

Giải pháp:
1. Kiểm tra XAMPP: Apache + MySQL đã start?
2. Check file config\database.php:
   - $host = 'localhost';
   - $username = 'root';
   - $password = ''; (để trống nếu XAMPP mặc định)
3. Test kết nối: php -r "new PDO('mysql:host=localhost', 'root', '');"
```

### ❌ Lỗi import database
```bash
Error: SQL syntax error...

Giải pháp:
1. Dùng file database.sql đã fix (không có DELIMITER)
2. Import qua phpMyAdmin thay vì command line
3. Hoặc chạy setup.php để import tự động
```

### ❌ Lỗi phím tắt không hoạt động
```bash
Giải pháp:
1. Test trên Chrome/Edge thay vì Firefox
2. Tắt extensions browser
3. Check console F12 có lỗi JavaScript không
4. Test bằng file test_shortcuts.html
```

### ❌ Lỗi 404 Not Found
```bash
Error: http://localhost/trendy_station/pages/products.php → 404

Giải pháp:
1. Kiểm tra file .htaccess có tồn tại không
2. Kiểm tra cấu trúc thư mục đúng chưa
3. Test truy cập trực tiếp: http://localhost/trendy_station/index.php?page=products
```

### ❌ Lỗi upload file/in hóa đơn
```bash
Error: Permission denied...

Giải pháp:
1. Tạo thư mục uploads\ với quyền write
2. Check PHP extensions: php_gd, php_zip enabled
3. Tăng upload_max_filesize trong php.ini
```

---

## 📤 NỘP BÀI

### 📁 Cấu trúc nộp bài
```bash
TenSinhVien_MSSV_TrendyStation.zip
├── source_code\           (Copy toàn bộ project)
├── database\
│   ├── database.sql       (File SQL để import)
│   └── sample_data.sql    (Dữ liệu test nếu có)
├── documentation\
│   ├── STUDENT_GUIDE.md   (File này)
│   ├── screenshots\       (Ảnh chụp màn hình)
│   └── demo_video.mp4     (Video demo nếu yêu cầu)
└── README.txt             (Hướng dẫn ngắn gọn)
```

### 📸 Screenshots cần có
```bash
✅ Trang Dashboard với đầy đủ thống kê
✅ Danh sách sản phẩm với data mẫu
✅ Giao diện bán hàng + hóa đơn đã tạo
✅ Báo cáo doanh thu 
✅ Database trong phpMyAdmin
✅ Console log phím tắt (F12)
```

### ✅ Checklist
```bash
□ Code chạy được trên máy khác (test trên máy khác)
□ Database import thành công
□ Tất cả chức năng hoạt động
□ Phím tắt work (Alt+1,2,3...)
□ Không có lỗi PHP/JavaScript
□ Screenshots đầy đủ
□ Documentation đầy đủ
```