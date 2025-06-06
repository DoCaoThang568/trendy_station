# 🎓 HƯỚNG DẪN CHO SINH VIÊN
## The Trendy Station - Hệ thống quản lý shop thời trang

### 🎯 **MỤC TIÊU BÀI TẬP**

Bài tập này giúp sinh viên:
- ✅ Thực hành **phát triển web full-stack** với PHP + MySQL
- ✅ Hiểu và áp dụng **thiết kế database** thực tế
- ✅ Xây dựng **giao diện người dùng** hiện đại và responsive
- ✅ Tích hợp **AJAX** cho trải nghiệm mượt mà
- ✅ Thực hiện **security best practices**
- ✅ Tạo **documentation** chuyên nghiệp

---

### 📚 **KIẾN THỨC TIÊN QUYẾT**

#### Bắt buộc:
- **HTML/CSS:** Cơ bản đến trung cấp
- **JavaScript:** Syntax cơ bản, DOM manipulation
- **PHP:** Variables, functions, arrays, OOP cơ bản
- **MySQL:** SELECT, INSERT, UPDATE, DELETE

#### Nên có:
- **Bootstrap:** Framework CSS
- **AJAX/jQuery:** Asynchronous requests
- **Git:** Version control cơ bản

---

### 🚀 **HƯỚNG DẪN CÀI ĐẶT NHANH**

#### Bước 1: Download & Setup XAMPP
```bash
1. Tải XAMPP từ: https://www.apachefriends.org/
2. Cài đặt và khởi động Apache + MySQL
3. Mở Control Panel, Start Apache & MySQL
```

#### Bước 2: Setup Project
```bash
1. Copy folder 'th_pttkht' vào: C:\xampp\htdocs\
2. Đổi tên folder thành 'trendy_station' (optional)
3. Mở browser: http://localhost/trendy_station
```

#### Bước 3: Auto Setup Database
```bash
1. Truy cập: http://localhost/trendy_station/setup.php
2. Follow hướng dẫn trên màn hình
3. Nhập thông tin database:
   - Host: localhost
   - Username: root  
   - Password: (để trống)
   - Database: trendy_station
```

#### Bước 4: Kiểm tra hoạt động
```bash
1. Truy cập: http://localhost/trendy_station
2. Kiểm tra Dashboard hiển thị đúng
3. Test các chức năng: Products, Sales, etc.
```

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

---

### 📝 **THUYẾT MINH ĐỒ ÁN**

#### Mô tả bài toán:
"Xây dựng hệ thống quản lý shop thời trang The Trendy Station với các chức năng quản lý sản phẩm, bán hàng, nhập hàng, khách hàng và báo cáo thống kê. Hệ thống cần có giao diện hiện đại, tính năng thông minh và khả năng mở rộng."

#### Phân tích yêu cầu:
1. **Functional Requirements:**
   - Quản lý sản phẩm (CRUD)
   - Xử lý bán hàng và hóa đơn
   - Quản lý nhập hàng từ NCC
   - Quản lý thông tin khách hàng
   - Báo cáo và thống kê

2. **Non-functional Requirements:**
   - Performance: Responsive < 2s
   - Usability: Intuitive interface  
   - Security: Data protection
   - Scalability: Extensible architecture

#### Thiết kế hệ thống:
- **Architecture:** 3-tier (Presentation, Business, Data)
- **Database:** Normalized relational design
- **Interface:** Responsive web application
- **Technology Stack:** PHP + MySQL + Bootstrap

---

### 🎯 **ĐÁNH GIÁ & CHẤM ĐIỂM**

#### Tiêu chí đánh giá:

**1. Thiết kế Database (20%)**
- ✅ Schema design chuẩn
- ✅ Relationships đúng
- ✅ Indexes tối ưu
- ✅ Sample data phong phú

**2. Backend Development (30%)**
- ✅ PHP code structure
- ✅ Security implementation
- ✅ Error handling
- ✅ Performance optimization

**3. Frontend Development (25%)**
- ✅ UI/UX design
- ✅ Responsive layout
- ✅ JavaScript functionality
- ✅ Cross-browser compatibility

**4. Features & Functionality (20%)**
- ✅ Complete CRUD operations
- ✅ Business logic implementation
- ✅ Advanced features (AJAX, auto-save)
- ✅ User experience enhancements

**5. Documentation & Presentation (5%)**
- ✅ Code comments
- ✅ User manual
- ✅ Installation guide
- ✅ Project presentation

---

### 💡 **TIPS CHO SINH VIÊN**

#### Development Tips:
1. **Start Small:** Bắt đầu với 1 trang đơn giản
2. **Test Frequently:** Test sau mỗi feature
3. **Comment Code:** Viết comment rõ ràng
4. **Backup Regular:** Backup code thường xuyên
5. **Debug Smart:** Sử dụng console.log() và var_dump()

#### Presentation Tips:
1. **Demo Flow:** Chuẩn bị demo script
2. **Error Handling:** Biết cách xử lý lỗi
3. **Explain Code:** Hiểu và giải thích được code
4. **Show Features:** Highlight tính năng độc đáo
5. **Be Confident:** Tự tin thuyết trình

#### Common Issues:
```
❌ Database connection error
✅ Check XAMPP MySQL running

❌ Page not found
✅ Check file paths and .htaccess

❌ AJAX not working  
✅ Check browser console for errors

❌ CSS not loading
✅ Check file paths and cache
```

---

### 🎪 **DEMO SCENARIOS**

#### Scenario 1: Bán hàng cơ bản
```
1. Vào trang Bán hàng (F2)
2. Chọn khách hàng từ dropdown
3. Thêm sản phẩm vào giỏ (F3)
4. Xem tính toán tự động
5. Lưu hóa đơn (Ctrl+Enter)
6. In hóa đơn
```

#### Scenario 2: Quản lý tồn kho
```
1. Vào trang Sản phẩm (F1)
2. Xem cảnh báo tồn kho thấp
3. Vào trang Nhập hàng (F3)
4. Tạo phiếu nhập cho sản phẩm hết
5. Quay lại kiểm tra tồn kho đã cập nhật
```

#### Scenario 3: Thống kê khách hàng
```
1. Vào trang Khách hàng (F4)
2. Thêm khách hàng mới
3. Thực hiện vài giao dịch bán hàng
4. Xem lịch sử mua hàng của khách
5. Kiểm tra ranking khách hàng
```

---

### 🏆 **KẾT LUẬN**

**The Trendy Station** là một project hoàn chỉnh thể hiện:

✅ **Technical Skills:** Full-stack development với PHP + MySQL  
✅ **Problem Solving:** Giải quyết bài toán thực tế  
✅ **Code Quality:** Clean, documented, maintainable code  
✅ **User Experience:** Modern, intuitive interface  
✅ **Professional Standards:** Production-ready application  

**Điểm mạnh của project:**
- Tính năng đầy đủ và thực tế
- Giao diện chuyên nghiệp
- Performance tối ưu
- Security được đảm bảo
- Documentation chi tiết

**Phù hợp cho:**
- Đồ án tốt nghiệp
- Portfolio cá nhân
- Learning reference
- Base cho project thực tế

---

### 📞 **HỖ TRỢ & LIÊN HỆ**

#### Nếu gặp vấn đề:
1. **Đọc documentation:** README.md, INSTALLATION_GUIDE.md
2. **Check console:** Browser Developer Tools
3. **Search online:** Stack Overflow, PHP.net
4. **Ask instructor:** Hỏi giảng viên khi cần

#### Resources hữu ích:
- **PHP Manual:** https://www.php.net/manual/
- **MySQL Documentation:** https://dev.mysql.com/doc/
- **Bootstrap Docs:** https://getbootstrap.com/docs/
- **MDN Web Docs:** https://developer.mozilla.org/

---

**🎉 Chúc các bạn thành công với đồ án!**

*"The best way to learn is by doing. The Trendy Station is your gateway to mastering modern web development!"*
