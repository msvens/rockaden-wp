<?php
/**
 * Rockaden Theme Shop.
 *
 * Owns the shop feature: the rc_shop_item custom post type and its meta, the
 * pricing/purchase meta box, and the read-only REST endpoint. The shop is
 * Rockaden-bespoke (Swedish equipment for sale) so it lives in the theme, not
 * the reusable plugin.
 *
 * CPT slug and all rc_* meta keys are intentionally kept identical to the
 * plugin's former implementation so existing content needs no migration.
 */

defined('ABSPATH') || exit;

class Rockaden_Theme_Shop {

	public const POST_TYPE = 'rc_shop_item';

	private const REST_NAMESPACE = 'rockaden/v1';

	/**
	 * Register hooks. Called from functions.php.
	 */
	public static function register(): void {
		self::register_post_type();
		self::register_meta();
	}

	/**
	 * Register the rc_shop_item post type.
	 */
	public static function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			[
				'labels'       => [
					'name'          => __('Shop Items', 'rockaden-theme'),
					'singular_name' => __('Shop Item', 'rockaden-theme'),
					'add_new_item'  => __('Add New Shop Item', 'rockaden-theme'),
					'edit_item'     => __('Edit Shop Item', 'rockaden-theme'),
				],
				// Items are shown only inside the shop grid block — there are no
				// per-item front-end pages. Keep the admin UI + REST for editing,
				// but no public single pages / archive / rewrite (the latter also
				// avoids clashing with the 'shop' page slug).
				'public'             => false,
				'show_ui'            => true,
				'show_in_rest'       => true,
				'publicly_queryable' => false,
				'exclude_from_search' => true,
				'supports'           => ['title', 'editor', 'thumbnail', 'excerpt'],
				'has_archive'        => false,
				'menu_icon'          => 'dashicons-cart',
				'rewrite'            => false,
			]
		);
	}

	/**
	 * Register post meta fields.
	 */
	private static function register_meta(): void {
		$meta_fields = [
			'rc_price'        => ['type' => 'string', 'default' => ''],
			'rc_sale_price'   => ['type' => 'string', 'default' => ''],
			'rc_buy_url'      => ['type' => 'string', 'default' => ''],
			'rc_stock_status' => ['type' => 'string', 'default' => 'in_stock'],
			'rc_how_to_order' => ['type' => 'string', 'default' => ''],
		];

		foreach ($meta_fields as $key => $args) {
			register_post_meta(
				self::POST_TYPE,
				$key,
				[
					'show_in_rest'  => true,
					'single'        => true,
					'type'          => $args['type'],
					'default'       => $args['default'],
					'auth_callback' => fn () => current_user_can('edit_posts'),
				]
			);
		}
	}

	/* ---------------------------------------------------------------------
	 * REST API
	 * ------------------------------------------------------------------- */

	/**
	 * Register REST routes.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/shop-items',
			[
				'methods'             => 'GET',
				'callback'            => [self::class, 'list_items'],
				'permission_callback' => '__return_true',
				'args'                => [
					'count' => [
						'type'        => 'integer',
						'description' => 'Maximum number of items to return.',
						'required'    => false,
						'default'     => 0,
					],
				],
			]
		);
	}

	/**
	 * List published shop items.
	 *
	 * @param WP_REST_Request $request The incoming request.
	 * @return WP_REST_Response
	 */
	public static function list_items(WP_REST_Request $request): WP_REST_Response {
		$count = (int) $request->get_param('count');

		$args = [
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => $count > 0 ? $count : -1,
			'orderby'        => 'menu_order date',
			'order'          => 'ASC',
		];

		$posts = get_posts($args);
		$items = array_map([self::class, 'format_item'], $posts);

		return new WP_REST_Response($items);
	}

	/**
	 * Format a shop-item post for the API response (and the block renderer).
	 *
	 * @param WP_Post $post The post to format.
	 * @return array<string, mixed>
	 */
	public static function format_item(WP_Post $post): array {
		$thumb_id = (int) get_post_thumbnail_id($post->ID);
		return [
			'id'          => $post->ID,
			'title'       => $post->post_title,
			'excerpt'     => $post->post_excerpt,
			'content'     => $post->post_content,
			'price'       => get_post_meta($post->ID, 'rc_price', true) ?: '',
			'salePrice'   => get_post_meta($post->ID, 'rc_sale_price', true) ?: '',
			'buyUrl'      => get_post_meta($post->ID, 'rc_buy_url', true) ?: '',
			'stockStatus' => get_post_meta($post->ID, 'rc_stock_status', true) ?: 'in_stock',
			'howToOrder'  => get_post_meta($post->ID, 'rc_how_to_order', true) ?: '',
			'imageUrl'    => $thumb_id ? wp_get_attachment_image_url($thumb_id, 'medium') : '',
			'imageAlt'    => $thumb_id ? get_post_meta($thumb_id, '_wp_attachment_image_alt', true) : '',
		];
	}

	/* ---------------------------------------------------------------------
	 * Pricing & Purchase meta box
	 * ------------------------------------------------------------------- */

	/**
	 * Register the meta box.
	 */
	public static function add_meta_box(): void {
		add_meta_box(
			'rc_shop_item_pricing',
			__('Pricing & Purchase', 'rockaden-theme'),
			[self::class, 'render_meta_box'],
			self::POST_TYPE,
			'side',
			'high'
		);
	}

	/**
	 * Render the meta box.
	 *
	 * @param WP_Post $post The current post object.
	 */
	public static function render_meta_box(WP_Post $post): void {
		wp_nonce_field('rc_shop_item_meta', 'rc_shop_item_meta_nonce');

		$price        = get_post_meta($post->ID, 'rc_price', true);
		$sale_price   = get_post_meta($post->ID, 'rc_sale_price', true);
		$buy_url      = get_post_meta($post->ID, 'rc_buy_url', true);
		$stock_status = get_post_meta($post->ID, 'rc_stock_status', true) ?: 'in_stock';
		$how_to_order = get_post_meta($post->ID, 'rc_how_to_order', true);
		?>
		<p>
			<label for="rc_price"><strong><?php esc_html_e('Price', 'rockaden-theme'); ?></strong></label><br />
			<input type="text" id="rc_price" name="rc_price" value="<?php echo esc_attr($price); ?>" class="widefat" placeholder="910 kr" />
		</p>
		<p>
			<label for="rc_sale_price"><strong><?php esc_html_e('Sale price', 'rockaden-theme'); ?></strong></label><br />
			<input type="text" id="rc_sale_price" name="rc_sale_price" value="<?php echo esc_attr($sale_price); ?>" class="widefat" placeholder="780 kr" />
			<span class="description"><?php esc_html_e('Optional. When set, the regular price is shown struck through.', 'rockaden-theme'); ?></span>
		</p>
		<p>
			<label for="rc_buy_url"><strong><?php esc_html_e('Buy URL', 'rockaden-theme'); ?></strong></label><br />
			<input type="url" id="rc_buy_url" name="rc_buy_url" value="<?php echo esc_attr($buy_url); ?>" class="widefat" placeholder="https://…" />
		</p>
		<p>
			<label for="rc_stock_status"><strong><?php esc_html_e('Stock status', 'rockaden-theme'); ?></strong></label><br />
			<select id="rc_stock_status" name="rc_stock_status" class="widefat">
				<option value="in_stock" <?php selected($stock_status, 'in_stock'); ?>><?php esc_html_e('In stock', 'rockaden-theme'); ?></option>
				<option value="out_of_stock" <?php selected($stock_status, 'out_of_stock'); ?>><?php esc_html_e('Out of stock', 'rockaden-theme'); ?></option>
			</select>
		</p>
		<p>
			<label for="rc_how_to_order"><strong><?php esc_html_e('How to order', 'rockaden-theme'); ?></strong></label><br />
			<textarea id="rc_how_to_order" name="rc_how_to_order" rows="3" class="widefat" placeholder="<?php esc_attr_e('e.g. Come to the club to buy, or email …', 'rockaden-theme'); ?>"><?php echo esc_textarea($how_to_order); ?></textarea>
			<span class="description"><?php esc_html_e('Optional. Shown on the public shop card when set.', 'rockaden-theme'); ?></span>
		</p>
		<?php
	}

	/**
	 * Save meta box fields.
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post    The post object.
	 */
	public static function save(int $post_id, WP_Post $post): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Required by save_post hook signature.
		if (!isset($_POST['rc_shop_item_meta_nonce'])) {
			return;
		}
		if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rc_shop_item_meta_nonce'])), 'rc_shop_item_meta')) {
			return;
		}
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}

		update_post_meta($post_id, 'rc_price', sanitize_text_field(wp_unslash($_POST['rc_price'] ?? '')));
		update_post_meta($post_id, 'rc_sale_price', sanitize_text_field(wp_unslash($_POST['rc_sale_price'] ?? '')));
		update_post_meta($post_id, 'rc_buy_url', esc_url_raw(wp_unslash($_POST['rc_buy_url'] ?? '')));

		$stock_status = sanitize_text_field(wp_unslash($_POST['rc_stock_status'] ?? 'in_stock'));
		if (!in_array($stock_status, ['in_stock', 'out_of_stock'], true)) {
			$stock_status = 'in_stock';
		}
		update_post_meta($post_id, 'rc_stock_status', $stock_status);
		update_post_meta($post_id, 'rc_how_to_order', sanitize_textarea_field(wp_unslash($_POST['rc_how_to_order'] ?? '')));
	}
}
