<?php
/**
 * Delivery options and local delivery pricing for WooCommerce checkout.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/**
 * Delivery settlements and prices.
 *
 * Add new settlements here using a unique key, visible label and price.
 */
function cg_get_delivery_zones() {
    return apply_filters('cg_delivery_zones', [
        'novovoronezh' => [
            'label' => 'Нововоронеж',
            'price' => 350,
        ],
        'olen-kolodez' => [
            'label' => 'Олень-Колодезь',
            'price' => 500,
        ],
    ]);
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
    if (!is_checkout()) return;

    $version = wp_get_theme()->get('Version');

    wp_enqueue_style(
        'cg-delivery-options',
        get_template_directory_uri() . '/assets/css/delivery-options.css',
        ['cg-woocommerce'],
        $version
    );

    wp_enqueue_script(
        'cg-delivery-zones',
        get_template_directory_uri() . '/assets/js/checkout-delivery-zones.js',
        ['jquery', 'wc-checkout'],
        $version,
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
        'options' => cg_delivery_zone_options(),
    ];

    $fields['order']['cg_delivery_custom_city'] = [
        'type' => 'text',
        'label' => 'Ваш населённый пункт',
        'placeholder' => 'Введите название населённого пункта',
        'required' => false,
        'class' => $custom_city_classes,
        'priority' => 11,
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

/** Save selected zone in the session before WooCommerce recalculates totals. */
function cg_capture_delivery_zone_session($posted_data) {
    if (!function_exists('WC') || !WC()->session) return;

    parse_str($posted_data, $data);

    $zone_key = isset($data['cg_delivery_zone']) ? sanitize_key($data['cg_delivery_zone']) : '';
    $custom_city = isset($data['cg_delivery_custom_city'])
        ? sanitize_text_field(wp_unslash($data['cg_delivery_custom_city']))
        : '';

    $zones = cg_get_delivery_zones();
    if ($zone_key !== 'other' && !isset($zones[$zone_key])) {
        $zone_key = '';
    }

    WC()->session->set('cg_delivery_zone', $zone_key);
    WC()->session->set('cg_delivery_custom_city', $custom_city);

    if (WC()->cart) {
        foreach (WC()->cart->get_shipping_packages() as $package_index => $package) {
            WC()->session->__unset('shipping_for_package_' . $package_index);
        }
    }
}
add_action('woocommerce_checkout_update_order_review', 'cg_capture_delivery_zone_session');

/** Replace configured shipping methods with one delivery rate based on the selected settlement. */
function cg_delivery_zone_package_rates($rates, $package) {
    if (is_admin() && !wp_doing_ajax()) return $rates;
    if (!function_exists('WC') || !WC()->session) return $rates;
    if (!is_checkout() && !wp_doing_ajax()) return $rates;

    $zone_key = (string) WC()->session->get('cg_delivery_zone', '');
    $zones = cg_get_delivery_zones();
    $cost = 0;
    $label = 'Доставка — выберите населённый пункт';

    if (isset($zones[$zone_key])) {
        $cost = (float) $zones[$zone_key]['price'];
        $label = 'Доставка — ' . $zones[$zone_key]['label'];
    } elseif ($zone_key === 'other') {
        $label = 'Доставка — стоимость уточняется';
    }

    $rate = new WC_Shipping_Rate(
        'cg_delivery_zone',
        $label,
        $cost,
        [],
        'cg_delivery_zone'
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
