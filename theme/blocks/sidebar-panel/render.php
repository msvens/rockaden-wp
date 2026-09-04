<?php
/**
 * Sidebar Panel block — server render.
 *
 * Visibility rules:
 * - When the block is in a template (templateMode: true), it's gated by the
 *   per-route admin toggle in Appearance → Rockaden → Sidebar Visibility.
 *   Each auto-generated route (home/news archive, single post, shop archive,
 *   single shop item) has its own checkbox.
 * - When the block is inserted directly into page content (templateMode: false,
 *   the default), it always renders if cards are configured. The admin chose
 *   to place it; no separate opt-in needed.
 *
 * In both cases, when the block must render hidden, we emit an empty
 * `.rc-sidebar-panel--hidden` placeholder so the existing `:has()` CSS in
 * custom.css collapses the parent sidebar column on templated pages.
 *
 * @var array<string, mixed> $attributes Block attributes.
 *
 * @package Rockaden_Theme
 */

defined( 'ABSPATH' ) || exit;

$template_mode = ! empty( $attributes['templateMode'] );

if ( $template_mode ) {
	$route_key = null;
	if ( is_home() ) {
		$route_key = 'home';
	} elseif ( is_singular( 'post' ) ) {
		$route_key = 'single_post';
	} elseif ( is_singular( 'rc_shop_item' ) ) {
		$route_key = 'single_shop_item';
	}

	$routes = Rockaden_Theme_Settings::get_options()['sidebar_routes'] ?? [];
	$show   = $route_key && ! empty( $routes[ $route_key ] );

	if ( ! $show ) {
		echo '<div class="rc-sidebar-panel--hidden" hidden></div>';
		return;
	}
}

$cards = Rockaden_Theme_Settings::get_options()['sidebar_cards'] ?? [];

if ( empty( $cards ) ) {
	// No cards configured — emit the hidden placeholder so a templated
	// column still collapses cleanly.
	echo '<div class="rc-sidebar-panel--hidden" hidden></div>';
	return;
}

echo '<aside class="rc-sidebar">';

foreach ( $cards as $card ) {
	// $type and $title are prefixed to avoid shadowing WordPress's own globals —
	// block render files are included in a shared scope.
	$card_type  = $card['type'] ?? 'text';
	$card_title = $card['title'] ?? '';
	$show_title = $card['show_title'] ?? true;
	$content    = $card['content'] ?? '';
	$link_url   = $card['link_url'] ?? '';
	$link_label = $card['link_label'] ?? '';
	$image_url  = $card['image_url'] ?? '';
	$full_bleed = ! empty( $card['full_bleed'] );

	$card_classes = 'rc-sidebar-card';
	if ( $full_bleed && 'image' === $card_type ) {
		$card_classes .= ' rc-sidebar-card--full-bleed';
	}

	echo '<div class="' . esc_attr( $card_classes ) . '">';

	if ( $show_title && '' !== $card_title ) {
		echo '<h3>' . esc_html( $card_title ) . '</h3>';
	}

	if ( 'image' === $card_type && '' !== $image_url ) {
		$img_tag = sprintf(
			'<img src="%s" alt="%s" />',
			esc_url( $image_url ),
			esc_attr( $card_title )
		);
		if ( '' !== $link_url ) {
			printf( '<a href="%s">%s</a>', esc_url( $link_url ), wp_kses_post( $img_tag ) );
		} else {
			echo wp_kses_post( $img_tag );
		}
	} else {
		if ( '' !== $content ) {
			// Already run through wp_kses_post on save; re-applying is idempotent
			// and keeps the escaping visible at the output site.
			echo wp_kses_post( $content );
		}

		if ( '' !== $link_url && '' !== $link_label ) {
			echo '<div class="wp-block-buttons"><div class="wp-block-button is-style-outline has-small-font-size">';
			echo '<a class="wp-block-button__link wp-element-button" href="' . esc_url( $link_url ) . '">' . esc_html( $link_label ) . '</a>';
			echo '</div></div>';
		}
	}

	echo '</div>';
}

echo '</aside>';
