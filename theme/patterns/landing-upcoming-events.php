<?php
/**
 * Title: Landing Upcoming Events
 * Slug: rockaden-theme/landing-upcoming-events
 * Categories: rockaden
 * Description: Heading + the upcoming-events block (next N occurrences).
 */
?>
<!-- wp:group {"align":"wide","className":"rockaden-landing-upcoming","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide rockaden-landing-upcoming">

  <!-- wp:heading {"level":2,"className":"rockaden-landing-upcoming__heading"} -->
  <h2 class="wp-block-heading rockaden-landing-upcoming__heading"><?php esc_html_e( 'Kommande aktiviteter', 'rockaden-theme' ); ?></h2>
  <!-- /wp:heading -->

  <!-- wp:rockaden/upcoming-events {"count":4} /-->

</div>
<!-- /wp:group -->
