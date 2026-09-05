<?php
/**
 * Server-side render for the Partner Logos block.
 *
 * The wrapper only supplies layout; every logo comes from a child
 * rockaden/partner-logo block, already rendered into $content. Rendering the
 * wrapper server-side rather than saving it means the markup and class names can
 * change in a later theme release without invalidating pages that already use
 * the block.
 *
 * @package Rockaden
 *
 * @var array<string, mixed> $attributes Block attributes.
 * @var string               $content    Rendered child blocks.
 * @var WP_Block             $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

// An empty container would render as a stray gap on the page.
if ( '' === trim( $content ) ) {
	return;
}

$rc_orientation = ( 'horizontal' === ( $attributes['orientation'] ?? 'vertical' ) )
	? 'horizontal'
	: 'vertical';

$rc_logo_width = (int) ( $attributes['logoWidth'] ?? 160 );
$rc_logo_width = max( 60, min( 400, $rc_logo_width ) );

$rc_wrapper = get_block_wrapper_attributes(
	[
		'class' => 'rockaden-partner-logos is-' . $rc_orientation,
		'style' => '--rockaden-partner-logo-width:' . $rc_logo_width . 'px',
	]
);
?>
<div <?php echo wp_kses_post( $rc_wrapper ); ?>>
	<?php
	// Safe to filter rather than echo raw: allowedBlocks limits the children to
	// rockaden/partner-logo, so the only markup here is the anchor and image
	// that block renders, both of which wp_kses_post permits in full.
	echo wp_kses_post( $content );
	?>
</div>
