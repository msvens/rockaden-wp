<?php
/**
 * Server-side render for the Feedback Form block.
 *
 * Renders a real <form> (works as a normal page element); view.js progressively
 * enhances submission via the REST endpoint. Labels are translated server-side
 * (respecting the visitor's cookie locale); the status messages view.js needs
 * are passed as data-* attributes so the no-build view script stays i18n-free.
 *
 * @package RockadenTheme
 *
 * @var array<string, mixed> $attributes Block attributes.
 */

defined('ABSPATH') || exit;

$heading = trim((string) ($attributes['heading'] ?? ''));
$intro   = trim((string) ($attributes['intro'] ?? ''));
$centered = isset($attributes['formAlign']) && 'center' === $attributes['formAlign'];

$inner_class = 'rockaden-feedback__inner' . ($centered ? ' rockaden-feedback__inner--center' : '');

$uid = wp_unique_id('rc-fb-');

$wrapper_attributes = get_block_wrapper_attributes(['class' => 'rockaden-feedback-block']);
?>
<div <?php echo wp_kses_post($wrapper_attributes); ?>>
	<div class="<?php echo esc_attr($inner_class); ?>">
	<?php if ('' !== $heading) : ?>
		<h2 class="rockaden-feedback__heading"><?php echo esc_html($heading); ?></h2>
	<?php endif; ?>
	<?php if ('' !== $intro) : ?>
		<p class="rockaden-feedback__intro"><?php echo esc_html($intro); ?></p>
	<?php endif; ?>

	<form
		class="rockaden-feedback"
		data-rest-url="<?php echo esc_url(rest_url('rockaden/v1/feedback')); ?>"
		data-nonce="<?php echo esc_attr(wp_create_nonce('wp_rest')); ?>"
		data-sending="<?php esc_attr_e('Skickar…', 'rockaden-theme'); ?>"
		data-success="<?php esc_attr_e('Tack! Ditt meddelande har skickats.', 'rockaden-theme'); ?>"
		data-error="<?php esc_attr_e('Något gick fel. Försök igen.', 'rockaden-theme'); ?>"
	>
		<p class="rockaden-feedback__field">
			<label for="<?php echo esc_attr($uid); ?>-name"><?php esc_html_e('Namn', 'rockaden-theme'); ?></label>
			<input type="text" id="<?php echo esc_attr($uid); ?>-name" name="name" required maxlength="200" autocomplete="name" />
		</p>
		<p class="rockaden-feedback__field">
			<label for="<?php echo esc_attr($uid); ?>-email"><?php esc_html_e('E-post', 'rockaden-theme'); ?></label>
			<input type="email" id="<?php echo esc_attr($uid); ?>-email" name="email" autocomplete="email" />
			<span class="rockaden-feedback__hint"><?php esc_html_e('Valfritt – fyll i om du vill att vi ska kunna kontakta dig.', 'rockaden-theme'); ?></span>
		</p>
		<p class="rockaden-feedback__field">
			<label for="<?php echo esc_attr($uid); ?>-message"><?php esc_html_e('Meddelande', 'rockaden-theme'); ?></label>
			<textarea id="<?php echo esc_attr($uid); ?>-message" name="message" rows="5" required maxlength="5000"></textarea>
		</p>

		<?php // Honeypot: hidden from people, tempting to bots. ?>
		<div class="rockaden-feedback__hp" aria-hidden="true">
			<label for="<?php echo esc_attr($uid); ?>-website"><?php esc_html_e('Webbplats', 'rockaden-theme'); ?></label>
			<input type="text" id="<?php echo esc_attr($uid); ?>-website" name="website" tabindex="-1" autocomplete="off" />
		</div>

		<button type="submit" class="rockaden-feedback__submit"><?php esc_html_e('Skicka', 'rockaden-theme'); ?></button>
		<p class="rockaden-feedback__status" role="status" aria-live="polite"></p>
	</form>

	<noscript><?php esc_html_e('Aktivera JavaScript för att skicka feedback.', 'rockaden-theme'); ?></noscript>
	</div>
</div>
