<?php
/**
 * Title: Landing Hero
 * Slug: rockaden-theme/landing-hero
 * Categories: rockaden
 * Description: Full-width hero banner with overlay text and CTA buttons.
 *
 * Markup matches what core/cover save() emits exactly (verified by running
 * "Attempt recovery" in the editor and copying the canonical output back):
 *  - is-light is folded into the className attribute (not free-floating in HTML)
 *  - span has only has-background-dim (no -50 variant — dimRatioToClass(50)
 *    returns null in core/cover; only non-default ratios get a class)
 */
?>
<!-- wp:cover {"dimRatio":50,"align":"full","className":"is-light rockaden-landing-hero"} -->
<div class="wp-block-cover alignfull is-light rockaden-landing-hero"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><div class="wp-block-cover__inner-container">
<!-- wp:heading {"level":1,"className":"rockaden-landing-hero__title"} -->
<h1 class="wp-block-heading rockaden-landing-hero__title"><?php esc_html_e( 'Upptäck schack hos SK Rockaden', 'rockaden-theme' ); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"rockaden-landing-hero__subtitle"} -->
<p class="rockaden-landing-hero__subtitle"><?php esc_html_e( 'Gemenskap, utveckling och spelglädje – för alla nivåer', 'rockaden-theme' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"className":"rockaden-landing-hero__buttons"} -->
<div class="wp-block-buttons rockaden-landing-hero__buttons"><!-- wp:button {"className":"is-style-fill rockaden-cta-primary"} -->
<div class="wp-block-button is-style-fill rockaden-cta-primary"><a class="wp-block-button__link wp-element-button" href="/bli-medlem"><?php esc_html_e( 'Bli medlem', 'rockaden-theme' ); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline rockaden-cta-outline"} -->
<div class="wp-block-button is-style-outline rockaden-cta-outline"><a class="wp-block-button__link wp-element-button" href="/kontakt"><?php esc_html_e( 'Prova gratis', 'rockaden-theme' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->
</div></div>
<!-- /wp:cover -->
