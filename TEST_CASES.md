# 🧪 TEST CASES - THE TRENDY STATION
## Test Cases cho Dashboard, Sản phẩm, Bán hàng

---

## 📊 **TEST CASES - TRANG DASHBOARD**

### TC-DASH-001: Kiểm tra hiển thị trang chủ
**Mục tiêu:** Verify dashboard loads correctly
**Pre-condition:** Hệ thống đã setup, database có dữ liệu
**Steps:**
1. Truy cập: `http://localhost/trendy_station/`
2. Verify trang dashboard hiển thị
3. Check navigation menu hiển thị đầy đủ

**Expected Result:**
- ✅ Dashboard hiển thị không lỗi
- ✅ Menu navigation có: Dashboard, Sản phẩm, Bán hàng, Nhập hàng, Khách hàng, Trả hàng, Báo cáo
- ✅ Cards thống kê hiển thị (tổng sản phẩm, doanh thu, khách hàng)

**Test Data:** N/A

---

### TC-DASH-002: Kiểm tra thống kê tổng quan
**Mục tiêu:** Verify dashboard statistics are correct
**Pre-condition:** Database có dữ liệu mẫu
**Steps:**
1. Truy cập dashboard
2. Kiểm tra số liệu trong các cards:
   - Tổng sản phẩm
   - Doanh thu hôm nay
   - Số khách hàng
   - Số hóa đơn

**Expected Result:**
- ✅ Số liệu hiển thị chính xác (so với database)
- ✅ Không có số âm hoặc NaN
- ✅ Format số đúng (VND cho tiền)

**Test Data:** 
```sql
-- Verify data với queries này:
SELECT COUNT(*) FROM products;
SELECT SUM(total_amount) FROM sales WHERE DATE(sale_date) = CURDATE();
SELECT COUNT(*) FROM customers;
```

---

### TC-DASH-003: Kiểm tra biểu đồ doanh thu
**Mục tiêu:** Verify revenue chart displays correctly
**Pre-condition:** Có dữ liệu bán hàng trong 7 ngày gần đây
**Steps:**
1. Scroll xuống phần biểu đồ
2. Kiểm tra Chart.js hiển thị
3. Hover vào các điểm dữ liệu

**Expected Result:**
- ✅ Biểu đồ line chart hiển thị
- ✅ Có dữ liệu 7 ngày gần đây
- ✅ Tooltip hiển thị khi hover
- ✅ Không có lỗi JavaScript console

**Test Data:** Tạo sales data cho 7 ngày gần đây

---

### TC-DASH-004: Kiểm tra top sản phẩm bán chạy
**Mục tiêu:** Verify best selling products section
**Pre-condition:** Có dữ liệu sale_details
**Steps:**
1. Kiểm tra phần "Sản phẩm bán chạy"
2. Verify danh sách hiển thị đúng
3. Check thông tin: tên SP, số lượng bán, doanh thu

**Expected Result:**
- ✅ Hiển thị top 5 sản phẩm
- ✅ Sắp xếp theo số lượng bán (DESC)
- ✅ Thông tin chính xác

---

### TC-DASH-005: Kiểm tra responsive mobile
**Mục tiêu:** Verify dashboard is mobile-friendly
**Pre-condition:** Dashboard đã load
**Steps:**
1. Press F12 → Toggle device toolbar
2. Chọn iPhone/Android viewport
3. Kiểm tra layout

**Expected Result:**
- ✅ Cards xếp thành 1 cột trên mobile
- ✅ Menu collapse thành hamburger
- ✅ Biểu đồ responsive
- ✅ Không có horizontal scroll

---

## 📦 **TEST CASES - TRANG SẢN PHẨM**

### TC-PROD-001: Hiển thị danh sách sản phẩm
**Mục tiêu:** Verify products page loads and displays products list
**Pre-condition:** Database có ít nhất 5 sản phẩm
**Steps:**
1. Click menu "Sản phẩm" hoặc `index.php?page=products`
2. Kiểm tra danh sách sản phẩm hiển thị
3. Verify các columns: Mã SP, Tên, Giá, Tồn kho, Thao tác

**Expected Result:**
- ✅ Table hiển thị đầy đủ products
- ✅ Data chính xác với database
- ✅ Buttons Sửa/Xóa hiển thị cho mỗi row
- ✅ Badge tồn kho có màu sắc phù hợp:
  - 🔴 <= 5: "Hết hàng" (danger)
  - 🟡 <= 20: "Sắp hết" (warning)  
  - 🟢 > 20: "Còn hàng" (success)

**Test Data:**
```sql
INSERT INTO products VALUES 
(1, 'SP001', 'Áo thun nam', 'Thời trang nam', 150000, 3, 'Áo thun cotton'),
(2, 'SP002', 'Quần jean nữ', 'Thời trang nữ', 280000, 15, 'Quần jean skinny'),
(3, 'SP003', 'Váy đầm', 'Thời trang nữ', 320000, 25, 'Váy đầm công sở');
```

---

### TC-PROD-002: Tìm kiếm sản phẩm
**Mục tiêu:** Verify search functionality works correctly
**Pre-condition:** Có dữ liệu sản phẩm đa dạng
**Steps:**
1. Click vào ô tìm kiếm hoặc nhấn F2
2. Test các cases:
   - Tìm theo tên: "áo"
   - Tìm theo mã: "SP001"
   - Tìm theo danh mục: "nam"
   - Tìm chuỗi không tồn tại: "xyz123"

**Expected Result:**
- ✅ Tìm theo tên: Hiển thị tất cả SP chứa "áo"
- ✅ Tìm theo mã: Hiển thị chính xác SP001
- ✅ Tìm theo danh mục: Hiển thị các SP thời trang nam
- ✅ Không tìm thấy: Hiển thị "Không tìm thấy sản phẩm"
- ✅ Search case-insensitive

**Test Data:** Dùng data từ TC-PROD-001

---

### TC-PROD-003: Thêm sản phẩm mới
**Mục tiêu:** Verify add new product functionality
**Pre-condition:** Trang products đã load
**Steps:**
1. Click nút "Thêm sản phẩm" hoặc nhấn F1
2. Verify modal mở
3. Điền thông tin:
   - Mã SP: "SP004"
   - Tên: "Áo khoác"
   - Danh mục: "Thời trang nam"
   - Giá: "450000"
   - Tồn kho: "10"
   - Mô tả: "Áo khoác dạ"
4. Click "Lưu" hoặc Ctrl+Enter

**Expected Result:**
- ✅ Modal hiển thị với form đầy đủ fields
- ✅ Validation hoạt động (required fields)
- ✅ Lưu thành công → Toast notification
- ✅ Sản phẩm mới xuất hiện trong danh sách
- ✅ Modal đóng sau khi lưu

**Test Data:** Như steps

---

### TC-PROD-004: Validation form thêm sản phẩm
**Mục tiêu:** Verify form validation works
**Pre-condition:** Modal thêm SP đã mở
**Steps:**
1. Test các cases invalid:
   - Để trống tên SP
   - Nhập giá âm: "-1000"
   - Nhập tồn kho âm: "-5"
   - Nhập mã SP đã tồn tại: "SP001"
2. Click Lưu cho từng case

**Expected Result:**
- ✅ Tên trống: "Vui lòng nhập tên sản phẩm"
- ✅ Giá âm: "Giá phải lớn hơn 0"
- ✅ Tồn kho âm: "Số lượng phải >= 0"
- ✅ Mã trùng: "Mã sản phẩm đã tồn tại"
- ✅ Form không submit khi có lỗi

---

### TC-PROD-005: Sửa sản phẩm
**Mục tiêu:** Verify edit product functionality
**Pre-condition:** Có ít nhất 1 sản phẩm
**Steps:**
1. Click nút "Sửa" ở sản phẩm đầu tiên
2. Verify modal mở với data cũ
3. Thay đổi thông tin:
   - Tên: "Áo thun nam cập nhật"
   - Giá: "160000"
4. Click "Cập nhật"

**Expected Result:**
- ✅ Modal hiển thị với data hiện tại
- ✅ Update thành công
- ✅ Toast "Cập nhật sản phẩm thành công"
- ✅ Danh sách refresh với data mới

---

### TC-PROD-006: Xóa sản phẩm
**Mục tiêu:** Verify delete product functionality
**Pre-condition:** Có ít nhất 1 sản phẩm không có trong hóa đơn
**Steps:**
1. Click nút "Xóa" ở 1 sản phẩm
2. Confirm dialog xuất hiện
3. Click "Có, xóa!"

**Expected Result:**
- ✅ Confirm dialog hiển thị
- ✅ Xóa thành công nếu SP chưa bán
- ✅ Toast "Xóa sản phẩm thành công"
- ✅ Sản phẩm biến mất khỏi danh sách

---

### TC-PROD-007: Xóa sản phẩm có ràng buộc
**Mục tiêu:** Verify cannot delete product with sales
**Pre-condition:** Có sản phẩm đã có trong sale_details
**Steps:**
1. Click xóa sản phẩm đã bán
2. Confirm xóa

**Expected Result:**
- ✅ Error: "Không thể xóa sản phẩm đã có giao dịch"
- ✅ Sản phẩm không bị xóa

---

### TC-PROD-008: Phím tắt
**Mục tiêu:** Verify keyboard shortcuts work
**Pre-condition:** Đang ở trang products
**Steps:**
1. Nhấn F1 → Modal thêm SP mở
2. ESC → Modal đóng
3. F2 → Focus vào ô tìm kiếm

**Expected Result:**
- ✅ Phím tắt hoạt động đúng
- ✅ Focus chính xác

---

## 💰 **TEST CASES - TRANG BÁN HÀNG**

### TC-SALE-001: Hiển thị trang bán hàng
**Mục tiêu:** Verify sales page loads correctly
**Pre-condition:** Database có sản phẩm và khách hàng
**Steps:**
1. Click menu "Bán hàng" hoặc `index.php?page=sales`
2. Kiểm tra giao diện

**Expected Result:**
- ✅ Form bán hàng hiển thị đầy đủ:
  - Dropdown khách hàng
  - Ô tìm kiếm sản phẩm
  - Table sản phẩm trong giỏ (rỗng)
  - Tổng tiền = 0
- ✅ Danh sách hóa đơn gần đây bên phải
- ✅ Không có lỗi JavaScript

---

### TC-SALE-002: Chọn khách hàng
**Mục tiêu:** Verify customer selection works
**Pre-condition:** Database có ít nhất 3 khách hàng
**Steps:**
1. Click dropdown "Chọn khách hàng"
2. Chọn khách hàng đầu tiên
3. Verify thông tin hiển thị

**Expected Result:**
- ✅ Dropdown hiển thị danh sách khách hàng
- ✅ Sau khi chọn: Tên và SĐT hiển thị
- ✅ Input hidden customer_id có value

**Test Data:**
```sql
INSERT INTO customers VALUES 
(1, 'Nguyễn Văn A', '0901234567', 'a@email.com', 'Hà Nội', 'VIP'),
(2, 'Trần Thị B', '0909876543', 'b@email.com', 'HCM', 'Regular'),
(3, 'Lê Văn C', '0912345678', 'c@email.com', 'Đà Nẵng', 'VVIP');
```

---

### TC-SALE-003: Tìm kiếm sản phẩm bằng mã
**Mục tiêu:** Verify product search by code
**Pre-condition:** Có sản phẩm SP001 với tồn kho > 0
**Steps:**
1. Nhấn F2 → Focus vào ô tìm kiếm
2. Nhập "SP001"
3. Nhấn Enter

**Expected Result:**
- ✅ Sản phẩm SP001 tự động thêm vào giỏ
- ✅ Số lượng mặc định = 1
- ✅ Tổng tiền cập nhật
- ✅ Ô tìm kiếm clear

---

### TC-SALE-004: Tìm kiếm sản phẩm bằng tên
**Mục tiêu:** Verify product search by name
**Pre-condition:** Có sản phẩm tên chứa "áo"
**Steps:**
1. F2 → Nhập "áo"
2. Chờ dropdown suggestions
3. Click chọn sản phẩm

**Expected Result:**
- ✅ Dropdown hiển thị các SP chứa "áo"
- ✅ Click chọn → SP thêm vào giỏ
- ✅ Tính toán đúng

---

### TC-SALE-005: Thêm nhiều sản phẩm
**Mục tiêu:** Verify multiple products can be added
**Pre-condition:** Có ít nhất 3 sản phẩm với tồn kho > 0
**Steps:**
1. Thêm SP001 (số lượng 2)
2. F3 → Thêm dòng mới
3. Thêm SP002 (số lượng 1)
4. Thêm SP003 (số lượng 3)

**Expected Result:**
- ✅ Tất cả SP hiển thị trong table
- ✅ Tổng tiền = (SP001_price * 2) + (SP002_price * 1) + (SP003_price * 3)
- ✅ Mỗi dòng có nút xóa

**Test Data:** Dùng sản phẩm từ TC-PROD-001

---

### TC-SALE-006: Validation số lượng
**Mục tiêu:** Verify quantity validation
**Pre-condition:** SP001 có tồn kho = 10
**Steps:**
1. Thêm SP001 vào giỏ
2. Nhập số lượng = 15 (> tồn kho)
3. Tab ra khỏi ô input

**Expected Result:**
- ✅ Error: "Số lượng vượt quá tồn kho (10)"
- ✅ Số lượng reset về max available
- ✅ Toast cảnh báo

---

### TC-SALE-007: Áp dụng giảm giá
**Mục tiêu:** Verify discount calculation
**Pre-condition:** Giỏ hàng có tổng tiền 500,000 VND
**Steps:**
1. Nhập giảm giá = 10%
2. Tab ra khỏi ô input

**Expected Result:**
- ✅ Tiền giảm = 50,000 VND
- ✅ Thành tiền = 450,000 VND
- ✅ Tính toán real-time

---

### TC-SALE-008: Lưu hóa đơn
**Mục tiêu:** Verify save invoice functionality
**Pre-condition:** Giỏ hàng có ít nhất 1 SP, đã chọn khách hàng
**Steps:**
1. Đảm bảo form valid (khách hàng + sản phẩm)
2. Nhấn Ctrl+Enter hoặc click "Lưu hóa đơn"

**Expected Result:**
- ✅ Loading indicator hiển thị
- ✅ Success toast: "Lưu hóa đơn thành công"
- ✅ Form reset về trạng thái ban đầu
- ✅ Hóa đơn mới xuất hiện trong danh sách
- ✅ Tồn kho cập nhật (giảm đi)

---

### TC-SALE-009: Validation form lưu
**Mục tiêu:** Verify form validation before save
**Pre-condition:** Trang bán hàng đã load
**Steps:**
1. Không chọn khách hàng, click Lưu
2. Chọn khách hàng, giỏ rỗng, click Lưu

**Expected Result:**
- ✅ Case 1: "Vui lòng chọn khách hàng"
- ✅ Case 2: "Vui lòng thêm ít nhất 1 sản phẩm"
- ✅ Form không submit

---

### TC-SALE-010: Form validation
**Mục tiêu:** Verify form validation functionality
**Pre-condition:** Trang bán hàng đã load
**Steps:**
1. Để trống tên khách hàng và thử submit
2. Thêm sản phẩm với số lượng = 0
3. Thêm sản phẩm với số lượng > tồn kho
4. Kiểm tra validation message

**Expected Result:**
- ✅ Form không submit khi thiếu thông tin bắt buộc
- ✅ Warning khi số lượng = 0 hoặc > tồn kho
- ✅ Toast hiển thị message validation phù hợp

---

### TC-SALE-011: Xem chi tiết hóa đơn
**Mục tiêu:** Verify invoice detail modal
**Pre-condition:** Có ít nhất 1 hóa đơn đã lưu
**Steps:**
1. Click vào 1 hóa đơn trong danh sách
2. Verify modal chi tiết mở

**Expected Result:**
- ✅ Modal hiển thị thông tin:
  - Số HĐ, ngày, khách hàng
  - Chi tiết sản phẩm
  - Tổng tiền, giảm giá
- ✅ Nút "In hóa đơn" hoạt động

---

### TC-SALE-012: In hóa đơn
**Mục tiêu:** Verify print invoice functionality
**Pre-condition:** Modal chi tiết HĐ đã mở
**Steps:**
1. Click "In hóa đơn" hoặc Ctrl+P
2. Kiểm tra cửa sổ print

**Expected Result:**
- ✅ Cửa sổ mới mở với template in
- ✅ Thông tin chính xác và format đẹp
- ✅ Print dialog của browser mở

---

### TC-SALE-013: Reset form
**Mục tiêu:** Verify reset functionality
**Pre-condition:** Form có dữ liệu
**Steps:**
1. Điền form với khách hàng + sản phẩm
2. Nhấn Ctrl+R hoặc click "Reset"

**Expected Result:**
- ✅ Confirm dialog: "Bạn có muốn reset form?"
- ✅ Đồng ý → Form về trạng thái ban đầu
- ✅ Tất cả fields được xóa sạch

---

### TC-SALE-014: Phím tắt
**Mục tiêu:** Verify keyboard shortcuts
**Pre-condition:** Đang ở trang bán hàng
**Steps:**
1. F2 → Focus tìm kiếm SP
2. F3 → Thêm dòng SP mới
3. Ctrl+R → Reset form
4. Ctrl+Enter → Lưu HĐ (khi valid)

**Expected Result:**
- ✅ Tất cả phím tắt hoạt động đúng

---

### TC-SALE-015: Responsive mobile
**Mục tiêu:** Verify mobile compatibility
**Pre-condition:** Trang bán hàng đã load
**Steps:**
1. F12 → Mobile viewport
2. Test các chức năng chính

**Expected Result:**
- ✅ Layout stack vertically
- ✅ Touch-friendly buttons
- ✅ Modal responsive
- ✅ Tất cả chức năng hoạt động

---

## 🎯 TEST CASES - KEYBOARD SHORTCUTS

### TC-KS-001: Navigation Shortcuts
**Mục tiêu:** Test các phím tắt Alt+1-7 cho navigation
**Steps:**
1. Mở bất kỳ trang nào trong hệ thống
2. Nhấn Alt+1 → Kiểm tra chuyển đến Dashboard
3. Nhấn Alt+2 → Kiểm tra chuyển đến Products
4. Nhấn Alt+3 → Kiểm tra chuyển đến Sales
5. Nhấn Alt+4 → Kiểm tra chuyển đến Imports
6. Nhấn Alt+5 → Kiểm tra chuyển đến Customers  
7. Nhấn Alt+6 → Kiểm tra chuyển đến Returns
8. Nhấn Alt+7 → Kiểm tra chuyển đến Reports

**Expected Result:**
- Mỗi phím tắt chuyển đúng trang
- Hiển thị toast notification
- URL thay đổi đúng
- Console không có lỗi

**Error Scenarios:**
- Browser conflict với Alt+1-9
- JavaScript bị disable
- Script.js không load

### TC-KS-002: Help Shortcuts
**Mục tiêu:** Test phím tắt hiển thị help
**Steps:**
1. Nhấn F1 → Hiển thị modal help
2. Nhấn Alt+H → Hiển thị modal help
3. Nhấn Escape → Đóng modal

**Expected Result:**
- Modal help hiển thị đầy đủ shortcuts
- Escape đóng modal
- Không xung đột với browser F1

### TC-KS-003: Page Specific Shortcuts
**Mục tiêu:** Test phím tắt riêng từng trang
**Steps (Sales Page):**
1. Vào trang Sales (Alt+3)
2. Nhấn F2 → Focus vào ô search sản phẩm
3. Nhấn F3 → Thêm dòng sản phẩm
4. Nhấn F4 → Thanh toán
5. Nhấn F5 → In hóa đơn
6. Nhấn Ctrl+D → Xóa draft

**Expected Result:**
- Mỗi function key thực hiện đúng chức năng
- Toast notification hiển thị
- Form behavior chính xác

### TC-KS-004: Cross-browser Compatibility
**Mục tiêu:** Test compatibility trên các browser
**Steps:**
1. Test trên Chrome: Alt+1-7
2. Test trên Firefox: Alt+1-7  
3. Test trên Edge: Alt+1-7
4. Test browser fullscreen mode (F11)

**Expected Result:**
- Chrome: Có thể conflict, cần fullscreen
- Firefox: Có thể conflict, cần fullscreen
- Edge: Có thể conflict, cần fullscreen
- Fullscreen: Hoạt động tốt hơn

### TC-KS-005: Keyboard Shortcut Indicator
**Mục tiêu:** Test hiển thị indicator trong navigation
**Steps:**
1. Load bất kỳ trang nào
2. Kiểm tra navigation menu
3. Xác nhận có hiển thị (Alt+1), (Alt+2), etc.

**Expected Result:**
- Mỗi nav item có shortcut indicator
- Style đẹp, không bị lỗi layout
- Responsive trên mobile

### TC-KS-006: Test Script Debug
**Mục tiêu:** Test debugging tools
**Steps:**
1. Mở test_shortcuts.html
2. Test Auto Test All function
3. Kiểm tra console logs
4. Verify event detection

**Expected Result:**
- Auto test chạy được
- Console log chi tiết events
- Visual feedback (highlight)
- Browser info hiển thị

### TC-KS-007: Error Handling
**Mục tiêu:** Test xử lý lỗi phím tắt
**Steps:**
1. Disable JavaScript → Test shortcuts
2. Block script.js → Test shortcuts  
3. Slow network → Test script load
4. Mobile device → Test shortcuts

**Expected Result:**
- JavaScript disabled: Fallback to click navigation
- Script blocked: Error message, manual navigation
- Slow network: Progressive loading
- Mobile: Shortcuts disabled, touch navigation

### TC-KS-008: Performance Test
**Mục tiêu:** Test performance của keyboard events
**Steps:**
1. Spam Alt+1-7 nhanh liên tục
2. Test trên page có nhiều content
3. Test với network slow 3G
4. Monitor memory usage

**Expected Result:**
- Không lag, không crash
- Event debouncing hoạt động
- Memory không leak
- Smooth trên slow network

---

## 🚨 **CRITICAL ERROR SCENARIOS**

### ERROR-001: Database connection fail
**Steps:** Tắt MySQL service, reload trang
**Expected:** Error message "Không thể kết nối database"

### ERROR-002: Empty database
**Steps:** Drop toàn bộ tables, reload
**Expected:** Graceful handling, không crash

### ERROR-003: Concurrent sales
**Steps:** 2 users cùng bán 1 SP cuối cùng
**Expected:** 1 người thành công, 1 người báo lỗi tồn kho

### ERROR-004: Invalid SQL injection
**Steps:** Nhập `'; DROP TABLE products; --` vào ô tìm kiếm
**Expected:** Được escape, không execute

### ERROR-005: XSS attempt
**Steps:** Nhập `<script>alert('xss')</script>` vào tên SP
**Expected:** Được escape, hiển thị as text

---

## ✅ **TEST EXECUTION CHECKLIST**

### Pre-test Setup:
- [ ] XAMPP running (Apache + MySQL)
- [ ] Database trendy_station created
- [ ] All SQL files imported
- [ ] Sample data available
- [ ] Browser Developer Tools open

### Test Environment:
- [ ] Chrome/Firefox latest version
- [ ] Console clear (no existing errors)
- [ ] LocalStorage cleared
- [ ] Multiple tabs for concurrent testing

### Post-test Verification:
- [ ] Check database consistency
- [ ] Verify no JavaScript errors
- [ ] Check performance (loading times)
- [ ] Mobile testing completed
- [ ] Print functionality tested

---

*📝 Created: 08/06/2025*
*🧪 Total Test Cases: 45+*
*⏱️ Estimated Testing Time: 2-3 hours*
