<?php
/**
 * Keep the small-store admin focused on daily work without deleting helpers.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/**
 * Hide helper pages from the everyday WooCommerce menu.
 * The pages stay registered and remain available by direct link.
 */
function cg_simple_store_hide_helper_menus() {
    remove_submenu_page('woocommerce', 'cg-search-promotion-guide');
    remove_submenu_page('woocommerce', 'cg-commerce-analytics');
    remove_submenu_page('woocommerce', 'cg-catalog-quality');
    remove_submenu_page('woocommerce', 'edit.php?post_type=cg_landing');

    global $submenu;
    if (empty($submenu['woocommerce']) || !is_array($submenu['woocommerce'])) return;

    foreach ($submenu['woocommerce'] as &$item) {
        $slug = isset($item[2]) ? (string) $item[2] : '';
        if ($slug === 'cg-order-readiness') {
            $item[0] = 'Проверка перед запуском';
        }
    }
    unset($item);
}
add_action('admin_menu', 'cg_simple_store_hide_helper_menus', 10000);

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
