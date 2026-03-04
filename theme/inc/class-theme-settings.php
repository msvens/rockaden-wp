<?php
/**
 * Rockaden Theme Settings page.
 *
 * Registers an admin page under Appearance → Rockaden where site admins
 * can configure navigation items, the "Mer" menu, and appearance options.
 */

defined('ABSPATH') || exit;

class Rockaden_Theme_Settings {

	const OPTION_KEY = 'rockaden_theme_options';
	const PAGE_SLUG  = 'rockaden-theme-settings';

	/**
	 * Default settings.
	 */
	public static function defaults(): array {
		return [
			'show_header_border' => true,
			'show_dark_toggle'   => true,
			'main_nav'           => [
				['label' => 'Nyheter',   'url' => '/'],
				['label' => 'Kalender',  'url' => '/kalender'],
				['label' => 'Träning',   'url' => '/training'],
				['label' => 'Medlemmar', 'url' => '/medlemmar'],
			],
			'more_nav'           => [
				['label' => 'Om Rockaden', 'url' => '/om-rockaden'],
				['label' => 'Kontakt',     'url' => '/kontakt'],
			],
		];
	}

	/**
	 * Get current settings merged with defaults.
	 */
	public static function get_options(): array {
		$saved = get_option(self::OPTION_KEY, []);
		if (! is_array($saved)) {
			$saved = [];
		}
		return array_merge(self::defaults(), $saved);
	}

	/**
	 * Return the config object for the frontend JS.
	 */
	public static function get_frontend_config(): array {
		$opts = self::get_options();
		return [
			'mainNav'          => $opts['main_nav'],
			'moreNav'          => $opts['more_nav'],
			'showDarkToggle'   => (bool) $opts['show_dark_toggle'],
			'showHeaderBorder' => (bool) $opts['show_header_border'],
		];
	}

	/**
	 * Register the admin page.
	 */
	public static function register_page(): void {
		add_theme_page(
			'Rockaden Settings',
			'Rockaden',
			'manage_options',
			self::PAGE_SLUG,
			[self::class, 'render_page']
		);
	}

	/**
	 * Enqueue admin assets only on our settings page.
	 */
	public static function enqueue_admin_assets(string $hook): void {
		if ($hook !== 'appearance_page_' . self::PAGE_SLUG) {
			return;
		}

		wp_enqueue_style(
			'rockaden-theme-settings',
			get_theme_file_uri('assets/css/theme-settings.css'),
			[],
			wp_get_theme()->get('Version')
		);

		wp_enqueue_script(
			'rockaden-theme-settings',
			get_theme_file_uri('assets/js/theme-settings.js'),
			[],
			wp_get_theme()->get('Version'),
			true
		);
	}

	/**
	 * Handle form save.
	 */
	public static function handle_save(): void {
		if (! isset($_POST['rockaden_settings_nonce'])) {
			return;
		}

		if (! wp_verify_nonce($_POST['rockaden_settings_nonce'], 'rockaden_save_settings')) {
			wp_die('Security check failed.');
		}

		if (! current_user_can('manage_options')) {
			wp_die('Unauthorized.');
		}

		$options = [];

		// Checkboxes.
		$options['show_header_border'] = ! empty($_POST['show_header_border']);
		$options['show_dark_toggle']   = ! empty($_POST['show_dark_toggle']);

		// Navigation items.
		$options['main_nav'] = self::sanitize_nav_items($_POST['main_nav_label'] ?? [], $_POST['main_nav_url'] ?? []);
		$options['more_nav'] = self::sanitize_nav_items($_POST['more_nav_label'] ?? [], $_POST['more_nav_url'] ?? []);

		update_option(self::OPTION_KEY, $options);

		wp_safe_redirect(add_query_arg([
			'page'    => self::PAGE_SLUG,
			'updated' => '1',
		], admin_url('themes.php')));
		exit;
	}

	/**
	 * Sanitize parallel arrays of labels and URLs into nav items.
	 */
	private static function sanitize_nav_items(array $labels, array $urls): array {
		$items = [];
		$count = min(count($labels), count($urls));
		for ($i = 0; $i < $count; $i++) {
			$label = sanitize_text_field($labels[$i]);
			$url   = sanitize_text_field($urls[$i]);
			if ($label === '' && $url === '') {
				continue;
			}
			$items[] = [
				'label' => $label,
				'url'   => $url,
			];
		}
		return $items;
	}

	/**
	 * Render the settings page.
	 */
	public static function render_page(): void {
		$options = self::get_options();
		$pages   = get_pages(['sort_column' => 'post_title', 'sort_order' => 'ASC']);
		?>
		<div class="wrap rockaden-settings-wrap">
			<h1>Rockaden Theme Settings</h1>

			<?php if (isset($_GET['updated'])) : ?>
				<div class="notice notice-success is-dismissible">
					<p>Settings saved.</p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
				<input type="hidden" name="action" value="rockaden_save_settings" />
				<?php wp_nonce_field('rockaden_save_settings', 'rockaden_settings_nonce'); ?>

				<!-- Header Options -->
				<h2>Header</h2>
				<table class="form-table">
					<tr>
						<th scope="row">Show header border</th>
						<td>
							<label>
								<input type="checkbox" name="show_header_border" value="1"
									<?php checked($options['show_header_border']); ?> />
								Display a bottom border on the header
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">Show dark mode toggle</th>
						<td>
							<label>
								<input type="checkbox" name="show_dark_toggle" value="1"
									<?php checked($options['show_dark_toggle']); ?> />
								Show the dark/light mode toggle in the Mer menu
							</label>
						</td>
					</tr>
				</table>

				<!-- Main Navigation -->
				<h2>Main Navigation</h2>
				<p class="description">These links appear in the header navigation bar.</p>
				<div class="rockaden-nav-repeater" id="main-nav-repeater">
					<?php foreach ($options['main_nav'] as $item) : ?>
						<div class="rockaden-nav-row">
							<input type="text" name="main_nav_label[]"
								value="<?php echo esc_attr($item['label']); ?>"
								placeholder="Label" class="regular-text" />
							<input type="text" name="main_nav_url[]"
								value="<?php echo esc_attr($item['url']); ?>"
								placeholder="/url" class="regular-text rockaden-url-input" />
							<select class="rockaden-page-select">
								<option value="">— Select page —</option>
								<?php foreach ($pages as $page) : ?>
									<option value="<?php echo esc_attr(wp_make_link_relative(get_permalink($page))); ?>"
										<?php selected(wp_make_link_relative(get_permalink($page)), $item['url']); ?>>
										<?php echo esc_html($page->post_title); ?>
									</option>
								<?php endforeach; ?>
								<option value="__custom__">Custom URL</option>
							</select>
							<button type="button" class="button rockaden-remove-row">&times;</button>
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button rockaden-add-row" data-target="main-nav-repeater" data-prefix="main_nav">+ Add item</button>

				<!-- More Menu -->
				<h2>More Menu</h2>
				<p class="description">These links appear in the "Mer" dropdown and mobile drawer.</p>
				<div class="rockaden-nav-repeater" id="more-nav-repeater">
					<?php foreach ($options['more_nav'] as $item) : ?>
						<div class="rockaden-nav-row">
							<input type="text" name="more_nav_label[]"
								value="<?php echo esc_attr($item['label']); ?>"
								placeholder="Label" class="regular-text" />
							<input type="text" name="more_nav_url[]"
								value="<?php echo esc_attr($item['url']); ?>"
								placeholder="/url" class="regular-text rockaden-url-input" />
							<select class="rockaden-page-select">
								<option value="">— Select page —</option>
								<?php foreach ($pages as $page) : ?>
									<option value="<?php echo esc_attr(wp_make_link_relative(get_permalink($page))); ?>"
										<?php selected(wp_make_link_relative(get_permalink($page)), $item['url']); ?>>
										<?php echo esc_html($page->post_title); ?>
									</option>
								<?php endforeach; ?>
								<option value="__custom__">Custom URL</option>
							</select>
							<button type="button" class="button rockaden-remove-row">&times;</button>
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button rockaden-add-row" data-target="more-nav-repeater" data-prefix="more_nav">+ Add item</button>

				<?php submit_button('Save Settings'); ?>
			</form>

			<!-- Hidden template for new rows (used by JS) -->
			<template id="rockaden-nav-row-template">
				<div class="rockaden-nav-row">
					<input type="text" name="" placeholder="Label" class="regular-text" />
					<input type="text" name="" placeholder="/url" class="regular-text rockaden-url-input" />
					<select class="rockaden-page-select">
						<option value="">— Select page —</option>
						<?php foreach ($pages as $page) : ?>
							<option value="<?php echo esc_attr(wp_make_link_relative(get_permalink($page))); ?>">
								<?php echo esc_html($page->post_title); ?>
							</option>
						<?php endforeach; ?>
						<option value="__custom__">Custom URL</option>
					</select>
					<button type="button" class="button rockaden-remove-row">&times;</button>
				</div>
			</template>
		</div>
		<?php
	}
}
