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
 */

defined('ABSPATH') || exit;

class Rockaden_Theme_I18n {

	public const COOKIE = 'rc_locale';

	public const DEFAULT_LOCALE = 'sv_SE';

	/**
	 * Locales the visitor toggle may select.
	 *
	 * @var string[]
	 */
	private const ALLOWED = ['sv_SE', 'en_US'];

	/**
	 * Register hooks. Called from functions.php.
	 */
	public static function register(): void {
		add_filter('locale', [self::class, 'filter_locale']);
		add_filter('language_attributes', [self::class, 'add_lang_attribute']);
		// Priority 1 — block patterns register on `init` (priority 10) and
		// have their PHP rendered with gettext applied at that time, so the
		// textdomain must be loaded first.
		add_action('init', [self::class, 'load_textdomain'], 1);
	}

	/**
	 * Override the front-end locale from the rc_locale cookie.
	 *
	 * @param string $locale The locale WordPress determined.
	 * @return string
	 */
	public static function filter_locale(string $locale): string {
		if (is_admin()) {
			return $locale;
		}
		return self::current_locale();
	}

	/**
	 * The resolved front-end locale (cookie value if valid, else default).
	 */
	public static function current_locale(): string {
		$cookie = isset($_COOKIE[self::COOKIE]) ? sanitize_text_field(wp_unslash($_COOKIE[self::COOKIE])) : '';
		if (in_array($cookie, self::ALLOWED, true)) {
			return $cookie;
		}
		return self::DEFAULT_LOCALE;
	}

	/**
	 * Short language code ("sv" / "en") for the active front-end locale.
	 * Used for the data-lang attribute the docs CSS keys off.
	 */
	public static function current_lang(): string {
		return str_starts_with(self::current_locale(), 'en') ? 'en' : 'sv';
	}

	/**
	 * Add data-lang to the <html> tag so CSS-based dual-language blocks
	 * (e.g. documentation) show the right language without any JS.
	 *
	 * @param string $output The language attributes string.
	 * @return string
	 */
	public static function add_lang_attribute(string $output): string {
		if (is_admin()) {
			return $output;
		}
		return $output . ' data-lang="' . esc_attr(self::current_lang()) . '"';
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
		$mofile = get_theme_file_path('languages/rockaden-theme-' . $locale . '.mo');
		if (file_exists($mofile)) {
			load_textdomain('rockaden-theme', $mofile, $locale);
		}
	}
}
