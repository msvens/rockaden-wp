<?php
/**
 * Version for the membership-form view script.
 *
 * Without this file WordPress versions view.js by block.json's "version",
 * so an edited script would keep being served from visitors' caches.
 */

return [
	'dependencies' => [],
	'version'      => (string) filemtime(__DIR__ . '/view.js'),
];
