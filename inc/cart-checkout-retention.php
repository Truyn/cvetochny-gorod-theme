<?php
/**
 * Friction reduction for cart/checkout and first-party recently viewed products.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/** Keep only product IDs in a small first-party cookie; no order/contact data. */
function cg_retention_track_product_view() {
    if (is_admin() || !function_exists('is_product') || !is_product()) return;

    $product_id = get_queried_object_id();
    if (!$product_id || get_post_status($product_id) !== 'publish') return;

    $ids = [];
    if (!empty($_COOKIE['cg_recent_products'])) {
        $raw = sanitize_text_field(wp_unslash($_COOKIE['cg_recent_products']));
        $ids = array_values(array_filter(array_map('absint', explode('|', $raw))));
    }

    $ids = array_values(array_diff($ids, [$product_id]));
    array_unshift($ids, $product_id);
    $ids = array_slice(array_values(array_unique($ids)), 0, 12);

    $value = implode('|', $ids);
    if (function_exists('wc_setcookie')) {
        wc_setcookie('cg_recent_products', $value, time() + DAY_IN_SECONDS * 30);
    } else {
        setcookie('cg_recent_products', $value, time() + DAY_IN_SECONDS * 30, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true);
    }

    $_COOKIE['cg_recent_products'] = $value;
}
add_action('template_redirect', 'cg_retention_track_product_view', 25);

function cg_retention_recent_product_ids($limit = 4) {
    if (empty($_COOKIE['cg_recent_products'])) return [];

    $raw = sanitize_text_field(wp_unslash($_COOKIE['cg_recent_products']));
    $ids = array_values(array_unique(array_filter(array_map('absint', explode('|', $raw)))));

    if (function_exists('WC') && WC()->cart) {
        $in_cart = [];
        foreach (WC()->cart->get_cart() as $item) {
            $in_cart[] = absint($item['product_id'] ?? 0);
            $in_cart[] = absint($item['variation_id'] ?? 0);
        }
        $ids = array_values(array_diff($ids, array_filter($in_cart)));
    }

    $valid = [];
    foreach ($ids as $id) {
        $product = function_exists('wc_get_product') ? wc_get_product($id) : false;
        if (!$product instanceof WC_Product || !$product->is_visible()) continue;
        $valid[] = $id;
        if (count($valid) >= max(1, absint($limit))) break;
    }

    return $valid;
}

function cg_retention_render_recent_products() {
    if (!class_exists('WooCommerce')) return;
    $ids = cg_retention_recent_product_ids(4);
    if (!$ids) return;

    echo '<section class="cg-recent-products" aria-labelledby="cg-recent-products-title">';
    echo '<div class="cg-recent-products__head">';
    echo '<div><span>Можно вернуться</span><h2 id="cg-recent-products-title">Вы недавно смотрели</h2></div>';
    echo '<a href="' . esc_url(cg_catalog_url()) . '">Весь каталог →</a>';
    echo '</div>';
    echo '<div class="cg-recent-products__grid">';

    foreach ($ids as $id) {
        $product = wc_get_product($id);
        if (!$product) continue;
        $url = get_permalink($id);
        echo '<article class="cg-recent-product">';
        echo '<a class="cg-recent-product__image" href="' . esc_url($url) . '">' . $product->get_image('woocommerce_thumbnail', ['loading' => 'lazy']) . '</a>';
        echo '<div class="cg-recent-product__body">';
        echo '<a class="cg-recent-product__title" href="' . esc_url($url) . '">' . esc_html($product->get_name()) . '</a>';
        echo '<div class="cg-recent-product__price">' . wp_kses_post($product->get_price_html()) . '</div>';
        echo '<a class="cg-recent-product__button" href="' . esc_url($url) . '">Посмотреть</a>';
        echo '</div></article>';
    }

    echo '</div></section>';
}
add_action('woocommerce_after_cart', 'cg_retention_render_recent_products', 35);
add_action('woocommerce_cart_is_empty', 'cg_retention_render_recent_products', 30);

/** Compact checkout reassurance next to the order summary. */
function cg_retention_checkout_summary_helper() {
    if (!function_exists('is_checkout') || !is_checkout() || is_order_received_page()) return;

    echo '<aside class="cg-checkout-helper" aria-label="Перед оформлением заказа">';
    echo '<div class="cg-checkout-helper__top"><strong>Почти готово</strong><a href="' . esc_url(wc_get_cart_url()) . '">Изменить корзину</a></div>';
    echo '<div class="cg-checkout-helper__items">';
    echo '<span><b>📷</b> Фото букета перед отправкой</span>';
    echo '<span><b>💬</b> Свяжемся, если потребуется уточнение</span>';
    echo '<span><b>💐</b> Собираем непосредственно перед доставкой</span>';
    echo '</div>';
    echo '</aside>';
}
add_action('woocommerce_checkout_before_order_review', 'cg_retention_checkout_summary_helper', 3);

/** Reassurance immediately before the final order button. */
function cg_retention_before_place_order() {
    echo '<div class="cg-place-order-note">Нажимая кнопку оформления, вы отправляете заказ магазину. Итоговую композицию покажем на фото перед передачей курьеру.</div>';
}
add_action('woocommerce_review_order_before_submit', 'cg_retention_before_place_order', 8);

function cg_retention_assets() {
    if (!class_exists('WooCommerce')) return;
    $is_checkout_screen = (function_exists('is_checkout') && is_checkout() && !is_order_received_page()) || is_page_template('page-templates/premium-checkout.php');
    if (!is_cart() && !$is_checkout_screen) return;

    $version = wp_get_theme()->get('Version');
    $css = get_template_directory() . '/assets/css/cart-checkout-retention.css';
    wp_enqueue_style(
        'cg-cart-checkout-retention',
        get_template_directory_uri() . '/assets/css/cart-checkout-retention.css',
        ['cg-woocommerce'],
        file_exists($css) ? filemtime($css) : $version
    );

    if ($is_checkout_screen) {
        $js = get_template_directory() . '/assets/js/checkout-friction.js';
        wp_enqueue_script(
            'cg-checkout-friction',
            get_template_directory_uri() . '/assets/js/checkout-friction.js',
            ['jquery', 'wc-checkout'],
            file_exists($js) ? filemtime($js) : $version,
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'cg_retention_assets', 88);
