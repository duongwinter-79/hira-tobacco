# Kế hoạch đưa Annamleaf.com lên chạy thật

Tài liệu này là **kế hoạch**, đi từ hôm nay tới lúc khách gõ `annamleaf.com` và thấy website.
Thao tác chi tiết từng bước nằm ở [dua-len-mang.md](dua-len-mang.md).

Giả định: domain `annamleaf.com` khách **đã mua rồi**, còn hosting thì chưa có.

## Tổng quan

| Giai đoạn | Làm gì | Ai làm | Thời gian | Chi phí |
| --- | --- | --- | --- | --- |
| 0 | Chốt nội dung, thay ảnh mượn | Bạn + khách | 1–2 tuần chờ khách | 0 |
| 1 | Mua hosting, cài WordPress | Bạn | 1 giờ | 100–300k/tháng |
| 2 | Cài theme + plugin, nhập nội dung | Bạn | 1 giờ | 0 |
| 3 | Trỏ tên miền, bật HTTPS | Bạn + người giữ domain | 30 phút + 1–24h chờ DNS | 0 |
| 4 | Email tên miền, form báo giá chạy được | Bạn | 1 giờ | 0–150k/tháng |
| 5 | Kiểm tra trước khi công bố | Bạn | 30 phút | 0 |
| 6 | Bàn giao, sao lưu định kỳ | Bạn → khách | 1 giờ | 0 |

Giai đoạn 1–5 gói gọn trong **một ngày làm việc**, trừ thời gian chờ DNS. Nút thắt thật sự
là giai đoạn 0 — chờ nội dung và ảnh từ khách.

## Giai đoạn 0 — Phải xong trước khi công bố

Không có mấy thứ này thì đừng bật site cho công chúng:

- [ ] **Thay hết ảnh mượn.** Ảnh đang dùng lấy từ website của các công ty khác. Xem
      `credits.json`; khung nào còn tên trong đó là còn ảnh mượn. Đây là nhà máy, kho và
      sản phẩm của công ty khác — đăng lên annamleaf.com là nói với người mua rằng đó là
      của Annam Leaf. Thay bằng ảnh thật: `node tools/set-photos.mjs --from=<thư mục> --apply`
- [ ] **Tên pháp lý và mã số thuế** cho chân trang, nếu khách muốn hiện lại
- [ ] **Địa chỉ thật** — hiện đang để `Việt Nam`, không phải địa chỉ
- [ ] **Tên tỉnh vùng trồng** — hiện cũng đang để `Việt Nam`
- [ ] **Email `sales@annamleaf.com` phải tồn tại và có người đọc** (giai đoạn 4)
- [ ] **Luật sư/pháp chế rà soát nội dung.** Ngành thuốc lá bị hạn chế quảng cáo; site này
      là hồ sơ nguyên liệu bán cho nhà máy, câu chữ cần được duyệt
- [ ] **Tắt ô vàng**: Company profile → bỏ tick *Mark empty fields*
- [ ] **Bật cổng tuổi**: Company profile → tick *Show the 18+ age gate*

## Giai đoạn 1 — Hosting

**Chọn gì.** Hosting WordPress chia sẻ là đủ: site 6 trang, không bán hàng online, lưu lượng
thấp. Cần: PHP 8.1+, MySQL/MariaDB, SSL Let's Encrypt miễn phí, panel có cài WordPress một
chạm, sao lưu tự động.

| Hướng | Ví dụ | Ghi chú |
| --- | --- | --- |
| Hosting Việt Nam | AZDIGI, TinoHost, Vietnix | Hỗ trợ tiếng Việt, thanh toán nội địa, nhanh với người xem trong nước |
| Hosting quốc tế | Hostinger, SiteGround | Nhanh hơn với người mua nước ngoài — mà đó mới là khách của site này |
| VPS | DigitalOcean, Vultr | Rẻ hơn nhưng bạn phải tự vá bảo mật. Không nên nếu sau này bàn giao cho khách |

**Khuyến nghị:** hosting chia sẻ quốc tế có CDN, vì người mua là nhà máy ở nước ngoài.

Xong giai đoạn này phải có: một bản WordPress trắng chạy được ở địa chỉ tạm của nhà cung cấp,
biết tài khoản quản trị và có quyền vào panel để sửa DNS.

## Giai đoạn 2 — Đưa code lên

```sh
sh tools/package.sh
```

Ra `dist/annamleaf-core.zip` và `dist/annamleaf-theme.zip`. Trên wp-admin của hosting:

1. **Plugins → Add New → Upload** → `annamleaf-core.zip` → Activate — nội dung mẫu tự dựng
2. **Appearance → Themes → Add New → Upload** → `annamleaf-theme.zip` → Activate
3. **Settings → Permalinks** → Save Changes một lần
4. **Company profile** → đối chiếu với bản khai của khách, sửa những gì còn thiếu

Thứ tự **plugin trước, theme sau** — theme cần các loại nội dung do plugin đăng ký.

Ảnh mặc định đi kèm trong file theme zip, nên site vừa cài đã có ảnh, không cần upload gì.

## Giai đoạn 3 — Tên miền và HTTPS

Đây là phần cần phối hợp với người đang giữ tài khoản domain.

**Bước 1 — biết domain đang ở đâu.** Đăng ký ở đâu (Namecheap, GoDaddy, PA Vietnam,
Mắt Bão…), ai giữ tài khoản. Cần quyền sửa **DNS record**, không cần chuyển domain đi đâu cả.

**Bước 2 — chọn cách trỏ.**

| Cách | Làm gì | Khi nào dùng |
| --- | --- | --- |
| **Đổi nameserver** | Trỏ NS về nhà cung cấp hosting | Đơn giản nhất, hosting quản lý toàn bộ DNS |
| **Chỉ thêm A record** | Giữ nguyên NS, thêm bản ghi trỏ IP | Khi domain còn dùng cho email hay dịch vụ khác — **an toàn hơn** |

Nếu khách đang dùng email theo tên miền (`@annamleaf.com`), **đừng đổi nameserver** trừ khi
đã chép hết bản ghi MX sang chỗ mới. Đổi NS mà quên MX là mất email công ty.

**Bước 3 — bản ghi cần có** (thay `123.45.67.89` bằng IP hosting):

| Loại | Tên | Giá trị | TTL |
| --- | --- | --- | --- |
| A | `@` | `123.45.67.89` | 3600 |
| CNAME | `www` | `annamleaf.com` | 3600 |

**Bước 4 — chờ.** DNS lan truyền 1–24 giờ, thường dưới 1 giờ. Kiểm tra:

```sh
nslookup annamleaf.com
```

**Bước 5 — HTTPS.** Trong panel hosting bật **Let's Encrypt** cho cả `annamleaf.com` và
`www.annamleaf.com`. Chỉ bật được **sau khi** DNS đã trỏ đúng.

**Bước 6 — chốt địa chỉ chính.** wp-admin → **Settings → General**, đặt cả hai ô
WordPress Address và Site Address thành `https://annamleaf.com` (có `https`, không có `www`,
không có dấu `/` cuối). Rồi bật chuyển hướng `www` → không `www` trong panel hosting.

### Cloudflare — nên dùng, nhưng đọc kỹ ba chỗ

Cloudflare **không phải hosting** cho site này: Cloudflare Pages chỉ chạy HTML tĩnh, không
chạy PHP, nên WordPress không đặt lên đó được — giống Vercel. Nó đứng **trước** hosting, làm
DNS + CDN + tường lửa.

Bản miễn phí đủ dùng và đáng bật, nhất là khi chọn hosting đặt máy chủ ở Việt Nam mà người
mua lại ở nước ngoài:

| Được gì | Ý nghĩa với site này |
| --- | --- |
| CDN ~300 điểm | Ảnh và CSS phục vụ từ nước gần người mua, không kéo về tận VN |
| SSL miễn phí | Kể cả khi hosting chưa cấp cert |
| Chống DDoS, chặn bot | Không phải cấu hình gì |
| Quản lý DNS gọn | Sửa bản ghi thấy hiệu lực gần như tức thì |
| Analytics không cần script | Không phải nhúng Google Analytics |

**Chỗ thứ nhất — dùng Cloudflare là phải đổi nameserver.** Đây đúng là việc mục trên khuyên
tránh khi domain đang chạy email. Cloudflare có nhập tự động các bản ghi hiện có khi thêm
domain, nhưng **phải tự đối chiếu lại**: chụp màn hình toàn bộ bản ghi DNS cũ trước khi đổi,
rồi so từng dòng `MX`, `TXT` (SPF/DKIM/DMARC) sau khi nhập. Thiếu một dòng MX là mất email
công ty.

**Chỗ thứ hai — chế độ SSL phải là Full (strict).** Mặc định của Cloudflare là *Flexible*,
nghĩa là Cloudflare nói HTTPS với người xem nhưng nói HTTP với hosting. WordPress sẽ rơi vào
vòng lặp chuyển hướng, hoặc hiện cảnh báo nội dung hỗn hợp. Bật SSL trên hosting trước, rồi
đặt Cloudflare sang **Full (strict)**.

**Chỗ thứ ba — chỉ bật đám mây cam cho web.** Bản ghi nào phục vụ email (`mail`, `smtp`,
`webmail`) phải để **DNS only** (đám mây xám). Cloudflare không chuyển tiếp lưu lượng email;
bật cam cho những bản ghi đó là email chết.

Không cần đụng tới cache: Cloudflare mặc định **không** cache HTML, nên wp-admin và form báo
giá chạy bình thường. Chỉ khi nào muốn nhanh hơn nữa mới tính tới cache trang, và lúc đó phải
loại trừ `/wp-admin/*` cùng cookie đăng nhập.

Thứ tự làm: hosting chạy được trên IP tạm → trỏ domain thẳng về hosting → xác nhận site và
email đều ổn → **rồi mới** thêm Cloudflare. Thêm Cloudflare ngay từ đầu thì lúc hỏng không
biết hỏng ở tầng nào.

## Giai đoạn 4 — Email và form báo giá

Form báo giá trên trang Liên hệ gửi thư bằng `wp_mail()`. Mặc định PHP gửi thẳng, và thư
kiểu đó **hay vào spam hoặc bị chặn** — người mua gửi yêu cầu mà mình không nhận được thì
hỏng cả mục đích của website.

Phải làm hai việc:

1. **Hộp thư `sales@annamleaf.com` phải tồn tại.** Hosting thường cho tạo email theo tên
   miền miễn phí. Nếu khách muốn dùng giao diện Gmail thì mua Google Workspace
   (~150k/người/tháng) và cập nhật bản ghi **MX**.
2. **Gửi thư qua SMTP.** Cài plugin **WP Mail SMTP** hoặc **FluentSMTP** (miễn phí), khai
   báo SMTP của hộp thư trên, rồi **gửi thử một thư kiểm tra**.

Xong thì tự điền form trên site và xác nhận thư về đúng hộp — kể cả kiểm tra thư mục spam.

## Giai đoạn 5 — Kiểm tra trước khi công bố

- [ ] `https://annamleaf.com` mở được, không cảnh báo bảo mật
- [ ] `http://` và `www.` đều tự chuyển về `https://annamleaf.com`
- [ ] Sáu trang mở được, menu đúng, không trang nào lỗi 404
- [ ] Không còn ô vàng `[NHƯ THẾ NÀY]` nào
- [ ] Không còn khung ảnh nào hiện chữ **TEMPORARY**
- [ ] Điền thử form báo giá → thư về đúng hộp
- [ ] Xem trên điện thoại — menu, ảnh bìa, bảng
- [ ] Cổng tuổi 18+ hiện ra ở lần vào đầu tiên
- [ ] Google PageSpeed ≥ 80 trên di động
- [ ] Bật sao lưu tự động hằng ngày trong panel hosting

## Giai đoạn 6 — Bàn giao

- Giao tài khoản quản trị cho khách; tạo cho họ tài khoản **Editor** để sửa nội dung, giữ
  tài khoản **Administrator** cho bạn
- Gửi kèm hướng dẫn 5 việc dễ nhất (mục cuối trong bản khai thông tin)
- Chốt ai chịu trách nhiệm cập nhật WordPress và plugin — nên bật cập nhật tự động cho bản
  vá bảo mật
- Đặt lịch nhắc: 3 tháng nữa rà lại nội dung và ảnh

## Sau khi chạy

Cập nhật code về sau: chạy lại `sh tools/package.sh`, upload đè theme/plugin qua wp-admin,
rồi **Company profile → Rebuild default content** nếu có thay đổi nội dung mẫu. Nút đó ghi
đè chữ của các trang mẫu, không đụng ảnh và hồ sơ công ty.

Nếu muốn tự động hơn thì gắn deploy qua Git hoặc SFTP, nhưng với site 6 trang thì upload zip
mỗi vài tháng là đủ.
