# POD-style Order Manager — README

## Tổng quan
Đây là tài liệu hướng dẫn (file `README.md`) cho một ứng dụng quản lý đơn POD (Print-On-Demand). Tài liệu mô tả **ý tưởng**, **cách triển khai**, **cấu trúc**, và **những điểm cần chỉnh** để kết nối tới cơ sở dữ liệu MySQL (`45.79.0.186`, user `duytan`, password `tandb`). Tài liệu chỉ là hướng dẫn — **không kèm code**. Nếu bạn muốn, tôi có thể tạo code sau khi bạn confirm schema.

Ứng dụng hỗ trợ chức năng chính:
- Đăng nhập / đăng xuất (login / logout).
- Tìm kiếm đơn hàng (search order) — trả về thông tin đơn và danh sách item (ảnh, variant id / style, size, quantity).
- In nhãn (print label) — hoạt động bằng cách **proxy** ảnh nhãn về server của bạn rồi mở trang in (vì không thể in trực tiếp từ một URL thuộc domain khác).

---

## Cấu trúc đề xuất của project
Gợi ý cây thư mục (để bạn triển khai sau):

- `public/` — chứa các trang web public (login, orders, proxy image, assets)
- `src/` — chứa cấu hình, kết nối DB, helper, và các truy vấn SQL
- `assets/` — CSS, JS, fonts
- `README.md` — file hướng dẫn này

Bạn sẽ đặt document root webserver trỏ vào thư mục `public/`.

---

## Cấu trúc database
> **Ghi chú:** Đọc chi tiết từ file `database_schema.md`. Ở đây không mô tả chi tiết schema mà chỉ tham chiếu để lấy thông tin bảng và cột từ file đó.

---

## Luồng chức năng chính (miêu tả)

### 1. Đăng nhập / Đăng xuất
- Người dùng vào trang login, nhập username & password.
- Ứng dụng kiểm tra thông tin bằng truy vấn tới bảng `users` và so khớp mật khẩu với hash (ví dụ `password_verify` trong PHP).
- Sau khi đăng nhập thành công lưu session (user_id, display_name) và chuyển tới trang chính (orders).
- Logout xóa session và chuyển về trang login.

### 2. Tìm kiếm đơn hàng
- Trường tìm kiếm cho phép nhập `order id` (số) hoặc `order number` (chuỗi).
- Nếu nhập số hợp lệ, ưu tiên tìm theo `id`; nếu nhập chuỗi, tìm theo `order_number` bằng `LIKE` (partial match).
- Kết quả trả về danh sách đơn (hoặc một đơn). Mỗi đơn hiển thị thông tin cơ bản và danh sách item liên quan: ảnh, product name, variant_id/style, size, quantity.

### 3. In nhãn (Print Label)
- Mỗi order có thể có một trường `label_url` (URL tới ảnh nhãn do hệ thống in label cung cấp).
- Vì trình duyệt không cho in cross-origin image page trực tiếp từ domain khác, giải pháp là **proxy** ảnh đó về server của bạn (ví dụ `proxy_label.php?src=<encoded-url>`).
- Khi user bấm **Print Label**, hệ thống mở cửa sổ mới chứa thẻ `<img src="/proxy_label.php?src=...">` — ảnh sẽ tải từ cùng-origin và script tự gọi `window.print()` để mở hộp thoại in.

---

## Giao diện / UX (POD style)
Mục tiêu giao diện: tối giản, sắc nét, cảm giác "POD" — sạch, nhiều khoảng trắng, góc bo lớn, nút bo tròn, palette màu trung tính với accent màu xanh dương.

**Các thành phần UI đề xuất:**
- Header fixed với logo nhỏ ở trái và thông tin người dùng + nút Logout ở phải.
- Trang login: card ở giữa viewport, form đơn giản, input bo tròn, button accent.
- Trang Orders: thanh tìm kiếm phía trên, danh sách đơn hiện dưới dạng card, mỗi card hiển thị thông tin order & table items.
- Mỗi hàng item có thumbnail ảnh sản phẩm, cột variant/style/size/qty.
- Nút `Print Label` rõ ràng, nằm ở góc phải mỗi card order.

Bạn có thể dùng font system (Inter, Roboto, ...). Nếu muốn UI mẫu hoàn chỉnh, tôi có thể tạo HTML/CSS riêng.

---

## Các lưu ý bảo mật và vận hành
- **Mật khẩu:** luôn lưu dạng băm (bcrypt / password_hash). Không lưu plain-text.
- **Proxy ảnh:** giới hạn whitelist domain để tránh proxy tới các URL độc hại; giới hạn kích thước file; set timeout; cache hợp lý.
- **CSRF:** thêm token cho form POST nếu ứng dụng public.
- **XSS:** escape mọi dữ liệu người dùng khi render HTML.
- **Thông tin DB:** file README chứa thông tin host/user/pass — hãy giữ file này an toàn (không commit vào public repo).

---

## Cách deploy (tóm tắt)
1. Chuẩn bị máy chủ PHP (Apache/Nginx + PHP-FPM), bật `pdo_mysql`.
2. Tải mã nguồn vào server, đặt `public/` làm document root.
3. Cập nhật config DB: host = `45.79.0.186`, user = `duytan`, pass = `tandb`, và tên database chính xác.
4. Tạo hoặc kiểm tra bảng `users`, `orders`, `order_items` theo schema trong `database_schema.md`. Tạo user admin bằng cách chèn `username` và `password_hash` (sử dụng `password_hash('yourpass', PASSWORD_DEFAULT)`).
5. Mở trang login, đăng nhập, bắt đầu tìm kiếm.

---

## Mở rộng (tùy chọn)
- Tạo script SQL để tạo bảng `users` và seed một admin.
- Thêm chức năng in hàng loạt (chọn nhiều order, in nhiều nhãn cùng lúc).
- Thêm logs truy cập/thao tác để audit.
- Thêm hệ thống quyền (role-based access) nếu nhiều user với quyền khác nhau.

---

Nếu bạn chỉ cần file `README.md` (chính xác là file `.md`), tài liệu này đã sẵn sàng. Nếu muốn tôi chuyển sang tạo mã nguồn thực tế hoặc tạo file ZIP chứa scaffold, chỉ cần nói "tạo code" hoặc upload `database_schema.md` để tôi chỉnh SQL tương ứng.