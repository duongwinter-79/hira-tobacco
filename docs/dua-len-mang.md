# Đưa site lên mạng cho người khác xem

Có hai nhu cầu khác nhau, làm khác nhau:

| Nhu cầu | Dùng cách nào | Chi phí | Thời gian |
| --- | --- | --- | --- |
| Cho khách xem thử vài hôm | Link tạm (mục 1) | 0đ | 5–15 phút |
| Site chạy thật ở annamleaf.com | Hosting + domain (mục 2) | ~100–300k/tháng | 1–2 giờ |

Cả hai đều bắt đầu bằng hai file zip:

```sh
sh tools/package.sh
```

Ra `dist/annamleaf-core.zip` và `dist/annamleaf-theme.zip`. Lên WordPress nào cũng chỉ cần
upload hai file này — **plugin trước, theme sau**.

---

## 1. Cho khách xem thử (chưa cần domain)

### Cách nhanh nhất: Live Link của LocalWP

Nếu bạn đang chạy site bằng **LocalWP** (Cách B trong `chay-thu-local.md`):

1. Mở Local, chọn site.
2. Bấm nút **Live Link** ở góc trên (bật lên).
3. Local cho một địa chỉ dạng `https://xxxx.wp.local-site.com` — gửi link đó cho khách.

Link sống chừng nào máy bạn bật và Local đang chạy. Đủ để khách xem và góp ý, **không phải
hosting thật** — đừng dùng cho khách hàng quốc tế thật sự truy cập.

### Hoặc: WordPress sandbox miễn phí trên mạng

Dùng **InstaWP** (instawp.com) hoặc **TasteWP** (tastewp.com) — tạo một site WordPress rỗng
miễn phí trong ~30 giây, có sẵn link công khai:

1. Tạo site mới, vào wp-admin của nó.
2. **Plugins → Add New → Upload Plugin** → `annamleaf-core.zip` → Install → **Activate**.
3. **Appearance → Themes → Add New → Upload Theme** → `annamleaf-theme.zip` → Install → **Activate**.
4. **Settings → Permalinks** → Save Changes.
5. Gửi link cho khách.

Site miễn phí thường tự xoá sau vài ngày. Đủ cho một vòng duyệt thiết kế.

### Hoặc: dựng luôn trên hosting thật, chưa trỏ domain chính

Cách này tốn tiền hosting nhưng **không phải làm lại** khi go-live: dựng trên
`preview.annamleaf.com` (hoặc domain tạm hosting cấp), khách duyệt xong thì đổi sang
`annamleaf.com`. Xem mục 2.

---

## 2. Chạy thật ở annamleaf.com

### 2.1 Chọn hosting

WordPress cần **PHP 8.0+** và **MySQL/MariaDB** — hosting tĩnh (Cloudflare Pages, Netlify,
Vercel) **không chạy được**.

| Loại | Ví dụ | Giá tham khảo | Ghi chú |
| --- | --- | --- | --- |
| Shared hosting VN | AZDIGI, Vietnix, Tino | ~50–150k/tháng | Rẻ, đủ cho site giới thiệu |
| Shared quốc tế | Hostinger, SiteGround | ~$3–8/tháng | Nên chọn datacenter Singapore |
| Managed WordPress | Cloudways, Kinsta | ~$11–35/tháng | Nhanh, tự backup, đắt hơn |

Khách mua hàng là nhà máy ở nước ngoài, nên ưu tiên server **Singapore** hoặc bật CDN
(Cloudflare miễn phí) để họ mở nhanh. Giá trên là tham khảo, cần kiểm tra lại khi mua.

Yêu cầu tối thiểu khi chọn gói: PHP 8.0+, dung lượng ≥ 5 GB, **SSL miễn phí (Let's Encrypt)**,
backup tự động, và cho tạo email theo tên miền (`sales@annamleaf.com`).

### 2.2 Cài đặt

1. **Cài WordPress** — hầu hết hosting có nút cài 1 chạm trong cPanel/DirectAdmin.
2. **Upload plugin** — wp-admin → Plugins → Add New → Upload Plugin → `annamleaf-core.zip`
   → Install → **Activate**. Lần bật đầu tiên plugin tạo sẵn 7 bước quy trình, 3 sản phẩm,
   3 vùng trồng, 5 trang, trang chủ và menu.
3. **Upload theme** — Appearance → Themes → Add New → Upload Theme → `annamleaf-theme.zip`
   → Install → **Activate**.
4. **Settings → Permalinks** → Save Changes (để `/process/` và `/our-leaf/` chạy).

### 2.3 Trỏ domain

Ở trang bạn mua `annamleaf.com`, đổi DNS theo một trong hai cách hosting hướng dẫn:

- **Đổi nameserver** sang nameserver của hosting (dễ nhất), hoặc
- **Tạo A record** `@` và `www` trỏ về IP hosting.

Chờ 5 phút–24 giờ để DNS lan. Sau đó trong hosting bật **SSL (Let's Encrypt)** và bật
**ép HTTPS**, rồi vào WordPress **Settings → General** đảm bảo hai ô địa chỉ đều là
`https://annamleaf.com`.

### 2.4 Cài thêm plugin

| Plugin | Để làm gì |
| --- | --- |
| **WP Mail SMTP** | Form báo giá gửi được mail thật (bắt buộc) |
| **Polylang** | Song ngữ EN/VI |
| **UpdraftPlus** | Backup tự động |
| **Cloudflare Turnstile** | Chống spam form |
| **Yoast SEO** hoặc **Rank Math** | Tuỳ chọn — theme đã tự lo meta/schema, cài vào thì theme tự nhường |

### 2.5 Checklist trước khi cho khách hàng thật truy cập

- [ ] Điền hết **Company profile** (tên, địa chỉ, email, điện thoại, 4 số liệu năng lực)
- [ ] Thay hết placeholder — đối chiếu `docs/placeholders.md`
- [ ] Upload ảnh thật theo `docs/shot-list.md` (cột **Photo** trong admin không còn "Not uploaded")
- [ ] Logo thật ở Appearance → Customize → Site Identity
- [ ] **Tắt** ô "Mark empty fields" trong Company profile
- [ ] **Bật** "Show the 18+ age gate"
- [ ] Gửi thử form báo giá, kiểm tra mail về đúng hộp thư
- [ ] Kiểm tra trên điện thoại
- [ ] HTTPS chạy, không còn cảnh báo "not secure"
- [ ] Thêm site vào **Google Search Console**, nộp sitemap
- [ ] **Luật sư của khách rà soát nội dung** — xem mục pháp lý trong README

Xong checklist mới nên đưa link cho khách hàng và đối tác.
