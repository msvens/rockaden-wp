<?php
/**
 * Title: Hero
 * Slug: rockaden-theme/hero
 * Categories: rockaden
 * Description: Hero section with club name and tagline.
 */
?>
<!-- wp:cover {"dimRatio":70,"overlayColor":"primary","isUserOverlayColor":true,"minHeight":400,"align":"full","layout":{"type":"constrained"}} -->
<div class="wp-block-cover alignfull" style="min-height:400px">
  <span aria-hidden="true" class="wp-block-cover__background has-primary-background-color has-background-dim-70 has-background-dim"></span>
  <div class="wp-block-cover__inner-container">
    <!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"3rem"},"color":{"text":"#ffffff"}}} -->
    <h1 class="wp-block-heading has-text-align-center" style="color:#ffffff;font-size:3rem"><?php esc_html_e('Welcome to SK Rockaden', 'rockaden-theme'); ?></h1>
    <!-- /wp:heading -->
    <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"1.25rem"},"color":{"text":"#d1d5db"}}} -->
    <p class="has-text-align-center" style="color:#d1d5db;font-size:1.25rem"><?php esc_html_e('One of Stockholm\'s oldest and most active chess clubs', 'rockaden-theme'); ?></p>
    <!-- /wp:paragraph -->
  </div>
</div>
<!-- /wp:cover -->
