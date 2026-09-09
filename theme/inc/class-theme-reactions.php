<?php
/**
 * Rockaden Theme — Reactions.
 *
 * Emoji reactions on news posts: the rc_reactions post meta that holds the
 * counts, the public REST endpoint the view script talks to, and the shared
 * renderer used by the reactions block and the latest-news cards.
 *
 * Nothing about the reader is stored server-side. "One reaction per reader"
 * is remembered in the browser's localStorage by the view script, and the
 * endpoint only applies the +1/-1 deltas it is sent. The counts are therefore
 * best-effort by design — someone determined can inflate them, and nothing
 * depends on their accuracy. Site-specific, so it lives in the theme.
 *
 * @package Rockaden_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reactions: post meta, REST routes, settings accessors and the markup.
 */
class Rockaden_Theme_Reactions {

	public const META_KEY = 'rc_reactions';

	public const SCRIPT_HANDLE = 'rockaden-reactions';

	private const REST_NAMESPACE = 'rockaden/v1';

	/** Nonce action used by the view script (the standard REST nonce). */
	private const NONCE_ACTION = 'wp_rest';

	/** The emoji offered when the setting is empty or holds nothing valid. */
	public const DEFAULT_EMOJIS = '👍 👎 🙂 😂 😢 😱 ❤️';

	/** Upper bound on distinct emoji per post, so meta cannot be grown without limit. */
	private const MAX_KEYS = 20;

	/** Longest accepted emoji in bytes (a flag or skin-tone sequence is 8; ZWJ families are longer). */
	private const MAX_BYTES = 32;

	/**
	 * Register the meta and the view script. Called from functions.php on `init`.
	 */
	public static function register(): void {
		register_post_meta(
			'post',
			self::META_KEY,
			[
				'show_in_rest'  => false,
				'single'        => true,
				'type'          => 'string', // JSON object: emoji => count.
				'default'       => '{}',
				'auth_callback' => fn () => current_user_can( 'edit_posts' ),
			]
		);

		// Registered here rather than only through block.json so render() can
		// enqueue it for the latest-news cards, where no reactions block exists.
		$path = get_theme_file_path( 'blocks/reactions/view.js' );
		wp_register_script(
			self::SCRIPT_HANDLE,
			get_theme_file_uri( 'blocks/reactions/view.js' ),
			[],
			(string) filemtime( $path ),
			true
		);
	}

	/**
	 * Register REST routes. Called from functions.php on `rest_api_init`.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/reactions/(?P<id>\d+)',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ self::class, 'get_reactions' ],
					'permission_callback' => '__return_true',
				],
				[
					'methods'             => 'POST',
					'callback'            => [ self::class, 'react' ],
					'permission_callback' => [ self::class, 'verify_nonce' ],
				],
			]
		);
	}

	/*
	---------------------------------------------------------------------
	 * Settings
	 * -------------------------------------------------------------------
	*/

	/**
	 * Whether reactions are shown at all (Appearance → Rockaden).
	 *
	 * @return bool
	 */
	public static function enabled(): bool {
		return ! empty( Rockaden_Theme_Settings::get_options()['reactions_enabled'] );
	}

	/**
	 * Whether the bar also appears under each post in lists and archives.
	 *
	 * @return bool
	 */
	public static function in_lists(): bool {
		return ! empty( Rockaden_Theme_Settings::get_options()['reactions_in_lists'] );
	}

	/**
	 * The emoji offered in the picker, in the configured order.
	 *
	 * @return string[]
	 */
	public static function emojis(): array {
		$setting = (string) ( Rockaden_Theme_Settings::get_options()['reaction_emojis'] ?? '' );
		$list    = self::parse_emoji_list( $setting );
		return empty( $list ) ? self::parse_emoji_list( self::DEFAULT_EMOJIS ) : $list;
	}

	/**
	 * Turn a space-separated setting into a list of valid emoji, order kept, duplicates dropped.
	 *
	 * @param string $setting Space-separated emoji.
	 * @return string[]
	 */
	public static function parse_emoji_list( string $setting ): array {
		$out = [];
		foreach ( preg_split( '/\s+/u', trim( $setting ) ) ?: [] as $item ) {
			$emoji = self::sanitize_emoji( $item );
			if ( null !== $emoji && ! in_array( $emoji, $out, true ) ) {
				$out[] = $emoji;
			}
		}
		return $out;
	}

	/*
	---------------------------------------------------------------------
	 * Markup
	 * -------------------------------------------------------------------
	*/

	/**
	 * Print the reactions bar for a post: badges for the reactions given so far,
	 * the add trigger, and the hidden picker. Prints nothing unless reactions
	 * are enabled and the post is a published news post. The badges are not
	 * clickable — as in Signal, changing or removing your reaction goes through
	 * the picker — and a count of 1 is left out to save space.
	 *
	 * The view script refreshes the counts (the page may be cached), fetches a
	 * REST nonce, then enables the buttons. Strings the script needs are passed
	 * as data-* attributes so the no-build script stays i18n-free.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function render( int $post_id ): void {
		if ( ! self::enabled() || ! self::is_reactable( $post_id ) ) {
			return;
		}

		$emojis = self::emojis();
		$counts = self::sort_counts( self::get_counts( $post_id ), $emojis );

		wp_enqueue_script( self::SCRIPT_HANDLE );
		?>
		<div class="rockaden-reactions"
			data-post-id="<?php echo esc_attr( (string) $post_id ); ?>"
			data-rest-url="<?php echo esc_url( rest_url( self::REST_NAMESPACE . '/reactions/' . $post_id ) ); ?>"
			data-error="<?php esc_attr_e( 'Din reaktion kunde inte sparas. Försök igen.', 'rockaden-theme' ); ?>">
			<div class="rockaden-reactions__given">
				<?php foreach ( $counts as $emoji => $count ) : ?>
					<span class="rockaden-reactions__pill" data-emoji="<?php echo esc_attr( $emoji ); ?>">
						<span class="rockaden-reactions__emoji"><?php echo esc_html( $emoji ); ?></span>
						<span class="rockaden-reactions__count"><?php echo $count > 1 ? esc_html( (string) $count ) : ''; ?></span>
					</span>
				<?php endforeach; ?>
			</div>
			<button type="button" class="rockaden-reactions__add" aria-haspopup="true" aria-expanded="false" aria-label="<?php esc_attr_e( 'Lägg till reaktion', 'rockaden-theme' ); ?>" disabled>
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
					<path d="M18.3 5.2a4.8 4.8 0 0 0-6.8 0L10.6 6.1l-.9-.9a4.8 4.8 0 0 0-6.8 6.8l.9.9 6.8 6.8 6.8-6.8.9-.9a4.8 4.8 0 0 0 0-6.8z" />
					<circle cx="19" cy="5" r="4.75" fill="var(--wp--preset--color--surface, #ffffff)" stroke="none" />
					<path d="M19 2.75v4.5M16.75 5h4.5" stroke-width="2" />
				</svg>
			</button>
			<div class="rockaden-reactions__picker" role="group" aria-label="<?php esc_attr_e( 'Välj reaktion', 'rockaden-theme' ); ?>" hidden>
				<?php foreach ( $emojis as $emoji ) : ?>
					<button type="button" class="rockaden-reactions__option" data-emoji="<?php echo esc_attr( $emoji ); ?>" aria-pressed="false"><?php echo esc_html( $emoji ); ?></button>
				<?php endforeach; ?>
			</div>
			<p class="rockaden-reactions__status" role="status" aria-live="polite"></p>
		</div>
		<?php
	}

	/*
	---------------------------------------------------------------------
	 * REST
	 * -------------------------------------------------------------------
	*/

	/**
	 * Permission callback for reacting: require the standard REST nonce, which
	 * the view script fetches from the GET route. Valid for logged-out visitors
	 * too — this is CSRF protection, not an authentication gate.
	 *
	 * @param WP_REST_Request $request The incoming request.
	 * @return bool
	 */
	public static function verify_nonce( WP_REST_Request $request ): bool {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		return is_string( $nonce ) && false !== wp_verify_nonce( $nonce, self::NONCE_ACTION );
	}

	/**
	 * Current counts plus a fresh nonce. Fetched on every page view so neither
	 * can go stale behind a page cache.
	 *
	 * @param WP_REST_Request $request The incoming request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_reactions( WP_REST_Request $request ) {
		$post_id = self::resolve_post( $request );
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$response = new WP_REST_Response(
			[
				'counts' => (object) self::get_counts( $post_id ),
				'nonce'  => wp_create_nonce( self::NONCE_ACTION ),
			]
		);
		// Anonymous REST responses carry no cache headers by default; this one must never be cached.
		$response->header( 'Cache-Control', 'no-store' );
		return $response;
	}

	/**
	 * Apply a reaction change: `{ "add": "👍" }`, `{ "remove": "👍" }`, or both
	 * when the reader switches emoji. Returns the resulting counts.
	 *
	 * @param WP_REST_Request $request The incoming request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function react( WP_REST_Request $request ) {
		$post_id = self::resolve_post( $request );
		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$params = $request->get_json_params();
		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		$add    = self::read_emoji_param( $params, 'add' );
		$remove = self::read_emoji_param( $params, 'remove' );
		if ( is_wp_error( $add ) ) {
			return $add;
		}
		if ( is_wp_error( $remove ) ) {
			return $remove;
		}
		if ( null === $add && null === $remove ) {
			return new WP_Error( 'rc_reactions_empty', 'Nothing to change.', [ 'status' => 400 ] );
		}

		$counts = self::get_counts( $post_id );

		if ( null !== $remove && isset( $counts[ $remove ] ) ) {
			--$counts[ $remove ];
			if ( $counts[ $remove ] <= 0 ) {
				unset( $counts[ $remove ] );
			}
		}

		if ( null !== $add ) {
			if ( ! isset( $counts[ $add ] ) && count( $counts ) >= self::MAX_KEYS ) {
				return new WP_Error( 'rc_reactions_full', 'This post accepts no further emoji.', [ 'status' => 400 ] );
			}
			$counts[ $add ] = ( $counts[ $add ] ?? 0 ) + 1;
		}

		update_post_meta( $post_id, self::META_KEY, wp_json_encode( (object) $counts, JSON_UNESCAPED_UNICODE ) );

		return new WP_REST_Response( [ 'counts' => (object) $counts ] );
	}

	/*
	---------------------------------------------------------------------
	 * Data helpers
	 * -------------------------------------------------------------------
	*/

	/**
	 * Decode the stored counts, dropping anything malformed.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, int> Emoji => count, counts > 0 only.
	 */
	public static function get_counts( int $post_id ): array {
		$raw     = get_post_meta( $post_id, self::META_KEY, true );
		$decoded = is_string( $raw ) && '' !== $raw ? json_decode( $raw, true ) : null;
		if ( ! is_array( $decoded ) ) {
			return [];
		}

		$counts = [];
		foreach ( $decoded as $emoji => $count ) {
			$emoji = self::sanitize_emoji( (string) $emoji );
			$count = (int) $count;
			if ( null === $emoji || $count <= 0 ) {
				continue;
			}
			$counts[ $emoji ] = $count;
		}
		return $counts;
	}

	/**
	 * Order counts for display: most given first, ties in picker order,
	 * emoji no longer in the picker last. The view script sorts the same way.
	 *
	 * @param array<string, int> $counts Emoji => count.
	 * @param string[]           $emojis Picker order.
	 * @return array<string, int>
	 */
	private static function sort_counts( array $counts, array $emojis ): array {
		$order = array_flip( $emojis );
		uksort(
			$counts,
			static function ( string $a, string $b ) use ( $counts, $order ): int {
				if ( $counts[ $a ] !== $counts[ $b ] ) {
					return $counts[ $b ] <=> $counts[ $a ];
				}
				return ( $order[ $a ] ?? PHP_INT_MAX ) <=> ( $order[ $b ] ?? PHP_INT_MAX );
			}
		);
		return $counts;
	}

	/**
	 * Accept a value only if it is one printable grapheme cluster of sane size
	 * that is not plain ASCII — an emoji, possibly with a variation selector,
	 * skin tone or ZWJ sequence. The picker's list is not consulted: the server
	 * stores any well-formed emoji and the markup shows whatever was stored.
	 *
	 * @param string $value Candidate emoji.
	 * @return string|null The emoji, or null when rejected.
	 */
	public static function sanitize_emoji( string $value ): ?string {
		$value = trim( $value );
		if ( '' === $value || strlen( $value ) > self::MAX_BYTES ) {
			return null;
		}
		// Exactly one extended grapheme cluster.
		if ( 1 !== preg_match( '/^\X$/u', $value ) ) {
			return null;
		}
		// A single ASCII character is a letter or punctuation, never an emoji.
		if ( 1 === preg_match( '/^[\x00-\x7F]$/', $value ) ) {
			return null;
		}
		return $value;
	}

	/**
	 * Whether a post can carry reactions: a published news post.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private static function is_reactable( int $post_id ): bool {
		$post = $post_id > 0 ? get_post( $post_id ) : null;
		return $post instanceof WP_Post && 'post' === $post->post_type && 'publish' === $post->post_status;
	}

	/**
	 * Resolve the `id` route parameter to a published news post.
	 *
	 * @param WP_REST_Request $request The incoming request.
	 * @return int|WP_Error
	 */
	private static function resolve_post( WP_REST_Request $request ) {
		$post_id = (int) $request->get_param( 'id' );
		// With reactions switched off the routes vanish too, so the setting is a real off-switch.
		if ( ! self::enabled() || ! self::is_reactable( $post_id ) ) {
			return new WP_Error( 'rc_reactions_not_found', 'Post not found.', [ 'status' => 404 ] );
		}
		return $post_id;
	}

	/**
	 * Read an optional emoji parameter: absent/empty => null, present but invalid => 400.
	 *
	 * @param array<string, mixed> $params Request parameters.
	 * @param string               $key    Parameter name.
	 * @return string|null|WP_Error
	 */
	private static function read_emoji_param( array $params, string $key ) {
		if ( ! isset( $params[ $key ] ) || '' === $params[ $key ] ) {
			return null;
		}
		$emoji = is_string( $params[ $key ] ) ? self::sanitize_emoji( $params[ $key ] ) : null;
		if ( null === $emoji ) {
			return new WP_Error( 'rc_reactions_invalid', 'Not a valid emoji.', [ 'status' => 400 ] );
		}
		return $emoji;
	}
}
