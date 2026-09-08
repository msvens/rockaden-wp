<?php
/**
 * Server-side render for the Sidebar block.
 *
 * The wrapper only supplies the column; every card comes from a child
 * rockaden/sidebar-card block, already rendered into $content.
 *
 * Emits the same `rc-sidebar` wrapper the settings-driven panel does
 * (blocks/sidebar-panel/render.php), so both are styled by the one rule set in
 * custom.css and a change to the sidebar's look lands on the news views and on
 * pages together.
 *
 * @package Rockaden
 *
 * @var array<string, mixed> $attributes Block attributes.
 * @var string               $content    Rendered child blocks.
 * @var WP_Block             $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

// An empty sidebar would render as a stray gap in the column.
if ( '' === trim( $content ) ) {
	return;
}

// A width cap set on the block, so the stack can be narrower than whatever
// column it sits in. Validated rather than trusted: the value reaches a style
// attribute, so anything that is not a plain CSS length is dropped.
$rc_attrs     = [ 'class' => 'rc-sidebar' ];
$rc_max_width = trim( (string) ( $attributes['maxWidth'] ?? '' ) );

if ( '' !== $rc_max_width && preg_match( '/^\d+(\.\d+)?(px|rem|em|%|ch|vw)$/', $rc_max_width ) ) {
	$rc_attrs['style'] = 'max-width:' . $rc_max_width;
}

$rc_wrapper = get_block_wrapper_attributes( $rc_attrs );
?>
<aside <?php echo wp_kses_post( $rc_wrapper ); ?>>
	<?php
	// Safe to filter rather than echo raw: allowedBlocks limits the children to
	// rockaden/sidebar-card, so the only markup here is the card, image, link
	// and button that block renders — all of which wp_kses_post permits.
	echo wp_kses_post( $content );
	?>
</aside>
