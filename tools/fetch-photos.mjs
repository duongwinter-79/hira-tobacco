/**
 * Download the default photographs into the theme.
 *
 * The theme ships with its own pictures so a fresh install looks finished with no clicking
 * and no database rows. This gathers candidates from several free-licence libraries, scores
 * them, and saves the best one per frame into
 * wp-content/themes/annamleaf/assets/photos/, with credits.json beside them.
 *
 * Why scoring rather than "first result": the first version took whatever a search returned
 * first, and Commons answered "tobacco leaves sorting" with a 19th century engraving of
 * enslaved people and "tobacco factory" with a building in Bristol that carries that name.
 * A candidate now has to earn its place — subject words present, recent, landscape, big
 * enough — and anything below the bar is left to the theme's illustration instead.
 *
 *     node tools/fetch-photos.mjs                 fill the empty frames
 *     node tools/fetch-photos.mjs --force         replace what is already there
 *     node tools/fetch-photos.mjs --dry-run       show what it would pick, download nothing
 *     node tools/fetch-photos.mjs --slot=stage-5  one frame only
 *     node tools/fetch-photos.mjs --show=5        list the top 5 candidates per frame
 *
 * Sources: Wikimedia Commons and Openverse need no credentials. Pexels and Unsplash join in
 * when PEXELS_API_KEY / UNSPLASH_ACCESS_KEY are set in the environment — they carry far more
 * modern agricultural photography than Commons does.
 *
 * Whatever it picks still needs a human look before committing. A search result is not a
 * picture editor.
 */

import { mkdir, writeFile, access, readFile } from "node:fs/promises";
import { constants } from "node:fs";
import path from "node:path";
import { fileURLToPath, pathToFileURL } from "node:url";

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const OUT = path.join(ROOT, "wp-content/themes/annamleaf/assets/photos");

const ARGS = process.argv.slice(2);
const FORCE = ARGS.includes("--force");
const DRY_RUN = ARGS.includes("--dry-run");
const ONLY = (ARGS.find((a) => a.startsWith("--slot=")) || "").split("=")[1] || "";
const SHOW = Number((ARGS.find((a) => a.startsWith("--show=")) || "").split("=")[1] || 0);

const MAX_BYTES = 900 * 1024;
const MIN_SCORE = 6;

/**
 * One entry per image frame.
 *
 * `must` are the words that make a picture about the right thing at all — a candidate
 * without one of them cannot win the frame. `good` words lift a candidate that is not just
 * on subject but on the right part of it, and `avoid` words push down the near misses that
 * keep surfacing: shop signs, museum pieces, packets of cigarettes.
 */
const SLOTS = [
	{
		slot: "home",
		queries: ["Cao Bang Vietnam landscape", "Cao Bang province rice fields", "Vietnam karst mountains fields"],
		must: ["cao bang", "cao bằng", "vietnam", "viet nam"],
		good: ["landscape", "field", "mountain", "rice", "valley", "karst", "farm"],
		avoid: ["city", "street", "temple", "market", "portrait", "waterfall tourists"],
	},
	{
		slot: "stage-1",
		queries: ["tobacco seedbed", "tobacco seedlings", "seedling tray nursery farm", "young tobacco plants field"],
		must: ["tobacco", "seedling", "nicotiana", "nursery", "seedbed"],
		good: ["seedling", "young", "nursery", "tray", "planting", "greenhouse", "sprout"],
		avoid: ["cigarette", "pipe", "smoking", "shop", "packet"],
	},
	{
		slot: "stage-2",
		queries: ["tobacco field", "tobacco plantation crop", "Nicotiana tabacum growing", "tobacco farm rows"],
		must: ["tobacco", "nicotiana"],
		good: ["field", "crop", "farm", "growing", "rows", "plantation", "leaves", "green"],
		avoid: ["cigarette", "smoking", "museum", "sign", "packet", "factory building"],
	},
	{
		slot: "stage-3",
		queries: ["tobacco harvesting", "tobacco leaf picking", "harvesting tobacco leaves farmer"],
		must: ["tobacco", "nicotiana"],
		good: ["harvest", "picking", "farmer", "worker", "leaves", "basket", "cutting"],
		avoid: ["cigarette", "smoking", "museum", "packet"],
	},
	{
		slot: "stage-4",
		queries: ["tobacco curing barn", "tobacco drying barn", "tobacco leaves hanging drying", "tobacco kiln"],
		must: ["tobacco", "curing", "barn", "drying"],
		good: ["barn", "curing", "drying", "hanging", "kiln", "leaves", "shed"],
		avoid: ["cigarette", "smoking", "ruin", "abandoned", "museum"],
	},
	{
		slot: "stage-5",
		queries: ["dried tobacco leaves", "tobacco leaves sorting", "tobacco leaf grading", "bundle of tobacco leaves"],
		must: ["tobacco", "nicotiana"],
		good: ["dried", "leaves", "sorting", "grading", "bundle", "hands", "stack"],
		avoid: ["cigarette", "cigar", "smoking", "pipe", "museum", "packet", "shop"],
	},
	{
		slot: "stage-6",
		queries: ["tobacco bales warehouse", "baled tobacco leaves", "tobacco processing machine", "tobacco leaf warehouse interior"],
		must: ["tobacco"],
		good: ["bale", "warehouse", "processing", "machine", "factory interior", "stack", "conveyor"],
		avoid: ["cigarette", "smoking", "facade", "building exterior", "street", "theatre", "pub", "museum"],
	},
	{
		slot: "stage-7",
		queries: ["shipping container terminal", "container port loading crane", "cargo containers stacked port"],
		must: ["container", "port", "terminal", "cargo"],
		good: ["container", "terminal", "crane", "port", "stacked", "loading", "ship"],
		avoid: ["model", "toy", "diagram", "map", "house", "architecture"],
	},
];

/**
 * Artwork, archive scans and museum objects are never right for a working supplier's site.
 */
const REJECT = [
	"engraving", "lithograph", "etching", "woodcut", "drawing", "painting", "sketch",
	"illustration", "poster", "advertisement", "postcard", "map", "diagram", "chart",
	"logo", "coat of arms", "stamp", "banknote", "cigarette card", "trade card",
	"kitlv", "lccn", "wellcome", "tropenmuseum", "rijksmuseum", "collectie", "nationaal archief",
	"slave", "slavery", "colonial", "maatschappij", "herbarium", "specimen", "engraved",
];

const OLDEST_YEAR = 1990;
const UA = "AnnamLeafPhotoFetch/1.0 (WordPress site build; contact via repository)";

/* ------------------------------------------------------------------ sources */

async function getJson(url, headers = {}) {
	const response = await fetch(url, { headers: { "User-Agent": UA, ...headers } });

	if (!response.ok) {
		throw new Error(`${response.status}`);
	}

	return response.json();
}

const strip = (v) => String(v || "").replace(/<[^>]*>/g, " ").replace(/\s+/g, " ").trim();

/**
 * Wikimedia Commons. Free licences, no key, but heavy on archive material.
 */
async function fromCommons(query) {
	const url =
		"https://commons.wikimedia.org/w/api.php?action=query&format=json&generator=search" +
		`&gsrsearch=${encodeURIComponent(query + " filetype:bitmap")}` +
		"&gsrnamespace=6&gsrlimit=12&prop=imageinfo&iiprop=url|size|extmetadata&iiurlwidth=1600";

	const data = await getJson(url);
	const pages = data?.query?.pages ? Object.values(data.query.pages) : [];

	return pages.flatMap((page) => {
		const info = page.imageinfo?.[0];

		if (!info?.thumburl) return [];

		const meta = info.extmetadata || {};
		const artist = strip(meta.Artist?.value);
		const licence = strip(meta.LicenseShortName?.value) || "Wikimedia Commons";

		return [{
			source: "commons",
			title: strip(page.title).replace(/^File:/, ""),
			description: strip(meta.ImageDescription?.value),
			tags: strip(meta.Categories?.value).split("|").join(" "),
			date: strip(meta.DateTimeOriginal?.value) || strip(meta.DateTime?.value),
			width: Number(info.width) || 0,
			height: Number(info.height) || 0,
			url: info.thumburl,
			smaller: info.thumburl.replace(/\/\d+px-/, "/1100px-"),
			credit: `${artist ? artist + " · " : ""}${licence} · Wikimedia Commons`,
			page: `https://commons.wikimedia.org/wiki/${encodeURIComponent(page.title || "")}`,
		}];
	});
}

/**
 * Openverse, the CC search WordPress itself runs. Aggregates Flickr and museum APIs, so it
 * reaches modern documentary photography Commons does not have. No key needed.
 */
async function fromOpenverse(query) {
	const url =
		"https://api.openverse.org/v1/images/?" +
		`q=${encodeURIComponent(query)}` +
		"&license_type=commercial,modification&page_size=20&mature=false&aspect_ratio=wide";

	const data = await getJson(url);

	return (data?.results || []).flatMap((item) => {
		if (!item?.url) return [];

		const licence = [item.license, item.license_version].filter(Boolean).join(" ").toUpperCase();

		return [{
			source: "openverse",
			title: strip(item.title),
			description: strip(item.description),
			tags: (item.tags || []).map((t) => t.name).join(" "),
			date: strip(item.date_taken || item.created_on),
			width: Number(item.width) || 0,
			height: Number(item.height) || 0,
			url: item.url,
			smaller: item.thumbnail || item.url,
			credit: `${item.creator ? item.creator + " · " : ""}${licence || "CC"} · ${item.source || "Openverse"}`,
			page: item.foreign_landing_url || item.url,
		}];
	});
}

/**
 * Pexels. Curated modern stock, free for commercial use. Needs PEXELS_API_KEY.
 */
async function fromPexels(query) {
	const key = process.env.PEXELS_API_KEY;

	if (!key) return [];

	const url = `https://api.pexels.com/v1/search?query=${encodeURIComponent(query)}&orientation=landscape&per_page=15`;
	const data = await getJson(url, { Authorization: key });

	return (data?.photos || []).flatMap((photo) => {
		if (!photo?.src) return [];

		return [{
			source: "pexels",
			title: strip(photo.alt),
			description: strip(photo.alt),
			tags: strip(photo.alt),
			date: "",
			width: Number(photo.width) || 0,
			height: Number(photo.height) || 0,
			url: photo.src.large2x || photo.src.large,
			smaller: photo.src.large || photo.src.medium,
			credit: `${photo.photographer || "Pexels"} · Pexels`,
			page: photo.url,
		}];
	});
}

/**
 * Unsplash. Same idea as Pexels. Needs UNSPLASH_ACCESS_KEY.
 */
async function fromUnsplash(query) {
	const key = process.env.UNSPLASH_ACCESS_KEY;

	if (!key) return [];

	const url = `https://api.unsplash.com/search/photos?query=${encodeURIComponent(query)}&orientation=landscape&per_page=15`;
	const data = await getJson(url, { Authorization: `Client-ID ${key}` });

	return (data?.results || []).flatMap((photo) => {
		if (!photo?.urls) return [];

		return [{
			source: "unsplash",
			title: strip(photo.alt_description || photo.description),
			description: strip(photo.description || photo.alt_description),
			tags: (photo.tags || []).map((t) => t.title).join(" "),
			date: strip(photo.created_at),
			width: Number(photo.width) || 0,
			height: Number(photo.height) || 0,
			url: `${photo.urls.raw}&w=1600&fit=max&q=80&fm=jpg`,
			smaller: `${photo.urls.raw}&w=1100&fit=max&q=80&fm=jpg`,
			credit: `${photo.user?.name || "Unsplash"} · Unsplash`,
			page: photo.links?.html || "",
		}];
	});
}

const SOURCES = [
	{ name: "Wikimedia Commons", fn: fromCommons, bonus: 0 },
	{ name: "Openverse", fn: fromOpenverse, bonus: 1 },
	{ name: "Pexels", fn: fromPexels, bonus: 3 },
	{ name: "Unsplash", fn: fromUnsplash, bonus: 3 },
];

/* ------------------------------------------------------------------ scoring */

function yearIn(text) {
	const match = String(text || "").match(/\b(1[6-9]\d{2}|20\d{2})\b/);

	return match ? Number(match[1]) : 0;
}

/**
 * How well does this candidate fit the frame? Negative means "do not use".
 */
function score(candidate, slot, sourceBonus) {
	const text = `${candidate.title} ${candidate.description} ${candidate.tags}`.toLowerCase();

	if (REJECT.some((term) => text.includes(term))) return { total: -100, why: "archive or artwork" };

	const titleYear = yearIn(candidate.title);
	const shotYear = yearIn(candidate.date);

	if (titleYear && titleYear < OLDEST_YEAR) return { total: -100, why: `dated ${titleYear}` };
	if (shotYear && shotYear < OLDEST_YEAR) return { total: -100, why: `taken ${shotYear}` };

	if (!slot.must.some((word) => text.includes(word))) return { total: -100, why: "not on subject" };

	const ratio = candidate.height ? candidate.width / candidate.height : 0;

	if (candidate.width < 1200) return { total: -100, why: `only ${candidate.width}px wide` };
	if (ratio < 1.2) return { total: -100, why: "portrait or square" };

	let total = 5 + sourceBonus;
	const why = [];

	const hits = slot.good.filter((word) => text.includes(word)).length;
	total += Math.min(hits, 4) * 1.5;
	if (hits) why.push(`${hits} subject words`);

	const misses = slot.avoid.filter((word) => text.includes(word)).length;
	total -= misses * 3;
	if (misses) why.push(`${misses} off-subject words`);

	if (shotYear >= 2010) {
		total += 3;
		why.push(`taken ${shotYear}`);
	} else if (shotYear >= OLDEST_YEAR) {
		total += 1;
	} else if (!shotYear) {
		why.push("no date");
	}

	if (ratio >= 1.4 && ratio <= 2.1) total += 2;
	if (candidate.width >= 1600) total += 1;

	// A description of any length means someone said what the picture shows.
	if (candidate.description.length > 30) total += 1;

	return { total: Math.round(total * 10) / 10, why: why.join(", ") || "on subject" };
}

/* ------------------------------------------------------------------ running */

async function download(url) {
	const response = await fetch(url, { headers: { "User-Agent": UA } });

	if (!response.ok) throw new Error(`download failed (${response.status})`);

	return Buffer.from(await response.arrayBuffer());
}

async function exists(file) {
	try {
		await access(file, constants.F_OK);
		return true;
	} catch {
		return false;
	}
}

/**
 * Every candidate for one frame, from every source, scored and sorted.
 */
async function candidatesFor(slot) {
	const seen = new Set();
	const scored = [];

	for (const source of SOURCES) {
		for (const query of slot.queries) {
			let batch = [];

			try {
				batch = await source.fn(query);
			} catch (error) {
				console.error(`    ${source.name}: "${query}" failed (${error.message})`);
				continue;
			}

			for (const candidate of batch) {
				if (seen.has(candidate.url)) continue;

				seen.add(candidate.url);

				const result = score(candidate, slot, source.bonus);

				if (result.total > 0) {
					scored.push({ ...candidate, score: result.total, why: result.why, query });
				}
			}
		}
	}

	return scored.sort((a, b) => b.score - a.score);
}

async function main() {
	await mkdir(OUT, { recursive: true });

	const creditsFile = path.join(OUT, "credits.json");
	let credits = {};

	if (await exists(creditsFile)) {
		try {
			credits = JSON.parse(await readFile(creditsFile, "utf8"));
		} catch {
			credits = {};
		}
	}

	const enabled = SOURCES.filter((s) => s.fn !== fromPexels || process.env.PEXELS_API_KEY)
		.filter((s) => s.fn !== fromUnsplash || process.env.UNSPLASH_ACCESS_KEY);

	console.log(`Sources: ${enabled.map((s) => s.name).join(", ")}`);

	if (!process.env.PEXELS_API_KEY && !process.env.UNSPLASH_ACCESS_KEY) {
		console.log("Set PEXELS_API_KEY or UNSPLASH_ACCESS_KEY for modern stock photography too.\n");
	}

	let saved = 0;
	let kept = 0;
	let skipped = 0;

	for (const slot of SLOTS) {
		if (ONLY && ONLY !== slot.slot) continue;

		const target = path.join(OUT, `${slot.slot}.jpg`);

		if (!FORCE && !DRY_RUN && (await exists(target))) {
			console.log(`${slot.slot.padEnd(9)} kept (already downloaded)`);
			kept++;
			continue;
		}

		console.log(`${slot.slot.padEnd(9)} searching…`);

		const ranked = await candidatesFor(slot);

		if (SHOW) {
			ranked.slice(0, SHOW).forEach((c, i) => {
				console.log(`    ${i + 1}. ${String(c.score).padStart(5)}  ${c.source.padEnd(9)} ${c.title.slice(0, 60)}  (${c.why})`);
			});
		}

		const best = ranked[0];

		if (!best || best.score < MIN_SCORE) {
			console.log(`${slot.slot.padEnd(9)} nothing good enough (best ${best ? best.score : "none"}) — leaving the illustration`);
			skipped++;
			continue;
		}

		console.log(`${slot.slot.padEnd(9)} ${best.score} from ${best.source}: ${best.title.slice(0, 60)} (${best.why})`);

		if (DRY_RUN) continue;

		try {
			let bytes = await download(best.url);

			if (bytes.length > MAX_BYTES && best.smaller && best.smaller !== best.url) {
				bytes = await download(best.smaller);
			}

			if (bytes.length > MAX_BYTES * 2) {
				console.log(`${slot.slot.padEnd(9)} too heavy (${(bytes.length / 1024).toFixed(0)} KB) — skipped`);
				skipped++;
				continue;
			}

			await writeFile(target, bytes);

			credits[slot.slot] = {
				credit: best.credit,
				source: best.page,
				title: best.title,
				query: best.query,
				score: best.score,
			};

			console.log(`${slot.slot.padEnd(9)} saved ${(bytes.length / 1024).toFixed(0)} KB — ${best.credit}`);
			saved++;
		} catch (error) {
			console.error(`${slot.slot.padEnd(9)} ${error.message}`);
			skipped++;
		}
	}

	if (!DRY_RUN && Object.keys(credits).length) {
		await writeFile(creditsFile, JSON.stringify(credits, null, "\t") + "\n");
	}

	console.log(`\n${saved} downloaded, ${kept} kept, ${skipped} left empty.`);
	console.log("Open every file before committing — a search result is not a picture editor.");

	if (!saved && !kept) process.exitCode = 1;
}

// Importable for the scoring test; only runs when invoked directly.
if (import.meta.url === pathToFileURL(process.argv[1] || "").href) {
	main().catch((error) => {
		console.error(error.message);
		process.exitCode = 1;
	});
}

export { SLOTS, score, REJECT };
