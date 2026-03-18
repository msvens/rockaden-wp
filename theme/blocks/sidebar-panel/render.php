<?php
/**
 * Sidebar Panel block — server render.
 *
 * Checks global sidebar visibility setting and per-page override,
 * then renders sidebar cards from the rockaden_theme_options option.
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

$cards = $options['sidebar_cards'] ?? [];

if (empty($cards)) {
	echo '<div class="rc-sidebar-panel--hidden" hidden></div>';
	return;
}

echo '<aside class="rc-sidebar">';

foreach ($cards as $card) {
	$type       = $card['type'] ?? 'text';
	$title      = $card['title'] ?? '';
	$show_title = $card['show_title'] ?? true;
	$content    = $card['content'] ?? '';
	$link_url   = $card['link_url'] ?? '';
	$link_label = $card['link_label'] ?? '';
	$image_url  = $card['image_url'] ?? '';
	$full_bleed = ! empty($card['full_bleed']);

	$card_classes = 'rc-sidebar-card';
	if ($full_bleed && $type === 'image') {
		$card_classes .= ' rc-sidebar-card--full-bleed';
	}

	echo '<div class="' . esc_attr($card_classes) . '">';

	if ($show_title && $title !== '') {
		echo '<h3>' . esc_html($title) . '</h3>';
	}

	if ($type === 'image' && $image_url !== '') {
		$img_tag = '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($title) . '" />';
		if ($link_url !== '') {
			echo '<a href="' . esc_url($link_url) . '">' . $img_tag . '</a>';
		} else {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above
			echo $img_tag;
		}
	} else {
		if ($content !== '') {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitized via wp_kses_post on save
			echo $content;
		}

		if ($link_url !== '' && $link_label !== '') {
			echo '<div class="wp-block-buttons"><div class="wp-block-button is-style-outline has-small-font-size">';
			echo '<a class="wp-block-button__link wp-element-button" href="' . esc_url($link_url) . '">' . esc_html($link_label) . '</a>';
			echo '</div></div>';
		}
	}

	echo '</div>';
}

echo '</aside>';
