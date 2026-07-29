<?php
/**
 * Delivery options and local delivery pricing for WooCommerce checkout.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/**
 * The store can use either the regular WooCommerce checkout page or the
 * dedicated Premium Checkout page template.
 */
function cg_is_delivery_checkout_screen() {
    return is_checkout() || is_page_template('page-templates/premium-checkout.php');
}

/** Delivery pricing is also required on the cart page. */
function cg_is_delivery_pricing_screen() {
    return cg_is_delivery_checkout_screen() || is_cart();
}

/**
 * Read a simple fixed price from a WooCommerce flat-rate method.
 *
 * Formula-based costs are intentionally skipped because their displayed price
 * cannot be known before WooCommerce calculates a concrete cart package.
 */
function cg_delivery_parse_flat_rate_cost($raw_cost) {
    $raw_cost = html_entity_decode(wp_strip_all_tags((string) $raw_cost), ENT_QUOTES, 'UTF-8');
    $raw_cost = str_replace(["\xc2\xa0", ' '], '', trim($raw_cost));
    $raw_cost = str_replace(',', '.', $raw_cost);

    if ($raw_cost === '' || !preg_match('/^\d+(?:\.\d+)?$/', $raw_cost)) {
        return null;
    }

    return max(0, (float) $raw_cost);
}

/**
 * Delivery settlements and prices are managed in WooCommerce shipping zones.
 *
 * Every enabled "Flat rate" instance with a simple numeric cost becomes one
 * option in the cart and checkout selectors. The instance ID is used as a
 * stable key, so renaming a method in the admin area does not break selection.
 */
function cg_get_delivery_zones() {
    static $delivery_zones = null;

    if ($delivery_zones !== null) {
        return apply_filters('cg_delivery_zones', $delivery_zones);
    }

    $delivery_zones = [];

    if (!class_exists('WC_Shipping_Zones') || !class_exists('WC_Shipping_Zone')) {
        return apply_filters('cg_delivery_zones', $delivery_zones);
    }

    $zone_ids = [];
    foreach (WC_Shipping_Zones::get_zones() as $zone_key => $zone_data) {
        $zone_ids[] = isset($zone_data['zone_id']) ? absint($zone_data['zone_id']) : absint($zone_key);
    }

    // Zone 0 is "Locations not covered by your other zones".
    $zone_ids[] = 0;
    $zone_ids = array_values(array_unique($zone_ids));

    foreach ($zone_ids as $zone_id) {
        $zone = new WC_Shipping_Zone($zone_id);
        $methods = $zone->get_shipping_methods(true);

        foreach ($methods as $method) {
            $method_id = isset($method->id) ? (string) $method->id : '';
            if ($method_id !== 'flat_rate') continue;

            $instance_id = method_exists($method, 'get_instance_id')
                ? absint($method->get_instance_id())
                : (isset($method->instance_id) ? absint($method->instance_id) : 0);

            if ($instance_id < 1) continue;

            $price = cg_delivery_parse_flat_rate_cost($method->get_option('cost', ''));
            if ($price === null) continue;

            $label = trim((string) $method->get_option('title', ''));
            if ($label === '' && method_exists($method, 'get_title')) {
                $label = trim((string) $method->get_title());
            }
            if ($label === '') {
                $label = 'Доставка';
            }

            $key = 'flat-rate-' . $instance_id;
            $delivery_zones[$key] = [
                'label' => $label,
                'price' => $price,
                'method_id' => 'flat_rate',
                'instance_id' => $instance_id,
                'shipping_zone_id' => $zone_id,
            ];
        }
    }

    return apply_filters('cg_delivery_zones', $delivery_zones);
}

function cg_delivery_zone_options() {
    $options = ['' => 'Выберите населённый пункт'];

    foreach (cg_get_delivery_zones() as $key => $zone) {
        $options[$key] = sprintf(
            '%s — %s ₽',
            $zone['label'],
            number_format_i18n((float) $zone['price'], 0)
        );
    }

    $options['other'] = 'Другой населённый пункт — стоимость уточним';

    return $options;
}

function cg_delivery_options_assets() {
    if (!cg_is_delivery_checkout_screen()) return;

    $theme_version = wp_get_theme()->get('Version');
    $style_path = get_template_directory() . '/assets/css/delivery-options.css';
    $script_path = get_template_directory() . '/assets/js/checkout-delivery-zones.js';
    $dependencies = ['jquery'];

    if (wp_script_is('wc-checkout', 'registered')) {
        wp_enqueue_script('wc-checkout');
        $dependencies[] = 'wc-checkout';
    }

    wp_enqueue_style(
        'cg-delivery-options',
        get_template_directory_uri() . '/assets/css/delivery-options.css',
        ['cg-woocommerce'],
        file_exists($style_path) ? filemtime($style_path) : $theme_version
    );

    wp_enqueue_script(
        'cg-delivery-zones',
        get_template_directory_uri() . '/assets/js/checkout-delivery-zones.js',
        $dependencies,
        file_exists($script_path) ? filemtime($script_path) : $theme_version,
        true
    );

    $zones_for_script = [];
    foreach (cg_get_delivery_zones() as $key => $zone) {
        $zones_for_script[$key] = [
            'label' => $zone['label'],
            'price' => number_format_i18n((float) $zone['price'], 0) . ' ₽',
        ];
    }

    wp_localize_script('cg-delivery-zones', 'cgDeliveryZones', [
        'zones' => $zones_for_script,
        'messages' => [
            'empty' => 'Выберите населённый пункт — стоимость доставки сразу появится в заказе.',
            'other' => 'Стоимость доставки уточним после оформления заказа. Менеджер свяжется с вами до подтверждения.',
            'known' => 'Стоимость доставки: %s.',
        ],
    ]);
}
add_action('wp_enqueue_scripts', 'cg_delivery_options_assets', 20);

function cg_delivery_checkout_fields($fields) {
    $current_zone = (function_exists('WC') && WC()->session)
        ? (string) WC()->session->get('cg_delivery_zone', '')
        : '';
    $current_custom_city = (function_exists('WC') && WC()->session)
        ? (string) WC()->session->get('cg_delivery_custom_city', '')
        : '';

    $custom_city_classes = ['form-row-wide', 'cg-checkout-field', 'cg-delivery-custom-city'];
    if ($current_zone !== 'other') {
        $custom_city_classes[] = 'is-hidden';
    }

    $fields['order']['cg_delivery_zone'] = [
        'type' => 'select',
        'label' => 'Населённый пункт доставки',
        'required' => true,
        'class' => ['form-row-wide', 'cg-checkout-field', 'cg-delivery-zone-field'],
        'input_class' => ['cg-delivery-zone-select'],
        'priority' => 10,
        'default' => $current_zone,
        'options' => cg_delivery_zone_options(),
    ];

    $fields['order']['cg_delivery_custom_city'] = [
        'type' => 'text',
        'label' => 'Ваш населённый пункт',
        'placeholder' => 'Введите название населённого пункта',
        'required' => false,
        'class' => $custom_city_classes,
        'priority' => 11,
        'default' => $current_custom_city,
        'autocomplete' => 'address-level2',
    ];

    $fields['order']['cg_delivery_date'] = [
        'type' => 'date',
        'label' => 'Дата доставки',
        'required' => true,
        'class' => ['cg-checkout-half', 'cg-checkout-field'],
        'priority' => 20,
        'custom_attributes' => ['min' => wp_date('Y-m-d')],
    ];

    $fields['order']['cg_delivery_time'] = [
        'type' => 'select',
        'label' => 'Интервал доставки',
        'required' => true,
        'class' => ['cg-checkout-half', 'cg-checkout-field'],
        'priority' => 21,
        'options' => [
            '' => 'Выберите интервал',
            '09:00–12:00' => '09:00–12:00',
            '12:00–15:00' => '12:00–15:00',
            '15:00–18:00' => '15:00–18:00',
            '18:00–21:00' => '18:00–21:00',
            'По согласованию' => 'По согласованию с менеджером',
        ],
    ];

    $fields['order']['cg_card_message'] = [
        'type' => 'textarea',
        'label' => 'Текст для бесплатной открытки',
        'placeholder' => 'Напишите пожелание получателю',
        'required' => false,
        'class' => ['form-row-wide', 'cg-checkout-field'],
        'priority' => 22,
        'custom_attributes' => ['maxlength' => 300],
    ];

    foreach (['cg_anonymous_delivery', 'cg_hide_price', 'order_comments_upload'] as $key) {
        unset($fields['order'][$key]);
    }

    return $fields;
}
add_filter('woocommerce_checkout_fields', 'cg_delivery_checkout_fields', 25);

function cg_resolve_delivery_city($zone_key, $custom_city = '') {
    $zones = cg_get_delivery_zones();

    if (isset($zones[$zone_key])) {
        return (string) $zones[$zone_key]['label'];
    }

    if ($zone_key === 'other') {
        return sanitize_text_field($custom_city);
    }

    return '';
}

/** Store a validated delivery selection and invalidate cached shipping rates. */
function cg_store_delivery_zone_session($zone_key, $custom_city = '') {
    if (!function_exists('WC') || !WC()->session) return '';

    $zone_key = sanitize_key($zone_key);
    $custom_city = sanitize_text_field($custom_city);
    $zones = cg_get_delivery_zones();

    if ($zone_key !== 'other' && !isset($zones[$zone_key])) {
        $zone_key = '';
    }

    if ($zone_key !== 'other') {
        $custom_city = '';
    }

    WC()->session->set('cg_delivery_zone', $zone_key);
    WC()->session->set('cg_delivery_custom_city', $custom_city);

    if (WC()->cart) {
        foreach (WC()->cart->get_shipping_packages() as $package_index => $package) {
            WC()->session->__unset('shipping_for_package_' . $package_index);
        }
    }

    return $zone_key;
}

/** Save selected zone in the session before WooCommerce recalculates checkout totals. */
function cg_capture_delivery_zone_session($posted_data) {
    parse_str($posted_data, $data);

    $zone_key = isset($data['cg_delivery_zone']) ? $data['cg_delivery_zone'] : '';
    $custom_city = isset($data['cg_delivery_custom_city'])
        ? wp_unslash($data['cg_delivery_custom_city'])
        : '';

    cg_store_delivery_zone_session($zone_key, $custom_city);
}
add_action('woocommerce_checkout_update_order_review', 'cg_capture_delivery_zone_session');

/** Update the cart delivery selection and return freshly rendered totals. */
function cg_ajax_set_delivery_zone() {
    check_ajax_referer('cg_cart_delivery_zone', 'security');

    if (!function_exists('WC') || !WC()->session || !WC()->cart) {
        wp_send_json_error(['message' => 'Корзина недоступна. Обновите страницу и попробуйте ещё раз.'], 400);
    }

    $zone_key = isset($_POST['zone']) ? wp_unslash($_POST['zone']) : '';
    $custom_city = isset($_POST['custom_city']) ? wp_unslash($_POST['custom_city']) : '';
    $zone_key = cg_store_delivery_zone_session($zone_key, $custom_city);

    WC()->cart->calculate_shipping();
    WC()->cart->calculate_totals();

    ob_start();
    woocommerce_cart_totals();
    $cart_totals = ob_get_clean();

    wp_send_json_success([
        'cartTotals' => $cart_totals,
        'zone' => $zone_key,
    ]);
}
add_action('wc_ajax_cg_set_delivery_zone', 'cg_ajax_set_delivery_zone');

/** Replace configured rates with the selected WooCommerce flat-rate instance. */
function cg_delivery_zone_package_rates($rates, $package) {
    if (is_admin() && !wp_doing_ajax()) return $rates;
    if (!function_exists('WC') || !WC()->session) return $rates;
    if (!cg_is_delivery_pricing_screen() && !wp_doing_ajax()) return $rates;

    $zone_key = (string) WC()->session->get('cg_delivery_zone', '');
    $zones = cg_get_delivery_zones();
    $cost = 0;
    $label = 'Доставка — выберите населённый пункт';
    $method_id = 'cg_delivery_zone';
    $instance_id = 0;

    if (isset($zones[$zone_key])) {
        $cost = (float) $zones[$zone_key]['price'];
        $label = (string) $zones[$zone_key]['label'];
        $method_id = (string) $zones[$zone_key]['method_id'];
        $instance_id = absint($zones[$zone_key]['instance_id']);
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
add_filter('woocommerce_package_rates', 'cg_delivery_zone_package_rates', 100, 2);

add_filter('woocommerce_shipping_chosen_method', function($chosen_method, $available_methods) {
    return isset($available_methods['cg_delivery_zone']) ? 'cg_delivery_zone' : $chosen_method;
}, 100, 2);

/** Do not describe an unknown-price delivery as free. */
add_filter('woocommerce_cart_shipping_method_full_label', function($label, $method) {
    if (!is_object($method) || $method->get_id() !== 'cg_delivery_zone') return $label;
    if (!function_exists('WC') || !WC()->session) return $label;

    $zone_key = (string) WC()->session->get('cg_delivery_zone', '');

    if ($zone_key === 'other') {
        return 'Доставка — стоимость уточним после оформления';
    }

    if ($zone_key === '') {
        return 'Доставка — выберите населённый пункт';
    }

    return $label;
}, 100, 2);

/** Copy the selected settlement into WooCommerce billing city data. */
add_filter('woocommerce_checkout_posted_data', function($data) {
    $zone_key = isset($_POST['cg_delivery_zone']) ? sanitize_key(wp_unslash($_POST['cg_delivery_zone'])) : '';
    $custom_city = isset($_POST['cg_delivery_custom_city'])
        ? sanitize_text_field(wp_unslash($_POST['cg_delivery_custom_city']))
        : '';

    $city = cg_resolve_delivery_city($zone_key, $custom_city);
    if ($city !== '') {
        $data['billing_city'] = $city;
    }

    return $data;
}, 20);

function cg_validate_delivery_checkout_fields() {
    $zone_key = isset($_POST['cg_delivery_zone']) ? sanitize_key(wp_unslash($_POST['cg_delivery_zone'])) : '';
    $custom_city = isset($_POST['cg_delivery_custom_city'])
        ? trim(sanitize_text_field(wp_unslash($_POST['cg_delivery_custom_city'])))
        : '';
    $zones = cg_get_delivery_zones();

    if ($zone_key === '') {
        wc_add_notice('Выберите населённый пункт доставки.', 'error');
    } elseif ($zone_key !== 'other' && !isset($zones[$zone_key])) {
        wc_add_notice('Выберите населённый пункт доставки из списка.', 'error');
    } elseif ($zone_key === 'other' && $custom_city === '') {
        wc_add_notice('Введите ваш населённый пункт.', 'error');
    }

    if (!empty($_POST['cg_delivery_date'])) {
        $delivery_date = sanitize_text_field(wp_unslash($_POST['cg_delivery_date']));
        if ($delivery_date < wp_date('Y-m-d')) {
            wc_add_notice('Дата доставки не может быть в прошлом.', 'error');
        }
    }
}
add_action('woocommerce_checkout_process', 'cg_validate_delivery_checkout_fields');

function cg_save_delivery_checkout_fields($order, $data) {
    foreach (['cg_delivery_date', 'cg_delivery_time', 'cg_card_message'] as $field) {
        if (!isset($_POST[$field])) continue;

        $value = $field === 'cg_card_message'
            ? sanitize_textarea_field(wp_unslash($_POST[$field]))
            : sanitize_text_field(wp_unslash($_POST[$field]));

        $order->update_meta_data('_' . $field, $value);
    }

    $zone_key = isset($_POST['cg_delivery_zone']) ? sanitize_key(wp_unslash($_POST['cg_delivery_zone'])) : '';
    $custom_city = isset($_POST['cg_delivery_custom_city'])
        ? sanitize_text_field(wp_unslash($_POST['cg_delivery_custom_city']))
        : '';
    $zones = cg_get_delivery_zones();
    $city = cg_resolve_delivery_city($zone_key, $custom_city);

    $order->update_meta_data('_cg_delivery_zone', $zone_key);
    $order->update_meta_data('_cg_delivery_city', $city);
    $order->update_meta_data('_cg_delivery_custom_city', $zone_key === 'other' ? $custom_city : '');

    if (isset($zones[$zone_key])) {
        $order->update_meta_data('_cg_delivery_price', (float) $zones[$zone_key]['price']);
        $order->update_meta_data('_cg_delivery_price_status', 'fixed');
        $order->update_meta_data('_cg_delivery_method_title', (string) $zones[$zone_key]['label']);
        $order->update_meta_data('_cg_delivery_method_instance_id', absint($zones[$zone_key]['instance_id']));
        $order->update_meta_data('_cg_delivery_shipping_zone_id', absint($zones[$zone_key]['shipping_zone_id']));
    } else {
        $order->update_meta_data('_cg_delivery_price', 0);
        $order->update_meta_data('_cg_delivery_price_status', 'to_confirm');
    }

    if ($city !== '') {
        $order->set_billing_city($city);
    }
}
add_action('woocommerce_checkout_create_order', 'cg_save_delivery_checkout_fields', 10, 2);

function cg_admin_delivery_order_meta($order) {
    $date = $order->get_meta('_cg_delivery_date');
    $time = $order->get_meta('_cg_delivery_time');
    $message = $order->get_meta('_cg_card_message');
    $city = $order->get_meta('_cg_delivery_city');
    $price = (float) $order->get_meta('_cg_delivery_price');
    $price_status = $order->get_meta('_cg_delivery_price_status');

    echo '<div class="cg-order-delivery-meta"><h3>Доставка букета</h3>';
    if ($city) echo '<p><strong>Населённый пункт:</strong> ' . esc_html($city) . '</p>';
    if ($price_status === 'to_confirm') {
        echo '<p><strong>Стоимость доставки:</strong> уточнить у клиента</p>';
    } elseif ($price > 0) {
        echo '<p><strong>Стоимость доставки:</strong> ' . wp_kses_post(wc_price($price)) . '</p>';
    }
    if ($date) echo '<p><strong>Дата:</strong> ' . esc_html(wp_date('d.m.Y', strtotime($date))) . '</p>';
    if ($time) echo '<p><strong>Интервал:</strong> ' . esc_html($time) . '</p>';
    if ($message) echo '<p><strong>Открытка:</strong><br>' . nl2br(esc_html($message)) . '</p>';
    echo '</div>';
}
add_action('woocommerce_admin_order_data_after_shipping_address', 'cg_admin_delivery_order_meta');

function cg_delivery_email_meta_fields($fields, $sent_to_admin, $order) {
    $date = $order->get_meta('_cg_delivery_date');
    $time = $order->get_meta('_cg_delivery_time');
    $message = $order->get_meta('_cg_card_message');
    $city = $order->get_meta('_cg_delivery_city');
    $price_status = $order->get_meta('_cg_delivery_price_status');

    if ($city) $fields['cg_delivery_city'] = ['label' => 'Населённый пункт', 'value' => $city];
    if ($price_status === 'to_confirm') {
        $fields['cg_delivery_price_status'] = [
            'label' => 'Стоимость доставки',
            'value' => 'Уточняется после оформления',
        ];
    }
    if ($date) $fields['cg_delivery_date'] = ['label' => 'Дата доставки', 'value' => wp_date('d.m.Y', strtotime($date))];
    if ($time) $fields['cg_delivery_time'] = ['label' => 'Интервал доставки', 'value' => $time];
    if ($message) $fields['cg_card_message'] = ['label' => 'Текст открытки', 'value' => $message];

    return $fields;
}
add_filter('woocommerce_email_order_meta_fields', 'cg_delivery_email_meta_fields', 10, 3);

function cg_clear_delivery_zone_session() {
    if (!function_exists('WC') || !WC()->session) return;
    WC()->session->__unset('cg_delivery_zone');
    WC()->session->__unset('cg_delivery_custom_city');
}
add_action('woocommerce_checkout_order_processed', 'cg_clear_delivery_zone_session', 20);
