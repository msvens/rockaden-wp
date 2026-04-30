<?php
/**
 * Title: Landing Why Section
 * Slug: rockaden-theme/landing-why
 * Categories: rockaden
 * Description: "Why Rockaden?" section with feature cards on the left and image on the right.
 *
 * Structure: heading, cards, and media are all direct siblings of .rockaden-landing-why
 * so that CSS Grid (defined in custom.css) can place media spanning both the
 * heading row and the cards row, making the image span the full left-column height.
 */
?>
<!-- wp:group {"align":"wide","className":"rockaden-landing-why","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide rockaden-landing-why">

  <!-- wp:heading {"level":2,"className":"rockaden-landing-why__heading"} -->
  <h2 class="wp-block-heading rockaden-landing-why__heading"><?php esc_html_e( 'Varför välja SK Rockaden?', 'rockaden-theme' ); ?></h2>
  <!-- /wp:heading -->

  <!-- wp:group {"className":"rockaden-landing-why__cards","layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->
  <div class="wp-block-group rockaden-landing-why__cards">

    <!-- wp:group {"className":"rockaden-feature-card","layout":{"type":"flex","flexWrap":"nowrap"}} -->
    <div class="wp-block-group rockaden-feature-card">
      <!-- wp:paragraph {"className":"rockaden-feature-card__icon"} -->
      <p class="rockaden-feature-card__icon">♟️</p>
      <!-- /wp:paragraph -->
      <!-- wp:group {"className":"rockaden-feature-card__body","layout":{"type":"flex","orientation":"vertical"}} -->
      <div class="wp-block-group rockaden-feature-card__body">
        <!-- wp:heading {"level":3,"className":"rockaden-feature-card__title"} -->
        <h3 class="wp-block-heading rockaden-feature-card__title"><?php esc_html_e( 'Träningsgrupper för alla åldrar', 'rockaden-theme' ); ?></h3>
        <!-- /wp:heading -->
        <!-- wp:paragraph {"className":"rockaden-feature-card__text"} -->
        <p class="rockaden-feature-card__text"><?php esc_html_e( 'Nybörjare till elit', 'rockaden-theme' ); ?></p>
        <!-- /wp:paragraph -->
      </div>
      <!-- /wp:group -->
    </div>
    <!-- /wp:group -->

    <!-- wp:group {"className":"rockaden-feature-card","layout":{"type":"flex","flexWrap":"nowrap"}} -->
    <div class="wp-block-group rockaden-feature-card">
      <!-- wp:paragraph {"className":"rockaden-feature-card__icon"} -->
      <p class="rockaden-feature-card__icon">👧</p>
      <!-- /wp:paragraph -->
      <!-- wp:group {"className":"rockaden-feature-card__body","layout":{"type":"flex","orientation":"vertical"}} -->
      <div class="wp-block-group rockaden-feature-card__body">
        <!-- wp:heading {"level":3,"className":"rockaden-feature-card__title"} -->
        <h3 class="wp-block-heading rockaden-feature-card__title"><?php esc_html_e( 'Tjejgrupp – trygg miljö och gemenskap', 'rockaden-theme' ); ?></h3>
        <!-- /wp:heading -->
        <!-- wp:paragraph {"className":"rockaden-feature-card__text"} -->
        <p class="rockaden-feature-card__text"><?php esc_html_e( 'Gemenskap och fler tjejer på schackbrädet', 'rockaden-theme' ); ?></p>
        <!-- /wp:paragraph -->
      </div>
      <!-- /wp:group -->
    </div>
    <!-- /wp:group -->

    <!-- wp:group {"className":"rockaden-feature-card","layout":{"type":"flex","flexWrap":"nowrap"}} -->
    <div class="wp-block-group rockaden-feature-card">
      <!-- wp:paragraph {"className":"rockaden-feature-card__icon"} -->
      <p class="rockaden-feature-card__icon">📍</p>
      <!-- /wp:paragraph -->
      <!-- wp:group {"className":"rockaden-feature-card__body","layout":{"type":"flex","orientation":"vertical"}} -->
      <div class="wp-block-group rockaden-feature-card__body">
        <!-- wp:heading {"level":3,"className":"rockaden-feature-card__title"} -->
        <h3 class="wp-block-heading rockaden-feature-card__title"><?php esc_html_e( 'Centralt i Hägersten', 'rockaden-theme' ); ?></h3>
        <!-- /wp:heading -->
        <!-- wp:paragraph {"className":"rockaden-feature-card__text"} -->
        <p class="rockaden-feature-card__text"><?php esc_html_e( 'Riksdalergatan 2 / Sparbanksvägen 31', 'rockaden-theme' ); ?></p>
        <!-- /wp:paragraph -->
      </div>
      <!-- /wp:group -->
    </div>
    <!-- /wp:group -->

  </div>
  <!-- /wp:group -->

  <!-- wp:group {"className":"rockaden-landing-why__media","layout":{"type":"default"}} -->
  <div class="wp-block-group rockaden-landing-why__media">
    <!-- wp:image {"sizeSlug":"large","className":"rockaden-landing-why__image"} -->
    <figure class="wp-block-image size-large rockaden-landing-why__image"><img src="" alt="<?php esc_attr_e( 'Barn spelar schack', 'rockaden-theme' ); ?>"/></figure>
    <!-- /wp:image -->
  </div>
  <!-- /wp:group -->

</div>
<!-- /wp:group -->
