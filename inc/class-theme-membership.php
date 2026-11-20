<?php
/**
 * Rockaden Theme — Membership requests.
 *
 * Owns the membership form: the rc_member_request post type that stores
 * submissions, the public REST endpoint the membership-form block posts to,
 * the emails, and the editor-only "handled" flow in wp-admin.
 *
 * A submission is a request, never an action. Someone asks to join or to leave;
 * an editor updates the club's own member register by hand, marks the request
 * handled, and deletes it when it is no longer needed. Nothing here is a member
 * register and nothing reads these rows as one.
 *
 * @package Rockaden_Theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Membership requests: post type, REST endpoint, emails and admin screens.
 */
class Rockaden_Theme_Membership {

	public const POST_TYPE = 'rc_member_request';

	/** Request kinds. Also the block's `variant` values. */
	public const KINDS = [ 'join', 'leave' ];

	/** Membership categories, in display order. Stored as these slugs. */
	public const CATEGORIES = [ 'skolschack', 'klubbschack', 'vuxen', 'pensionar' ];

	/** Categories where the member is a child and a guardian is the contact. */
	public const JUNIOR_CATEGORIES = [ 'skolschack', 'klubbschack' ];

	private const REST_NAMESPACE = 'rockaden/v1';

	/** Nonce action used by the public form (the standard REST nonce). */
	private const NONCE_ACTION = 'wp_rest';

	/** The admin-post action that marks a request handled / new again. */
	private const STATUS_ACTION = 'rc_member_request_status';

	/** Query var behind the list table's Ny / Hanterad filter. */
	private const STATUS_QUERY_VAR = 'rc_mr_status';

	/**
	 * Query vars behind the list's filter dropdowns. Except for the status,
	 * each is named after the meta key it filters on.
	 */
	private const LIST_FILTERS = [
		'rc_mr_status'   => true,
		'rc_mr_kind'     => true,
		'rc_mr_category' => true,
		'rc_mr_school'   => true,
	];

	/**
	 * Capability required to see or touch requests at all. The rows hold
	 * personal data, some of it about children, so authors and contributors
	 * (who have edit_posts) are deliberately kept out.
	 */
	private const CAPABILITY = 'edit_others_posts';

	/**
	 * The locale this submission is answered in: the page's, sent by the form.
	 * The form posts without cookies (see blocks/membership-form/view.js), so
	 * the rc_locale cookie cannot say it.
	 *
	 * @var string
	 */
	private static string $locale = Rockaden_Theme_I18n::DEFAULT_LOCALE;

	/** String meta keys, in the order the admin screen lists them. */
	private const STRING_META = [
		'rc_mr_kind',
		'rc_mr_category',
		'rc_mr_first_name',
		'rc_mr_last_name',
		'rc_mr_birthdate',
		'rc_mr_personnummer',
		'rc_mr_guardian_name',
		'rc_mr_email',
		'rc_mr_phone',
		'rc_mr_school',
		'rc_mr_grade',
		// Join requests: the member's address, or the family's (guardian's)
		// for the junior categories. Postal code stored as five digits.
		'rc_mr_street',
		'rc_mr_postal_code',
		'rc_mr_city',
		// Junior categories: an optional second guardian, with an optional
		// address of their own (empty = same as the first guardian's).
		'rc_mr_guardian2_name',
		'rc_mr_guardian2_email',
		'rc_mr_guardian2_phone',
		'rc_mr_guardian2_street',
		'rc_mr_guardian2_postal_code',
		'rc_mr_guardian2_city',
		// The language the visitor used the site in (sv_SE / en_US). Missing
		// on older requests, which then count as Swedish.
		'rc_mr_locale',
	];

	/*
	---------------------------------------------------------------------
	 * Registration
	 * -------------------------------------------------------------------
	*/

	/**
	 * Register the CPT + meta. Called from functions.php on `init`.
	 */
	public static function register(): void {
		register_post_type(
			self::POST_TYPE,
			[
				'labels'              => [
					'name'          => __( 'Medlemsförfrågningar', 'rockaden-theme' ),
					'singular_name' => __( 'Medlemsförfrågan', 'rockaden-theme' ),
					'menu_name'     => __( 'Medlemskap', 'rockaden-theme' ),
					'edit_item'     => __( 'Visa förfrågan', 'rockaden-theme' ),
					'all_items'     => __( 'Medlemsförfrågningar', 'rockaden-theme' ),
					'search_items'  => __( 'Sök förfrågningar', 'rockaden-theme' ),
					'not_found'     => __( 'Inga förfrågningar.', 'rockaden-theme' ),
				],
				// Received through the public REST endpoint, reviewed in the admin
				// only: no front-end pages, archive or rewrite.
				'public'              => false,
				'show_ui'             => true,
				'show_in_rest'        => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				// No title or editor: the edit screen is a read-only view of the
				// request, rendered by the meta boxes below.
				'supports'            => false,
				'has_archive'         => false,
				'menu_icon'           => 'dashicons-id',
				'rewrite'             => false,
				'capabilities'        => [
					// Requests come from the form only.
					'create_posts' => 'do_not_allow',
					// Menu + list need editor rights, not just edit_posts. With
					// post_author 0 (inserted by a logged-out visitor), the per-post
					// edit_post / delete_post checks already resolve to the
					// *_others_posts capabilities.
					'edit_posts'   => self::CAPABILITY,
				],
				'map_meta_cap'        => true,
			]
		);

		foreach ( self::STRING_META as $key ) {
			register_post_meta(
				self::POST_TYPE,
				$key,
				[
					'show_in_rest'  => false,
					'single'        => true,
					'type'          => 'string',
					'default'       => '',
					'auth_callback' => fn () => current_user_can( self::CAPABILITY ),
				]
			);
		}
		// Handled = rc_mr_handled_at is set. There is no separate status field
		// that could disagree with it.
		foreach ( [ 'rc_mr_handled_at', 'rc_mr_handled_by' ] as $key ) {
			register_post_meta(
				self::POST_TYPE,
				$key,
				[
					'show_in_rest'  => false,
					'single'        => true,
					'type'          => 'integer',
					'default'       => 0,
					'auth_callback' => fn () => current_user_can( self::CAPABILITY ),
				]
			);
		}
	}

	/**
	 * Wire every hook the feature needs. Called once from functions.php.
	 */
	public static function hooks(): void {
		add_action( 'init', [ self::class, 'register' ] );
		add_action( 'rest_api_init', [ self::class, 'register_routes' ] );
		add_action( 'add_meta_boxes_' . self::POST_TYPE, [ self::class, 'add_meta_boxes' ] );
		add_action( 'admin_post_' . self::STATUS_ACTION, [ self::class, 'handle_status' ] );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', [ self::class, 'columns' ] );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ self::class, 'column_content' ], 10, 2 );
		add_filter( 'post_row_actions', [ self::class, 'row_actions' ], 10, 2 );
		add_filter( 'bulk_actions-edit-' . self::POST_TYPE, [ self::class, 'bulk_actions' ] );
		add_filter( 'add_menu_classes', [ self::class, 'add_menu_bubble' ] );
		add_filter( 'query_vars', [ self::class, 'register_query_var' ] );
		add_action( 'restrict_manage_posts', [ self::class, 'status_filter' ] );
		add_action( 'pre_get_posts', [ self::class, 'apply_status_filter' ] );
	}

	/*
	---------------------------------------------------------------------
	 * Labels + settings helpers
	 * -------------------------------------------------------------------
	*/

	/**
	 * Display label for a category slug, in the current locale.
	 *
	 * @param string $category Category slug.
	 */
	public static function category_label( string $category ): string {
		switch ( $category ) {
			case 'skolschack':
				return __( 'Skolschack', 'rockaden-theme' );
			case 'klubbschack':
				return __( 'Klubbschack', 'rockaden-theme' );
			case 'vuxen':
				return __( 'Vuxen medlem', 'rockaden-theme' );
			case 'pensionar':
				return __( 'Pensionär', 'rockaden-theme' );
		}
		return $category;
	}

	/**
	 * Display label for a request kind, in the current locale.
	 *
	 * @param string $kind 'join' or 'leave'.
	 */
	public static function kind_label( string $kind ): string {
		return 'leave' === $kind
			? __( 'Utträde', 'rockaden-theme' )
			: __( 'Anmälan', 'rockaden-theme' );
	}

	/**
	 * The schools offered in the Skolschack dropdown, from theme settings
	 * (one per line). An empty list means the school is typed as free text.
	 *
	 * @return array<int, string>
	 */
	public static function schools(): array {
		$options = Rockaden_Theme_Settings::get_options();
		return self::parse_lines( (string) ( $options['membership_schools'] ?? '' ) );
	}

	/**
	 * Split a textarea into clean, non-empty, unique lines.
	 *
	 * The /u flag is essential: without it \R also matches the single byte
	 * 0x85, which is the second byte of "Å" in UTF-8, so every Å was cut in
	 * half and the broken UTF-8 made the whole settings save fail.
	 *
	 * Lists are often pasted from a message, so each line also loses leading
	 * list markers ("- ", "• ", "* ") and any Unicode whitespace, including
	 * non-breaking spaces, at either end; runs of whitespace inside a name
	 * become one space.
	 *
	 * @param string $text Raw textarea value.
	 * @return array<int, string>
	 */
	public static function parse_lines( string $text ): array {
		$lines = [];
		foreach ( preg_split( '/\R/u', $text ) ?: [] as $line ) {
			$line = (string) preg_replace( '/^[\s\x{00A0}\x{200B}\x{FEFF}]*(?:[-•*–][\s\x{00A0}]+)?|[\s\x{00A0}\x{200B}\x{FEFF}]+$/u', '', $line );
			$line = (string) preg_replace( '/[\s\x{00A0}]+/u', ' ', $line );
			if ( '' !== $line ) {
				$lines[] = $line;
			}
		}
		return array_values( array_unique( $lines ) );
	}

	/**
	 * Whether the form collects a full personnummer instead of a birth date.
	 */
	public static function uses_personnummer(): bool {
		$options = Rockaden_Theme_Settings::get_options();
		return 'personnummer' === ( $options['membership_id_field'] ?? 'birthdate' );
	}

	/**
	 * The address request notifications go to: the membership recipient, or
	 * the feedback recipient when that is empty. '' turns notifications off.
	 */
	private static function recipient(): string {
		$options = Rockaden_Theme_Settings::get_options();
		$email   = trim( (string) ( $options['membership_email'] ?? '' ) );
		if ( '' === $email ) {
			$email = trim( (string) ( $options['feedback_email'] ?? '' ) );
		}
		return $email;
	}

	/*
	---------------------------------------------------------------------
	 * REST API — public endpoints
	 * -------------------------------------------------------------------
	*/

	/**
	 * Register REST routes. Called on `rest_api_init`.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/member-requests/context',
			[
				'methods'             => 'GET',
				'callback'            => [ self::class, 'context' ],
				'permission_callback' => '__return_true',
			]
		);
		register_rest_route(
			self::REST_NAMESPACE,
			'/member-requests',
			[
				'methods'             => 'POST',
				'callback'            => [ self::class, 'submit' ],
				'permission_callback' => [ self::class, 'verify_nonce' ],
			]
		);
	}

	/**
	 * A fresh REST nonce. Fetched by the form on every page view so the form
	 * keeps working when the page itself is served from a cache.
	 */
	public static function context(): WP_REST_Response {
		$response = new WP_REST_Response( [ 'nonce' => wp_create_nonce( self::NONCE_ACTION ) ] );
		$response->header( 'Cache-Control', 'no-store' );
		return $response;
	}

	/**
	 * Permission callback: require the standard REST nonce. Valid for
	 * logged-out visitors too — this is CSRF protection, not authentication.
	 *
	 * @param WP_REST_Request $request The incoming request.
	 */
	public static function verify_nonce( WP_REST_Request $request ): bool {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		return is_string( $nonce ) && false !== wp_verify_nonce( $nonce, self::NONCE_ACTION );
	}

	/**
	 * Handle a submission: validate, store, email.
	 *
	 * @param WP_REST_Request $request The incoming request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function submit( WP_REST_Request $request ) {
		$params = $request->get_json_params();
		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		// Honeypot: a real visitor never fills the hidden "website" field.
		// Pretend success, store and send nothing.
		if ( ! empty( $params['website'] ) ) {
			return new WP_REST_Response( [ 'ok' => true ] );
		}

		$locale = (string) ( $params['locale'] ?? '' );
		if ( in_array( $locale, Rockaden_Theme_I18n::ALLOWED, true ) ) {
			self::$locale = $locale;
			self::use_locale( $locale );
		}

		$data    = self::sanitize( $params );
		$problem = self::validate( $data );
		if ( null !== $problem ) {
			return new WP_Error(
				'rc_member_request_invalid',
				$problem['message'],
				[
					'status' => 400,
					'field'  => $problem['field'],
				]
			);
		}

		$post_id = wp_insert_post(
			[
				'post_type'    => self::POST_TYPE,
				'post_status'  => 'publish',
				'post_title'   => self::in_source_language( fn () => self::title( $data ) ),
				'post_content' => $data['message'],
			],
			true
		);
		if ( is_wp_error( $post_id ) ) {
			return new WP_Error(
				'rc_member_request_store_failed',
				__( 'Kunde inte spara din förfrågan. Försök igen.', 'rockaden-theme' ),
				[ 'status' => 500 ]
			);
		}

		foreach ( self::STRING_META as $key ) {
			$field = substr( $key, strlen( 'rc_mr_' ) );
			if ( '' !== $data[ $field ] ) {
				update_post_meta( $post_id, $key, $data[ $field ] );
			}
		}

		self::send_notification( $post_id, $data );
		self::send_confirmation( $data );

		return new WP_REST_Response( [ 'ok' => true ] );
	}

	/**
	 * Sanitize the submitted fields into one flat record. Fields that do not
	 * apply to the request's kind and category are dropped here, so a crafted
	 * request cannot store data the form never asked for.
	 *
	 * @param array<string, mixed> $params Raw request params.
	 * @return array<string, string>
	 */
	private static function sanitize( array $params ): array {
		$text = function ( string $key, int $max ) use ( $params ): string {
			$value = sanitize_text_field( (string) ( $params[ $key ] ?? '' ) );
			return trim( mb_substr( $value, 0, $max ) );
		};

		$kind = in_array( $params['kind'] ?? '', self::KINDS, true ) ? (string) $params['kind'] : '';
		$join = 'join' === $kind;
		// Only a join request has a category; a member is in exactly one,
		// which the club's register already knows.
		$category = $join ? $text( 'category', 20 ) : '';
		$junior   = $join && in_array( $category, self::JUNIOR_CATEGORIES, true );
		$school   = $join && 'skolschack' === $category;
		$pnr      = self::uses_personnummer();

		$data = [
			'kind'          => $kind,
			'category'      => $category,
			'first_name'    => $text( 'first_name', 100 ),
			'last_name'     => $text( 'last_name', 100 ),
			// Both are typed in many ways; normalized here to one stored form
			// (YYYY-MM-DD and twelve digits). Anything unrecognized is kept as
			// typed, so validation can say what is wrong with it.
			'birthdate'     => $pnr ? '' : self::normalize_birthdate( $text( 'birthdate', 20 ) ),
			'personnummer'  => $pnr ? self::normalize_personnummer( $text( 'personnummer', 20 ) ) : '',
			'guardian_name' => $junior ? $text( 'guardian_name', 200 ) : '',
			'email'         => sanitize_email( (string) ( $params['email'] ?? '' ) ),
			'phone'         => $join ? $text( 'phone', 30 ) : '',
			'school'        => $school ? $text( 'school', 200 ) : '',
			'grade'         => $school ? $text( 'grade', 20 ) : '',
			'street'        => $join ? $text( 'street', 200 ) : '',
			'postal_code'   => $join ? self::normalize_postal_code( $text( 'postal_code', 20 ) ) : '',
			'city'          => $join ? $text( 'city', 100 ) : '',
			'locale'        => self::$locale,
			'message'       => trim( mb_substr( sanitize_textarea_field( (string) ( $params['message'] ?? '' ) ), 0, 5000 ) ),
		];

		// Second guardian: junior join requests only. Their address is kept
		// only when the form says they live somewhere else.
		$second    = $junior;
		$own_place = $second && ! empty( $params['guardian2_own_address'] );

		$data['guardian2_name']        = $second ? $text( 'guardian2_name', 200 ) : '';
		$data['guardian2_email']       = $second ? sanitize_email( (string) ( $params['guardian2_email'] ?? '' ) ) : '';
		$data['guardian2_phone']       = $second ? $text( 'guardian2_phone', 30 ) : '';
		$data['guardian2_street']      = $own_place ? $text( 'guardian2_street', 200 ) : '';
		$data['guardian2_postal_code'] = $own_place ? self::normalize_postal_code( $text( 'guardian2_postal_code', 20 ) ) : '';
		$data['guardian2_city']        = $own_place ? $text( 'guardian2_city', 100 ) : '';
		// Not stored: only decides whether the address fields are required.
		$data['guardian2_own_address'] = $own_place ? '1' : '';

		return $data;
	}

	/**
	 * Swedish postal codes are five digits, often written "123 45".
	 *
	 * @param string $raw As typed.
	 * @return string Five digits, or $raw if it is not that.
	 */
	private static function normalize_postal_code( string $raw ): string {
		$digits = (string) preg_replace( '/[\s\x{00A0}]+/u', '', $raw );
		return 1 === preg_match( '/^\d{5}$/', $digits ) ? $digits : $raw;
	}

	/**
	 * Check an address: all three parts present, and a valid postal code.
	 *
	 * @param array<string, string> $data   Sanitized record.
	 * @param string                $prefix '' for the main address, 'guardian2_' for the second guardian's.
	 * @return array{field: string, message: string}|null
	 */
	private static function check_address( array $data, string $prefix ): ?array {
		if ( '' === $data[ $prefix . 'street' ] ) {
			return self::problem( $prefix . 'street', __( 'Fyll i gatuadress.', 'rockaden-theme' ) );
		}
		if ( 1 !== preg_match( '/^\d{5}$/', $data[ $prefix . 'postal_code' ] ) ) {
			return self::problem( $prefix . 'postal_code', __( 'Postnumret ska vara fem siffror, t.ex. 123 45.', 'rockaden-theme' ) );
		}
		if ( '' === $data[ $prefix . 'city' ] ) {
			return self::problem( $prefix . 'city', __( 'Fyll i ort.', 'rockaden-theme' ) );
		}
		return null;
	}

	/**
	 * Check a sanitized record. Returns the first problem, naming the form
	 * field it belongs to so the form can mark and focus it, or null.
	 *
	 * @param array<string, string> $data Sanitized record.
	 * @return array{field: string, message: string}|null
	 */
	private static function validate( array $data ): ?array {
		if ( '' === $data['kind'] ) {
			return self::problem( 'kind', __( 'Okänd typ av förfrågan.', 'rockaden-theme' ) );
		}
		if ( 'join' === $data['kind'] && ! in_array( $data['category'], self::CATEGORIES, true ) ) {
			return self::problem( 'category', __( 'Välj en kategori.', 'rockaden-theme' ) );
		}
		if ( '' === $data['first_name'] ) {
			return self::problem( 'first_name', __( 'Fyll i förnamn.', 'rockaden-theme' ) );
		}
		if ( '' === $data['last_name'] ) {
			return self::problem( 'last_name', __( 'Fyll i efternamn.', 'rockaden-theme' ) );
		}

		$identity = self::uses_personnummer()
			? self::check_personnummer( $data['personnummer'] )
			: self::check_birthdate( $data['birthdate'] );
		if ( null !== $identity ) {
			return $identity;
		}

		$join = 'join' === $data['kind'];
		if ( $join && in_array( $data['category'], self::JUNIOR_CATEGORIES, true ) && '' === $data['guardian_name'] ) {
			return self::problem( 'guardian_name', __( 'Fyll i vårdnadshavarens namn.', 'rockaden-theme' ) );
		}
		if ( '' === $data['email'] || ! is_email( $data['email'] ) ) {
			return self::problem( 'email', __( 'Ange en giltig e-postadress.', 'rockaden-theme' ) );
		}
		// Loose on purpose: people write phone numbers in many ways. Only
		// catch what cannot be a number at all.
		if ( '' !== $data['phone'] && 1 !== preg_match( '/^\+?[0-9\s\-()]{6,30}$/', $data['phone'] ) ) {
			return self::problem( 'phone', __( 'Ange ett giltigt telefonnummer eller lämna fältet tomt.', 'rockaden-theme' ) );
		}

		if ( $join ) {
			$address = self::check_address( $data, '' );
			if ( null !== $address ) {
				return $address;
			}
		}

		// The second guardian is optional, but once started it needs a name,
		// a valid email if one is given, and a complete address if they live
		// somewhere else.
		$has_second = '' !== $data['guardian2_name'] || '' !== $data['guardian2_email'] || '' !== $data['guardian2_phone'] || '1' === $data['guardian2_own_address'];
		if ( $has_second ) {
			if ( '' === $data['guardian2_name'] ) {
				return self::problem( 'guardian2_name', __( 'Fyll i den andra vårdnadshavarens namn.', 'rockaden-theme' ) );
			}
			if ( '' !== $data['guardian2_email'] && ! is_email( $data['guardian2_email'] ) ) {
				return self::problem( 'guardian2_email', __( 'Ange en giltig e-postadress eller lämna fältet tomt.', 'rockaden-theme' ) );
			}
			if ( '' !== $data['guardian2_phone'] && 1 !== preg_match( '/^\+?[0-9\s\-()]{6,30}$/', $data['guardian2_phone'] ) ) {
				return self::problem( 'guardian2_phone', __( 'Ange ett giltigt telefonnummer eller lämna fältet tomt.', 'rockaden-theme' ) );
			}
			if ( '1' === $data['guardian2_own_address'] ) {
				$address = self::check_address( $data, 'guardian2_' );
				if ( null !== $address ) {
					return $address;
				}
			}
		}

		if ( $join && 'skolschack' === $data['category'] ) {
			$schools = self::schools();
			if ( '' === $data['school'] || ( $schools && ! in_array( $data['school'], $schools, true ) ) ) {
				return self::problem( 'school', __( 'Välj en skola.', 'rockaden-theme' ) );
			}
			if ( '' === $data['grade'] ) {
				return self::problem( 'grade', __( 'Fyll i årskurs eller klass.', 'rockaden-theme' ) );
			}
		}

		return null;
	}

	/**
	 * Normalize a typed birth date to YYYY-MM-DD. Accepts 2023-03-02,
	 * 2023-3-2, 20230302, 230302 and 23-03-02 (also with / or . between the
	 * parts). A two-digit year gets the most recent century that does not put
	 * the date in the future.
	 *
	 * @param string $raw As typed.
	 * @return string YYYY-MM-DD, or $raw (without spaces) if unrecognized.
	 */
	private static function normalize_birthdate( string $raw ): string {
		$value = (string) preg_replace( '/\s+/', '', $raw );
		if ( 1 === preg_match( '/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})$/', $value, $m )
			|| 1 === preg_match( '/^(\d{4})(\d{2})(\d{2})$/', $value, $m ) ) {
			return sprintf( '%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3] );
		}
		if ( 1 === preg_match( '/^(\d{2})[-\/.]?(\d{2})[-\/.]?(\d{2})$/', $value, $m ) ) {
			$year = self::full_year( (int) $m[1], (int) $m[2], (int) $m[3] );
			return sprintf( '%04d-%02d-%02d', $year, (int) $m[2], (int) $m[3] );
		}
		return $value;
	}

	/**
	 * Normalize a typed personnummer to twelve digits (YYYYMMDDNNNN). Accepts
	 * twelve digits or the ten-digit form, with or without a hyphen. In the
	 * ten-digit form a '+' instead of the hyphen marks someone aged 100 or
	 * more, as on Swedish ID documents.
	 *
	 * @param string $raw As typed.
	 * @return string Twelve digits, or $raw (without spaces) if unrecognized.
	 */
	private static function normalize_personnummer( string $raw ): string {
		$value = (string) preg_replace( '/\s+/', '', $raw );
		if ( 1 === preg_match( '/^(\d{8})-?(\d{4})$/', $value, $m ) ) {
			return $m[1] . $m[2];
		}
		if ( 1 === preg_match( '/^(\d{2})(\d{2})(\d{2})([-+]?)(\d{4})$/', $value, $m ) ) {
			$year = self::full_year( (int) $m[1], (int) $m[2], (int) $m[3] );
			if ( '+' === $m[4] ) {
				$year -= 100;
			}
			return sprintf( '%04d%s%s%s', $year, $m[2], $m[3], $m[5] );
		}
		return $value;
	}

	/**
	 * The four-digit year for a two-digit one: this century, unless that puts
	 * the date in the future, in which case the previous one.
	 *
	 * @param int $yy    Two-digit year.
	 * @param int $month Month (only used to compare with today).
	 * @param int $day   Day (only used to compare with today).
	 */
	private static function full_year( int $yy, int $month, int $day ): int {
		$century = (int) ( (int) gmdate( 'Y' ) / 100 ) * 100;
		$year    = $century + $yy;
		if ( sprintf( '%04d-%02d-%02d', $year, $month, $day ) > gmdate( 'Y-m-d' ) ) {
			$year -= 100;
		}
		return $year;
	}

	/**
	 * Check a birth date, already normalized to YYYY-MM-DD where possible.
	 *
	 * @param string $birthdate The value.
	 * @return array{field: string, message: string}|null
	 */
	private static function check_birthdate( string $birthdate ): ?array {
		if ( '' === $birthdate ) {
			return self::problem( 'birthdate', __( 'Fyll i födelsedatum.', 'rockaden-theme' ) );
		}
		if ( 1 !== preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $birthdate, $m ) ) {
			return self::problem( 'birthdate', __( 'Skriv födelsedatum som ÅÅÅÅ-MM-DD, ÅÅÅÅMMDD eller ÅÅMMDD.', 'rockaden-theme' ) );
		}
		if ( ! self::is_real_date( (int) $m[1], (int) $m[2], (int) $m[3] ) ) {
			return self::problem( 'birthdate', __( 'Det datumet finns inte. Kontrollera år, månad och dag.', 'rockaden-theme' ) );
		}
		return null;
	}

	/**
	 * Check a personnummer, already normalized to twelve digits where
	 * possible: a real date, and the Luhn check digit over the last ten.
	 *
	 * @param string $personnummer Normalized value.
	 * @return array{field: string, message: string}|null
	 */
	private static function check_personnummer( string $personnummer ): ?array {
		if ( '' === $personnummer ) {
			return self::problem( 'personnummer', __( 'Fyll i personnummer.', 'rockaden-theme' ) );
		}
		if ( 1 !== preg_match( '/^(\d{4})(\d{2})(\d{2})\d{4}$/', $personnummer, $m ) ) {
			return self::problem( 'personnummer', __( 'Skriv personnumret som ÅÅÅÅMMDD-NNNN eller ÅÅMMDD-NNNN.', 'rockaden-theme' ) );
		}
		if ( ! self::is_real_date( (int) $m[1], (int) $m[2], (int) $m[3] ) ) {
			return self::problem( 'personnummer', __( 'Det datumet finns inte. Kontrollera år, månad och dag.', 'rockaden-theme' ) );
		}
		if ( ! self::luhn_is_valid( substr( $personnummer, 2 ) ) ) {
			return self::problem( 'personnummer', __( 'Personnumret stämmer inte. Kontrollera det och försök igen.', 'rockaden-theme' ) );
		}
		return null;
	}

	/**
	 * Whether a date exists, is not in the future, and is not before 1900.
	 *
	 * @param int $year  Year.
	 * @param int $month Month.
	 * @param int $day   Day.
	 */
	private static function is_real_date( int $year, int $month, int $day ): bool {
		if ( $year < 1900 || ! checkdate( $month, $day, $year ) ) {
			return false;
		}
		return sprintf( '%04d-%02d-%02d', $year, $month, $day ) <= gmdate( 'Y-m-d' );
	}

	/**
	 * The Luhn check a Swedish personnummer carries, over its ten-digit form.
	 *
	 * @param string $digits Ten digits (YYMMDDNNNN).
	 */
	private static function luhn_is_valid( string $digits ): bool {
		$sum = 0;
		for ( $i = 0; $i < 10; $i++ ) {
			$digit = (int) $digits[ $i ];
			if ( 0 === $i % 2 ) {
				$digit *= 2;
			}
			$sum += $digit > 9 ? $digit - 9 : $digit;
		}
		return 0 === $sum % 10;
	}

	/**
	 * Build one validation problem.
	 *
	 * @param string $field   Form field name.
	 * @param string $message What to tell the visitor.
	 * @return array{field: string, message: string}
	 */
	private static function problem( string $field, string $message ): array {
		return [
			'field'   => $field,
			'message' => $message,
		];
	}

	/*
	---------------------------------------------------------------------
	 * Email
	 * -------------------------------------------------------------------
	*/

	/**
	 * Load the theme's catalogue for $locale. The source strings are Swedish,
	 * so for sv_SE (which has no catalogue) unloading is all it takes.
	 *
	 * The theme loads its catalogue with load_textdomain() and an explicit path
	 * (see Rockaden_Theme_I18n::load_textdomain), so switch_to_locale() would
	 * not move it.
	 *
	 * @param string $locale One of Rockaden_Theme_I18n::ALLOWED.
	 */
	private static function use_locale( string $locale ): void {
		unload_textdomain( 'rockaden-theme' );
		$mofile = get_theme_file_path( 'languages/rockaden-theme-' . $locale . '.mo' );
		if ( file_exists( $mofile ) ) {
			load_textdomain( 'rockaden-theme', $mofile, $locale );
		}
	}

	/**
	 * Run $build in the source language, Swedish, whatever language the
	 * visitor used, then switch back. The club's mailbox and admin list are
	 * always in Swedish.
	 *
	 * @template T
	 * @param callable(): T $build Builds the text.
	 * @return T
	 */
	private static function in_source_language( callable $build ) {
		unload_textdomain( 'rockaden-theme' );
		try {
			return $build();
		} finally {
			self::use_locale( self::$locale );
		}
	}

	/**
	 * Post title / email subject, e.g. "Anmälan: Anna Svensson (Klubbschack)".
	 *
	 * @param array<string, string> $data Sanitized record.
	 */
	private static function title( array $data ): string {
		if ( '' === $data['category'] ) {
			return sprintf( '%s: %s %s', self::kind_label( $data['kind'] ), $data['first_name'], $data['last_name'] );
		}
		return sprintf(
			'%s: %s %s (%s)',
			self::kind_label( $data['kind'] ),
			$data['first_name'],
			$data['last_name'],
			self::category_label( $data['category'] )
		);
	}

	/**
	 * Labelled lines describing a request, for the email and the admin view.
	 * Empty fields are left out. The personnummer is never included: the
	 * email sits in a mailbox long after the request is deleted, so it is
	 * only shown in wp-admin.
	 *
	 * @param array<string, string> $data Record (field => value).
	 * @return array<string, array{0: string, 1: string}> Field => [label, value].
	 */
	private static function describe( array $data ): array {
		$junior = in_array( $data['category'] ?? '', self::JUNIOR_CATEGORIES, true );
		$rows   = [
			'kind'              => [ __( 'Typ', 'rockaden-theme' ), self::kind_label( $data['kind'] ?? '' ) ],
			'category'          => [ __( 'Kategori', 'rockaden-theme' ), self::category_label( $data['category'] ?? '' ) ],
			'name'              => [ __( 'Namn', 'rockaden-theme' ), trim( ( $data['first_name'] ?? '' ) . ' ' . ( $data['last_name'] ?? '' ) ) ],
			'birthdate'         => [ __( 'Födelsedatum', 'rockaden-theme' ), $data['birthdate'] ?? '' ],
			'guardian_name'     => [ __( 'Vårdnadshavare', 'rockaden-theme' ), $data['guardian_name'] ?? '' ],
			'email'             => [
				'join' === ( $data['kind'] ?? '' ) && $junior
					? __( 'E-post (vårdnadshavare)', 'rockaden-theme' )
					: __( 'E-post', 'rockaden-theme' ),
				$data['email'] ?? '',
			],
			'phone'             => [ __( 'Telefon', 'rockaden-theme' ), $data['phone'] ?? '' ],
			'address'           => [ __( 'Adress', 'rockaden-theme' ), self::format_address( $data, '' ) ],
			'guardian2_name'    => [ __( 'Vårdnadshavare 2', 'rockaden-theme' ), $data['guardian2_name'] ?? '' ],
			'guardian2_email'   => [ __( 'E-post (vårdnadshavare 2)', 'rockaden-theme' ), $data['guardian2_email'] ?? '' ],
			'guardian2_phone'   => [ __( 'Telefon (vårdnadshavare 2)', 'rockaden-theme' ), $data['guardian2_phone'] ?? '' ],
			'guardian2_address' => [ __( 'Adress (vårdnadshavare 2)', 'rockaden-theme' ), self::format_address( $data, 'guardian2_' ) ],
			'school'            => [ __( 'Skola', 'rockaden-theme' ), $data['school'] ?? '' ],
			'grade'             => [ __( 'Årskurs/klass', 'rockaden-theme' ), $data['grade'] ?? '' ],
			// Only shown when it is not Swedish, so it stands out when it matters.
			'locale'            => [ __( 'Språk', 'rockaden-theme' ), self::used_english( $data ) ? __( 'Engelska', 'rockaden-theme' ) : '' ],
		];
		return array_filter( $rows, fn ( $row ) => '' !== $row[1] );
	}

	/**
	 * What to check before acting on a request to leave. The form does not
	 * verify who sent it, so anyone could ask to remove someone else; the
	 * person handling it checks against the member register instead. Shown in
	 * the email and on the request in wp-admin.
	 */
	private static function leave_check_text(): string {
		return __( 'Formuläret kontrollerar inte vem som skickat det. Jämför namn, födelsedatum och e-post med medlemsregistret, och bekräfta med medlemmen (eller vårdnadshavaren) via kontaktuppgifterna i registret, inte genom att svara på det här mejlet. Stämmer något inte, eller känner medlemmen inte till begäran: ta inte bort medlemmen. Markera ändå förfrågan som hanterad.', 'rockaden-theme' );
	}

	/**
	 * An address on one line, e.g. "Storgatan 1, 123 45 Stockholm", or ''.
	 *
	 * @param array<string, string> $data   Record.
	 * @param string                $prefix '' or 'guardian2_'.
	 */
	private static function format_address( array $data, string $prefix ): string {
		$street = $data[ $prefix . 'street' ] ?? '';
		$postal = $data[ $prefix . 'postal_code' ] ?? '';
		$city   = $data[ $prefix . 'city' ] ?? '';
		if ( 1 === preg_match( '/^\d{5}$/', $postal ) ) {
			$postal = substr( $postal, 0, 3 ) . ' ' . substr( $postal, 3 );
		}
		$place = trim( $postal . ' ' . $city );
		return trim( $street . ( '' !== $street && '' !== $place ? ', ' : '' ) . $place );
	}

	/**
	 * The hint shown to the club when the visitor used the site in English.
	 */
	private static function english_hint(): string {
		return __( 'Obs: personen använde webbplatsen på engelska. Svara gärna på engelska.', 'rockaden-theme' );
	}

	/**
	 * Whether the visitor used the site in English. The club's email and
	 * admin view stay Swedish, but say so, so the reply can be in English.
	 *
	 * @param array<string, string> $data Record.
	 */
	private static function used_english( array $data ): bool {
		return str_starts_with( $data['locale'] ?? '', 'en' );
	}

	/**
	 * Email the club about a new request, always in Swedish. Reply-To is the
	 * applicant, so answering from the mailbox reaches them.
	 *
	 * HTML rather than plain text, so the link to the request can be a button
	 * instead of a bare wp-admin URL. Inline styles only: mail clients ignore
	 * style sheets. Every value is escaped; most of it was typed by a visitor.
	 * Opening the link changes nothing.
	 *
	 * @param int                   $post_id Stored request.
	 * @param array<string, string> $data    Sanitized record.
	 */
	private static function send_notification( int $post_id, array $data ): void {
		$recipient = self::recipient();
		if ( '' === $recipient ) {
			return; // Notifications off — the request is already stored.
		}

		// Built by hand: get_edit_post_link() returns null for the logged-out
		// visitor this request runs as.
		$url = admin_url( 'post.php?post=' . $post_id . '&action=edit' );

		[ $subject, $body ] = self::in_source_language(
			function () use ( $data, $url ): array {
				$rows = self::describe( $data );
				if ( '' !== $data['personnummer'] ) {
					$rows['personnummer'] = [ __( 'Personnummer', 'rockaden-theme' ), __( 'Visas i förfrågan i adminpanelen', 'rockaden-theme' ) ];
				}

				// The submitted data sits in its own framed card so it reads as
				// "what the visitor typed", apart from our own text around it.
				// Table layout with bgcolor attributes: the one thing every mail
				// client (Outlook included) renders the same way.
				$label_cell = 'padding:8px 16px;vertical-align:top;color:#6b7280;white-space:nowrap;border-top:1px solid #e5e7eb;';
				$value_cell = 'padding:8px 16px 8px 0;vertical-align:top;border-top:1px solid #e5e7eb;';
				$card       = '';
				foreach ( $rows as [ $label, $value ] ) {
					$card .= '<tr><td style="' . $label_cell . '">' . esc_html( $label ) . '</td>'
						. '<td style="' . $value_cell . '">' . esc_html( $value ) . '</td></tr>';
				}
				if ( '' !== $data['message'] ) {
					$card .= '<tr><td style="' . $label_cell . '">' . esc_html__( 'Meddelande', 'rockaden-theme' ) . '</td>'
						. '<td style="' . $value_cell . '">' . nl2br( esc_html( $data['message'] ) ) . '</td></tr>';
				}

				$intro = 'leave' === $data['kind']
					? __( 'Ny begäran om utträde via webbplatsens formulär.', 'rockaden-theme' )
					: __( 'Ny anmälan via webbplatsens formulär.', 'rockaden-theme' );

				$html  = '<div style="font-family:-apple-system,Segoe UI,Helvetica,Arial,sans-serif;font-size:15px;line-height:1.5;color:#111827;">';
				$html .= '<p style="margin:0 0 16px;font-size:16px;font-weight:600;">' . esc_html( $intro ) . '</p>';
				if ( self::used_english( $data ) ) {
					$html .= '<p style="margin:-8px 0 16px;max-width:560px;padding:8px 12px;background:#dbeafe;border-left:4px solid #2563eb;">'
						. esc_html( self::english_hint() ) . '</p>';
				}
				$html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#f9fafb" style="max-width:560px;border-collapse:separate;border:1px solid #d1d5db;border-radius:6px;background:#f9fafb;">'
					. '<tr><td colspan="2" style="padding:10px 16px;font-size:12px;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:#374151;">'
					. esc_html__( 'Uppgifter från formuläret', 'rockaden-theme' ) . '</td></tr>'
					. $card . '</table>';
				if ( 'leave' === $data['kind'] ) {
					$html .= '<div style="margin:20px 0 0;max-width:560px;padding:12px 16px;background:#fef3c7;border-left:4px solid #d97706;">'
						. '<p style="margin:0 0 6px;font-weight:600;">' . esc_html__( 'Kontrollera innan du tar bort någon', 'rockaden-theme' ) . '</p>'
						. '<p style="margin:0;">' . esc_html( self::leave_check_text() ) . '</p></div>';
				}
				$html .= '<p style="margin:24px 0 8px;"><a href="' . esc_url( $url ) . '" style="display:inline-block;padding:10px 18px;background:#2563eb;color:#ffffff;text-decoration:none;border-radius:4px;font-weight:600;">'
					. esc_html__( 'Öppna förfrågan', 'rockaden-theme' ) . '</a></p>';
				$html .= '<p style="margin:0;color:#6b7280;font-size:13px;">' . esc_html__( 'Markera den som hanterad när ändringen är gjord i medlemsregistret.', 'rockaden-theme' ) . '</p>';
				$html .= '</div>';

				return [ self::title( $data ), $html ];
			}
		);

		Rockaden_Theme_Settings::send_mail(
			$recipient,
			$subject,
			$body,
			[
				'Content-Type: text/html; charset=UTF-8',
				// The bare address, not "Name <address>": see Rockaden_Theme_Feedback::send_email().
				'Reply-To: ' . $data['email'],
			]
		);
	}

	/**
	 * Confirm receipt to the applicant, in the language they used the form in.
	 *
	 * Fixed text with nothing the applicant typed in it — no name, no message —
	 * so the form cannot be used to send someone else arbitrary text.
	 *
	 * @param array<string, string> $data Sanitized record.
	 */
	private static function send_confirmation( array $data ): void {
		$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

		if ( 'leave' === $data['kind'] ) {
			/* translators: %s: site name. */
			$subject = sprintf( __( 'Vi har tagit emot din begäran om utträde – %s', 'rockaden-theme' ), $site );
			/* translators: %s: site name. */
			$intro = sprintf( __( 'Tack! Vi har tagit emot din begäran om utträde ur %s.', 'rockaden-theme' ), $site );
		} else {
			/* translators: %s: site name. */
			$subject = sprintf( __( 'Vi har tagit emot din anmälan – %s', 'rockaden-theme' ), $site );
			/* translators: 1: membership category, 2: site name. */
			$intro = sprintf( __( 'Tack! Vi har tagit emot din anmälan till %1$s hos %2$s.', 'rockaden-theme' ), self::category_label( $data['category'] ), $site );
		}

		$body = implode(
			"\n\n",
			[
				$intro,
				__( 'Vi hör av oss när vi har behandlat den.', 'rockaden-theme' ),
				__( 'Det här är ett automatiskt meddelande. Har du frågor kan du svara på det.', 'rockaden-theme' ),
			]
		);

		$headers   = [];
		$recipient = self::recipient();
		if ( '' !== $recipient ) {
			$headers[] = 'Reply-To: ' . $recipient;
		}

		Rockaden_Theme_Settings::send_mail( $data['email'], $subject, $body, $headers );
	}

	/*
	---------------------------------------------------------------------
	 * Admin: read-only view + handled flow
	 * -------------------------------------------------------------------
	*/

	/**
	 * Register the request and status meta boxes.
	 */
	public static function add_meta_boxes(): void {
		add_meta_box(
			'rc_mr_details',
			__( 'Förfrågan', 'rockaden-theme' ),
			[ self::class, 'render_details_box' ],
			self::POST_TYPE,
			'normal',
			'high'
		);
		add_meta_box(
			'rc_mr_status',
			__( 'Status', 'rockaden-theme' ),
			[ self::class, 'render_status_box' ],
			self::POST_TYPE,
			'side',
			'high'
		);
	}

	/**
	 * A stored request as a field => value record.
	 *
	 * @param int $post_id Request ID.
	 * @return array<string, string>
	 */
	private static function load( int $post_id ): array {
		$data = [];
		foreach ( self::STRING_META as $key ) {
			$data[ substr( $key, strlen( 'rc_mr_' ) ) ] = (string) get_post_meta( $post_id, $key, true );
		}
		return $data;
	}

	/**
	 * Render the read-only request view.
	 *
	 * @param WP_Post $post The request.
	 */
	public static function render_details_box( WP_Post $post ): void {
		$data = self::load( $post->ID );
		?>
		<h2 style="padding:0 0 .5em;font-size:1.3em;"><?php echo esc_html( get_the_title( $post ) ); ?></h2>
		<?php if ( self::used_english( $data ) ) : ?>
			<div class="notice notice-info inline" style="max-width:40rem"><p><?php echo esc_html( self::english_hint() ); ?></p></div>
		<?php endif; ?>
		<table class="widefat striped" style="max-width:40rem">
			<tbody>
			<?php foreach ( self::describe( $data ) as $field => [ $label, $value ] ) : ?>
				<tr>
					<th scope="row" style="width:12rem"><?php echo esc_html( $label ); ?></th>
					<td>
						<?php if ( 'email' === $field || 'guardian2_email' === $field ) : ?>
							<a href="<?php echo esc_url( 'mailto:' . $value ); ?>"><?php echo esc_html( $value ); ?></a>
						<?php elseif ( 'phone' === $field || 'guardian2_phone' === $field ) : ?>
							<a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $value ) ); ?>"><?php echo esc_html( $value ); ?></a>
						<?php else : ?>
							<?php echo esc_html( $value ); ?>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
			<?php if ( '' !== $data['personnummer'] ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Personnummer', 'rockaden-theme' ); ?></th>
					<td><?php echo esc_html( substr( $data['personnummer'], 0, 8 ) . '-' . substr( $data['personnummer'], 8 ) ); ?></td>
				</tr>
			<?php endif; ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Mottagen', 'rockaden-theme' ); ?></th>
					<td><?php echo esc_html( get_the_date( 'Y-m-d H:i', $post ) ); ?></td>
				</tr>
			</tbody>
		</table>
		<?php if ( '' !== trim( $post->post_content ) ) : ?>
			<h3><?php esc_html_e( 'Meddelande', 'rockaden-theme' ); ?></h3>
			<div style="max-width:40rem;white-space:pre-wrap"><?php echo esc_html( $post->post_content ); ?></div>
		<?php endif; ?>
		<?php
	}

	/**
	 * Nonce-protected link that sets a request's status. GET with a nonce, the
	 * same way WordPress's own Trash links work: it is only ever rendered
	 * inside wp-admin, never mailed, so a link scanner cannot reach it.
	 *
	 * @param int    $post_id Request ID.
	 * @param string $to      'handled' or 'new'.
	 */
	private static function status_url( int $post_id, string $to ): string {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=' . self::STATUS_ACTION . '&post=' . $post_id . '&to=' . $to ),
			self::STATUS_ACTION . '_' . $post_id
		);
	}

	/**
	 * Render the status box with its one button.
	 *
	 * @param WP_Post $post The request.
	 */
	public static function render_status_box( WP_Post $post ): void {
		$handled_at = (int) get_post_meta( $post->ID, 'rc_mr_handled_at', true );
		if ( ! $handled_at ) {
			?>
			<p><strong><?php esc_html_e( 'Ny', 'rockaden-theme' ); ?></strong></p>
			<?php if ( 'leave' === get_post_meta( $post->ID, 'rc_mr_kind', true ) ) : ?>
				<div class="notice notice-warning inline"><p><?php echo esc_html( self::leave_check_text() ); ?></p></div>
			<?php endif; ?>
			<p><a class="button button-primary" href="<?php echo esc_url( self::status_url( $post->ID, 'handled' ) ); ?>"><?php esc_html_e( 'Markera som hanterad', 'rockaden-theme' ); ?></a></p>
			<?php
			return;
		}

		$user = get_userdata( (int) get_post_meta( $post->ID, 'rc_mr_handled_by', true ) );
		?>
		<p>
			<strong><?php esc_html_e( 'Hanterad', 'rockaden-theme' ); ?></strong>
			<?php echo esc_html( wp_date( 'Y-m-d H:i', $handled_at ) ); ?>
			<?php if ( $user ) : ?>
				<?php
				/* translators: %s: user display name. */
				echo esc_html( sprintf( __( 'av %s', 'rockaden-theme' ), $user->display_name ) );
				?>
			<?php endif; ?>
		</p>
		<p><a class="button" href="<?php echo esc_url( self::status_url( $post->ID, 'new' ) ); ?>"><?php esc_html_e( 'Markera som ny igen', 'rockaden-theme' ); ?></a></p>
		<p class="description"><?php esc_html_e( 'Ta bort förfrågan (Flytta till papperskorgen) när den inte behövs längre.', 'rockaden-theme' ); ?></p>
		<?php
	}

	/**
	 * Set a request's status (admin-post handler, logged-in users only).
	 */
	public static function handle_status(): void {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		check_admin_referer( self::STATUS_ACTION . '_' . $post_id );

		if ( self::POST_TYPE !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'Du har inte behörighet att ändra den här förfrågan.', 'rockaden-theme' ), 403 );
		}

		$to = isset( $_GET['to'] ) ? sanitize_key( wp_unslash( $_GET['to'] ) ) : '';
		if ( 'handled' === $to ) {
			update_post_meta( $post_id, 'rc_mr_handled_at', time() );
			update_post_meta( $post_id, 'rc_mr_handled_by', get_current_user_id() );
		} elseif ( 'new' === $to ) {
			delete_post_meta( $post_id, 'rc_mr_handled_at' );
			delete_post_meta( $post_id, 'rc_mr_handled_by' );
		}

		// Back to wherever the button was pressed (edit screen or list).
		wp_safe_redirect( wp_get_referer() ?: admin_url( 'edit.php?post_type=' . self::POST_TYPE ) );
		exit;
	}

	/**
	 * Append the number of new requests to the admin menu item, with the
	 * markup WordPress uses for the Comments / Plugins counts. Done on
	 * add_menu_classes, which hands over the built menu: post type labels
	 * are escaped, so the badge cannot go in the label itself.
	 *
	 * @param array<int, array<int, string>> $menu The admin menu.
	 * @return array<int, array<int, string>>
	 */
	public static function add_menu_bubble( array $menu ): array {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return $menu;
		}
		$count = self::count_new();
		if ( ! $count ) {
			return $menu;
		}
		$slug = 'edit.php?post_type=' . self::POST_TYPE;
		foreach ( $menu as $index => $item ) {
			if ( isset( $item[2] ) && $slug === $item[2] ) {
				$menu[ $index ][0] .= sprintf( ' <span class="awaiting-mod count-%1$d"><span class="pending-count">%1$d</span></span>', $count );
				break;
			}
		}
		return $menu;
	}

	/**
	 * Number of requests not yet handled. Counted in PHP over the (few) stored
	 * requests; WP_Query primes their meta in one query.
	 */
	private static function count_new(): int {
		$ids = get_posts(
			[
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			]
		);
		update_postmeta_cache( $ids );
		$new = array_filter( $ids, fn ( $id ) => ! get_post_meta( (int) $id, 'rc_mr_handled_at', true ) );
		return count( $new );
	}

	/*
	---------------------------------------------------------------------
	 * Admin list
	 * -------------------------------------------------------------------
	*/

	/**
	 * List columns: type, category, email, status.
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string>
	 */
	public static function columns( array $columns ): array {
		$new = [];
		foreach ( $columns as $key => $label ) {
			if ( 'title' === $key ) {
				$new['title']          = __( 'Förfrågan', 'rockaden-theme' );
				$new['rc_mr_kind']     = __( 'Typ', 'rockaden-theme' );
				$new['rc_mr_category'] = __( 'Kategori', 'rockaden-theme' );
				$new['rc_mr_email']    = __( 'E-post', 'rockaden-theme' );
				$new['rc_mr_status']   = __( 'Status', 'rockaden-theme' );
			} elseif ( 'date' === $key ) {
				$new['date'] = __( 'Mottagen', 'rockaden-theme' );
			} else {
				$new[ $key ] = $label;
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
	public static function column_content( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'rc_mr_kind':
				echo esc_html( self::kind_label( (string) get_post_meta( $post_id, 'rc_mr_kind', true ) ) );
				break;
			case 'rc_mr_category':
				echo esc_html( self::category_label( (string) get_post_meta( $post_id, 'rc_mr_category', true ) ) );
				break;
			case 'rc_mr_email':
				$email = (string) get_post_meta( $post_id, 'rc_mr_email', true );
				if ( $email ) {
					printf( '<a href="%s">%s</a>', esc_url( 'mailto:' . $email ), esc_html( $email ) );
				}
				break;
			case 'rc_mr_status':
				$handled_at = (int) get_post_meta( $post_id, 'rc_mr_handled_at', true );
				if ( $handled_at ) {
					echo esc_html( __( 'Hanterad', 'rockaden-theme' ) . ' ' . wp_date( 'Y-m-d', $handled_at ) );
				} else {
					echo '<strong>' . esc_html__( 'Ny', 'rockaden-theme' ) . '</strong>';
				}
				break;
		}
	}

	/**
	 * Row actions: drop Quick Edit (there is nothing to edit) and add a
	 * one-click status change.
	 *
	 * @param array<string, string> $actions Row actions.
	 * @param WP_Post               $post    The row's post.
	 * @return array<string, string>
	 */
	public static function row_actions( array $actions, WP_Post $post ): array {
		if ( self::POST_TYPE !== $post->post_type ) {
			return $actions;
		}
		unset( $actions['inline hide-if-no-js'] );

		if ( isset( $actions['edit'] ) ) {
			$actions['edit'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( (string) get_edit_post_link( $post->ID ) ),
				esc_html__( 'Visa', 'rockaden-theme' )
			);
		}
		if ( current_user_can( 'edit_post', $post->ID ) ) {
			$handled                 = (bool) get_post_meta( $post->ID, 'rc_mr_handled_at', true );
			$actions['rc_mr_status'] = sprintf(
				'<a href="%s">%s</a>',
				esc_url( self::status_url( $post->ID, $handled ? 'new' : 'handled' ) ),
				$handled ? esc_html__( 'Markera som ny igen', 'rockaden-theme' ) : esc_html__( 'Markera som hanterad', 'rockaden-theme' )
			);
		}
		return $actions;
	}

	/**
	 * Bulk actions: only Move to Trash. There is nothing to bulk-edit.
	 *
	 * @param array<string, string> $actions Bulk actions.
	 * @return array<string, string>
	 */
	public static function bulk_actions( array $actions ): array {
		unset( $actions['edit'] );
		return $actions;
	}

	/**
	 * Make the list filters public query vars, so edit.php passes them from
	 * the URL into the list query.
	 *
	 * @param array<int, string> $vars Query vars.
	 * @return array<int, string>
	 */
	public static function register_query_var( array $vars ): array {
		return array_merge( $vars, array_keys( self::LIST_FILTERS ) );
	}

	/**
	 * The options for each list filter dropdown: value => label, '' first.
	 *
	 * @return array<string, array<string, string>> Query var => options.
	 */
	private static function filter_choices(): array {
		$categories = [ '' => __( 'Alla kategorier', 'rockaden-theme' ) ];
		foreach ( self::CATEGORIES as $category ) {
			$categories[ $category ] = self::category_label( $category );
		}

		// Schools come from the requests themselves rather than from the
		// settings, so a school since removed from the list can still be
		// found. Requests are few and get deleted, so reading them is cheap.
		$schools = [ '' => __( 'Alla skolor', 'rockaden-theme' ) ];
		$ids     = get_posts(
			[
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			]
		);
		update_postmeta_cache( $ids );
		$names = [];
		foreach ( $ids as $id ) {
			$school = (string) get_post_meta( (int) $id, 'rc_mr_school', true );
			if ( '' !== $school ) {
				$names[ $school ] = $school;
			}
		}
		ksort( $names, SORT_LOCALE_STRING );

		return [
			self::STATUS_QUERY_VAR => [
				''        => __( 'Alla statusar', 'rockaden-theme' ),
				'new'     => __( 'Nya', 'rockaden-theme' ),
				'handled' => __( 'Hanterade', 'rockaden-theme' ),
			],
			'rc_mr_kind'           => [
				''      => __( 'Alla typer', 'rockaden-theme' ),
				'join'  => self::kind_label( 'join' ),
				'leave' => self::kind_label( 'leave' ),
			],
			'rc_mr_category'       => $categories,
			'rc_mr_school'         => $schools + $names,
		];
	}

	/**
	 * Status, type, category and school dropdowns above the list.
	 *
	 * @param string $post_type Post type being listed.
	 */
	public static function status_filter( string $post_type ): void {
		if ( self::POST_TYPE !== $post_type ) {
			return;
		}
		foreach ( self::filter_choices() as $var => $choices ) {
			// A school filter with no schools to choose from is just noise.
			if ( 'rc_mr_school' === $var && count( $choices ) < 2 ) {
				continue;
			}
			$current = (string) get_query_var( $var );
			echo '<select name="' . esc_attr( $var ) . '">';
			foreach ( $choices as $value => $label ) {
				printf( '<option value="%s"%s>%s</option>', esc_attr( (string) $value ), selected( $current, (string) $value, false ), esc_html( $label ) );
			}
			echo '</select>';
		}
	}

	/**
	 * Apply the list filters to the admin list query. Each one is a meta
	 * condition; together they must all hold.
	 *
	 * @param WP_Query $query The query.
	 */
	public static function apply_status_filter( WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() || self::POST_TYPE !== $query->get( 'post_type' ) ) {
			return;
		}

		$conditions = [];

		$status = (string) $query->get( self::STATUS_QUERY_VAR );
		if ( 'new' === $status || 'handled' === $status ) {
			$conditions[] = [
				'key'     => 'rc_mr_handled_at',
				'compare' => 'new' === $status ? 'NOT EXISTS' : 'EXISTS',
			];
		}

		foreach ( [ 'rc_mr_kind', 'rc_mr_category', 'rc_mr_school' ] as $var ) {
			$value = (string) $query->get( $var );
			if ( '' !== $value ) {
				$conditions[] = [
					'key'   => $var,
					'value' => $value,
				];
			}
		}

		if ( $conditions ) {
			$query->set( 'meta_query', array_merge( [ 'relation' => 'AND' ], $conditions ) );
		}
	}
}
