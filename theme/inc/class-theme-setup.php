<?php
/**
 * Rockaden Theme Setup.
 *
 * Creates stub pages and default settings on theme activation.
 *
 * @package Rockaden_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Theme activation: stub pages, the landing page, and default settings.
 */
class Rockaden_Theme_Setup {

	/**
	 * Pages to create on activation: title => slug.
	 */
	private const STUB_PAGES = [
		'Nyheter'     => 'nyheter',
		'Kalender'    => 'kalender',
		'Träning'     => 'training',
		'Turneringar' => 'tournaments',
		'Handla'      => 'shop',
		'Medlemmar'   => 'medlemmar',
		'Om Rockaden' => 'om-rockaden',
		'Kontakt'     => 'kontakt',
		'Bli medlem'  => 'bli-medlem',
	];

	/**
	 * Locales the landing page is seeded in. Swedish is the msgid source, so it
	 * needs no catalogue; English has one under theme/languages/.
	 */
	private const LOCALE_SV = 'sv_SE';
	private const LOCALE_EN = 'en_US';

	/**
	 * Run on theme activation (after_switch_theme).
	 */
	public static function activate(): void {
		self::create_stub_pages();
		self::create_landing_page();
		self::create_landing_page_en();
		self::set_default_options();

		// The shop CPT is registered on `init`, which hasn't run yet during
		// after_switch_theme. Register it here too so its /shop/ rewrite rules
		// are present when we flush.
		Rockaden_Theme_Shop::register_post_type();
		flush_rewrite_rules();
	}

	/**
	 * Create the "Hem" landing page (if absent), pre-fill with landing patterns,
	 * assign the page-landing template, and set it as the static front page.
	 *
	 * Idempotent: every assertion is guarded so this can run on every deploy
	 * (e.g. wired into rockaden-update.sh) without overwriting admin choices.
	 *  - Page is only created when missing.
	 *  - Template is only assigned to a Hem page that has no template yet
	 *    (so an admin who deliberately changed it isn't reverted).
	 *  - show_on_front / page_on_front are only set on a fresh install where
	 *    the admin hasn't picked a different home-page setup.
	 */
	private static function create_landing_page(): void {
		$page = get_page_by_path( 'hem' );

		if ( ! $page ) {
			$content = self::build_landing_content( self::LOCALE_SV );

			$page_id = wp_insert_post(
				[
					'post_title'   => 'Hem',
					'post_name'    => 'hem',
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_content' => $content,
					'meta_input'   => [
						'_wp_page_template' => 'page-landing',
					],
				]
			);

			// wp_insert_post() only returns WP_Error when called with
			// $wp_error = true; otherwise failure is 0.
			if ( ! $page_id ) {
				return;
			}
		} else {
			$page_id = $page->ID;
			// Only assign the landing template if the page has none yet —
			// don't revert an admin's deliberate template change.
			if ( get_post_meta( $page_id, '_wp_page_template', true ) === '' ) {
				update_post_meta( $page_id, '_wp_page_template', 'page-landing' );
			}
		}

		// Only set front-page mode if admin hasn't chosen something else
		// (e.g. left the default "Latest posts" or pointed at a different page).
		if ( get_option( 'show_on_front' ) !== 'page' ) {
			update_option( 'show_on_front', 'page' );
		}

		// Only set page_on_front when nothing is set (0 = unset).
		if ( (int) get_option( 'page_on_front' ) === 0 ) {
			update_option( 'page_on_front', (int) $page_id );
		}

		// Wire the Nyheter stub page as WP's "Posts page" so /nyheter/ serves
		// the post archive (using home.html / index.html). Asserted on every
		// activate() run — page_for_posts is theme-owned routing, not an
		// editor-facing choice, so no guard against existing values. This is
		// also self-healing if the option ever points at a deleted/orphan page.
		$nyheter = get_page_by_path( 'nyheter' );
		if ( $nyheter ) {
			update_option( 'page_for_posts', (int) $nyheter->ID );
		}
	}

	/**
	 * Create the English "Home" landing page, mirroring the Swedish one.
	 *
	 * Same content, same template, same design — in English. Without it the
	 * English home-page setting has nothing to point at, and whoever sets the
	 * site up has to hand-build a second landing page to get there.
	 *
	 * Idempotent on the same terms as the Swedish page: created only when
	 * absent, template only assigned when the page has none, and the
	 * front_page_en setting only written when it is still unset, so an admin's
	 * own choice is never reverted.
	 */
	private static function create_landing_page_en(): void {
		$options = get_option( Rockaden_Theme_Settings::OPTION_KEY, [] );
		$chosen  = is_array( $options ) ? (int) ( $options['front_page_en'] ?? 0 ) : 0;

		// An admin who has already nominated an English home page has answered
		// this question; do not second-guess them with a page of our own.
		if ( $chosen > 0 && 'page' === get_post_type( $chosen ) ) {
			return;
		}

		// The slug is a guess, and on a site that already has a page called
		// "home" it is probably somebody else's page about something else.
		// Adopting it — assigning our template, making it the English front
		// page — would be presumptuous, so leave it alone entirely and let the
		// admin point the setting wherever they mean.
		if ( get_page_by_path( 'home' ) ) {
			return;
		}

		$page_id = wp_insert_post(
			[
				'post_title'   => 'Home',
				'post_name'    => 'home',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => self::build_landing_content( self::LOCALE_EN ),
				'meta_input'   => [
					'_wp_page_template' => 'page-landing',
				],
			]
		);

		// wp_insert_post() returns 0 on failure unless asked for a WP_Error.
		if ( ! $page_id ) {
			return;
		}

		if ( ! is_array( $options ) ) {
			$options = [];
		}
		$options['front_page_en'] = (int) $page_id;
		update_option( Rockaden_Theme_Settings::OPTION_KEY, $options );
	}

	/**
	 * Render the landing patterns into block markup for one locale.
	 *
	 * The patterns' PHP is included directly rather than fetched from
	 * WP_Block_Patterns_Registry, because the registry evaluates a pattern file
	 * once and caches the result (class-wp-block-patterns-registry.php unsets
	 * filePath after the first read). Asking it for the same pattern under a
	 * second locale would return the first locale's text.
	 *
	 * switch_to_locale() alone is not enough either: the theme loads its own
	 * textdomain with load_textdomain() and an explicit path, so it does not
	 * follow WordPress's locale switch. The catalogue is unloaded and reloaded
	 * by hand — verified, without it every locale renders Swedish.
	 *
	 * @param string $locale Locale to render under, e.g. 'sv_SE' or 'en_US'.
	 * @return string Resolved block markup.
	 */
	private static function render_landing_for_locale( string $locale ): string {
		$files = [
			get_theme_file_path( 'patterns/landing-hero.php' ),
			get_theme_file_path( 'patterns/landing-why.php' ),
			get_theme_file_path( 'patterns/landing-news-and-shop.php' ),
		];

		$switched = switch_to_locale( $locale );

		unload_textdomain( 'rockaden-theme' );
		$mofile = get_theme_file_path( 'languages/rockaden-theme-' . $locale . '.mo' );
		if ( file_exists( $mofile ) ) {
			load_textdomain( 'rockaden-theme', $mofile, $locale );
		}

		$parts = [];
		foreach ( $files as $file ) {
			if ( ! is_readable( $file ) ) {
				continue;
			}
			ob_start();
			include $file;
			$parts[] = trim( (string) ob_get_clean() );
		}

		// Put the request's own catalogue back, or everything after this call
		// renders in the locale we just borrowed.
		unload_textdomain( 'rockaden-theme' );
		if ( $switched ) {
			restore_previous_locale();
		}
		Rockaden_Theme_I18n::load_textdomain();

		return implode( "\n\n", array_filter( $parts ) );
	}

	/**
	 * Seed the landing content for a locale.
	 *
	 * Historically this wrote pattern *references* so gettext could re-translate
	 * them on every request. That never survived contact with the editor:
	 * opening and saving the page resolves the references permanently, and it
	 * froze whichever language the admin happened to be using — a Swedish site
	 * ended up with an English home page because the editor was in English.
	 *
	 * Each locale now gets its own page holding its own resolved text, which is
	 * both what the club asked for and what makes the freeze a non-event: the
	 * content is deliberately fixed in the right language from the start, and
	 * the design that has to keep improving lives in blocks rather than in this
	 * markup.
	 *
	 * @param string $locale Locale to seed for.
	 * @return string Resolved block markup.
	 */
	private static function build_landing_content( string $locale = self::LOCALE_SV ): string {
		return self::render_landing_for_locale( $locale );
	}

	/**
	 * Create stub pages if they don't already exist.
	 */
	private static function create_stub_pages(): void {
		foreach ( self::STUB_PAGES as $title => $slug ) {
			$existing = get_page_by_path( $slug );
			if ( $existing ) {
				continue;
			}

			// Nyheter is wired as WP's page_for_posts below, so its content is
			// never rendered — /nyheter/ is drawn entirely by home.html. Seed it
			// empty: placeholder text there looks like real content, and a
			// non-empty body also suppresses core's classic-editor lock, which
			// is conditional on `empty( $post->post_content )`.
			$content = ( 'nyheter' === $slug )
				? ''
				: '<!-- wp:paragraph --><p>Innehåll kommer snart.</p><!-- /wp:paragraph -->';

			wp_insert_post(
				[
					'post_title'   => $title,
					'post_name'    => $slug,
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_content' => $content,
				]
			);
		}
	}

	/**
	 * Set default theme options if they don't exist.
	 */
	private static function set_default_options(): void {
		if ( get_option( Rockaden_Theme_Settings::OPTION_KEY ) !== false ) {
			return;
		}

		$defaults             = Rockaden_Theme_Settings::defaults();
		$defaults['main_nav'] = [
			[
				'label' => 'Nyheter',
				'url'   => '/nyheter',
			],
			[
				'label' => 'Kalender',
				'url'   => '/kalender',
			],
			[
				'label' => 'Träning',
				'url'   => '/training',
			],
			[
				'label' => 'Medlemmar',
				'url'   => '/medlemmar',
			],
		];
		$defaults['more_nav'] = [
			[
				'label' => 'Om Rockaden',
				'url'   => '/om-rockaden',
			],
			[
				'label' => 'Kontakt',
				'url'   => '/kontakt',
			],
		];

		update_option( Rockaden_Theme_Settings::OPTION_KEY, $defaults );
	}
}
