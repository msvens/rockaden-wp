import { dirname, join } from 'path';
import { fileURLToPath } from 'url';

export const root = join( dirname( fileURLToPath( import.meta.url ) ), '..', '..' );

/**
 * The one translatable package: this repository, at its root.
 *
 * Kept as a list so the i18n scripts stay identical to the plugin's copy
 * (chess-wp-plugin/scripts) — only this file differs between the two.
 */
export const PACKAGES = [
	{
		name: 'theme',
		dir: '.',
		domain: 'rockaden-theme',
		// English source, Swedish catalogue: the same convention as the plugin.
		locales: [ 'sv_SE' ],
		exclude: 'node_modules,vendor,docs,dist,scripts',
		// Strings that are spelled the same in English and Swedish. Anything
		// else translated to itself fails i18n:check.
		sameInTranslation: [
			'Rockaden',
			'Rockaden Theme',
			'SK Rockaden',
			'https://github.com/msvens/rockaden-wp',
			'https://rockaden.se',
			'Feedback',
			'Status',
			'Junior',
			'Allsvenskan',
			'Geist Sans',
			'Riksdalergatan 2 / Sparbanksvägen 31',
		],
		tsSources: [],
		// The theme ships no JS translations; its few JS strings are passed in
		// already-translated via wp_localize_script or data-* attributes.
		jed: false,
	},
];

export const paths = ( pkg, locale ) => ( {
	pkgDir: join( root, pkg.dir ),
	langDir: join( root, pkg.dir, 'languages' ),
	pot: join( root, pkg.dir, 'languages', `${ pkg.domain }.pot` ),
	po: join( root, pkg.dir, 'languages', `${ pkg.domain }-${ locale }.po` ),
	mo: join( root, pkg.dir, 'languages', `${ pkg.domain }-${ locale }.mo` ),
	php: join( root, pkg.dir, 'languages', `${ pkg.domain }-${ locale }.l10n.php` ),
	json: join( root, pkg.dir, 'languages', `${ pkg.domain }-${ locale }.json` ),
	wp: join( root, pkg.dir, 'vendor', 'bin', 'wp' ),
} );
