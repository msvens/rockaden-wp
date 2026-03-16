<?php
/**
 * Sidebar Panel block — server render.
 *
 * Checks global sidebar visibility setting and per-page override,
 * then either renders the sidebar template part or a hidden marker
 * (so CSS can collapse the empty column).
 */

defined('ABSPATH') || exit;

$options          = Rockaden_Theme_Settings::get_options();
$sidebar_enabled  = $options['sidebar_enabled'] ?? 'none';
$post_id          = get_the_ID();
$page_override    = $post_id ? get_post_meta($post_id, 'rc_sidebar_override', true) : '';

// Determine visibility: per-page override wins over global setting.
if ($page_override === 'show') {
	$visible = true;
} elseif ($page_override === 'hide') {
	$visible = false;
} else {
	// Global setting.
	switch ($sidebar_enabled) {
		case 'all':
			$visible = true;
			break;
		case 'landing':
			$visible = is_front_page();
			break;
		default: // 'none'
			$visible = false;
			break;
	}
}

if (! $visible) {
	echo '<div class="rc-sidebar-panel--hidden" hidden></div>';
	return;
}

// Render the sidebar template part.
$template_part = '<!-- wp:template-part {"slug":"sidebar"} /-->';
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block markup
echo do_blocks($template_part);
