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
- **Không thấy nút chuyển EN/VI trên header.** Đúng: nút đó chỉ hiện khi đã cài và cấu
  hình Polylang đủ hai ngôn ngữ. Cấu hình local mặc định không cài Polylang để lần chạy
  đầu nhẹ nhất. Khi muốn thử song ngữ: vào **Plugins → Add New**, tìm "Polylang", cài và
  bật, rồi thêm hai ngôn ngữ English + Tiếng Việt.
- **Trang chủ hiện nhiều ô vàng.** Đúng như thiết kế: đó là các chỗ đang chờ nội dung thật
  từ khách hàng. Xem mục 7 ở bảng trên để tắt.
- **Logo là chữ "[COMPANY NAME]"**. Chưa có logo thật. Điền tên ở Company profile, hoặc
  upload logo ở **Appearance → Customize → Site Identity**.
- **`/process/` báo 404.** Vào **Settings → Permalinks** bấm **Save Changes** một lần.

---

## Xử lý lỗi thường gặp

### `Could not find the current WordPress version in the cache and the network is not available`

`wp-env` gọi `api.wordpress.org` để hỏi bản WordPress mới nhất, và request đó không đi
được. Lỗi xảy ra **trước khi Docker được dùng đến**, nên không liên quan tới Docker.

Repo đã ghim sẵn phiên bản trong `.wp-env.json` (`"core": "WordPress/WordPress#6.7"`) để
`wp-env` khỏi phải hỏi — nó tải thẳng từ GitHub. Nếu bạn gặp lỗi này, hãy `git pull` để lấy
bản cấu hình mới rồi chạy lại:

```sh
npx @wordpress/env destroy
npx @wordpress/env start
```

Vẫn lỗi thì chẩn đoán mạng theo thứ tự:

1. **Mở trình duyệt** vào https://api.wordpress.org/core/version-check/1.7/
   Ra một đống chữ JSON = mạng ổn ở mức trình duyệt.
2. **Thử ở Terminal** (Windows, dùng `curl.exe` chứ không phải `curl` vì PowerShell đổi tên lệnh):
   ```sh
   curl.exe -I https://api.wordpress.org/core/version-check/1.7/
   node -e "fetch('https://api.wordpress.org/core/version-check/1.7/').then(r=>console.log('OK',r.status)).catch(e=>console.log('FAIL',e.message))"
   ```
3. **Trình duyệt vào được nhưng Terminal thì không** → có gì đó chặn `node.exe`:
   - Đang bật **VPN** hoặc proxy công ty → tắt rồi thử lại
   - **Antivirus / tường lửa** chặn Node → cho phép `node.exe`
   - npm còn cấu hình proxy cũ:
     ```sh
     npm config get proxy
     npm config get https-proxy
     npm config delete proxy
     npm config delete https-proxy
     ```
4. **Cả hai đều không vào được** → nhiều khả năng DNS của nhà mạng. Đổi DNS máy sang
   `1.1.1.1` và `8.8.8.8` rồi thử lại.

### Vẫn không tải được WordPress: cài thủ công

Nếu mạng chặn hẳn, tự tải WordPress bằng trình duyệt rồi trỏ `wp-env` vào thư mục đó:

1. Tải https://wordpress.org/latest.zip bằng trình duyệt.
2. Giải nén vào repo, sao cho có thư mục `wordpress/` chứa `wp-admin`, `wp-includes`…
   (thư mục này đã được `.gitignore` bỏ qua).
3. Sửa `.wp-env.json`, đổi dòng `"core"` thành:
   ```json
   "core": "./wordpress",
   ```
4. `npx @wordpress/env destroy` rồi `npx @wordpress/env start`.

Lưu ý: Docker vẫn cần mạng để tải image lần đầu (`mariadb`, `wordpress`). Nếu Docker cũng
không tải được image thì dùng **Cách B (LocalWP)**.

### `Cannot connect to the Docker daemon`

Docker Desktop chưa chạy. Mở app lên, đợi biểu tượng cá voi báo "Running", rồi chạy lại.

### `port is already allocated` / cổng 8888 bận

Có thứ khác đang chiếm cổng 8888. Thêm vào `.wp-env.json`:

```json
"port": 8889,
```

rồi `npx @wordpress/env start` và mở http://localhost:8889.

## Nếu kẹt

Chụp màn hình lỗi và nói tôi biết bạn đang ở bước nào — kèm dòng lệnh cuối cùng bạn chạy
và thông báo lỗi đầy đủ.
