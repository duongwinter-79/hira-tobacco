# Copilot instructions for Annam Leaf

## Build, test, and lint commands

This is a custom WordPress theme + plugin repository. There are no npm scripts; run the checked-in tools directly from the repository root.

```sh
sh tools/lint.sh
```

Runs PHP syntax checks for `wp-content/` and `tools/`, validates `wp-content/themes/annamleaf/theme.json`, and renders the main templates through `tools/render-check.php`. Treat notices and warnings from the render harness as failures.

```sh
php tools/render-check.php
```

Renders the theme templates without a WordPress install and writes reviewable HTML to `tools/output/`.

```sh
node tools/test-photo-scoring.mjs
```

Runs the photo-scoring regression cases for `tools/fetch-photos.mjs`. This is the only focused test file in the repo; add/edit cases in its `CASES` array when changing scoring behavior.

```sh
docker compose up -d
docker compose run --rm cli wp core install --url=http://localhost:8888 --title="Annam Leaf" --admin_user=admin --admin_password=password --admin_email=admin@example.com
docker compose run --rm cli wp plugin activate annamleaf-core
docker compose run --rm cli wp theme activate annamleaf
docker compose run --rm cli wp rewrite flush --hard
```

Starts a local WordPress at `http://localhost:8888` and activates the plugin before the theme. Use Docker Compose as the default local path; `.wp-env.json` exists, but `wp-env` depends on access to `api.wordpress.org`.

```sh
sh tools/package.sh
```

Runs lint first, then creates uploadable zips in `dist/`: `annamleaf-theme.zip` and `annamleaf-core.zip`.

For photo/reference tooling:

```sh
node tools/fetch-photos.mjs
node tools/fetch-photos.mjs --apply
node tools/reference-shots.mjs --list
node tools/reference-shots.mjs --only=mibica
```

`fetch-photos` shortlists stock candidates and writes `tools/photo-review.html`; do not use `--auto` unless intentionally accepting the top scorer. `reference-shots` requires Playwright + Chromium and writes competitor/reference material under `tools/reference/`, which must remain uncommitted.

## High-level architecture

The central boundary is **content in the plugin, presentation in the theme**:

- `wp-content/plugins/annamleaf-core/` owns the structured content model, seed data, admin screens, settings, temporary photo import, and theme-facing accessors.
- `wp-content/themes/annamleaf/` owns templates, CSS, visual frames, RFQ form presentation/handling, frontend JS, fallback illustrations, SEO tags, and block patterns.

The theme is designed to degrade if the plugin is inactive. Calls into plugin functionality must be guarded with `function_exists()` and routed through wrappers in `inc/template-tags.php` such as `annamleaf_get()`, `annamleaf_get_meta()`, `annamleaf_get_field()`, and `annamleaf_ph()`.

The plugin registers three custom content types:

- `annam_stage`: seven ordered process stages, public single URLs, no archive.
- `annam_leaf`: ordered leaf/product records with specs and excerpts, public single URLs, no archive.
- `annam_region`: internal growing-region records, shown in wp-admin but not public.

Six real Pages are seeded on first plugin activation. Page templates then combine editable page content with plugin records:

- `/` uses `front-page.php` and pulls page hero fields, Company profile stats, stages, leaves, and regions.
- `/process/` uses `page-templates/process.php` and renders the page intro plus ordered `annam_stage` records.
- `/our-leaf/` uses `page-templates/leaf.php` and renders `annam_leaf` cards, a spec table, then the page body.
- `/contact/` uses `page-templates/contact.php` and combines page content, Company profile contact fields, and the RFQ form.

Seed content lives in `includes/seed.php`. First activation runs it once; Company profile's "Rebuild default content" can force it again. Rebuilding restores default pages/records/menu content but should not be treated as a migration mechanism for user-supplied company profile data or uploaded photos.

Editable field declarations are centralized:

- Post/page meta fields are declared in `annamleaf_field_groups()` in `includes/meta.php`; metabox rendering, sanitization, saving, and registration derive from that declaration.
- Company profile option fields are declared in `annamleaf_option_fields()` in `includes/settings.php`; sanitization and rendering derive from that declaration.
- Theme templates should read via `includes/api.php` accessors instead of direct `get_option()`/`get_post_meta()` calls.

Every image frame is an `annamleaf_plate()` in `inc/plates.php`. The fill order is featured image, bundled `assets/photos/<slot>.jpg`, then SVG motif placeholder with shot-note caption. Slot names are real integration points (`home`, `region`, `stage-1` through `stage-7`, `leaf-1` through `leaf-4`) used by templates and photo tooling.

The RFQ form posts to `admin-post.php`, validates a nonce and required fields, uses a honeypot, sends with `wp_mail()`, and does not store enquiries in the database. Delivery email comes from Company profile via `annamleaf_rfq_recipient()`.

The theme emits basic meta description, Open Graph/Twitter tags, and front-page Organization schema unless a known SEO plugin is active (`Yoast`, `Rank Math`, `SEOPress`, or All in One SEO). Placeholder markers are stripped from descriptions/schema.

## Key conventions

- Keep the tobacco site positioned as a B2B industrial-buyer capability profile. Avoid consumer-facing tobacco promotion, smoking imagery, retail pricing, or language that reads like cigarette advertising.
- Preserve the plugin/theme separation. New reusable business/content fields belong in the plugin; templates and presentational helpers belong in the theme.
- Keep theme calls to plugin-owned functions guarded or wrapped so disabling the plugin does not fatal the theme.
- Use the `annamleaf_` function prefix, `ANNAMLEAF_` constants, text domains `annamleaf` for the theme and `annamleaf-core` for the plugin, and `@package AnnamLeaf` in PHP file headers.
- Follow the existing WordPress escaping pattern: escape at output, allow `the_content`/`wp_kses_post()` only where filtered page or block HTML is intended, and sanitize by declared field type.
- Ordering for stages, leaves, and regions is by `menu_order` then title. Do not replace this with date/order-by-ID behavior.
- Placeholder markers are intentional during build. Empty client-supplied fields show bracketed placeholders while "Mark empty fields" is enabled; do not remove this behavior from templates unless replacing it consistently.
- Featured images always override bundled placeholder photos. Bundled stock photos must live in `wp-content/themes/annamleaf/assets/photos/` with credits in `credits.json`; product/leaf slots should stay illustrative until the client supplies real product photos.
- Temporary/reference photo workflows are intentionally conservative: `tools/fetch-photos.mjs` shortlists for human review, and `tools/reference-shots.mjs` is for shot briefs only. Never move reference-site images into the theme or commit `tools/reference/`.
- Frontend JavaScript is intentionally limited to the mobile menu and optional 18+ trade gate. Avoid adding JS-dependent core content rendering.
- The design system is a fixed light B2B brochure identity: dark field green, leaf green, cured gold, paper background, Lora headings, Be Vietnam Pro body text, IBM Plex Mono labels/figures.
- Polylang is the expected content translation layer. The theme only provides translated interface strings and a language switcher when Polylang is active.
