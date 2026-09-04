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
	{ slot: "stage-1", queries: ["tobacco seedlings", "tobacco nursery", "seedling tray greenhouse"] },
	{ slot: "stage-2", queries: ["tobacco field", "tobacco plantation", "Nicotiana tabacum field"] },
	{ slot: "stage-3", queries: ["tobacco harvest", "tobacco harvesting", "tobacco leaves picking"] },
	{ slot: "stage-4", queries: ["tobacco curing barn", "tobacco drying barn", "tobacco leaves drying"] },
	{ slot: "stage-5", queries: ["tobacco leaves sorting", "tobacco grading", "dried tobacco leaves"] },
	{ slot: "stage-6", queries: ["tobacco factory", "tobacco processing plant", "tobacco warehouse"] },
	{ slot: "stage-7", queries: ["shipping container terminal", "container port loading", "cargo container ship"] },
];

const API = "https://commons.wikimedia.org/w/api.php";
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
			const response = await fetch(photo.url, { headers: { "User-Agent": UA } });

			if (!response.ok) {
				throw new Error(`download failed (${response.status})`);
			}

			const bytes = Buffer.from(await response.arrayBuffer());
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

	if (failed && !saved) {
		process.exitCode = 1;
	}
}

main().catch((error) => {
	console.error(error.message);
	process.exitCode = 1;
});
