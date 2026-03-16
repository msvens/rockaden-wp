<?php
/**
 * Page Title block — server render.
 *
 * Renders wp:post-title unless the per-page rc_hide_title meta is set.
 */

defined('ABSPATH') || exit;

$post_id = get_the_ID();
$hide    = $post_id ? get_post_meta($post_id, 'rc_hide_title', true) : '';

if ($hide) {
	return;
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block markup
echo do_blocks('<!-- wp:post-title {"level":1} /-->');
