import { execSync } from 'child_process';
import { mkdirSync, existsSync, rmSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const root = join(__dirname, '..');
const dist = join(root, 'dist');

if (!existsSync(dist)) mkdirSync(dist);

// `zip -r` UPDATES an existing archive rather than replacing it, so anything
// removed from a package (or newly excluded below) would linger in the zip
// forever. Start from a clean file each time.
for (const zip of ['rockaden-chess.zip', 'rockaden-theme.zip']) {
  rmSync(join(dist, zip), { force: true });
}

// Re-install Composer deps without dev requirements so the bundled vendor/
// only contains runtime libraries (e.g. plugin-update-checker), not PHPStan
// or phpcs. After packaging, run `composer install` in plugin/ and theme/ to
// restore dev tools for local linting.
console.log('Installing production Composer deps...');
execSync(`cd "${join(root, 'plugin')}" && composer install --no-dev --no-progress --quiet`, { stdio: 'inherit' });
execSync(`cd "${join(root, 'theme')}" && composer install --no-dev --no-progress --quiet`, { stdio: 'inherit' });

console.log('Packaging plugin...');
execSync(
  `cd "${join(root, 'plugin')}" && zip -r "${join(dist, 'rockaden-chess.zip')}" . -x "node_modules/*" "js/*" "package.json" "webpack.config.js" "tsconfig.json" ".npmrc" "composer.json" "composer.lock" "phpstan.neon" "phpstan-bootstrap.php" "phpcs.xml" ".eslintrc.json" "languages/*.pot" "languages/*.po"`,
  { stdio: 'inherit' },
);

console.log('Packaging theme...');
execSync(
  `cd "${join(root, 'theme')}" && zip -r "${join(dist, 'rockaden-theme.zip')}" . -x "node_modules/*" "*/node_modules/*" "composer.json" "composer.lock" "phpstan.neon" "phpcs.xml" "package.json" ".eslintrc.json" "languages/*.pot" "languages/*.po"`,
  { stdio: 'inherit' },
);

console.log('Done! Zips in dist/');
