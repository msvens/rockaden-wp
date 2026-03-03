import { execSync } from 'child_process';
import { mkdirSync, existsSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const root = join(__dirname, '..');
const dist = join(root, 'dist');

if (!existsSync(dist)) mkdirSync(dist);

console.log('Packaging plugin...');
execSync(
  `cd "${join(root, 'plugin')}" && zip -r "${join(dist, 'rockaden-chess.zip')}" . -x "node_modules/*" "src/*" "js/*" "package.json" "webpack.config.js" "tsconfig.json" ".npmrc"`,
  { stdio: 'inherit' },
);

console.log('Packaging theme...');
execSync(
  `cd "${join(root, 'theme')}" && zip -r "${join(dist, 'rockaden-theme.zip')}" . -x "node_modules/*"`,
  { stdio: 'inherit' },
);

console.log('Done! Zips in dist/');
