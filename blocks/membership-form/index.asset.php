<?php
/**
 * Script dependencies + version for the membership-form editor script.
 *
 * Hand-authored (the theme has no build step), like blocks/feedback-form.
 */

return [
	'dependencies' => ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render'],
	// Version by filemtime so editing index.js always busts the editor cache.
	'version'      => (string) filemtime(__DIR__ . '/index.js'),
];
