<?php
/**
 * Script dependencies + version for the shop-grid editor script.
 *
 * Hand-authored (the theme has no build step). WordPress reads this file
 * automatically when registering the block's editorScript from block.json,
 * so the editor script loads after its wp.* dependencies.
 */

return [
	'dependencies' => ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components'],
	// Version by filemtime so editing index.js always busts the editor cache.
	'version'      => (string) filemtime(__DIR__ . '/index.js'),
];
