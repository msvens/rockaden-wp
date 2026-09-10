# rockaden-wp — Rockaden theme

WordPress block theme `rockaden-theme` for SK Rockaden chess club (Stockholm). This repository
holds only the theme, at its root. The chess plugin `rockaden-chess` lives in
[chess-wp-plugin](https://github.com/msvens/chess-wp-plugin) (sibling checkout `../chess-wp-plugin`).
Until v0.45.0 both were released from here.

**Where things belong:** anything chess (training, tournaments, calendar, SSF, and their blocks)
goes in the plugin; anything about the site (design, templates, shop, feedback, sidebars, reactions,
news presentation, settings page) goes here. The plugin never depends on the theme. The theme places
three plugin blocks by name (`rockaden/tournament`, `rockaden/training-group`,
`rockaden/upcoming-events`), provides `single-rc_tournament.html` / `single-rc_training_group.html`,
and registers its docs through the plugin's `rc_register_docs` action, guarded by `class_exists`.

## Architecture

- Block theme (FSE), `theme.json` v3, `functions.php` for setup + block registration, classes in
  `inc/class-theme-*.php` (`Rockaden_Theme_*`, all static, required from `functions.php`)
- **Theme blocks** (`blocks/*/block.json`, server-rendered, no build): section-nav, sidebar-panel,
  page-title, shop-grid, feedback-form, footer-nav, sidebar, sidebar-card, latest-news, reactions.
  Editor scripts are hand-written `index.js` + `index.asset.php`; view scripts are vanilla ES5 IIFEs
  reading `data-*` attributes (strings translated in PHP).
- **Site features in `inc/`**: settings page (Appearance → Rockaden, `rockaden_theme_options`,
  hand-rolled admin-post form — every new key must be assigned in `handle_save()` or it is lost),
  shop CPT `rc_shop_item` + REST, feedback CPT `rc_feedback` + public REST with nonce, reactions
  (post meta `rc_reactions` + public REST), cookie-driven SV/EN locale (`rc_locale`), section nav,
  comments off, content-aware excerpts, setup/seeding on activation
- Colors: `primary` #2563eb + gray palette; dark mode = `html.dark` overriding
  `--wp--preset--color--*` in `assets/css/custom.css` (also loaded as editor style); typography Geist
- Templates: index, home, single, page, archive, author, page-section, page-landing, the two CPT
  singles (declared in theme.json `customTemplates`); parts header/footer; patterns in `patterns/`
- Docs: `docs/*.html` (sv/en) rendered by the plugin's documentation block

## Development

```bash
composer install
npx wp-env start          # http://localhost:8888 (admin/password); mounts ../chess-wp-plugin as rockaden-chess
# no build step; PHP/CSS/JS served as-is (custom.css is versioned by theme version → hard reload)
```

## Quality

```bash
pnpm run check            # PHPStan level 6 + phpcs (WordPress standard, text domain rockaden-theme) + i18n:check
pnpm i18n                 # regenerate catalogues; edit only languages/rockaden-theme-en_US.po; READ EVERY FUZZY (msgmerge guesses wrong)
pnpm package              # dist/rockaden-theme.zip with root folder rockaden-theme (the install slug)
```

- Never add phpstan-ignore / phpcs:ignore / eslint-disable silently — discuss first
- Theme JS is deliberately no-build and unlinted (ES5 style)
- `pnpm package` runs `composer install --no-dev`; re-run `composer install` afterwards

## Release

`/release` bumps `Version:` in `style.css`, regenerates catalogues, commits, tags `vX.Y.Z`, pushes;
`release.yml` builds the zip. Sites update through the plugin-update-checker reading this
repository's releases (asset `rockaden-theme.zip`, `REQUIRE_RELEASE_ASSETS`). **Never push a version
bump without its tag, never push tags by hand**: with no release the checker falls back to the branch
source zip, which has no `vendor/`. Live and test sites update manually from Dashboard → Updates.

## Conventions
- Meta keys / CPTs prefixed `rc_`, options `rockaden_`, REST namespace `rockaden/v1`, CSS classes
  `rockaden-*` / `rc-*`, no global functions (static classes only)
- Prefer WordPress preset slugs and Gutenberg primitives over bespoke tokens/CSS
- Git: never commit, push or open PRs without an explicit request; one PR at a time; no Claude
  attribution or session links in commits or PR bodies
