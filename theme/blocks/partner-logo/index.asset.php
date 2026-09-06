<?php
/**
 * Asset manifest for the Partner Logo editor script.
 *
 * Hand-authored (the theme has no build step) — mirrors blocks/shop-grid.
 *
 * @package Rockaden_Theme
 */

return [
	'dependencies' => [ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components' ],
	// Version by filemtime so editing index.js always busts the editor cache.
	'version'      => (string) filemtime( __DIR__ . '/index.js' ),
];
