# RestaurantQR – Nhóm 02 (PHP + MySQL, chạy bằng XAMPP)

Web gọi món bằng mã QR cho nhà hàng. Viết bằng **PHP thuần + MySQL**, không cần Node.js hay npm.
Mọi dữ liệu (tài khoản, bàn, QR, thực đơn, phiên bàn, đơn, bếp/phục vụ, hóa đơn, thanh toán, thông báo, nhật ký) lưu trong **MySQL của XAMPP**. Tải lại trang hay đăng nhập máy khác vẫn thấy dữ liệu.

## Cách chạy (làm một lần)
1. Chép **cả thư mục `RestaurantQR`** vào `C:\xampp\htdocs\` (kết quả: `C:\xampp\htdocs\RestaurantQR\index.php`).
2. Mở **XAMPP Control Panel**, bấm **Start** cho **Apache** và **MySQL**.
3. Vào http://localhost/phpmyadmin → tab **Nhập (Import)** → chọn `database/restaurantqr.sql` → **Thực hiện**.
   File này tạo CSDL riêng `restaurantqr_nhom02` (không đụng CSDL khác), có thể nhập lại để làm lại từ đầu.
4. Mở **http://localhost/RestaurantQR/**

Sửa code bằng **VS Code**: `File > Open Folder` chọn thư mục `RestaurantQR` (nên đặt thẳng trong `htdocs`), lưu file rồi tải lại trình duyệt. Gợi ý cài extension *PHP Intelephense*.
Nếu MySQL có mật khẩu root hoặc khác cổng: sửa `app/config/config.php`.

## Tài khoản có sẵn (đổi mật khẩu sau khi đăng nhập: Quản trị → Tài khoản)
| Vai trò | Tên đăng nhập | Mật khẩu |
|---|---|---|
| Thu ngân | thungan | Thungan@123 |
| Bếp | bep | Bep@123 |
| Phục vụ | phucvu | Phucvu@123 |
| Quản trị | admin | Admin@123 |

Quên mật khẩu admin: mở http://localhost/RestaurantQR/create_admin.php, đặt lại, rồi **xóa file này**.

## Thử nhanh một vòng
1. Đăng nhập `thungan` → chọn một bàn → **Mở phiên bàn** (hiện mã 4 số + mã QR).
2. Mở trang chủ (cửa sổ ẩn danh cho giống khách) → bấm bàn đó → nhập mã 4 số → chọn món → **Gửi đơn**.
3. `bep` thấy món → **+1 xong**. `phucvu` thấy món sẵn sàng → **+1 mang ra**.
4. `thungan` → **Chốt hóa đơn** → **Xác nhận đã nhận tiền** → **Đóng phiên**.
5. `admin` xem doanh thu, nhật ký; kiểm tra bảng `orders`, `order_items`, `payments`… trong phpMyAdmin.

## Cấu trúc thư mục
```
RestaurantQR/
├─ app/
│  ├─ config/config.php      cấu hình MySQL
│  ├─ database.php           kết nối PDO + hàm truy vấn
│  ├─ init.php               khởi động (session, nạp file)
│  ├─ helpers/               functions.php (hiển thị, CSRF, thông báo), auth.php (đăng nhập, phân quyền)
│  ├─ models/                truy vấn MySQL: user, table, menu, order, invoice, notice, stats, audit
│  └─ controllers/           nghiệp vụ + kiểm tra quyền: customer, staff, admin
├─ database/restaurantqr.sql tạo CSDL + dữ liệu khởi tạo
├─ views/                    khung giao diện dùng chung (layout, staff_layout, customer_layout)
├─ public/
│  ├─ index.php              trang chủ, chọn bàn
│  ├─ login.php, logout.php, dashboard.php
│  ├─ customer/              join, menu, cart, orders (khách tại bàn)
│  ├─ staff/                 cashier, kitchen, waiter
│  ├─ admin/                 index, menu, menu_form, menu_save, tables, users, audit
│  └─ assets/                css, js (qrcode.min.js: thư viện QR MIT)
├─ create_admin.php          tạo/đặt lại quản trị viên
└─ index.php                 chuyển vào public/
```

## Nguyên tắc
- PHP xử lý nghiệp vụ và kiểm tra quyền (`assert_role` ở từng thao tác, không chỉ ẩn nút); MySQL lưu dữ liệu; trình duyệt chỉ hiển thị.
- Mật khẩu băm bcrypt; mọi form có mã CSRF; mọi câu SQL dùng tham số (PDO prepared); giá món luôn lấy từ CSDL.
- Quy tắc: một bàn chỉ một phiên mở (ràng buộc UNIQUE); mã tham gia 4 số theo từng phiên, sai 5 lần khóa 60 giây; chống gửi trùng đơn; số phần luôn thỏa `đã mang ra ≤ đã làm ≤ đã gọi − đã hủy`; hủy món cần quyền thu ngân + lý do + ghi nhật ký; chốt hóa đơn thì khóa gọi thêm; mỗi hóa đơn chỉ một thanh toán; “tôi đã chuyển khoản” chỉ tạo thông báo.
- Các trang bếp, phục vụ, thu ngân, đơn của khách tự làm mới vài giây một lần (không cần tải lại).
- Giỏ hàng nháp của khách giữ trong phiên PHP cho tới khi gửi đơn; đơn thật lưu trong MySQL.
- Phông chữ lấy từ Google Fonts (cần Internet; không có thì dùng phông hệ thống).
