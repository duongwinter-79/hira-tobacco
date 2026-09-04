# Thông tin khách hàng cần cung cấp

Bản khai điền được (tự lưu, có nút tổng hợp để gửi lại, in ra giấy được):
**https://claude.ai/code/artifact/9950b0ec-213f-4766-9733-405b0aa385e0**

Bản dưới đây là nội dung tương ứng, để lưu trong repo và đối chiếu khi nhập dữ liệu.
Khách hàng **không cần biết dùng WordPress** — họ điền thông tin, mình nhập vào site.

## 01. Nhận diện công ty

| Thông tin | Bắt buộc | Hiện ở đâu |
| --- | --- | --- |
| Tên giao dịch (VD "Annam Leaf") | ✓ | Header, footer, mọi trang |
| Tên pháp lý đầy đủ | ✓ | Chân trang, schema.org |
| Mã số thuế / số ĐKKD | ✓ | Chân trang |
| Dòng chữ nhỏ dưới tên (≤ 6 từ) | | Header, footer |
| Logo (.ai/.svg/.pdf, hoặc PNG nền trong ≥ 1000px) | | Header, footer |

## 02. Liên hệ

| Thông tin | Bắt buộc | Hiện ở đâu |
| --- | --- | --- |
| Địa chỉ văn phòng | ✓ | Liên hệ, chân trang |
| Địa chỉ nhà máy | | Liên hệ |
| Email kinh doanh (nên theo tên miền) | ✓ | Liên hệ, chân trang |
| Điện thoại (có mã quốc gia) | ✓ | Liên hệ, chân trang |
| WhatsApp / Zalo | | Liên hệ |
| Hộp thư nhận yêu cầu báo giá | ✓ | Form RFQ |

## 03. Bốn số liệu năng lực (trang chủ)

Mỗi mục gồm **con số** và **nhãn**. Làm tròn là được.

| Số liệu | Bắt buộc |
| --- | --- |
| Diện tích vùng trồng ký hợp đồng (ha) | ✓ |
| Số hộ nông dân hợp tác | ✓ |
| Sản lượng chế biến (tấn/năm) | ✓ |
| Số thị trường xuất khẩu | ✓ |

## 04. Giới thiệu công ty

Năm thành lập ✓ · vùng trồng chính ✓ · số cán bộ nông vụ · năng lực nhà máy (số lò sấy,
công suất t/h, diện tích kho) · điều gì khác với một nhà buôn lá.

Trang Giới thiệu đã có bài viết mẫu đầy đủ — chỉ cần dữ kiện, không cần khách tự viết bài.

## 05. Sản phẩm

Với mỗi chủng loại (Flue-cured Virginia, Burley, Oriental, lá sẫm sấy gió): **có bán hay
không** · grade · độ ẩm giao hàng · quy cách đóng gói. Loại không bán thì xoá khỏi site.

Thêm: dạng giao hàng (lamina / lá nguyên / cọng / sợi cắt / lá vụn), cảng xuất khẩu ✓,
thị trường xuất khẩu đã có.

## 06. Vùng trồng

Đã biết: **Cao Bằng**. Cần thêm diện tích (ha) · chủng loại trồng ở đó · tháng thu hoạch.
Nếu còn vùng khác thì bổ sung tên, diện tích, chủng loại, tháng thu hoạch cho từng vùng.

## 07. Lịch mùa vụ

Gieo ươm · trồng ra đồng · thu hoạch & sấy · chế biến · giao hàng — ghi theo tháng.

## 08. Chất lượng & chứng nhận

Chứng nhận đang có (tên + số hiệu + đơn vị cấp, kèm scan) · cách kiểm nghiệm dư lượng ·
% lò sấy dùng sinh khối và số cây trồng mỗi năm · cam kết lao động.

## 09. Ảnh — phần quan trọng nhất

12 cảnh, chi tiết ở [shot-list.md](shot-list.md). Quan trọng nhất là **ảnh 05: lá vàng treo
trong lò sấy**.

Trong lúc chờ, site đang dùng ảnh tạm giấy phép tự do từ Wikimedia Commons (bật/tắt ở
Company profile). Ảnh tạm là ảnh chung của ngành, **phải thay hết trước khi go-live**.

Ba điều cần tránh:

- Không có người đang hút thuốc, không có bao bì sản phẩm tiêu dùng trong khung
- Không gửi ảnh đã nén qua Zalo/Messenger — gửi file gốc qua Drive/Dropbox/WeTransfer
- Không lấy ảnh trên mạng

## 10. Pháp lý & xác nhận

Người duyệt nội dung ✓ · đầu mối pháp chế rà soát trước khi go-live ✓ · ghi chú thêm.

## Nhập vào đâu

| Nhóm thông tin | wp-admin |
| --- | --- |
| 01, 02, 03 | **Company profile** |
| 04, 05 (chữ), 07 | **Pages** → trang tương ứng |
| 05 (grade, độ ẩm, đóng gói) | **Our Leaf** → từng chủng loại |
| 06 | **Regions** |
| 08 | **Pages → Quality & Sustainability** |
| 09 | **Process** / **Pages** → Featured image từng mục |

Nhập xong hết: **Company profile** → bỏ tick "Mark empty fields", tick "Show the 18+ age
gate" → Save.
