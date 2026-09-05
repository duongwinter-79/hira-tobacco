/**
 * Pull the photographs off reference sites and build a shot brief from them.
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
 * The sites live in tools/reference-sites.json, not in this file — add one there and it is
 * picked up. Each site names a starting page and how far to wander from it; the crawler
 * finds the product and factory pages itself rather than you listing every URL.
 *
 *     node tools/reference-shots.mjs                      every site in the list
 *     node tools/reference-shots.mjs --only=mibica        just the ones matching
 *     node tools/reference-shots.mjs --url=https://…      only this, ignoring the list
 *     node tools/reference-shots.mjs --list               show the list, visit nothing
 *     open tools/reference/index.html                     look at what came back
 *
 * Results accumulate: a second run adds to the contact sheet rather than replacing it, so
 * you can gather one site today and another tomorrow. --fresh starts over.
 *
 *     --depth=2     wander further from the starting page (overrides the list)
 *     --max=25      more pages per site (overrides the list)
 *     --min=700     ignore images narrower than this
 *     --fresh       forget earlier runs
 *     --sites=PATH  a different site list
 *
 * Needs Playwright and a Chromium. Once, in the repository folder:
 *
 *     npm install --save-dev playwright
 *     npx playwright install chromium
 */

import { mkdir, writeFile, readFile, access, rm } from "node:fs/promises";
import { constants } from "node:fs";
import path from "node:path";
import { createRequire } from "node:module";
import { fileURLToPath, pathToFileURL } from "node:url";

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const OUT = path.join(ROOT, "tools/reference");
const STORE = path.join(OUT, "reference.json");

const ARGS = process.argv.slice(2);
const flag = (name) => (ARGS.find((a) => a.startsWith(`--${name}=`)) || "").split("=").slice(1).join("=");
const num = (name) => { const v = flag(name); return v === "" ? null : Number(v); };

const ONLY = flag("only");
const EXTRA = ARGS.filter((a) => a.startsWith("--url=")).map((a) => a.slice(6));
const SITES_FILE = flag("sites") || path.join(ROOT, "tools/reference-sites.json");
const LIST = ARGS.includes("--list");
const FRESH = ARGS.includes("--fresh");

/**
 * Used when tools/reference-sites.json is missing, so a fresh clone still works.
 */
const FALLBACK = {
	minWidth: 500,
	skipUrl: "(logo|favicon|sprite|avatar|placeholder|/icons?/|icon-)",
	sites: [{ name: "Đồng Việt Thành", start: "https://dongvietthanh.com/", depth: 1, maxPages: 12 }],
};

/**
 * The frames on the Annam Leaf site, as tagging options.
 *
 * These are the real slots the templates read, not a wish list: the code is the name of the
 * bundled file (assets/photos/<code>.jpg) and of the item whose featured image fills it.
 * docs/shot-list.md describes each one.
 */
const FRAMES = [
	["", "— chưa gắn —"],
	["home", "home · Ảnh bìa trang chủ"],
	["stage-1", "stage-1 · Vườn ươm"],
	["stage-2", "stage-2 · Đồng ruộng"],
	["stage-3", "stage-3 · Thu hoạch"],
	["stage-4", "stage-4 · Lò sấy"],
	["stage-5", "stage-5 · Phân cấp"],
	["stage-6", "stage-6 · Tách cọng & đóng kiện"],
	["stage-7", "stage-7 · Kho & xuất hàng"],
	["leaf-1", "leaf-1 · Sản phẩm 1"],
	["leaf-2", "leaf-2 · Sản phẩm 2"],
	["leaf-3", "leaf-3 · Sản phẩm 3"],
	["region", "region · Vùng trồng"],
	["hero-about", "Hero trang About"],
	["hero-leaf", "Hero trang Our Leaf"],
	["hero-process", "Hero trang Process"],
	["hero-quality", "Hero trang Quality"],
	["layout", "Bố cục trang, không phải một khung ảnh"],
];

/* ------------------------------------------------------------------ helpers */

const slug = (v) =>
	String(v || "")
		.normalize("NFD").replace(/[\u0300-\u036f]/g, "")
		.replace(/đ/gi, "d")
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

async function readJson(file, fallback) {
	if (!(await exists(file))) return fallback;

	try {
		return JSON.parse(await readFile(file, "utf8"));
	} catch (error) {
		console.error(`${file}: không đọc được (${error.message})`);
		return fallback;
	}
}

/**
 * The real pixel size, read from the file header rather than from what the page claimed.
 *
 * A lazy-loading <img> reports the size of its 1x1 placeholder, and a CSS background
 * reports the size of the box it fills, so neither number can be trusted for filtering.
 * Returns null for formats not covered (AVIF), which counts as "keep it".
 */
function intrinsicSize(buf) {
	if (buf.length < 24) return null;

	const tag = (start, end) => buf.subarray(start, end).toString("latin1");

	if (buf.readUInt32BE(0) === 0x89504e47) {
		return { width: buf.readUInt32BE(16), height: buf.readUInt32BE(20) };
	}

	if (tag(0, 3) === "GIF") {
		return { width: buf.readUInt16LE(6), height: buf.readUInt16LE(8) };
	}

	if (tag(0, 4) === "RIFF" && tag(8, 12) === "WEBP") {
		const format = tag(12, 16);

		if (format === "VP8 ") return { width: buf.readUInt16LE(26) & 0x3fff, height: buf.readUInt16LE(28) & 0x3fff };
		if (format === "VP8L") {
			const bits = buf.readUInt32LE(21);
			return { width: (bits & 0x3fff) + 1, height: ((bits >> 14) & 0x3fff) + 1 };
		}
		if (format === "VP8X") return { width: buf.readUIntLE(24, 3) + 1, height: buf.readUIntLE(27, 3) + 1 };
	}

	if (buf[0] === 0xff && buf[1] === 0xd8) {
		let i = 2;

		while (i < buf.length - 9) {
			if (buf[i] !== 0xff) {
				i++;
				continue;
			}

			const marker = buf[i + 1];

			// Any start-of-frame marker carries the dimensions; skip the tables in between.
			if (marker >= 0xc0 && marker <= 0xcf && marker !== 0xc4 && marker !== 0xc8 && marker !== 0xcc) {
				return { height: buf.readUInt16BE(i + 5), width: buf.readUInt16BE(i + 7) };
			}

			i += 2 + buf.readUInt16BE(i + 2);
		}
	}

	return null;
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
		"/opt/homebrew/lib/node_modules",
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
		"Không tìm thấy Playwright. Trong thư mục repo chạy:\n" +
		"    npm install --save-dev playwright\n" +
		"    npx playwright install chromium"
	);
}

/**
 * A site entry from the list, with the defaults and command-line overrides applied.
 */
function normalise(site, config) {
	const start = typeof site === "string" ? site : site.start;
	const entry = typeof site === "string" ? {} : site;
	const host = new URL(start).hostname.replace(/^www\./, "");

	return {
		name: entry.name || host,
		start,
		host,
		key: slug(entry.name || host),
		depth: num("depth") ?? entry.depth ?? 0,
		maxPages: num("max") ?? entry.maxPages ?? 8,
		include: entry.include ? new RegExp(entry.include, "i") : null,
		exclude: entry.exclude ? new RegExp(entry.exclude, "i") : null,
		minWidth: num("min") ?? entry.minWidth ?? config.minWidth ?? 500,
		skipUrl: new RegExp(entry.skipUrl || config.skipUrl || "(?!)", "i"),
	};
}

/* ---------------------------------------------------------------- the crawl */

/**
 * Everything worth looking at on one page: the pictures, the links onward, and a
 * screenshot of the whole page so the layout itself is a reference.
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

	return page.evaluate(() => {
		const images = [];
		const seen = new Set();

		const context = (el) => {
			const section = el.closest("section, article, figure, .container, main") || document.body;
			const heading = section.querySelector("h1, h2, h3");
			const caption = el.closest("figure")?.querySelector("figcaption");
			return [caption?.textContent, heading?.textContent, document.title]
				.map((t) => (t || "").replace(/\s+/g, " ").trim())
				.filter(Boolean)[0] || "";
		};

		const add = (src, el, kind, width, height) => {
			if (!src || !/^https?:/i.test(src) || seen.has(src)) return;
			seen.add(src);
			images.push({
				src,
				kind,
				width,
				height,
				alt: (el.getAttribute?.("alt") || "").replace(/\s+/g, " ").trim(),
				context: context(el),
			});
		};

		for (const img of document.querySelectorAll("img")) {
			// Some lazy-loading libraries never swap the real file in, so read their attributes too.
			const lazy = img.getAttribute("data-src") || img.getAttribute("data-original") ||
				img.getAttribute("data-lazy-src") || img.getAttribute("data-srcset")?.split(/\s|,/)[0];

			add(img.currentSrc || img.src, img, "img", img.naturalWidth || img.width || 0, img.naturalHeight || img.height || 0);

			if (lazy) {
				add(new URL(lazy, location.href).href, img, "lazy", img.naturalWidth || img.width || 0, img.naturalHeight || img.height || 0);
			}
		}

		// Hero pictures are very often a CSS background rather than an <img>.
		for (const el of document.querySelectorAll("*")) {
			const bg = getComputedStyle(el).backgroundImage;
			const match = bg && bg.match(/url\((['"]?)(https?:[^'")]+)\1\)/);
			if (!match) continue;
			const box = el.getBoundingClientRect();
			if (box.width < 300 || box.height < 200) continue;
			add(match[2], el, "background", Math.round(box.width), Math.round(box.height));
		}

		const links = [...document.querySelectorAll("a[href]")]
			.map((a) => a.href)
			.filter((h) => /^https?:/i.test(h))
			.map((h) => h.split("#")[0]);

		return { images, links: [...new Set(links)] };
	});
}

/**
 * Walk one site: its starting page, then same-host links up to its depth and page budget.
 */
async function crawl(page, context, site, gathered) {
	const dir = path.join(OUT, site.key);
	const queue = [{ url: site.start, depth: 0 }];
	const visited = new Set();
	const pages = [];

	await mkdir(dir, { recursive: true });

	while (queue.length && pages.length < site.maxPages) {
		const { url, depth } = queue.shift();

		if (visited.has(url)) continue;
		visited.add(url);

		console.log(`  ${url}`);

		let result;

		try {
			result = await readPage(page, url);
		} catch (error) {
			console.error(`    không mở được: ${error.message.split("\n")[0]}`);
			continue;
		}

		const pageSlug = slug(new URL(url).pathname) || "home";
		const shot = `${site.key}/page-${pageSlug}.png`;

		try {
			await page.screenshot({ path: path.join(OUT, shot), fullPage: true });
		} catch (error) {
			console.error(`    không chụp được trang: ${error.message.split("\n")[0]}`);
		}

		const images = [];
		let index = 0;

		for (const item of result.images) {
			// Trust the DOM size only for a loaded <img>; the real check is on the bytes below.
			if (item.kind === "img" && item.width && item.width < site.minWidth) continue;
			if (site.skipUrl.test(item.src)) continue;
			if (gathered.has(item.src)) continue;

			gathered.add(item.src);
			index++;

			const ext = (item.src.match(/\.(jpe?g|png|webp|avif)(?:$|\?)/i) || [, "jpg"])[1].toLowerCase();
			const name = `${pageSlug}-${String(index).padStart(2, "0")}-${slug(item.alt || item.context) || "anh"}.${ext}`;

			try {
				const response = await context.request.get(item.src, { headers: { Referer: url }, timeout: 30000 });

				if (!response.ok()) throw new Error(String(response.status()));

				const body = await response.body();

				// A lazy attribute may point at a placeholder; anything tiny is not a photograph.
				if (body.length < 12000) continue;

				const size = intrinsicSize(body);

				if (size && size.width < site.minWidth) continue;

				await writeFile(path.join(dir, name), body);

				images.push({
					...item,
					width: size ? size.width : item.width,
					height: size ? size.height : item.height,
					file: `${site.key}/${name}`,
					bytes: body.length,
					from: url,
				});
			} catch (error) {
				console.error(`    bỏ qua ${item.src.slice(0, 60)} (${error.message.split("\n")[0]})`);
			}
		}

		console.log(`    ${images.length} ảnh`);

		pages.push({ url, site: site.name, key: site.key, screenshot: shot, images });

		if (depth < site.depth) {
			for (const link of result.links) {
				let host;

				try {
					host = new URL(link).hostname.replace(/^www\./, "");
				} catch {
					continue;
				}

				if (site.include ? !site.include.test(link) : host !== site.host) continue;
				if (site.exclude && site.exclude.test(link)) continue;
				if (visited.has(link) || queue.some((q) => q.url === link)) continue;

				queue.push({ url: link, depth: depth + 1 });
			}
		}

		// One page at a time, with a pause. These are somebody's servers.
		await page.waitForTimeout(1200);
	}

	return pages;
}

/* ------------------------------------------------------------------ running */

async function main() {
	const config = await readJson(SITES_FILE, FALLBACK);

	// --url means "just this, now", so it stands in for the list rather than adding to it.
	let sites = EXTRA.length
		? EXTRA.map((url) => normalise({ start: url }, config))
		: (config.sites || []).map((site) => normalise(site, config));

	if (ONLY) {
		const needle = ONLY.toLowerCase();
		sites = sites.filter((s) => (s.name + " " + s.start + " " + s.key).toLowerCase().includes(needle));
	}

	if (LIST) {
		console.log(`${sites.length} site trong ${path.relative(ROOT, SITES_FILE)}:\n`);
		for (const site of sites) {
			console.log(`  ${site.name.padEnd(22)} ${site.start}`);
			console.log(`  ${"".padEnd(22)} depth ${site.depth} · tối đa ${site.maxPages} trang · ảnh ≥ ${site.minWidth}px`);
		}
		return;
	}

	if (!sites.length) {
		console.error(ONLY ? `Không site nào khớp "${ONLY}".` : "Danh sách site rỗng.");
		process.exitCode = 1;
		return;
	}

	if (FRESH) await rm(OUT, { recursive: true, force: true });

	await mkdir(OUT, { recursive: true });

	// Earlier runs are kept, so sites can be gathered a few at a time.
	const previous = await readJson(STORE, []);
	const chromium = await loadChromium();
	const browser = await chromium.launch();
	const context = await browser.newContext({
		viewport: { width: 1440, height: 900 },
		ignoreHTTPSErrors: true,
		userAgent:
			"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) " +
			"Chrome/124.0 Safari/537.36",
	});
	const page = await context.newPage();

	const gathered = new Set();
	const fresh = [];

	for (const site of sites) {
		console.log(`\n${site.name} — ${site.start}`);

		try {
			fresh.push(...(await crawl(page, context, site, gathered)));
		} catch (error) {
			console.error(`  bỏ site này: ${error.message.split("\n")[0]}`);
		}
	}

	await browser.close();

	// A page visited again replaces its earlier record; everything else is kept.
	const visited = new Set(fresh.map((p) => p.url));
	const all = [...previous.filter((p) => !visited.has(p.url)), ...fresh];

	await writeFile(STORE, JSON.stringify(all, null, "\t") + "\n");
	await writeFile(path.join(OUT, "index.html"), sheet(all));

	const count = all.reduce((n, p) => n + p.images.length, 0);
	const added = fresh.reduce((n, p) => n + p.images.length, 0);

	console.log(`\n${added} ảnh mới · tổng ${count} ảnh từ ${all.length} trang.`);
	console.log("\nMở bảng tham chiếu:");
	console.log("  macOS:   open tools/reference/index.html");
	console.log("  Windows: start tools\\reference\\index.html");
	console.log("\nGắn mỗi ảnh với khung tương ứng rồi bấm “Tổng hợp brief” — đó là bản mô tả");
	console.log("để đưa người chụp ảnh ở nhà máy. Ảnh trong thư mục này là tài liệu tham chiếu,");
	console.log("không đưa lên website: chúng là nhà máy của công ty khác.");
}

/* --------------------------------------------------------------- the sheet */

function sheet(pages) {
	const options = FRAMES.map(([v, l]) => `<option value="${esc(v)}">${esc(l)}</option>`).join("");
	const bySite = new Map();

	for (const page of pages) {
		if (!bySite.has(page.site)) bySite.set(page.site, []);
		bySite.get(page.site).push(page);
	}

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

	const sections = [...bySite].map(([site, list]) => `
		<section>
			<h2>${esc(site)}<span class="dim"> — ${list.length} trang, ${list.reduce((n, p) => n + p.images.length, 0)} ảnh</span></h2>
			${list.map((page) => `
			<div class="page">
				<p class="dim"><a href="${esc(page.url)}" target="_blank" rel="noopener">${esc(page.url)}</a>
				· <a href="${esc(page.screenshot)}" target="_blank" rel="noopener">ảnh chụp toàn trang</a></p>
				${page.images.length ? `<div class="grid">${cards(page)}</div>` : `<p class="dim">Không lấy được ảnh nào đủ lớn.</p>`}
			</div>`).join("")}
		</section>`).join("");

	return `<!doctype html>
<html lang="vi">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tham chiếu ảnh — Annam Leaf</title>
<style>
	:root { color-scheme: light dark; --line:#d7d2c7; --ink:#1d2016; --dim:#6b6a5e; --bg:#faf8f3; --card:#fff; --gold:#9C6F14; }
	@media (prefers-color-scheme: dark) { :root { --line:#3a3a33; --ink:#ece9e1; --dim:#a09d92; --bg:#16170f; --card:#20211a; --gold:#D6A73F; } }
	body { margin:0; background:var(--bg); color:var(--ink); font:15px/1.5 system-ui,-apple-system,"Segoe UI",sans-serif; }
	header, section, footer { max-width:1280px; margin:0 auto; padding:0 20px; }
	header { padding-top:32px; }
	h1 { font-size:24px; margin:0 0 8px; }
	h2 { font-size:19px; margin:38px 0 2px; padding-bottom:6px; border-bottom:1px solid var(--line); }
	.page { margin-top:16px; }
	.lede { color:var(--dim); max-width:78ch; }
	.warn { background:rgba(156,111,20,.12); border-left:3px solid var(--gold); padding:14px 16px; margin:18px 0 0; max-width:78ch; }
	.dim { color:var(--dim); font-size:13px; font-weight:400; }
	.grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:16px; margin-top:10px; }
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

export { sheet, FRAMES, slug, normalise, intrinsicSize };
