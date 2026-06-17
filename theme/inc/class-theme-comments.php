<?php
/**
 * Rockaden Theme — Comments disabled site-wide.
 *
 * The club doesn't use post comments. Rather than rely on Discussion settings
 * (which live in the DB and wouldn't travel from dev to the live site), this
 * closes comments everywhere in code — new and old posts alike — and tidies the
 * admin so comments aren't presented anywhere. The single-post template's
 * comments block and the post-list "comment" buttons are removed separately.
 */

defined('ABSPATH') || exit;

class Rockaden_Theme_Comments {

	/**
	 * Register hooks. Called from functions.php.
	 */
	public static function register(): void {
		// Comments + pings closed everywhere, regardless of any stored per-post
		// flag (covers existing/old posts too).
		add_filter('comments_open', '__return_false', 20);
		add_filter('pings_open', '__return_false', 20);

		// Never surface existing comments, even if a template tries to list them.
		add_filter('comments_array', '__return_empty_array', 20);

		// Drop comment/trackback support from the content types.
		add_action('init', [self::class, 'remove_support']);

		// Admin tidy-up.
		add_action('admin_menu', [self::class, 'remove_admin_menu']);
		add_action('admin_init', [self::class, 'redirect_comment_pages']);
		add_action('wp_dashboard_setup', [self::class, 'remove_dashboard_widget']);
		add_action('admin_bar_menu', [self::class, 'remove_admin_bar_node'], 999);
	}

	/**
	 * Remove comments/trackbacks support from posts and pages.
	 */
	public static function remove_support(): void {
		foreach (['post', 'page'] as $type) {
			if (post_type_supports($type, 'comments')) {
				remove_post_type_support($type, 'comments');
			}
			if (post_type_supports($type, 'trackbacks')) {
				remove_post_type_support($type, 'trackbacks');
			}
		}
	}

	/**
	 * Remove the Comments admin menu item.
	 */
	public static function remove_admin_menu(): void {
		remove_menu_page('edit-comments.php');
	}

	/**
	 * Redirect anyone landing on the comments admin screens back to the dashboard.
	 */
	public static function redirect_comment_pages(): void {
		global $pagenow;
		if ('edit-comments.php' === $pagenow || 'options-discussion.php' === $pagenow) {
			wp_safe_redirect(admin_url());
			exit;
		}
	}

	/**
	 * Remove the "Recent Comments" dashboard widget.
	 */
	public static function remove_dashboard_widget(): void {
		remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
	}

	/**
	 * Remove the comments node from the admin bar.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar The admin bar instance.
	 */
	public static function remove_admin_bar_node($wp_admin_bar): void {
		$wp_admin_bar->remove_node('comments');
	}
}
