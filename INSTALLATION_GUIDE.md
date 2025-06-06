# 🚀 HƯỚNG DẪN CÀI ĐẶT & SỬ DỤNG - THE TRENDY STATION

## 📋 YÊU CẦU HỆ THỐNG

### 💻 Phần mềm cần thiết:
- **XAMPP** (Apache + MySQL + PHP 7.4+) 
- **Web Browser** (Chrome, Firefox, Edge...)
- **Text Editor** (VS Code, Sublime Text...)

---

## ⚡ HƯỚNG DẪN CÀI ĐẶT NHANH

### 1️⃣ **Cài đặt XAMPP:**
```bash
# Tải XAMPP từ https://www.apachefriends.org/
# Cài đặt vào thư mục mặc định: C:\xampp\
# Khởi động Apache và MySQL từ XAMPP Control Panel
```

### 2️⃣ **Thiết lập Database:**
```bash
# Truy cập: http://localhost/phpmyadmin
# Tạo database mới tên: trendy_station
# Import các file SQL theo thứ tự:
```

1. **Bước 1:** Import file `database.sql` (bảng cơ bản)
2. **Bước 2:** Import file `database_imports.sql` (bảng nhập hàng)  
3. **Bước 3:** Import file `database_customers.sql` (bảng khách hàng)

### 3️⃣ **Copy source code:**
```bash
# Copy toàn bộ thư mục project vào:
C:\xampp\htdocs\trendy_station\

# Cấu trúc thư mục:
trendy_station/
├── config/database.php
├── pages/*.php
├── assets/css|js/*
├── includes/header.php|footer.php
├── ajax/*.php
└── index.php
```

### 4️⃣ **Truy cập hệ thống:**
```
http://localhost/trendy_station/
```

---

## 🎯 CÁC TRANG CHỨC NĂNG

### 📦 **Trang Sản phẩm** (`index.php?page=products`)
- ✅ Xem danh sách sản phẩm với filter
- ✅ Thêm/sửa/xóa sản phẩm  
- ✅ Tìm kiếm theo tên, mã, danh mục
- ✅ Badge hiển thị tồn kho (đỏ/vàng/xanh)

**Phím tắt:** F1 (Thêm SP), F2 (Tìm kiếm), F3 (Thêm nhanh)

### 💰 **Trang Bán hàng** (`index.php?page=sales`)
- ✅ Lập hóa đơn bán hàng 
- ✅ Chọn khách hàng (tìm nhanh bằng SĐT)
- ✅ Thêm sản phẩm (mã SP hoặc tìm kiếm)
- ✅ Tính tổng, giảm giá tự động
- ✅ In hóa đơn chuyên nghiệp
- ✅ Xem chi tiết HĐ đã bán (AJAX modal)
- ✅ Auto-save draft mỗi 30 giây

**Phím tắt:** F2 (Tìm SP), F3 (Thêm dòng), Ctrl+Enter (Lưu HĐ), Ctrl+R (Reset)

### 📥 **Trang Nhập hàng** (`index.php?page=imports`)
- ✅ Tạo phiếu nhập hàng
- ✅ Chọn nhà cung cấp
- ✅ Thêm sản phẩm nhập với giá nhập
- ✅ Tính tổng tiền nhập
- ✅ Xóa phiếu nhập
- ✅ Cập nhật tồn kho tự động

**Phím tắt:** F1 (Tạo phiếu), F2 (Tìm SP), F3 (Thêm dòng), Ctrl+Enter (Lưu)

### 👥 **Trang Khách hàng** (`index.php?page=customers`)
- ✅ Quản lý thông tin khách hàng
- ✅ Phân loại hạng thành viên (VVIP/VIP/Thông thường)
- ✅ Theo dõi hoạt động mua hàng
- ✅ Xem chi tiết + lịch sử mua hàng (AJAX)
- ✅ Thống kê chi tiêu, sản phẩm yêu thích
- ✅ Filter theo trạng thái, hạng thành viên

**Phím tắt:** F1 (Thêm KH), F2 (Tìm kiếm), Ctrl+Enter (Lưu)

---

## 🔧 TÍNH NĂNG NỔI BẬT

### 🚀 **UX/UI Hiện đại:**
- Responsive design (mobile-friendly)
- Toast notifications
- Card layout đẹp mắt
- Loading animations
- Color-coded system

### ⌨️ **Phím tắt toàn diện:**
- F1, F2, F3 cho các thao tác chính
- Ctrl+Enter để lưu nhanh  
- Ctrl+R để reset form
- ESC để đóng modal

### 💾 **Auto-save & Validation:**
- Tự động lưu nháp (trang bán hàng)
- Validation thông minh (SĐT, email...)
- Cảnh báo tồn kho
- Preview trước khi lưu

### 📊 **AJAX & Dynamic:**
- Xem chi tiết không reload trang
- Tìm kiếm real-time
- Update UI động
- Print-friendly design

---

## 🐛 TROUBLESHOOTING

### ❌ **Lỗi kết nối database:**
```php
// Kiểm tra file config/database.php
$host = 'localhost';
$dbname = 'trendy_station'; // Tên database
$username = 'root';         // Username MySQL
$password = '';             // Password MySQL (thường để trống)
```

### ❌ **Lỗi 404 không tìm thấy trang:**
```bash
# Kiểm tra URL: http://localhost/trendy_station/
# Kiểm tra Apache đã start trong XAMPP chưa
# Kiểm tra file index.php có trong thư mục gốc không
```

### ❌ **Lỗi import database:**
```bash
# Vào phpMyAdmin → Import
# Chọn file .sql
# Encoding: utf8_general_ci
# Import từng file một theo thứ tự
```

### ❌ **CSS/JS không load:**
```bash
# Kiểm tra đường dẫn assets/css/style.css
# Kiểm tra quyền đọc file
# Refresh cache browser (Ctrl+F5)
```

---

## 📞 HỖ TRỢ

### 🔍 **Debug mode:**
```php
// Thêm vào đầu file PHP để debug:
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### 📝 **Log lỗi:**
```bash
# Kiểm tra error log tại:
C:\xampp\apache\logs\error.log
C:\xampp\mysql\data\*.err
```

### 💬 **Liên hệ hỗ trợ:**
- GitHub Issues  
- Email: support@trendystation.com
- Documentation: /docs/

---

## ✨ **TIPS FOR STUDENTS**

1. **Đọc kỹ code** để hiểu cách hoạt động
2. **Thực hành phím tắt** để thao tác nhanh  
3. **Tùy chỉnh giao diện** theo ý thích
4. **Thêm tính năng mới** để nâng cao điểm
5. **Ghi chú lại** những thay đổi quan trọng

**Happy Coding! 🚀👩‍💻👨‍💻**
