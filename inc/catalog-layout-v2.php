<?php
/**
 * Stable catalog layout: render filters as a real sidebar before the product content.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

function cg_catalog_wrapper_start_v2() {
    echo '<main id="primary" class="site-main"><div class="container content-area cg-woo-wrap">';

    if (is_shop() || is_product_taxonomy()) {
        echo '<div class="cg-shop-shell cg-shop-shell--server-layout">';

        if (function_exists('cg_catalog_top_filters')) {
            cg_catalog_top_filters();
        }

        echo '<div class="cg-shop-content">';
    }
}

function cg_catalog_wrapper_end_v2() {
    if (is_shop() || is_product_taxonomy()) {
        echo '</div></div>';
    }

    echo '</div></main>';
}

function cg_enable_catalog_wrapper_v2() {
    remove_action('woocommerce_before_main_content', 'cg_wc_wrapper_start', 10);
    remove_action('woocommerce_after_main_content', 'cg_wc_wrapper_end', 10);
    remove_action('woocommerce_before_shop_loop', 'cg_catalog_top_filters', 10);

    add_action('woocommerce_before_main_content', 'cg_catalog_wrapper_start_v2', 10);
    add_action('woocommerce_after_main_content', 'cg_catalog_wrapper_end_v2', 10);
}
add_action('wp', 'cg_enable_catalog_wrapper_v2', 20);
