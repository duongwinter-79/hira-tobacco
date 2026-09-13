/**
 * Draw the world once, as an SVG silhouette for the export markets map.
 *
 *     node tools/build-markets-map.mjs
 *
 * Writes wp-content/themes/annamleaf/assets/markets-map.svg. Run it when the map itself needs
 * to change, which is close to never — the markets are editable in Company profile and are
 * drawn over this by PHP at render time, so adding a country does not mean rebuilding.
 *
 * Source is Natural Earth 1:110m admin-0 countries, which is public domain: no permission and
 * no attribution required. That matters on a site that no longer prints credits over pictures.
 *
 * The map is decoration. Every market it marks is also written out as text beside it, because
 * a silhouette is unreadable to a screen reader and not much better on a phone.
 *
 * PROJECTION — equirectangular, and it must match annamleaf_markets_point() in the theme:
 *
 *     x = lon + 180        lon -180..180  ->  x 0..360
 *     y = 84 - lat         lat   84..-56  ->  y 0..140
 *
 * Antarctica and most of the Arctic are cropped. They carry no markets and cost a third of the
 * height, and equirectangular smears them into nonsense anyway.
 */

import { writeFile, readFile, mkdir } from "node:fs/promises";
import path from "node:path";
import { fileURLToPath, pathToFileURL } from "node:url";

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const OUT = path.join(ROOT, "wp-content/themes/annamleaf/assets/markets-map.svg");
const CACHE = path.join(ROOT, "tools/.cache/ne_110m_countries.geojson");

const SOURCE =
	"https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/geojson/ne_110m_admin_0_countries.geojson";

// Viewport, in the projected units above.
const WIDTH = 360;
const HEIGHT = 140;
const LAT_TOP = 84;

// Coordinates snap to this grid before duplicates are dropped. Coastline detail is wasted
// here — the map renders about 900px wide and the question it answers is "which countries",
// not which inlet.
//
// 0.5 rather than a coarser value on purpose. A coarser grid rounds more points together but
// lands them on values like 2.1 and 2.8, which cost three characters each; 0.5 lands on 2 and
// 2.5. Tried at 0.7 and the file came out 6 KB BIGGER than at 0.5.
const GRID = 0.5;

// Rings smaller than this bounding box are dropped. Keeps a few thousand islands out of the
// file without losing anything a reader would look for.
const MIN_SPAN = 0.9;

const project = (lon, lat) => [lon + 180, LAT_TOP - lat];

/**
 * Natural Earth as GeoJSON, downloaded once and kept.
 */
async function geojson() {
	try {
		return JSON.parse(await readFile(CACHE, "utf8"));
	} catch {
		// Not cached yet.
	}

	process.stdout.write(`fetching ${SOURCE}\n`);

	const response = await fetch(SOURCE);

	if (!response.ok) throw new Error(`source returned ${response.status}`);

	const text = await response.text();

	await mkdir(path.dirname(CACHE), { recursive: true });
	await writeFile(CACHE, text);

	return JSON.parse(text);
}

/**
 * One ring of coordinates as an SVG path fragment, or "" if it is too small to bother with.
 */
function ring(coords) {
	const points = [];
	let minX = Infinity;
	let maxX = -Infinity;
	let minY = Infinity;
	let maxY = -Infinity;

	for (const [lon, lat] of coords) {
		if (lat > LAT_TOP || lat < LAT_TOP - HEIGHT) continue;

		const [px, py] = project(lon, lat);
		const x = Math.round(px / GRID) * GRID;
		const y = Math.round(py / GRID) * GRID;
		const last = points[points.length - 1];

		if (last && last[0] === x && last[1] === y) continue;

		points.push([x, y]);
		minX = Math.min(minX, x);
		maxX = Math.max(maxX, x);
		minY = Math.min(minY, y);
		maxY = Math.max(maxY, y);
	}

	if (points.length < 4) return "";
	if (maxX - minX < MIN_SPAN && maxY - minY < MIN_SPAN) return "";

	const n = (v) => (Math.round(v * 10) / 10).toString();

	return `M${points.map(([x, y]) => `${n(x)} ${n(y)}`).join("L")}Z`;
}

function rings(geometry) {
	if (!geometry) return [];

	if ("Polygon" === geometry.type) return geometry.coordinates;
	if ("MultiPolygon" === geometry.type) return geometry.coordinates.flat();

	return [];
}

async function main() {
	const data = await geojson();
	const parts = [];
	let dropped = 0;

	for (const feature of data.features || []) {
		const name = feature.properties?.ADMIN || feature.properties?.NAME || "";

		// Antarctica is entirely below the crop; skipping it by name avoids the work.
		if ("Antarctica" === name) continue;

		for (const coords of rings(feature.geometry)) {
			const d = ring(coords);

			if (d) parts.push(d);
			else dropped++;
		}
	}

	const svg = [
		`<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${WIDTH} ${HEIGHT}"`,
		` role="presentation" focusable="false" class="markets-map__world">`,
		`<path class="markets-map__land" d="${parts.join("")}"/>`,
		`</svg>`,
	].join("");

	await writeFile(OUT, svg + "\n");

	console.log(`${parts.length} rings kept, ${dropped} too small to draw`);
	console.log(`${(svg.length / 1024).toFixed(0)} KB written to ${path.relative(ROOT, OUT)}`);
}

if (import.meta.url === pathToFileURL(process.argv[1] || "").href) {
	main().catch((error) => {
		console.error(error.stack || error.message);
		process.exitCode = 1;
	});
}
