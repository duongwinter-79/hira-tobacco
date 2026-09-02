# Annamleaf.com

Website giới thiệu cho một công ty lá thuốc lá nguyên liệu tại Việt Nam — tích hợp dọc
từ vườn ươm đến container xuất khẩu. Đối tượng đọc là **khách hàng công nghiệp (B2B)**,
không phải người tiêu dùng.

## Trạng thái hiện tại: bản demo

`index.html` là bản demo click-được, tự chứa trong một file:

- 6 trang (Home, About, Process, Our Leaf, Quality, Contact) chạy bằng hash routing
- Song ngữ **EN mặc định / VI**, chuyển bằng nút EN·VI trên header (lưu lựa chọn vào `localStorage`)
- Nội dung thật chưa có ⇒ mọi chỗ chờ dữ liệu khách hàng đều là placeholder dạng `[NHƯ THẾ NÀY]`,
  được tô nền vàng để nhìn ra ngay (xem `docs/placeholders.md`)
- Ảnh chụp chưa có ⇒ dùng minh hoạ vector duotone, mỗi khung có chú thích `PHOTO xx`
  nói rõ ảnh nào cần chụp (xem `docs/shot-list.md`)
- Form RFQ chỉ là giao diện, chưa nối backend
- Age gate 18+ đã dựng nhưng **để tắt** trong demo cho dễ xem — bấm "Preview age gate"
  trên thanh DEMO để xem thử; sẽ bật chặn thật khi go-live

Mở bằng cách mở thẳng `index.html` trong trình duyệt, không cần build.

## Xem trước

- Kế hoạch triển khai: https://claude.ai/code/artifact/c2075d19-5c05-482a-90dc-6edbb9b082dc
- Bản demo: https://claude.ai/code/artifact/6d345449-d606-4d8d-b75e-2d726a1ede40

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

Site cố ý chỉ có **một theme sáng** — đây là site doanh nghiệp, không làm dark mode.

## Bước tiếp theo (khi có nội dung thật)

1. Thay placeholder theo `docs/placeholders.md`, thay minh hoạ bằng ảnh theo `docs/shot-list.md`
   (bỏ ảnh vào `assets/photos/`, xuất AVIF + WebP kèm `srcset`).
2. Tách demo thành site nhiều trang thật bằng **Astro + Tailwind**: mỗi `section[data-view]`
   thành một route riêng, nội dung EN/VI tách ra file Markdown, i18n dùng cơ chế sẵn có của Astro.
   Việc này cần cho SEO — hash routing không tạo được URL riêng cho từng trang.
3. Nối form RFQ (Cloudflare Worker + Resend, chống spam bằng Turnstile).
4. Bật age gate, thêm privacy policy + cookie banner.
5. Deploy lên Cloudflare Pages, trỏ DNS của `annamleaf.com`.

## Lưu ý pháp lý

Luật Phòng, chống tác hại của thuốc lá 2012 cấm quảng cáo thuốc lá dưới mọi hình thức.
Site được viết như **hồ sơ năng lực nguyên liệu B2B**: không mô tả sản phẩm tiêu dùng,
không hình ảnh hút thuốc, không giá bán lẻ, có dòng "for trade and business use only" ở chân trang.
Khách hàng cần cho luật sư rà soát nội dung trước khi go-live.
