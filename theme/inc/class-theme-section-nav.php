<?php
/**
 * Section navigation helper.
 *
 * Single source of truth for "what is the section menu for a given page" so the
 * front-end block (blocks/section-nav/render.php) and the editor's "Section menu"
 * meta box (Rockaden_Theme_Settings) compute identical results.
 *
 * The menu is derived from page hierarchy: the section root is the page's parent
 * (or the page itself when it is top-level), and the menu is the root followed by
 * the root's direct child pages, ordered by menu_order then title. Each page may
 * override how it appears via the rc_section_menu_label meta.
 *
 * @package Rockaden
 */

defined('ABSPATH') || exit;

class Rockaden_Theme_Section_Nav {

	/**
	 * Meta key holding a page's display label override in section menus.
	 */
	public const LABEL_META_KEY = 'rc_section_menu_label';

	/**
	 * Meta key flagging a page as hidden from its section menu ('1' or absent).
	 */
	public const HIDDEN_META_KEY = 'rc_section_menu_hidden';

	/**
	 * Register the per-page section-menu meta so the editor panel can edit any
	 * item's label and visibility via the REST API. Called on init.
	 */
	public static function register(): void {
		$args = [
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => [self::class, 'can_edit_meta'],
			'default'           => '',
		];
		register_post_meta('page', self::LABEL_META_KEY, $args);
		register_post_meta('page', self::HIDDEN_META_KEY, $args);
	}

	/**
	 * Write permission for the section-menu meta: users who can edit other
	 * people's pages (the same gate as the panel's interactive controls), since
	 * editing the menu rewrites attributes on sibling/parent pages.
	 *
	 * @return bool
	 */
	public static function can_edit_meta(): bool {
		return current_user_can('edit_others_pages');
	}

	/**
	 * Resolve the section root for a page: its parent if it has one, else itself.
	 *
	 * @param int $page_id Page ID.
	 * @return int Root page ID, or 0 if the ID is not a valid page.
	 */
	public static function get_root_id(int $page_id): int {
		$post = get_post($page_id);
		if (!$post instanceof WP_Post || 'page' !== $post->post_type) {
			return 0;
		}

		return $post->post_parent ? (int) $post->post_parent : (int) $post->ID;
	}

	/**
	 * Effective display label for a page in section menus: the override meta when
	 * non-empty, otherwise the page title.
	 *
	 * @param int $page_id Page ID.
	 * @return string
	 */
	public static function get_label(int $page_id): string {
		$override = get_post_meta($page_id, self::LABEL_META_KEY, true);
		if (is_string($override) && '' !== trim($override)) {
			return $override;
		}

		return get_the_title($page_id);
	}

	/**
	 * Build the ordered section menu for a page.
	 *
	 * Returns an empty array when the page is not part of a section (the root has
	 * no child pages) — both the front-end render and the meta box treat [] as the
	 * "no section menu here" signal.
	 *
	 * @param int $page_id Page ID.
	 * @return list<array{id:int,label:string,url:string,is_current:bool,is_root:bool,hidden:bool}>
	 */
	public static function get_menu_items(int $page_id): array {
		$root_id = self::get_root_id($page_id);
		if (0 === $root_id) {
			return [];
		}

		$root = get_post($root_id);
		if (!$root instanceof WP_Post) {
			return [];
		}

		$children = get_pages(
			[
				'parent'      => $root_id,
				'sort_column' => 'menu_order,post_title',
			]
		);
		if (empty($children)) {
			return [];
		}

		$posts = array_merge([$root], $children);

		$items = [];
		foreach ($posts as $index => $item) {
			$url = get_permalink($item->ID);
			$items[] = [
				'id'         => (int) $item->ID,
				'label'      => self::get_label((int) $item->ID),
				'url'        => false === $url ? '' : $url,
				'is_current' => (int) $item->ID === $page_id,
				'is_root'    => 0 === $index,
				'hidden'     => '1' === get_post_meta((int) $item->ID, self::HIDDEN_META_KEY, true),
			];
		}

		return $items;
	}
}
