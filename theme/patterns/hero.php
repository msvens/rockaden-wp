<?php
/**
 * Title: Hero
 * Slug: rockaden-theme/hero
 * Categories: rockaden
 * Description: Clean text hero with heading and subtitle.
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"4rem","bottom":"4rem","left":"2rem","right":"2rem"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:4rem;padding-bottom:4rem;padding-left:2rem;padding-right:2rem">

  <!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"3rem","fontWeight":"300","letterSpacing":"0.025em"}}} -->
  <h1 class="wp-block-heading has-text-align-center" style="font-size:3rem;font-weight:300;letter-spacing:0.025em"><?php esc_html_e('Welcome to SK Rockaden', 'rockaden-theme'); ?></h1>
  <!-- /wp:heading -->

  <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"1.125rem"},"color":{"text":"var(--wp--preset--color--on-surface-muted)"},"spacing":{"margin":{"top":"1rem"}}}} -->
  <p class="has-text-align-center" style="font-size:1.125rem;color:var(--wp--preset--color--on-surface-muted);margin-top:1rem"><?php esc_html_e('One of Stockholm\'s oldest and most active chess clubs', 'rockaden-theme'); ?></p>
  <!-- /wp:paragraph -->

</div>
<!-- /wp:group -->
