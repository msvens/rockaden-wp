<?php
/**
 * Rockaden Theme — Feedback.
 *
 * Owns the "Send us feedback" feature: the rc_feedback custom post type that
 * stores submissions, and the public REST endpoint the feedback-form block
 * posts to. Each submission is stored (reviewable in wp-admin) and emailed to
 * the configured recipient. Club-bespoke, so it lives in the theme — not the
 * reusable plugin.
 */

defined('ABSPATH') || exit;

class Rockaden_Theme_Feedback {

	public const POST_TYPE = 'rc_feedback';

	private const REST_NAMESPACE = 'rockaden/v1';

	/** Nonce action used by the public form (the standard REST nonce). */
	private const NONCE_ACTION = 'wp_rest';

	/**
	 * Register the CPT + meta. Called from functions.php on `init`.
	 */
	public static function register(): void {
		register_post_type(
			self::POST_TYPE,
			[
				'labels'              => [
					'name'          => __('Feedback', 'rockaden-theme'),
					'singular_name' => __('Feedback', 'rockaden-theme'),
					'edit_item'     => __('Visa feedback', 'rockaden-theme'),
					'all_items'     => __('Feedback', 'rockaden-theme'),
				],
				// Submissions are received via the public REST endpoint and only
				// reviewed in the admin — no front-end pages, archive or rewrite.
				'public'              => false,
				'show_ui'             => true,
				'show_in_rest'        => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'supports'            => ['title', 'editor'],
				'has_archive'         => false,
				'menu_icon'           => 'dashicons-email',
				'rewrite'             => false,
				// Reviewed-only: editors can read/delete but not create new ones
				// from the admin (submissions come through the form).
				'capabilities'        => ['create_posts' => 'do_not_allow'],
				'map_meta_cap'        => true,
			]
		);

		register_post_meta(
			self::POST_TYPE,
			'rc_fb_name',
			[
				'show_in_rest'  => false,
				'single'        => true,
				'type'          => 'string',
				'default'       => '',
				'auth_callback' => fn () => current_user_can('edit_posts'),
			]
		);
		register_post_meta(
			self::POST_TYPE,
			'rc_fb_email',
			[
				'show_in_rest'  => false,
				'single'        => true,
				'type'          => 'string',
				'default'       => '',
				'auth_callback' => fn () => current_user_can('edit_posts'),
			]
		);
	}

	/* ---------------------------------------------------------------------
	 * REST API — public submission endpoint
	 * ------------------------------------------------------------------- */

	/**
	 * Register REST routes. Called from functions.php on `rest_api_init`.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/feedback',
			[
				'methods'             => 'POST',
				'callback'            => [self::class, 'submit'],
				'permission_callback' => [self::class, 'verify_nonce'],
			]
		);
	}

	/**
	 * Permission callback: require the standard REST nonce (sent as X-WP-Nonce by
	 * the form's view script). Valid for logged-out visitors too — this is CSRF
	 * protection, not an authentication gate.
	 *
	 * @param WP_REST_Request $request The incoming request.
	 * @return bool
	 */
	public static function verify_nonce(WP_REST_Request $request): bool {
		$nonce = $request->get_header('X-WP-Nonce');
		return $nonce && false !== wp_verify_nonce($nonce, self::NONCE_ACTION);
	}

	/**
	 * Handle a feedback submission: validate, store, email.
	 *
	 * @param WP_REST_Request $request The incoming request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function submit(WP_REST_Request $request) {
		$params = $request->get_json_params();
		if (! is_array($params)) {
			$params = $request->get_params();
		}

		// Honeypot: a real user never fills the hidden "website" field. If it's
		// set, pretend success but store/send nothing.
		if (! empty($params['website'])) {
			return new WP_REST_Response(['ok' => true]);
		}

		$name    = sanitize_text_field((string) ($params['name'] ?? ''));
		$email   = sanitize_email((string) ($params['email'] ?? ''));
		$message = sanitize_textarea_field((string) ($params['message'] ?? ''));

		$name    = trim(mb_substr($name, 0, 200));
		$message = trim(mb_substr($message, 0, 5000));

		// Name + message are required. Email is optional — visitors only provide
		// it if they want to be reachable — but must be valid when given.
		if ('' === $name || '' === $message) {
			return new WP_Error(
				'rc_feedback_invalid',
				__('Fyll i ditt namn och ett meddelande.', 'rockaden-theme'),
				['status' => 400]
			);
		}
		if ('' !== $email && ! is_email($email)) {
			return new WP_Error(
				'rc_feedback_invalid_email',
				__('Ange en giltig e-postadress eller lämna fältet tomt.', 'rockaden-theme'),
				['status' => 400]
			);
		}

		// Store the submission.
		$post_id = wp_insert_post(
			[
				'post_type'    => self::POST_TYPE,
				'post_status'  => 'publish',
				/* translators: %s: sender name. */
				'post_title'   => sprintf(__('Feedback från %s', 'rockaden-theme'), $name),
				'post_content' => $message,
			],
			true
		);

		if (is_wp_error($post_id)) {
			return new WP_Error(
				'rc_feedback_store_failed',
				__('Kunde inte spara ditt meddelande. Försök igen.', 'rockaden-theme'),
				['status' => 500]
			);
		}

		update_post_meta($post_id, 'rc_fb_name', $name);
		update_post_meta($post_id, 'rc_fb_email', $email);

		self::send_email($name, $email, $message);

		return new WP_REST_Response(['ok' => true]);
	}

	/**
	 * Notify the configured recipient of a new submission.
	 *
	 * Email notifications are optional: with no recipient configured, submissions
	 * are only stored (reviewable under the Feedback menu) and no email is sent.
	 * Purely informational — deliberately NO Reply-To, so feedback doesn't turn
	 * into an email thread; the sender's address (if any) is in the body and on
	 * the stored entry for the club to follow up manually if they choose.
	 *
	 * @param string $name    Sender name.
	 * @param string $email   Sender email ('' if not provided).
	 * @param string $message Message body.
	 */
	private static function send_email(string $name, string $email, string $message): void {
		$options   = Rockaden_Theme_Settings::get_options();
		$recipient = isset($options['feedback_email']) ? trim((string) $options['feedback_email']) : '';
		if ('' === $recipient) {
			return; // Notifications off — the submission is already stored.
		}

		/* translators: %s: sender name. */
		$subject = sprintf(__('Webbplatsfeedback från %s', 'rockaden-theme'), $name);
		$body    = sprintf(
			"%s: %s\n%s: %s\n\n%s",
			__('Namn', 'rockaden-theme'),
			$name,
			__('E-post', 'rockaden-theme'),
			'' !== $email ? $email : '—',
			$message
		);

		wp_mail($recipient, $subject, $body);
	}

	/* ---------------------------------------------------------------------
	 * Admin list + meta box (review submissions)
	 * ------------------------------------------------------------------- */

	/**
	 * Register the read-only contact meta box.
	 */
	public static function add_meta_box(): void {
		add_meta_box(
			'rc_feedback_contact',
			__('Avsändare', 'rockaden-theme'),
			[self::class, 'render_meta_box'],
			self::POST_TYPE,
			'side',
			'high'
		);
	}

	/**
	 * Render the read-only sender meta box.
	 *
	 * @param WP_Post $post The current post.
	 */
	public static function render_meta_box(WP_Post $post): void {
		$name  = (string) get_post_meta($post->ID, 'rc_fb_name', true);
		$email = (string) get_post_meta($post->ID, 'rc_fb_email', true);
		?>
		<p><strong><?php esc_html_e('Namn', 'rockaden-theme'); ?>:</strong><br /><?php echo esc_html($name); ?></p>
		<p>
			<strong><?php esc_html_e('E-post', 'rockaden-theme'); ?>:</strong><br />
			<?php if ($email) : ?>
				<a href="<?php echo esc_url('mailto:' . $email); ?>"><?php echo esc_html($email); ?></a>
			<?php endif; ?>
		</p>
		<?php
	}

	/**
	 * Admin list columns: replace the date with email + received.
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string>
	 */
	public static function columns(array $columns): array {
		$new = [];
		foreach ($columns as $key => $label) {
			if ('title' === $key) {
				$new[$key]          = __('Från', 'rockaden-theme');
				$new['rc_fb_email'] = __('E-post', 'rockaden-theme');
			} else {
				$new[$key] = $label;
			}
		}
		return $new;
	}

	/**
	 * Render the custom column values.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Post ID.
	 */
	public static function column_content(string $column, int $post_id): void {
		if ('rc_fb_email' === $column) {
			$email = (string) get_post_meta($post_id, 'rc_fb_email', true);
			if ($email) {
				printf('<a href="%s">%s</a>', esc_url('mailto:' . $email), esc_html($email));
			}
		}
	}
}
