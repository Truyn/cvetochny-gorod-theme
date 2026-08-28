<?php
/**
 * Keep the small-store admin focused on daily work without deleting helpers.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

require_once get_template_directory() . '/inc/store-tools-hub.php';
require_once get_template_directory() . '/inc/local-search-seo.php';

/**
 * Keep only one occasional-tools entry in the WooCommerce menu.
 * Helper pages stay registered and are opened from «Инструменты магазина».
 */
function cg_simple_store_hide_helper_menus() {
    remove_submenu_page('woocommerce', 'cg-search-promotion-guide');
    remove_submenu_page('woocommerce', 'cg-commerce-analytics');
    remove_submenu_page('woocommerce', 'cg-catalog-quality');
    remove_submenu_page('woocommerce', 'edit.php?post_type=cg_landing');
    remove_submenu_page('woocommerce', 'cg-order-readiness');
}
add_action('admin_menu', 'cg_simple_store_hide_helper_menus', 10000);

/**
 * Register hidden compatibility endpoints for helper screens.
 *
 * Some WordPress/WooCommerce combinations can reject a direct request after
 * a submenu is removed from the visible menu. The tools hub keeps these pages
 * hidden, but they must remain valid admin routes.
 */
function cg_simple_store_register_helper_access_bridges() {
    $slugs = [
        'cg-commerce-analytics',
        'cg-catalog-quality',
        'cg-order-readiness',
    ];

    foreach ($slugs as $slug) {
        add_submenu_page(
            null,
            'Инструменты магазина',
            'Инструменты магазина',
            'manage_woocommerce',
            $slug,
            static function () use ($slug) {
                if (!current_user_can('manage_woocommerce')) return;
                do_action('woocommerce_page_' . $slug);
            }
        );
    }
}
add_action('admin_menu', 'cg_simple_store_register_helper_access_bridges', 10001);

/** Remove optional SEO boxes from the normal product-editing workflow. */
function cg_simple_store_remove_product_meta_boxes() {
    remove_meta_box('cg-product-seo-checklist', 'product', 'side');
    remove_meta_box('cg-product-seo-snippet', 'product', 'side');
}
add_action('add_meta_boxes_product', 'cg_simple_store_remove_product_meta_boxes', 100);

/**
 * Automatic SEO fallbacks remain active, but advanced category snippet fields
 * are hidden from the everyday editing form. Existing saved values are kept.
 */
remove_action('product_cat_add_form_fields', 'cg_seo_three_category_add_fields', 30);
remove_action('product_cat_edit_form_fields', 'cg_seo_three_category_edit_fields', 30);

/** Remove old helper notices that are no longer useful in the simple workflow. */
remove_action('admin_notices', 'cg_seo_owner_friendly_product_notice');
remove_action('admin_notices', 'cg_admin_notice');

/** Load the visual polish layer after the theme's existing WooCommerce CSS. */
function cg_product_page_polish_assets() {
    if (!class_exists('WooCommerce')) return;

    $path = get_template_directory() . '/assets/css/product-page-polish.css';
    $version = file_exists($path) ? filemtime($path) : wp_get_theme()->get('Version');

    if (is_product()) {
        wp_enqueue_style(
            'cg-product-page-polish',
            get_template_directory_uri() . '/assets/css/product-page-polish.css',
            ['cg-product-hotfix'],
            $version
        );
    }

    if (is_shop() || is_product_taxonomy()) {
        wp_enqueue_style(
            'cg-catalog-controls-polish',
            get_template_directory_uri() . '/assets/css/product-page-polish.css',
            ['cg-premium-filters'],
            $version
        );
    }
}
add_action('wp_enqueue_scripts', 'cg_product_page_polish_assets', 100);
