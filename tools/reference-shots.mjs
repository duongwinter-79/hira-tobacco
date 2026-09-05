/**
 * Pull the photographs off the reference sites and build a shot brief from them.
 *
 * The point is NOT to reuse the pictures. They belong to the companies that took them,
 * and they show those companies' factories — putting a competitor's warehouse on Annam
 * Leaf's site tells a buyer it is Annam Leaf's warehouse, which is the kind of thing the
 * leaf trade notices. The point is to see how a working leaf site photographs itself:
 * what is in frame, from what angle, on what background, at what crop.
 *
 * So this downloads them into tools/reference/ (git-ignored, never inside the theme),
 * screenshots each page whole for layout, and builds a contact sheet where you tag each
 * picture with the Annam Leaf frame it is a reference FOR. Tag them, press the button,
 * and you get a brief to hand the photographer at the factory.
 *
 *     node tools/reference-shots.mjs                       the default reference list
 *     node tools/reference-shots.mjs --url=https://…       add a site (repeatable)
 *     node tools/reference-shots.mjs --only=dongvietthanh  just the sites matching this
 *     node tools/reference-shots.mjs --min=700             ignore images under 700px
 *     node tools/reference-shots.mjs --depth=1             also follow same-site links
 *     start tools\reference\index.html                     look at what came back
 *
 * Needs Playwright and a Chromium. Once, in the repository folder:
 *
 *     npm install --save-dev playwright
 *     npx playwright install chromium
 */

import { mkdir, writeFile, readFile, access } from "node:fs/promises";
import { constants } from "node:fs";
import path from "node:path";
import { createRequire } from "node:module";
import { fileURLToPath, pathToFileURL } from "node:url";

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const OUT = path.join(ROOT, "tools/reference");

const ARGS = process.argv.slice(2);
const URLS = ARGS.filter((a) => a.startsWith("--url=")).map((a) => a.slice(6));
const ONLY = (ARGS.find((a) => a.startsWith("--only=")) || "").split("=")[1] || "";
const MIN = Number((ARGS.find((a) => a.startsWith("--min=")) || "").split("=")[1] || 500);
const DEPTH = Number((ARGS.find((a) => a.startsWith("--depth=")) || "").split("=")[1] || 0);

/**
 * The sites worth looking at. The first is the one the client pointed to; the rest are the
 * international merchants whose product and factory photography sets the expectation.
 */
const REFERENCES = [
	"https://dongvietthanh.com/",
	"https://dongvietthanh.com/cong-thuoc-la",
	"https://dongvietthanh.com/soi-thuoc-la",
	"https://dongvietthanh.com/kho-thuong",
	"https://dongvietthanh.com/nhap-hang-hoa-nguyen-lieu",
	"https://dongvietthanh.com/gioi-thieu",
	"https://arestobacco.com/",
	"https://www.satatobacco.com/",
	"https://meti.com.tr/",
	"https://www.universalcorp.com/universal-leaf-tobacco/",
];

/**
 * The frames on the Annam Leaf site, as tagging options. Same names the photo tooling and
 * the shot list use, so a tagged brief lines up with docs/shot-list.md.
 */
const FRAMES = [
	["", "— chưa gắn —"],
	["A1", "A1 · Lá nguyên theo grade"],
	["A2", "A2 · Lamina"],
	["A3", "A3 · Cọng"],
	["A4", "A4 · Sợi cắt"],
	["A5", "A5 · Lá vụn"],
	["A6", "A6 · Nắm lá trên tay"],
	["A7", "A7 · Kiện có nhãn lô"],
	["A8", "A8 · Carton đóng gói"],
	["B1", "B1 · Mặt tiền nhà máy"],
	["B2", "B2 · Toàn cảnh xưởng"],
	["B3", "B3 · Dây chuyền tách cọng"],
	["B4", "B4 · Máy thái sợi"],
	["B5", "B5 · Máy đóng kiện"],
	["B6", "B6 · Kho thành phẩm"],
	["B7", "B7 · Kho nguyên liệu"],
	["B8", "B8 · Đóng container"],
	["B9", "B9 · Cân hàng đầu vào"],
	["C1", "C1 · Bàn phân cấp"],
	["C2", "C2 · Phòng lab"],
	["C3", "C3 · Đo độ ẩm"],
	["C4", "C4 · Scan chứng nhận"],
	["D1", "D1 · Cánh đồng (ảnh bìa)"],
	["D2", "D2 · Lò sấy"],
	["D3", "D3 · Thu hoạch"],
	["D4", "D4 · Nông vụ và nông dân"],
	["D5", "D5 · Vườn ươm"],
	["D6", "D6 · Flycam vùng trồng"],
	["D7", "D7 · Đội ngũ"],
	["layout", "Bố cục trang, không phải một khung ảnh"],
];

/* ------------------------------------------------------------------ helpers */

const slug = (v) =>
	String(v || "")
		.normalize("NFD").replace(/[̀-ͯ]/g, "")
		.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "").slice(0, 48);

const esc = (v) =>
	String(v ?? "").replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");

async function exists(file) {
	try {
		await access(file, constants.F_OK);
		return true;
	} catch {
		return false;
	}
}

/**
 * Playwright may live in the project, or globally on a machine that already has it.
 */
async function loadChromium() {
	const require = createRequire(import.meta.url);
	const roots = [
		ROOT,
		"/opt/node22/lib/node_modules",
		"/usr/lib/node_modules",
		"/usr/local/lib/node_modules",
		process.env.APPDATA ? path.join(process.env.APPDATA, "npm/node_modules") : "",
	].filter(Boolean);

	for (const from of roots) {
		try {
			const entry = require.resolve("playwright", { paths: [from] });
			const mod = await import(pathToFileURL(entry).href);
			// Playwright is CommonJS, so the named exports may sit under .default.
			const chromium = (mod.default || mod).chromium;

			if (chromium) return chromium;
		} catch {
			continue;
		}
	}

	throw new Error(
		"Playwright not found. In the repository folder run:\n" +
		"    npm install --save-dev playwright\n" +
		"    npx playwright install chromium"
	);
}

/* ---------------------------------------------------------------- the crawl */

/**
 * Everything worth looking at on one page: the pictures, and a screenshot of the whole
 * page so the layout itself is a reference.
 */
async function readPage(page, url) {
	await page.goto(url, { waitUntil: "domcontentloaded", timeout: 45000 });

	// Lazy-loaded images only appear once they have been scrolled past.
	await page.evaluate(async () => {
		await new Promise((done) => {
			let y = 0;
			const step = setInterval(() => {
				window.scrollBy(0, 600);
				y += 600;
				if (y >= document.body.scrollHeight + 1200) {
					clearInterval(step);
					window.scrollTo(0, 0);
					done();
				}
			}, 120);
		});
	});

	await page.waitForTimeout(900);

	const found = await page.evaluate(() => {
		const out = [];
		const seen = new Set();

		const context = (el) => {
			const section = el.closest("section, article, figure, .container, main") || document.body;
			const heading = section.querySelector("h1, h2, h3");
			const caption = el.closest("figure")?.querySelector("figcaption");
			return [caption?.textContent, heading?.textContent, document.title]
				.map((t) => (t || "").replace(/\s+/g, " ").trim())
				.filter(Boolean)[0] || "";
		};

		for (const img of document.querySelectorAll("img")) {
			const src = img.currentSrc || img.src;
			if (!src || !/^https?:/i.test(src) || seen.has(src)) continue;
			seen.add(src);
			out.push({
				src,
				kind: "img",
				width: img.naturalWidth || img.width || 0,
				height: img.naturalHeight || img.height || 0,
				alt: (img.alt || "").replace(/\s+/g, " ").trim(),
				context: context(img),
			});
		}

		// Hero pictures are very often a CSS background rather than an <img>.
		for (const el of document.querySelectorAll("*")) {
			const bg = getComputedStyle(el).backgroundImage;
			const match = bg && bg.match(/url\((['"]?)(https?:[^'")]+)\1\)/);
			if (!match || seen.has(match[2])) continue;
			const box = el.getBoundingClientRect();
			if (box.width < 300 || box.height < 200) continue;
			seen.add(match[2]);
			out.push({
				src: match[2],
				kind: "background",
				width: Math.round(box.width),
				height: Math.round(box.height),
				alt: "",
				context: context(el),
			});
		}

		return out;
	});

	const links = DEPTH > 0
		? await page.evaluate(() =>
			[...document.querySelectorAll("a[href]")]
				.map((a) => a.href)
				.filter((h) => /^https?:/i.test(h)))
		: [];

	return { found, links };
}

async function main() {
	const chromium = await loadChromium();

	let targets = URLS.length ? URLS : REFERENCES;
	if (ONLY) targets = targets.filter((u) => u.includes(ONLY));

	if (!targets.length) {
		console.error("No pages to visit.");
		process.exitCode = 1;
		return;
	}

	await mkdir(OUT, { recursive: true });

	const browser = await chromium.launch();
	const context = await browser.newContext({
		viewport: { width: 1440, height: 900 },
		userAgent:
			"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) " +
			"Chrome/124.0 Safari/537.36",
	});
	const page = await context.newPage();

	const pages = [];
	const queue = targets.map((url) => ({ url, depth: 0 }));
	const visited = new Set();
	let downloaded = 0;
	let skipped = 0;

	while (queue.length) {
		const { url, depth } = queue.shift();

		if (visited.has(url)) continue;
		visited.add(url);

		const host = new URL(url).hostname.replace(/^www\./, "");
		const dir = path.join(OUT, slug(host));

		console.log(`\n${url}`);

		let result;

		try {
			result = await readPage(page, url);
		} catch (error) {
			console.error(`  không mở được: ${error.message.split("\n")[0]}`);
			continue;
		}

		await mkdir(dir, { recursive: true });

		const pageSlug = slug(new URL(url).pathname) || "home";
		const shot = `${slug(host)}/page-${pageSlug}.png`;

		try {
			await page.screenshot({ path: path.join(OUT, shot), fullPage: true });
			console.log(`  ảnh chụp toàn trang: ${shot}`);
		} catch (error) {
			console.error(`  không chụp được trang: ${error.message.split("\n")[0]}`);
		}

		const images = [];
		let index = 0;

		for (const item of result.found) {
			if (item.width < MIN) {
				skipped++;
				continue;
			}

			index++;

			const ext = (item.src.match(/\.(jpe?g|png|webp|avif)(?:$|\?)/i) || [, "jpg"])[1].toLowerCase();
			const name = `${String(index).padStart(2, "0")}-${slug(item.alt || item.context) || "anh"}.${ext}`;
			const file = path.join(dir, name);

			try {
				const response = await context.request.get(item.src, { headers: { Referer: url } });

				if (!response.ok()) throw new Error(String(response.status()));

				const body = await response.body();
				await writeFile(file, body);

				images.push({
					...item,
					file: `${slug(host)}/${name}`,
					bytes: body.length,
					from: url,
				});

				downloaded++;
			} catch (error) {
				console.error(`  bỏ qua ${item.src.slice(0, 70)} (${error.message})`);
			}
		}

		console.log(`  ${images.length} ảnh tải về`);

		pages.push({ url, host, screenshot: shot, images });

		if (depth < DEPTH) {
			for (const link of result.links) {
				if (new URL(link).hostname !== new URL(url).hostname) continue;
				if (visited.has(link) || queue.some((q) => q.url === link)) continue;
				queue.push({ url: link, depth: depth + 1 });
			}
		}

		// One page at a time, with a pause. These are somebody's servers.
		await page.waitForTimeout(1200);
	}

	await browser.close();

	await writeFile(path.join(OUT, "index.html"), sheet(pages));
	await writeFile(path.join(OUT, "reference.json"), JSON.stringify(pages, null, "\t") + "\n");

	console.log(`\n${downloaded} ảnh từ ${pages.length} trang (bỏ ${skipped} ảnh nhỏ hơn ${MIN}px).`);
	console.log("Mở bảng tham chiếu:");
	console.log("  Windows: start tools\\reference\\index.html");
	console.log("  macOS:   open tools/reference/index.html");
	console.log("\nGắn mỗi ảnh với khung tương ứng rồi bấm “Tổng hợp brief” — đó là bản mô tả");
	console.log("để đưa người chụp ảnh ở nhà máy. Ảnh trong thư mục này là tài liệu tham chiếu,");
	console.log("không đưa lên website: chúng là nhà máy của công ty khác.");
}

/* --------------------------------------------------------------- the sheet */

function sheet(pages) {
	const options = FRAMES.map(([v, l]) => `<option value="${esc(v)}">${esc(l)}</option>`).join("");

	const cards = (page) => page.images.map((img) => `
			<figure class="card">
				<a href="${esc(img.file)}" target="_blank" rel="noopener"><img src="${esc(img.file)}" alt="" loading="lazy"></a>
				<figcaption>
					<span class="dim">${esc(img.width)}×${esc(img.height)} · ${(img.bytes / 1024).toFixed(0)} KB · ${esc(img.kind)}</span>
					<span class="alt">${esc(img.alt || img.context || "—")}</span>
					<select data-file="${esc(img.file)}" data-alt="${esc(img.alt || img.context || "")}" data-from="${esc(img.from)}">${options}</select>
					<textarea rows="2" placeholder="Ghi chú: chụp lại thế nào ở nhà máy mình…" data-note="${esc(img.file)}"></textarea>
				</figcaption>
			</figure>`).join("");

	const sections = pages.map((page) => `
		<section>
			<h2>${esc(page.host)}<span class="dim"> — ${esc(new URL(page.url).pathname)}</span></h2>
			<p class="dim"><a href="${esc(page.url)}" target="_blank" rel="noopener">${esc(page.url)}</a>
			· <a href="${esc(page.screenshot)}" target="_blank" rel="noopener">ảnh chụp toàn trang</a></p>
			${page.images.length ? `<div class="grid">${cards(page)}</div>` : `<p class="dim">Không lấy được ảnh nào đủ lớn.</p>`}
		</section>`).join("");

	return `<!doctype html>
<html lang="vi">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tham chiếu ảnh — Annam Leaf</title>
<style>
	:root { color-scheme: light dark; --line:#d7d2c7; --ink:#1d2016; --dim:#6b6a5e; --bg:#faf8f3; --card:#fff; --gold:#9C6F14; }
	@media (prefers-color-scheme: dark) { :root { --line:#3a3a33; --ink:#ece9e1; --dim:#a09d92; --bg:#16170f; --card:#20211a; --gold:#D6A73F; } }
	body { margin:0; padding-bottom:80px; background:var(--bg); color:var(--ink); font:15px/1.5 system-ui,-apple-system,"Segoe UI",sans-serif; }
	header, section, footer { max-width:1280px; margin:0 auto; padding:0 20px; }
	header { padding-top:32px; }
	h1 { font-size:24px; margin:0 0 8px; }
	h2 { font-size:18px; margin:34px 0 2px; }
	.lede { color:var(--dim); max-width:78ch; }
	.warn { background:rgba(156,111,20,.12); border-left:3px solid var(--gold); padding:14px 16px; margin:18px 0 0; max-width:78ch; }
	.dim { color:var(--dim); font-size:13px; }
	.grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:16px; margin-top:14px; }
	.card { margin:0; background:var(--card); border:1px solid var(--line); border-radius:10px; overflow:hidden; }
	.card img { display:block; width:100%; height:180px; object-fit:cover; background:var(--line); }
	figcaption { display:flex; flex-direction:column; gap:6px; padding:10px 12px; }
	.alt { font-size:13px; }
	select, textarea { font:inherit; font-size:13px; width:100%; padding:5px 6px; border:1px solid var(--line); border-radius:5px; background:var(--bg); color:var(--ink); }
	textarea { resize:vertical; }
	.card.tagged { border-color:var(--gold); box-shadow:0 0 0 2px rgba(156,111,20,.2); }
	footer { background:var(--bg); border-top:1px solid var(--line); padding:16px 20px 30px; margin-top:40px; }
	button { font:inherit; padding:9px 16px; border-radius:8px; border:1px solid var(--line); background:var(--gold); color:#20180A; cursor:pointer; }
	#brief { width:100%; min-height:220px; margin-top:12px; font-family:ui-monospace,monospace; font-size:12.5px; }
	#brief[hidden] { display:none; }
	code { background:rgba(128,128,128,.15); padding:1px 5px; border-radius:4px; }
</style>
<header>
	<h1>Tham chiếu ảnh</h1>
	<p class="lede">Ảnh lấy về từ website của các công ty khác, để xem <b>họ chụp cái gì, từ góc nào, nền ra sao</b>. Gắn mỗi ảnh với khung tương ứng trên site Annam Leaf, ghi chú cách chụp lại, rồi bấm <b>Tổng hợp brief</b> ở cuối trang.</p>
	<p class="warn"><b>Không đưa những ảnh này lên annamleaf.com.</b> Chúng có bản quyền của công ty chụp, và chúng là nhà máy, kho, sản phẩm của công ty khác — đưa lên site Annam Leaf là nói với người mua rằng đó là nhà máy của Annam Leaf. Dùng chúng làm mẫu để chụp lại tại nhà máy của khách.</p>
</header>
${sections}
<footer>
	<button id="build" type="button">Tổng hợp brief</button>
	<span class="dim" id="note"> </span>
	<textarea id="brief" hidden readonly placeholder="Bấm “Tổng hợp brief” để lấy nội dung dán vào docs/shot-brief.md hoặc gửi người chụp ảnh…"></textarea>
</footer>
<script>
document.addEventListener("change", (event) => {
	if (!event.target.matches("select[data-file]")) return;
	event.target.closest(".card").classList.toggle("tagged", Boolean(event.target.value));
});
document.getElementById("build").addEventListener("click", () => {
	const rows = [...document.querySelectorAll("select[data-file]")]
		.filter((s) => s.value)
		.map((s) => ({
			frame: s.value,
			file: s.dataset.file,
			alt: s.dataset.alt,
			from: s.dataset.from,
			note: (document.querySelector('[data-note="' + CSS.escape(s.dataset.file) + '"]') || {}).value || "",
		}))
		.sort((a, b) => a.frame.localeCompare(b.frame));

	const lines = ["# Brief chụp ảnh — dựng theo ảnh tham chiếu", "",
		"Ảnh tham chiếu nằm trong tools/reference/. Chúng là ảnh của công ty khác,",
		"chỉ dùng để mô tả góc máy và bố cục — không đưa lên website.", ""];

	let frame = "";

	for (const row of rows) {
		if (row.frame !== frame) {
			frame = row.frame;
			lines.push("## " + frame);
		}
		lines.push("- Mẫu: " + row.file + (row.alt ? " — " + row.alt : ""));
		lines.push("  Nguồn: " + row.from);
		if (row.note) lines.push("  Cách chụp lại: " + row.note);
	}

	if (!rows.length) lines.push("(chưa gắn ảnh nào với khung)");

	const out = document.getElementById("brief");
	out.hidden = false;
	out.value = lines.join("\\n");
	out.focus();
	out.select();
	document.getElementById("note").textContent = rows.length + " ảnh đã gắn — đã bôi đen sẵn, Ctrl+C để copy";
});
</script>
</html>
`;
}

if (import.meta.url === pathToFileURL(process.argv[1] || "").href) {
	main().catch((error) => {
		console.error(error.message);
		process.exitCode = 1;
	});
}

export { sheet, FRAMES, slug };
