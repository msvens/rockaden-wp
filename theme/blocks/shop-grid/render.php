<?php
/**
 * Server-side render for the Shop Items block.
 *
 * @package RockadenTheme
 *
 * @var array<string, mixed> $attributes Block attributes.
 */

defined('ABSPATH') || exit;

$count      = isset($attributes['count']) ? (int) $attributes['count'] : 0;
$more_url   = (string) ($attributes['moreUrl'] ?? '/shop');
$more_label = (string) ($attributes['moreLabel'] ?? __('Mer schackmaterial', 'rockaden-theme'));
$layout     = isset($attributes['layout']) && 'column' === $attributes['layout'] ? 'column' : 'grid';
$condensed  = isset($attributes['display']) && 'condensed' === $attributes['display'];

// count: 0 (or any non-positive value) means "show all" — used on the full shop
// page. Positive values cap the result set, used by the front-page teaser.
$posts_per_page = $count > 0 ? $count : -1;

$shop_posts = get_posts(
	[
		'post_type'      => Rockaden_Theme_Shop::POST_TYPE,
		'post_status'    => 'publish',
		'posts_per_page' => $posts_per_page,
		'orderby'        => 'menu_order date',
		'order'          => 'ASC',
	]
);

$items = array_map([Rockaden_Theme_Shop::class, 'format_item'], $shop_posts);

// The "more" link only makes sense when the grid is actually hiding items.
// On a show-all grid (count <= 0) or when everything already fits, it must
// never appear (it would just link back to the page you're on).
$total_published = (int) wp_count_posts(Rockaden_Theme_Shop::POST_TYPE)->publish;
$show_more       = $count > 0 && $total_published > $count && '' !== $more_url;

$classes = 'rockaden-shop-grid rockaden-shop-grid--' . $layout;
if ($condensed) {
	$classes .= ' rockaden-shop-grid--condensed';
}

$wrapper_attributes = get_block_wrapper_attributes(['class' => $classes]);
?>
<div <?php echo wp_kses_post($wrapper_attributes); ?>>
	<?php if (empty($items)) : ?>
		<p class="rockaden-shop-grid__empty"><?php esc_html_e('No shop items yet.', 'rockaden-theme'); ?></p>
	<?php else : ?>
		<ul class="rockaden-shop-grid__list">
			<?php foreach ($items as $item) : ?>
				<li class="rockaden-shop-card">
					<?php if ($item['imageUrl']) : ?>
						<figure class="rockaden-shop-card__media">
							<img src="<?php echo esc_url($item['imageUrl']); ?>" alt="<?php echo esc_attr($item['imageAlt'] ?: $item['title']); ?>" loading="lazy" />
						</figure>
					<?php endif; ?>
					<div class="rockaden-shop-card__body">
						<h3 class="rockaden-shop-card__title"><?php echo esc_html($item['title']); ?></h3>

						<?php if (! $condensed) : ?>
							<?php if ('out_of_stock' === ($item['stockStatus'] ?? 'in_stock')) : ?>
								<p class="rockaden-shop-card__stock rockaden-shop-card__stock--out">
									<?php esc_html_e('Slut i lager', 'rockaden-theme'); ?>
								</p>
							<?php endif; ?>
							<?php if ($item['salePrice']) : ?>
								<p class="rockaden-shop-card__price">
									<s class="rockaden-shop-card__price-original"><?php echo esc_html($item['price']); ?></s>
									<span class="rockaden-shop-card__price-sale"><?php echo esc_html($item['salePrice']); ?></span>
								</p>
							<?php elseif ($item['price']) : ?>
								<p class="rockaden-shop-card__price">
									<span><?php echo esc_html($item['price']); ?></span>
								</p>
							<?php endif; ?>
							<?php if (! empty($item['content'])) : ?>
								<div class="rockaden-shop-card__desc">
									<?php echo wp_kses_post(wpautop(do_blocks($item['content']))); ?>
								</div>
							<?php endif; ?>
							<?php if (! empty($item['howToOrder'])) : ?>
								<p class="rockaden-shop-card__instructions">
									<?php echo esc_html($item['howToOrder']); ?>
								</p>
							<?php endif; ?>
							<?php if ($item['buyUrl']) : ?>
								<a href="<?php echo esc_url($item['buyUrl']); ?>" class="rockaden-shop-card__buy">
									<?php esc_html_e('Köp', 'rockaden-theme'); ?>
								</a>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ($show_more) : ?>
		<p class="rockaden-shop-grid__more">
			<a href="<?php echo esc_url($more_url); ?>" class="rockaden-more-link">
				<?php echo esc_html($more_label); ?>
			</a>
		</p>
	<?php endif; ?>
</div>
