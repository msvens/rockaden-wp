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
			'sidebar_cards'        => [
				[
					'type'       => 'text',
					'title'      => 'Aktuellt',
					'show_title' => true,
					'content'    => '<p>Välkomna till ny säsong på SK Rockaden! Vi tränar varje tisdag 18:00 i Stockholms Schackförbunds lokaler.</p>',
					'link_url'   => '',
					'link_label' => '',
					'image_url'  => '',
					'full_bleed' => false,
				],
				[
					'type'       => 'text',
					'title'      => 'Bli medlem',
					'show_title' => true,
					'content'    => '<p>Vill du spela schack med oss? Kom på en provträning!</p>',
					'link_url'   => '/kontakt',
					'link_label' => 'Kontakta oss',
					'image_url'  => '',
					'full_bleed' => false,
				],
				[
					'type'       => 'text',
					'title'      => 'Hitta oss',
					'show_title' => true,
					'content'    => '<p>Stockholms Schackförbund<br>Kungsholmstorg 6<br>112 21 Stockholm</p>',
					'link_url'   => '',
					'link_label' => '',
					'image_url'  => '',
					'full_bleed' => false,
				],
			],
			'cta_label'          => 'Bli medlem',
			'cta_label_en'       => 'Join',
			'cta_url'            => '',
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
		$cta = [];
		$cta_url = trim($opts['cta_url']);
		if ($cta_url !== '') {
			$cta = [
				'label'   => $opts['cta_label'],
				'labelEn' => $opts['cta_label_en'],
				'url'     => $cta_url,
			];
		}

		return [
			'mainNav'            => $opts['main_nav'],
			'moreNav'            => $opts['more_nav'],
			'ctaButton'          => $cta,
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

		wp_enqueue_media();
		wp_enqueue_editor();

		wp_enqueue_style(
			'rockaden-theme-settings',
			get_theme_file_uri('assets/css/theme-settings.css'),
			[],
			wp_get_theme()->get('Version')
		);

		wp_enqueue_script(
			'rockaden-theme-settings',
			get_theme_file_uri('assets/js/theme-settings.js'),
			['jquery'],
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

		// CTA button.
		$options['cta_label']    = sanitize_text_field($_POST['cta_label'] ?? 'Bli medlem');
		$options['cta_label_en'] = sanitize_text_field($_POST['cta_label_en'] ?? 'Join');
		$options['cta_url']      = sanitize_text_field($_POST['cta_url'] ?? '');

		// Sidebar visibility.
		$sidebar_value = sanitize_text_field($_POST['sidebar_enabled'] ?? 'none');
		$options['sidebar_enabled'] = in_array($sidebar_value, ['none', 'landing', 'all'], true)
			? $sidebar_value
			: 'none';

		// Sidebar cards.
		$options['sidebar_cards'] = self::sanitize_sidebar_cards(
			$_POST['sidebar_card_type'] ?? [],
			$_POST['sidebar_card_title'] ?? [],
			$_POST['sidebar_card_show_title'] ?? [],
			$_POST['sidebar_card_content'] ?? [],
			$_POST['sidebar_card_link_url'] ?? [],
			$_POST['sidebar_card_link_label'] ?? [],
			$_POST['sidebar_card_image_url'] ?? [],
			$_POST['sidebar_card_full_bleed'] ?? []
		);

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
	 * Sanitize parallel arrays of sidebar card fields into card items.
	 */
	private static function sanitize_sidebar_cards(
		array $types,
		array $titles,
		array $show_titles,
		array $contents,
		array $link_urls,
		array $link_labels,
		array $image_urls,
		array $full_bleeds
	): array {
		$cards = [];
		$count = count($types);
		for ($i = 0; $i < $count; $i++) {
			$type = sanitize_text_field($types[$i] ?? 'text');
			if (! in_array($type, ['text', 'image'], true)) {
				$type = 'text';
			}
			$cards[] = [
				'type'       => $type,
				'title'      => sanitize_text_field($titles[$i] ?? ''),
				'show_title' => ! empty($show_titles[$i]),
				'content'    => wp_kses_post($contents[$i] ?? ''),
				'link_url'   => esc_url_raw($link_urls[$i] ?? ''),
				'link_label' => sanitize_text_field($link_labels[$i] ?? ''),
				'image_url'  => esc_url_raw($image_urls[$i] ?? ''),
				'full_bleed' => ! empty($full_bleeds[$i]),
			];
		}
		return $cards;
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
							<p class="description">Show the right sidebar panel. Configure cards in the Sidebar Cards section below.</p>
						</td>
					</tr>
				</table>

				<!-- CTA Button -->
				<h2>CTA Button</h2>
				<p class="description">A prominent call-to-action button in the header, always visible (including on mobile). Leave the URL empty to hide it.</p>
				<table class="form-table">
					<tr>
						<th scope="row">Label (SV)</th>
						<td>
							<input type="text" name="cta_label" value="<?php echo esc_attr($options['cta_label']); ?>" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row">Label (EN)</th>
						<td>
							<input type="text" name="cta_label_en" value="<?php echo esc_attr($options['cta_label_en']); ?>" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row">Link</th>
						<td>
							<input type="text" name="cta_url" value="<?php echo esc_attr($options['cta_url']); ?>" class="regular-text rockaden-url-input" placeholder="/bli-medlem" />
							<select class="rockaden-page-select" data-target-url="cta_url">
								<option value="">— Select page —</option>
								<?php foreach ($pages as $page) : ?>
									<option value="<?php echo esc_attr(wp_make_link_relative(get_permalink($page))); ?>"
										<?php selected(wp_make_link_relative(get_permalink($page)), $options['cta_url']); ?>>
										<?php echo esc_html($page->post_title); ?>
									</option>
								<?php endforeach; ?>
								<option value="__custom__">Custom URL</option>
							</select>
						</td>
					</tr>
				</table>

				<!-- Sidebar Cards -->
				<h2>Sidebar Cards</h2>
				<p class="description">Cards displayed in the sidebar panel. Drag to reorder, collapse to save space.</p>
				<div id="rc-sidebar-cards">
					<?php
					$cards = $options['sidebar_cards'] ?? [];
					$editor_settings = [
						'teeny'         => true,
						'media_buttons' => false,
						'textarea_rows' => 5,
						'quicktags'     => false,
					];
					foreach ($cards as $i => $card) :
						$card_type       = $card['type'] ?? 'text';
						$card_title      = $card['title'] ?? '';
						$card_show_title = $card['show_title'] ?? true;
						$card_content    = $card['content'] ?? '';
						$card_link       = $card['link_url'] ?? '';
						$card_label      = $card['link_label'] ?? '';
						$card_image      = $card['image_url'] ?? '';
						$card_full_bleed = $card['full_bleed'] ?? false;
						$preview         = $card_title !== '' ? $card_title : 'New card';
					?>
					<div class="rc-card-panel">
						<div class="rc-card-header">
							<span class="rc-card-title-preview"><?php echo esc_html($preview); ?></span>
							<span class="rc-card-type-badge"><?php echo esc_html(ucfirst($card_type)); ?></span>
							<span class="rc-card-header-actions">
								<button type="button" class="button-link rc-card-move-up" title="Move up">&uarr;</button>
								<button type="button" class="button-link rc-card-move-down" title="Move down">&darr;</button>
								<button type="button" class="button-link rc-card-collapse" title="Collapse">&#9656;</button>
								<button type="button" class="button-link rc-card-remove" title="Remove">&times;</button>
							</span>
						</div>
						<div class="rc-card-body">
							<p>
								<label>Type</label><br>
								<select name="sidebar_card_type[]" class="rc-card-type-select">
									<option value="text" <?php selected($card_type, 'text'); ?>>Text</option>
									<option value="image" <?php selected($card_type, 'image'); ?>>Image</option>
								</select>
							</p>
							<p>
								<label>Title</label><br>
								<input type="text" name="sidebar_card_title[]" value="<?php echo esc_attr($card_title); ?>" class="regular-text rc-card-title-input" />
							</p>
							<p>
								<label>Show title on frontend</label><br>
								<select name="sidebar_card_show_title[]" class="rc-card-show-title-select">
									<option value="1" <?php selected($card_show_title, true); ?>>Yes</option>
									<option value="0" <?php selected($card_show_title, false); ?>>No</option>
								</select>
							</p>
							<div class="rc-card-text-fields" <?php if ($card_type === 'image') echo 'style="display:none"'; ?>>
								<p><label>Content</label></p>
								<?php wp_editor($card_content, 'sidebar_card_content_' . $i, array_merge($editor_settings, ['textarea_name' => 'sidebar_card_content[]'])); ?>
							</div>
							<div class="rc-card-image-fields" <?php if ($card_type !== 'image') echo 'style="display:none"'; ?>>
								<p>
									<label>Image</label><br>
									<input type="hidden" name="sidebar_card_image_url[]" value="<?php echo esc_attr($card_image); ?>" class="rc-card-image-url" />
									<button type="button" class="button rc-card-select-image">Select Image</button>
									<button type="button" class="button rc-card-remove-image" <?php if ($card_image === '') echo 'style="display:none"'; ?>>Remove</button>
								</p>
								<?php if ($card_image !== '') : ?>
								<div class="rc-card-image-preview">
									<img src="<?php echo esc_url($card_image); ?>" alt="" />
								</div>
								<?php else : ?>
								<div class="rc-card-image-preview"></div>
								<?php endif; ?>
								<p>
									<label>Full card image</label><br>
									<select name="sidebar_card_full_bleed[]" class="rc-card-full-bleed-select">
										<option value="0" <?php selected($card_full_bleed, false); ?>>No</option>
										<option value="1" <?php selected($card_full_bleed, true); ?>>Yes — remove padding and background</option>
									</select>
								</p>
							</div>
							<p class="rc-card-link-url-field">
								<label>Link URL</label><br>
								<input type="text" name="sidebar_card_link_url[]" value="<?php echo esc_attr($card_link); ?>" class="regular-text" placeholder="/page-url or https://..." />
							</p>
							<p class="rc-card-link-label-field" <?php if ($card_type === 'image') echo 'style="display:none"'; ?>>
								<label>Link Label</label><br>
								<input type="text" name="sidebar_card_link_label[]" value="<?php echo esc_attr($card_label); ?>" class="regular-text" placeholder="Button text" />
							</p>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button" id="rc-add-sidebar-card">+ Add Card</button>

				<!-- Template for new sidebar cards (JS clones this) -->
				<template id="rc-sidebar-card-template">
					<div class="rc-card-panel">
						<div class="rc-card-header">
							<span class="rc-card-title-preview">New card</span>
							<span class="rc-card-type-badge">Text</span>
							<span class="rc-card-header-actions">
								<button type="button" class="button-link rc-card-move-up" title="Move up">&uarr;</button>
								<button type="button" class="button-link rc-card-move-down" title="Move down">&darr;</button>
								<button type="button" class="button-link rc-card-collapse" title="Collapse">&#9656;</button>
								<button type="button" class="button-link rc-card-remove" title="Remove">&times;</button>
							</span>
						</div>
						<div class="rc-card-body">
							<p>
								<label>Type</label><br>
								<select name="sidebar_card_type[]" class="rc-card-type-select">
									<option value="text">Text</option>
									<option value="image">Image</option>
								</select>
							</p>
							<p>
								<label>Title</label><br>
								<input type="text" name="sidebar_card_title[]" value="" class="regular-text rc-card-title-input" />
							</p>
							<p>
								<label>Show title on frontend</label><br>
								<select name="sidebar_card_show_title[]" class="rc-card-show-title-select">
									<option value="1" selected>Yes</option>
									<option value="0">No</option>
								</select>
							</p>
							<div class="rc-card-text-fields">
								<p><label>Content</label></p>
								<textarea name="sidebar_card_content[]" class="rc-card-content-textarea" rows="5" style="width:100%"></textarea>
							</div>
							<div class="rc-card-image-fields" style="display:none">
								<p>
									<label>Image</label><br>
									<input type="hidden" name="sidebar_card_image_url[]" value="" class="rc-card-image-url" />
									<button type="button" class="button rc-card-select-image">Select Image</button>
									<button type="button" class="button rc-card-remove-image" style="display:none">Remove</button>
								</p>
								<div class="rc-card-image-preview"></div>
								<p>
									<label>Full card image</label><br>
									<select name="sidebar_card_full_bleed[]" class="rc-card-full-bleed-select">
										<option value="0" selected>No</option>
										<option value="1">Yes — remove padding and background</option>
									</select>
								</p>
							</div>
							<p class="rc-card-link-url-field">
								<label>Link URL</label><br>
								<input type="text" name="sidebar_card_link_url[]" value="" class="regular-text" placeholder="/page-url or https://..." />
							</p>
							<p class="rc-card-link-label-field">
								<label>Link Label</label><br>
								<input type="text" name="sidebar_card_link_label[]" value="" class="regular-text" placeholder="Button text" />
							</p>
						</div>
					</div>
				</template>

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
