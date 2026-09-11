# Prompt sinh ảnh AI — 17 khung ảnh

Mỗi khung ảnh trên website là **một prompt**. Danh sách khớp 1:1 với
[shot-list.md](shot-list.md) và với tên file mà theme đọc trong
`wp-content/themes/annamleaf/assets/photos/`.

Prompt viết bằng **tiếng Anh** — model sinh ảnh cho kết quả ổn định hơn nhiều với tiếng Anh.
Phần mô tả xung quanh để tiếng Việt cho dễ đọc.

## Chạy thế nào

**File này là nguồn duy nhất.** `tools/generate-images.mjs` đọc thẳng prompt và
`aspectRatio` từ đây — sửa prompt trong file này rồi chạy lại là ra ảnh mới, không phải
chép prompt đi đâu cả.

```sh
cp .env.example .env                                  # rồi điền GEMINI_API_KEY
node tools/generate-images.mjs --list                 # 17 khung file này định nghĩa
node tools/generate-images.mjs --all --dry-run        # xem hết bao nhiêu tiền trước
node tools/generate-images.mjs --slot=stage-4 --n=3   # 3 phương án cho 1 khung
php  tools/finish-photos.php stage-4 2                # chọn phương án 2, cắt vào theme
```

Ảnh nháp nằm ở `tools/generated/` (không commit). `finish-photos.php` cắt đúng quy cách
trong bảng tỉ lệ dưới đây, xuất JPEG q80 ≤ 900 KB, và ghi vào `credits.json` rằng đây là
ảnh AI — `plates.php` in dòng credit đó dưới mỗi ảnh, nên không ai nhầm ảnh AI với ảnh
chụp thật của nhà máy.

Hai model dùng được, đổi bằng `--model`:

| `--model` | Model ID | Giá/ảnh (1K–2K) | Khi nào dùng |
| --- | --- | --- | --- |
| `pro` (mặc định) | `gemini-3-pro-image-preview` | ~$0.134 | Khung khó: `stage-5`, `stage-6`, và mọi khung có người Việt |
| `flash` | `gemini-3.1-flash-image` | ~$0.067 | Vòng thử prompt, và khung phong cảnh dễ |

Cách rẻ nhất: thử prompt bằng `--model=flash --size=1K` cho đến khi bố cục đúng, rồi chạy
lại đúng prompt đó bằng `--model=pro --size=2K` (riêng `home` dùng `--size=4K` mới đủ
2400 px ngang).

**Cần bật billing.** Google đã bỏ gói free cho sinh ảnh (model free cũ
`gemini-2.5-flash-image-preview` đã tắt 15/01/2026). Khoá chưa bật billing sẽ báo quota
bằng 0. Bật tại https://aistudio.google.com/apikey. Sinh chữ vẫn chạy trên gói free.

Muốn sửa prompt qua lại trong lúc chat thì có server `gemini-image` trong
[.mcp.json](../.mcp.json). Đó là package cộng đồng, không phải của Google, mà nó nhận khoá
API — **đọc source trước khi dùng**. Chạy hàng loạt thì dùng script ở trên, an toàn hơn và
tự cắt ảnh luôn.

## Ba điều cấm — đã nhúng sẵn vào mọi prompt

Theo [shot-list.md](shot-list.md) và Luật Phòng, chống tác hại của thuốc lá 2012:

- Không có người đang hút thuốc
- Không có bao bì thuốc lá thành phẩm, điếu thuốc, tem nhãn
- Không có chữ, logo, watermark trong khung — model rất hay tự bịa chữ méo

## Tỉ lệ khung hình

Sinh ở tỉ lệ gần nhất rồi cắt — `finish-photos.php` tự cắt, không phải làm tay:

| Khung | Sinh ở | Cắt về |
| --- | --- | --- |
| `home` | `16:9` | 2400×1350 |
| `stage-*`, `region` | `4:3` | 1600×1067 |
| `leaf-*` | `4:3` | 1600×1200 — không cần cắt |
| Ảnh nền tiêu đề trang | `16:9` | giữ nguyên |

Bản Gemini mới có thêm nhiều tỉ lệ ngoài `1:1, 3:4, 4:3, 9:16, 16:9`. Nếu model đang dùng
nhận thẳng `3:2` thì `--aspect=3:2` cho `stage-*` và `region` sẽ khỏi phải cắt — kiểm tra
trang model rồi hãy dùng.

---

# Bắt buộc — 5 khung

## `home.jpg` — Ảnh bìa trang chủ

`aspectRatio: 16:9` · tham chiếu: `tools/reference/dong-viet-thanh/dau-tu-trong-nguyen-lieu-thuoc-la-01-cong-ty-co-phan-dong-viet-thanh.jpeg`

Chừa khoảng trống bên **trái** cho chữ đè lên — đã ghi trong prompt.

```text
Wide establishing photograph of a tobacco growing valley in northern Vietnam at first light. Rows of broad green tobacco plants running from the foreground into the middle distance, limestone karst ridges in soft morning haze behind, low warm sun. The left third of the frame must stay visually quiet, open sky and unbroken field with no detail, so white headline text can be overlaid there. Camera at low drone height, horizon high. Photorealistic documentary landscape photography, natural light, neutral white balance, no HDR, no oversaturation, sharp focus. No text, no logos, no watermarks, no signage lettering. No cigarettes, no cigarette packs, no packaging, no smoking, no smoke.
```

## `stage-1.jpg` — Seed & nursery

`aspectRatio: 4:3` · tham chiếu: `tools/reference/sata-tobacco/strength-practices-01-strength-practices.jpg`

```text
Tobacco seedling nursery under a net house in Vietnam. Long rows of black plastic float trays filled with young bright green tobacco seedlings, receding at a forty-five degree angle into the frame. Soft light diffused through the shade netting overhead. A pair of hands in the near foreground thinning seedlings, face not visible. Damp soil and condensation on the trays. Photorealistic documentary photography, natural light, neutral white balance, no HDR, sharp focus throughout. No text, no logos, no watermarks. No cigarettes, no packaging, no smoking, no smoke.
```

## `stage-2.jpg` — Fields & farmer training

`aspectRatio: 4:3` · tham chiếu: `tools/reference/dong-viet-thanh/dau-tu-trong-nguyen-lieu-thuoc-la-02-cong-ty-co-phan-dong-viet-thanh.jpg`

```text
A Vietnamese field technician in a light collared shirt and cap standing with a contracted smallholder grower in a conical straw hat between rows of waist-high tobacco plants, the two of them looking down at an open field notebook. Late afternoon side light, coconut palms and a low farmhouse in the far background, flat delta farmland. Both figures at a natural working distance, mid-shot, unposed. Photorealistic documentary photography, natural light, neutral white balance, no HDR, sharp focus. No text, no logos, no watermarks. No cigarettes, no packaging, no smoking, no smoke.
```

## `stage-3.jpg` — Harvest

`aspectRatio: 4:3` · tham chiếu: `tools/reference/sata-tobacco/home-05-high-quality-tobacco.jpg`

```text
Close documentary photograph of hand harvesting in a Vietnamese tobacco field. A grower in a checked shirt and conical straw hat snapping a broad ripe lower-stalk leaf from the stem, an armful of freshly cut yellow-green leaves held against the body. Hands and leaves sharp in the foreground, rows of plants falling into shallow depth of field behind. Warm natural side light, no flash. Photorealistic documentary photography, neutral white balance, no HDR. No text, no logos, no watermarks. No cigarettes, no packaging, no smoking, no smoke.
```

## `stage-4.jpg` — Curing ⭐ đầu tư kỹ nhất

`aspectRatio: 4:3` · tham chiếu: `tools/reference/mibica/home-06-mibica-commodities-images-leaf-tobacco.jpg`

```text
Interior of a Vietnamese flue-curing barn packed floor to ceiling with tobacco leaves hanging in tiers on curing sticks, caught mid-cure so the colour graduates from green at the top through yellow to deep gold lower down. Deep one-point perspective down the length of the barn. Warm low light from a single open doorway and the heat exchanger below, fine dust suspended in the air, the stillness of a long exposure. Rough timber and brick structure. Photorealistic documentary photography, natural light only, neutral white balance, no HDR, no flash. No text, no logos, no watermarks. No cigarettes, no packaging, no smoking, no smoke.
```

---

# Nên có — 7 khung

## `stage-5.jpg` — Buying & grading

`aspectRatio: 4:3` · tham chiếu: `tools/reference/mibica/tobacco-01-mibica-commodities-premium-leaf-tobacco-from-vie.jpg`

```text
Buying and grading station inside a Vietnamese leaf processing plant. Workers in uniform shirts and caps standing along both sides of a long grading table heaped with golden cured tobacco leaf, one of them holding up a hand of leaf to compare against reference grade samples pinned nearby. Rows of tables receding into depth. Even overhead industrial daylight through roof panels. Photorealistic documentary photography, neutral white balance, no HDR, sharp focus. No text, no logos, no watermarks, no signage lettering. No cigarettes, no packaging, no smoking, no smoke.
```

## `stage-6.jpg` — Threshing, redrying & baling

`aspectRatio: 4:3` · tham chiếu: `tools/reference/dong-viet-thanh/quy-trinh-san-xuat-cua-nha-may-02-cong-ty-co-phan-dong-viet-thanh.jpg`

```text
Threshing and redrying line running inside a Vietnamese tobacco processing factory. Painted steel and stainless machinery, conveyor belts carrying separated lamina, ducting and pipework overhead, a control panel at the side, one worker in uniform and hearing protection watching the line. Clean high-roofed industrial interior, even light from roof lights, slight motion in the belt. Photorealistic industrial documentary photography, neutral white balance, no HDR, sharp focus. No text, no logos, no watermarks, no brand plates on machinery. No cigarettes, no packaging, no smoking, no smoke.
```

## `stage-7.jpg` — Testing, storage & export

`aspectRatio: 4:3` · tham chiếu: `tools/reference/dong-viet-thanh/kho-mat-03-cong-ty-co-phan-dong-viet-thanh.jpg`

```text
Interior of a temperature-controlled warehouse in Vietnam holding processed tobacco. Cartons and pressed bales stacked high in long rows on both sides of a central aisle that recedes to a bright open loading doorway at the far end. Clean sealed concrete floor, steel portal frame structure, even cool overhead lighting, a forklift parked to one side. Strong one-point perspective conveying capacity. Photorealistic documentary photography, neutral white balance, no HDR, sharp focus. No text, no logos, no watermarks, no printed labels on the cartons. No cigarettes, no packaging, no smoking, no smoke.
```

## `leaf-1.jpg` — Flue-cured Virginia

`aspectRatio: 4:3` · tham chiếu: `tools/reference/mibica/tobacco-08-mibica-commodities-images-product-tobacco-virgin.jpg`

> **Bốn ảnh `leaf-*` phải cùng bố cục, cùng khoảng cách, cùng ánh sáng.** Người mua so màu
> giữa các grade — màu lá chính là grade. Chỉ đổi phần mô tả lá, giữ nguyên phần còn lại.

```text
Product photograph for a B2B tobacco leaf supplier catalogue: whole flue-cured Virginia tobacco leaves laid flat and slightly overlapping at the centre of a plain smooth mid-grey seamless background, even margins on all four sides. Camera square to the surface at a fixed working distance. Soft diffused daylight from a window to the left, gentle falloff, no flash, no coloured light, no harsh specular highlights, because the colour must read true and the leaf colour is the grade. Bright lemon-to-orange golden leaf with a light sheen, visible midrib and fine vein structure, dry cured texture. Photorealistic product photography, sharp across the whole leaf, neutral white balance. No text, no logos, no watermarks, no hands, no people. No cigarettes, no cigarette packs, no packaging, no smoking, no smoke.
```

## `leaf-2.jpg` — Burley

`aspectRatio: 4:3` · tham chiếu: `tools/reference/mibica/tobacco-07-mibica-commodities-images-product-tobacco-burley.jpg`

```text
Product photograph for a B2B tobacco leaf supplier catalogue: whole air-cured Burley tobacco leaves laid flat and slightly overlapping at the centre of a plain smooth mid-grey seamless background, even margins on all four sides. Camera square to the surface at a fixed working distance, identical framing and distance to the other grades in the set. Soft diffused daylight from a window to the left, gentle falloff, no flash, no coloured light, because the colour must read true and the leaf colour is the grade. Light tan to reddish-brown leaf, matte surface with no sheen, open porous grainy texture, prominent pale midrib. Photorealistic product photography, sharp across the whole leaf, neutral white balance. No text, no logos, no watermarks, no hands, no people. No cigarettes, no cigarette packs, no packaging, no smoking, no smoke.
```

## `leaf-3.jpg` — Oriental

`aspectRatio: 4:3` · tham chiếu: `tools/reference/mibica/tobacco-09-mibica-commodities-images-product-tobacco-orient.jpg`

```text
Product photograph for a B2B tobacco leaf supplier catalogue: whole sun-cured Oriental tobacco leaves laid flat and slightly overlapping at the centre of a plain smooth mid-grey seamless background, even margins on all four sides. Camera square to the surface at a fixed working distance, identical framing and distance to the other grades in the set. Soft diffused daylight from a window to the left, gentle falloff, no flash, no coloured light, because the colour must read true and the leaf colour is the grade. Noticeably small narrow leaves compared to the other grades, pale gold to light russet brown, thin and slightly brittle, tight vein structure. Photorealistic product photography, sharp across the whole leaf, neutral white balance. No text, no logos, no watermarks, no hands, no people. No cigarettes, no cigarette packs, no packaging, no smoking, no smoke.
```

## `leaf-4.jpg` — Dark air-cured

`aspectRatio: 4:3` · tham chiếu: `tools/reference/meti/home-01-global-provider-of-the-finest-tobacco.jpg`

```text
Product photograph for a B2B tobacco leaf supplier catalogue: whole dark air-cured tobacco leaves laid flat and slightly overlapping at the centre of a plain smooth mid-grey seamless background, even margins on all four sides. Camera square to the surface at a fixed working distance, identical framing and distance to the other grades in the set. Soft diffused daylight from a window to the left, gentle falloff, no flash, no coloured light, because the colour must read true and the leaf colour is the grade. Heavy thick leaf in deep chocolate brown, matte and leathery, coarse prominent veins, visibly denser and darker than the other grades. Photorealistic product photography, sharp across the whole leaf, neutral white balance. No text, no logos, no watermarks, no hands, no people. No cigarettes, no cigarette packs, no packaging, no smoking, no smoke.
```

## `region.jpg` — Vùng trồng

`aspectRatio: 4:3` · tham chiếu: `tools/reference/dong-viet-thanh/dau-tu-trong-nguyen-lieu-thuoc-la-01-cong-ty-co-phan-dong-viet-thanh.jpeg`

```text
Aerial photograph looking down over a tobacco growing valley in northern Vietnam. A patchwork of small green tobacco plots divided by earth paths and irrigation channels, a river curving through, limestone karst ridges rising at the edges, thin morning haze softening the far distance. Drone altitude, camera tilted down about forty degrees. Photorealistic documentary aerial photography, natural morning light, neutral white balance, no HDR, no oversaturation. No text, no logos, no watermarks. No cigarettes, no packaging, no smoking, no smoke.
```

---

# Tuỳ chọn — 4 ảnh nền tiêu đề trang

Bốn khung này **chỉ nạp được qua Featured image** trong wp-admin, không có file đóng gói
trong theme. Sinh xong thì upload vào từng trang.

## Trang About

`aspectRatio: 16:9` · tham chiếu: `tools/reference/trust-tobacco/en-aboutus-01-trusttobacco-net.jpg`

```text
The frontage of a tobacco leaf processing facility in Vietnam photographed straight on in clear morning light. Low white industrial building with a steel gate, a blank unlettered sign board mounted above the entrance, palms and a flagpole to one side, clean apron in front. Calm corporate documentary photography, no people in frame. Photorealistic, natural light, neutral white balance, no HDR, sharp focus. The sign board must be completely blank. No text, no letters, no logos, no watermarks anywhere in the image. No cigarettes, no packaging, no smoking, no smoke.
```

## Trang Our Leaf

`aspectRatio: 16:9` · tham chiếu: `tools/reference/universal-leaf/universal-leaf-tobacco-03-leaf-tobacco.jpeg`

```text
Two cupped weathered hands holding a loose handful of dry cured golden tobacco leaves, raised into warm backlight so the leaf edges glow and the veins show through. Shallow depth of field, a soft blurred field or barn interior behind. Skin and leaf texture sharp. Photorealistic documentary photography, natural light, neutral white balance, no HDR. No face visible, no text, no logos, no watermarks. No cigarettes, no packaging, no smoking, no smoke.
```

## Trang Process

`aspectRatio: 16:9` · tham chiếu: `tools/reference/trust-tobacco/en-products-02-trusttobacco-net.jpg`

```text
Wide overview of the main processing hall of a Vietnamese tobacco factory, photographed from a raised gantry looking down the length of the line. Conveyors, threshing and redrying machinery running away into the depth of the frame, a few workers in uniform at their stations, high steel roof with daylight panels. Sense of scale and order. Photorealistic industrial documentary photography, even natural light, neutral white balance, no HDR, sharp focus. No text, no logos, no watermarks, no brand plates. No cigarettes, no packaging, no smoking, no smoke.
```

## Trang Quality & Sustainability

`aspectRatio: 16:9` · **không có ảnh tham chiếu** — cả 143 ảnh crawl về không có ảnh phòng lab nào

```text
Quality control laboratory bench in a Vietnamese tobacco processing facility. A technician in a white coat and gloves measuring the moisture content of a leaf sample with a benchtop moisture meter, graded leaf samples laid out in trays beside the instrument, clean white bench and pale wall behind. Calm clinical daylight from a side window. Photorealistic documentary photography, neutral white balance, no HDR, sharp focus. Trays and instrument displays must be blank. No text, no numbers, no letters, no logos, no watermarks anywhere. No cigarettes, no packaging, no smoking, no smoke.
```

---

## Sau khi sinh xong

1. Cắt về đúng quy cách trong bảng tỉ lệ ở đầu file
2. Xuất JPEG chất lượng 80, mỗi file ≤ 900 KB
3. Đặt vào `wp-content/themes/annamleaf/assets/photos/<tên khung>.jpg`
4. Sửa `credits.json`: ghi rõ ảnh do AI sinh, thay cho dòng `TẠM · <tên miền>` hiện tại —
   đây là lúc gỡ được hẳn vấn đề dùng ảnh của công ty khác
5. Viết alt text song ngữ thật cho từng ảnh — vừa là điểm SEO, vừa là yêu cầu trong shot-list

Ảnh AI là **ảnh tạm cho bản demo**. Kế hoạch chụp thật vẫn nằm ở
[shot-list.md](shot-list.md) và [shot-brief.md](shot-brief.md).
