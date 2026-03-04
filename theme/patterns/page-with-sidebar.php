<?php
/**
 * Title: Page with Sidebar Navigation
 * Slug: rockaden-theme/page-with-sidebar
 * Categories: rockaden
 * Description: Two-column layout with left sidebar navigation and content area.
 */
?>
<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"2rem"}}}} -->
<div class="wp-block-columns">

  <!-- wp:column {"width":"25%","className":"rockaden-sidebar-nav"} -->
  <div class="wp-block-column rockaden-sidebar-nav" style="flex-basis:25%">
    <!-- wp:navigation {"orientation":"vertical","layout":{"type":"flex","orientation":"vertical"}} -->
      <!-- wp:navigation-link {"label":"Overview","url":"#","kind":"custom"} /-->
      <!-- wp:navigation-link {"label":"Section One","url":"#","kind":"custom"} /-->
      <!-- wp:navigation-link {"label":"Section Two","url":"#","kind":"custom"} /-->
      <!-- wp:navigation-link {"label":"Section Three","url":"#","kind":"custom"} /-->
    <!-- /wp:navigation -->
  </div>
  <!-- /wp:column -->

  <!-- wp:column {"width":"75%"} -->
  <div class="wp-block-column" style="flex-basis:75%">
    <!-- wp:heading {"level":2,"style":{"typography":{"fontWeight":"300","letterSpacing":"0.025em"}}} -->
    <h2 class="wp-block-heading" style="font-weight:300;letter-spacing:0.025em">Page Title</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>Add your content here. This layout provides a sidebar navigation on the left and a main content area on the right. On mobile devices, the columns will stack vertically.</p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:column -->

</div>
<!-- /wp:columns -->
