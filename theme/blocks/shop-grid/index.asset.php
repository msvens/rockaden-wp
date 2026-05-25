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
	'version'      => '0.1.0',
];
