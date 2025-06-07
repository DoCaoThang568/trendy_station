# 🎯 HƯỚNG DẪN SỬ DỤNG HỆ THỐNG - THE TRENDY STATION

## ⌨️ PHÍM TẮT CHUNG TOÀN HỆ THỐNG:

### 🚀 **Navigation nhanh:**
- **Alt + 1** - Trang Sản phẩm
- **Alt + 2** - Trang Bán hàng  
- **Alt + 3** - Trang Nhập hàng
- **Alt + 4** - Trang Khách hàng
- **Alt + 5** - Trang Trả hàng
- **Alt + 6** - Trang Báo cáo

---

## 💰 TRANG BÁN HÀNG (SALES):

### 🔥 **Phím tắt chính:**
- **F2** - Focus vào ô tìm kiếm sản phẩm
- **F3** - Thêm dòng sản phẩm mới
- **Ctrl + Enter** - Lưu hóa đơn (submit form)
- **Ctrl + R** - Reset form (làm mới)
- **Ctrl + P** - In hóa đơn được chọn
- **Enter** (trong ô số lượng) - Thêm dòng sản phẩm tiếp theo

### 🔍 **Tìm kiếm & Thêm nhanh:**
- Nhấn **F2** → Nhập mã SP (VD: SP001) → **Enter** = Thêm sản phẩm tự động
- Nhập 4 số đầu SĐT trong ô điện thoại = Tìm khách hàng tự động
- Tìm kiếm theo: Tên SP, Mã SP, Danh mục

### 💾 **Auto-save:**
- Tự động lưu nháp mỗi 30 giây
- Khôi phục nháp khi mở lại trang
- Xóa nháp khi lưu hóa đơn thành công

---

## 👥 TRANG KHÁCH HÀNG (CUSTOMERS):

### 🎯 **Phím tắt chính:**
- **F1** - Thêm khách hàng mới
- **F2** - Focus vào ô tìm kiếm khách hàng
- **Ctrl + Enter** - Lưu thông tin khách hàng (trong modal)
- **ESC** - Đóng modal/hủy thao tác

### 🔍 **Tìm kiếm nâng cao:**
- Tìm theo: Tên, SĐT, Email, Mã khách hàng
- Filter theo: Trạng thái (hoạt động/không hoạt động)
- Filter theo: Hạng thành viên (VVIP/VIP/Thông thường)
- Auto-search khi nhập (delay 500ms)

---

## 📦 TRANG NHẬP HÀNG (IMPORTS):

### 🔥 **Phím tắt chính:**
- **F2** - Focus vào ô tìm kiếm sản phẩm
- **F3** - Thêm dòng sản phẩm nhập mới
- **Ctrl + Enter** - Lưu phiếu nhập hàng
- **Ctrl + R** - Reset form (làm mới)
- **Enter** (trong modal chi tiết) - Xem chi tiết phiếu nhập
- **Ctrl + P** (trong modal chi tiết) - In phiếu nhập

### 📋 **Thao tác nhanh:**
- Click vào phiếu nhập = Xem chi tiết
- Nhập mã sản phẩm trong ô tìm kiếm = Thêm tự động
- Validation thông minh: Số lượng, giá cả, nhà cung cấp

---

## ↩️ TRANG TRẢ HÀNG (RETURNS):

### 🎯 **Phím tắt chính:**
- **F1** - Tạo phiếu trả hàng mới
- **ESC** - Đóng modal tạo phiếu trả
- **Ctrl + P** - In phiếu trả hàng

### 📋 **Quy trình trả hàng:**
1. Chọn hóa đơn cần trả (trong vòng 30 ngày)
2. Chọn lý do trả hàng
3. Chọn sản phẩm và số lượng trả
4. Tự động cập nhật tồn kho
5. Tạo phiếu trả và hoàn tiền

---

## 📊 TRANG BÁO CÁO (REPORTS):

### 🔥 **Phím tắt chính:**
- **Ctrl + E** - Xuất báo cáo (Export CSV)
- **Ctrl + P** - In báo cáo

### 📈 **Các loại báo cáo:**
- **Báo cáo bán hàng**: Doanh thu, top sản phẩm, theo ngày
- **Báo cáo tồn kho**: Cảnh báo hết hàng, giá trị tồn kho
- **Báo cáo lợi nhuận**: Phân tích margin, chi phí, lợi nhuận
- **Tổng quan**: Dashboard tổng hợp tất cả chỉ số

---

## 📦 TRANG SẢN PHẨM (PRODUCTS):

### 🎯 **Phím tắt cơ bản:**
- **F1** - Thêm sản phẩm mới
- **F2** - Focus vào ô tìm kiếm sản phẩm
- **ESC** - Đóng modal
- **Enter** (trong modal) - Lưu sản phẩm

### 🔍 **Tìm kiếm & Filter:**
- Tìm theo: Tên, mã sản phẩm, danh mục
- Filter theo: Tồn kho (hết hàng, sắp hết, đủ hàng)
- Sắp xếp theo: Tên, giá, tồn kho, ngày tạo

---

## 🎨 TÍNH NĂNG NÂNG CAO:

### 💡 **Toast Notifications:**
- Tự động hiển thị thông báo sau mỗi thao tác
- Màu sắc: 🟢 Thành công, 🟡 Cảnh báo, 🔴 Lỗi, 🔵 Thông tin
- Tự động ẩn sau 3-5 giây

### 🎯 **Modal & Popup:**
- Responsive design, tương thích mobile
- Smooth animation (fade in/out)
- Click overlay hoặc ESC để đóng
- Auto-focus vào field đầu tiên

### 📱 **Mobile Responsive:**
- Tối ưu cho màn hình 320px trở lên
- Touch-friendly buttons và inputs
- Swipe gestures cho navigation
- Compact layout cho mobile

### 💾 **Auto-save & Validation:**
- Real-time validation (số điện thoại, email, số lượng)
- Auto-save draft trong localStorage
- Khôi phục dữ liệu khi reload trang
- Cảnh báo trước khi rời khỏi trang có thay đổi

---

## 🔒 BẢO MẬT & HIỆU SUẤT:

### 🛡️ **Bảo mật:**
- SQL injection protection (prepared statements)
- XSS protection (htmlspecialchars)
- Input validation server-side
- Session management

### ⚡ **Hiệu suất:**
- AJAX loading cho các thao tác nhanh
- Lazy loading cho danh sách dài
- CSS/JS minification
- Database indexing cho query nhanh

---

## 📞 HỖ TRỢ & GỢI Ý:

### 💡 **Tips sử dụng hiệu quả:**
1. Sử dụng phím tắt thay vì click chuột
2. Nhập mã sản phẩm thay vì tìm kiếm tên
3. Sử dụng auto-complete cho khách hàng
4. Kiểm tra báo cáo hàng ngày
5. Backup dữ liệu định kỳ

### 🚨 **Xử lý lỗi thường gặp:**
- **Lỗi kết nối DB**: Kiểm tra config/database.php
- **Lỗi permission**: Chmod 755 cho thư mục uploads
- **Lỗi session**: Xóa cache trình duyệt
- **Lỗi in ấn**: Cho phép popup trong browser

---

## 📝 CHÚ THÍCH PHIÊN BẢN:

**Version 2.0** 
- ✅ Hoàn thiện CRUD tất cả modules
- ✅ Thêm trang Trả hàng với full workflow
- ✅ Báo cáo nâng cao với export CSV
- ✅ Print templates cho tất cả chức năng
- ✅ AJAX real-time cho UX tốt hơn
- ✅ Mobile responsive 100%
- ✅ Keyboard shortcuts toàn bộ
- ✅ Auto-save & draft recovery
- ✅ Advanced search & filtering
