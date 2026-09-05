import { execSync } from 'child_process';
import { mkdirSync, existsSync, rmSync, cpSync } from 'fs';
import { join, dirname, sep } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const root = join(__dirname, '..');
const dist = join(root, 'dist');

// WordPress expects a zip to contain exactly one top-level directory, and uses
// its name as the install slug. Zipping a package's *contents* leaves the
// installer to invent a name — `wp plugin install` produced
// `wp-content/plugins/rockaden-chess-mG3oNH`, which breaks the update checker,
// since PUC matches on the directory name. In-place updates were unaffected
// (the destination comes from the update metadata, not the archive), so this
// only ever bit fresh installs — ours on staging, and every other club's.
const packages = [
  {
    dir: 'plugin',
    // The folder name becomes the install slug: do not change it casually.
    slug: 'rockaden-chess',
    zip: 'rockaden-chess.zip',
    // Paths are relative to the package root, so `composer.json` excludes only
    // ours and leaves vendor/**/composer.json alone.
    exclude: [
      'node_modules/*',
      'js/*',
      'package.json',
      'webpack.config.js',
      'tsconfig.json',
      '.npmrc',
      'composer.json',
      'composer.lock',
      'phpstan.neon',
      'phpstan-bootstrap.php',
      'phpcs.xml',
      '.eslintrc.json',
      // The catalogue sources are build inputs; only .mo/.l10n.php ship.
      'languages/*.pot',
      'languages/*.po',
    ],
  },
  {
    dir: 'theme',
    slug: 'rockaden-theme',
    zip: 'rockaden-theme.zip',
    exclude: [
      'node_modules/*',
      '*/node_modules/*',
      'composer.json',
      'composer.lock',
      'phpstan.neon',
      'phpcs.xml',
      'package.json',
      '.eslintrc.json',
      'languages/*.pot',
      'languages/*.po',
    ],
  },
];

if (!existsSync(dist)) mkdirSync(dist);

// `zip -r` UPDATES an existing archive rather than replacing it, so anything
// removed from a package (or newly excluded above) would linger in the zip
// forever. Start from a clean file each time.
for (const { zip } of packages) {
  rmSync(join(dist, zip), { force: true });
}

// Re-install Composer deps without dev requirements so the bundled vendor/
// only contains runtime libraries (e.g. plugin-update-checker), not PHPStan
// or phpcs. After packaging, run `composer install` in plugin/ and theme/ to
// restore dev tools for local linting.
console.log('Installing production Composer deps...');
for (const { dir } of packages) {
  execSync(`cd "${join(root, dir)}" && composer install --no-dev --no-progress --quiet`, {
    stdio: 'inherit',
  });
}

// zip cannot rename an archive's root, so stage each package under its slug and
// zip that directory. node_modules is skipped during the copy purely for speed;
// the exclude lists above are what actually decide the contents.
const staging = join(dist, '.staging');
rmSync(staging, { recursive: true, force: true });

for (const { dir, slug, zip, exclude } of packages) {
  console.log(`Packaging ${dir}...`);

  const staged = join(staging, slug);
  cpSync(join(root, dir), staged, {
    recursive: true,
    filter: (src) => !src.split(sep).includes('node_modules'),
  });

  // Re-anchor the excludes onto the new root folder.
  const args = exclude.map((pattern) => `"${slug}/${pattern}"`).join(' ');
  execSync(`cd "${staging}" && zip -r "${join(dist, zip)}" "${slug}" -x ${args}`, {
    stdio: 'inherit',
  });
}

rmSync(staging, { recursive: true, force: true });

console.log('Done! Zips in dist/');
