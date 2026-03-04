<?php
/**
 * Title: Page with Sidebar and Info Panel
 * Slug: rockaden-theme/page-three-column
 * Categories: rockaden
 * Description: Three-column layout with sidebar navigation, content area, and info panel.
 */
?>
<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"2rem"}}}} -->
<div class="wp-block-columns">

  <!-- wp:column {"width":"20%","className":"rockaden-sidebar-nav"} -->
  <div class="wp-block-column rockaden-sidebar-nav" style="flex-basis:20%">
    <!-- wp:navigation {"orientation":"vertical","layout":{"type":"flex","orientation":"vertical"}} -->
      <!-- wp:navigation-link {"label":"Overview","url":"#","kind":"custom"} /-->
      <!-- wp:navigation-link {"label":"Section One","url":"#","kind":"custom"} /-->
      <!-- wp:navigation-link {"label":"Section Two","url":"#","kind":"custom"} /-->
    <!-- /wp:navigation -->
  </div>
  <!-- /wp:column -->

  <!-- wp:column {"width":"55%"} -->
  <div class="wp-block-column" style="flex-basis:55%">
    <!-- wp:heading {"level":2,"style":{"typography":{"fontWeight":"300","letterSpacing":"0.025em"}}} -->
    <h2 class="wp-block-heading" style="font-weight:300;letter-spacing:0.025em">Page Title</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>Main content area. This three-column layout provides sidebar navigation, a content area, and an info panel. On mobile devices, all columns stack vertically.</p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:column -->

  <!-- wp:column {"width":"25%"} -->
  <div class="wp-block-column" style="flex-basis:25%">
    <!-- wp:group {"className":"rockaden-info-panel","layout":{"type":"flex","orientation":"vertical"}} -->
    <div class="wp-block-group rockaden-info-panel">
      <!-- wp:heading {"level":3,"style":{"typography":{"fontWeight":"300","letterSpacing":"0.025em","fontSize":"1.1rem"}}} -->
      <h3 class="wp-block-heading" style="font-weight:300;letter-spacing:0.025em;font-size:1.1rem">Upcoming Events</h3>
      <!-- /wp:heading -->

      <!-- wp:list {"style":{"typography":{"fontSize":"0.9rem"},"color":{"text":"var(--wp--preset--color--on-surface-muted)"}}} -->
      <ul style="font-size:0.9rem;color:var(--wp--preset--color--on-surface-muted)">
        <li>Event placeholder</li>
        <li>Event placeholder</li>
        <li>Event placeholder</li>
      </ul>
      <!-- /wp:list -->
    </div>
    <!-- /wp:group -->
  </div>
  <!-- /wp:column -->

</div>
<!-- /wp:columns -->
