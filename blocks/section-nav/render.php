<?php
/**
 * Server-side render for the Section Nav block.
 * Builds a sidebar navigation from the current page's parent/child hierarchy.
 *
 * @package Rockaden
 *
 * @var array<string, mixed> $attributes Block attributes.
 * @var string               $content    Block content.
 * @var WP_Block             $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$current_id = get_the_ID();
if ( ! $current_id ) {
	return;
}

// Shared helper builds the section menu (root + children, with label overrides).
// Empty means this page is not part of a section — render nothing.
$all_items = Rockaden_Theme_Section_Nav::get_menu_items( (int) $current_id );
if ( empty( $all_items ) ) {
	return;
}

// Root is the first item; its label labels the nav for screen readers (even if
// the root itself is hidden from the menu).
$root_label = $all_items[0]['label'];

// Current page label (for the mobile dropdown button) — from the full list, as
// the current page may itself be hidden from its own menu.
$current_label = $root_label;
foreach ( $all_items as $item ) {
	if ( $item['is_current'] ) {
		$current_label = $item['label'];
		break;
	}
}

// Drop items hidden from the menu. If nothing visible remains, render nothing.
$nav_items = array_values(
	array_filter(
		$all_items,
		static function ( $item ) {
			return ! $item['hidden'];
		}
	)
);
if ( empty( $nav_items ) ) {
	return;
}

// Unique ID for this instance (multiple blocks on one page).
$nav_id = 'rc-snav-' . wp_unique_id();

$wrapper_attributes = get_block_wrapper_attributes(
	[ 'class' => 'rc-section-nav' ]
);
?>
<div <?php echo wp_kses_post( $wrapper_attributes ); ?>>
	<nav class="rc-section-nav__desktop" aria-label="<?php echo esc_attr( $root_label ); ?>">
		<ul class="rc-section-nav__list">
			<?php foreach ( $nav_items as $item ) : ?>
				<li>
					<a
						href="<?php echo esc_url( $item['url'] ); ?>"
						<?php echo $item['is_current'] ? ' aria-current="page"' : ''; ?>
					><?php echo esc_html( $item['label'] ); ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
	</nav>

	<div class="rc-section-nav__mobile-wrap">
		<button
			type="button"
			class="rc-section-nav__mobile-btn"
			id="<?php echo esc_attr( $nav_id ); ?>-btn"
			aria-expanded="false"
			aria-controls="<?php echo esc_attr( $nav_id ); ?>-menu"
		>
			<span><?php echo esc_html( $current_label ); ?></span>
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
				<path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
			</svg>
		</button>
		<ul
			class="rc-section-nav__mobile-menu"
			id="<?php echo esc_attr( $nav_id ); ?>-menu"
			role="listbox"
			aria-label="<?php echo esc_attr( $root_label ); ?>"
		>
			<?php foreach ( $nav_items as $item ) : ?>
				<li>
					<a
						href="<?php echo esc_url( $item['url'] ); ?>"
						<?php echo $item['is_current'] ? ' aria-current="page"' : ''; ?>
					><?php echo esc_html( $item['label'] ); ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</div>
