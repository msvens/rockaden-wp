<?php
/**
 * Title: Page with sidebar
 * Slug: rockaden-theme/page-with-sidebar
 * Categories: rockaden
 * Description: Two columns — page content on the left, a sidebar of cards on the right.
 *
 * A starting point rather than a layout the theme owns. Inserting it drops
 * ordinary blocks into the page, which the editor can then widen, reorder or
 * delete like any others — and, being page content, the sidebar is visible while
 * editing. A page *template* would put the columns in the template layer, where
 * the page editor cannot render them.
 *
 * The 75/25 split and the 2rem gap mirror single.html, so a page with a sidebar
 * sits at the same measure as a news item. Only the wide column carries an
 * explicit width: giving both an explicit percentage makes Gutenberg set
 * flex-grow to 0 on each, which stops them filling the container once the gap is
 * taken into account. Same reasoning as landing-why.
 *
 * @package Rockaden_Theme
 */

?>
<!-- wp:columns {"className":"rc-content-columns","style":{"spacing":{"blockGap":{"left":"2rem"}}}} -->
<div class="wp-block-columns rc-content-columns">
<!-- wp:column {"width":"75%"} -->
<div class="wp-block-column" style="flex-basis:75%">
<!-- wp:paragraph -->
<p></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->

<!-- wp:column {"className":"rc-sidebar-column"} -->
<div class="wp-block-column rc-sidebar-column">
<!-- wp:rockaden/sidebar -->
<!-- wp:rockaden/sidebar-card {"title":"<?php echo esc_attr_x( 'Title', 'sidebar card placeholder', 'rockaden-theme' ); ?>"} /-->
<!-- /wp:rockaden/sidebar -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
