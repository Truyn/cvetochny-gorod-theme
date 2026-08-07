<?php
/**
 * Public delivery and payment information page.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/** Public URL used in navigation and calls to action. */
function cg_delivery_payment_url() {
    $fallback = home_url('/delivery/');
    $custom_url = (string) get_theme_mod('cg_delivery_url', $fallback);
    return $custom_url !== '' ? $custom_url : $fallback;
}

/** Register a virtual page so the section works without manual page creation. */
function cg_register_delivery_payment_route() {
    add_rewrite_rule('^(?:delivery|dostavka-i-oplata)/?$', 'index.php?cg_delivery_payment_page=1', 'top');

    $route_version = '1';
    if (get_option('cg_delivery_payment_route_version') !== $route_version) {
        flush_rewrite_rules(false);
        update_option('cg_delivery_payment_route_version', $route_version, false);
    }
}
add_action('init', 'cg_register_delivery_payment_route');

add_filter('query_vars', function($vars) {
    $vars[] = 'cg_delivery_payment_page';
    return $vars;
});

/** Whether the current request should use the dedicated information page. */
function cg_is_delivery_payment_page() {
    if ((int) get_query_var('cg_delivery_payment_page') === 1) return true;

    if (isset($_GET['cg_delivery_payment_page'])) {
        return absint(wp_unslash($_GET['cg_delivery_payment_page'])) === 1;
    }

    return is_page(['delivery', 'dostavka-i-oplata'])
        || is_page_template('page-templates/delivery-payment.php');
}

/** Keep WordPress from redirecting the virtual page to another canonical URL. */
add_filter('redirect_canonical', function($redirect_url) {
    return cg_is_delivery_payment_page() ? false : $redirect_url;
});

add_action('template_redirect', function() {
    if (!cg_is_delivery_payment_page()) return;

    global $wp_query;
    if ($wp_query instanceof WP_Query) {
        $wp_query->is_404 = false;
        $wp_query->is_page = true;
        $wp_query->is_singular = true;
    }

    status_header(200);
    nocache_headers();
});

/** Use the same template for the virtual route and manually assigned page. */
function cg_delivery_payment_template($template) {
    if (!cg_is_delivery_payment_page()) return $template;

    $page_template = get_template_directory() . '/page-delivery.php';
    return file_exists($page_template) ? $page_template : $template;
}
add_filter('template_include', 'cg_delivery_payment_template', 60);

add_filter('pre_get_document_title', function($title) {
    return cg_is_delivery_payment_page()
        ? 'Доставка и оплата — ' . get_bloginfo('name')
        : $title;
});

add_filter('body_class', function($classes) {
    if (cg_is_delivery_payment_page()) {
        $classes = array_values(array_diff($classes, ['error404']));
        $classes[] = 'cg-delivery-payment-page';
    }
    return $classes;
});

/**
 * Delivery methods shown to customers.
 *
 * Prices come from the same enabled WooCommerce flat-rate methods used by the
 * cart and checkout. The original configured price is retained when the current
 * cart has already earned free Novovoronezh delivery.
 */
function cg_delivery_payment_methods() {
    $methods = [];
    $zones = function_exists('cg_get_delivery_zones') ? cg_get_delivery_zones() : [];

    foreach ($zones as $key => $zone) {
        if (!is_array($zone) || empty($zone['label'])) continue;

        $price = array_key_exists('configured_price', $zone)
            ? (float) $zone['configured_price']
            : (float) ($zone['price'] ?? 0);

        $methods[] = [
            'key' => sanitize_key($key),
            'label' => (string) $zone['label'],
            'price' => max(0, $price),
            'is_novovoronezh' => function_exists('cg_delivery_zone_is_novovoronezh')
                ? cg_delivery_zone_is_novovoronezh($zone)
                : false,
        ];
    }

    usort($methods, function($left, $right) {
        if ($left['is_novovoronezh'] !== $right['is_novovoronezh']) {
            return $left['is_novovoronezh'] ? -1 : 1;
        }
        return strnatcasecmp($left['label'], $right['label']);
    });

    return $methods;
}

/** Enabled payment methods configured in WooCommerce. */
function cg_delivery_payment_gateways() {
    if (!class_exists('WooCommerce') || !function_exists('WC')) return [];

    $woocommerce = WC();
    if (!$woocommerce || !$woocommerce->payment_gateways()) return [];

    $gateways = [];
    foreach ($woocommerce->payment_gateways()->payment_gateways() as $gateway) {
        if (!$gateway instanceof WC_Payment_Gateway || $gateway->enabled !== 'yes') continue;

        $description = method_exists($gateway, 'get_description')
            ? wp_strip_all_tags((string) $gateway->get_description())
            : '';

        $gateways[] = [
            'id' => sanitize_key($gateway->id),
            'title' => wp_strip_all_tags((string) $gateway->get_title()),
            'description' => trim($description),
        ];
    }

    return $gateways;
}
