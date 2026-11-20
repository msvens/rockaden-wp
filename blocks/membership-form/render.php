<?php
/**
 * Server-side render for the Membership Form block.
 *
 * Renders the whole form; view.js submits it through the REST endpoint. Field
 * groups that only apply to some categories carry data-for="<slugs>" and start
 * out hidden and disabled (a disabled fieldset's inputs are neither required
 * nor submitted); view.js switches them as the category changes. When the
 * block fixes the category, the right groups are rendered visible here.
 *
 * Labels are translated server-side (respecting the visitor's cookie locale);
 * the messages view.js needs are passed as data-* attributes. Headings and
 * introductions are ordinary heading / paragraph blocks placed around this one.
 *
 * @package RockadenTheme
 *
 * @var array<string, mixed> $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$variant = in_array( $attributes['variant'] ?? 'join', Rockaden_Theme_Membership::KINDS, true ) ? (string) $attributes['variant'] : 'join';
$is_join = 'join' === $variant;
// Only a join request has a category: a member is in exactly one, which the
// club's register already knows, so the leave form does not ask.
$fixed   = $is_join && in_array( $attributes['category'] ?? '', Rockaden_Theme_Membership::CATEGORIES, true ) ? (string) $attributes['category'] : '';
$use_pnr = Rockaden_Theme_Membership::uses_personnummer();
$schools = Rockaden_Theme_Membership::schools();
$uid     = wp_unique_id( 'rc-mr-' );

/**
 * Print a category-specific group's attributes: data-for, plus hidden +
 * disabled unless the fixed category is one the group belongs to.
 *
 * @param array<int, string> $categories Categories the group applies to.
 */
$group_attrs = static function ( array $categories ) use ( $fixed ): void {
	echo ' data-for="' . esc_attr( implode( ' ', $categories ) ) . '"';
	if ( ! in_array( $fixed, $categories, true ) ) {
		echo ' hidden disabled';
	}
};

/**
 * The marker on a required field's label. Hidden from assistive technology,
 * which already announces the input's own required attribute.
 */
$required_marker = static function (): void {
	echo ' <span class="rockaden-membership__req" aria-hidden="true">*</span>';
};

/**
 * One labelled input. $id_suffix keeps ids unique when the same field name
 * appears in two groups (only one of which is ever enabled).
 *
 * @param string               $name      Field name (matches the REST param).
 * @param string               $label     Visible label.
 * @param array<string, mixed> $input     Extra input attributes (true = bare attribute).
 * @param string               $hint      Optional hint below the input.
 * @param string               $id_suffix Optional id suffix.
 */
$field = static function ( string $name, string $label, array $input = [], string $hint = '', string $id_suffix = '' ) use ( $uid, $required_marker ): void {
	$id = $uid . '-' . $name . $id_suffix;
	?>
	<p class="rockaden-membership__field">
		<label for="<?php echo esc_attr( $id ); ?>">
			<?php
			echo esc_html( $label );
			if ( ! empty( $input['required'] ) ) {
				$required_marker();
			}
			?>
		</label>
		<input id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>"
		<?php
		foreach ( array_merge( [ 'type' => 'text' ], $input ) as $key => $value ) {
			echo true === $value
				? ' ' . esc_attr( $key )
				: ' ' . esc_attr( $key ) . '="' . esc_attr( (string) $value ) . '"';
		}
		?>
		/>
		<?php if ( '' !== $hint ) : ?>
			<span class="rockaden-membership__hint"><?php echo esc_html( $hint ); ?></span>
		<?php endif; ?>
	</p>
	<?php
};

/**
 * Street, postal code and city. The same fields appear in two groups (the
 * guardian's and an adult member's, only one ever enabled), and once more
 * for a second guardian who lives elsewhere, hence the name prefix and the
 * id suffix.
 *
 * @param string $prefix    Field name prefix: '' or 'guardian2_'.
 * @param string $id_suffix Id suffix, to keep ids unique.
 * @param bool   $required  Whether the browser should require the fields.
 */
$address_fields = static function ( string $prefix, string $id_suffix, bool $required ) use ( $field ): void {
	// A separate autofill section, so the browser does not fill the second
	// guardian's address with the first one's.
	$section = '' === $prefix ? '' : 'section-guardian2 ';
	$field(
		$prefix . 'street',
		__( 'Gatuadress', 'rockaden-theme' ),
		[
			'required'     => $required,
			'maxlength'    => 200,
			'autocomplete' => $section . 'street-address',
		],
		'',
		$id_suffix
	);
	?>
	<div class="rockaden-membership__row">
		<?php
		$field(
			$prefix . 'postal_code',
			__( 'Postnummer', 'rockaden-theme' ),
			[
				'required'     => $required,
				'maxlength'    => 6,
				'inputmode'    => 'numeric',
				'autocomplete' => $section . 'postal-code',
			],
			'',
			$id_suffix
		);
		$field(
			$prefix . 'city',
			__( 'Ort', 'rockaden-theme' ),
			[
				'required'     => $required,
				'maxlength'    => 100,
				'autocomplete' => $section . 'address-level2',
			],
			'',
			$id_suffix
		);
		?>
	</div>
	<?php
};

$wrapper_attributes = get_block_wrapper_attributes( [ 'class' => 'rockaden-membership-block' ] );
?>
<div <?php echo wp_kses_post( $wrapper_attributes ); ?>>
	<form
		class="rockaden-membership"
		data-context-url="<?php echo esc_url( rest_url( 'rockaden/v1/member-requests/context' ) ); ?>"
		data-submit-url="<?php echo esc_url( rest_url( 'rockaden/v1/member-requests' ) ); ?>"
		data-sending="<?php esc_attr_e( 'Skickar…', 'rockaden-theme' ); ?>"
		data-success="<?php echo esc_attr( $is_join ? __( 'Tack! Vi har tagit emot din anmälan och skickat en bekräftelse till din e-post. Vi hör av oss när vi har gått igenom den.', 'rockaden-theme' ) : __( 'Tack! Vi har tagit emot din begäran om utträde och skickat en bekräftelse till din e-post. Vi hör av oss innan något ändras.', 'rockaden-theme' ) ); ?>"
		data-error="<?php esc_attr_e( 'Något gick fel. Försök igen.', 'rockaden-theme' ); ?>"
		<?php if ( ! $is_join ) : ?>
			data-confirm-fallback="<?php esc_attr_e( 'Skicka begäran om utträde?', 'rockaden-theme' ); ?>"
		<?php endif; ?>
	>
		<input type="hidden" name="kind" value="<?php echo esc_attr( $variant ); ?>" />
		<input type="hidden" name="locale" value="<?php echo esc_attr( Rockaden_Theme_I18n::current_locale() ); ?>" />

		<p class="rockaden-membership__legend-note">
			<span aria-hidden="true">*</span> <?php esc_html_e( 'Obligatoriskt fält', 'rockaden-theme' ); ?>
		</p>

		<?php if ( ! $is_join ) : ?>
			<?php // No category on a leave request: see $fixed above. ?>
		<?php elseif ( '' !== $fixed ) : ?>
			<input type="hidden" name="category" value="<?php echo esc_attr( $fixed ); ?>" />
		<?php else : ?>
			<p class="rockaden-membership__field">
				<label for="<?php echo esc_attr( $uid ); ?>-category"><?php esc_html_e( 'Kategori', 'rockaden-theme' ); ?><?php $required_marker(); ?></label>
				<select id="<?php echo esc_attr( $uid ); ?>-category" name="category" required>
					<option value=""><?php esc_html_e( 'Välj…', 'rockaden-theme' ); ?></option>
					<?php foreach ( Rockaden_Theme_Membership::CATEGORIES as $category ) : ?>
						<option value="<?php echo esc_attr( $category ); ?>"><?php echo esc_html( Rockaden_Theme_Membership::category_label( $category ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
		<?php endif; ?>

		<?php // No heading here: the page around the form says who it is for. ?>
		<div class="rockaden-membership__group">
			<div class="rockaden-membership__row">
				<?php
				$field(
					'first_name',
					__( 'Förnamn', 'rockaden-theme' ),
					[
						'required'  => true,
						'maxlength' => 100,
					]
				);
				$field(
					'last_name',
					__( 'Efternamn', 'rockaden-theme' ),
					[
						'required'  => true,
						'maxlength' => 100,
					]
				);
				?>
			</div>
			<?php
			if ( $use_pnr ) {
				$field(
					'personnummer',
					__( 'Personnummer', 'rockaden-theme' ),
					[
						'required'     => true,
						'maxlength'    => 15,
						'inputmode'    => 'numeric',
						'autocomplete' => 'off',
						'placeholder'  => __( 'ÅÅÅÅMMDD-NNNN', 'rockaden-theme' ),
					]
				);
			} else {
				$field(
					'birthdate',
					__( 'Födelsedatum', 'rockaden-theme' ),
					[
						'required'     => true,
						'maxlength'    => 10,
						'inputmode'    => 'numeric',
						'autocomplete' => 'off',
						'placeholder'  => __( 'ÅÅÅÅ-MM-DD', 'rockaden-theme' ),
					]
				);
			}
			?>
		</div>

		<?php if ( $is_join ) : ?>
			<fieldset class="rockaden-membership__group"<?php $group_attrs( [ 'skolschack' ] ); ?>>
				<legend><?php esc_html_e( 'Skola', 'rockaden-theme' ); ?></legend>
				<div class="rockaden-membership__row">
					<?php if ( $schools ) : ?>
						<p class="rockaden-membership__field">
							<label for="<?php echo esc_attr( $uid ); ?>-school"><?php esc_html_e( 'Skola', 'rockaden-theme' ); ?><?php $required_marker(); ?></label>
							<select id="<?php echo esc_attr( $uid ); ?>-school" name="school" required>
								<option value=""><?php esc_html_e( 'Välj skola…', 'rockaden-theme' ); ?></option>
								<?php foreach ( $schools as $school ) : ?>
									<option value="<?php echo esc_attr( $school ); ?>"><?php echo esc_html( $school ); ?></option>
								<?php endforeach; ?>
							</select>
						</p>
						<?php
					else :
						$field(
							'school',
							__( 'Skola', 'rockaden-theme' ),
							[
								'required'  => true,
								'maxlength' => 200,
							]
						);
					endif;
					$field(
						'grade',
						__( 'Årskurs/klass', 'rockaden-theme' ),
						[
							'required'  => true,
							'maxlength' => 20,
						]
					);
			?>
				</div>
			</fieldset>

			<fieldset class="rockaden-membership__group"<?php $group_attrs( Rockaden_Theme_Membership::JUNIOR_CATEGORIES ); ?>>
				<legend><?php esc_html_e( 'Vårdnadshavare', 'rockaden-theme' ); ?></legend>
				<?php
				$field(
					'guardian_name',
					__( 'Namn', 'rockaden-theme' ),
					[
						'required'     => true,
						'maxlength'    => 200,
						'autocomplete' => 'name',
					]
				);
				?>
				<div class="rockaden-membership__row">
					<?php
					$field(
						'email',
						__( 'E-post', 'rockaden-theme' ),
						[
							'type'         => 'email',
							'required'     => true,
							'autocomplete' => 'email',
						],
						'',
						'-guardian'
					);
					$field(
						'phone',
						__( 'Telefon (valfritt)', 'rockaden-theme' ),
						[
							'type'         => 'tel',
							'maxlength'    => 30,
							'autocomplete' => 'tel',
						],
						'',
						'-guardian'
					);
					?>
				</div>
				<?php $address_fields( '', '-guardian', true ); ?>

				<?php
				// Optional second guardian. Its fields are sent even while the
				// disclosure is closed, but empty, and the server ignores an
				// empty second guardian.
				?>
				<details class="rockaden-membership__more">
					<summary><?php esc_html_e( 'Lägg till en vårdnadshavare till', 'rockaden-theme' ); ?></summary>
					<?php
					$field(
						'guardian2_name',
						__( 'Namn', 'rockaden-theme' ),
						[
							'maxlength'    => 200,
							'autocomplete' => 'section-guardian2 name',
						]
					);
					?>
					<div class="rockaden-membership__row">
						<?php
						$field(
							'guardian2_email',
							__( 'E-post (valfritt)', 'rockaden-theme' ),
							[
								'type'         => 'email',
								'autocomplete' => 'section-guardian2 email',
							]
						);
						$field(
							'guardian2_phone',
							__( 'Telefon (valfritt)', 'rockaden-theme' ),
							[
								'type'         => 'tel',
								'maxlength'    => 30,
								'autocomplete' => 'section-guardian2 tel',
							]
						);
						?>
					</div>
					<p class="rockaden-membership__check">
						<label>
							<input type="checkbox" name="guardian2_own_address" value="1" />
							<?php esc_html_e( 'Bor på en annan adress', 'rockaden-theme' ); ?>
						</label>
					</p>
					<?php // Enabled by view.js when the box is ticked. ?>
					<fieldset class="rockaden-membership__group" data-own-address hidden disabled>
						<?php $address_fields( 'guardian2_', '', true ); ?>
					</fieldset>
				</details>
			</fieldset>

			<fieldset class="rockaden-membership__group"<?php $group_attrs( [ 'vuxen', 'pensionar' ] ); ?>>
				<legend><?php esc_html_e( 'Kontaktuppgifter', 'rockaden-theme' ); ?></legend>
				<div class="rockaden-membership__row">
					<?php
					$field(
						'email',
						__( 'E-post', 'rockaden-theme' ),
						[
							'type'         => 'email',
							'required'     => true,
							'autocomplete' => 'email',
						]
					);
					$field(
						'phone',
						__( 'Telefon (valfritt)', 'rockaden-theme' ),
						[
							'type'         => 'tel',
							'maxlength'    => 30,
							'autocomplete' => 'tel',
						]
					);
					?>
				</div>
				<?php $address_fields( '', '', true ); ?>
			</fieldset>
		<?php else : ?>
			<?php
			$field(
				'email',
				__( 'E-post', 'rockaden-theme' ),
				[
					'type'         => 'email',
					'required'     => true,
					'autocomplete' => 'email',
				],
				__( 'Vi skickar en bekräftelse hit.', 'rockaden-theme' )
			);
			?>
		<?php endif; ?>

		<p class="rockaden-membership__field">
			<label for="<?php echo esc_attr( $uid ); ?>-message"><?php esc_html_e( 'Meddelande (valfritt)', 'rockaden-theme' ); ?></label>
			<textarea id="<?php echo esc_attr( $uid ); ?>-message" name="message" rows="4" maxlength="5000"></textarea>
		</p>

		<?php // Honeypot: hidden from people, tempting to bots. ?>
		<div class="rockaden-membership__hp" aria-hidden="true">
			<label for="<?php echo esc_attr( $uid ); ?>-website"><?php esc_html_e( 'Webbplats', 'rockaden-theme' ); ?></label>
			<input type="text" id="<?php echo esc_attr( $uid ); ?>-website" name="website" tabindex="-1" autocomplete="off" />
		</div>

		<button type="submit" class="rockaden-membership__submit" disabled>
			<?php echo esc_html( $is_join ? __( 'Skicka anmälan', 'rockaden-theme' ) : __( 'Skicka begäran', 'rockaden-theme' ) ); ?>
		</button>
		<p class="rockaden-membership__status" role="status" aria-live="polite"></p>
	</form>

	<?php
	// Leave requests ask once more before sending. Outside the <form>: a
	// dialog's buttons must not submit it, and forms cannot nest.
	if ( ! $is_join ) :
		?>
		<dialog class="rockaden-membership__dialog rockaden-membership__confirm" aria-labelledby="<?php echo esc_attr( $uid ); ?>-confirm-title">
			<h2 id="<?php echo esc_attr( $uid ); ?>-confirm-title" class="rockaden-membership__dialog-title"><?php esc_html_e( 'Skicka begäran om utträde?', 'rockaden-theme' ); ?></h2>
			<p><?php esc_html_e( 'Begäran skickas till klubben. Medlemskapet avslutas inte förrän vi har kontaktat dig och bekräftat den.', 'rockaden-theme' ); ?></p>
			<div class="rockaden-membership__dialog-actions">
				<button type="button" class="rockaden-membership__submit" data-confirm="cancel"><?php esc_html_e( 'Avbryt', 'rockaden-theme' ); ?></button>
				<button type="button" class="rockaden-membership__submit" data-confirm="ok"><?php esc_html_e( 'Ja, skicka begäran', 'rockaden-theme' ); ?></button>
			</div>
		</dialog>
	<?php endif; ?>

	<?php
	// Shown after a successful submission. Says what happens next: a person
	// handles the request, so the visitor should expect it to take a while.
	?>
	<dialog class="rockaden-membership__dialog rockaden-membership__done" aria-labelledby="<?php echo esc_attr( $uid ); ?>-done-title">
		<h2 id="<?php echo esc_attr( $uid ); ?>-done-title" class="rockaden-membership__dialog-title">
			<?php echo esc_html( $is_join ? __( 'Tack för din anmälan!', 'rockaden-theme' ) : __( 'Tack, vi har tagit emot din begäran', 'rockaden-theme' ) ); ?>
		</h2>
		<p>
			<?php
			echo esc_html(
				$is_join
					? __( 'En bekräftelse är på väg till din e-post. Anmälan gås igenom av en person i klubben, så det kan dröja några dagar innan vi hör av oss. Medlemskapet börjar när vi har bekräftat det.', 'rockaden-theme' )
					: __( 'En bekräftelse är på väg till din e-post. Begäran gås igenom av en person i klubben, så det kan dröja några dagar. Medlemskapet avslutas först när vi har kontaktat dig.', 'rockaden-theme' )
			);
			?>
		</p>
		<div class="rockaden-membership__dialog-actions">
			<button type="button" class="rockaden-membership__submit" data-done="close"><?php esc_html_e( 'Stäng', 'rockaden-theme' ); ?></button>
		</div>
	</dialog>

	<noscript><?php esc_html_e( 'Aktivera JavaScript för att skicka formuläret.', 'rockaden-theme' ); ?></noscript>
</div>
