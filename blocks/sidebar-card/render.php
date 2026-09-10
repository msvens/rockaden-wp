<?php
/**
 * Server-side render for one Sidebar Card.
 *
 * Emits the markup the settings-driven panel already produces
 * (blocks/sidebar-panel/render.php), class for class, so both are styled by the
 * single rule set in custom.css. A change to how sidebar cards look then lands
 * on the news views and on pages together, rather than drifting apart.
 *
 * Rendered from attributes rather than saved markup so the image goes through
 * wp_get_attachment_image() — that is what produces a responsive srcset, and it
 * keeps working when an attachment's sizes are regenerated.
 *
 * @package Rockaden
 *
 * @var array<string, mixed> $attributes Block attributes.
 * @var string               $content    Block content.
 * @var WP_Block             $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$rc_type       = 'image' === ( $attributes['cardType'] ?? 'text' ) ? 'image' : 'text';
$rc_title      = (string) ( $attributes['title'] ?? '' );
$rc_show_title = ! empty( $attributes['showTitle'] );
$rc_link_url   = trim( (string) ( $attributes['linkUrl'] ?? '' ) );
$rc_full_bleed = ! empty( $attributes['fullBleed'] );

$rc_classes = 'rc-sidebar-card';
if ( $rc_full_bleed && 'image' === $rc_type ) {
	$rc_classes .= ' rc-sidebar-card--full-bleed';
}

$rc_body = '';

if ( 'image' === $rc_type ) {
	$rc_media_id  = (int) ( $attributes['mediaId'] ?? 0 );
	$rc_media_url = (string) ( $attributes['mediaUrl'] ?? '' );
	// The settings form falls back to the card title for alt text; an explicit
	// alt field is better, so prefer it and keep the title as the fallback.
	$rc_alt = (string) ( $attributes['alt'] ?? '' );
	if ( '' === $rc_alt ) {
		$rc_alt = $rc_title;
	}

	if ( $rc_media_id > 0 && wp_attachment_is_image( $rc_media_id ) ) {
		$rc_body = wp_get_attachment_image(
			$rc_media_id,
			'medium',
			false,
			[
				'alt'     => $rc_alt,
				'loading' => 'lazy',
			]
		);
	} elseif ( '' !== $rc_media_url ) {
		// Only reached for a card whose attachment has since been deleted.
		$rc_body = sprintf(
			'<img src="%s" alt="%s" loading="lazy" />',
			esc_url( $rc_media_url ),
			esc_attr( $rc_alt )
		);
	}

	if ( '' !== $rc_body && '' !== $rc_link_url ) {
		$rc_body = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $rc_link_url ),
			$rc_body
		);
	}
} else {
	$rc_body = (string) ( $attributes['content'] ?? '' );

	$rc_link_label = trim( (string) ( $attributes['linkLabel'] ?? '' ) );
	if ( '' !== $rc_link_url && '' !== $rc_link_label ) {
		$rc_body .= '<div class="wp-block-buttons"><div class="wp-block-button is-style-outline has-small-font-size">'
			. '<a class="wp-block-button__link wp-element-button" href="' . esc_url( $rc_link_url ) . '">'
			. esc_html( $rc_link_label )
			. '</a></div></div>';
	}
}

// A card with neither a body nor a visible title is one the editor has not
// filled in yet; rendering an empty panel on the live site is worse than
// rendering none. A title on its own is legitimate, so it counts as content.
$rc_has_title = $rc_show_title && '' !== $rc_title;
if ( '' === trim( $rc_body ) && ! $rc_has_title ) {
	return;
}

$rc_wrapper = get_block_wrapper_attributes( [ 'class' => $rc_classes ] );
?>
<div <?php echo wp_kses_post( $rc_wrapper ); ?>>
	<?php if ( $rc_show_title && '' !== $rc_title ) : ?>
		<h3><?php echo esc_html( $rc_title ); ?></h3>
	<?php endif; ?>
	<?php echo wp_kses_post( $rc_body ); ?>
</div>
