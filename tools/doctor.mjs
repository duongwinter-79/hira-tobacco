/**
 * Kiểm tra bản chạy trên máy, và sửa những gì sửa được.
 *
 * Mỗi lần site hỏng, các lệnh chẩn đoán lại giống hệt nhau: container còn sống không, theme
 * đã bật chưa, .htaccess có luật rewrite không, WordPress đang tưởng mình ở địa chỉ nào. File
 * này gom hết lại, và với những lỗi cấu hình thì tự chạy luôn lệnh sửa.
 *
 *     node tools/doctor.mjs                              kiểm tra, không sửa gì
 *     node tools/doctor.mjs --fix                        sửa những gì sửa được
 *     node tools/doctor.mjs --url=https://abc.trycloudflare.com   kiểm tra thêm qua tunnel
 *
 * Nó không đụng tới database ngoài việc bật theme/plugin và đặt permalink — những thứ đằng
 * nào cũng phải đúng thì site mới chạy.
 */

import { execFile } from "node:child_process";
import { Resolver, promises as dnsPromises } from "node:dns";
import path from "node:path";
import { fileURLToPath, pathToFileURL } from "node:url";

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const ARGS = process.argv.slice(2);
const FIX = ARGS.includes("--fix");
const URL_ARG = (ARGS.find((a) => a.startsWith("--url=")) || "").split("=").slice(1).join("=");
const LOCAL = "http://localhost:8888";

const ok = (s) => `  \x1b[32m✓\x1b[0m ${s}`;
const bad = (s) => `  \x1b[31m✗\x1b[0m ${s}`;
const warn = (s) => `  \x1b[33m!\x1b[0m ${s}`;
const dim = (s) => `\x1b[2m${s}\x1b[0m`;

/**
 * Chạy một lệnh, không bao giờ ném lỗi — trả về mã thoát và output để tự đọc.
 */
function run(file, args, env = {}) {
	return new Promise((resolve) => {
		execFile(
			file,
			args,
			{ cwd: ROOT, env: { ...process.env, ...env }, timeout: 120000, windowsHide: true },
			(error, stdout, stderr) => {
				resolve({
					code: error?.code ?? 0,
					out: String(stdout || "").trim(),
					err: String(stderr || "").trim(),
					failed: Boolean(error),
				});
			}
		);
	});
}

const compose = (args, env) => run("docker", ["compose", ...args], env);
const wp = (args) => compose(["run", "--rm", "cli", "wp", ...args]);

async function head(url) {
	try {
		const response = await fetch(url, { redirect: "manual", signal: AbortSignal.timeout(15000) });
		return { status: response.status, location: response.headers.get("location") || "" };
	} catch (error) {
		return { status: 0, location: "", error: error.message };
	}
}

async function body(url) {
	try {
		const response = await fetch(url, { signal: AbortSignal.timeout(20000) });
		return { status: response.status, text: await response.text() };
	} catch (error) {
		return { status: 0, text: "", error: error.message };
	}
}

/**
 * Phân giải tên miền bằng DNS của máy và bằng 1.1.1.1, để tách lỗi máy khỏi lỗi tunnel.
 */
async function resolveBoth(host) {
	const viaSystem = dnsPromises.lookup(host).then(
		(r) => ({ ok: true, addresses: [r.address] }),
		(e) => ({ ok: false, error: e.code || e.message, addresses: [] })
	);

	const resolver = new Resolver();
	resolver.setServers(["1.1.1.1"]);

	const viaCloudflare = new Promise((resolve) => {
		resolver.resolve4(host, (error, addresses) => {
			resolve(error ? { ok: false, error: error.code || error.message, addresses: [] } : { ok: true, addresses });
		});
	});

	const [system, cloudflare] = await Promise.all([viaSystem, viaCloudflare]);

	return { system, cloudflare };
}

/* ------------------------------------------------------------------- kiểm tra */

const problems = [];

/**
 * Ghi nhận một lỗi, kèm lệnh sửa nếu có.
 */
function fail(message, fix) {
	problems.push({ message, fix });
	console.log(bad(message));

	if (fix) console.log(dim(`      sửa: ${fix.label}`));
}

async function main() {
	console.log(`\nKiểm tra bản chạy ở ${ROOT}\n`);

	// ---------------------------------------------------------------- Docker
	console.log("Docker");

	const daemon = await run("docker", ["version", "--format", "{{.Server.Version}}"]);

	if (daemon.failed) {
		fail("Docker chưa chạy — mở Docker Desktop rồi chạy lại.");
		return report();
	}

	console.log(ok(`daemon ${daemon.out}`));

	const ps = await compose(["ps", "--format", "{{.Service}} {{.State}}"]);
	const states = Object.fromEntries(
		ps.out.split("\n").filter(Boolean).map((l) => l.trim().split(/\s+/))
	);

	for (const service of ["db", "wordpress"]) {
		if ("running" === states[service]) {
			console.log(ok(`${service} đang chạy`));
		} else {
			fail(`${service} không chạy (${states[service] || "không thấy"})`, {
				label: "docker compose up -d",
				cmd: ["compose", ["up", "-d"]],
			});
		}
	}

	if (problems.length) return report();

	// ------------------------------------------------------------- WordPress
	console.log("\nWordPress");

	const home = await head(LOCAL);

	if (home.status >= 200 && home.status < 400) {
		console.log(ok(`${LOCAL} trả ${home.status}`));
	} else {
		fail(`${LOCAL} không trả lời (${home.error || home.status})`);
	}

	const theme = await wp(["theme", "list", "--status=active", "--field=name"]);

	if (theme.out.includes("annamleaf")) {
		console.log(ok("theme annamleaf đang bật"));
	} else {
		fail(`theme đang bật là "${theme.out || "không rõ"}", không phải annamleaf`, {
			label: "wp theme activate annamleaf",
			cmd: ["wp", ["theme", "activate", "annamleaf"]],
		});
	}

	const plugin = await wp(["plugin", "list", "--status=active", "--field=name"]);

	if (plugin.out.includes("annamleaf-core")) {
		console.log(ok("plugin annamleaf-core đang bật"));
	} else {
		fail("plugin annamleaf-core chưa bật — nội dung mẫu sẽ không có", {
			label: "wp plugin activate annamleaf-core",
			cmd: ["wp", ["plugin", "activate", "annamleaf-core"]],
		});
	}

	// -------------------------------------------------------------- Permalink
	console.log("\nĐường dẫn đẹp");

	const structure = await wp(["option", "get", "permalink_structure"]);

	if (structure.out.trim()) {
		console.log(ok(`cấu trúc ${structure.out.trim()}`));
	} else {
		fail("permalink đang để mặc định", {
			label: "wp rewrite structure '/%postname%/'",
			cmd: ["wp", ["rewrite", "structure", "/%postname%/"]],
		});
	}

	const htaccess = await compose(["exec", "-T", "wordpress", "cat", "/var/www/html/.htaccess"]);

	if (htaccess.out.includes("RewriteRule . /index.php")) {
		console.log(ok(".htaccess có luật rewrite"));
	} else {
		fail(
			htaccess.failed ? "không có file .htaccess" : ".htaccess rỗng giữa hai dấu mốc (WP-CLI ghi ra thế)",
			{
				label: "docker compose cp docker/htaccess wordpress:/var/www/html/.htaccess",
				cmd: ["compose", ["cp", "docker/htaccess", "wordpress:/var/www/html/.htaccess"]],
			}
		);
	}

	const allow = await compose([
		"exec", "-T", "wordpress", "grep", "-r", "AllowOverride All", "/etc/apache2/conf-enabled/",
	]);

	if (allow.out.includes("AllowOverride All")) {
		console.log(ok("Apache đọc .htaccess (AllowOverride All)"));
	} else {
		fail("Apache đang bỏ qua .htaccess — thiếu mount docker/apache-permalinks.conf", {
			label: "git pull, rồi docker compose down && docker compose up -d",
			cmd: ["compose", ["up", "-d", "--force-recreate"]],
		});
	}

	const about = await head(`${LOCAL}/about/`);

	if (200 === about.status) {
		console.log(ok("/about/ trả 200"));
	} else {
		fail(`/about/ trả ${about.status || about.error}${about.location ? ` → ${about.location}` : ""}`);
	}

	// ------------------------------------------------------------- địa chỉ site
	console.log("\nĐịa chỉ site");

	const mu = await compose(["exec", "-T", "wordpress", "ls", "/var/www/html/wp-content/mu-plugins"]);

	if (mu.out.includes("annamleaf-site-url.php")) {
		console.log(ok("mu-plugin địa chỉ site đã nạp"));
	} else {
		fail("thiếu mu-plugin annamleaf-site-url.php — chưa git pull, hoặc container cũ", {
			label: "git pull, rồi docker compose down && docker compose up -d",
			cmd: ["compose", ["up", "-d", "--force-recreate"]],
		});
	}

	const siteUrl = await compose(["exec", "-T", "wordpress", "printenv", "SITE_URL"]);
	const value = siteUrl.out.trim();

	if ("auto" === value) {
		console.log(ok("SITE_URL=auto — tunnel nào cũng chạy"));
	} else if (value) {
		fail(`SITE_URL đang ghim vào "${value}" — link tunnel cũ đã chết thì mọi request bị đẩy về đó (NXDOMAIN)`, {
			label: 'đặt SITE_URL=auto rồi dựng lại container',
			cmd: ["compose", ["up", "-d", "--force-recreate"], { SITE_URL: "auto" }],
		});
	} else if (URL_ARG) {
		fail("SITE_URL rỗng — xem qua tunnel sẽ vỡ CSS vì link vẫn trỏ localhost", {
			label: 'đặt SITE_URL=auto rồi dựng lại container',
			cmd: ["compose", ["up", "-d", "--force-recreate"], { SITE_URL: "auto" }],
		});
	} else {
		console.log(warn("SITE_URL rỗng — chạy localhost thì không sao, xem qua tunnel thì phải đặt auto"));
	}

	// ------------------------------------------------------------------ tunnel
	if (URL_ARG) {
		console.log(`\nQua tunnel ${URL_ARG}`);

		const host = new URL(URL_ARG).host.split(":")[0];
		const dns = await resolveBoth(host);

		if (dns.system.ok) {
			console.log(ok(`DNS phân giải ${host} → ${dns.system.addresses[0]}`));
		} else if (dns.cloudflare.ok) {
			// Máy phân giải được qua 1.1.1.1 nhưng không qua DNS mặc định: lỗi ở máy, không
			// phải ở tunnel. Đây là lúc trình duyệt báo NXDOMAIN dù tunnel vẫn sống.
			fail(
				`DNS của máy không phân giải được ${host} (${dns.system.error}), nhưng 1.1.1.1 thì được`,
				{
					label: "ipconfig /flushdns, rồi đổi DNS của card mạng sang 1.1.1.1 và 8.8.8.8",
					manual: true,
				}
			);
		} else {
			fail(`không phân giải được ${host} ở cả DNS máy lẫn 1.1.1.1 — tunnel đã tắt hoặc link sai`);
		}

		const page = dns.system.ok || dns.cloudflare.ok ? await body(URL_ARG) : { status: 0, error: "bỏ qua vì DNS hỏng" };

		if (200 !== page.status) {
			if (dns.system.ok) fail(`trả ${page.status || page.error} — tunnel còn chạy không?`);
		} else {
			console.log(ok(`trả 200`));

			const hrefs = [...page.text.matchAll(/<link[^>]+rel=["']stylesheet["'][^>]*href=["']([^"']+)["']/gi)]
				.map((m) => m[1]);
			const wanted = new URL(URL_ARG).host;
			const wrongHost = hrefs.filter((h) => /^https?:\/\//i.test(h) && new URL(h).host !== wanted);
			const insecure = hrefs.filter((h) => h.startsWith("http://"));

			if (!hrefs.length) {
				console.log(warn("không thấy thẻ stylesheet nào — trang có thể đang lỗi"));
			} else if (wrongHost.length) {
				fail(`CSS trỏ sang ${new URL(wrongHost[0]).host} thay vì ${wanted}`, {
					label: 'đặt SITE_URL=auto rồi dựng lại container',
					cmd: ["compose", ["up", "-d", "--force-recreate"], { SITE_URL: "auto" }],
				});
			} else if (insecure.length) {
				fail("CSS dùng http:// trong trang https — trình duyệt chặn mixed content, X-Forwarded-Proto không tới được PHP");
			} else {
				console.log(ok(`${hrefs.length} thẻ CSS đều trỏ đúng ${wanted}`));
			}
		}
	}

	return report();
}

/* --------------------------------------------------------------------- kết */

async function report() {
	console.log("");

	if (!problems.length) {
		console.log("\x1b[32mKhông có vấn đề nào.\x1b[0m\n");
		return;
	}

	const fixable = problems.filter((p) => p.fix && !p.fix.manual);
	const manual = problems.filter((p) => p.fix && p.fix.manual);

	for (const problem of manual) {
		console.log(`Phải tự chạy: ${problem.fix.label}\n`);
	}

	console.log(`\x1b[31m${problems.length} vấn đề\x1b[0m, ${fixable.length} sửa tự động được.\n`);

	if (!fixable.length) return void (process.exitCode = 1);

	if (!FIX) {
		console.log("Chạy lại với --fix để sửa:\n");
		console.log("  node tools/doctor.mjs --fix\n");
		process.exitCode = 1;
		return;
	}

	console.log("Đang sửa…\n");

	for (const problem of fixable) {
		const [kind, args, env] = problem.fix.cmd;
		console.log(`  → ${problem.fix.label}`);

		const result = "wp" === kind ? await wp(args) : await compose(args, env || {});

		if (result.failed) {
			console.log(bad(`    thất bại: ${(result.err || result.out).split("\n")[0]}`));
		} else {
			console.log(ok("    xong"));
		}
	}

	console.log("\nKiểm tra lại:\n");
	console.log("  node tools/doctor.mjs" + (URL_ARG ? ` --url=${URL_ARG}` : "") + "\n");
}

if (import.meta.url === pathToFileURL(process.argv[1] || "").href) {
	main().catch((error) => {
		console.error(error.stack || error.message);
		process.exitCode = 1;
	});
}

export { run, head, body };
