/**
 * Gather the default photographs for the theme.
 *
 * The theme ships with its own pictures so a fresh install looks finished with no clicking
 * and no database rows. They live in wp-content/themes/annamleaf/assets/photos/ with
 * credits.json beside them.
 *
 * It covers the frames a stock photograph can honestly stand in for: the cover, the seven
 * process stages and the growing region. The product frames (leaf-1 to leaf-3) are not here
 * on purpose — a stock picture of somebody else's dried leaf tells a buyer this is the grade
 * on offer, which is a claim about the goods. Those stay as illustrations until the client
 * photographs their own.
 *
 * A search engine has no eyes and neither has this script: it can only read the words
 * someone typed next to a picture. Two runs proved how far that gets you — Commons answered
 * "tobacco leaves sorting" with a 19th century engraving of enslaved people, and "shipping
 * container terminal" with a soldier at an airport terminal. So the script no longer decides.
 * It shortlists, and you pick with your eyes:
 *
 *     node tools/fetch-photos.mjs              search, build tools/photo-review.html
 *     start tools/photo-review.html            look at the shortlist, tick one per frame
 *                                              (macOS/Linux: open tools/photo-review.html)
 *     node tools/fetch-photos.mjs --apply      download exactly what you ticked
 *
 * Other flags:
 *     --slot=stage-5     one frame only
 *     --show=5           print the top 5 per frame in the terminal as well
 *     --picks=PATH       read the picks file from somewhere else
 *     --auto             skip the review, take the top scorer (not recommended)
 *     --force            with --auto or --apply, overwrite files already downloaded
 *
 * Sources: Wikimedia Commons and Openverse need no credentials. Pexels and Unsplash join in
 * when PEXELS_API_KEY / UNSPLASH_ACCESS_KEY are set — they carry far more modern working
 * agriculture than Commons, which is mostly archive scans.
 */

import { mkdir, writeFile, access, readFile } from "node:fs/promises";
import { constants } from "node:fs";
import os from "node:os";
import path from "node:path";
import { fileURLToPath, pathToFileURL } from "node:url";

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const OUT = path.join(ROOT, "wp-content/themes/annamleaf/assets/photos");
const REVIEW = path.join(ROOT, "tools/photo-review.html");
const PICKS = path.join(ROOT, "tools/photo-picks.json");

const ARGS = process.argv.slice(2);
const APPLY = ARGS.includes("--apply");
const AUTO = ARGS.includes("--auto");
const FORCE = ARGS.includes("--force");
const ONLY = (ARGS.find((a) => a.startsWith("--slot=")) || "").split("=")[1] || "";
const SHOW = Number((ARGS.find((a) => a.startsWith("--show=")) || "").split("=")[1] || 0);
const PICKS_ARG = (ARGS.find((a) => a.startsWith("--picks=")) || "").split("=")[1] || "";

const MAX_BYTES = 900 * 1024;
const MIN_SCORE = 6;
const SHORTLIST = 8;

/**
 * One entry per image frame.
 *
 * `must` is a list of groups, and a candidate has to satisfy every group to be a candidate
 * at all — "container" AND a harbour word, "tobacco" AND a curing word. One loose keyword is
 * what let a military photograph win the shipping frame on the word "terminal".
 *
 * The words have to appear in the title or the description. Tags are machine-made noise on
 * most libraries and only count towards `good`.
 */
const SLOTS = [
	{
		slot: "home",
		shows: "Cover: the growing region — Cao Bằng landscape, karst hills and farmland",
		queries: ["Cao Bang Vietnam landscape", "Cao Bang province rice fields", "Vietnam karst mountains farmland", "northern Vietnam countryside hills"],
		must: [
			["cao bang", "cao bằng", "vietnam", "viet nam"],
			["landscape", "field", "fields", "mountain", "hill", "rice", "valley", "karst", "farm", "countryside", "terrace", "paddy", "village"],
		],
		good: ["cao bang", "karst", "terrace", "paddy", "valley", "green", "mountain", "farmland", "morning"],
		avoid: ["city", "street", "temple", "market", "portrait", "hotel", "traffic", "monument", "waterfall"],
	},
	{
		slot: "region",
		shows: "Growing regions: the growing area seen wide, or one valley from above",
		queries: ["Cao Bang Vietnam valley", "Vietnam northern highlands farmland", "Cao Bang terraced fields", "Vietnam mountain valley farms"],
		must: [
			["cao bang", "cao bằng", "vietnam", "viet nam"],
			["valley", "landscape", "field", "fields", "farm", "farmland", "terrace", "rice", "mountain", "countryside", "village", "aerial"],
		],
		good: ["cao bang", "valley", "terrace", "aerial", "farmland", "karst", "green", "paddy"],
		avoid: ["city", "street", "temple", "market", "portrait", "hotel", "monument", "waterfall"],
	},
	{
		slot: "stage-1",
		shows: "Seedbeds and nursery: trays or beds of young tobacco plants",
		queries: ["tobacco seedbed", "tobacco seedlings nursery", "young tobacco plants field", "tobacco transplanting"],
		must: [
			["tobacco", "nicotiana"],
			["seedling", "seedlings", "seedbed", "seed bed", "nursery", "young plant", "young plants", "transplant", "transplanting", "tray", "greenhouse", "sprout", "plantlet"],
		],
		good: ["seedling", "nursery", "tray", "planting", "greenhouse", "row", "irrigation", "farmer"],
		avoid: ["cigarette", "pipe", "smoking", "shop", "packet", "herbarium"],
	},
	{
		slot: "stage-2",
		shows: "Field and agronomy: a tobacco crop growing in the open, in colour",
		queries: ["tobacco field", "tobacco crop growing", "Nicotiana tabacum plantation rows", "tobacco farm leaves green"],
		must: [
			["tobacco", "nicotiana"],
			["field", "fields", "crop", "farm", "farmland", "plantation", "growing", "plants", "leaves", "rows", "agriculture", "cultivation"],
		],
		good: ["green", "row", "rows", "crop", "farmer", "growing", "flowering", "irrigation", "sky"],
		avoid: ["cigarette", "smoking", "museum", "sign", "packet", "factory building", "dried"],
	},
	{
		slot: "stage-3",
		shows: "Harvest: leaves being primed or picked by hand",
		queries: ["tobacco harvesting", "tobacco leaf picking farmer", "harvesting tobacco leaves by hand", "tobacco priming harvest"],
		must: [
			["tobacco", "nicotiana"],
			["harvest", "harvesting", "picking", "picked", "cutting", "priming", "reaping", "farmer", "worker", "workers", "labour", "hands"],
		],
		good: ["harvest", "picking", "farmer", "basket", "cart", "carrying", "bundle", "field"],
		avoid: ["cigarette", "smoking", "museum", "packet", "machine gun"],
	},
	{
		slot: "stage-4",
		shows: "Curing: leaves hanging in a working barn or kiln",
		queries: ["tobacco curing barn interior", "tobacco leaves hanging drying", "flue cured tobacco kiln", "tobacco drying shed leaves"],
		must: [
			["tobacco"],
			["curing", "cured", "cure", "drying", "dried", "barn", "kiln", "shed", "hanging", "hung", "rack", "racks", "flue"],
		],
		good: ["hanging", "curing", "kiln", "interior", "leaves", "rack", "flue", "stick", "bamboo"],
		avoid: ["cigarette", "smoking", "ruin", "abandoned", "derelict", "lawn", "park", "signpost", "exterior"],
	},
	{
		slot: "stage-5",
		shows: "Grading and baling: cured leaf being sorted, graded or tied into hands",
		queries: ["tobacco leaf grading", "sorting dried tobacco leaves", "tobacco leaves bundle hands", "tobacco grading warehouse"],
		must: [
			["tobacco"],
			["leaf", "leaves", "grading", "graded", "sorting", "sorted", "bundle", "bundles", "bale", "bales", "stack", "hands", "selection", "classing"],
		],
		good: ["grading", "sorting", "bundle", "stack", "hands", "worker", "table", "dried", "golden"],
		avoid: ["cigarette", "cigar", "smoking", "pipe", "museum", "packet", "shop", "rolling"],
	},
	{
		slot: "stage-6",
		shows: "Processing: threshing line, bales or a working leaf warehouse",
		queries: ["tobacco bales warehouse", "tobacco processing plant machinery", "tobacco threshing line", "baled tobacco leaf storage"],
		must: [
			["tobacco"],
			["warehouse", "processing", "threshing", "thresher", "bale", "bales", "baling", "conveyor", "machinery", "machine", "plant interior", "production line", "factory floor"],
		],
		good: ["bale", "warehouse", "conveyor", "machinery", "stack", "pallet", "interior", "worker"],
		avoid: ["facade", "exterior", "street", "theatre", "pub", "museum", "chimney", "cigarette", "advert"],
	},
	{
		slot: "stage-7",
		shows: "Storage and shipping: containers on a working quay",
		queries: ["shipping container terminal crane", "container port loading ship", "cargo containers stacked quay", "container terminal gantry crane"],
		must: [
			["container", "containers", "containerised", "containerized"],
			["port", "terminal", "dock", "docks", "quay", "harbour", "harbor", "crane", "cranes", "ship", "vessel", "yard", "wharf", "freight"],
		],
		good: ["crane", "gantry", "stacked", "quay", "loading", "ship", "terminal", "berth"],
		avoid: ["model", "toy", "diagram", "map", "house", "architecture", "airport", "soldier", "office"],
	},
];

/**
 * Words that disqualify a picture outright, wherever they appear.
 *
 * Artwork and archive scans, because this is a working supplier's site and not a history
 * lesson. Museums and heritage exhibits, because a barn on a mown lawn with a signboard is
 * not a curing barn in use. Anything military, because that is how a US Air Force photograph
 * reached the shipping frame.
 */
const REJECT = [
	// Artwork and print
	"engraving", "engraved", "lithograph", "etching", "woodcut", "drawing", "painting",
	"sketch", "illustration", "poster", "advertisement", "advert", "postcard", "map",
	"diagram", "chart", "logo", "coat of arms", "stamp", "banknote", "cigarette card",
	"trade card", "label design", "packaging",
	// Archive scans and the collections they come from
	"kitlv", "lccn", "wellcome", "tropenmuseum", "rijksmuseum", "collectie", "nationaal archief",
	"bundesarchiv", "national archives", "state library", "photograph collection", "glass plate",
	"black and white", "black-and-white", "monochrome", "sepia", "scanned", "scan of",
	"archival", "archive photo", "historic photograph", "historical photograph",
	// History we will not put on a leaf merchant's home page
	"slave", "slavery", "colonial", "maatschappij", "plantation era", "indentured",
	// Objects behind glass
	"museum", "heritage centre", "heritage center", "open air museum", "replica", "monument",
	"memorial", "statue", "exhibit", "exhibition", "reconstruction", "listed building",
	"herbarium", "specimen", "botanical plate",
	// Not our subject at all
	"soldier", "soldiers", "military", "army", "navy", "air force", "airman", "troops",
	"uniform", "war", "battalion", "regiment", "airport", "aircraft", "wedding", "protest",
];

const OLDEST_YEAR = 1995;
const UA = "AnnamLeafPhotoFetch/2.0 (WordPress site build; contact via repository)";

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
		"&gsrnamespace=6&gsrlimit=16&prop=imageinfo&iiprop=url|size|extmetadata&iiurlwidth=1600";

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
			title: strip(page.title).replace(/^File:/, "").replace(/\.[a-z]+$/i, ""),
			description: strip(meta.ImageDescription?.value),
			tags: strip(meta.Categories?.value).split("|").join(" "),
			date: strip(meta.DateTimeOriginal?.value) || strip(meta.DateTime?.value),
			width: Number(info.width) || 0,
			height: Number(info.height) || 0,
			url: info.thumburl,
			smaller: info.thumburl.replace(/\/\d+px-/, "/1100px-"),
			thumb: info.thumburl.replace(/\/\d+px-/, "/480px-"),
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
			thumb: item.thumbnail || item.url,
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

	const url = `https://api.pexels.com/v1/search?query=${encodeURIComponent(query)}&orientation=landscape&per_page=20`;
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
			thumb: photo.src.medium || photo.src.small,
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

	const url = `https://api.unsplash.com/search/photos?query=${encodeURIComponent(query)}&orientation=landscape&per_page=20`;
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
			thumb: `${photo.urls.raw}&w=480&fit=max&q=70&fm=jpg`,
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
	const match = String(text || "").match(/\b(1[6-9]\d{2}|20[0-2]\d)\b/);

	return match ? Number(match[1]) : 0;
}

/**
 * How well does this candidate fit the frame? Negative means "do not offer it at all".
 */
function score(candidate, slot, sourceBonus) {
	const words = (v) => String(v || "").toLowerCase();
	const subject = `${words(candidate.title)} ${words(candidate.description)}`;
	const text = `${subject} ${words(candidate.tags)}`;

	const banned = REJECT.find((term) => text.includes(term));
	if (banned) return { total: -100, why: `rejected on "${banned}"` };

	const titleYear = yearIn(candidate.title);
	const shotYear = yearIn(candidate.date);
	const anyYear = yearIn(subject);

	if (titleYear && titleYear < OLDEST_YEAR) return { total: -100, why: `dated ${titleYear}` };
	if (shotYear && shotYear < OLDEST_YEAR) return { total: -100, why: `taken ${shotYear}` };
	if (anyYear && anyYear < OLDEST_YEAR) return { total: -100, why: `mentions ${anyYear}` };
	if (/\b(19|20)\d0s\b/.test(subject)) return { total: -100, why: "a decade, not a date" };

	// Every group, or it is not a picture of what the frame needs.
	const missing = slot.must.findIndex((group) => !group.some((word) => subject.includes(word)));
	if (missing === 0) return { total: -100, why: "not on subject" };
	if (missing > 0) return { total: -100, why: `no ${slot.must[missing][0]} in it` };

	const ratio = candidate.height ? candidate.width / candidate.height : 0;

	if (candidate.width < 1400) return { total: -100, why: `only ${candidate.width}px wide` };
	if (ratio < 1.2) return { total: -100, why: "portrait or square" };
	if (ratio > 2.6) return { total: -100, why: "panorama, will crop badly" };

	let total = 5 + sourceBonus;
	const why = [];

	const hits = slot.good.filter((word) => subject.includes(word)).length;
	const tagHits = slot.good.filter((word) => !subject.includes(word) && words(candidate.tags).includes(word)).length;
	total += Math.min(hits, 4) * 1.5 + Math.min(tagHits, 4) * 0.5;
	if (hits || tagHits) why.push(`${hits + tagHits} subject words`);

	const misses = slot.avoid.filter((word) => text.includes(word)).length;
	total -= misses * 3;
	if (misses) why.push(`${misses} off-subject words`);

	if (shotYear >= 2015) {
		total += 3;
		why.push(`taken ${shotYear}`);
	} else if (shotYear >= OLDEST_YEAR) {
		total += 1;
	} else if (!shotYear) {
		why.push("no date");
	}

	if (ratio >= 1.4 && ratio <= 2.1) total += 2;
	if (candidate.width >= 2000) total += 1;

	// A description of any length means someone said what the picture shows.
	if (String(candidate.description).length > 30) total += 1;

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

/* ------------------------------------------------------------------- review */

const esc = (v) =>
	String(v || "").replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");

/**
 * A contact sheet you open in a browser: the shortlist for every frame, side by side, big
 * enough to judge. Tick one per frame, save the picks file, run --apply.
 */
function reviewPage(shortlists) {
	const cards = (slot, list) => list.map((c, i) => `
			<label class="card">
				<input type="radio" name="${esc(slot)}" value="${i}">
				<img src="${esc(c.thumb || c.smaller || c.url)}" alt="" loading="lazy">
				<span class="meta">
					<b>${esc(c.score)}</b> · ${esc(c.source)} · ${esc(c.width)}×${esc(c.height)}<br>
					${esc(c.title.slice(0, 90)) || "<span class=dim>untitled</span>"}<br>
					<span class="dim">${esc(c.credit)}</span><br>
					<a href="${esc(c.page)}" target="_blank" rel="noopener">source page</a>
				</span>
			</label>`).join("");

	const sections = shortlists.map(({ slot, shows, list }) => `
		<section>
			<h2>${esc(slot)}</h2>
			<p class="shows">${esc(shows)}</p>
			${list.length ? `<div class="grid">${cards(slot, list)}
			<label class="card none"><input type="radio" name="${esc(slot)}" value="-1" checked><span class="meta">Leave this frame empty — the theme draws its illustration instead.</span></label>
			</div>` : `<p class="empty">Nothing passed the filters. The theme will draw its illustration here.</p>`}
		</section>`).join("");

	// A title containing </script> would otherwise end the block early.
	const data = JSON.stringify(shortlists.map(({ slot, list }) => ({ slot, list }))).replace(/</g, "\\u003c");

	return `<!doctype html>
<html lang="en">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Annam Leaf — choose the default photographs</title>
<style>
	:root { color-scheme: light dark; --line: #d7d2c7; --ink: #1d2016; --dim: #6b6a5e; --bg: #faf8f3; --card: #fff; }
	@media (prefers-color-scheme: dark) { :root { --line:#3a3a33; --ink:#ece9e1; --dim:#a09d92; --bg:#16170f; --card:#20211a; } }
	body { margin: 0; background: var(--bg); color: var(--ink); font: 15px/1.5 system-ui, -apple-system, "Segoe UI", sans-serif; }
	header, section, footer { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
	header { padding-top: 32px; }
	h1 { font-size: 24px; margin: 0 0 6px; }
	h2 { font-size: 18px; margin: 32px 0 2px; }
	.shows, .lede { color: var(--dim); margin: 0 0 14px; }
	.grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px; }
	.card { display: block; background: var(--card); border: 2px solid var(--line); border-radius: 10px; overflow: hidden; cursor: pointer; }
	.card:has(:checked) { border-color: #6f8f3f; box-shadow: 0 0 0 3px rgba(111,143,63,.25); }
	.card img { display: block; width: 100%; height: 190px; object-fit: cover; background: var(--line); }
	.card input { position: absolute; opacity: 0; }
	.meta { display: block; padding: 10px 12px; font-size: 12.5px; }
	.dim { color: var(--dim); }
	.none .meta { padding: 18px 12px; }
	.empty { color: var(--dim); font-style: italic; }
	footer { position: sticky; bottom: 0; background: var(--bg); border-top: 1px solid var(--line); padding: 14px 20px; margin-top: 40px; }
	button { font: inherit; padding: 10px 18px; border-radius: 8px; border: 1px solid var(--line); background: #6f8f3f; color: #fff; cursor: pointer; }
	code { background: rgba(128,128,128,.15); padding: 1px 5px; border-radius: 4px; }
</style>
<header>
	<h1>Choose the default photographs</h1>
	<p class="lede">Nothing is downloaded yet. Look at each frame, tick the picture that belongs there, then press <b>Save picks</b> and run <code>node tools/fetch-photos.mjs --apply</code>. Anything you leave untouched stays empty and the theme draws its own illustration — an empty frame beats a wrong photograph.</p>
</header>
${sections}
<footer>
	<button id="save">Save picks</button>
	<span id="note" class="dim"> </span>
</footer>
<script>
const DATA = ${data};
document.getElementById("save").addEventListener("click", () => {
	const picks = {};
	for (const { slot, list } of DATA) {
		const checked = document.querySelector('input[name="' + slot + '"]:checked');
		const index = checked ? Number(checked.value) : -1;
		if (index >= 0 && list[index]) picks[slot] = list[index];
	}
	const blob = new Blob([JSON.stringify(picks, null, "\\t")], { type: "application/json" });
	const a = document.createElement("a");
	a.href = URL.createObjectURL(blob);
	a.download = "photo-picks.json";
	a.click();
	document.getElementById("note").textContent =
		Object.keys(picks).length + " picked — save the file into the tools folder, then run: node tools/fetch-photos.mjs --apply";
});
</script>
</html>
`;
}

async function findPicksFile() {
	const tries = [
		PICKS_ARG && path.resolve(PICKS_ARG),
		PICKS,
		path.join(os.homedir(), "Downloads", "photo-picks.json"),
		path.join(os.homedir(), "Desktop", "photo-picks.json"),
	].filter(Boolean);

	for (const file of tries) {
		if (await exists(file)) return file;
	}

	return "";
}

/**
 * Download one candidate into the theme. Returns the credit row, or throws.
 */
async function save(slot, pick) {
	let bytes = await download(pick.url);

	if (bytes.length > MAX_BYTES && pick.smaller && pick.smaller !== pick.url) {
		bytes = await download(pick.smaller);
	}

	if (bytes.length > MAX_BYTES * 2) {
		throw new Error(`too heavy (${(bytes.length / 1024).toFixed(0)} KB)`);
	}

	await writeFile(path.join(OUT, `${slot}.jpg`), bytes);

	return {
		bytes: bytes.length,
		credit: {
			credit: pick.credit,
			source: pick.page,
			title: pick.title,
			query: pick.query || "",
			score: pick.score ?? null,
		},
	};
}

async function readCredits() {
	const file = path.join(OUT, "credits.json");

	if (!(await exists(file))) return {};

	try {
		return JSON.parse(await readFile(file, "utf8"));
	} catch {
		return {};
	}
}

/* --------------------------------------------------------------- the modes */

async function runApply() {
	const file = await findPicksFile();

	if (!file) {
		console.error("No photo-picks.json found. Run the search first, open tools/photo-review.html,");
		console.error("tick your choices, press Save picks, and put the file in the tools folder.");
		process.exitCode = 1;
		return;
	}

	console.log(`Picks: ${file}\n`);

	const picks = JSON.parse(await readFile(file, "utf8"));
	const credits = await readCredits();
	let saved = 0;

	await mkdir(OUT, { recursive: true });

	for (const [slot, pick] of Object.entries(picks)) {
		if (ONLY && ONLY !== slot) continue;
		if (!SLOTS.some((s) => s.slot === slot)) {
			console.log(`${slot.padEnd(9)} unknown frame — skipped`);
			continue;
		}

		if (!FORCE && (await exists(path.join(OUT, `${slot}.jpg`)))) {
			console.log(`${slot.padEnd(9)} kept (already there; --force to replace)`);
			continue;
		}

		try {
			const result = await save(slot, pick);
			credits[slot] = result.credit;
			saved++;
			console.log(`${slot.padEnd(9)} saved ${(result.bytes / 1024).toFixed(0)} KB — ${pick.credit}`);
		} catch (error) {
			console.error(`${slot.padEnd(9)} ${error.message}`);
		}
	}

	if (saved) {
		await writeFile(path.join(OUT, "credits.json"), JSON.stringify(credits, null, "\t") + "\n");
	}

	console.log(`\n${saved} downloaded into wp-content/themes/annamleaf/assets/photos/.`);
}

async function runSearch() {
	const enabled = SOURCES.filter((s) => s.fn !== fromPexels || process.env.PEXELS_API_KEY)
		.filter((s) => s.fn !== fromUnsplash || process.env.UNSPLASH_ACCESS_KEY);

	console.log(`Sources: ${enabled.map((s) => s.name).join(", ")}`);

	if (!process.env.PEXELS_API_KEY && !process.env.UNSPLASH_ACCESS_KEY) {
		console.log("Set PEXELS_API_KEY or UNSPLASH_ACCESS_KEY for modern stock photography too.\n");
	}

	const shortlists = [];
	const credits = await readCredits();
	let saved = 0;

	await mkdir(OUT, { recursive: true });

	for (const slot of SLOTS) {
		if (ONLY && ONLY !== slot.slot) continue;

		console.log(`${slot.slot.padEnd(9)} searching…`);

		const ranked = (await candidatesFor(slot)).filter((c) => c.score >= MIN_SCORE);

		if (SHOW) {
			ranked.slice(0, SHOW).forEach((c, i) => {
				console.log(`    ${i + 1}. ${String(c.score).padStart(5)}  ${c.source.padEnd(9)} ${c.title.slice(0, 60)}  (${c.why})`);
			});
		}

		shortlists.push({ slot: slot.slot, shows: slot.shows, list: ranked.slice(0, SHORTLIST) });

		if (!AUTO) {
			console.log(`${slot.slot.padEnd(9)} ${ranked.length} candidates shortlisted`);
			continue;
		}

		const best = ranked[0];

		if (!best) {
			console.log(`${slot.slot.padEnd(9)} nothing good enough — leaving the illustration`);
			continue;
		}

		if (!FORCE && (await exists(path.join(OUT, `${slot.slot}.jpg`)))) {
			console.log(`${slot.slot.padEnd(9)} kept (already there)`);
			continue;
		}

		try {
			const result = await save(slot.slot, best);
			credits[slot.slot] = result.credit;
			saved++;
			console.log(`${slot.slot.padEnd(9)} saved ${(result.bytes / 1024).toFixed(0)} KB — ${best.credit}`);
		} catch (error) {
			console.error(`${slot.slot.padEnd(9)} ${error.message}`);
		}
	}

	if (AUTO && saved) {
		await writeFile(path.join(OUT, "credits.json"), JSON.stringify(credits, null, "\t") + "\n");
	}

	await writeFile(REVIEW, reviewPage(shortlists));

	const total = shortlists.reduce((n, s) => n + s.list.length, 0);
	const empty = shortlists.filter((s) => !s.list.length).map((s) => s.slot);

	console.log(`\n${total} candidates across ${shortlists.length} frames.`);
	if (empty.length) console.log(`No candidate at all for: ${empty.join(", ")}.`);

	console.log(`\nShortlist written to tools/photo-review.html`);
	console.log("  Windows: start tools\\photo-review.html");
	console.log("  macOS:   open tools/photo-review.html");
	console.log("Tick one picture per frame, press Save picks, drop photo-picks.json into tools/,");
	console.log("then: node tools/fetch-photos.mjs --apply");
}

async function main() {
	if (APPLY) return runApply();

	return runSearch();
}

// Importable for the scoring test; only runs when invoked directly.
if (import.meta.url === pathToFileURL(process.argv[1] || "").href) {
	main().catch((error) => {
		console.error(error.message);
		process.exitCode = 1;
	});
}

export { SLOTS, score, REJECT, reviewPage };
