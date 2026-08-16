<?php
/**
 * Repeat-customer helpers and small launch-readiness additions.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/** Find the latest real customer order without touching recipient fields. */
function cg_repeat_customer_last_order($customer_id = 0) {
    static $cache = [];

    $customer_id = $customer_id ?: get_current_user_id();
    if ($customer_id <= 0 || !function_exists('wc_get_orders')) return false;
    if (array_key_exists($customer_id, $cache)) return $cache[$customer_id];

    $orders = wc_get_orders([
        'customer_id' => $customer_id,
        'status'      => ['wc-processing', 'wc-completed', 'wc-on-hold'],
        'limit'       => 1,
        'orderby'     => 'date',
        'order'       => 'DESC',
        'return'      => 'objects',
    ]);

    $cache[$customer_id] = !empty($orders) && $orders[0] instanceof WC_Order ? $orders[0] : false;
    return $cache[$customer_id];
}

/** Only completed orders are offered as a deliberate repeat purchase. */
function cg_repeat_customer_last_completed_order($customer_id = 0) {
    static $cache = [];

    $customer_id = $customer_id ?: get_current_user_id();
    if ($customer_id <= 0 || !function_exists('wc_get_orders')) return false;
    if (array_key_exists($customer_id, $cache)) return $cache[$customer_id];

    $orders = wc_get_orders([
        'customer_id' => $customer_id,
        'status'      => ['wc-completed'],
        'limit'       => 1,
        'orderby'     => 'date',
        'order'       => 'DESC',
        'return'      => 'objects',
    ]);

    $cache[$customer_id] = !empty($orders) && $orders[0] instanceof WC_Order ? $orders[0] : false;
    return $cache[$customer_id];
}

/** Native WooCommerce "order again" URL: only products are returned to cart. */
function cg_repeat_customer_order_again_url($order) {
    if (!$order instanceof WC_Order || $order->get_status() !== 'completed') return '';

    return wp_nonce_url(
        add_query_arg('order_again', $order->get_id(), wc_get_cart_url()),
        'woocommerce-order_again'
    );
}

/** Prefill sender data for signed-in repeat customers, never recipient data. */
function cg_repeat_customer_prefill_sender($value, $input) {
    if (!is_user_logged_in() || !function_exists('is_checkout') || !is_checkout() || is_order_received_page()) return $value;
    if (!in_array($input, ['cg_sender_first_name', 'cg_sender_last_name', 'cg_sender_phone', 'cg_sender_email'], true)) return $value;
    if (trim((string) $value) !== '') return $value;

    $order = cg_repeat_customer_last_order();
    $user = wp_get_current_user();

    $fallback = [
        'cg_sender_first_name' => $user instanceof WP_User ? (string) $user->first_name : '',
        'cg_sender_last_name'  => $user instanceof WP_User ? (string) $user->last_name : '',
        'cg_sender_phone'      => '',
        'cg_sender_email'      => $user instanceof WP_User ? (string) $user->user_email : '',
    ];

    if ($order instanceof WC_Order) {
        $meta_map = [
            'cg_sender_first_name' => '_cg_sender_first_name',
            'cg_sender_last_name'  => '_cg_sender_last_name',
            'cg_sender_phone'      => '_cg_sender_phone',
            'cg_sender_email'      => '_cg_sender_email',
        ];
        $saved = trim((string) $order->get_meta($meta_map[$input]));
        if ($saved !== '') return $saved;
    }

    return $fallback[$input] ?? $value;
}
add_filter('woocommerce_checkout_get_value', 'cg_repeat_customer_prefill_sender', 12, 2);

/** Compact summary of the products from an order. */
function cg_repeat_customer_order_products($order, $limit = 3) {
    if (!$order instanceof WC_Order) return [];

    $items = [];
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if (!$product instanceof WC_Product || !$product->is_visible()) continue;
        $items[] = [
            'name' => $item->get_name(),
            'qty'  => max(1, (int) $item->get_quantity()),
            'url'  => get_permalink($product->get_id()),
            'img'  => $product->get_image('woocommerce_thumbnail'),
        ];
        if (count($items) >= $limit) break;
    }
    return $items;
}

/** Useful repeat-purchase card on the customer dashboard. */
function cg_repeat_customer_dashboard_card() {
    if (!is_user_logged_in()) return;
    $order = cg_repeat_customer_last_completed_order();
    if (!$order instanceof WC_Order) return;

    $products = cg_repeat_customer_order_products($order, 3);
    if (!$products) return;

    echo '<section class="cg-repeat-order" aria-labelledby="cg-repeat-order-title">';
    echo '<div class="cg-repeat-order__head"><div><span>Быстрый повтор</span><h2 id="cg-repeat-order-title">Понравился прошлый заказ?</h2><p>Вернём товары из заказа №' . esc_html($order->get_order_number()) . ' в корзину. Дату, адрес и пожелания вы укажете заново.</p></div>';
    echo '<a class="button" href="' . esc_url(cg_repeat_customer_order_again_url($order)) . '">Повторить товары</a></div>';
    echo '<div class="cg-repeat-order__products">';
    foreach ($products as $product) {
        echo '<a href="' . esc_url($product['url']) . '" class="cg-repeat-order__product">';
        echo '<span class="cg-repeat-order__image">' . wp_kses_post($product['img']) . '</span>';
        echo '<span><strong>' . esc_html($product['name']) . '</strong><small>Количество: ' . esc_html($product['qty']) . '</small></span>';
        echo '</a>';
    }
    echo '</div></section>';
}
add_action('woocommerce_account_dashboard', 'cg_repeat_customer_dashboard_card', 16);

/** Repeat action on an owned completed order page. */
function cg_repeat_customer_order_action($order) {
    if (!$order instanceof WC_Order || !is_user_logged_in()) return;
    if ((int) $order->get_user_id() !== get_current_user_id()) return;
    if ($order->get_status() !== 'completed') return;

    echo '<section class="cg-repeat-order cg-repeat-order--compact">';
    echo '<div><span>Заказать ещё раз</span><h3>Повторить товары из этого заказа</h3><p>Товары вернутся в корзину, а данные новой доставки вы заполните заново.</p></div>';
    echo '<a class="button" href="' . esc_url(cg_repeat_customer_order_again_url($order)) . '">Повторить товары</a>';
    echo '</section>';
}
add_action('woocommerce_order_details_after_order_table', 'cg_repeat_customer_order_action', 35, 1);

/** Add a repeat shortcut to the customer's order history. */
function cg_repeat_customer_order_list_actions($actions, $order) {
    if (!$order instanceof WC_Order || $order->get_status() !== 'completed') return $actions;
    if ((int) $order->get_user_id() !== get_current_user_id()) return $actions;

    $url = cg_repeat_customer_order_again_url($order);
    if ($url !== '') {
        $actions['cg-repeat'] = [
            'url'  => $url,
            'name' => 'Повторить',
        ];
    }
    return $actions;
}
add_filter('woocommerce_my_account_my_orders_actions', 'cg_repeat_customer_order_list_actions', 30, 2);

/** Small post-order cross-sell without forcing an account or another purchase. */
function cg_repeat_customer_thankyou_extra($order_id) {
    $order = wc_get_order($order_id);
    if (!$order instanceof WC_Order) return;

    echo '<section class="cg-thankyou-extra">';
    echo '<strong>Нужен ещё один букет?</strong>';
    echo '<span>Можно продолжить покупки — текущий заказ уже сохранён и не изменится.</span>';
    echo '<a href="' . esc_url(cg_catalog_url()) . '">Открыть каталог →</a>';
    echo '</section>';
}
add_action('woocommerce_thankyou', 'cg_repeat_customer_thankyou_extra', 18, 1);

/** Add a plain-language catalog summary to the existing order readiness page. */
function cg_repeat_customer_readiness_catalog_notice() {
    if (!current_user_can('manage_woocommerce')) return;
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    if ($page !== 'cg-order-readiness' || !function_exists('cg_catalog_quality_report')) return;

    $report = cg_catalog_quality_report();
    $total = count((array) ($report['products'] ?? []));
    $score = (int) ($report['score'] ?? 0);
    $heavy = (int) ($report['heavy'] ?? 0);
    $level = ($total === 0 || $score < 60) ? 'error' : ($score < 85 || $heavy > 0 ? 'warning' : 'success');

    echo '<div class="notice notice-' . esc_attr($level) . ' inline cg-launch-catalog-summary"><p>';
    echo '<strong>Каталог перед запуском:</strong> опубликовано товаров — ' . esc_html($total) . ', готовность — ' . esc_html($score) . '%';
    if ($heavy > 0) echo ', тяжёлых главных фото — ' . esc_html($heavy);
    echo '. <a href="' . esc_url(admin_url('admin.php?page=cg-catalog-quality')) . '">Открыть качество каталога</a>';
    echo '</p></div>';
}
add_action('admin_notices', 'cg_repeat_customer_readiness_catalog_notice', 20);

/** The old Elementor setup reminder is obsolete for the finished storefront. */
function cg_disable_legacy_elementor_setup_notice() {
    remove_action('admin_notices', 'cg_admin_notice');
}
add_action('admin_init', 'cg_disable_legacy_elementor_setup_notice', 100);

/** Styles only where the repeat-customer UI is actually visible. */
function cg_repeat_customer_assets() {
    $needs_style = (function_exists('is_account_page') && is_account_page())
        || (function_exists('is_order_received_page') && is_order_received_page());
    if (!$needs_style) return;

    $path = get_template_directory() . '/assets/css/repeat-customer.css';
    wp_enqueue_style(
        'cg-repeat-customer',
        get_template_directory_uri() . '/assets/css/repeat-customer.css',
        ['cg-account-search'],
        file_exists($path) ? filemtime($path) : wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'cg_repeat_customer_assets', 80);
