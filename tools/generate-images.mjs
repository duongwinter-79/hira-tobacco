/**
 * Generate the theme's photographs with Google's Gemini image models.
 *
 * The prompts are not in this file. docs/ai-image-prompts.md is the source of truth —
 * one prompt per photo frame, with the aspect ratio beside it — and this script reads
 * them straight out of that document. Edit the prose, rerun, get the new picture.
 *
 * It writes candidates to tools/generated/ and stops there. Nothing reaches the theme
 * until you have looked at the results and run tools/finish-photos.php, which crops to
 * the sizes in the shot list and records in credits.json that the picture is synthetic.
 * Same shape as fetch-photos.mjs: the machine shortlists, a person picks.
 *
 *     node tools/generate-images.mjs --list             what the document defines
 *     node tools/generate-images.mjs --slot=home --n=3  three candidates for one frame
 *     node tools/generate-images.mjs --all              one candidate for every frame
 *     php tools/finish-photos.php home 2                crop the winner into the theme
 *
 * Flags:
 *     --slot=home        one frame; repeat the flag for several
 *     --all              every frame in the document
 *     --n=3              candidates per frame (default 1)
 *     --model=pro        pro (default) or flash — see MODELS below
 *     --size=2K          512, 1K, 2K or 4K (default 2K; home wants 4K)
 *     --aspect=3:2       override the ratio the document asks for
 *     --force            overwrite candidates already on disk
 *     --dry-run          resolve prompts and print the cost, call nothing
 *
 * Needs GEMINI_API_KEY in .env or the environment, on a project with billing enabled.
 * Google withdrew the free image tier, so an unbilled key fails with a zero quota.
 */

import { mkdir, writeFile, readFile, access } from "node:fs/promises";
import { constants } from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const PROMPTS = path.join(ROOT, "docs/ai-image-prompts.md");
const OUT = path.join(ROOT, "tools/generated");

/**
 * The two models worth pointing at this job, with the per-image price at each size so
 * --dry-run can tell you what a run costs before you spend it.
 */
const MODELS = {
	pro: {
		id: "gemini-3-pro-image-preview",
		label: "Nano Banana Pro",
		price: { "512": 0.134, "1K": 0.134, "2K": 0.134, "4K": 0.24 },
	},
	flash: {
		id: "gemini-3.1-flash-image",
		label: "Nano Banana 2",
		price: { "512": 0.045, "1K": 0.067, "2K": 0.101, "4K": 0.15 },
	},
};

const SIZES = ["512", "1K", "2K", "4K"];
const ENDPOINT = "https://generativelanguage.googleapis.com/v1beta/models";

const ARGS = process.argv.slice(2);
const flag = (name) => ARGS.includes(`--${name}`);
const value = (name, fallback = "") => {
	const hit = ARGS.find((a) => a.startsWith(`--${name}=`));
	return hit ? hit.slice(name.length + 3) : fallback;
};

const SLOTS = ARGS.filter((a) => a.startsWith("--slot=")).map((a) => a.slice(7));
const ALL = flag("all");
const LIST = flag("list");
const FORCE = flag("force");
const DRY = flag("dry-run");
const N = Math.max(1, Number(value("n", "1")) || 1);
const SIZE = value("size", "2K");
const ASPECT = value("aspect");
const MODEL = MODELS[value("model", "pro")];

/**
 * Read KEY=VALUE lines out of .env so the key never has to live in a shell profile.
 * Deliberately dumb: no interpolation, no export, no multi-line values.
 *
 * @returns {Promise<Record<string, string>>}
 */
async function readEnv() {
	const out = {};

	try {
		const text = await readFile(path.join(ROOT, ".env"), "utf8");

		for (const line of text.split("\n")) {
			const match = /^\s*([A-Z0-9_]+)\s*=\s*(.*)$/.exec(line);

			if (!match) continue;

			out[match[1]] = match[2].trim().replace(/^["']|["']$/g, "");
		}
	} catch {
		// No .env is fine — the key may be in the environment already.
	}

	return out;
}

/**
 * Pull the frames out of docs/ai-image-prompts.md.
 *
 * A frame is a level-two heading that either names a file (`## \`home.jpg\``) or names a
 * page whose picture is set through wp-admin (`## Trang About`), followed somewhere by an
 * `aspectRatio:` marker and a fenced text block holding the prompt. Headings without a
 * fenced block — the section dividers — are skipped.
 *
 * @returns {Promise<Array<{slot: string, title: string, aspect: string, prompt: string}>>}
 */
async function readPrompts() {
	const text = await readFile(PROMPTS, "utf8");
	const frames = [];

	for (const section of text.split(/^## /m).slice(1)) {
		const heading = section.split("\n", 1)[0].trim();
		const prompt = /```text\n([\s\S]*?)\n```/.exec(section);
		const aspect = /`aspectRatio:\s*([0-9]+:[0-9]+)`/.exec(section);

		if (!prompt || !aspect) continue;

		const named = /^`([a-z0-9-]+)\.jpg`/.exec(heading);
		const page = /^Trang\s+(.+)$/.exec(heading);
		let slot;

		if (named) {
			slot = named[1];
		} else if (page) {
			slot = "page-" + slugify(page[1]);
		} else {
			continue;
		}

		frames.push({
			slot,
			title: heading.replace(/`/g, ""),
			aspect: aspect[1],
			prompt: prompt[1].trim(),
		});
	}

	return frames;
}

/**
 * Reduce a page name to something usable as a file name.
 *
 * @param {string} text Heading text.
 * @returns {string}
 */
function slugify(text) {
	return text
		.normalize("NFD")
		.replace(/[̀-ͯ]/g, "")
		.replace(/đ/gi, "d")
		.toLowerCase()
		.replace(/[^a-z0-9]+/g, "-")
		.replace(/^-|-$/g, "");
}

/**
 * Ask Gemini for one picture.
 *
 * @param {string} key    API key.
 * @param {string} prompt The prompt text.
 * @param {string} aspect Aspect ratio, e.g. "16:9".
 * @returns {Promise<{bytes: Buffer, mime: string}>}
 */
async function generate(key, prompt, aspect) {
	const response = await fetch(`${ENDPOINT}/${MODEL.id}:generateContent`, {
		method: "POST",
		headers: { "content-type": "application/json", "x-goog-api-key": key },
		body: JSON.stringify({
			contents: [{ parts: [{ text: prompt }] }],
			generationConfig: {
				responseModalities: ["IMAGE"],
				imageConfig: { aspectRatio: aspect, imageSize: SIZE },
			},
		}),
	});

	const body = await response.json().catch(() => null);

	if (!response.ok) {
		const detail = body?.error?.message || `HTTP ${response.status}`;
		throw new Error(detail);
	}

	// A refusal comes back as a well-formed response with no picture in it. Tobacco is a
	// restricted subject at every provider, so say plainly which guard fired rather than
	// letting it read as an empty reply.
	const blocked = body?.promptFeedback?.blockReason;

	if (blocked) throw new Error(`prompt blocked (${blocked})`);

	const candidate = body?.candidates?.[0];
	const part = candidate?.content?.parts?.find((p) => p.inlineData?.data);

	if (!part) {
		const reason = candidate?.finishReason || "no image in response";
		throw new Error(`no image returned (${reason})`);
	}

	return {
		bytes: Buffer.from(part.inlineData.data, "base64"),
		mime: part.inlineData.mimeType || "image/png",
	};
}

/**
 * Whether a path already exists.
 *
 * @param {string} file Path to test.
 * @returns {Promise<boolean>}
 */
async function exists(file) {
	try {
		await access(file, constants.F_OK);
		return true;
	} catch {
		return false;
	}
}

async function main() {
	if (!MODEL) {
		console.error(`Unknown --model. Choose one of: ${Object.keys(MODELS).join(", ")}`);
		process.exit(1);
	}

	if (!SIZES.includes(SIZE)) {
		console.error(`Unknown --size. Choose one of: ${SIZES.join(", ")}`);
		process.exit(1);
	}

	const frames = await readPrompts();

	if (LIST) {
		console.log(`${frames.length} frames in docs/ai-image-prompts.md\n`);

		for (const frame of frames) {
			console.log(`  ${frame.slot.padEnd(14)} ${frame.aspect.padEnd(6)} ${frame.title}`);
		}

		return;
	}

	// Name the typo before complaining that nothing was selected — a misspelled --slot
	// otherwise reports as an empty selection, which sends you looking in the wrong place.
	const missing = SLOTS.filter((s) => !frames.some((f) => f.slot === s));

	if (missing.length) {
		console.error(`No such frame: ${missing.join(", ")}. Run --list for the names.`);
		process.exit(1);
	}

	const wanted = ALL ? frames : frames.filter((f) => SLOTS.includes(f.slot));

	if (!wanted.length) {
		console.error("Nothing selected. Pass --slot=NAME (repeatable) or --all, or --list to see the names.");
		process.exit(1);
	}

	const count = wanted.length * N;
	const cost = count * MODEL.price[SIZE];

	console.log(`${MODEL.label} (${MODEL.id}) at ${SIZE}`);
	console.log(`${wanted.length} frames × ${N} = ${count} images, about $${cost.toFixed(2)}\n`);

	if (DRY) {
		for (const frame of wanted) {
			console.log(`  ${frame.slot.padEnd(14)} ${(ASPECT || frame.aspect).padEnd(6)} ${frame.prompt.slice(0, 60)}…`);
		}

		return;
	}

	const env = await readEnv();
	const key = process.env.GEMINI_API_KEY || env.GEMINI_API_KEY;

	if (!key) {
		console.error("No GEMINI_API_KEY. Put it in .env — see .env.example.");
		process.exit(1);
	}

	await mkdir(OUT, { recursive: true });

	let made = 0;
	let failed = 0;

	for (const frame of wanted) {
		const aspect = ASPECT || frame.aspect;

		for (let n = 1; n <= N; n++) {
			const file = path.join(OUT, `${frame.slot}-${n}.png`);

			if (!FORCE && (await exists(file))) {
				console.log(`  ${frame.slot}-${n}  skipped, already there (--force to redo)`);
				continue;
			}

			try {
				const image = await generate(key, frame.prompt, aspect);
				const ext = image.mime.includes("jpeg") ? "jpg" : "png";
				const target = path.join(OUT, `${frame.slot}-${n}.${ext}`);

				await writeFile(target, image.bytes);
				made++;

				const kb = Math.round(image.bytes.length / 1024);
				console.log(`  ${frame.slot}-${n}  ${aspect} ${SIZE}  ${kb} KB`);
			} catch (error) {
				failed++;
				console.error(`  ${frame.slot}-${n}  FAILED: ${error.message}`);
			}
		}
	}

	console.log(`\n${made} written to tools/generated/${failed ? `, ${failed} failed` : ""}`);

	if (made) {
		console.log("Look at them, then: php tools/finish-photos.php SLOT N");
	}
}

main().catch((error) => {
	console.error(error);
	process.exit(1);
});
