<?php
/** Avoid duplicating WooCommerce BreadcrumbList on native product pages. */
if (!defined('ABSPATH')) exit;

function cg_seo_schema_compat_native_product() {
    if (function_exists('is_product') && is_product()) {
        remove_action('wp_head', 'cg_seo_three_structured_data', 8);
    }
}
add_action('wp', 'cg_seo_schema_compat_native_product', 50);
