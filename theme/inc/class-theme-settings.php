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
			'header_style'         => 'contrast',
			'header_density'       => 'normal',
			'header_border_width'  => 'thin',
			'show_header_border'   => true,
			'show_dark_toggle'     => true,
			'show_language_toggle' => true,
			'sidebar_enabled'      => 'none',
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
			'mainNav'            => $opts['main_nav'],
			'moreNav'            => $opts['more_nav'],
			'showDarkToggle'     => (bool) $opts['show_dark_toggle'],
			'showHeaderBorder'   => (bool) $opts['show_header_border'],
			'showLanguageToggle' => (bool) $opts['show_language_toggle'],
			'headerStyle'        => $opts['header_style'],
			'headerDensity'      => $opts['header_density'],
			'headerBorderWidth'  => $opts['header_border_width'],
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

		// Header style.
		$style = sanitize_text_field($_POST['header_style'] ?? 'contrast');
		$options['header_style'] = in_array($style, ['default', 'contrast'], true) ? $style : 'contrast';

		// Header density.
		$density = sanitize_text_field($_POST['header_density'] ?? 'normal');
		$options['header_density'] = in_array($density, ['compact', 'normal', 'large'], true) ? $density : 'normal';

		// Header border width.
		$border_width = sanitize_text_field($_POST['header_border_width'] ?? 'thin');
		$options['header_border_width'] = in_array($border_width, ['thin', 'medium'], true) ? $border_width : 'thin';

		// Checkboxes.
		$options['show_header_border']   = ! empty($_POST['show_header_border']);
		$options['show_dark_toggle']     = ! empty($_POST['show_dark_toggle']);
		$options['show_language_toggle'] = ! empty($_POST['show_language_toggle']);

		// Sidebar visibility.
		$sidebar_value = sanitize_text_field($_POST['sidebar_enabled'] ?? 'none');
		$options['sidebar_enabled'] = in_array($sidebar_value, ['none', 'landing', 'all'], true)
			? $sidebar_value
			: 'none';

		// Navigation items.
		$options['main_nav'] = self::sanitize_nav_items($_POST['main_nav_label'] ?? [], $_POST['main_nav_url'] ?? [], $_POST['main_nav_label_en'] ?? []);
		$options['more_nav'] = self::sanitize_nav_items($_POST['more_nav_label'] ?? [], $_POST['more_nav_url'] ?? [], $_POST['more_nav_label_en'] ?? []);

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
	private static function sanitize_nav_items(array $labels, array $urls, array $labels_en = []): array {
		$items = [];
		$count = min(count($labels), count($urls));
		for ($i = 0; $i < $count; $i++) {
			$label    = sanitize_text_field($labels[$i]);
			$url      = sanitize_text_field($urls[$i]);
			$label_en = sanitize_text_field($labels_en[$i] ?? '');
			if ($label === '' && $url === '') {
				continue;
			}
			$item = [
				'label' => $label,
				'url'   => $url,
			];
			if ($label_en !== '') {
				$item['labelEn'] = $label_en;
			}
			$items[] = $item;
		}
		return $items;
	}

	/**
	 * Register page display meta box on pages.
	 */
	public static function register_page_display_meta_box(): void {
		add_meta_box(
			'rc_page_display',
			'Rockaden Display',
			[self::class, 'render_page_display_meta_box'],
			'page',
			'side',
			'default'
		);
	}

	/**
	 * Render page display meta box (title visibility + sidebar override).
	 */
	public static function render_page_display_meta_box(\WP_Post $post): void {
		$hide_title = get_post_meta($post->ID, 'rc_hide_title', true);
		$sidebar    = get_post_meta($post->ID, 'rc_sidebar_override', true);
		wp_nonce_field('rc_page_display_save', 'rc_page_display_nonce');
		?>
		<p>
			<label>
				<input type="checkbox" name="rc_hide_title" value="1"
					<?php checked($hide_title, '1'); ?> />
				Hide page title
			</label>
		</p>
		<p>
			<label for="rc_sidebar_override"><strong>Sidebar</strong></label><br>
			<select name="rc_sidebar_override" id="rc_sidebar_override" style="width:100%">
				<option value="" <?php selected($sidebar, ''); ?>>Default (use global setting)</option>
				<option value="show" <?php selected($sidebar, 'show'); ?>>Show</option>
				<option value="hide" <?php selected($sidebar, 'hide'); ?>>Hide</option>
			</select>
		</p>
		<?php
	}

	/**
	 * Save page display meta.
	 */
	public static function save_page_display_meta(int $post_id): void {
		if (! isset($_POST['rc_page_display_nonce'])) {
			return;
		}
		if (! wp_verify_nonce($_POST['rc_page_display_nonce'], 'rc_page_display_save')) {
			return;
		}
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}
		if (! current_user_can('edit_post', $post_id)) {
			return;
		}

		// Hide title.
		if (! empty($_POST['rc_hide_title'])) {
			update_post_meta($post_id, 'rc_hide_title', '1');
		} else {
			delete_post_meta($post_id, 'rc_hide_title');
		}

		// Sidebar override.
		$sidebar = sanitize_text_field($_POST['rc_sidebar_override'] ?? '');
		if (in_array($sidebar, ['show', 'hide'], true)) {
			update_post_meta($post_id, 'rc_sidebar_override', $sidebar);
		} else {
			delete_post_meta($post_id, 'rc_sidebar_override');
		}
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
						<th scope="row">Header style</th>
						<td>
							<select name="header_style">
								<option value="default" <?php selected($options['header_style'], 'default'); ?>>Default</option>
								<option value="contrast" <?php selected($options['header_style'], 'contrast'); ?>>Contrast</option>
							</select>
							<p class="description">Contrast uses a darker background in light mode for a more prominent header.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">Header density</th>
						<td>
							<select name="header_density">
								<option value="compact" <?php selected($options['header_density'], 'compact'); ?>>Compact (48px)</option>
								<option value="normal" <?php selected($options['header_density'], 'normal'); ?>>Normal (56px)</option>
								<option value="large" <?php selected($options['header_density'], 'large'); ?>>Large (64px)</option>
							</select>
						</td>
					</tr>
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
						<th scope="row">Border width</th>
						<td>
							<select name="header_border_width">
								<option value="thin" <?php selected($options['header_border_width'], 'thin'); ?>>Thin (1px)</option>
								<option value="medium" <?php selected($options['header_border_width'], 'medium'); ?>>Medium (2px)</option>
							</select>
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
					<tr>
						<th scope="row">Show language toggle</th>
						<td>
							<label>
								<input type="checkbox" name="show_language_toggle" value="1"
									<?php checked($options['show_language_toggle']); ?> />
								Show the SV/EN language switcher in the Mer menu
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">Sidebar</th>
						<td>
							<select name="sidebar_enabled">
								<option value="none" <?php selected($options['sidebar_enabled'], 'none'); ?>>Disabled</option>
								<option value="landing" <?php selected($options['sidebar_enabled'], 'landing'); ?>>Landing page only</option>
								<option value="all" <?php selected($options['sidebar_enabled'], 'all'); ?>>All pages</option>
							</select>
							<p class="description">Show the right sidebar panel. Content is edited via Appearance &rarr; Editor &rarr; Template Parts &rarr; Sidebar.</p>
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
								placeholder="Label (SV)" class="regular-text" />
							<input type="text" name="main_nav_label_en[]"
								value="<?php echo esc_attr($item['labelEn'] ?? ''); ?>"
								placeholder="Label (EN)" class="regular-text rockaden-label-en-input" />
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
								placeholder="Label (SV)" class="regular-text" />
							<input type="text" name="more_nav_label_en[]"
								value="<?php echo esc_attr($item['labelEn'] ?? ''); ?>"
								placeholder="Label (EN)" class="regular-text rockaden-label-en-input" />
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
					<input type="text" name="" placeholder="Label (SV)" class="regular-text" />
					<input type="text" name="" placeholder="Label (EN)" class="regular-text rockaden-label-en-input" />
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
