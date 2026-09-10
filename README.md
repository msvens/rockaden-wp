# rockaden-wp — the Rockaden theme (`rockaden-theme`)

WordPress block theme for [SK Rockaden](https://rockaden.com) chess club (Stockholm): the club's
design, page templates, dark mode, the Swedish/English switch, the shop, the feedback form, sidebars,
reactions, and the templates for the chess plugin's post types.

The plugin lives in its own repository: [chess-wp-plugin](https://github.com/msvens/chess-wp-plugin)
(`rockaden-chess`). The theme places some of its blocks and provides templates for its post types,
and runs without it. Until v0.45.0 both packages were released from here; the old release pages keep
both zips.

## Requirements

- WordPress 6.5+
- PHP 8.1+

## Installation

1. Download `rockaden-theme.zip` from the [latest release](https://github.com/msvens/rockaden-wp/releases/latest)
2. In WP Admin, go to **Appearance → Themes → Add New → Upload Theme** and upload it
3. Activate. Install the plugin from its own repository for the chess features.

Updates appear in WP Admin like any other theme (the theme checks this repository's releases).

## Development

Requires PHP 8.1+ with Composer, Node.js 22+ (for the i18n and packaging scripts), and Docker.

```bash
git clone https://github.com/msvens/rockaden-wp.git
cd rockaden-wp
composer install
npx wp-env start        # WordPress at http://localhost:8888 (admin/password)
```

`.wp-env.json` mounts this directory as `wp-content/themes/rockaden-theme` and the plugin from a
sibling checkout at `../chess-wp-plugin` as `wp-content/plugins/rockaden-chess`, the same names as
on a real site. Clone the plugin beside this repository. On first start activate both once:

```bash
npx wp-env run cli wp theme activate rockaden-theme
npx wp-env run cli wp plugin activate rockaden-chess
npx wp-env run cli wp rewrite structure '/%postname%/'
npx wp-env run cli wp rewrite flush --hard
```

There is no build step; PHP, CSS and the no-build block scripts are served as they are.

### Quality checks

```bash
pnpm run check     # PHPStan + phpcs + translations
```

### Translations

Source strings are **Swedish**; the catalogue is `languages/rockaden-theme-en_US.po` for the
visitor SV/EN switch, the only file you edit. Everything else is generated:

```bash
pnpm i18n          # re-extract, merge into the .po, regenerate .mo/.l10n.php
pnpm i18n:check    # verify nothing has drifted (runs in `check` and CI)
```

**After adding or changing a user-facing string, run `pnpm i18n`**, then fill in any untranslated
entries and run it again. `msgmerge` may mark a reworded string **fuzzy** with a guessed translation;
fuzzy entries are excluded from the compiled catalogue, so review every one. Many entries are
identity translations: the admin UI is written in English and those strings translate to themselves.

Regenerating needs `vendor/bin/wp` (`composer install`) and GNU gettext (`brew install gettext`).
Checking needs neither. The bundled documentation ships both languages as `rc-doc-sv` / `rc-doc-en`
elements toggled by CSS, and nav labels, CTA and footer text are per-locale option pairs edited in
**Appearance → Rockaden**.

### Package

```bash
pnpm package            # dist/rockaden-theme.zip (root folder = install slug)
```

### Creating a release

Bump `Version:` in `style.css`, run `pnpm i18n`, commit, then tag and push — GitHub Actions builds
the zip and publishes a release:

```bash
git tag v0.46.0
git push origin main v0.46.0
```

Never push a version bump to `main` without tagging it: the update checker falls back to the
branch's source zip if it finds no release, and that zip has no `vendor/`.

## License

[MIT](LICENSE)
