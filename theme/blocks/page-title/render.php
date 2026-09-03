<?php
/**
 * Page Title block — server render.
 *
 * Renders wp:post-title unless the per-page rc_hide_title meta is set.
 *
 * @package Rockaden_Theme
 */

defined( 'ABSPATH' ) || exit;

// Prefixed to avoid shadowing WordPress's own $post_id global — block render
// files are included in a shared scope.
$rc_post_id = get_the_ID();
$rc_hide    = $rc_post_id ? get_post_meta( $rc_post_id, 'rc_hide_title', true ) : '';

if ( $rc_hide ) {
	return;
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block markup
echo do_blocks( '<!-- wp:post-title {"level":1} /-->' );
