<?php
/**
 * Conservative performance improvements that do not change WooCommerce logic.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/** Preload only the most likely LCP image on the homepage or product page. */
function cg_performance_preload_lcp_image() {
    $url = '';

    if (is_front_page() && function_exists('cg_get_home_slides')) {
        $slides = cg_get_home_slides();
        if (!empty($slides[0]['image'])) $url = esc_url_raw($slides[0]['image']);
    } elseif (function_exists('is_product') && is_product()) {
        $product = function_exists('wc_get_product') ? wc_get_product(get_queried_object_id()) : false;
        if ($product && $product->get_image_id()) {
            $url = wp_get_attachment_image_url($product->get_image_id(), 'woocommerce_single');
        }
    }

    if ($url) echo "\n<link rel=\"preload\" as=\"image\" href=\"" . esc_url($url) . "\" fetchpriority=\"high\">\n";
}
add_action('wp_head', 'cg_performance_preload_lcp_image', 2);

/** Mark the primary product image as eager/high priority; leave all others native-lazy. */
function cg_performance_product_image_attributes($attr, $attachment, $size) {
    if (!function_exists('is_product') || !is_product()) return $attr;
    $product = function_exists('wc_get_product') ? wc_get_product(get_queried_object_id()) : false;
    if (!$product || (int) $product->get_image_id() !== (int) $attachment->ID) return $attr;

    $attr['loading'] = 'eager';
    $attr['fetchpriority'] = 'high';
    $attr['decoding'] = 'async';
    return $attr;
}
add_filter('wp_get_attachment_image_attributes', 'cg_performance_product_image_attributes', 30, 3);

/** Remove the legacy embed helper when nothing on the storefront needs it. */
function cg_performance_dequeue_legacy_embed() {
    if (is_admin()) return;
    wp_dequeue_script('wp-embed');
    wp_deregister_script('wp-embed');
}
add_action('wp_enqueue_scripts', 'cg_performance_dequeue_legacy_embed', 100);
