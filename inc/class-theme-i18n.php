<?php
/**
 * Rockaden Theme i18n / locale.
 *
 * Drives a visitor-facing language switch through a real WordPress locale:
 * a cookie (`rc_locale`) chooses the front-end locale, gettext then resolves
 * every translated string (theme + plugin) for that request. The visitor
 * toggle sets the cookie and reloads — no client-side text swapping.
 *
 * Front-end only: the admin keeps the user's / site's own locale.
 *
 * @package Rockaden_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cookie-driven visitor locale, applied through WordPress's own gettext pipeline.
 */
class Rockaden_Theme_I18n {

	public const COOKIE = 'rc_locale';

	public const DEFAULT_LOCALE = 'sv_SE';

	/**
	 * Query parameter carried by the switcher links.
	 */
	public const SWITCH_QUERY_VAR = 'rc_setlang';

	/**
	 * Short language code to WordPress locale, for the switch handler.
	 *
	 * Deliberately the same pair of locales as ALLOWED, which guards the read
	 * side. Keep the two in step.
	 *
	 * @var array<string, string>
	 */
	private const LANG_LOCALES = [
		'sv' => 'sv_SE',
		'en' => 'en_US',
	];

	/**
	 * Locales the visitor toggle may select.
	 *
	 * @var string[]
	 */
	private const ALLOWED = [ 'sv_SE', 'en_US' ];

	/**
	 * Register hooks. Called from functions.php.
	 */
	public static function register(): void {
		add_filter( 'locale', [ self::class, 'filter_locale' ] );
		add_filter( 'language_attributes', [ self::class, 'add_lang_attribute' ] );
		// Priority 1 — block patterns register on `init` (priority 10) and
		// have their PHP rendered with gettext applied at that time, so the
		// textdomain must be loaded first.
		add_action( 'init', [ self::class, 'load_textdomain' ], 1 );

		// The switcher links carry ?rc_setlang=. Reading it as a query var
		// rather than from $_GET keeps WordPress.Security.NonceVerification
		// quiet without a suppression.
		add_filter( 'query_vars', [ self::class, 'register_query_var' ] );
		// template_redirect is front-end only, so this needs no admin, REST,
		// ajax or cron guards, and headers are set but not yet flushed so
		// setcookie() is still valid. Priority 0 keeps it ahead of core's
		// redirect_canonical, so a switch is never two hops.
		add_action( 'template_redirect', [ self::class, 'maybe_switch_language' ], 0 );
	}

	/**
	 * Make the switch parameter readable through get_query_var().
	 *
	 * @param array<int, string> $vars Public query vars.
	 * @return array<int, string>
	 */
	public static function register_query_var( array $vars ): array {
		$vars[] = self::SWITCH_QUERY_VAR;
		return $vars;
	}

	/**
	 * Act on a click of the language switcher.
	 *
	 * Sets the locale cookie, then sends the visitor to the paired page when
	 * the current one has a counterpart configured in the nav settings, and
	 * back to the same URL when it does not. Without this the switch reloaded
	 * the Swedish page with an English menu, so actually reaching the English
	 * page took a second click on the same menu item.
	 */
	public static function maybe_switch_language(): void {
		$requested = get_query_var( self::SWITCH_QUERY_VAR );
		if ( ! is_string( $requested ) || '' === $requested ) {
			return;
		}

		$locale = self::LANG_LOCALES[ $requested ] ?? null;
		if ( null === $locale ) {
			// Unrecognised value: ignore it rather than act on it.
			return;
		}

		self::set_locale_cookie( $locale );

		$request = isset( $_SERVER['REQUEST_URI'] )
			? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) )
			: '/';

		// Dropping the switch parameter is what makes a redirect loop
		// impossible: the destination can never re-enter this handler. Every
		// other query argument is kept, so campaign tags survive the hop.
		$clean = remove_query_arg( self::SWITCH_QUERY_VAR, $request );
		$path  = self::normalize_path( $clean );
		$query = wp_parse_url( $clean, PHP_URL_QUERY );
		$query = is_string( $query ) ? $query : '';

		$paired = self::paired_path( $path, $requested );
		$target = home_url( null !== $paired ? $paired : ( '' !== $path ? $path : '/' ) );

		if ( '' !== $query ) {
			$target .= '?' . $query;
		}

		// The response varies by the cookie being set, so it must not be
		// cached; 302 and never 301, because a cached permanent redirect would
		// stop the switch URL ever reaching the server again.
		nocache_headers();
		wp_safe_redirect( $target, 302, 'Rockaden language switch' );
		exit;
	}

	/**
	 * Persist the chosen locale.
	 *
	 * The flags must match the ones language.js used to write, or a browser
	 * ends up holding two cookies at different paths and PHP reads whichever
	 * it happens to send first. Not HttpOnly on purpose: the switcher reads the
	 * value to mark the active language, and it is not a secret.
	 *
	 * @param string $locale A validated WordPress locale.
	 */
	private static function set_locale_cookie( string $locale ): void {
		setcookie(
			self::COOKIE,
			$locale,
			[
				'expires'  => time() + YEAR_IN_SECONDS,
				'path'     => '/',
				'secure'   => is_ssl(),
				'httponly' => false,
				'samesite' => 'Lax',
			]
		);
	}

	/**
	 * Reduce a stored or requested URL to a comparable site-relative path.
	 *
	 * Both sides of every comparison go through this, which is what lets
	 * '/kalender' (how defaults() stores it) match '/kalender/' (how the page
	 * picker stores it) and match the request URI as well.
	 *
	 * Returns an empty string for anything that cannot be a page on this site,
	 * such as another host or a mailto: link, so a custom external URL in the
	 * nav can never be matched or become a redirect target.
	 *
	 * @param string $url Stored nav URL or request URI.
	 */
	private static function normalize_path( string $url ): string {
		$url = trim( $url );
		if ( '' === $url ) {
			return '';
		}

		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) ) {
			return '';
		}

		if ( isset( $parts['scheme'] ) && ! in_array( $parts['scheme'], [ 'http', 'https' ], true ) ) {
			return '';
		}

		if ( isset( $parts['host'] ) ) {
			$home_host = (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST );
			if ( strtolower( $parts['host'] ) !== strtolower( $home_host ) ) {
				return '';
			}
		}

		$path = (string) ( $parts['path'] ?? '' );
		if ( '' === $path ) {
			return '';
		}

		// A request URI and a stored slug can disagree on hex case for a, a, o
		// with diacritics, so compare decoded.
		$path = rawurldecode( $path );
		$path = '/' . ltrim( $path, '/' );

		// Subdirectory installs: a stored value may or may not carry the site
		// path, so strip it from both sides and let home_url() put it back.
		$home_path = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$home_path = trailingslashit( '' !== $home_path ? $home_path : '/' );
		if ( '/' !== $home_path && str_starts_with( $path, $home_path ) ) {
			$path = '/' . ltrim( substr( $path, strlen( $home_path ) ), '/' );
		}

		return trailingslashit( $path );
	}

	/**
	 * The counterpart path for the page being viewed, or null when none exists.
	 *
	 * Looks the path up in both columns rather than trusting the requested
	 * language to say which side we are on. Someone who deep-linked to an
	 * English page while holding a Swedish cookie then behaves sensibly: no
	 * match, so they stay where they are and only the labels change.
	 *
	 * @param string $path Normalised path of the current page.
	 * @param string $lang Language being switched to, 'sv' or 'en'.
	 */
	private static function paired_path( string $path, string $lang ): ?string {
		// filter_front_page() already swaps the home page by ID. Redirecting
		// here as well would be two mechanisms fighting over '/'.
		if ( '' === $path || '/' === $path ) {
			return null;
		}

		foreach ( Rockaden_Theme_Settings::nav_url_pairs() as $pair ) {
			$sv = self::normalize_path( $pair['sv'] );
			$en = self::normalize_path( $pair['en'] );

			// Nothing to move between: an external URL dropped by
			// normalisation, the two sides equal, or the front page.
			if ( '' === $sv || '' === $en || $sv === $en || '/' === $sv || '/' === $en ) {
				continue;
			}

			$target = null;
			if ( 'en' === $lang && $path === $sv ) {
				$target = $en;
			} elseif ( 'sv' === $lang && $path === $en ) {
				$target = $sv;
			}

			if ( null !== $target ) {
				return self::is_viewable( $target ) ? $target : null;
			}
		}

		return null;
	}

	/**
	 * Whether a mapped path still resolves to something a visitor may see.
	 *
	 * Mirrors the guard filter_front_page() carries, which was added after a
	 * trashed English page turned the front page into a 404.
	 *
	 * Two lookups are needed because url_to_postid() only matches *published*
	 * posts: it returns 0 for a draft and for a trashed page alike, so trusting
	 * it alone would happily redirect a visitor onto a 404. get_page_by_path()
	 * finds a page whatever its status, which catches the draft case; trashing
	 * additionally frees the slug, so nothing resolves at all.
	 *
	 * Hence the final `false`: a path nothing resolves to would 404, and leaving
	 * the visitor where they are is strictly better than that. The deliberate
	 * cost is that a pair pointing at something which is not a page, such as a
	 * term archive, stops redirecting. Staying put is a graceful failure; a 404
	 * is not.
	 *
	 * @param string $path Site-relative path.
	 */
	private static function is_viewable( string $path ): bool {
		$post_id = url_to_postid( home_url( $path ) );
		if ( $post_id > 0 ) {
			return 'publish' === get_post_status( $post_id );
		}

		$page = get_page_by_path( trim( $path, '/' ) );
		if ( $page instanceof WP_Post ) {
			return 'publish' === $page->post_status;
		}

		return false;
	}

	/**
	 * Override the front-end locale from the rc_locale cookie.
	 *
	 * @param string $locale The locale WordPress determined.
	 * @return string
	 */
	public static function filter_locale( string $locale ): string {
		if ( is_admin() ) {
			return $locale;
		}
		return self::current_locale();
	}

	/**
	 * The resolved front-end locale (cookie value if valid, else default).
	 */
	public static function current_locale(): string {
		$cookie = isset( $_COOKIE[ self::COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE ] ) ) : '';
		if ( in_array( $cookie, self::ALLOWED, true ) ) {
			return $cookie;
		}
		return self::DEFAULT_LOCALE;
	}

	/**
	 * Short language code ("sv" / "en") for the active front-end locale.
	 * Used for the data-lang attribute the docs CSS keys off.
	 */
	public static function current_lang(): string {
		return str_starts_with( self::current_locale(), 'en' ) ? 'en' : 'sv';
	}

	/**
	 * Add data-lang to the <html> tag so CSS-based dual-language blocks
	 * (e.g. documentation) show the right language without any JS.
	 *
	 * @param string $output The language attributes string.
	 * @return string
	 */
	public static function add_lang_attribute( string $output ): string {
		if ( is_admin() ) {
			return $output;
		}
		return $output . ' data-lang="' . esc_attr( self::current_lang() ) . '"';
	}

	/**
	 * Load the theme's translations (Swedish-source msgids, en_US.mo for English).
	 *
	 * We use load_textdomain() directly with an explicit path because
	 * load_theme_textdomain() in WP 6.5+ doesn't reliably populate the
	 * translation controller for non-global theme language directories.
	 */
	public static function load_textdomain(): void {
		$locale = determine_locale();
		$mofile = get_theme_file_path( 'languages/rockaden-theme-' . $locale . '.mo' );
		if ( file_exists( $mofile ) ) {
			load_textdomain( 'rockaden-theme', $mofile, $locale );
		}
	}
}
