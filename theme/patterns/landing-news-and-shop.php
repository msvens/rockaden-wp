<?php
/**
 * Title: Landing News & Shop
 * Slug: rockaden-theme/landing-news-and-shop
 * Categories: rockaden
 * Description: Two-column section: latest news on the left, shop items + upcoming events stacked on the right.
 */
?>
<!-- wp:group {"align":"wide","className":"rockaden-landing-news-shop","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide rockaden-landing-news-shop">

  <!-- wp:group {"className":"rockaden-landing-news-shop__grid"} -->
  <div class="wp-block-group rockaden-landing-news-shop__grid">

    <!-- wp:group {"className":"rockaden-landing-news-shop__news"} -->
    <div class="wp-block-group rockaden-landing-news-shop__news">
      <!-- wp:heading {"level":2,"className":"rockaden-landing-news-shop__heading"} -->
      <h2 class="wp-block-heading rockaden-landing-news-shop__heading"><?php esc_html_e( 'Senaste nyheter', 'rockaden-theme' ); ?></h2>
      <!-- /wp:heading -->
      <!-- wp:rockaden/latest-news {"count":3} /-->
    </div>
    <!-- /wp:group -->

    <!-- wp:group {"className":"rockaden-landing-news-shop__shop"} -->
    <div class="wp-block-group rockaden-landing-news-shop__shop">
      <!-- wp:heading {"level":2,"className":"rockaden-landing-news-shop__heading"} -->
      <h2 class="wp-block-heading rockaden-landing-news-shop__heading"><?php esc_html_e( 'Schackmaterial', 'rockaden-theme' ); ?></h2>
      <!-- /wp:heading -->
      <!-- wp:rockaden/shop-grid {"count":4,"layout":"column"} /-->

      <!-- wp:heading {"level":2,"className":"rockaden-landing-news-shop__heading rockaden-landing-news-shop__heading--secondary"} -->
      <h2 class="wp-block-heading rockaden-landing-news-shop__heading rockaden-landing-news-shop__heading--secondary"><?php esc_html_e( 'Kommande aktiviteter', 'rockaden-theme' ); ?></h2>
      <!-- /wp:heading -->
      <!-- wp:rockaden/upcoming-events {"count":3} /-->
    </div>
    <!-- /wp:group -->

  </div>
  <!-- /wp:group -->

</div>
<!-- /wp:group -->
