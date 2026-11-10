-- RestaurantQR - Nhóm 02: tạo cơ sở dữ liệu cho MySQL của XAMPP
-- Cách dùng: phpMyAdmin > Nhập (Import) > chọn file này > Thực hiện.
-- Có thể nhập lại nhiều lần: CSDL restaurantqr_nhom02 sẽ được tạo lại từ đầu.
DROP DATABASE IF EXISTS restaurantqr_nhom02;
CREATE DATABASE restaurantqr_nhom02 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE restaurantqr_nhom02;

-- Tài khoản nhân viên
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  full_name VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(10) NOT NULL,            -- cashier, kitchen, waiter, admin
  locked TINYINT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL
);

-- Bàn ăn và mã QR (qr_key đổi khi tạo lại QR)
CREATE TABLE dining_tables (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(10) NOT NULL UNIQUE,
  name VARCHAR(50) NOT NULL,
  seats INT NOT NULL DEFAULT 4,
  active TINYINT NOT NULL DEFAULT 1,
  qr_key VARCHAR(20) NOT NULL,
  join_code CHAR(4) NOT NULL DEFAULT '0000'    -- mã 4 số cố định của bàn
);

-- Thực đơn
CREATE TABLE menu_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  category VARCHAR(50) NOT NULL,
  description VARCHAR(300) NOT NULL DEFAULT '',
  unit VARCHAR(40) NOT NULL DEFAULT 'phần',
  price INT NOT NULL,
  available TINYINT NOT NULL DEFAULT 1,
  art VARCHAR(20) NOT NULL DEFAULT 'bowl',   -- kiểu tranh minh họa
  tone TINYINT NOT NULL DEFAULT 0,
  image_url VARCHAR(255) NULL,
  sort_no INT NOT NULL DEFAULT 0
);

-- Phiên bàn: mỗi bàn chỉ có 1 phiên đang mở (open_flag = 1; khi đóng đặt NULL)
CREATE TABLE table_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  table_id INT NOT NULL,
  join_code CHAR(4) NOT NULL,
  opened_by INT NOT NULL,
  opened_at DATETIME NOT NULL,
  closed_at DATETIME NULL,
  open_flag TINYINT NULL,
  UNIQUE KEY uq_one_open_session (table_id, open_flag),
  FOREIGN KEY (table_id) REFERENCES dining_tables(id),
  FOREIGN KEY (opened_by) REFERENCES users(id)
);

-- Đơn gọi món và các dòng món
CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL UNIQUE,
  session_id INT NOT NULL,
  request_token VARCHAR(40) NOT NULL,       -- chống gửi trùng đơn khi bấm đúp
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_request (session_id, request_token),
  FOREIGN KEY (session_id) REFERENCES table_sessions(id)
);
CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  menu_item_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  unit VARCHAR(40) NOT NULL,
  price INT NOT NULL,                       -- giá lấy từ CSDL lúc gọi món
  qty INT NOT NULL,
  cancelled INT NOT NULL DEFAULT 0,
  done INT NOT NULL DEFAULT 0,              -- bếp đã làm xong
  served INT NOT NULL DEFAULT 0,            -- phục vụ đã mang ra
  note VARCHAR(200) NOT NULL DEFAULT '',
  started TINYINT NOT NULL DEFAULT 0,
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
);

-- Hóa đơn, dòng hóa đơn, thanh toán (mỗi hóa đơn chỉ có 1 thanh toán)
CREATE TABLE invoices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id INT NOT NULL UNIQUE,
  total INT NOT NULL,
  status VARCHAR(10) NOT NULL DEFAULT 'unpaid',
  finalized_at DATETIME NOT NULL,
  finalized_by INT NOT NULL,
  FOREIGN KEY (session_id) REFERENCES table_sessions(id),
  FOREIGN KEY (finalized_by) REFERENCES users(id)
);
CREATE TABLE invoice_lines (
  id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  unit VARCHAR(40) NOT NULL,
  qty INT NOT NULL,
  price INT NOT NULL,
  FOREIGN KEY (invoice_id) REFERENCES invoices(id)
);
CREATE TABLE payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT NOT NULL UNIQUE,
  method VARCHAR(10) NOT NULL,              -- cash, transfer
  amount INT NOT NULL,
  paid_at DATETIME NOT NULL,
  confirmed_by INT NOT NULL,
  FOREIGN KEY (invoice_id) REFERENCES invoices(id),
  FOREIGN KEY (confirmed_by) REFERENCES users(id)
);

-- Thông báo từ khách (gọi nhân viên, báo đã chuyển khoản) và nhật ký thao tác
CREATE TABLE notices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id INT NOT NULL,
  table_id INT NOT NULL,
  kind VARCHAR(20) NOT NULL,                -- transfer-claim, call-staff
  text VARCHAR(200) NOT NULL,
  handled TINYINT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  FOREIGN KEY (session_id) REFERENCES table_sessions(id)
);
CREATE TABLE audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  at DATETIME NOT NULL,
  actor VARCHAR(100) NOT NULL,
  action VARCHAR(60) NOT NULL,
  target VARCHAR(100) NOT NULL DEFAULT '',
  detail VARCHAR(255) NULL,
  reason VARCHAR(255) NULL
);

-- ===== Dữ liệu khởi tạo =====

INSERT INTO users (username, full_name, password_hash, role, locked, created_at) VALUES
('thungan', 'Nguyễn Thu Ngân', '$2y$10$NTMuK4QVMSyyko447enjreWgAB54hfUAL0eHOhpbFIFblA7Tnb6N2', 'cashier', 0, NOW()),
('bep', 'Trần Văn Bếp', '$2y$10$qgH0HuwfSkpPrlgvC0qm8eQc9iVZm02q/aiNeqyhoeP27AxyjA1dW', 'kitchen', 0, NOW()),
('phucvu', 'Lê Phục Vụ', '$2y$10$9.5GNzxb7OTsGkmu6uMnMuVmN/eJyeDFSMG1cBB2MT03AWkEHa6eO', 'waiter', 0, NOW()),
('admin', 'Quản trị viên', '$2y$10$NsZ.YM4iBEkpJSrDCUGlv.hT1sV4O4yDzeca6odR0urkpGWfKlaXS', 'admin', 0, NOW());

INSERT INTO dining_tables (code, name, seats, active, qr_key, join_code) VALUES
('B01', 'Bàn 01', 2, 1, 'k01x7q', '1001'),
('B02', 'Bàn 02', 2, 1, 'k02x7q', '1002'),
('B03', 'Bàn 03', 2, 1, 'k03x7q', '1003'),
('B04', 'Bàn 04', 2, 1, 'k04x7q', '1004'),
('B05', 'Bàn 05', 2, 1, 'k05x7q', '1005'),
('B06', 'Bàn 06', 2, 1, 'k06x7q', '1006'),
('B07', 'Bàn 07', 4, 1, 'k07x7q', '1007'),
('B08', 'Bàn 08', 4, 1, 'k08x7q', '1008'),
('B09', 'Bàn 09', 4, 1, 'k09x7q', '1009'),
('B10', 'Bàn 10', 4, 1, 'k10x7q', '1010'),
('B11', 'Bàn 11', 4, 1, 'k11x7q', '1011'),
('B12', 'Bàn 12', 4, 1, 'k12x7q', '1012'),
('B13', 'Bàn 13', 6, 1, 'k13x7q', '1013'),
('B14', 'Bàn 14', 6, 1, 'k14x7q', '1014'),
('B15', 'Bàn 15', 6, 1, 'k15x7q', '1015');

INSERT INTO menu_items (name, category, description, unit, price, available, art, tone, image_url, sort_no) VALUES
('Gỏi cuốn tôm thịt', 'Khai vị', 'Tôm, thịt luộc, bún, rau thơm cuốn bánh tráng, chấm tương đậu phộng.', 'phần 2 cuốn', 45000, 1, 'roll', 0, NULL, 1),
('Chả giò chiên giòn', 'Khai vị', 'Nhân thịt, miến, mộc nhĩ, chiên vàng, ăn kèm rau sống.', 'phần 4 cuốn', 50000, 1, 'roll', 1, NULL, 2),
('Gỏi ngó sen tôm thịt', 'Khai vị', 'Ngó sen giòn, tôm, thịt ba chỉ, rau răm, đậu phộng rang.', 'đĩa', 85000, 1, 'salad', 2, NULL, 3),
('Nem nướng Nha Trang', 'Khai vị', 'Nem nướng than hoa, cuốn bánh tráng với rau và bún, nước chấm đặc biệt.', 'phần 5 xiên', 79000, 1, 'skewer', 1, NULL, 4),
('Phở bò tái', 'Phở & bún', 'Nước dùng hầm xương 12 giờ, bò tái, bánh phở mềm, hành, rau thơm.', 'tô', 65000, 1, 'bowl', 3, NULL, 5),
('Bún bò Huế', 'Phở & bún', 'Nước dùng sả ớt, giò heo, bò bắp, chả cua, ăn kèm rau sống.', 'tô', 69000, 1, 'bowl', 1, NULL, 6),
('Bún chả Hà Nội', 'Phở & bún', 'Chả viên và chả miếng nướng than, bún, nước mắm chua ngọt, rau sống.', 'phần', 70000, 1, 'bowl', 2, NULL, 7),
('Mì Quảng gà', 'Phở & bún', 'Sợi mì vàng, gà xé, nước nhân sền sệt, đậu phộng, bánh tráng nướng.', 'tô', 62000, 1, 'bowl', 0, NULL, 8),
('Cơm tấm sườn bì chả', 'Cơm', 'Sườn nướng mật ong, bì, chả trứng, mỡ hành, nước mắm tỏi ớt.', 'đĩa', 68000, 1, 'plate', 4, NULL, 9),
('Cơm gà Hội An', 'Cơm', 'Cơm nấu nước luộc gà, gà xé, hành phi, rau răm, tương ớt.', 'đĩa', 65000, 1, 'plate', 0, NULL, 10),
('Cơm chiên hải sản', 'Cơm', 'Cơm chiên trứng với tôm, mực, rau củ, thêm chút nước tương.', 'đĩa', 75000, 1, 'plate', 2, NULL, 11),
('Bò lúc lắc', 'Món chính', 'Thăn bò xào lửa lớn với hành tây, ớt chuông, ăn cùng khoai chiên.', 'đĩa', 115000, 1, 'skewer', 4, NULL, 12),
('Cá kho tộ', 'Món chính', 'Cá lóc kho nồi đất với nước màu, tiêu, hành; hợp ăn với cơm trắng.', 'tộ', 98000, 1, 'pot', 4, NULL, 13),
('Rau muống xào tỏi', 'Món chính', 'Rau muống xào lửa lớn với tỏi phi thơm.', 'đĩa', 45000, 1, 'salad', 5, NULL, 14),
('Bánh xèo tôm thịt', 'Bánh', 'Vỏ bánh giòn nghệ, tôm, thịt, giá; cuốn rau và chấm nước mắm.', 'cái', 72000, 1, 'pancake', 2, NULL, 15),
('Bánh mì thịt nướng', 'Bánh', 'Bánh mì giòn, thịt nướng, đồ chua, rau mùi, sốt đặc biệt.', 'ổ', 35000, 1, 'sandwich', 3, NULL, 16),
('Cà phê sữa đá', 'Đồ uống & tráng miệng', 'Cà phê phin đậm, sữa đặc, nhiều đá.', 'ly', 29000, 1, 'glass', 4, NULL, 17),
('Trà đào cam sả', 'Đồ uống & tráng miệng', 'Trà đen, đào miếng, cam tươi, sả đập dập.', 'ly', 39000, 1, 'glass', 1, NULL, 18),
('Nước mía', 'Đồ uống & tráng miệng', 'Mía ép tươi, thêm chút tắc.', 'ly', 25000, 1, 'glass', 5, NULL, 19),
('Chè ba màu', 'Đồ uống & tráng miệng', 'Đậu đỏ, đậu xanh, thạch lá dứa, nước cốt dừa, đá bào.', 'ly', 32000, 1, 'dessert', 0, NULL, 20);
