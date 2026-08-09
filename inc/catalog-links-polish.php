<?php
/**
 * Small navigation and catalog polish fixes.
 *
 * Keeps store links configurable and fixes legacy /about/ links without
 * hard-coding the actual About page slug into templates.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/** The real map location used when the old in-page address link is untouched. */
function cg_store_yandex_maps_url() {
    return 'https://yandex.ru/maps/org/florals_city/102742626474/';
}

/**
 * Upgrade only the old untouched address-link default.
 *
 * A merchant-entered custom URL is never overwritten.
 */
function cg_upgrade_header_address_link() {
    $version = '1';
    if (get_option('cg_header_address_link_version') === $version) return;

    $current = trim((string) get_theme_mod('cg_address_url', ''));
    $legacy = [
        '',
        home_url('/contacts/#cg-contacts-map-title'),
        home_url('/contacts#cg-contacts-map-title'),
    ];

    if (in_array(untrailingslashit($current), array_map('untrailingslashit', $legacy), true)) {
        set_theme_mod('cg_address_url', cg_store_yandex_maps_url());
    }

    update_option('cg_header_address_link_version', $version, false);
}
add_action('after_setup_theme', 'cg_upgrade_header_address_link', 40);

/** Find the published page that uses the dedicated About template. */
function cg_find_about_page() {
    $pages = get_posts([
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'meta_key'       => '_wp_page_template',
        'meta_value'     => 'page-templates/about.php',
        'orderby'        => ['menu_order' => 'ASC', 'date' => 'ASC'],
        'no_found_rows'  => true,
    ]);

    if (!empty($pages) && $pages[0] instanceof WP_Post) {
        return $pages[0];
    }

    foreach (['about', 'o-nas'] as $slug) {
        $page = get_page_by_path($slug, OBJECT, 'page');
        if ($page instanceof WP_Post && $page->post_status === 'publish') {
            return $page;
        }
    }

    return null;
}

/** Resolve the About URL from Customizer first, then from the assigned template. */
function cg_about_url() {
    $custom = trim((string) get_theme_mod('cg_about_url', ''));
    if ($custom !== '') return $custom;

    $page = cg_find_about_page();
    if ($page instanceof WP_Post) {
        $url = get_permalink($page);
        if ($url) return $url;
    }

    $base = untrailingslashit((string) get_option('home'));
    return $base . '/about/';
}

/**
 * Make old template calls like home_url('/about/') point to the real About page.
 * This fixes the Contacts button without forcing a particular page slug.
 */
function cg_filter_legacy_about_home_url($url, $path, $orig_scheme, $blog_id) {
    $normalized_path = '/' . trim((string) $path, '/') . '/';
    if ($normalized_path !== '/about/') return $url;

    $custom = trim((string) get_theme_mod('cg_about_url', ''));
    if ($custom !== '') return $custom;

    $direct = get_page_by_path('about', OBJECT, 'page');
    if ($direct instanceof WP_Post && $direct->post_status === 'publish') {
        return $url;
    }

    $page = cg_find_about_page();
    if (!$page instanceof WP_Post) return $url;

    $target = get_permalink($page);
    return $target ?: $url;
}
add_filter('home_url', 'cg_filter_legacy_about_home_url', 20, 4);

/** Keep old /about/ bookmarks working if WordPress currently returns a 404 there. */
function cg_redirect_legacy_about_url() {
    if (is_admin() || wp_doing_ajax() || !is_404()) return;

    $request_path = isset($_SERVER['REQUEST_URI'])
        ? (string) wp_parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH)
        : '';
    $home_path = rtrim((string) wp_parse_url((string) get_option('home'), PHP_URL_PATH), '/');
    $legacy_path = $home_path . '/about/';

    if (untrailingslashit($request_path) !== untrailingslashit($legacy_path)) return;

    $target = cg_about_url();
    $legacy_url = untrailingslashit((string) get_option('home')) . '/about/';
    if (!$target || untrailingslashit($target) === untrailingslashit($legacy_url)) return;

    wp_safe_redirect($target, 301, 'Cvetochny Gorod');
    exit;
}
add_action('template_redirect', 'cg_redirect_legacy_about_url', 2);

/** Add an optional explicit About-page URL and clarify the address link setting. */
function cg_catalog_links_customizer($wp_customize) {
    if (!$wp_customize instanceof WP_Customize_Manager) return;

    if (!$wp_customize->get_setting('cg_about_url')) {
        $wp_customize->add_setting('cg_about_url', [
            'default' => '',
            'sanitize_callback' => 'esc_url_raw',
        ]);
    }

    if (!$wp_customize->get_control('cg_about_url')) {
        $wp_customize->add_control('cg_about_url', [
            'label' => 'Ссылка «О нас»',
            'description' => 'Можно оставить пустой: тема найдёт страницу с шаблоном «О нас — Цветочный город» автоматически.',
            'section' => 'cg_header_settings',
            'type' => 'url',
        ]);
    }

    $address_control = $wp_customize->get_control('cg_address_url');
    if ($address_control) {
        $address_control->description = 'Ссылка по клику на адрес в верхней панели. По умолчанию открывает точку магазина в Яндекс Картах.';
    }
}
add_action('customize_register', 'cg_catalog_links_customizer', 40);

/** Load the final plus/minus alignment fix after the catalog filter styles. */
function cg_catalog_controls_fix_assets() {
    if (!class_exists('WooCommerce')) return;
    if (!is_shop() && !is_product_taxonomy()) return;

    $path = get_template_directory() . '/assets/css/catalog-controls-fix.css';
    wp_enqueue_style(
        'cg-catalog-controls-fix',
        get_template_directory_uri() . '/assets/css/catalog-controls-fix.css',
        ['cg-premium-filters'],
        file_exists($path) ? filemtime($path) : wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'cg_catalog_controls_fix_assets', 46);
