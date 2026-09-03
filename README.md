# Annamleaf.com

Website giới thiệu cho một công ty lá thuốc lá nguyên liệu tại Việt Nam — tích hợp dọc từ
vườn ươm đến container xuất khẩu. Đối tượng đọc là **khách hàng công nghiệp (B2B)**,
không phải người tiêu dùng.

## Repo có gì

```
wp-content/themes/annamleaf/     Theme WordPress — chỉ phần trình bày
wp-content/plugins/annamleaf-core/  Plugin — toàn bộ nội dung có cấu trúc
demo/index.html                  Bản demo tĩnh 1 file, dùng làm bản thiết kế gốc
docs/                            Kiến trúc WordPress, shot list ảnh, danh sách placeholder
tools/render-check.php           Render thử template không cần cài WordPress
```

Site chạy trên **WordPress** để khách hàng tự sửa nội dung sau này. Ranh giới theme/plugin
là điểm chính của kiến trúc: nội dung nằm trong plugin nên đổi giao diện về sau không mất dữ liệu.
Chi tiết và hướng dẫn cài đặt: **[docs/wordpress.md](docs/wordpress.md)**.

## Khách hàng sửa được gì

Toàn bộ nội dung là các ô nhập có nhãn trong wp-admin, không phải sửa code:

- **Company profile** — tên công ty, địa chỉ, email, điện thoại, 4 con số năng lực, dòng chú
  thích chân trang, bật/tắt age gate 18+
- **Process** — 7 bước quy trình: tiêu đề, mô tả, thứ tự, ảnh
- **Our Leaf** — chủng loại lá: grade, độ ẩm, kiểu sấy, đóng gói
- **Regions** — vùng trồng
- **Pages** — nội dung các trang, có sẵn khối dựng sẵn (block pattern) cho từng mục

Bố cục do theme giữ cố định, nên khách sửa chữ và ảnh mà không làm vỡ thiết kế.

## Trạng thái

- **Nội dung**: chưa có dữ liệu thật ⇒ mọi chỗ chờ khách cung cấp hiển thị dạng
  `[NHƯ THẾ NÀY]`, tô vàng. Tắt bằng ô "Mark empty fields" trước khi go-live.
  Danh sách: [docs/placeholders.md](docs/placeholders.md)
- **Ảnh**: chưa có ⇒ mỗi khung ảnh vẽ minh hoạ vector kèm chú thích ảnh cần chụp. Upload
  featured image vào là ảnh thật thay chỗ ngay. Shot list: [docs/shot-list.md](docs/shot-list.md)
- **Logo**: đang là wordmark tạm (chữ + mark lá vector). Có logo thật thì đặt ở
  Appearance → Customize → Site Identity.
- **Song ngữ**: EN mặc định. Chuỗi giao diện đã dịch sẵn sang tiếng Việt
  (`themes/annamleaf/languages/vi_VN.mo`); nội dung trang dịch bằng Polylang.
- **Form báo giá**: đã nối `wp_mail()`, có nonce + honeypot. Nên cài WP Mail SMTP và
  một plugin captcha trước khi chạy thật.
- **SEO**: meta description, Open Graph và schema.org Organization đã có sẵn trong theme;
  tự tắt khi cài Yoast/Rank Math để tránh xuất trùng.

## Xem trước

- Kế hoạch triển khai: https://claude.ai/code/artifact/c2075d19-5c05-482a-90dc-6edbb9b082dc
- Bản demo tĩnh: https://claude.ai/code/artifact/6d345449-d606-4d8d-b75e-2d726a1ede40

`demo/index.html` giữ lại làm bản thiết kế gốc (mở thẳng bằng trình duyệt, không cần build).
Theme WordPress dùng đúng bảng màu, typography và khung ảnh của nó.

## Kiểm tra khi sửa code

```sh
sh tools/lint.sh             # php -l toàn bộ + validate theme.json + render 8 template
npx @wordpress/env start     # dựng WordPress local (cần Docker)
```

`tools/render-check.php` giả lập các hàm WordPress để render thật 8 template ra
`tools/output/*.html`, mọi notice/warning đều làm script fail. Nó bắt lỗi gọi sai hàm và
cho xem trước markup mà không cần dựng site — nhưng không thay thế việc test trên
WordPress thật.

## Nhận diện

| Vai trò | Giá trị |
| --- | --- |
| Field (nền đậm, header/footer) | `#1E3D25` |
| Leaf (nhấn chính) | `#2F6136` |
| Cured (nhấn phụ, lá sau sấy) | `#B8841C` |
| Paper (nền trang) | `#F1F2EA` |
| Ink (chữ) | `#1B2018` |
| Tiêu đề | Lora |
| Nội dung | Be Vietnam Pro (hỗ trợ dấu tiếng Việt) |
| Nhãn, số liệu | IBM Plex Mono |

Site cố ý **chỉ một theme sáng** — đây là site doanh nghiệp, không làm dark mode.

## Lưu ý pháp lý

Luật Phòng, chống tác hại của thuốc lá 2012 cấm quảng cáo thuốc lá dưới mọi hình thức.
Site được viết như **hồ sơ năng lực nguyên liệu B2B**: không mô tả sản phẩm tiêu dùng,
không hình ảnh hút thuốc, không giá bán lẻ, có dòng "for trade and business use only" ở chân
trang và age gate 18+ dựng sẵn. Khách hàng cần cho luật sư rà soát nội dung trước khi go-live.
