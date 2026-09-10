# Chạy thử website trên máy bạn

Hướng dẫn cho người **chưa dùng WordPress bao giờ**. Có ba cách, chọn một:

| Cách | Cần gì | Chọn khi |
| --- | --- | --- |
| **A. Docker Compose** | Docker Desktop | Khuyến nghị. Không cần Node, không gọi wordpress.org |
| **B. LocalWP** | Không cần Terminal | Bạn không quen dòng lệnh |
| **C. wp-env** | Docker + Node | Chỉ khi mạng vào được `api.wordpress.org` |

> **Dùng Windows PowerShell:** chạy **mỗi lệnh một dòng**. Windows PowerShell 5.1 (bản mặc
> định của Windows) không hiểu `&&` để nối hai lệnh — nó báo
> `The token '&&' is not a valid statement separator`. Muốn nối thì dùng `;`, hoặc cài
> PowerShell 7.

---

## Bắt đầu nhanh — vừa clone repo về (macOS / Linux)

Toàn bộ chuỗi lệnh, chạy theo thứ tự. Chi tiết từng bước ở các mục bên dưới.

```sh
# 0. kiểm tra máy — thiếu Docker thì cài Docker Desktop và MỞ APP LÊN
docker --version
node -v

# 1. đúng nhánh
cd ~/Desktop/hira-tobacco
git branch --show-current            # phải là claude/annamleaf-website-plan-40gtio
git pull

# 2. bật WordPress (lần đầu tải ~600 MB, vài phút)
docker compose up -d
until curl -sf -o /dev/null http://localhost:8888; do sleep 3; done; echo "sẵn sàng"

# 3. cài WordPress bằng một lệnh, không cần qua màn hình wizard
docker compose run --rm cli wp core install \
  --url=http://localhost:8888 \
  --title="Annam Leaf" \
  --admin_user=admin \
  --admin_password=annamleaf \
  --admin_email=admin@example.com \
  --skip-email

# 4. bật theme + plugin, đặt permalink
docker compose run --rm cli wp theme activate annamleaf
docker compose run --rm cli wp plugin activate annamleaf-core
docker compose run --rm cli wp rewrite structure '/%postname%/'

# 5. luật rewrite cho Apache — bắt buộc khi cài bằng WP-CLI, xem ghi chú bên dưới
docker compose cp docker/htaccess wordpress:/var/www/html/.htaccess
```

Bước 5 không bỏ được. `wp rewrite structure --hard` chạy trong container `cli`, ở đó
WordPress không thấy mình đang chạy dưới Apache nên ghi ra một `.htaccess` **rỗng** — trang
chủ mở được còn `/about/` trả 404. Chép file có sẵn trong repo vào là xong.

Xong: **http://localhost:8888** · quản trị **http://localhost:8888/wp-admin** (`admin` /
`annamleaf`). Bật plugin là nội dung mẫu tự dựng — 6 trang, 7 bước quy trình, sản phẩm, vùng
trồng.

Ba việc tuỳ chọn sau đó:

```sh
# ảnh mặc định — chọn bằng mắt, xem mục "Ảnh mặc định đi kèm theme"
export PEXELS_API_KEY="khoá-của-bạn"
node tools/fetch-photos.mjs
open tools/photo-review.html
node tools/fetch-photos.mjs --apply

# ảnh tham chiếu làm brief chụp — xem mục cuối tài liệu này
npm install --save-dev playwright
npx playwright install chromium
node tools/reference-shots.mjs
open tools/reference/index.html

# kiểm tra code (macOS không có sẵn PHP)
brew install php
sh tools/lint.sh
sh tools/package.sh
```

Tắt và làm lại:

```sh
docker compose down        # tắt, giữ dữ liệu
docker compose down -v     # xoá sạch database, cài lại từ bước 2
```

**Windows:** thay `open` bằng `start`, và chạy mỗi lệnh một dòng (xem cảnh báo PowerShell ở
trên). Bước 2 không có `curl` thì cứ mở http://localhost:8888 bằng trình duyệt, thấy trang
là chạy tiếp được.

**Đừng chạy `npx @wordpress/env`** — đường đó hỏng trên mạng không vào được
`api.wordpress.org`. File `.wp-env.json` còn trong repo chỉ là di tích của Cách C.

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
   Đây là lúc nội dung mẫu được tạo: 7 bước quy trình, 3 sản phẩm, vùng trồng Cao Bằng,
   6 trang, trang chủ và menu. **Phải làm bước này trước.**
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
| 1 | Mở trang chủ | Hero xanh đậm, dải 4 số liệu, 4 thẻ quy trình, 3 thẻ sản phẩm, vùng trồng, dải CTA |
| 2 | Bấm **Process** trên menu | Đoạn mở đầu + 7 bước, ảnh so le trái–phải |
| 3 | Bấm **Our Leaf** | 3 thẻ sản phẩm, bảng quy cách, mục "Shipped in the form you need" và lịch mùa vụ |
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

## Ảnh mặc định đi kèm theme

Cách này lưu ảnh **thẳng vào repo**, nên cài ở đâu cũng có ảnh sẵn, không phải bấm gì trong
wp-admin. Cần Node 18+ (`node -v`).

Quy trình ba bước — **bạn chọn ảnh, không phải máy chọn**:

```powershell
node tools/fetch-photos.mjs
start tools\photo-review.html
node tools/fetch-photos.mjs --apply
```

(macOS/Linux đổi `start tools\photo-review.html` thành `open tools/photo-review.html`.)

1. **Bước 1** tìm ảnh ở nhiều thư viện giấy phép tự do, lọc và chấm điểm, rồi sinh ra một
   trang HTML tại `tools/photo-review.html`. **Chưa tải ảnh nào về.**
2. **Bước 2** mở trang đó trong trình duyệt: mỗi khung ảnh (ảnh bìa + 7 bước quy trình)
   có tối đa 8 ứng viên hiện ảnh thật, kèm điểm, nguồn, giấy phép và link tới trang gốc.
   Tích chọn ảnh bạn thấy đúng, bấm **Save picks** — trình duyệt tải xuống
   `photo-picks.json`. Chuyển file đó vào thư mục `tools/` (hoặc cứ để trong Downloads,
   script tự tìm).
   Khung nào không có ảnh nào ưng thì để nguyên — theme sẽ vẽ hình minh hoạ, **hình vẽ đúng
   chỗ vẫn hơn ảnh sai chủ đề**.
3. **Bước 3** tải đúng những ảnh bạn đã tích vào
   `wp-content/themes/annamleaf/assets/photos/`, kèm `credits.json` ghi tác giả và giấy phép.

Sau đó commit:

```powershell
git add wp-content/themes/annamleaf/assets/photos
git commit -m "Add default photos"
```

| Nguồn | Cần khoá API | Ghi chú |
| --- | --- | --- |
| Wikimedia Commons | Không | Nhiều tư liệu lịch sử, ít ảnh hiện đại |
| Openverse | Không | Cổng tìm CC của chính WordPress, gom cả Flickr |
| Pexels | `PEXELS_API_KEY` | Ảnh stock hiện đại, chất lượng tốt nhất |
| Unsplash | `UNSPLASH_ACCESS_KEY` | Như trên |

Lấy khoá miễn phí ở pexels.com/api hoặc unsplash.com/developers rồi:

```powershell
$env:PEXELS_API_KEY="khoá-của-bạn"
node tools/fetch-photos.mjs
```

Không có khoá thì script vẫn chạy với hai nguồn đầu, nhưng danh sách sẽ nghèo hơn nhiều —
Commons chủ yếu là ảnh lưu trữ cũ. **Nên lấy khoá Pexels**, mất khoảng hai phút.

**Bộ lọc làm gì.** Loại thẳng nếu: là tranh khắc / bưu thiếp / hiện vật bảo tàng, ảnh lưu
trữ đen trắng, có nhắc tới năm trước 1995, dính từ quân đội / sân bay, ảnh dọc, ảnh
panorama, hoặc dưới 1400px. Quan trọng nhất: mỗi khung yêu cầu **nhiều nhóm từ khoá cùng
lúc** — khung vận chuyển phải có *container* **và** một từ về cảng, khung sấy phải có
*tobacco* **và** một từ về sấy. Một từ đơn lẻ chính là lý do trước đây một tấm ảnh lính Mỹ
lọt vào khung vận chuyển: nó có chữ *terminal* (nhà ga sân bay).

Nhưng bộ lọc chỉ đọc được **chữ mô tả cạnh bức ảnh**, nó không nhìn thấy ảnh. Vì vậy bước
duyệt bằng mắt ở trên là bắt buộc, không phải tuỳ chọn.

Tuỳ chọn khác:

```sh
node tools/fetch-photos.mjs --show=5         # in 5 ứng viên đầu bảng mỗi khung ra terminal
node tools/fetch-photos.mjs --slot=stage-5   # làm lại đúng một khung
node tools/fetch-photos.mjs --apply --force  # tải đè ảnh đã có
node tools/fetch-photos.mjs --picks=D:/tai-ve/photo-picks.json --apply
node tools/fetch-photos.mjs --auto           # bỏ qua bước duyệt, lấy ảnh cao điểm nhất
node tools/test-photo-scoring.mjs            # kiểm tra bộ lọc bằng chính các ảnh sai lần trước
```

`--auto` để đó cho trường hợp gấp; đừng dùng nó rồi commit thẳng.

Thứ tự ưu tiên khi hiển thị một khung ảnh:

1. **Featured image** của bài đó — ảnh thật của khách, luôn thắng
2. **Ảnh mặc định** trong `assets/photos/` — ảnh tạm đi kèm theme
3. **Hình vẽ minh hoạ** — khi không có cả hai

Xoá ảnh tạm = xoá file trong thư mục đó, giao diện tự quay về hình vẽ.

## Đưa ảnh vào website — không cần vào wp-admin

Ảnh nằm trong `wp-content/themes/annamleaf/assets/photos/`, mà thư mục đó nằm trong repo.
Bỏ file đúng tên vào đó là **mọi bản cài mới đều có ảnh**, không cần thư viện ảnh, không cần
đặt Featured image cho từng mục.

`tools/set-photos.mjs` làm việc chép đó, và tự đoán ảnh nào vào khung nào theo tên file:

```sh
node tools/set-photos.mjs --list                              # 12 khung, khung nào đã có ảnh
node tools/set-photos.mjs --from=~/Downloads/anh-khach        # xem nó định làm gì
node tools/set-photos.mjs --from=~/Downloads/anh-khach --apply
git add wp-content/themes/annamleaf/assets/photos
git commit -m "Anh cua khach"
```

Không có `--apply` thì **không ghi gì**, chỉ in ra bản nháp.

**Cách nó đoán.** Đọc tên file, bỏ dấu tiếng Việt, so với từ khoá của từng khung — cả tiếng
Việt lẫn tiếng Anh: `Lo say lua vang.jpg` → `stage-4`, `vuon uom khay.jpg` → `stage-1`,
`cong thuoc la.jpg` → `leaf-3`. File đặt đúng tên khung (`stage-6.jpg`) thì vào thẳng khung
đó, không cần đoán. File nào không đoán được, hoặc lẫn giữa hai khung, nó báo ra chứ không
đoán bừa — lúc đó chỉ định thẳng:

```sh
node tools/set-photos.mjs "stage-7=~/Downloads/IMG_20260910_113355.jpg" --apply
```

Bỏ ảnh khỏi một khung (quay về hình vẽ minh hoạ):

```sh
node tools/set-photos.mjs --clear=leaf-2 --apply
```

**Nó cũng kiểm tra giúp:** in ra kích thước thật và dung lượng, cảnh báo nếu ảnh dưới 1400px
(sẽ mờ), ảnh dọc (khung là ảnh ngang nên bị cắt nhiều) hoặc nặng quá 1,5 MB. Giữ nguyên định
dạng gốc — theme đọc `.jpg`, `.png`, `.webp`.

Và khi một khung nhận ảnh của khách, dòng ghi công **TEMPORARY** của ảnh mượn ở khung đó tự
bị gỡ khỏi `credits.json`.

---

## Biến ảnh đã upload thành ảnh mặc định của bản cài

Ảnh khách upload nằm trong `wp-content/uploads/` — thư mục này **không** nằm trong repo, nên
cài lại ở máy khác là mất. `tools/export-photos.php` chép chúng vào theme:

```sh
docker compose run --rm cli wp eval-file /repo/tools/export-photos.php --dry-run
docker compose run --rm cli wp eval-file /repo/tools/export-photos.php
```

Script duyệt trang chủ, các mục Process, Our Leaf và Regions theo đúng thứ tự hiển thị, lấy
**Featured image** của từng mục và ghi vào `wp-content/themes/annamleaf/assets/photos/` với
đúng tên khung: `home`, `stage-1..7`, `leaf-1..N`, `region`. Thư mục đó nằm trong repo, nên:

```sh
git add wp-content/themes/annamleaf/assets/photos
git commit -m "Ảnh mặc định của khách"
```

Từ đó về sau, **cài mới ở bất cứ đâu là đã có sẵn ảnh** — không cần thư viện ảnh, không cần
bản ghi database. Mục nào chưa đặt Featured image thì script báo ra và bỏ qua.

Giữ nguyên định dạng gốc — theme đọc được `.jpg`, `.jpeg`, `.png` và `.webp`.

---

## Ảnh tạm từ Wikimedia Commons (cách thứ hai, vào thư viện ảnh)

Cách này đưa ảnh vào **thư viện ảnh của WordPress** thay vì vào repo — tiện khi muốn chọn
ảnh khác cho từng mục ngay trong wp-admin. Vào **Company profile → Temporary photographs →
Import temporary photographs**. Nó tải ảnh có giấy phép tự do từ Wikimedia Commons (cánh đồng ở
Cao Bằng, lò sấy, phân loại lá, cảng container) và gán làm ảnh đại diện cho các mục còn
trống. Mỗi ảnh hiện kèm dòng ghi công **TEMPORARY · tên tác giả · giấy phép** ngay trên ảnh
— vừa đúng yêu cầu giấy phép, vừa để không ai nhầm đó là ảnh của công ty.

Muốn **xem trước ảnh nào sẽ được tải** thì vào **Company profile → Photo preview**: màn
hình này hiện 3 ảnh ứng viên cho từng khung, kèm tên tác giả và giấy phép, bấm **Use this
one** để chọn đúng ảnh bạn thích thay vì để máy tự chọn.

Cần server ra được internet, chạy mất tới một phút. Bấm **Remove temporary photographs**
để xoá sạch; hoặc cứ upload ảnh thật vào Featured image, ảnh tạm sẽ bị thay.

**Ảnh tạm không được để lại khi go-live** — chúng là ảnh chung của ngành, không phải của
công ty này.

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

## Cho khách xem bản chạy trên máy bạn

Máy bạn đang chạy WordPress ở `localhost:8888` — chỉ mình bạn vào được. Muốn khách xem, hoặc
muốn gắn luôn `annamleaf.com` vào đó, dùng **tunnel**: một dịch vụ mở đường từ internet về
cổng 8888 trên máy bạn, không cần IP tĩnh, không cần mở cổng router.

> **Đây là cách xem tạm, không phải cách chạy thật.** Site chỉ sống khi máy bạn bật và Docker
> đang chạy. Tắt máy, mất mạng, hoặc Windows Update khởi động lại là khách thấy lỗi.
>
> Cách 1 cho một link ngẫu nhiên, dựng trong hai phút, không đụng tới tên miền — dùng khi chỉ
> cần khách xem rồi góp ý. Cách 2 gắn thẳng `annamleaf.com` vào máy bạn, cần đổi nameserver
> sang Cloudflare và nên khoá wp-admin lại.

### Cách 1 — Link tạm, không đụng tới tên miền

Cài `cloudflared` một lần: https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/downloads/
(Windows có bản `.msi`; macOS `brew install cloudflared`).

```sh
cloudflared tunnel --url http://localhost:8888
```

Nó in ra một địa chỉ dạng `https://<chữ-ngẫu-nhiên>.trycloudflare.com`.

**Chuẩn bị một lần duy nhất** — bật chế độ tự nhận địa chỉ cho WordPress:

```powershell
cd C:\Users\Admin\Desktop\hira-tobacco
$env:SITE_URL="auto"
docker compose up -d
```

`auto` nghĩa là WordPress lấy địa chỉ từ chính request đang tới. Sau bước này, **mở tunnel
bao nhiêu lần cũng được, link đổi bao nhiêu lần cũng được** — không phải gõ lại gì, không
phải dựng lại container. `localhost:8888` vẫn chạy song song bình thường.

Không có bước này thì WordPress in `localhost:8888` vào mọi thẻ CSS và ảnh, trình duyệt phía
khách không tải được, trang hiện ra trơ trụi không định dạng.

> **Đừng đặt `SITE_URL` bằng một địa chỉ tunnel cụ thể.** Link `trycloudflare.com` chết ngay
> khi bạn tắt cloudflared. Lần sau mở tunnel mới mà container vẫn giữ địa chỉ cũ thì WordPress
> ép mọi request về địa chỉ đã chết đó, và trình duyệt báo **`DNS_PROBE_FINISHED_NXDOMAIN`**.
> Dùng `auto` là hết hẳn chuyện này.

Gửi link cho khách. Xong việc thì Ctrl+C tắt cloudflared — site vẫn chạy ở `localhost:8888`.

Muốn trả về hoàn toàn như cũ:

```powershell
Remove-Item Env:\SITE_URL
docker compose up -d
```

Không bắt buộc: `auto` để nguyên cũng không ảnh hưởng gì khi chạy trên máy.

#### Vẫn vỡ CSS, hoặc bị chuyển hướng về địa chỉ cũ?

Kiểm tra container thật sự đang giữ giá trị nào:

```powershell
docker compose exec wordpress printenv SITE_URL
docker compose exec wordpress ls /var/www/html/wp-content/mu-plugins
```

Lệnh đầu phải in `auto`. In ra một địa chỉ `trycloudflare.com` cũ nghĩa là container chưa
được dựng lại — `docker compose down` rồi `up -d`. In ra rỗng thì bạn đặt biến ở cửa sổ
PowerShell này nhưng chạy `docker compose` ở cửa sổ khác; biến môi trường không đi theo cửa
sổ.

Lệnh sau phải liệt kê `annamleaf-site-url.php`.

Cách chắc chắn nhất, dùng khi rối:

```powershell
docker compose down
$env:SITE_URL="auto"
docker compose up -d --force-recreate
docker compose exec wordpress printenv SITE_URL
```

### Cách 2 — Gắn thẳng `annamleaf.com` vào máy bạn

Domain đang đăng ký ở **Squarespace**. Squarespace cho đổi nameserver, nên làm được.

Chuyển DNS sang Cloudflare **một lần bây giờ** là hợp lý: giai đoạn tunnel dùng nó, và khi
lên Hostinger thì chỉ đổi bản ghi `A` trong Cloudflare chứ không phải quay lại Squarespace.
Một lần đổi, không phải hai — khác với lo ngại ở mục dưới.

Cloudflare Tunnel **bắt buộc** DNS phải nằm ở Cloudflare: `cloudflared tunnel route dns` tạo
một bản ghi trỏ tới `<id>.cfargotunnel.com`, và tên đó chỉ phân giải được bên trong DNS của
Cloudflare. Giữ DNS ở Squarespace thì phải dùng ngrok trả phí (~$8–10/tháng) — nó cho gắn tên
miền riêng bằng `CNAME` từ bất kỳ nhà cung cấp DNS nào.

#### B1. Đưa domain vào Cloudflare

1. Tạo tài khoản Cloudflare (gói Free), **Add a site** → `annamleaf.com`
2. Cloudflare quét và nhập các bản ghi DNS đang có ở Squarespace
3. **Chụp màn hình toàn bộ bản ghi DNS ở Squarespace trước khi sang bước sau**, rồi đối chiếu
   từng dòng với danh sách Cloudflare vừa nhập — đặc biệt `MX` và `TXT`. Thiếu một dòng `MX`
   là mất email của domain
4. Cloudflare đưa ra **hai nameserver** dạng `xxx.ns.cloudflare.com`

#### B2. Đổi nameserver ở Squarespace

Trong tài khoản Squarespace: **Domains** → chọn `annamleaf.com` → **DNS** (hoặc
*Nameservers* / *Advanced DNS settings*) → chọn dùng **custom nameservers**, xoá nameserver
Squarespace và điền hai của Cloudflare.

Chờ 15 phút đến vài giờ. Cloudflare gửi email khi domain đã active. Kiểm tra:

```sh
nslookup -type=ns annamleaf.com
```

> Nếu domain này đang chạy một website Squarespace thì đổi nameserver sẽ **tắt** website đó.

#### B3. Tạo tunnel có tên

Link `trycloudflare.com` đổi mỗi lần chạy lại, nên phải dùng tunnel có tên:

```sh
cloudflared tunnel login
cloudflared tunnel create annamleaf
cloudflared tunnel route dns annamleaf annamleaf.com
cloudflared tunnel route dns annamleaf www.annamleaf.com
```

`login` mở trình duyệt để cấp quyền. `route dns` tự tạo bản ghi trong Cloudflare — không phải
thêm tay.

Tạo file cấu hình (Windows: `C:\Users\Admin\.cloudflared\config.yml`):

```yaml
tunnel: annamleaf
credentials-file: C:\Users\Admin\.cloudflared\<tunnel-id>.json

ingress:
  - hostname: annamleaf.com
    service: http://localhost:8888
  - hostname: www.annamleaf.com
    service: http://localhost:8888
  - service: http_status:404
```

Chạy:

```sh
cloudflared tunnel run annamleaf
```

Muốn nó tự chạy lại sau khi khởi động máy: `cloudflared service install`.

#### B4. Báo địa chỉ cho WordPress

```powershell
$env:SITE_URL="https://annamleaf.com"
docker compose up -d
```

Thiếu bước này thì mọi link và ảnh vẫn trỏ `localhost:8888`.

#### B5. Cloudflare SSL

**SSL/TLS → Overview**: đặt **Full**. Tunnel đã mã hoá đoạn từ Cloudflare về máy bạn, nên
*Flexible* là thừa và gây vòng lặp chuyển hướng.

#### B6. Chặn wp-admin khỏi internet

Đây là phần bù cho rủi ro lớn nhất của cách này — một bản WordPress quản trị mở ra internet,
chạy trên máy cá nhân, không có bản vá của nhà cung cấp hosting.

**Cloudflare Zero Trust → Access → Applications** → **Add an application** → *Self-hosted*:

- Application domain: `annamleaf.com`, path `wp-admin`
- Policy: **Allow**, tiêu chí **Emails** → điền email của bạn
- Thêm một application nữa cho path `wp-login.php`

Miễn phí tới 50 người dùng. Sau đó ai vào `/wp-admin` cũng phải xác thực email trước khi
WordPress kịp thấy request. Trang công khai không bị ảnh hưởng.

Và vẫn phải:

```powershell
docker compose run --rm cli wp user update admin --user_pass="<mật khẩu dài, ngẫu nhiên>"
```

#### Cần biết về cách này

- **Site chỉ sống khi máy bạn bật và Docker chạy.** Tắt máy, mất mạng, Windows Update khởi
  động lại — khách vào `annamleaf.com` thấy lỗi 502 của Cloudflare
- Nên giữ **Hide from search engines** bật cho tới khi lên hosting thật
- Khi chuyển sang Hostinger: tắt tunnel, đổi bản ghi `A` của `@` trong Cloudflare trỏ về IP
  Hostinger, xoá bản ghi tunnel, bỏ `SITE_URL`. Squarespace không phải đụng tới nữa

### Cách 3 — Mở cổng router

Về lý thuyết được, thực tế hiếm khi chạy ở Việt Nam: hầu hết nhà mạng dùng CGNAT nên máy bạn
không có IP công khai riêng, và nhiều nhà mạng chặn cổng 80/443. Bỏ qua.

### Nhớ khi dùng tunnel

- Công tắc **Hide from search engines** trong Company profile phải **bật** — mặc định đã bật.
  Không thì Google có thể lập chỉ mục bản demo chạy trên máy bạn.
- Đổi mật khẩu `admin`. Mật khẩu `annamleaf` trong hướng dẫn cài là để chạy trên máy, không
  phải để mở ra internet.
- Link `trycloudflare.com` đổi mỗi lần chạy lại. Cần link cố định thì phải là tunnel có tên
  (Cách 2) hoặc tài khoản ngrok trả phí.

---

## Xử lý lỗi thường gặp

### Trang chủ mở được, nhưng `/about/` trả về "Not Found" của Apache

Lỗi này là của **Apache**, không phải WordPress — nhận ra qua dòng
`Apache/2.4.x (Debian) Server at localhost Port 8888`. Trang chủ không cần rewrite nên vẫn
chạy; mọi đường dẫn khác thì cần.

Hai nguyên nhân, phân biệt bằng hai lệnh:

```sh
docker compose exec wordpress cat /var/www/html/.htaccess
docker compose exec wordpress grep -A3 "Directory /var/www" /etc/apache2/apache2.conf
```

**1. Không có file `.htaccess`** (lệnh đầu báo `No such file`) — WordPress chưa ghi được nó:

```sh
docker compose run --rm cli wp rewrite structure '/%postname%/' --hard
docker compose run --rm cli wp rewrite flush --hard
```

**Hoặc có file nhưng rỗng giữa `# BEGIN WordPress` và `# END WordPress`** — đây là trường
hợp hay gặp nhất khi cài bằng WP-CLI: nó chạy ngoài Apache nên WordPress tưởng không có
mod_rewrite và ghi ra khối trống. Chép bản có sẵn trong repo vào:

```sh
docker compose cp docker/htaccess wordpress:/var/www/html/.htaccess
```

**2. Có `.htaccess` nhưng Apache bỏ qua nó** — lệnh thứ hai in ra `AllowOverride None`.
Debian đặt như vậy theo mặc định. Repo đã có sẵn `docker/apache-permalinks.conf` để sửa,
chỉ cần lấy code mới rồi dựng lại container:

```sh
git pull
docker compose down
docker compose up -d
```

`down` (không có `-v`) chỉ tắt container, **không mất dữ liệu**.

Kiểm tra lại:

```sh
curl -I http://localhost:8888/about/
```

Phải là `HTTP/1.1 200 OK`. Nếu ra `404` thì dán kết quả hai lệnh chẩn đoán ở trên.



### Không thấy menu "Company profile" trong wp-admin

Menu đó do **plugin** tạo ra, nên nó chỉ hiện khi plugin đang bật.

1. Mở thẳng **http://localhost:8888/wp-admin/admin.php?page=annamleaf-settings**
   - Vào được → plugin đang chạy, chỉ là bạn chưa nhìn thấy menu. Nó nằm ở **thanh bên
     trái**, ngay dưới **Pages**, cùng cụm với **Process**, **Our Leaf**, **Regions**.
   - Báo lỗi quyền hoặc trang trắng → plugin chưa bật, làm bước 2.
2. Vào **Plugins**, tìm **Annam Leaf Core**, bấm **Activate**.
   Bật xong sẽ thấy link **Company profile** ngay trên dòng của plugin đó.
3. Nếu trong danh sách Plugins **không có** Annam Leaf Core: thư mục plugin chưa nằm đúng
   chỗ. Với Docker Compose thì nó được mount sẵn — kiểm tra bạn đang chạy `docker compose`
   từ đúng thư mục repo. Với LocalWP thì copy lại `wp-content/plugins/annamleaf-core` vào
   `app/public/wp-content/plugins/`.

Khi theme đang bật mà plugin thì không, wp-admin sẽ hiện luôn một dòng cảnh báo vàng
nhắc bạn bật plugin.


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

## Lấy ảnh site tham chiếu về làm brief chụp ảnh

`tools/reference-shots.mjs` mở Chromium, đi qua các site tham chiếu, **tải toàn bộ ảnh về
`tools/reference/`**, chụp lại toàn trang để xem bố cục, rồi dựng một bảng để gắn từng ảnh
với khung tương ứng trên site Annam Leaf.

Cài một lần:

```sh
npm install --save-dev playwright
npx playwright install chromium
```

Chạy:

```sh
node tools/reference-shots.mjs
open tools/reference/index.html
```

Trong bảng: mỗi ảnh có một ô chọn khung (`home`, `stage-1..7`, `leaf-1..4`, `region`, hero
các trang — đúng tên khung trong [shot-list.md](shot-list.md)) và một ô ghi chú *"chụp lại thế nào ở nhà máy mình"*. Gắn xong
bấm **Tổng hợp brief** — nội dung sinh ra dán được vào `docs/shot-brief.md` hoặc gửi thẳng
cho người chụp ảnh.

### Danh sách site nằm ở `tools/reference-sites.json`

Không phải trong code. Thêm site mới thì thêm một mục vào file đó:

```json
{
	"name": "Tên hiển thị",
	"start": "https://example.com/",
	"depth": 1,
	"maxPages": 14,
	"include": "example\\.com",
	"exclude": "(/tag/|/category/|\\.pdf$)"
}
```

| Khoá | Ý nghĩa |
| --- | --- |
| `start` | Trang bắt đầu. Chỉ cần một trang, script tự tìm các trang khác |
| `depth` | Đi xa bao nhiêu cấp link từ trang đó. `0` = chỉ trang này, `1` là đủ cho hầu hết site |
| `maxPages` | Trần số trang, để không bò cả website |
| `include` | Regex: chỉ theo link khớp. Bỏ trống thì mặc định là cùng tên miền |
| `exclude` | Regex: bỏ qua link khớp — tag, category, phân trang, PDF |
| `minWidth` | Ngưỡng chiều rộng riêng cho site này |

Hai khoá ở cấp ngoài cùng áp cho mọi site: `minWidth` và `skipUrl` (regex loại logo, icon,
favicon theo tên file).

### Tuỳ chọn dòng lệnh

```sh
node tools/reference-shots.mjs --list              # xem danh sách, không chạy gì
node tools/reference-shots.mjs --only=mibica       # chỉ site khớp chuỗi này
node tools/reference-shots.mjs --url=https://…     # chỉ URL này, bỏ qua danh sách
node tools/reference-shots.mjs --depth=2 --max=25  # đè cấu hình, đi sâu hơn
node tools/reference-shots.mjs --min=700           # bỏ ảnh nhỏ hơn 700px
node tools/reference-shots.mjs --fresh             # xoá kết quả cũ, làm lại từ đầu
node tools/reference-shots.mjs --sites=khac.json   # dùng danh sách khác
```

**Kết quả cộng dồn qua các lần chạy.** Hôm nay lấy một site, mai lấy site khác, bảng tham
chiếu vẫn hiện đủ cả hai. Trang nào vào lại thì bản ghi cũ của trang đó bị thay, phần còn
lại giữ nguyên. Muốn xoá sạch thì `--fresh`.

### Script lọc ảnh thế nào

- Lấy cả `<img>`, ảnh nền CSS (ảnh bìa thường là ảnh nền), và các thuộc tính lazy-load
  (`data-src`, `data-original`, `data-lazy-src`) mà thư viện lazy không kịp nạp
- Cuộn hết trang trước khi đọc, để lazy-load nhả ảnh ra
- **Đọc kích thước thật từ header file** (PNG/JPEG/GIF/WebP) chứ không tin số DOM báo — ảnh
  lazy khai kích thước của placeholder 1×1, ảnh nền khai kích thước cái khung nó lấp
- Bỏ file dưới 12 KB (placeholder, ảnh nền mờ) và ảnh trùng URL giữa các trang
- Nghỉ 1,2 giây giữa các trang — đây là máy chủ của người khác

### Ảnh trong `tools/reference/` không được đưa lên website

Thư mục này nằm trong `.gitignore` và **không** nằm trong theme, có lý do:

- Ảnh có bản quyền của công ty đã chụp chúng.
- Quan trọng hơn: đó là **nhà máy, kho và sản phẩm của công ty khác**. Đưa lên
  annamleaf.com tức là nói với người mua rằng đó là nhà máy của Annam Leaf. Ngành nguyên
  liệu thuốc lá ở Việt Nam nhỏ, người mua và các nhà chế biến biết nhau — bị nhận ra là mất
  uy tín, chưa kể rủi ro pháp lý.

Ba đường có ảnh dùng được, theo thứ tự nên làm:

1. **Ảnh thật của khách.** Cầm brief xuống nhà máy một buổi là đủ nhóm A và B. Điện thoại
   đời mới chụp ban ngày dùng được.
2. **Ảnh stock có bản quyền thương mại.** Adobe Stock / Getty có ảnh chế biến lá thuốc; mua
   giấy phép rồi upload vào Media Library như ảnh thường.
3. **Ảnh giấy phép tự do** qua `tools/fetch-photos.mjs` — miễn phí, nhưng chỉ nên coi là ảnh
   tạm cho tới khi có ảnh thật.
