<?php
/**
 * Small storefront fixes for cart delivery, promo wording and product UI.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/** Whether the current request is a WooCommerce AJAX request. */
function cg_storefront_is_woocommerce_ajax() {
    return wp_doing_ajax() || (defined('WC_DOING_AJAX') && WC_DOING_AJAX);
}

/** Cart, checkout and their AJAX refresh requests use the custom delivery selector. */
function cg_storefront_uses_custom_delivery_selector() {
    return is_cart()
        || is_checkout()
        || is_page_template('page-templates/premium-checkout.php')
        || cg_storefront_is_woocommerce_ajax();
}

/**
 * Clear a stale package cache once before totals are calculated.
 *
 * Old cached WooCommerce rates could otherwise reappear below the custom
 * settlement selector even after the customer selected another settlement.
 */
function cg_storefront_reset_delivery_rate_cache($cart) {
    static $reset = false;

    if ($reset || !cg_storefront_uses_custom_delivery_selector()) return;
    if (is_admin() && !cg_storefront_is_woocommerce_ajax()) return;
    if (!function_exists('WC') || !WC()->session || !$cart instanceof WC_Cart) return;

    foreach ($cart->get_shipping_packages() as $package_index => $package) {
        WC()->session->__unset('shipping_for_package_' . $package_index);
    }

    $reset = true;
}
add_action('woocommerce_before_calculate_totals', 'cg_storefront_reset_delivery_rate_cache', 1);

/**
 * Always leave WooCommerce with exactly one rate generated from our selector.
 *
 * This is deliberately later than regular shipping methods and plugins, so the
 * native radio list cannot disagree with the settlement selected above totals.
 */
function cg_storefront_force_selected_delivery_rate($rates, $package) {
    if (!cg_storefront_uses_custom_delivery_selector()) return $rates;
    if (is_admin() && !cg_storefront_is_woocommerce_ajax()) return $rates;
    if (!function_exists('WC') || !WC()->session || !function_exists('cg_get_delivery_zones')) return $rates;

    $zone_key = (string) WC()->session->get('cg_delivery_zone', '');
    $zones = cg_get_delivery_zones();
    $cost = 0;
    $label = 'Доставка — выберите населённый пункт';
    $method_id = 'cg_delivery_zone';
    $instance_id = 0;

    if (isset($zones[$zone_key])) {
        $zone = $zones[$zone_key];
        $cost = isset($zone['price']) ? max(0, (float) $zone['price']) : 0;
        $label = !empty($zone['label']) ? (string) $zone['label'] : 'Доставка';
        $method_id = !empty($zone['method_id']) ? (string) $zone['method_id'] : 'flat_rate';
        $instance_id = !empty($zone['instance_id']) ? absint($zone['instance_id']) : 0;
    } elseif ($zone_key === 'other') {
        $label = 'Доставка — стоимость уточняется';
    }

    $rate = new WC_Shipping_Rate(
        'cg_delivery_zone',
        $label,
        $cost,
        [],
        $method_id,
        $instance_id
    );

    return ['cg_delivery_zone' => $rate];
}
add_filter('woocommerce_package_rates', 'cg_storefront_force_selected_delivery_rate', 9999, 2);

/** Keep the generated custom delivery rate selected after every totals refresh. */
function cg_storefront_keep_custom_delivery_chosen($chosen_method, $available_methods) {
    return isset($available_methods['cg_delivery_zone'])
        ? 'cg_delivery_zone'
        : $chosen_method;
}
add_filter('woocommerce_shipping_chosen_method', 'cg_storefront_keep_custom_delivery_chosen', 9999, 2);

/** Use customer-friendly promo-code wording in the cart. */
function cg_storefront_promo_code_wording($translation, $text, $domain) {
    if ($domain !== 'woocommerce' || !is_cart()) return $translation;

    if ($text === 'Coupon code') return 'Промокод';
    if ($text === 'Apply coupon') return 'Применить промокод';

    return $translation;
}
add_filter('gettext_woocommerce', 'cg_storefront_promo_code_wording', 20, 3);

/** The shop does not collect or display product reviews. */
function cg_storefront_remove_product_reviews_tab($tabs) {
    unset($tabs['reviews']);
    return $tabs;
}
add_filter('woocommerce_product_tabs', 'cg_storefront_remove_product_reviews_tab', 100);
add_filter('woocommerce_product_get_reviews_allowed', '__return_false', 100);

function cg_storefront_close_product_comments($open, $post_id) {
    return get_post_type($post_id) === 'product' ? false : $open;
}
add_filter('comments_open', 'cg_storefront_close_product_comments', 100, 2);

/** Remove rating output together with the disabled review system. */
function cg_storefront_remove_product_rating_ui() {
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);
    remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5);
}
add_action('wp', 'cg_storefront_remove_product_rating_ui', 20);

/** Load late CSS overrides after the cart, checkout and product styles. */
function cg_storefront_visual_fixes_assets() {
    if (!class_exists('WooCommerce')) return;

    $is_checkout_screen = is_checkout() || is_page_template('page-templates/premium-checkout.php');
    if (!is_cart() && !is_product() && !$is_checkout_screen) return;

    $style_path = get_template_directory() . '/assets/css/storefront-visual-fixes.css';
    $style_version = file_exists($style_path) ? filemtime($style_path) : wp_get_theme()->get('Version');
    $dependencies = ['cg-woocommerce'];

    if (is_cart()) $dependencies[] = 'cg-cart-premium';
    if (is_product()) $dependencies[] = 'cg-product-conversion-premium';
    if ($is_checkout_screen) $dependencies[] = 'cg-classic-checkout-template';

    wp_enqueue_style(
        'cg-storefront-visual-fixes',
        get_template_directory_uri() . '/assets/css/storefront-visual-fixes.css',
        $dependencies,
        $style_version
    );

    if (is_product()) {
        $script_path = get_template_directory() . '/assets/js/storefront-visual-fixes.js';
        $script_version = file_exists($script_path) ? filemtime($script_path) : wp_get_theme()->get('Version');
        $script_dependencies = ['jquery'];

        if (wp_script_is('wc-single-product', 'registered')) {
            $script_dependencies[] = 'wc-single-product';
        }

        wp_enqueue_script(
            'cg-storefront-visual-fixes',
            get_template_directory_uri() . '/assets/js/storefront-visual-fixes.js',
            $script_dependencies,
            $script_version,
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'cg_storefront_visual_fixes_assets', 45);

/** Load final contrast and color corrections for the About and Contacts pages. */
function cg_information_page_polish_assets() {
    $is_about = is_page('about') || is_page_template('page-templates/about.php');
    $is_contacts = is_page('contacts') || is_page_template('page-templates/contacts.php');
    if (!$is_about && !$is_contacts) return;

    $style_path = get_template_directory() . '/assets/css/information-page-polish.css';
    wp_enqueue_style(
        'cg-information-page-polish',
        get_template_directory_uri() . '/assets/css/information-page-polish.css',
        [],
        file_exists($style_path) ? filemtime($style_path) : wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'cg_information_page_polish_assets', 60);

/** Load conservative mobile guards after the page-specific styles. */
function cg_mobile_audit_assets() {
    $style_path = get_template_directory() . '/assets/css/mobile-audit.css';
    wp_enqueue_style(
        'cg-mobile-audit',
        get_template_directory_uri() . '/assets/css/mobile-audit.css',
        [],
        file_exists($style_path) ? filemtime($style_path) : wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'cg_mobile_audit_assets', 90);

/** Load the store-manager integration for VK order notifications. */
require_once get_template_directory() . '/inc/vk-order-notifications.php';

/** Keep signed-in favorites synchronized across devices. */
require_once get_template_directory() . '/inc/favorites-account-sync.php';

/** Register the public delivery and payment information page. */
require_once get_template_directory() . '/inc/delivery-payment-page.php';

/** Add quantity, layout and scale controls to the homepage photo gallery. */
require_once get_template_directory() . '/inc/home-gallery-layout.php';

/** Add conservative SEO defaults and a launch-readiness checklist. */
require_once get_template_directory() . '/inc/launch-readiness.php';

/** Mirror delivery/order details into Custom Fields for the WooCommerce mobile app. */
require_once get_template_directory() . '/inc/mobile-order-fields.php';

/** Fix legacy navigation targets and catalog expand/collapse controls. */
require_once get_template_directory() . '/inc/catalog-links-polish.php';

/** Register draft legal documents, seller settings and checkout legal notices. */
require_once get_template_directory() . '/inc/legal-commerce.php';

/** Use flower-specific, legally conservative wording for returns and claims. */
require_once get_template_directory() . '/inc/legal-returns-policy.php';

/** Final alignment, catalog-header and checkout-phone polish from the visual audit. */
require_once get_template_directory() . '/inc/final-layout-phone-polish.php';

/** Add automatic order-flow diagnostics and a real-order verification checklist. */
require_once get_template_directory() . '/inc/order-readiness.php';

/** Simplify occasion/holiday assignment in products and move «Повод» up in filters. */
require_once get_template_directory() . '/inc/catalog-occasion-admin.php';

/** Add manually curated SEO landing pages and conversion-oriented selections. */
require_once get_template_directory() . '/inc/seo-landing-pages.php';

/** Category SEO, faceted-index guards and curated conversion/internal links. */
require_once get_template_directory() . '/inc/seo-stage-two.php';

/** Put curated SEO selections on the homepage without hard-coding them into the template. */
function cg_storefront_render_home_seo_landings_before_footer() {
    if (!is_front_page() || !function_exists('cg_seo_stage_two_home_landings')) return;
    cg_seo_stage_two_home_landings();
}
add_action('get_footer', 'cg_storefront_render_home_seo_landings_before_footer', 2);
