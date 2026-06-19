<?php
/**
 * Rockaden Theme — smarter post excerpts.
 *
 * The news/archive lists use the core Post Excerpt block, which always appends
 * the "Läs mer" link (and an ellipsis) even when the post is barely longer than
 * the excerpt. That makes "Läs mer" reveal almost nothing, and the cut can land
 * on a stray word or punctuation mark.
 *
 * This makes the excerpt content-aware:
 *  - If the post fits within the excerpt length (+ a small grace), show the full
 *    text with no ellipsis and no "Läs mer" — the heading already links to it.
 *  - Otherwise trim on a word boundary, strip trailing punctuation, add "…", and
 *    keep the "Läs mer" link.
 * Manual excerpts (author-written teasers) are left untouched and keep the link.
 */

defined('ABSPATH') || exit;

class Rockaden_Theme_Excerpt {

	/** Extra words allowed before we bother truncating. */
	private const GRACE = 10;

	/** Fallback excerpt length when none is filtered/set. */
	private const DEFAULT_LENGTH = 55;

	/**
	 * Register hooks. Called from functions.php.
	 */
	public static function register(): void {
		add_filter('get_the_excerpt', [self::class, 'smart_excerpt'], 11, 2);
		add_filter('render_block_core/post-excerpt', [self::class, 'maybe_remove_more_link'], 10, 3);
	}

	/**
	 * Content-aware excerpt: full text when it (almost) fits, otherwise a clean
	 * trimmed excerpt ending in "…". Auto excerpts only — manual excerpts pass
	 * through unchanged.
	 *
	 * @param string   $excerpt The current excerpt.
	 * @param \WP_Post $post    The post.
	 * @return string
	 */
	public static function smart_excerpt($excerpt, $post) {
		if (! $post instanceof \WP_Post || has_excerpt($post)) {
			return $excerpt;
		}

		$length = self::length();
		$words  = self::content_words($post);
		$count  = count($words);

		if ($count <= $length + self::GRACE) {
			return implode(' ', $words);
		}

		$trimmed = implode(' ', array_slice($words, 0, $length));
		// Strip trailing whitespace/punctuation (ASCII only — rtrim is byte-based)
		// so the "…" doesn't sit after a stray comma or period.
		$trimmed = rtrim($trimmed, " \t\n\r\0\x0B.,;:!?-");

		return $trimmed . '…';
	}

	/**
	 * Drop the Post Excerpt block's "more" link when the post wasn't truncated.
	 *
	 * @param string    $content  Rendered block HTML.
	 * @param array     $block    Parsed block.
	 * @param \WP_Block $instance Block instance (for context).
	 * @return string
	 */
	public static function maybe_remove_more_link($content, $block, $instance): string {
		$post_id = $instance->context['postId'] ?? get_the_ID();
		if (! $post_id) {
			return $content;
		}

		$length = isset($block['attrs']['excerptLength'])
			? (int) $block['attrs']['excerptLength']
			: self::DEFAULT_LENGTH;

		if (self::is_truncated(get_post($post_id), $length)) {
			return $content;
		}

		// Not truncated — remove the "Läs mer" link (the heading already links).
		return preg_replace(
			'#\s*<a\b[^>]*wp-block-post-excerpt__more-link[^>]*>.*?</a>#s',
			'',
			$content
		);
	}

	/**
	 * Whether the post's excerpt is meaningfully truncated.
	 *
	 * @param \WP_Post|null $post   The post.
	 * @param int           $length Excerpt length in words.
	 * @return bool
	 */
	private static function is_truncated($post, int $length): bool {
		if (! $post instanceof \WP_Post) {
			return false;
		}
		if (has_excerpt($post)) {
			return true; // Manual teaser — assume there's a fuller article behind it.
		}
		return count(self::content_words($post)) > $length + self::GRACE;
	}

	/**
	 * The post's content as a flat array of words, mirroring how WordPress
	 * derives an auto excerpt (blocks/shortcodes/tags stripped).
	 *
	 * @param \WP_Post $post The post.
	 * @return array<int, string>
	 */
	private static function content_words(\WP_Post $post): array {
		$text = get_the_content('', false, $post);
		$text = excerpt_remove_blocks($text);
		$text = strip_shortcodes($text);
		$text = wp_strip_all_tags($text);
		$text = trim(preg_replace('/\s+/u', ' ', $text));

		return '' === $text ? [] : explode(' ', $text);
	}

	/**
	 * Effective excerpt length (respects the active excerpt_length filter, which
	 * the Post Excerpt block sets to its own value while generating).
	 *
	 * @return int
	 */
	private static function length(): int {
		return (int) apply_filters('excerpt_length', self::DEFAULT_LENGTH);
	}
}
