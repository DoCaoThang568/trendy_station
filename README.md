# 🏪 THE TRENDY STATION
### *Hệ thống quản lý shop thời trang hiện đại*

![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?style=flat&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.1.3-7952B3?style=flat&logo=bootstrap&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=flat)

---

## 🎯 **GIỚI THIỆU**

**The Trendy Station** là hệ thống quản lý shop thời trang được phát triển bằng **PHP + MySQL**, với giao diện hiện đại, tính năng đầy đủ và trải nghiệm người dùng tuyệt vời.

### ✨ **Tính năng nổi bật:**
- 🚀 **Phím tắt thông minh** (F1, F2, F3, Ctrl+Enter...)
- 📱 **Responsive design** (mobile-friendly)
- ⚡ **AJAX real-time** (không cần reload trang)
- 💾 **Auto-save draft** (lưu nháp tự động)
- 🎨 **UI/UX hiện đại** (Bootstrap 5 + custom CSS)
- 📊 **Thống kê thông minh** (tồn kho, khách hàng, doanh thu)

---

## 📋 **CÁC TRANG CHỨC NĂNG**

| Trang | Trạng thái | Tính năng chính | Phím tắt |
|-------|------------|-----------------|----------|
| 🏠 **Dashboard** | ✅ Hoàn thành | Tổng quan, thống kê, biểu đồ | F1-F5 |
| 📦 **Sản phẩm** | ✅ Hoàn thành | CRUD sản phẩm, tìm kiếm, phân loại | F1, F2, F3 |
| 💰 **Bán hàng** | ✅ Hoàn thành | Lập HĐ, auto-save, in HĐ, AJAX | F2, F3, Ctrl+Enter |
| 📥 **Nhập hàng** | ✅ Hoàn thành | Phiếu nhập, chọn NCC, cập nhật tồn kho | F1, F2, F3 |
| 👥 **Khách hàng** | ✅ Hoàn thành | CRUD KH, phân hạng, lịch sử mua hàng | F1, F2, Ctrl+Enter |
| ↩️ **Trả hàng** | ✅ Hoàn thành | Xử lý trả hàng, hoàn tiền, báo cáo | F1, F2, F3 |
| 📊 **Báo cáo** | ✅ Hoàn thành | Doanh thu, tồn kho, phân tích, export | F1, F2, F3 |

---

## 🚀 **HƯỚNG DẪN CÀI ĐẶT NHANH**

### 1️⃣ **Tải XAMPP & Khởi động:**
```bash
# Tải XAMPP: https://www.apachefriends.org/
# Khởi động Apache + MySQL
```

### 2️⃣ **Thiết lập Database:**
```sql
-- Truy cập: http://localhost/phpmyadmin
-- Tạo database: trendy_station
-- Import theo thứ tự:
1. database.sql
2. database_imports.sql  
3. database_customers.sql
```

### 3️⃣ **Deploy Source Code:**
```bash
# Copy project vào: C:\xampp\htdocs\trendy_station\
# Truy cập: http://localhost/trendy_station/
```

### 📖 **Hướng dẫn chi tiết:** [INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md)

---

## ⌨️ **PHÍM TẮT THÔNG MINH**

| Phím | Chức năng | Áp dụng |
|------|-----------|---------|
| **F1** | Thêm mới | Sản phẩm, Khách hàng, Phiếu nhập |
| **F2** | Focus tìm kiếm | Tất cả trang |
| **F3** | Thêm dòng sản phẩm | Bán hàng, Nhập hàng |
| **Ctrl+Enter** | Lưu nhanh | Form modal, Hóa đơn |
| **Ctrl+R** | Reset form | Bán hàng, Nhập hàng |
| **ESC** | Đóng modal | Tất cả modal |

### 📚 **Hướng dẫn đầy đủ:** [KEYBOARD_SHORTCUTS.md](KEYBOARD_SHORTCUTS.md)

---

## 🗂️ **CẤU TRÚC PROJECT**

```
trendy_station/
├── 📁 config/              # Cấu hình database
├── 📁 includes/            # Header/Footer chung  
├── 📁 pages/               # Các trang chức năng
├── 📁 ajax/                # API AJAX
├── 📁 assets/              # CSS/JS/Images
├── 📄 index.php            # Router chính
├── 📄 print_invoice.php    # In hóa đơn
├── 📄 *.sql                # Database scripts
└── 📄 *.md                 # Documentation
```

---

## 🎨 **SCREENSHOTS**

### 💰 Trang Bán hàng
![Sales Page](assets/images/sales-screenshot.png)
*Giao diện bán hàng với auto-save, phím tắt và AJAX*

### 👥 Trang Khách hàng  
![Customers Page](assets/images/customers-screenshot.png)
*Quản lý khách hàng với card view và phân hạng thành viên*

### 📦 Trang Sản phẩm
![Products Page](assets/images/products-screenshot.png)
*Quản lý sản phẩm với filter và cảnh báo tồn kho*

---

## 🛠️ **STACK CÔNG NGHỆ**

### **Backend:**
- ![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=flat&logo=php) **PHP 7.4+** - Server-side scripting
- ![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?style=flat&logo=mysql) **MySQL 5.7+** - Relational database
- **PDO** - Database abstraction layer

### **Frontend:**
- ![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=flat&logo=html5&logoColor=white) **HTML5** - Markup language
- ![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=flat&logo=css3) **CSS3** - Styling + Custom animations
- ![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=flat&logo=javascript&logoColor=black) **JavaScript ES6** - Client-side scripting
- ![Bootstrap](https://img.shields.io/badge/Bootstrap-5.1.3-7952B3?style=flat&logo=bootstrap) **Bootstrap 5.1.3** - CSS framework
- **Font Awesome 6.0** - Icon library

---

## 📊 **DATABASE DESIGN**

### **Bảng chính:**
- `categories` - Danh mục sản phẩm
- `products` - Sản phẩm (với auto stock management)
- `sales` + `sale_details` - Hóa đơn bán hàng
- `customers` - Khách hàng (với membership levels)
- `suppliers` - Nhà cung cấp
- `imports` + `import_details` - Phiếu nhập hàng

### **Features:**
- ✅ **Auto-generated codes** (SP001, HD20240115001, KH001...)
- ✅ **Triggers & Views** (auto stock update, membership upgrade...)
- ✅ **Foreign key constraints** (data integrity)
- ✅ **Indexed columns** (performance optimization)

---

## 🌟 **ĐIỂM MẠNH**

### **🎯 For Students:**
- ✅ **Complete MVC structure** - Cấu trúc dự án chuẩn
- ✅ **Best practices** - Code sạch, có comment
- ✅ **Real-world features** - Tính năng thực tế
- ✅ **Detailed documentation** - Tài liệu đầy đủ

### **🚀 For Users:**
- ✅ **Fast & responsive** - Tốc độ nhanh, responsive
- ✅ **Intuitive UI/UX** - Giao diện trực quan
- ✅ **Keyboard shortcuts** - Thao tác nhanh
- ✅ **Smart notifications** - Thông báo thông minh

### **⚡ For Developers:**
- ✅ **Modular architecture** - Kiến trúc module
- ✅ **AJAX integration** - Tích hợp AJAX
- ✅ **Extensible design** - Dễ mở rộng
- ✅ **Clean code** - Code sạch, dễ đọc

---

## 📈 **ROADMAP**

### **🔥 Priority High:**
- [ ] **Authentication system** (login/logout/roles)
- [ ] **Advanced reports** (sales/profit/inventory)
- [ ] **Returns management** (complete returns page)

### **📋 Priority Medium:**
- [ ] **Barcode integration** (scan products)
- [ ] **Email notifications** (send invoices)
- [ ] **Data export** (Excel/PDF reports)

### **💡 Priority Low:**
- [ ] **Mobile app** (React Native/Flutter)
- [ ] **API development** (RESTful API)
- [ ] **Real-time updates** (WebSocket)

---

## 📖 **DOCUMENTATION**

| File | Mô tả |
|------|-------|
| [PROJECT_SUMMARY.md](PROJECT_SUMMARY.md) | Tổng quan chi tiết dự án |
| [INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md) | Hướng dẫn cài đặt từng bước |
| [KEYBOARD_SHORTCUTS.md](KEYBOARD_SHORTCUTS.md) | Hướng dẫn phím tắt đầy đủ |

---

## 🤝 **ĐÓNG GÓP**

### **Cách đóng góp:**
1. Fork project này
2. Tạo feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Mở Pull Request

### **Issues & Bug Reports:**
- 🐛 **Bug reports:** [GitHub Issues](https://github.com/yourusername/trendy-station/issues)
- 💡 **Feature requests:** [GitHub Discussions](https://github.com/yourusername/trendy-station/discussions)

---

## 📄 **LICENSE**

Distributed under the MIT License. See `LICENSE` for more information.