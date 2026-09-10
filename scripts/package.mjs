import { execSync } from 'child_process';
import { mkdirSync, existsSync, rmSync, cpSync, mkdtempSync } from 'fs';
import { join, dirname, sep, relative } from 'path';
import { tmpdir } from 'os';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const root = join(__dirname, '..');
const dist = join(root, 'dist');

// WordPress expects a zip to contain exactly one top-level directory, and uses
// its name as the install slug, so the theme is staged under its slug and that
// directory is zipped. The update checker matches on the directory name.
const pkg = {
  // The folder name becomes the install slug: do not change it casually.
  slug: 'rockaden-theme',
  zip: 'rockaden-theme.zip',
  // Paths are relative to the staged package root, so `composer.json` excludes
  // only ours and leaves vendor/**/composer.json alone.
  exclude: [
    'node_modules/*',
    '*/node_modules/*',
    'composer.json',
    'composer.lock',
    'phpstan.neon',
    'phpcs.xml',
    'package.json',
    '.eslintrc.json',
    // Repository housekeeping, not theme files.
    '.gitignore',
    '.wp-env.json',
    'CLAUDE.md',
    // The catalogue sources are build inputs; only .mo/.l10n.php ship.
    'languages/*.pot',
    'languages/*.po',
  ],
};

// Repository-level directories that are not part of the theme at all. They
// are skipped when staging (the excludes above decide the rest).
const NOT_THEME = new Set(['.git', '.github', '.claude', '.idea', '.wp-env', 'dist', 'scripts', 'node_modules']);

if (!existsSync(dist)) mkdirSync(dist);

// `zip -r` UPDATES an existing archive rather than replacing it, so anything
// removed or newly excluded would linger in the zip forever. Start clean.
rmSync(join(dist, pkg.zip), { force: true });

// Re-install Composer deps without dev requirements so the bundled vendor/
// only contains runtime libraries (plugin-update-checker), not PHPStan or
// phpcs. After packaging, run `composer install` to restore dev tools.
console.log('Installing production Composer deps...');
execSync('composer install --no-dev --no-progress --quiet', { cwd: root, stdio: 'inherit' });

// Stage outside the repository: Node refuses to copy a directory into its own
// subtree, and dist/ lives inside the root being copied.
const staging = mkdtempSync(join(tmpdir(), 'rockaden-theme-'));

console.log(`Packaging ${pkg.slug}...`);
const staged = join(staging, pkg.slug);
cpSync(root, staged, {
  recursive: true,
  filter: (src) => {
    const top = relative(root, src).split(sep)[0];
    return top === '' || !NOT_THEME.has(top);
  },
});

// Re-anchor the excludes onto the new root folder.
const args = pkg.exclude.map((pattern) => `"${pkg.slug}/${pattern}"`).join(' ');
execSync(`cd "${staging}" && zip -r "${join(dist, pkg.zip)}" "${pkg.slug}" -x ${args}`, {
  stdio: 'inherit',
});

rmSync(staging, { recursive: true, force: true });

console.log(`Done! dist/${pkg.zip}`);
