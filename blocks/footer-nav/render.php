<?php
/**
 * Server-side render for the Footer Nav block.
 *
 * Renders the links and text configured under Appearance → Rockaden. Rendered
 * server-side rather than from the JS config the header uses, so footer links
 * are present in the HTML for crawlers and assistive tech without waiting on JS.
 *
 * @package Rockaden
 *
 * @var array<string, mixed> $attributes Block attributes.
 * @var string               $content    Block content.
 * @var WP_Block             $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

$options = Rockaden_Theme_Settings::get_options();

// Labels and URLs resolved for the active front-end locale — English values
// fall back to Swedish when unset. Shared with the header so the two agree.
$nav_items = Rockaden_Theme_Settings::localize_nav_items( $options['footer_nav'] ?? [] );
$nav_items = array_values(
	array_filter(
		$nav_items,
		static function ( array $item ): bool {
			return '' !== $item['label'] && '' !== $item['url'];
		}
	)
);

$footer_text = Rockaden_Theme_Settings::footer_text();

if ( empty( $nav_items ) && '' === $footer_text ) {
	return;
}

$wrapper_attributes = get_block_wrapper_attributes( [ 'class' => 'rc-footer-nav' ] );
?>
<div <?php echo wp_kses_post( $wrapper_attributes ); ?>>
	<?php if ( ! empty( $nav_items ) ) : ?>
		<nav class="rc-footer-nav__nav">
			<ul class="rc-footer-nav__list">
				<?php foreach ( $nav_items as $item ) : ?>
					<li>
						<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>
	<?php endif; ?>

	<?php if ( '' !== $footer_text ) : ?>
		<p class="rc-footer-nav__text"><?php echo esc_html( $footer_text ); ?></p>
	<?php endif; ?>
</div>
