/**
 * Put photographs into the theme, straight from a folder on disk.
 *
 * The theme reads wp-content/themes/annamleaf/assets/photos/<slot>.<ext>. That folder is in
 * the repository, so whatever lands there is what every future install shows — no media
 * library, no featured images, no clicking through wp-admin.
 *
 * This copies files into it. Point it at the folder the client sent and it works out which
 * picture belongs in which frame from the file names, in Vietnamese or English; anything it
 * cannot place it says so rather than guessing.
 *
 *     node tools/set-photos.mjs --list
 *     node tools/set-photos.mjs --from=~/Downloads/anh-khach          xem nó định làm gì
 *     node tools/set-photos.mjs --from=~/Downloads/anh-khach --apply  ghi vào repo
 *     node tools/set-photos.mjs stage-4=~/Desktop/lo-say.jpg --apply  chỉ định thẳng
 *     node tools/set-photos.mjs --clear=leaf-2 --apply                bỏ một khung
 *
 * Nothing is written without --apply.
 */

import { readdir, copyFile, readFile, writeFile, mkdir, stat, unlink, access } from "node:fs/promises";
import { constants } from "node:fs";
import os from "node:os";
import path from "node:path";
import { fileURLToPath, pathToFileURL } from "node:url";

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const OUT = path.join(ROOT, "wp-content/themes/annamleaf/assets/photos");
const CREDITS = path.join(OUT, "credits.json");

const ARGS = process.argv.slice(2);
const APPLY = ARGS.includes("--apply");
const LIST = ARGS.includes("--list");
const FROM = (ARGS.find((a) => a.startsWith("--from=")) || "").split("=").slice(1).join("=");
const CLEAR = ARGS.filter((a) => a.startsWith("--clear=")).map((a) => a.slice(8));
const PAIRS = ARGS.filter((a) => /^[a-z0-9-]+=/.test(a) && !a.startsWith("--"));

const EXTENSIONS = ["jpg", "jpeg", "png", "webp"];

/**
 * Every frame the templates read, with the words that name it in a file.
 *
 * `must` is the slot's own name — a file called stage-4.jpg goes straight in. The rest are
 * how people actually name photographs, in both languages, with Vietnamese accents already
 * stripped because the matcher strips them too.
 */
const SLOTS = [
	{ slot: "home", what: "Ảnh bìa trang chủ — cánh đồng hoặc toàn cảnh vùng trồng",
	  words: ["bia", "cover", "hero", "panorama", "canh dong", "toan canh", "field"] },
	{ slot: "stage-1", what: "Vườn ươm",
	  words: ["uom", "vuon uom", "nursery", "seedling", "seedbed", "khay", "cay con"] },
	{ slot: "stage-2", what: "Đồng ruộng",
	  words: ["dong ruong", "ruong", "luong", "crop", "growing", "plantation", "field"] },
	{ slot: "stage-3", what: "Thu hoạch",
	  words: ["thu hoach", "harvest", "hai la", "picking", "gat"] },
	{ slot: "stage-4", what: "Lò sấy",
	  words: ["lo say", "say", "curing", "cured", "barn", "kiln", "treo"] },
	{ slot: "stage-5", what: "Phân cấp",
	  words: ["phan cap", "phan loai", "grading", "sorting", "grade"] },
	{ slot: "stage-6", what: "Tách cọng, đóng kiện",
	  words: ["day chuyen", "tach cong", "threshing", "baling", "kien", "dong kien", "may"] },
	{ slot: "stage-7", what: "Kho và xuất hàng",
	  words: ["kho", "warehouse", "storage", "container", "xuat hang", "shipping"] },
	{ slot: "leaf-1", what: "Sản phẩm 1 — Lá nguyên",
	  words: ["la nguyen", "whole leaf", "whole", "nguyen la"] },
	{ slot: "leaf-2", what: "Sản phẩm 2 — Lá đã tách cọng (lamina)",
	  words: ["lamina", "tach cong", "threshed", "destemmed"] },
	{ slot: "leaf-3", what: "Sản phẩm 3 — Cọng thuốc lá",
	  words: ["cong", "stem", "stems"] },
	{ slot: "region", what: "Vùng trồng",
	  words: ["vung trong", "region", "valley", "thung lung", "flycam", "drone", "aerial"] },
];

const NAMES = new Set(SLOTS.map((s) => s.slot));

/* ------------------------------------------------------------------ helpers */

const plain = (v) =>
	String(v || "")
		.normalize("NFD").replace(/[̀-ͯ]/g, "")
		.replace(/đ/gi, "d")
		.toLowerCase().replace(/[^a-z0-9]+/g, " ").trim();

const expand = (p) =>
	p.startsWith("~") ? path.join(os.homedir(), p.slice(1)) : path.resolve(p);

async function exists(file) {
	try {
		await access(file, constants.F_OK);
		return true;
	} catch {
		return false;
	}
}

/**
 * The real pixel size, read from the file header — enough to warn about a picture that will
 * look soft in a frame this wide.
 */
function intrinsicSize(buf) {
	if (buf.length < 24) return null;

	const tag = (a, b) => buf.subarray(a, b).toString("latin1");

	if (buf.readUInt32BE(0) === 0x89504e47) return { width: buf.readUInt32BE(16), height: buf.readUInt32BE(20) };
	if (tag(0, 3) === "GIF") return { width: buf.readUInt16LE(6), height: buf.readUInt16LE(8) };

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
			if (buf[i] !== 0xff) { i++; continue; }
			const marker = buf[i + 1];
			if (marker >= 0xc0 && marker <= 0xcf && marker !== 0xc4 && marker !== 0xc8 && marker !== 0xcc) {
				return { height: buf.readUInt16BE(i + 5), width: buf.readUInt16BE(i + 7) };
			}
			i += 2 + buf.readUInt16BE(i + 2);
		}
	}

	return null;
}

/**
 * Which frame does this file name describe? Returns every slot it could be, best first.
 */
function guess(file) {
	const name = plain(path.basename(file, path.extname(file)));
	const scored = [];

	for (const slot of SLOTS) {
		// An exact slot name wins outright: someone named the file for the frame.
		if (name === slot.slot || name.replace(/\s/g, "-") === slot.slot) {
			return [{ slot: slot.slot, score: 100 }];
		}

		let score = 0;

		for (const word of slot.words) {
			if (name.includes(word)) score += word.split(" ").length * 2 + word.length / 8;
		}

		if (score > 0) scored.push({ slot: slot.slot, score: Math.round(score * 10) / 10 });
	}

	return scored.sort((a, b) => b.score - a.score);
}

async function readCredits() {
	if (!(await exists(CREDITS))) return {};

	try {
		return JSON.parse(await readFile(CREDITS, "utf8"));
	} catch {
		return {};
	}
}

/**
 * Write one file into the theme, and drop the slot's temporary-photo credit if it had one.
 */
async function install(slot, source, credits) {
	const extension = path.extname(source).slice(1).toLowerCase();
	const target = path.join(OUT, `${slot}.${extension === "jpeg" ? "jpg" : extension}`);

	for (const other of EXTENSIONS) {
		const stale = path.join(OUT, `${slot}.${other}`);
		if (stale !== target && (await exists(stale))) await unlink(stale);
	}

	await copyFile(source, target);

	// A client photograph is not a borrowed one, so it carries no "TEMPORARY" chip.
	delete credits[slot];

	return path.basename(target);
}

/* ------------------------------------------------------------------ running */

async function collect() {
	const chosen = new Map();
	const notes = [];

	for (const pair of PAIRS) {
		const [slot, ...rest] = pair.split("=");
		const file = expand(rest.join("="));

		if (!NAMES.has(slot)) {
			notes.push(`"${slot}" không phải tên khung — xem --list`);
			continue;
		}

		if (!(await exists(file))) {
			notes.push(`${slot}: không thấy file ${file}`);
			continue;
		}

		chosen.set(slot, { file, why: "bạn chỉ định" });
	}

	if (FROM) {
		const dir = expand(FROM);

		if (!(await exists(dir))) {
			notes.push(`Không thấy thư mục ${dir}`);
			return { chosen, notes };
		}

		const files = (await readdir(dir))
			.filter((f) => EXTENSIONS.includes(path.extname(f).slice(1).toLowerCase()))
			.sort();

		if (!files.length) notes.push(`${dir} không có ảnh nào (${EXTENSIONS.join(", ")})`);

		// A file named on the command line is already placed; do not report it as unguessable.
		const claimed = new Set([...chosen.values()].map((c) => c.file));

		for (const name of files) {
			const file = path.join(dir, name);

			if (claimed.has(file)) continue;

			const ranked = guess(name);

			if (!ranked.length) {
				notes.push(`${name}: không đoán được khung nào — đặt lại tên, hoặc chỉ định slot=đường/dẫn`);
				continue;
			}

			const [best, second] = ranked;

			if (second && second.score === best.score) {
				notes.push(`${name}: lẫn giữa ${best.slot} và ${second.slot} — chỉ định thẳng`);
				continue;
			}

			// An explicit pair already claimed this slot.
			if (chosen.has(best.slot) && chosen.get(best.slot).why === "bạn chỉ định") continue;

			if (chosen.has(best.slot)) {
				notes.push(`${name}: khung ${best.slot} đã nhận ${path.basename(chosen.get(best.slot).file)} — bỏ qua`);
				continue;
			}

			chosen.set(best.slot, { file, why: best.score >= 100 ? "trùng tên khung" : `khớp tên (${best.score})` });
		}
	}

	return { chosen, notes };
}

async function main() {
	if (LIST) {
		console.log("Các khung ảnh của website:\n");
		for (const slot of SLOTS) {
			const here = (await Promise.all(EXTENSIONS.map((e) => exists(path.join(OUT, `${slot.slot}.${e}`)))))
				.some(Boolean);
			console.log(`  ${slot.slot.padEnd(9)} ${here ? "đã có " : "trống "} ${slot.what}`);
		}
		console.log("\nĐặt tên file theo tên khung (stage-4.jpg) là chắc ăn nhất.");
		return;
	}

	await mkdir(OUT, { recursive: true });

	const credits = await readCredits();
	const { chosen, notes } = await collect();

	for (const slot of CLEAR) {
		if (!NAMES.has(slot)) {
			notes.push(`--clear=${slot}: không phải tên khung`);
			continue;
		}

		for (const extension of EXTENSIONS) {
			const file = path.join(OUT, `${slot}.${extension}`);

			if (await exists(file)) {
				console.log(`${slot.padEnd(9)} ${APPLY ? "đã xoá" : "sẽ xoá"} ${slot}.${extension}`);
				if (APPLY) {
					await unlink(file);
					delete credits[slot];
				}
			}
		}
	}

	if (!chosen.size && !CLEAR.length) {
		console.log("Chưa chọn được ảnh nào. Dùng --from=<thư mục> hoặc slot=<đường dẫn>, hoặc --list để xem các khung.");
		notes.forEach((n) => console.log("  " + n));
		process.exitCode = 1;
		return;
	}

	let done = 0;

	for (const slot of SLOTS.map((s) => s.slot)) {
		if (!chosen.has(slot)) continue;

		const { file, why } = chosen.get(slot);
		const bytes = await readFile(file);
		const size = intrinsicSize(bytes);
		const stats = await stat(file);

		const facts = [
			size ? `${size.width}×${size.height}` : "kích thước không đọc được",
			`${(stats.size / 1024).toFixed(0)} KB`,
			why,
		];

		const warn = [];
		if (size && size.width < 1400) warn.push("hơi nhỏ, sẽ mờ khi phóng to");
		if (size && size.height > size.width) warn.push("ảnh dọc, khung là ảnh ngang nên sẽ bị cắt nhiều");
		if (stats.size > 1500 * 1024) warn.push("nặng, nên nén lại dưới 900 KB");

		console.log(`${slot.padEnd(9)} ${path.basename(file)}  [${facts.join(" · ")}]`);
		warn.forEach((w) => console.log(`${" ".repeat(10)}⚠ ${w}`));

		if (APPLY) {
			const written = await install(slot, file, credits);
			console.log(`${" ".repeat(10)}→ assets/photos/${written}`);
			done++;
		}
	}

	if (notes.length) {
		console.log("\nChưa xử lý:");
		notes.forEach((n) => console.log("  " + n));
	}

	if (APPLY) {
		if (Object.keys(credits).length) {
			await writeFile(CREDITS, JSON.stringify(credits, null, "\t") + "\n");
		} else if (await exists(CREDITS)) {
			await unlink(CREDITS);
		}

		console.log(`\n${done} ảnh đã ghi vào wp-content/themes/annamleaf/assets/photos/.`);
		console.log("Xem lại rồi: git add wp-content/themes/annamleaf/assets/photos && git commit");
	} else {
		console.log("\nĐây mới là bản nháp. Thêm --apply để ghi thật.");
	}
}

if (import.meta.url === pathToFileURL(process.argv[1] || "").href) {
	main().catch((error) => {
		console.error(error.message);
		process.exitCode = 1;
	});
}

export { SLOTS, guess, intrinsicSize, plain };
