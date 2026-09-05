<?php
/**
 * Server-side render for a single Partner Logo.
 *
 * Rendered from attributes rather than saved markup so the image is emitted
 * through wp_get_attachment_image() — that is what produces a responsive
 * srcset, and it keeps working when the attachment's sizes are regenerated.
 * The stored mediaUrl is only a fallback for a logo attached before an
 * attachment ID was recorded, or one whose attachment has since been deleted.
 *
 * @package Rockaden
 *
 * @var array<string, mixed> $attributes Block attributes.
 * @var string               $content    Block content.
 * @var WP_Block             $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$rc_media_id  = (int) ( $attributes['mediaId'] ?? 0 );
$rc_media_url = (string) ( $attributes['mediaUrl'] ?? '' );
$rc_alt       = (string) ( $attributes['alt'] ?? '' );
$rc_url       = trim( (string) ( $attributes['url'] ?? '' ) );

$rc_image = '';

if ( $rc_media_id > 0 && wp_attachment_is_image( $rc_media_id ) ) {
	$rc_image = wp_get_attachment_image(
		$rc_media_id,
		'medium',
		false,
		[
			'alt'     => $rc_alt,
			'class'   => 'rockaden-partner-logo__image',
			'loading' => 'lazy',
		]
	);
} elseif ( '' !== $rc_media_url ) {
	$rc_image = sprintf(
		'<img class="rockaden-partner-logo__image" src="%s" alt="%s" loading="lazy" />',
		esc_url( $rc_media_url ),
		esc_attr( $rc_alt )
	);
}

// A logo block with no image yet is a placeholder the editor has not filled in;
// rendering nothing beats rendering an empty box on the live site.
if ( '' === $rc_image ) {
	return;
}

$rc_wrapper = get_block_wrapper_attributes( [ 'class' => 'rockaden-partner-logo' ] );
?>
<div <?php echo wp_kses_post( $rc_wrapper ); ?>>
	<?php if ( '' !== $rc_url ) : ?>
		<a class="rockaden-partner-logo__link" href="<?php echo esc_url( $rc_url ); ?>">
			<?php echo wp_kses_post( $rc_image ); ?>
		</a>
	<?php else : ?>
		<?php echo wp_kses_post( $rc_image ); ?>
	<?php endif; ?>
</div>
