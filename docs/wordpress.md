# Kiến trúc WordPress

Site chạy trên **một theme riêng + một plugin riêng**. Ranh giới giữa hai phần là điểm
quan trọng nhất của kiến trúc này:

| | Chứa gì | Vì sao tách |
| --- | --- | --- |
| `wp-content/plugins/annamleaf-core/` | Nội dung: quy trình, chủng loại lá, vùng trồng, hồ sơ công ty, các trường của trang | Đổi giao diện sau này không mất dữ liệu |
| `wp-content/themes/annamleaf/` | Chỉ trình bày: template, CSS, minh hoạ, form RFQ | Có thể thiết kế lại mà không đụng vào nội dung |

Theme gọi plugin qua các hàm bọc có `function_exists()`. Tắt plugin thì site vẫn chạy,
chỉ mất phần nội dung động — không trắng trang.

## Khách hàng sửa được những gì, ở đâu

| Muốn sửa | Vào đâu trong wp-admin |
| --- | --- |
| Chữ trên từng trang (Giới thiệu, Quy trình, Sản phẩm, Chất lượng, Liên hệ) | **Pages** — mỗi trang đã có sẵn nội dung đầy đủ, chỉ sửa chữ |
| Tên công ty, địa chỉ, email, điện thoại, 4 con số năng lực, dòng chú thích chân trang, bật/tắt age gate | **Company profile** |
| 7 bước quy trình: tiêu đề, mô tả, thứ tự, ảnh | **Process** |
| Chủng loại lá: tên, tên tiếng Việt, kiểu sấy, grade, độ ẩm, đóng gói | **Our Leaf** |
| Vùng trồng: tên, diện tích, chủng loại, thời gian thu hoạch | **Regions** |
| Hero (tiêu đề lớn, câu mở đầu) của từng trang | **Pages** → mở trang → hộp **Page hero** |
| Menu | **Appearance → Menus** |
| Logo | **Appearance → Customize → Site Identity** |

Mỗi bước quy trình và mỗi trang đều có ô **Featured image**. Chưa có ảnh thì khung hiển thị
minh hoạ vector kèm chú thích ảnh cần chụp; upload ảnh vào là ảnh thật thay chỗ ngay,
không phải sửa code.

Ô **"Mark empty fields"** trong Company profile bật/tắt việc tô vàng các chỗ còn thiếu nội dung.
Bật trong lúc dựng, **tắt trước khi go-live**.

Danh sách Process và Our Leaf trong admin có cột **Photo**: chỗ nào chưa có ảnh thì hiện
"Not uploaded" kèm mô tả ảnh cần chụp — dùng cột này để theo dõi tiến độ chụp ảnh.

## Cấu trúc URL

| Trang | URL | Template |
| --- | --- | --- |
| Trang chủ | `/` | `front-page.php` |
| Giới thiệu | `/about/` | `page.php` |
| Quy trình | `/process/` | `page-templates/process.php` — nội dung trang + 7 bước |
| Sản phẩm | `/our-leaf/` | `page-templates/leaf.php` — thẻ + bảng quy cách + nội dung trang |
| Chất lượng | `/quality-sustainability/` | `page.php` |
| Liên hệ | `/contact/` | `page-templates/contact.php` — nội dung trang + form RFQ |

Năm trang này là **Page thật**, không phải archive — nên mọi chữ trên đó đều sửa được từ
**Pages**. Bản thân từng bước quy trình và từng chủng loại lá vẫn là record riêng, sửa ở
**Process** và **Our Leaf**.

## Nội dung mặc định

Kích hoạt plugin lần đầu là site đã đầy đủ nội dung — 6 trang **đã viết sẵn chữ**, 7 bước
quy trình, 4 chủng loại lá, vùng trồng Cao Bằng, menu và trang chủ. Không phải tự chèn khối nào.

**Ảnh tạm:** Company profile → *Import temporary photographs* tải ảnh giấy phép tự do từ
Wikimedia Commons vào các khung còn trống, có ghi công trên ảnh và xoá được bằng một nút.
Phải thay bằng ảnh thật trước khi go-live.

Nếu lỡ xoá mất nội dung một trang, hoặc sau khi cập nhật file theme/plugin muốn lấy lại bản
mới nhất: **Company profile → Rebuild default content**. Nút này ghi đè chữ của 6 trang mặc
định, **không** đụng tới ảnh, hồ sơ công ty và những gì bạn tự thêm.

## Cài đặt

1. Cài WordPress 6.4+ / PHP 8.0+.
2. Copy `wp-content/themes/annamleaf` và `wp-content/plugins/annamleaf-core` vào site.
3. Kích hoạt plugin **Annam Leaf Core** trước — lần kích hoạt đầu tiên nó tự tạo 7 bước
   quy trình, 4 chủng loại lá, 3 vùng trồng, 5 trang, đặt trang chủ và dựng menu chính.
4. Kích hoạt theme **Annam Leaf**.
5. Vào **Settings → Permalinks** bấm Save một lần (để URL `/process/` và `/our-leaf/` hoạt động).

## Chạy local

Cách gọn nhất, chỉ cần Docker Desktop:

```sh
docker compose up -d
```

Rồi mở http://localhost:8888, chạy trình cài WordPress, bật plugin **Annam Leaf Core**
(lần bật đầu tiên sẽ seed nội dung), bật theme **Annam Leaf**, và vào Settings → Permalinks
bấm Save. Từng bước một có trong [chay-thu-local.md](chay-thu-local.md).

Repo cũng có `.wp-env.json` cho ai quen `@wordpress/env`, nhưng wp-env cần gọi
`api.wordpress.org` trước khi chạy nên hỏng ở mạng chặn wordpress.org.

Kiểm tra code mà không cần dựng site:

```sh
sh tools/lint.sh
```

Script này chạy `php -l` toàn bộ file, validate `theme.json`, rồi render 8 template ra
`tools/output/*.html` bằng bộ giả lập hàm WordPress trong `tools/render-check.php` —
mọi notice/warning đều làm script fail. Đây **không** thay thế cho việc test trên WordPress
thật, nhưng bắt được lỗi gọi sai hàm, sai tham số và template chết trước khi lên site.

## Plugin cần cài thêm

| Plugin | Để làm gì | Bắt buộc |
| --- | --- | --- |
| **Polylang** (miễn phí) | Song ngữ EN/VI cho nội dung. Theme đã có sẵn nút chuyển ngôn ngữ, chỉ hiện khi Polylang bật | Có, nếu làm song ngữ |
| **WP Mail SMTP** | Form báo giá gửi được mail thật (mặc định `wp_mail()` của hosting hay bị chặn) | Nên có |
| **Yoast SEO** hoặc **Rank Math** | Meta description, sitemap, Open Graph | Nên có |
| **Cloudflare Turnstile** / reCAPTCHA | Chống spam form (hiện chỉ có honeypot + nonce) | Nên có |
| **WP Super Cache** / cache của hosting | Tốc độ | Tuỳ |

Chuỗi giao diện tiếng Việt đã dịch sẵn trong `themes/annamleaf/languages/vi_VN.mo`
(129/133 chuỗi; 4 chuỗi còn lại giữ nguyên vì tiếng Việt viết y hệt). Sửa `vi_VN.po` rồi
compile lại bằng `wp i18n make-mo languages/`.

## SEO

Theme tự xuất meta description, thẻ Open Graph/Twitter và schema.org `Organization`
(tên, địa chỉ, email, điện thoại lấy từ Company profile). Đủ để go-live mà chưa cần plugin.
Khi cài Yoast / Rank Math / SEOPress, phần này **tự tắt** để không xuất trùng thẻ.

Các placeholder `[NHƯ THẾ NÀY]` bị lọc khỏi meta description và schema — chúng chỉ hiển
thị trên trang, không bao giờ lọt vào kết quả tìm kiếm.

## Ghi chú kỹ thuật

- **Form báo giá** gửi qua `admin-post.php`, có nonce + honeypot, **không lưu vào database**.
  Muốn lưu lại đơn hỏi hàng thì cài thêm plugin lưu form.
- **Font** đang load từ Google Fonts. Có khách EU thì nên self-host cho đúng GDPR:
  bỏ file woff2 vào `assets/fonts/`, thêm `@font-face` vào `style.css`, rồi trả chuỗi rỗng
  qua filter `annamleaf_fonts_url`.
- **Age gate** mặc định tắt. Bật ở Company profile trước khi go-live.
- Theme cố ý **chỉ một theme sáng**, không có dark mode.
- Khung ảnh: hero `2400×1100`, ảnh mục `1600×1067` (cắt cứng tỉ lệ 3:2).
