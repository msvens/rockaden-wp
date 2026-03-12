<?php
/**
 * Title: News Grid
 * Slug: rockaden-theme/news-grid
 * Categories: rockaden
 * Description: A grid of recent news posts with card styling.
 */
?>
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">

  <!-- wp:heading {"level":2,"style":{"typography":{"fontWeight":"300","letterSpacing":"0.025em"}}} -->
  <h2 class="wp-block-heading" style="font-weight:300;letter-spacing:0.025em"><?php esc_html_e('Recent News', 'rockaden-theme'); ?></h2>
  <!-- /wp:heading -->

  <!-- wp:query {"queryId":10,"query":{"perPage":6,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date"},"displayLayout":{"type":"flex","columns":3}} -->
  <div class="wp-block-query">
    <!-- wp:post-template -->
      <!-- wp:group {"className":"rockaden-card","style":{"spacing":{"padding":{"top":"1.5rem","bottom":"1.5rem","left":"1.5rem","right":"1.5rem"}}},"layout":{"type":"flex","orientation":"vertical"}} -->
      <div class="wp-block-group rockaden-card" style="padding:1.5rem">
        <!-- wp:post-title {"isLink":true,"style":{"typography":{"fontWeight":"300","letterSpacing":"0.025em"}},"fontSize":"large"} /-->
        <!-- wp:group {"className":"rockaden-card-meta","layout":{"type":"flex","flexWrap":"wrap"},"style":{"spacing":{"blockGap":"0.5rem"}}} -->
        <div class="wp-block-group rockaden-card-meta">
          <!-- wp:post-date {"style":{"color":{"text":"var(--wp--preset--color--on-surface-muted)"}},"fontSize":"small"} /-->
          <!-- wp:post-author {"showAvatar":false,"showBio":false,"byline":"","isLink":true,"fontSize":"small","style":{"color":{"text":"var(--wp--preset--color--on-surface-muted)"}}} /-->
        </div>
        <!-- /wp:group -->
        <!-- wp:post-excerpt {"excerptLength":20,"style":{"color":{"text":"var(--wp--preset--color--on-surface-muted)"}}} /-->
      </div>
      <!-- /wp:group -->
    <!-- /wp:post-template -->
  </div>
  <!-- /wp:query -->

</div>
<!-- /wp:group -->
