<?php
/**
 * Privacy-conscious ecommerce analytics foundation.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

function cg_analytics_valid_ga4_id($value) {
    return (bool) preg_match('/^G-[A-Z0-9]{6,20}$/i', trim((string) $value));
}

function cg_analytics_product_item($product, $quantity = 1) {
    if (!$product instanceof WC_Product) return [];
    $categories = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']);
    $category = !is_wp_error($categories) && $categories ? (string) $categories[0] : '';
    $sku = trim((string) $product->get_sku());

    return array_filter([
        'item_id' => $sku !== '' ? $sku : (string) $product->get_id(),
        'item_name' => $product->get_name(),
        'item_category' => $category,
        'price' => (float) wc_get_price_to_display($product),
        'quantity' => max(1, (int) $quantity),
    ], static function ($value) {
        return $value !== '' && $value !== null;
    });
}

function cg_analytics_cart_items() {
    if (!function_exists('WC') || !WC()->cart) return [];
    $items = [];
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product = isset($cart_item['data']) && $cart_item['data'] instanceof WC_Product ? $cart_item['data'] : null;
        if (!$product) continue;
        $items[] = cg_analytics_product_item($product, (int) ($cart_item['quantity'] ?? 1));
    }
    return $items;
}

function cg_analytics_page_event() {
    if (!class_exists('WooCommerce')) return null;
    $currency = get_woocommerce_currency();

    if (is_product()) {
        $product = wc_get_product(get_queried_object_id());
        if (!$product) return null;
        return [
            'event' => 'view_item',
            'ecommerce' => [
                'currency' => $currency,
                'value' => (float) wc_get_price_to_display($product),
                'items' => [cg_analytics_product_item($product)],
            ],
        ];
    }

    if (is_cart() && WC()->cart) {
        return [
            'event' => 'view_cart',
            'ecommerce' => [
                'currency' => $currency,
                'value' => (float) WC()->cart->get_total('edit'),
                'items' => cg_analytics_cart_items(),
            ],
        ];
    }

    if (is_checkout() && !is_order_received_page() && WC()->cart) {
        return [
            'event' => 'begin_checkout',
            'ecommerce' => [
                'currency' => $currency,
                'value' => (float) WC()->cart->get_total('edit'),
                'items' => cg_analytics_cart_items(),
            ],
        ];
    }

    if (is_order_received_page()) {
        $order_id = absint(get_query_var('order-received'));
        if (!$order_id && isset($_GET['key'])) {
            $order_id = wc_get_order_id_by_order_key(wc_clean(wp_unslash($_GET['key'])));
        }
        $order = $order_id ? wc_get_order($order_id) : false;
        if (!$order) return null;

        $items = [];
        foreach ($order->get_items() as $order_item) {
            $product = $order_item->get_product();
            if (!$product) continue;
            $item = cg_analytics_product_item($product, (int) $order_item->get_quantity());
            $quantity = max(1, (int) $order_item->get_quantity());
            $item['price'] = (float) $order_item->get_total() / $quantity;
            $items[] = $item;
        }

        return [
            'event' => 'purchase',
            'event_key' => 'purchase:' . $order->get_order_number(),
            'ecommerce' => [
                'transaction_id' => (string) $order->get_order_number(),
                'currency' => $order->get_currency(),
                'value' => (float) $order->get_total(),
                'shipping' => (float) $order->get_shipping_total(),
                'tax' => (float) $order->get_total_tax(),
                'coupon' => implode(',', $order->get_coupon_codes()),
                'items' => $items,
            ],
        ];
    }

    return null;
}

function cg_analytics_enqueue_assets() {
    if (is_admin() || !class_exists('WooCommerce')) return;
    $relevant = is_front_page() || is_shop() || is_product_taxonomy() || is_product() || is_cart() || is_checkout();
    if (!$relevant) return;

    $ga4 = strtoupper(trim((string) get_option('cg_analytics_ga4_id', '')));
    if (cg_analytics_valid_ga4_id($ga4)) {
        wp_enqueue_script('cg-ga4', 'https://www.googletagmanager.com/gtag/js?id=' . rawurlencode($ga4), [], null, false);
        wp_add_inline_script(
            'cg-ga4',
            'window.dataLayer=window.dataLayer||[];window.gtag=window.gtag||function(){dataLayer.push(arguments);};gtag("js",new Date());gtag("config",' . wp_json_encode($ga4) . ');',
            'before'
        );
    }

    $path = get_template_directory() . '/assets/js/commerce-analytics.js';
    wp_enqueue_script(
        'cg-commerce-analytics',
        get_template_directory_uri() . '/assets/js/commerce-analytics.js',
        ['jquery'],
        file_exists($path) ? filemtime($path) : wp_get_theme()->get('Version'),
        true
    );

    $payload = [
        'currency' => get_woocommerce_currency(),
        'pageEvent' => cg_analytics_page_event(),
        'ga4Id' => cg_analytics_valid_ga4_id($ga4) ? $ga4 : '',
        'catalog' => is_shop() || is_product_taxonomy(),
    ];
    if (is_product()) {
        $product = wc_get_product(get_queried_object_id());
        if ($product) $payload['currentProduct'] = cg_analytics_product_item($product);
    }

    wp_add_inline_script(
        'cg-commerce-analytics',
        'window.cgAnalytics=' . wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';',
        'before'
    );
}
add_action('wp_enqueue_scripts', 'cg_analytics_enqueue_assets', 95);

function cg_analytics_loop_add_to_cart_args($args, $product) {
    if (!$product instanceof WC_Product) return $args;
    if (empty($args['attributes']) || !is_array($args['attributes'])) $args['attributes'] = [];
    $item = cg_analytics_product_item($product);
    $args['attributes']['data-cg-item-id'] = $item['item_id'] ?? (string) $product->get_id();
    $args['attributes']['data-cg-item-name'] = $item['item_name'] ?? $product->get_name();
    $args['attributes']['data-cg-item-price'] = isset($item['price']) ? (string) $item['price'] : '';
    $args['attributes']['data-cg-item-category'] = $item['item_category'] ?? '';
    return $args;
}
add_filter('woocommerce_loop_add_to_cart_args', 'cg_analytics_loop_add_to_cart_args', 30, 2);

function cg_analytics_sanitize_ga4($value) {
    $value = strtoupper(trim(sanitize_text_field((string) $value)));
    if ($value === '') return '';
    if (!cg_analytics_valid_ga4_id($value)) {
        add_settings_error('cg_analytics', 'invalid_ga4', 'GA4 Measurement ID должен выглядеть как G-XXXXXXXXXX.', 'error');
        return (string) get_option('cg_analytics_ga4_id', '');
    }
    return $value;
}

function cg_analytics_register_settings() {
    register_setting('cg_analytics_settings', 'cg_analytics_ga4_id', [
        'type' => 'string',
        'sanitize_callback' => 'cg_analytics_sanitize_ga4',
        'default' => '',
    ]);
}
add_action('admin_init', 'cg_analytics_register_settings');

function cg_analytics_register_admin_page() {
    add_submenu_page(
        'woocommerce',
        'Аналитика магазина',
        'Аналитика магазина',
        'manage_woocommerce',
        'cg-commerce-analytics',
        'cg_analytics_admin_page'
    );
}
add_action('admin_menu', 'cg_analytics_register_admin_page', 80);

function cg_analytics_admin_page() {
    if (!current_user_can('manage_woocommerce')) return;
    ?>
    <div class="wrap">
        <h1>Аналитика магазина</h1>
        <?php settings_errors('cg_analytics'); ?>
        <p>Тема формирует ecommerce-события магазина. Пока GA4 ID пустой, Google Analytics с сайта не загружается.</p>
        <form method="post" action="options.php" style="max-width:760px;background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:22px;margin-top:18px">
            <?php settings_fields('cg_analytics_settings'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="cg_analytics_ga4_id">Google Analytics 4</label></th>
                    <td><input class="regular-text" id="cg_analytics_ga4_id" name="cg_analytics_ga4_id" value="<?php echo esc_attr((string) get_option('cg_analytics_ga4_id', '')); ?>" placeholder="G-XXXXXXXXXX"><p class="description">Необязательно. Заполните после создания ресурса GA4.</p></td>
                </tr>
            </table>
            <?php submit_button('Сохранить'); ?>
        </form>
        <h2>Воронка и события</h2>
        <p><code>view_item</code>, <code>view_item_list</code>, <code>view_cart</code>, <code>add_to_cart</code>, <code>add_to_wishlist</code>, <code>begin_checkout</code>, <code>purchase</code>, <code>search</code>, <code>catalog_filter</code>.</p>
        <p><strong>Важно:</strong> перед включением внешней аналитики проверьте, что политика персональных данных и уведомления на сайте соответствуют фактически используемому сервису.</p>
    </div>
    <?php
}
