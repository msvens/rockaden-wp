<?php
/**
 * Rockaden Theme Setup.
 *
 * Creates stub pages and default settings on theme activation.
 */

defined('ABSPATH') || exit;

class Rockaden_Theme_Setup {

	/**
	 * Pages to create on activation: title => slug.
	 */
	private const STUB_PAGES = [
		'Nyheter'      => 'nyheter',
		'Kalender'     => 'kalender',
		'Träning'      => 'training',
		'Medlemmar'    => 'medlemmar',
		'Om Rockaden'  => 'om-rockaden',
		'Kontakt'      => 'kontakt',
		'Bli medlem'   => 'bli-medlem',
	];

	/**
	 * Run on theme activation (after_switch_theme).
	 */
	public static function activate(): void {
		self::create_stub_pages();
		self::create_landing_page();
		self::set_default_options();
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
		$page = get_page_by_path('hem');

		if (!$page) {
			$content = self::build_landing_content();

			$page_id = wp_insert_post([
				'post_title'   => 'Hem',
				'post_name'    => 'hem',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => $content,
				'meta_input'   => [
					'_wp_page_template' => 'page-landing',
				],
			]);

			if (is_wp_error($page_id) || !$page_id) {
				return;
			}
		} else {
			$page_id = $page->ID;
			// Only assign the landing template if the page has none yet —
			// don't revert an admin's deliberate template change.
			if (get_post_meta($page_id, '_wp_page_template', true) === '') {
				update_post_meta($page_id, '_wp_page_template', 'page-landing');
			}
		}

		// Only set front-page mode if admin hasn't chosen something else
		// (e.g. left the default "Latest posts" or pointed at a different page).
		if (get_option('show_on_front') !== 'page') {
			update_option('show_on_front', 'page');
		}

		// Only set page_on_front when nothing is set (0 = unset).
		if ((int) get_option('page_on_front') === 0) {
			update_option('page_on_front', (int) $page_id);
		}

		// Wire the Nyheter stub page as WP's "Posts page" so /nyheter/ serves
		// the post archive (using home.html / index.html). Only set if the
		// admin hasn't picked a different posts page.
		$nyheter = get_page_by_path('nyheter');
		if ($nyheter && (int) get_option('page_for_posts') === 0) {
			update_option('page_for_posts', (int) $nyheter->ID);
		}
	}

	/**
	 * Concatenate the registered landing patterns into a single block-content string.
	 */
	private static function build_landing_content(): string {
		$slugs = [
			'rockaden-theme/landing-hero',
			'rockaden-theme/landing-why',
			'rockaden-theme/landing-news-and-shop',
		];

		// Patterns are auto-registered from theme/patterns/ during init; ensure
		// they're available even if activation runs before that.
		if (function_exists('_register_theme_block_patterns')) {
			_register_theme_block_patterns();
		}

		$registry = \WP_Block_Patterns_Registry::get_instance();
		$parts    = [];

		foreach ($slugs as $slug) {
			$pattern = $registry->get_registered($slug);
			if ($pattern && !empty($pattern['content'])) {
				$parts[] = $pattern['content'];
			}
		}

		return implode("\n\n", $parts);
	}

	/**
	 * Create stub pages if they don't already exist.
	 */
	private static function create_stub_pages(): void {
		foreach (self::STUB_PAGES as $title => $slug) {
			$existing = get_page_by_path($slug);
			if ($existing) {
				continue;
			}

			wp_insert_post([
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '<!-- wp:paragraph --><p>Innehåll kommer snart.</p><!-- /wp:paragraph -->',
			]);
		}
	}

	/**
	 * Set default theme options if they don't exist.
	 */
	private static function set_default_options(): void {
		if (get_option(Rockaden_Theme_Settings::OPTION_KEY) !== false) {
			return;
		}

		$defaults             = Rockaden_Theme_Settings::defaults();
		$defaults['main_nav'] = [
			['label' => 'Nyheter',   'url' => '/nyheter'],
			['label' => 'Kalender',  'url' => '/kalender'],
			['label' => 'Träning',   'url' => '/training'],
			['label' => 'Medlemmar', 'url' => '/medlemmar'],
		];
		$defaults['more_nav'] = [
			['label' => 'Om Rockaden', 'url' => '/om-rockaden'],
			['label' => 'Kontakt',     'url' => '/kontakt'],
		];

		update_option(Rockaden_Theme_Settings::OPTION_KEY, $defaults);
	}
}
