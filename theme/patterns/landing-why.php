<?php
/**
 * Title: Landing Why Section
 * Slug: rockaden-theme/landing-why
 * Categories: rockaden
 * Description: "Why Rockaden?" section with feature cards on the left and image on the right.
 *
 * Uses native wp:columns. Only the right column has an explicit width (40%);
 * the left column has no width and uses Gutenberg's default `flex-grow: 1`
 * to fill the remaining ~60% (minus gap). This avoids the layout quirk where
 * BOTH columns having explicit-percent widths get `flex-grow: 0`, which can
 * cause the columns to not fill the container correctly when combined with
 * the flex gap. Same idea works for any future ratio — set only one column.
 *
 * - Left column holds the heading and feature cards (heading sits inside the
 *   column, naturally aligning the image's top edge to the heading's top edge).
 * - Right column holds the image, stretched via CSS to fill the column height.
 *
 * Default WP columns stack on mobile (≤782px viewport).
 */
?>
<!-- wp:group {"align":"wide","className":"rockaden-landing-why","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide rockaden-landing-why">

<!-- wp:columns {"className":"rockaden-landing-why__columns"} -->
<div class="wp-block-columns rockaden-landing-why__columns">

<!-- wp:column {"className":"rockaden-landing-why__cards-col"} -->
<div class="wp-block-column rockaden-landing-why__cards-col">
<!-- wp:heading {"level":2,"className":"rockaden-landing-why__heading"} -->
<h2 class="wp-block-heading rockaden-landing-why__heading"><?php esc_html_e( 'Varför välja SK Rockaden?', 'rockaden-theme' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:group {"className":"rockaden-feature-card","layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group rockaden-feature-card"><!-- wp:paragraph {"className":"rockaden-feature-card__icon"} -->
<p class="rockaden-feature-card__icon">♟️</p>
<!-- /wp:paragraph -->

<!-- wp:group {"className":"rockaden-feature-card__body","layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group rockaden-feature-card__body"><!-- wp:heading {"level":3,"className":"rockaden-feature-card__title"} -->
<h3 class="wp-block-heading rockaden-feature-card__title"><?php esc_html_e( 'Träningsgrupper för alla åldrar', 'rockaden-theme' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"rockaden-feature-card__text"} -->
<p class="rockaden-feature-card__text"><?php esc_html_e( 'Nybörjare till elit', 'rockaden-theme' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"rockaden-feature-card","layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group rockaden-feature-card"><!-- wp:paragraph {"className":"rockaden-feature-card__icon"} -->
<p class="rockaden-feature-card__icon">👧</p>
<!-- /wp:paragraph -->

<!-- wp:group {"className":"rockaden-feature-card__body","layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group rockaden-feature-card__body"><!-- wp:heading {"level":3,"className":"rockaden-feature-card__title"} -->
<h3 class="wp-block-heading rockaden-feature-card__title"><?php esc_html_e( 'Tjejgrupp – trygg miljö och gemenskap', 'rockaden-theme' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"rockaden-feature-card__text"} -->
<p class="rockaden-feature-card__text"><?php esc_html_e( 'Gemenskap och fler tjejer på schackbrädet', 'rockaden-theme' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"rockaden-feature-card","layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group rockaden-feature-card"><!-- wp:paragraph {"className":"rockaden-feature-card__icon"} -->
<p class="rockaden-feature-card__icon">📍</p>
<!-- /wp:paragraph -->

<!-- wp:group {"className":"rockaden-feature-card__body","layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group rockaden-feature-card__body"><!-- wp:heading {"level":3,"className":"rockaden-feature-card__title"} -->
<h3 class="wp-block-heading rockaden-feature-card__title"><?php esc_html_e( 'Centralt i Hägersten', 'rockaden-theme' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"rockaden-feature-card__text"} -->
<p class="rockaden-feature-card__text"><?php esc_html_e( 'Riksdalergatan 2 / Sparbanksvägen 31', 'rockaden-theme' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
</div>
<!-- /wp:column -->

<!-- wp:column {"width":"40%","className":"rockaden-landing-why__media-col"} -->
<div class="wp-block-column rockaden-landing-why__media-col" style="flex-basis:40%">
<!-- wp:image {"sizeSlug":"large","className":"rockaden-landing-why__image"} -->
<figure class="wp-block-image size-large rockaden-landing-why__image"><img src="" alt="<?php esc_attr_e( 'Barn spelar schack', 'rockaden-theme' ); ?>"/></figure>
<!-- /wp:image -->
</div>
<!-- /wp:column -->

</div>
<!-- /wp:columns -->

</div>
<!-- /wp:group -->
