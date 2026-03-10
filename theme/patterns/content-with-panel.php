<?php
/**
 * Title: Content with Info Panel
 * Slug: rockaden-theme/content-with-panel
 * Categories: rockaden
 * Description: Two-column layout (70/30) with main content and a right info panel.
 */
?>
<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"2rem"}}}} -->
<div class="wp-block-columns">

  <!-- wp:column {"width":"70%"} -->
  <div class="wp-block-column" style="flex-basis:70%">
    <!-- wp:paragraph -->
    <p>Add your main content here.</p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:column -->

  <!-- wp:column {"width":"30%","className":"rockaden-info-panel"} -->
  <div class="wp-block-column rockaden-info-panel" style="flex-basis:30%">
    <!-- wp:heading {"level":4} -->
    <h4 class="wp-block-heading">Info</h4>
    <!-- /wp:heading -->

    <!-- wp:paragraph -->
    <p>Add supplementary information, links, or details here.</p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:column -->

</div>
<!-- /wp:columns -->
