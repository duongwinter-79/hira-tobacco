# Deploy Annamleaf.com lên Hostinger

Hướng dẫn từng bước, viết riêng cho hPanel của Hostinger. Bối cảnh:

- Domain `annamleaf.com` **bạn giữ tài khoản**, đăng ký ở nơi khác → chỉ trỏ DNS, không chuyển domain
- Deploy **bản demo trước**: site chạy trên domain thật nhưng bật `noindex`, Google không lập chỉ mục
- Nội dung và ảnh thật bổ sung sau, không chặn việc dựng hạ tầng

> **Về tên menu và giá:** Hostinger đổi giao diện hPanel và bảng giá khá thường xuyên. Tên
> mục trong tài liệu này đúng ở thời điểm viết; nếu không thấy, tìm theo từ khoá gần nghĩa
> hoặc hỏi chat hỗ trợ — họ trả lời nhanh. **Luôn xem giá gia hạn, không chỉ giá năm đầu.**

## Chọn gói

| Gói | Đủ cho site này? | Điểm quyết định |
| --- | --- | --- |
| **Premium** | Đủ về kỹ thuật | Sao lưu **hằng tuần**. Rẻ nhất |
| **Business** ← khuyên dùng | Dư | Sao lưu **hằng ngày**, CDN sẵn, object cache cho WordPress, NVMe |
| Cloud Startup | Thừa | Tài nguyên riêng — site 6 trang không cần |

Chênh lệch thật sự giữa Premium và Business với site này chỉ là **tần suất sao lưu**. Mất
dữ liệu ngày thứ Ba mà bản lưu gần nhất là Chủ nhật thì mất ba ngày công nhập liệu của
khách. Chọn Business.

Vài lưu ý khi mua:

- **Kỳ hạn**: giá rẻ nhất luôn là gói 48 tháng. Trả 12 tháng cho dự án mới là hợp lý — chưa
  chắc sau một năm khách còn muốn giữ nguyên cách làm này.
- **Domain miễn phí kèm gói**: bạn đã có `annamleaf.com` rồi nên khuyến mãi này không dùng
  được cho nó. Cứ bỏ qua, hoặc để dành đăng ký `annamleaf.vn` sau.
- **Vị trí máy chủ**: chọn **Singapore**. Gần Việt Nam để bạn và khách quản trị, và vẫn tốt
  cho người mua ở châu Á. Người mua châu Âu/Mỹ thì để CDN lo.
- **Không mua thêm**: SSL (đã miễn phí), "SEO toolkit", bảo mật cộng thêm — không cần.

## 1. Tạo website trong hPanel

1. Đăng nhập hPanel → **Websites** → **Add Website**
2. Chọn **WordPress** — **không** chọn "AI Website Builder" hay "Empty website"
3. Khai tài khoản quản trị WordPress: email, mật khẩu. **Ghi lại.**
4. Khi nó hỏi domain: chọn **Use an existing domain** và gõ `annamleaf.com`
5. Nó sẽ báo domain chưa trỏ về Hostinger — **đúng như vậy**, cứ tiếp tục. Hostinger vẫn tạo
   website và cho một địa chỉ tạm dạng `annamleaf.com.<gì đó>.hostingersite.com` để làm việc
   trong lúc chờ DNS
6. Bỏ qua bước gợi ý cài theme/plugin của Hostinger — mình có theme riêng

Xong bước này: WordPress trắng chạy được ở địa chỉ tạm.

## 2. Dọn những gì Hostinger cài sẵn

hPanel cài kèm vài plugin và một theme mặc định. Vào **wp-admin → Plugins**:

| Plugin | Làm gì |
| --- | --- |
| LiteSpeed Cache | **Giữ** — có ích, cấu hình ở bước 8 |
| Hostinger / Hostinger AI Assistant | Xoá được, không ảnh hưởng |
| Hostinger Easy Onboarding | Xoá |
| Bất kỳ plugin quảng cáo nào khác | Xoá |

**Posts → Sample Post** và **Pages → Sample Page**: xoá cả hai.

## 3. Kiểm tra PHP

**hPanel → Advanced → PHP Configuration**:

- **PHP version**: 8.1 trở lên (8.2 hoặc 8.3 là tốt)
- Tab **PHP options**: `memory_limit` ≥ 256M, `upload_max_filesize` ≥ 32M

Theme zip nặng ~3 MB nên giới hạn upload mặc định thường đã đủ, nhưng đặt 32M cho chắc.

## 4. Đóng gói và upload code

Trên máy bạn:

```sh
git pull
sh tools/package.sh
```

Ra `dist/annamleaf-core.zip` và `dist/annamleaf-theme.zip`.

Trong wp-admin, **plugin trước, theme sau** — theme cần các loại nội dung do plugin đăng ký:

1. **Plugins → Add New Plugin → Upload Plugin** → `annamleaf-core.zip` → Install Now →
   **Activate**. Nội dung mẫu tự dựng: 6 trang, 7 bước quy trình, 3 sản phẩm, hồ sơ công ty
2. **Appearance → Themes → Add New Theme → Upload Theme** → `annamleaf-theme.zip` →
   Install Now → **Activate**
3. **Settings → Permalinks** → chọn **Post name** → **Save Changes**

Bước 3 bắt buộc, và phải bấm Save **từ trình duyệt** — đó là lúc WordPress ghi luật rewrite
vào `.htaccess`. Bỏ qua thì mọi trang trừ trang chủ trả về 404.

Mở địa chỉ tạm kiểm tra: phải thấy ảnh bìa, dải menu xanh đậm, thẻ sản phẩm, và cổng 18+ hiện
ra ở lần vào đầu.

## 5. Trỏ tên miền

Lấy IP trước: **hPanel → Websites → annamleaf.com → Dashboard**, mục **Website details**
hoặc **Plan details** có dòng **IP address**. Dạng `123.45.67.89`.

Vào tài khoản đăng ký domain của bạn, phần quản lý **DNS records**, thêm hai bản ghi:

| Loại | Tên/Host | Trỏ tới | TTL |
| --- | --- | --- | --- |
| A | `@` | IP của Hostinger | 3600 |
| CNAME | `www` | `annamleaf.com` | 3600 |

**Không đổi nameserver.** Giữ nguyên NS hiện tại thì các dịch vụ khác trên domain — email
chẳng hạn — không bị ảnh hưởng. Nếu đã có sẵn bản ghi `A` cho `@` thì sửa giá trị chứ đừng
thêm bản ghi thứ hai.

Chờ DNS lan truyền, thường 15 phút đến 2 giờ. Kiểm tra:

```sh
nslookup annamleaf.com
```

Ra đúng IP Hostinger là được.

## 6. Bật HTTPS

Chỉ làm được **sau khi** DNS đã trỏ đúng — Let's Encrypt phải xác minh domain thật sự chỉ về
máy chủ này.

1. **hPanel → Security → SSL** → chọn website → **Install SSL** (miễn phí, Let's Encrypt)
2. Chờ vài phút tới khi trạng thái là **Active**
3. Bật **Force HTTPS** ngay dưới đó

Rồi vào **wp-admin → Settings → General**, đặt **cả hai** ô:

```
WordPress Address (URL):  https://annamleaf.com
Site Address (URL):       https://annamleaf.com
```

Có `https`, **không** có `www`, **không** có dấu `/` ở cuối. Lưu xong WordPress bắt đăng nhập
lại — bình thường.

Cuối cùng **hPanel → Domains → Redirects**: chuyển `www.annamleaf.com` → `https://annamleaf.com`.

## 7. Email và form báo giá

Form yêu cầu báo giá gửi thư bằng `wp_mail()`. Mặc định PHP gửi thẳng, không xác thực —
**thư vào spam hoặc bị chặn**. Người mua gửi yêu cầu mà mình không nhận được thì hỏng cả mục
đích của website.

**Tạo hộp thư:**

**hPanel → Emails → Email Accounts** → tạo `sales@annamleaf.com`. Hostinger sẽ yêu cầu thêm
bản ghi **MX** vào DNS — vì DNS đang do bạn quản lý ở nơi khác, phải tự thêm. hPanel hiện sẵn
giá trị cần thêm; chép đúng cả `MX`, `SPF` (TXT) và `DKIM` (TXT).

**Cấu hình gửi thư:**

1. Cài plugin **FluentSMTP** (miễn phí) hoặc **WP Mail SMTP**
2. Chọn phương thức **Other SMTP**, khai:
   - Host: `smtp.hostinger.com`
   - Port: `465`, mã hoá **SSL**
   - Username: `sales@annamleaf.com`, Password: mật khẩu hộp thư
   - From: `sales@annamleaf.com`
3. Bấm **Send test email** và xác nhận thư về

Rồi tự điền form trên trang Contact và kiểm tra thư về đúng hộp — **kể cả thư mục spam**.

## 8. Cache và tốc độ

**LiteSpeed Cache** (đã cài sẵn): vào **LiteSpeed Cache → Cache** bật, để mặc định là được.
Không cần đụng tới thiết lập nâng cao cho site 6 trang.

**Hostinger CDN** (có trong gói Business): **hPanel → Performance → CDN** → bật.

**Về Cloudflare:** gói Business đã có CDN của Hostinger rồi, nên **chọn một** — dùng cả hai
không nhanh hơn mà thêm một tầng để đoán khi lỗi. Nếu sau này cần Cloudflare cho DNS hay
tường lửa thì tắt CDN Hostinger đi.

Mỗi lần đổi nội dung mà không thấy thay đổi: **LiteSpeed Cache → Toolbox → Purge All**.

## 9. Sao lưu

**hPanel → Files → Backups**: xác nhận sao lưu **hằng ngày** đang bật (gói Business có sẵn).

Tự tạo một bản ngay sau khi cài xong, trước khi giao cho khách nghịch.

## 10. Kiểm tra trước khi coi là xong

- [ ] `https://annamleaf.com` mở được, ổ khoá xanh, không cảnh báo
- [ ] `http://annamleaf.com` và `www.annamleaf.com` đều tự chuyển về `https://annamleaf.com`
- [ ] Cả 6 trang mở được, không trang nào 404 — **kiểm tra `/about/` trước tiên**, đó là chỗ
      lỗi rewrite lộ ra
- [ ] Cổng 18+ hiện ở lần vào đầu tiên
- [ ] Form báo giá gửi được, thư về đúng hộp
- [ ] Xem trên điện thoại: menu, ảnh bìa, bảng lịch mùa vụ
- [ ] Xem mã nguồn trang chủ, tìm `noindex` — **phải còn**, vì đây vẫn là bản demo
- [ ] PageSpeed Insights ≥ 80 trên di động
- [ ] Sao lưu hằng ngày đang bật

## Ngày go-live thật

Chỉ làm sau khi đã có ảnh thật, nội dung được duyệt, và hộp thư có người đọc:

1. **Company profile** → bỏ tick **Hide from search engines** → Save
   (dải cảnh báo vàng trong wp-admin sẽ biến mất — đó là cách biết đã tắt đúng)
2. **LiteSpeed Cache → Toolbox → Purge All**
3. Xem lại mã nguồn: `noindex` phải biến mất
4. Khai báo site với **Google Search Console**, nộp sitemap
5. Bàn giao: tạo tài khoản **Editor** cho khách, giữ **Administrator** cho bạn

## Cập nhật code về sau

```sh
git pull
sh tools/package.sh
```

Rồi trong wp-admin upload đè theme/plugin như bước 4 — WordPress hỏi có ghi đè không, chọn
**Replace current with uploaded**. Nội dung, ảnh và hồ sơ công ty trong database không bị
đụng tới.

Nếu bản mới có sửa nội dung mẫu và bạn muốn lấy: **Company profile → Rebuild default
content**. Nút này **ghi đè chữ của 6 trang mẫu** — nếu khách đã sửa tay thì mất. Không đụng
ảnh và hồ sơ công ty.
