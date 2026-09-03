# Chạy thử website trên máy bạn

Hướng dẫn cho người **chưa dùng WordPress bao giờ**. Chọn một trong hai cách.

Cách A cần Docker + Terminal, nhưng dùng đúng cấu hình đã có sẵn trong repo.
Cách B không cần Terminal, cài một app rồi copy hai thư mục — dễ hơn nếu bạn không quen dòng lệnh.

---

## Trước khi bắt đầu: kiểm tra code mà không cần cài gì

Nếu máy có PHP (`php -v` chạy được), bạn kiểm tra được toàn bộ code trong 5 giây:

```sh
sh tools/lint.sh
```

Script này kiểm tra cú pháp toàn bộ file và render thử 8 trang ra `tools/output/*.html`.
Mở mấy file HTML đó bằng trình duyệt là thấy giao diện — chưa phải WordPress thật, nhưng
đủ để xem trang trông thế nào.

---

## Cách A — Docker (khuyến nghị)

### A1. Cài hai thứ

1. **Docker Desktop** — https://www.docker.com/products/docker-desktop/
   Cài xong **mở app lên** và đợi tới khi biểu tượng con cá voi báo "Running".
   (Nếu không mở Docker, lệnh bên dưới sẽ báo lỗi "Cannot connect to the Docker daemon".)
2. **Node.js** bản LTS — https://nodejs.org/
   Kiểm tra: mở Terminal gõ `node -v`, ra số phiên bản là được.

### A2. Khởi động WordPress

Mở Terminal, đi vào thư mục repo (thư mục có file `.wp-env.json`):

```sh
cd đường/dẫn/tới/hira-tobacco
npx @wordpress/env start
```

Lần đầu sẽ tải WordPress + MySQL nên **mất 3–5 phút**. Nếu nó hỏi cài gói `@wordpress/env`,
gõ `y` rồi Enter.

Chạy xong màn hình hiện:

```
WordPress development site started at http://localhost:8888
```

### A3. Bật giao diện

```sh
npx @wordpress/env run cli wp theme activate annamleaf
npx @wordpress/env run cli wp rewrite flush --hard
```

Plugin **Annam Leaf Core** đã được bật tự động, và ngay lúc bật nó tự tạo sẵn 7 bước quy
trình, 4 chủng loại lá, 3 vùng trồng, 5 trang, đặt trang chủ và dựng menu.

### A4. Mở website

- Trang web: **http://localhost:8888**
- Trang quản trị: **http://localhost:8888/wp-admin**
  Tài khoản `admin` — mật khẩu `password`

### A5. Tắt / bật lại / xoá sạch

```sh
npx @wordpress/env stop      # tắt (giữ nguyên dữ liệu)
npx @wordpress/env start     # bật lại
npx @wordpress/env destroy   # xoá sạch, làm lại từ đầu
```

Sửa file trong `wp-content/themes/annamleaf` rồi F5 trình duyệt là thấy ngay, không cần
khởi động lại.

---

## Cách B — LocalWP (không cần Terminal)

1. Tải **Local** tại https://localwp.com/ và cài đặt (miễn phí).
2. Mở Local → **Create a new site** → đặt tên `annamleaf` → chọn **Preferred** →
   đặt username/password cho WordPress → **Add site**. Đợi nó cài xong.
3. Trong Local, bấm nút **Go to site folder** → mở tới thư mục `app/public/wp-content`.
4. Copy hai thư mục từ repo vào đó:
   - `wp-content/themes/annamleaf` → dán vào `wp-content/themes/`
   - `wp-content/plugins/annamleaf-core` → dán vào `wp-content/plugins/`
5. Trong Local bấm **WP Admin** để mở trang quản trị.
6. Vào **Plugins** → tìm **Annam Leaf Core** → bấm **Activate**.
   (Bật plugin lần đầu là lúc nội dung mẫu được tạo — nhớ làm bước này **trước**.)
7. Vào **Appearance → Themes** → di chuột lên **Annam Leaf** → bấm **Activate**.
8. Vào **Settings → Permalinks** → bấm **Save Changes** (không cần đổi gì).
   Bước này để đường dẫn `/process/` và `/our-leaf/` hoạt động.
9. Bấm **Open site** trong Local để xem.

---

## Xem thử 8 thứ này để nghiệm thu

| # | Làm gì | Kỳ vọng thấy gì |
| --- | --- | --- |
| 1 | Mở trang chủ | Hero xanh đậm, dải 4 số liệu, 4 thẻ quy trình, 4 thẻ sản phẩm, vùng trồng, dải CTA |
| 2 | Bấm **Process** trên menu | 7 bước, ảnh minh hoạ so le trái–phải |
| 3 | Bấm **Our Leaf** | 4 thẻ chủng loại + bảng quy cách bên dưới |
| 4 | Admin → **Company profile** → điền "Trading name" → Save | Tên công ty đổi ngay trên header và footer |
| 5 | Admin → **Process** → mở 1 bước → **Featured image** → upload ảnh bất kỳ | Ảnh thật thay chỗ minh hoạ vector, đúng trong khung đó |
| 6 | Admin → **Process** (danh sách) | Cột **Photo**: bước nào chưa có ảnh ghi "Not uploaded" kèm mô tả ảnh cần chụp |
| 7 | **Company profile** → bỏ tick "Mark empty fields" → Save | Các ô vàng `[NHƯ THẾ NÀY]` biến mất khỏi trang |
| 8 | **Company profile** → tick "Show the 18+ age gate" → Save → mở site ở cửa sổ ẩn danh | Hiện màn xác nhận 18+ trước khi vào site |

---

## Vài chuyện bình thường, không phải lỗi

- **Gửi thử form báo giá ở local sẽ báo không gửi được.** Máy local không có mail server.
  Trên hosting thật thì cần cài plugin **WP Mail SMTP**.
- **Polylang hiện wizard cài đặt** (chỉ ở cách A, vì nó được cài sẵn). Lần chạy thử đầu cứ
  bỏ qua, hoặc vào Plugins tắt Polylang đi. Nút chuyển EN/VI trên header chỉ hiện khi
  Polylang đã được cấu hình đủ hai ngôn ngữ.
- **Trang chủ hiện nhiều ô vàng.** Đúng như thiết kế: đó là các chỗ đang chờ nội dung thật
  từ khách hàng. Xem mục 7 ở bảng trên để tắt.
- **Logo là chữ "[COMPANY NAME]"**. Chưa có logo thật. Điền tên ở Company profile, hoặc
  upload logo ở **Appearance → Customize → Site Identity**.
- **`/process/` báo 404.** Vào **Settings → Permalinks** bấm **Save Changes** một lần.

---

## Nếu kẹt

Chụp màn hình lỗi và nói tôi biết bạn đang ở bước nào — kèm dòng lệnh cuối cùng bạn chạy
và thông báo lỗi đầy đủ.
