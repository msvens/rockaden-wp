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
		self::set_default_options();
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
			['label' => 'Nyheter',   'url' => '/'],
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
