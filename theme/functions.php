<?php
/**
 * Rockaden Theme functions.
 */

defined('ABSPATH') || exit;

/**
 * Enqueue custom styles (dark mode, etc.).
 */
add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_style(
        'rockaden-custom',
        get_theme_file_uri('assets/css/custom.css'),
        [],
        wp_get_theme()->get('Version')
    );
});

/**
 * Enqueue dark mode toggle script.
 */
add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_script(
        'rockaden-dark-mode',
        get_theme_file_uri('assets/js/dark-mode.js'),
        [],
        wp_get_theme()->get('Version'),
        true
    );
});

/**
 * Register block patterns category.
 */
add_action('init', function (): void {
    register_block_pattern_category('rockaden', [
        'label' => __('Rockaden', 'rockaden-theme'),
    ]);
});
