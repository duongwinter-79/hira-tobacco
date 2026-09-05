# Shot list — ảnh cần chụp

Danh sách này **khớp 1:1 với các khung ảnh có thật trong code**. Không có mục nào là mong
muốn suông: mỗi dòng dưới đây là một chỗ trên website đang chờ ảnh, và có đúng hai cách nạp
ảnh vào đó.

**Cách 1 — Featured image (khuyên dùng).** Vào wp-admin, mở mục tương ứng, đặt Featured
image. Không cần đặt tên file gì cả. Đây là cách khách hàng tự làm được.

**Cách 2 — File đóng gói trong theme.** Đặt file đúng tên vào
`wp-content/themes/annamleaf/assets/photos/` rồi commit. Dùng cho ảnh mặc định đi kèm bản
cài, để site vừa cài đã có ảnh.

Khung nào chưa có ảnh thì theme vẽ hình minh hoạ kèm chú thích cần chụp gì — nhìn ra ngay là
còn thiếu, và như vậy vẫn hơn dùng ảnh sai.

## Bắt buộc — 5 ảnh, đây là những gì khách nhìn thấy ngay

| Tên file | Featured image của | Cảnh | Ghi chú chụp |
| --- | --- | --- | --- |
| `home.jpg` | Trang **Home** | Ảnh bìa: cánh đồng lá hoặc toàn cảnh vùng trồng lúc sáng sớm | Khổ rất ngang (16:7). Chừa khoảng trống bên **trái** cho chữ đè lên |
| `stage-1.jpg` | Process → **Seed & nursery** | Khay ươm trong nhà lưới; hoặc tay cầm cây con | |
| `stage-2.jpg` | Process → **Fields & farmer training** | Luống lá ngoài đồng; có người thì tốt hơn | |
| `stage-3.jpg` | Process → **Harvest** | Tay hái lá; sọt lá xanh vừa thu | Cận cảnh, ánh sáng tự nhiên |
| `stage-4.jpg` | Process → **Curing** | Lá vàng treo kín trong lò sấy | **Ảnh biểu tượng nhất của nghề** — đầu tư kỹ nhất vào ảnh này |

Bốn ô đầu của mục "How we work" trên trang chủ dùng đúng `stage-1` … `stage-4`. Ảnh bìa và
bốn ô này là toàn bộ phần ảnh của trang chủ phía trên.

## Nên có — thêm 8 ảnh cho kín trang chủ và trang Process

| Tên file | Featured image của | Cảnh |
| --- | --- | --- |
| `stage-5.jpg` | Process → **Buying & grading** | Bàn phân cấp; nhân viên đối chiếu nắm lá với mẫu grade |
| `stage-6.jpg` | Process → **Threshing, redrying & baling** | Dây chuyền tách cọng đang chạy; kiện lá dán nhãn |
| `stage-7.jpg` | Process → **Testing, storage & export** | Kho kiện xếp cao, hoặc đóng container tại nhà máy |
| `leaf-1.jpg` | Our Leaf → **sản phẩm thứ 1** | Sản phẩm trải phẳng trên nền trơn |
| `leaf-2.jpg` | Our Leaf → **sản phẩm thứ 2** | như trên |
| `leaf-3.jpg` | Our Leaf → **sản phẩm thứ 3** | như trên |
| `leaf-4.jpg` | Our Leaf → **sản phẩm thứ 4** | như trên |
| `region.jpg` | Regions → **vùng đầu tiên** | Toàn cảnh vùng trồng, hoặc flycam một thung lũng |

Thứ tự `leaf-1..4` là thứ tự các mục trong **Our Leaf** ở wp-admin. Mặc định đang là
Flue-cured Virginia, Burley, Oriental, lá sẫm sấy gió — sửa hoặc xoá mục nào thì số thứ tự
chạy theo. Nếu dùng Featured image thì khỏi lo thứ tự.

**Cách chụp ảnh sản phẩm (`leaf-*`)**: nền xám hoặc gỗ mộc, ánh sáng cửa sổ ban ngày,
**không dùng flash** — flash làm sai màu lá, mà màu lá chính là grade. Mọi sản phẩm chụp cùng
một bố cục, cùng khoảng cách, để người mua so màu được giữa các grade.

## Tuỳ chọn — ảnh nền tiêu đề 4 trang

Bốn trang này có dải tiêu đề nhận ảnh, nhưng **chỉ qua Featured image**, không có file đóng
gói. Không đặt thì là hình vẽ, vẫn ổn.

| Trang | Cảnh gợi ý |
| --- | --- |
| About | Mặt tiền nhà máy có biển tên công ty, hoặc đội ngũ |
| Our Leaf | Nắm lá khô trên tay |
| Process | Toàn cảnh xưởng |
| Quality & Sustainability | Phòng lab, hoặc đo độ ẩm kiện lá |

## Không có khung ảnh

Để khỏi chụp thừa: **trang Contact**, **trang 404** và **trang blog** không hiển thị ảnh nào.

## Quy cách kỹ thuật

- `home.jpg`: 2400×1350, tỉ lệ rất ngang
- `stage-*.jpg`, `region.jpg`: 1600×1067 (3:2)
- `leaf-*.jpg`: 1600×1200 (4:3)
- JPEG chất lượng 80, mỗi file ≤ 900 KB
- Gửi **file gốc** qua Drive/Dropbox/WeTransfer — đừng gửi qua Zalo/Messenger (bị nén)
- Alt text song ngữ, mô tả thật (cũng là điểm SEO)

## Ba điều cấm

- **Không** có người đang hút thuốc trong khung
- **Không** có bao bì thuốc lá thành phẩm (bao thuốc, tem) trong khung — website này là hồ
  sơ nguyên liệu bán cho nhà máy, không phải quảng cáo sản phẩm tiêu dùng; luật Việt Nam
  cấm quảng cáo thuốc lá
- **Không** lấy ảnh trên mạng — có thể bị đòi bản quyền, và người mua trong ngành nhận ra
  ảnh stock ngay

## Tổng kết

| Mức | Số ảnh | Kết quả |
| --- | --- | --- |
| Tối thiểu | 5 | Trang chủ có ảnh thật ở mọi chỗ dễ thấy |
| Đủ | 13 | Không còn hình vẽ nào trên trang chủ, Process và Our Leaf |
| Đầy đủ | 17 | Thêm ảnh nền tiêu đề cho 4 trang phụ |
