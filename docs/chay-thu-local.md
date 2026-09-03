# Chạy thử website trên máy bạn

Hướng dẫn cho người **chưa dùng WordPress bao giờ**. Có ba cách, chọn một:

| Cách | Cần gì | Chọn khi |
| --- | --- | --- |
| **A. Docker Compose** | Docker Desktop | Khuyến nghị. Không cần Node, không gọi wordpress.org |
| **B. LocalWP** | Không cần Terminal | Bạn không quen dòng lệnh |
| **C. wp-env** | Docker + Node | Chỉ khi mạng vào được `api.wordpress.org` |

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

## Cách A — Docker Compose (khuyến nghị)

WordPress nằm sẵn trong image Docker, nên cách này **không cần Node và không gọi
wordpress.org** — tránh được hẳn lỗi `Could not find the current WordPress version`.

### A1. Cài Docker Desktop

https://www.docker.com/products/docker-desktop/ — cài xong **mở app lên**, đợi biểu tượng
con cá voi báo "Running".

### A2. Bật server

Mở Terminal, vào thư mục repo (thư mục có file `docker-compose.yml`):

```sh
cd đường/dẫn/tới/hira-tobacco
docker compose up -d
```

Lần đầu tải khoảng 500 MB image nên **mất vài phút**. Xong thì hiện `Started`.

### A3. Cài WordPress (một lần)

Mở **http://localhost:8888** — WordPress hiện màn hình cài đặt:

1. Chọn ngôn ngữ **English (United States)** → Continue
2. Site Title: `Annam Leaf`
3. Username: `admin` — Password: đặt gì cũng được, **ghi lại**
4. Your Email: điền email bất kỳ, ví dụ `admin@example.com`
5. Bấm **Install WordPress** → **Log In**

### A4. Bật plugin rồi bật giao diện

Trong wp-admin (menu bên trái):

1. **Plugins** → tìm **Annam Leaf Core** → bấm **Activate**.
   Đây là lúc nội dung mẫu được tạo: 7 bước quy trình, 4 chủng loại lá, 3 vùng trồng,
   5 trang, trang chủ và menu. **Phải làm bước này trước.**
2. **Appearance → Themes** → di chuột lên **Annam Leaf** → bấm **Activate**.
3. **Settings → Permalinks** → bấm **Save Changes** (không cần đổi gì).
   Bước này để `/process/` và `/our-leaf/` chạy được.

Xong. Mở **http://localhost:8888** để xem site.

### A5. Tắt / bật lại / xoá sạch

```sh
docker compose down       # tắt, giữ nguyên dữ liệu
docker compose up -d      # bật lại
docker compose down -v    # xoá sạch, cài lại từ đầu
```

Sửa file trong `wp-content/themes/annamleaf` rồi F5 trình duyệt là thấy ngay.

### A6. (Tuỳ chọn) Làm bước A3–A4 bằng dòng lệnh

Nếu ngại bấm qua giao diện:

```sh
docker compose run --rm cli wp core install --url=http://localhost:8888 --title="Annam Leaf" --admin_user=admin --admin_password=password --admin_email=admin@example.com
docker compose run --rm cli wp plugin activate annamleaf-core
docker compose run --rm cli wp theme activate annamleaf
docker compose run --rm cli wp rewrite flush --hard
```

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

## Cách C — wp-env (chỉ khi mạng vào được api.wordpress.org)

Repo có sẵn `.wp-env.json`. Cần Docker Desktop **và** Node.js:

```sh
npx @wordpress/env start
npx @wordpress/env run cli wp theme activate annamleaf
npx @wordpress/env run cli wp rewrite flush --hard
```

Site ở http://localhost:8888, admin `admin` / `password`.

Cách này gọn nhất **khi mạng thông**, nhưng `wp-env` bắt buộc phải hỏi `api.wordpress.org`
trước khi chạy — xem mục xử lý lỗi bên dưới nếu nó báo không kết nối được.

---

## Xem thử 8 thứ này để nghiệm thu

| # | Làm gì | Kỳ vọng thấy gì |
| --- | --- | --- |
| 1 | Mở trang chủ | Hero xanh đậm, dải 4 số liệu, 4 thẻ quy trình, 4 thẻ sản phẩm, vùng trồng, dải CTA |
| 2 | Bấm **Process** trên menu | Đoạn mở đầu + 7 bước, ảnh so le trái–phải |
| 3 | Bấm **Our Leaf** | 4 thẻ chủng loại, bảng quy cách, mục "Shipped in the form you need" và lịch mùa vụ |
| 3b | Bấm **About** và **Quality** | Trang đã có sẵn chữ đầy đủ, gồm khối 3 cột — không phải trang trống |
| 4 | Admin → **Company profile** → điền "Trading name" → Save | Tên công ty đổi ngay trên header và footer |
| 5 | Admin → **Process** → mở 1 bước → **Featured image** → upload ảnh bất kỳ | Ảnh thật thay chỗ minh hoạ vector, đúng trong khung đó |
| 6 | Admin → **Process** (danh sách) | Cột **Photo**: bước nào chưa có ảnh ghi "Not uploaded" kèm mô tả ảnh cần chụp |
| 7 | **Company profile** → bỏ tick "Mark empty fields" → Save | Các ô vàng `[NHƯ THẾ NÀY]` biến mất khỏi trang |
| 8 | **Company profile** → tick "Show the 18+ age gate" → Save → mở site ở cửa sổ ẩn danh | Hiện màn xác nhận 18+ trước khi vào site |

---

## Đã cài rồi, giờ cập nhật code mới

Nội dung mẫu chỉ được tạo **một lần** lúc kích hoạt plugin. Sau khi lấy code mới (git pull,
hoặc upload lại zip), vào **Company profile → Rebuild default content** để dựng lại 6 trang
theo bản mới. Nút này ghi đè chữ của các trang mẫu, không đụng ảnh và hồ sơ công ty.

Rồi vào **Settings → Permalinks** bấm **Save Changes** một lần.

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
được. Lỗi xảy ra **trước khi Docker được dùng đến**, nên không phải lỗi Docker.

**Ghim phiên bản trong `.wp-env.json` không chữa được lỗi này.** Nhìn stack trace sẽ thấy
`getDefaultConfig` → `parseCoreSource`: `wp-env` dựng cấu hình mặc định (`core: null`, tức
"bản mới nhất") **trước khi** đọc file cấu hình của bạn, nên nó vẫn hỏi mạng dù bạn đã ghi
rõ phiên bản.

Hai đường xử lý:

**Nhanh nhất: bỏ wp-env, dùng Cách A (Docker Compose) ở trên.** WordPress nằm sẵn trong
image Docker nên không cần hỏi wordpress.org.

**Hoặc sửa mạng**, chẩn đoán theo thứ tự:

1. **Mở trình duyệt** vào https://api.wordpress.org/core/version-check/1.7/
   Ra một đống chữ JSON = mạng ổn ở mức trình duyệt.
2. **Thử ở Terminal** (Windows dùng `curl.exe`, vì PowerShell đổi tên lệnh `curl`):
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
4. **Cả hai đều không vào được** → nhiều khả năng DNS nhà mạng chặn wordpress.org. Đổi DNS
   máy sang `1.1.1.1` và `8.8.8.8` rồi thử lại.

### wp-env: tải WordPress thủ công

Chỉ dùng khi bạn nhất định muốn ở lại với wp-env và đã sửa được lỗi mạng ở trên. Tự tải
WordPress bằng trình duyệt rồi trỏ `wp-env` vào thư mục đó:

1. Tải https://wordpress.org/latest.zip bằng trình duyệt.
2. Giải nén vào repo, sao cho có thư mục `wordpress/` chứa `wp-admin`, `wp-includes`…
   (thư mục này đã được `.gitignore` bỏ qua).
3. Sửa `.wp-env.json`, đổi dòng `"core"` thành:
   ```json
   "core": "./wordpress",
   ```
4. `npx @wordpress/env destroy` rồi `npx @wordpress/env start`.

Lưu ý: Docker vẫn cần mạng để tải image lần đầu. Nếu Docker cũng không tải được image thì
dùng **Cách B (LocalWP)**.

### `Cannot connect to the Docker daemon`

Docker Desktop chưa chạy. Mở app lên, đợi biểu tượng cá voi báo "Running", rồi chạy lại.

### `port is already allocated` / cổng 8888 bận

Có thứ khác đang chiếm cổng 8888.

- Cách A: sửa `docker-compose.yml`, đổi `"8888:80"` thành `"8889:80"`, rồi
  `docker compose up -d` và mở http://localhost:8889.
- Cách C: thêm `"port": 8889,` vào `.wp-env.json`.

## Nếu kẹt

Chụp màn hình lỗi và nói tôi biết bạn đang ở bước nào — kèm dòng lệnh cuối cùng bạn chạy
và thông báo lỗi đầy đủ.
