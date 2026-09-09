<?php
/**
 * Server-side render for the Reactions block.
 *
 * Thin wrapper: the markup lives in Rockaden_Theme_Reactions::render() so the
 * latest-news cards can show the same bar without a block. Inside a query
 * loop the post comes from block context; on a single post from the loop.
 *
 * @package Rockaden_Theme
 *
 * @var array<string, mixed> $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

// In the news list and archives the bar is optional (Appearance → Rockaden);
// on a single post it always shows while reactions are enabled.
if ( ! is_singular() && ! Rockaden_Theme_Reactions::in_lists() ) {
	return;
}

$reaction_post_id = (int) ( $block->context['postId'] ?? get_the_ID() );

Rockaden_Theme_Reactions::render( $reaction_post_id );
