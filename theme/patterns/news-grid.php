<?php
/**
 * Title: News Grid
 * Slug: rockaden-theme/news-grid
 * Categories: rockaden
 * Description: A grid of recent news posts.
 */
?>
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
  <!-- wp:heading {"level":2} -->
  <h2 class="wp-block-heading"><?php esc_html_e('Recent News', 'rockaden-theme'); ?></h2>
  <!-- /wp:heading -->

  <!-- wp:query {"queryId":10,"query":{"perPage":6,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date"},"displayLayout":{"type":"flex","columns":3}} -->
  <div class="wp-block-query">
    <!-- wp:post-template -->
      <!-- wp:group {"style":{"border":{"width":"1px","color":"var(--wp--preset--color--surface-dim)","radius":"8px"},"spacing":{"padding":{"top":"1.5rem","bottom":"1.5rem","left":"1.5rem","right":"1.5rem"}}},"layout":{"type":"flex","orientation":"vertical"}} -->
      <div class="wp-block-group" style="border-width:1px;border-color:var(--wp--preset--color--surface-dim);border-radius:8px;padding:1.5rem">
        <!-- wp:post-title {"isLink":true,"fontSize":"large"} /-->
        <!-- wp:post-date {"fontSize":"small"} /-->
        <!-- wp:post-excerpt {"excerptLength":20} /-->
      </div>
      <!-- /wp:group -->
    <!-- /wp:post-template -->
  </div>
  <!-- /wp:query -->
</div>
<!-- /wp:group -->
