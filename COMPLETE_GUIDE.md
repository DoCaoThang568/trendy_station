# 🎓 HƯỚNG DẪN HOÀN CHỈNH - THE TRENDY STATION
## Hệ thống quản lý shop thời trang

---

## 📋 **YÊU CẦU HỆ THỐNG**

### � **Phần mềm cần thiết:**
- **XAMPP** (Apache + MySQL + PHP 7.4+) 
- **Web Browser** (Chrome, Firefox, Edge...)
- **Text Editor** (VS Code, Sublime Text...)

### �📚 **KIẾN THỨC TIÊN QUYẾT**

#### Bắt buộc:
- **HTML/CSS:** Cơ bản về layout, styling
- **JavaScript:** Syntax cơ bản, DOM manipulation, events
- **PHP:** Variables, functions, arrays, OOP cơ bản
- **MySQL:** SELECT, INSERT, UPDATE, DELETE, JOIN

#### Nên có:
- **Bootstrap:** Framework CSS responsive
- **AJAX/jQuery:** Asynchronous requests
- **Git:** Version control cơ bản

---

## 🚀 **HƯỚNG DẪN CÀI ĐẶT CHI TIẾT**

### 1️⃣ **Cài đặt XAMPP:**
```bash
# Tải XAMPP từ https://www.apachefriends.org/
# Cài đặt vào thư mục mặc định: C:\xampp\
# Khởi động Apache và MySQL từ XAMPP Control Panel
```

### 2️⃣ **Setup Project:**
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
├── setup.php (Auto-setup script)
└── index.php
```

### 3️⃣ **Setup Database (Tự động):**
```bash
1. Truy cập: http://localhost/trendy_station/setup.php
2. Nhập thông tin database:
   - Host: localhost
   - Username: root  
   - Password: (để trống)
   - Database: trendy_station
3. Click "Setup Database" để tự động tạo bảng
```

### 4️⃣ **Setup Database (Thủ công):**
```bash
# Nếu auto-setup không hoạt động:
1. Truy cập: http://localhost/phpmyadmin
2. Tạo database: trendy_station
3. Import theo thứ tự:
   - database.sql (bảng cơ bản)
   - database_imports.sql (bảng nhập hàng)  
   - database_customers.sql (bảng khách hàng)
   - database_returns.sql (bảng trả hàng)
```

### 5️⃣ **Kiểm tra hoạt động:**
```bash
1. Truy cập: http://localhost/trendy_station
2. Kiểm tra Dashboard hiển thị đúng
3. Test các chức năng: Products, Sales, Imports, Customers
4. Kiểm tra phím tắt và AJAX
```

---

## 🐛 **TROUBLESHOOTING - XỬ LÝ LỖI**

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

---

## 🎯 **CÁC TRANG CHỨC NĂNG CHI TIẾT**

### 📦 **Trang Sản phẩm** (`index.php?page=products`)
- ✅ Xem danh sách sản phẩm với filter
- ✅ Thêm/sửa/xóa sản phẩm  
- ✅ Tìm kiếm theo tên, mã, danh mục
- ✅ Badge hiển thị tồn kho (đỏ/vàng/xanh)

**Phím tắt:** F1 (Thêm SP), F2 (Tìm kiếm), ESC (Đóng modal)

### 💰 **Trang Bán hàng** (`index.php?page=sales`)
- ✅ Lập hóa đơn bán hàng 
- ✅ Chọn khách hàng (tìm nhanh bằng SĐT)
- ✅ Thêm sản phẩm (mã SP hoặc tìm kiếm)
- ✅ Tính tổng, giảm giá tự động
- ✅ In hóa đơn chuyên nghiệp
- ✅ Xem chi tiết HĐ đã bán (AJAX modal)
- ✅ Auto-save draft mỗi 30 giây

**Phím tắt:** F2 (Tìm SP), F3 (Thêm dòng), Ctrl+Enter (Lưu HĐ), Ctrl+R (Reset), Ctrl+P (In HĐ)

### 📥 **Trang Nhập hàng** (`index.php?page=imports`)
- ✅ Tạo phiếu nhập hàng
- ✅ Chọn nhà cung cấp
- ✅ Thêm sản phẩm nhập với giá nhập
- ✅ Tính tổng tiền nhập
- ✅ Xóa phiếu nhập
- ✅ Cập nhật tồn kho tự động
- ✅ In phiếu nhập

**Phím tắt:** F2 (Tìm SP), F3 (Thêm dòng), Ctrl+Enter (Lưu), Ctrl+P (In phiếu)

### 👥 **Trang Khách hàng** (`index.php?page=customers`)
- ✅ Quản lý thông tin khách hàng
- ✅ Phân loại hạng thành viên (VVIP/VIP/Thông thường)
- ✅ Theo dõi hoạt động mua hàng
- ✅ Xem chi tiết + lịch sử mua hàng (AJAX)
- ✅ Thống kê chi tiêu, sản phẩm yêu thích
- ✅ Filter theo trạng thái, hạng thành viên

**Phím tắt:** F1 (Thêm KH), F2 (Tìm kiếm), Ctrl+Enter (Lưu), ESC (Đóng)

### ↩️ **Trang Trả hàng** (`index.php?page=returns`)
- ✅ Tạo phiếu trả hàng
- ✅ Chọn hóa đơn cần trả (trong 30 ngày)
- ✅ Chọn lý do trả hàng
- ✅ Tự động cập nhật tồn kho
- ✅ Hoàn tiền khách hàng

**Phím tắt:** F1 (Tạo phiếu trả), ESC (Đóng), Ctrl+P (In phiếu)

### 📊 **Trang Báo cáo** (`index.php?page=reports`)
- ✅ Báo cáo doanh thu theo ngày/tháng
- ✅ Báo cáo tồn kho và cảnh báo
- ✅ Top sản phẩm bán chạy
- ✅ Thống kê khách hàng VIP
- ✅ Export CSV

**Phím tắt:** Ctrl+E (Export), Ctrl+P (In báo cáo)

---

## 🔧 **TÍNH NĂNG NỔI BẬT**

### 🚀 **UX/UI Hiện đại:**
- Responsive design (mobile-friendly)
- Toast notifications thông minh
- Card layout đẹp mắt với màu sắc phân biệt
- Loading animations mượt mà
- Color-coded system (đỏ-cảnh báo, xanh-ok, vàng-chú ý)

### ⌨️ **Phím tắt toàn diện:**
- **Alt + 1-6:** Navigation nhanh giữa các trang
- **F1, F2, F3:** Thao tác chính mỗi trang
- **Ctrl+Enter:** Lưu nhanh  
- **Ctrl+R:** Reset form
- **ESC:** Đóng modal
- **Ctrl+P:** In document

### 💾 **Auto-save & Validation:**
- Tự động lưu nháp mỗi 30 giây (trang bán hàng)
- Real-time validation (SĐT, email, số lượng)
- Cảnh báo tồn kho thấp
- Preview trước khi lưu
- Khôi phục draft khi reload

### 📊 **AJAX & Dynamic Features:**
- Xem chi tiết không reload trang
- Tìm kiếm real-time với delay 500ms
- Update UI động khi thay đổi dữ liệu
- Print-friendly design
- Modal loading states

### 🛡️ **Bảo mật:**
- SQL injection prevention (prepared statements)
- XSS protection (output escaping)
- Input validation server-side
- CSRF protection với form tokens
- Session management

### ⚡ **Hiệu suất:**
- AJAX loading cho thao tác nhanh
- Lazy loading cho danh sách dài
- CSS/JS optimization
- Database indexing cho query nhanh
- Caching mechanisms

---

### 📖 **CÁCH SỬ DỤNG HỆ THỐNG**

#### 1. **Dashboard (Trang chủ)**
- Xem tổng quan doanh thu, sản phẩm, khách hàng
- Theo dõi biểu đồ doanh thu 7 ngày
- Xem top sản phẩm bán chạy
- Quick access đến các chức năng chính

#### 2. **Quản lý Sản phẩm** 📦
```
Phím tắt: F1 hoặc click menu "Sản phẩm"

Chức năng:
✅ Thêm sản phẩm mới (F2)
✅ Tìm kiếm sản phẩm (F3) 
✅ Sửa thông tin sản phẩm
✅ Xóa sản phẩm
✅ Xem cảnh báo tồn kho
```

#### 3. **Bán hàng** 💰
```
Phím tắt: F2 hoặc click menu "Bán hàng"

Chức năng:
✅ Tạo hóa đơn mới
✅ Chọn khách hàng
✅ Thêm sản phẩm vào giỏ (F3)
✅ Tính toán tự động
✅ Lưu nháp tự động (Auto-save)
✅ In hóa đơn (Ctrl+P)
✅ Xem lịch sử bán hàng
```

#### 4. **Nhập hàng** 📥  
```
Phím tắt: F3 hoặc click menu "Nhập hàng"

Chức năng:
✅ Tạo phiếu nhập mới
✅ Chọn nhà cung cấp
✅ Thêm sản phẩm nhập (F2)
✅ Cập nhật tồn kho tự động
✅ In phiếu nhập
✅ Xem lịch sử nhập hàng
```

#### 5. **Quản lý Khách hàng** 👥
```
Phím tắt: F4 hoặc click menu "Khách hàng"

Chức năng:
✅ Thêm khách hàng mới (F2)
✅ Tìm kiếm khách hàng
✅ Phân hạng khách hàng (VIP, Regular)
✅ Xem lịch sử mua hàng
✅ Thống kê chi tiêu
```

---

### ⌨️ **KEYBOARD SHORTCUTS (Phím tắt)**

#### Navigation:
- **F1:** Sản phẩm
- **F2:** Bán hàng  
- **F3:** Nhập hàng
- **F4:** Khách hàng
- **F5:** Refresh trang

#### Actions:
- **Ctrl+Enter:** Lưu form hiện tại
- **Ctrl+N:** Tạo mới
- **ESC:** Đóng modal/Hủy
- **Ctrl+P:** In (trong trang bán hàng)

#### Page-specific:
- **Products:** F2 (Thêm), F3 (Tìm kiếm)
- **Sales:** F3 (Thêm SP), Ctrl+R (Reset form)
- **Imports:** F2 (Thêm SP), F3 (Lưu)

---

### 🎨 **TÍNH NĂNG NỔI BẬT**

#### 1. **Real-time Operations**
- Auto-save draft mỗi 30 giây
- AJAX không reload trang
- Toast notifications
- Live search

#### 2. **Professional UI/UX**
- Responsive design (mobile-friendly)
- Modern Bootstrap 5 interface
- Smooth animations
- Professional color scheme

#### 3. **Smart Features**  
- Cảnh báo tồn kho thấp
- Tính toán tự động
- Validation thông minh
- Print-ready templates

---

### 📊 **HIỂU VỀ DATABASE**

#### Core Tables:
```sql
products        - Sản phẩm (tên, giá, tồn kho, mô tả...)
customers       - Khách hàng (tên, SĐT, email, địa chỉ...)
suppliers       - Nhà cung cấp (tên, thông tin liên hệ...)
sales           - Hóa đơn bán (ngày, khách, tổng tiền...)
sale_details    - Chi tiết hóa đơn (sản phẩm, số lượng, giá...)
imports         - Phiếu nhập (ngày, NCC, tổng tiền...)
import_details  - Chi tiết nhập (sản phẩm, số lượng, giá...)
```

#### Advanced Features:
- **Views:** Tổng hợp dữ liệu thông minh
- **Triggers:** Tự động cập nhật tồn kho
- **Foreign Keys:** Đảm bảo tính toàn vẹn dữ liệu

---

### 🔍 **CODE STRUCTURE**

#### Frontend:
```
assets/css/style.css    - Toàn bộ CSS custom
assets/js/script.js     - JavaScript functions
includes/header.php     - Template header
includes/footer.php     - Template footer
```

#### Backend:
```
config/database.php     - Kết nối database
pages/*.php            - Các trang chức năng
ajax/*.php             - AJAX endpoints
index.php              - Router chính
```

#### Key Concepts:
- **MVC Pattern:** Tách biệt logic và presentation
- **PDO:** Database abstraction với prepared statements
- **AJAX:** Asynchronous data operations
- **Responsive Design:** Mobile-first approach

---

### 🛡️ **SECURITY FEATURES**

#### Implemented:
- **SQL Injection Prevention:** Prepared statements
- **XSS Protection:** Output escaping
- **Input Validation:** Server-side validation
- **CSRF Protection:** Form tokens

#### Code Example:
```php
// Prepared Statement (SQL Injection Prevention)
$stmt = $pdo->prepare("SELECT * FROM products WHERE product_name LIKE ?");
$stmt->execute(['%' . $search . '%']);

// XSS Prevention
echo htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8');

// Input Validation
if (empty($product_name) || strlen($product_name) < 2) {
    $errors[] = "Tên sản phẩm phải có ít nhất 2 ký tự";
}
```
#### Thiết kế hệ thống:
- **Architecture:** 3-tier (Presentation, Business, Data)
- **Database:** Normalized relational design
- **Interface:** Responsive web application
- **Technology Stack:** PHP + MySQL + Bootstrap

### 💡 **TIPS & BEST PRACTICES**

#### 📖 **Để hiểu code tốt hơn:**
1. **Đọc kỹ code** từ index.php → pages → ajax
2. **Thực hành phím tắt** để thao tác nhanh  
3. **Tùy chỉnh giao diện** theo ý thích trong assets/css/style.css
4. **Thêm tính năng mới** để nâng cao điểm (VD: backup, import Excel)
5. **Ghi chú lại** những thay đổi quan trọng

#### 🚀 **Để demo hiệu quả:**
1. **Chuẩn bị data mẫu** với các scenarios khác nhau
2. **Thành thạo phím tắt** để thao tác nhanh
3. **Hiểu rõ workflow** của từng chức năng
4. **Giải thích được** technical concepts (AJAX, responsive, security)
5. **Sẵn sàng troubleshoot** khi có lỗi

#### 📝 **Để viết báo cáo tốt:**
1. **Nêu rõ problem** hệ thống cần giải quyết
2. **Mô tả solution** với technology stack
3. **Chụp screenshots** các tính năng chính
4. **Code examples** cho các phần quan trọng
5. **Đánh giá** ưu/nhược điểm và hướng phát triển

---

## 📞 **HỖ TRỢ & TÀI LIỆU THAM KHẢO**

### 🔍 **Khi gặp vấn đề:**
1. **Đọc documentation:** README.md, KEYBOARD_SHORTCUTS.md
2. **Check browser console:** F12 → Console tab
3. **Check PHP errors:** Trong XAMPP logs
4. **Search online:** Stack Overflow, PHP.net

### 📚 **Resources hữu ích:**
- **PHP Manual:** https://www.php.net/manual/
- **MySQL Documentation:** https://dev.mysql.com/doc/
- **Bootstrap Docs:** https://getbootstrap.com/docs/
- **MDN Web Docs:** https://developer.mozilla.org/
- **jQuery API:** https://api.jquery.com/

### 📧 **File support khác:**
- `README.md` - Tổng quan project
- `KEYBOARD_SHORTCUTS.md` - Phím tắt chi tiết
- `PRODUCTION_DEPLOYMENT.md` - Deploy production
- `PROJECT_SUMMARY.md` - Tóm tắt project