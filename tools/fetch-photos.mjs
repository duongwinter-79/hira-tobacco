/**
 * Download the default photographs into the theme.
 *
 * The theme ships with its own pictures so a fresh install looks finished with no
 * clicking and no database rows. This fetches freely licensed ones from Wikimedia
 * Commons into wp-content/themes/annamleaf/assets/photos/ and writes credits.json
 * beside them; the theme reads both.
 *
 * Needs Node 18+ (for fetch) and nothing else:
 *
 *     node tools/fetch-photos.mjs           only the missing ones
 *     node tools/fetch-photos.mjs --force   replace what is already there
 *
 * These are stand-ins for the shoot in docs/shot-list.md. Delete the files when the
 * client's own photography arrives — the theme falls back to its illustrations.
 */

import { mkdir, writeFile, access, readFile } from "node:fs/promises";
import { constants } from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const OUT = path.join(ROOT, "wp-content/themes/annamleaf/assets/photos");
const FORCE = process.argv.includes("--force");

/**
 * One entry per image frame in the theme. Keep in step with
 * annamleaf_demo_photo_slots() in the plugin, which searches the same terms at run time.
 */
const SLOTS = [
	{ slot: "home", queries: ["Cao Bang Vietnam landscape", "Cao Bang province", "Vietnam mountains terraces"] },
	{ slot: "stage-1", queries: ["tobacco seedbed", "tobacco seedlings tray", "vegetable seedlings greenhouse"] },
	{ slot: "stage-2", queries: ["tobacco field crop", "Nicotiana tabacum field", "tobacco growing field"] },
	{ slot: "stage-3", queries: ["tobacco leaves harvesting", "tobacco leaf picking", "harvesting tobacco crop"] },
	{ slot: "stage-4", queries: ["tobacco curing barn", "tobacco drying barn", "tobacco leaves hanging drying"] },
	{ slot: "stage-5", queries: ["dried tobacco leaves bundle", "tobacco leaves sorting", "tobacco leaf grading"] },
	{ slot: "stage-6", queries: ["tobacco bales warehouse", "tobacco leaves baled", "tobacco processing machine"] },
	{ slot: "stage-7", queries: ["shipping container terminal", "container port loading", "cargo container ship"] },
];

const API = "https://commons.wikimedia.org/w/api.php";

/**
 * A modern supplier's site cannot show museum prints or colonial-era plantation archives,
 * so anything that looks like artwork, an archive scan or a pre-1990 photograph is out.
 * The first run without this filter returned an engraving of enslaved people and a 1915
 * colonial plantation, which is exactly what these terms exclude.
 */
const REJECT = [
	"engraving", "lithograph", "etching", "woodcut", "drawing", "painting", "sketch",
	"illustration", "print", "poster", "advertisement", "postcard", "map", "diagram",
	"logo", "coat of arms", "stamp", "banknote", "label", "cigarette card",
	"kitlv", "lccn", "wellcome", "tropenmuseum", "museum", "archive", "collectie",
	"slave", "slavery", "colonial", "plantation of the", "maatschappij",
];

const OLDEST_YEAR = 1990;

/**
 * Is this file usable as a modern photograph?
 */
function acceptable(title, credit, date) {
	const haystack = `${title} ${credit}`.toLowerCase();

	if (REJECT.some((term) => haystack.includes(term))) return false;

	// "circa 1915", "- 1937 -": a year in the title means an archive scan.
	const inTitle = title.match(/\b(1[6-9]\d{2}|20[0-2]\d)\b/);
	if (inTitle && Number(inTitle[1]) < OLDEST_YEAR) return false;

	const shot = String(date || "").match(/\b(1[6-9]\d{2}|20\d{2})\b/);
	if (shot && Number(shot[1]) < OLDEST_YEAR) return false;

	return true;
}
const UA = "AnnamLeafPhotoFetch/1.0 (site build script)";

/**
 * Ask Commons for candidates, newest search first.
 */
async function search(query) {
	const url =
		`${API}?action=query&format=json&generator=search` +
		`&gsrsearch=${encodeURIComponent(query + " filetype:bitmap")}` +
		`&gsrnamespace=6&gsrlimit=8&prop=imageinfo&iiprop=url|size|extmetadata&iiurlwidth=1600`;

	const response = await fetch(url, { headers: { "User-Agent": UA } });

	if (!response.ok) {
		throw new Error(`Commons search failed (${response.status})`);
	}

	const data = await response.json();
	const pages = data?.query?.pages ? Object.values(data.query.pages) : [];
	const found = [];

	for (const page of pages) {
		const info = page.imageinfo?.[0];

		if (!info?.thumburl) continue;

		// Landscape and big enough for a hero; skips diagrams and portrait scans.
		if (info.width < 1200 || !info.height || info.width / info.height < 1.2) continue;

		const meta = info.extmetadata || {};
		const strip = (v) => String(v || "").replace(/<[^>]*>/g, "").trim();
		const artist = strip(meta.Artist?.value);
		const licence = strip(meta.LicenseShortName?.value) || "Wikimedia Commons";
		const taken = strip(meta.DateTimeOriginal?.value);

		if (!acceptable(page.title || "", artist, taken)) continue;

		found.push({
			url: info.thumburl,
			credit: `${artist ? artist + " · " : ""}${licence} · Wikimedia Commons`,
			source: `https://commons.wikimedia.org/wiki/${encodeURIComponent(page.title || "")}`,
			title: page.title || query,
			query,
		});
	}

	return found;
}

async function download(url) {
	const response = await fetch(url, { headers: { "User-Agent": UA } });

	if (!response.ok) {
		throw new Error(`download failed (${response.status})`);
	}

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

	let saved = 0;
	let kept = 0;
	let failed = 0;

	for (const { slot, queries } of SLOTS) {
		const target = path.join(OUT, `${slot}.jpg`);

		if (!FORCE && (await exists(target))) {
			console.log(`  ${slot.padEnd(9)} kept (already downloaded)`);
			kept++;
			continue;
		}

		let photo = null;

		for (const query of queries) {
			try {
				const candidates = await search(query);

				if (candidates.length) {
					photo = candidates[0];
					break;
				}
			} catch (error) {
				console.error(`  ${slot.padEnd(9)} search "${query}" failed: ${error.message}`);
			}
		}

		if (!photo) {
			console.error(`  ${slot.padEnd(9)} no usable picture found`);
			failed++;
			continue;
		}

		try {
			let bytes = await download(photo.url);

			// A hero image over ~700 KB is too heavy; ask Commons for a smaller rendering.
			for (const width of [1200, 1000]) {
				if (bytes.length <= 700 * 1024) break;

				const smaller = photo.url.replace(/\/\d+px-/, `/${width}px-`);

				if (smaller === photo.url) break;

				bytes = await download(smaller);
			}

			await writeFile(target, bytes);

			credits[slot] = {
				credit: photo.credit,
				source: photo.source,
				title: photo.title,
				query: photo.query,
			};

			console.log(`  ${slot.padEnd(9)} saved  ${(bytes.length / 1024).toFixed(0)} KB  ${photo.credit}`);
			saved++;
		} catch (error) {
			console.error(`  ${slot.padEnd(9)} ${error.message}`);
			failed++;
		}
	}

	if (Object.keys(credits).length) {
		await writeFile(creditsFile, JSON.stringify(credits, null, "\t") + "\n");
	}

	console.log(`\n${saved} downloaded, ${kept} kept, ${failed} failed.`);
	console.log(`Files: wp-content/themes/annamleaf/assets/photos/`);
	console.log("Commit them, and they ship with the theme until real photography replaces them.");
	console.log("Look at every file before committing — a search result is not a picture editor.");

	if (failed && !saved) {
		process.exitCode = 1;
	}
}

main().catch((error) => {
	console.error(error.message);
	process.exitCode = 1;
});
