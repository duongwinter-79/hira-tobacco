# Thông tin khách hàng cần cung cấp

Bản khai điền được (tự lưu, có nút tổng hợp để gửi lại, in ra giấy được):
**https://claude.ai/code/artifact/9950b0ec-213f-4766-9733-405b0aa385e0**

Bản dưới đây là nội dung tương ứng, để lưu trong repo và đối chiếu khi nhập dữ liệu.
Khách hàng **không cần biết dùng WordPress** — họ điền thông tin, mình nhập vào site.

Cấu trúc theo mô hình **bán sản phẩm + giới thiệu nhà máy** (tham chiếu dongvietthanh.com:
mỗi sản phẩm một trang, cộng trang kho và trang nhập nguyên liệu). Ba mục đậm dưới đây là
ba mục không có thì không lên site được: **05 Sản phẩm**, **06 Nhà máy & kho**, **12 Ảnh**
(13 khung, tối thiểu 5).

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
| Địa chỉ nhà máy | ✓ | Liên hệ, trang Nhà máy |
| Email kinh doanh (nên theo tên miền) | ✓ | Liên hệ, chân trang |
| Điện thoại / hotline (có mã quốc gia) | ✓ | Liên hệ, chân trang |
| WhatsApp / Zalo | | Liên hệ |
| Hộp thư nhận yêu cầu báo giá | ✓ | Form RFQ |
| Người phụ trách xuất khẩu (tên, chức vụ, email) | | Liên hệ |

## 03. Bốn số liệu năng lực (trang chủ)

Mỗi mục gồm **con số** và **nhãn**. Làm tròn là được. Với site kiểu nhà máy, nên ưu tiên số
liệu về năng lực chứ không chỉ về vùng trồng.

| Số liệu gợi ý | Bắt buộc |
| --- | --- |
| Công suất chế biến (tấn/năm) | ✓ |
| Tổng diện tích kho (m²) | ✓ |
| Diện tích vùng trồng ký hợp đồng (ha) | ✓ |
| Số thị trường xuất khẩu, hoặc số năm hoạt động | ✓ |

## 04. Giới thiệu công ty

Năm thành lập ✓ · ngành nghề chính ✓ · vùng trồng chính ✓ · số lao động · số cán bộ nông vụ ·
điều gì khác với một nhà buôn lá (tự trồng? tự sấy? tự tách cọng? truy xuất theo lô?).

Trang Giới thiệu đã có bài viết mẫu đầy đủ — chỉ cần dữ kiện, không cần khách tự viết bài.

## 05. Sản phẩm — **mục quan trọng nhất**

Mỗi sản phẩm đang bán sẽ có **một trang riêng**. Với mỗi sản phẩm cần:

| Trường | Bắt buộc | Ví dụ |
| --- | --- | --- |
| Tên sản phẩm (VN + EN) | ✓ | Cọng thuốc lá / Tobacco stem |
| Dạng | ✓ | Lá nguyên · lamina · cọng · sợi cắt · lá vụn (scrap) |
| Chủng loại lá | ✓ | Flue-cured Virginia, Burley, Oriental, lá sẫm sấy gió |
| Grade | ✓ | X2L, B3O, C1L… — người mua tìm theo grade |
| Độ ẩm giao hàng | ✓ | 12,0–13,5% |
| Nicotine (%) | | 1,8–2,4% |
| Đường khử (%) | | |
| Tạp chất / NTRM | | ≤ 0,05% |
| Quy cách cắt (với sợi) | | 0,8 mm |
| Quy cách đóng gói | ✓ | Kiện 200 kg · carton C48 · bao PP 50 kg |
| Khối lượng/container | ✓ | ~18 MT/cont 40' |
| MOQ | ✓ | 1 container |
| Thời gian giao hàng | ✓ | 20–30 ngày sau khi chốt đơn |
| Mã HS | | 2401.20 / 2401.30 |
| Chính sách mẫu | ✓ | Gửi mẫu 500 g miễn phí, khách trả cước |

Loại nào **không bán** thì ghi rõ "không bán" — trang đó sẽ bị xoá khỏi site chứ không để
trống.

## 06. Nhà máy & kho — **mục quan trọng thứ hai**

Đây là phần thay cho chuyến thăm nhà máy của người mua nước ngoài.

| Thông tin | Bắt buộc |
| --- | --- |
| Địa chỉ nhà máy | ✓ |
| Tổng diện tích đất (m²) | ✓ |
| Diện tích xưởng sản xuất (m²) | ✓ |
| **Tổng diện tích kho (m²)** | ✓ |
| Loại kho (kho thường / kho mát / kho lạnh) | ✓ |
| Điều kiện bảo quản (nhiệt độ, độ ẩm duy trì) | ✓ |
| Xử lý mối mọt / hun trùng — cách làm, tần suất | ✓ |
| Danh mục thiết bị chính (dây chuyền tách cọng, máy thái sợi, lò sấy, máy đóng kiện, máy dò kim loại) | ✓ |
| Công suất dây chuyền (tấn/giờ) | ✓ |
| Công suất chế biến cả năm (tấn/năm) | ✓ |
| Số lao động · số ca/ngày | |
| Năm đầu tư dây chuyền | |

**Nguồn nguyên liệu đầu vào** (trang riêng, giống `/nhap-hang-hoa-nguyen-lieu`): tỉ lệ tự
trồng so với thu mua ✓ · thu mua từ đâu · quy trình kiểm tra hàng nhập (cân, đo độ ẩm, phân
cấp) ✓ · cách đánh mã lô để truy xuất ✓.

## 07. Điều khoản thương mại

| Thông tin | Bắt buộc |
| --- | --- |
| Điều kiện giao hàng (EXW / FOB / CIF / CFR) | ✓ |
| Cảng xuất khẩu thường dùng | ✓ |
| Điều kiện thanh toán (L/C at sight, T/T, đặt cọc %) | ✓ |
| Đồng tiền báo giá | ✓ |
| Chứng từ cung cấp (invoice, packing list, C/O form nào, phyto, COA) | ✓ |
| Thị trường xuất khẩu đã có (liệt kê quốc gia) | |
| Có bán nội địa không, cho ai | |

## 08. Vùng trồng

Đã biết: **Cao Bằng**. Cần thêm diện tích (ha) · chủng loại trồng ở đó · tháng thu hoạch ·
số hộ nông dân hợp tác. Nếu còn vùng khác thì bổ sung tên, diện tích, chủng loại, tháng thu
hoạch cho từng vùng.

## 09. Lịch mùa vụ

Gieo ươm · trồng ra đồng · thu hoạch & sấy · chế biến · giao hàng — ghi theo tháng. Người
mua đọc mục này để biết khi nào có hàng mà lên kế hoạch.

## 10. Chất lượng & chứng nhận

Chứng nhận đang có (tên + số hiệu + đơn vị cấp, kèm scan) · cách kiểm nghiệm dư lượng (ai
kiểm, theo ngưỡng nào, mỗi vụ mấy lần) · thiết bị đo tại nhà máy · % lò sấy dùng sinh khối
và số cây trồng mỗi năm · cam kết không lao động trẻ em và cách kiểm tra.

## 11. Giấy phép ngành

Ngành nguyên liệu thuốc lá ở Việt Nam là ngành kinh doanh có điều kiện, và người mua công
nghiệp thường hỏi giấy phép trước khi hỏi giá.

| Giấy tờ | Bắt buộc | Ghi chú |
| --- | --- | --- |
| Giấy phép chế biến nguyên liệu thuốc lá | ✓ | Số, ngày cấp, cơ quan cấp, hạn |
| Giấy phép mua bán nguyên liệu thuốc lá | ✓ | Nếu có hoạt động thương mại |
| Giấy chứng nhận đăng ký doanh nghiệp | ✓ | |
| Giấy tờ liên quan xuất khẩu (nếu có) | | |

Đưa **số hiệu** lên site hay chỉ nói "đã được cấp phép" là quyết định của khách và luật sư —
hỏi rõ trước khi đăng.

## 12. Ảnh — **mục quan trọng nhất**

Website có đúng **13 khung ảnh** nạp được ảnh, cộng 4 dải tiêu đề tuỳ chọn. Không hơn — chi
tiết từng khung ở [shot-list.md](shot-list.md).

| Mức | Số ảnh | Gồm những gì |
| --- | --- | --- |
| **Tối thiểu** | 5 | Ảnh bìa + 4 bước đầu (vườn ươm, đồng ruộng, thu hoạch, **lò sấy**) |
| **Đủ** | 13 | Thêm 3 bước cuối (phân cấp, tách cọng, kho), 4 ảnh sản phẩm, 1 ảnh vùng trồng |
| Đầy đủ | 17 | Thêm ảnh nền tiêu đề cho About / Our Leaf / Process / Quality |

Ảnh quan trọng nhất là **lò sấy với lá vàng treo bên trong** — hình ảnh đặc trưng nhất của
nghề. Sau đó là **4 ảnh sản phẩm**: với người mua công nghiệp, ảnh sản phẩm là thứ họ xem
trước khi xem bất cứ gì khác.

Khách hàng nạp ảnh bằng cách vào wp-admin, mở đúng mục, đặt **Featured image** — không cần
đặt tên file. Danh sách Process / Our Leaf / Regions có cột **Photo** cho biết mục nào còn
thiếu ảnh.

Trong lúc chờ, site dùng ảnh tạm giấy phép tự do (chọn thủ công qua
`tools/fetch-photos.mjs`). Ảnh tạm là ảnh chung của ngành, **phải thay hết trước khi
go-live**.

Ba điều cần tránh:

- Không có người đang hút thuốc, không có bao bì sản phẩm tiêu dùng trong khung
- Không gửi ảnh đã nén qua Zalo/Messenger — gửi file gốc qua Drive/Dropbox/WeTransfer
- Không lấy ảnh trên mạng

## 13. Pháp lý & xác nhận

Người duyệt nội dung ✓ · đầu mối pháp chế rà soát trước khi go-live ✓ · ghi chú thêm.

## Nhập vào đâu

| Nhóm thông tin | wp-admin |
| --- | --- |
| 01, 02, 03 | **Company profile** |
| 04, 09 | **Pages** → trang tương ứng |
| 05 | **Our Leaf** → mỗi sản phẩm một mục |
| 06, 07 | **Pages → Nhà máy / Quy trình** |
| 08 | **Regions** |
| 10, 11 | **Pages → Quality & Sustainability** |
| 12 | **Process** / **Pages** → Featured image từng mục |

Nhập xong hết: **Company profile** → bỏ tick "Mark empty fields", tick "Show the 18+ age
gate" → Save.

## Thứ tự nên xin

Đừng gửi cả bản khai rồi chờ. Xin theo ba đợt, đợt sau chờ đợt trước:

1. **Đợt 1 — chốt được là site có nội dung thật:** mục 01, 02, 05, 06 và nhóm ảnh A + B.
2. **Đợt 2 — làm site đáng tin:** mục 03, 04, 07, 10, 11 và nhóm ảnh C.
3. **Đợt 3 — làm site khác biệt:** mục 08, 09, 13 và nhóm ảnh D.
