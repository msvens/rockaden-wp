<?php
/**
 * Sidebar Panel block — server render.
 *
 * Visibility rules:
 * - When the block is in a template (templateMode: true), it's gated by the
 *   per-page "Show sidebar on this page" checkbox (post meta rc_show_sidebar).
 *   Default behavior on templated pages: hidden.
 * - When the block is inserted directly into page content (templateMode: false,
 *   the default), it always renders if cards are configured. The admin chose
 *   to place it; no separate opt-in needed.
 *
 * In both cases, when the block must render hidden, we emit an empty
 * `.rc-sidebar-panel--hidden` placeholder so the existing `:has()` CSS in
 * custom.css collapses the parent sidebar column on templated pages.
 *
 * @var array<string, mixed> $attributes Block attributes.
 */

defined('ABSPATH') || exit;

$template_mode = ! empty($attributes['templateMode']);

if ($template_mode) {
	$post_id = get_queried_object_id();
	$show    = $post_id ? '1' === get_post_meta($post_id, 'rc_show_sidebar', true) : false;

	if (! $show) {
		echo '<div class="rc-sidebar-panel--hidden" hidden></div>';
		return;
	}
}

$cards = Rockaden_Theme_Settings::get_options()['sidebar_cards'] ?? [];

if (empty($cards)) {
	// No cards configured — emit the hidden placeholder so a templated
	// column still collapses cleanly.
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
