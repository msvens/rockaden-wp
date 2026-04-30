<?php
/**
 * Rockaden Theme functions.
 */

defined('ABSPATH') || exit;

// Include theme classes.
require_once get_theme_file_path('inc/class-theme-settings.php');
require_once get_theme_file_path('inc/class-theme-setup.php');

// Theme activation: create stub pages + default settings.
add_action('after_switch_theme', ['Rockaden_Theme_Setup', 'activate']);

// Admin settings page.
add_action('admin_menu', ['Rockaden_Theme_Settings', 'register_page']);
add_action('admin_enqueue_scripts', ['Rockaden_Theme_Settings', 'enqueue_admin_assets']);
add_action('admin_post_rockaden_save_settings', ['Rockaden_Theme_Settings', 'handle_save']);

// Page display meta box (title visibility + sidebar override).
add_action('add_meta_boxes', ['Rockaden_Theme_Settings', 'register_page_display_meta_box']);
add_action('save_post_page', ['Rockaden_Theme_Settings', 'save_page_display_meta']);


/**
 * Inline <head> script to apply dark mode class before render (prevents flash).
 * Also outputs the logo URL as a CSS custom property.
 */
add_action('wp_head', function (): void {
    $logo_url = esc_url(get_theme_file_uri('assets/images/logo.png'));
    ?>
    <script>
    (function(){var t=localStorage.getItem('theme');if(t==='dark')document.documentElement.classList.add('dark');document.documentElement.setAttribute('data-lang',localStorage.getItem('language')||'sv');})();
    </script>
    <style>
    :root { --rockaden-logo-url: url('<?php echo $logo_url; ?>'); }
    </style>
    <?php
}, 1);

/**
 * Enqueue custom styles (dark mode, etc.).
 */
add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_style(
        'rockaden-custom',
        get_theme_file_uri('assets/css/custom.css'),
        ['global-styles'],
        wp_get_theme()->get('Version')
    );
});

/**
 * Load custom.css inside the block editor canvas so the editor renders
 * landing-page sections (and other custom layouts) the same way as the
 * frontend. Without this, editors see raw stacked blocks and can't
 * predict the final layout.
 */
add_action('after_setup_theme', function (): void {
    add_editor_style('assets/css/custom.css');
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
 * Enqueue language switcher script.
 */
add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_script(
        'rockaden-language',
        get_theme_file_uri('assets/js/language.js'),
        [],
        wp_get_theme()->get('Version'),
        true
    );
});

/**
 * Expose comment_count on the posts REST API endpoint.
 */
add_action('rest_api_init', function (): void {
    register_rest_field('post', 'comment_count', [
        'get_callback' => function ($post) {
            return (int) get_comments_number($post['id']);
        },
        'schema' => [
            'type'        => 'integer',
            'description' => 'Number of comments',
            'context'     => ['view'],
        ],
    ]);
});

/**
 * Enqueue i18n post script (dates + "Läs mer" translation).
 */
add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_script(
        'rockaden-i18n-post',
        get_theme_file_uri('assets/js/i18n-post.js'),
        ['rockaden-language'],
        wp_get_theme()->get('Version'),
        true
    );
});

/**
 * Enqueue navigation script (Mer dropdown + mobile drawer).
 * Pass settings to JS via wp_localize_script.
 */
add_action('wp_enqueue_scripts', function (): void {
    wp_enqueue_script(
        'rockaden-navigation',
        get_theme_file_uri('assets/js/navigation.js'),
        ['rockaden-dark-mode', 'rockaden-language'],
        wp_get_theme()->get('Version'),
        true
    );

    wp_localize_script(
        'rockaden-navigation',
        'rockadenNav',
        Rockaden_Theme_Settings::get_frontend_config()
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

/**
 * Register theme documentation pages with the plugin's docs system.
 */
add_action('rc_register_docs', function (): void {
    if (!class_exists('Rockaden\Docs\DocsRegistry')) {
        return;
    }
    $docs_dir = get_theme_file_path('docs/');
    $entries  = [
        [
            'slug'       => 'sidor-och-mallar',
            'title_sv'   => 'Sidor och mallar',
            'title_en'   => 'Pages & templates',
            'section_sv' => 'Tema',
            'section_en' => 'Theme',
            'file'       => $docs_dir . 'sidor-och-mallar.html',
            'order'      => 10,
        ],
        [
            'slug'       => 'innehall',
            'title_sv'   => 'Innehåll',
            'title_en'   => 'Content',
            'section_sv' => 'Tema',
            'section_en' => 'Theme',
            'file'       => $docs_dir . 'innehall.html',
            'order'      => 20,
        ],
        [
            'slug'       => 'installningar',
            'title_sv'   => 'Inställningar',
            'title_en'   => 'Settings',
            'section_sv' => 'Tema',
            'section_en' => 'Theme',
            'file'       => $docs_dir . 'installningar.html',
            'order'      => 30,
        ],
    ];
    foreach ($entries as $entry) {
        \Rockaden\Docs\DocsRegistry::register($entry);
    }
});

/**
 * Register theme blocks.
 */
add_action('init', function (): void {
    register_block_type(get_theme_file_path('blocks/section-nav'));
    register_block_type(get_theme_file_path('blocks/sidebar-panel'));
    register_block_type(get_theme_file_path('blocks/page-title'));
});

/**
 * Register per-block CSS files for core block overrides.
 * Each file loads only when its block is present on the page,
 * and automatically after global styles (correct cascade order).
 */
add_action('init', function (): void {
    $blocks = [
        'core/post-template',
        'core/post-excerpt',
        'core/post-author',
        'core/post-title',
        'core/query',
        'core/heading',
    ];
    foreach ($blocks as $block) {
        $slug = str_replace('/', '-', $block);
        $path = "assets/blocks/{$slug}.css";
        if (file_exists(get_theme_file_path($path))) {
            wp_enqueue_block_style($block, [
                'handle' => "rockaden-block-{$slug}",
                'src'    => get_theme_file_uri($path),
                'path'   => get_theme_file_path($path),
            ]);
        }
    }
});

