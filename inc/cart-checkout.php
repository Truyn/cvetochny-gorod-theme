<?php
if (!defined('ABSPATH')) exit;

function cg_cart_reassurance() {
    echo '<aside class="cg-order-reassurance" aria-label="Преимущества заказа">';
    echo '<div><strong>🚚 Доставка по Нововоронежу</strong><span>Согласуем удобное время после оформления.</span></div>';
    echo '<div><strong>📷 Фото перед отправкой</strong><span>Покажем готовый букет до передачи курьеру.</span></div>';
    echo '<div><strong>💐 Свежие цветы</strong><span>Собираем букет непосредственно перед доставкой.</span></div>';
    echo '</aside>';
}
add_action('woocommerce_after_cart_table', 'cg_cart_reassurance', 20);

add_filter('default_checkout_billing_country', function() { return 'RU'; });
add_filter('default_checkout_shipping_country', function() { return 'RU'; });
add_filter('woocommerce_ship_to_different_address_checked', '__return_false');
add_filter('woocommerce_cart_needs_shipping_address', '__return_false');

/** Keep recipient, delivery and sender fields required for a local flower order. */
add_filter('woocommerce_checkout_fields', function($fields) {
    if (isset($fields['billing']['billing_first_name'])) {
        $fields['billing']['billing_first_name']['label'] = 'Имя получателя';
        $fields['billing']['billing_first_name']['placeholder'] = 'Имя получателя';
        $fields['billing']['billing_first_name']['priority'] = 10;
        $fields['billing']['billing_first_name']['required'] = true;
    }

    if (isset($fields['billing']['billing_last_name'])) {
        $fields['billing']['billing_last_name']['label'] = 'Фамилия получателя';
        $fields['billing']['billing_last_name']['placeholder'] = 'Фамилия (необязательно)';
        $fields['billing']['billing_last_name']['priority'] = 20;
        $fields['billing']['billing_last_name']['required'] = false;
    }

    if (isset($fields['billing']['billing_phone'])) {
        $fields['billing']['billing_phone']['label'] = 'Телефон получателя';
        $fields['billing']['billing_phone']['placeholder'] = '+7 (___) ___-__-__';
        $fields['billing']['billing_phone']['priority'] = 30;
        $fields['billing']['billing_phone']['required'] = true;
    }

    if (isset($fields['billing']['billing_city'])) {
        $fields['billing']['billing_city']['label'] = 'Населённый пункт';
        $fields['billing']['billing_city']['placeholder'] = 'Нововоронеж';
        $fields['billing']['billing_city']['priority'] = 40;
        $fields['billing']['billing_city']['required'] = true;
    }

    if (isset($fields['billing']['billing_address_1'])) {
        $fields['billing']['billing_address_1']['label'] = 'Адрес доставки';
        $fields['billing']['billing_address_1']['placeholder'] = 'Улица, дом, корпус';
        $fields['billing']['billing_address_1']['priority'] = 50;
        $fields['billing']['billing_address_1']['required'] = true;
    }

    if (isset($fields['billing']['billing_address_2'])) {
        $fields['billing']['billing_address_2']['label'] = 'Квартира, подъезд, этаж';
        $fields['billing']['billing_address_2']['placeholder'] = 'Квартира, подъезд, этаж (необязательно)';
        $fields['billing']['billing_address_2']['priority'] = 60;
        $fields['billing']['billing_address_2']['required'] = false;
    }

    if (isset($fields['billing']['billing_state'])) {
        $fields['billing']['billing_state']['label'] = 'Область / район';
        $fields['billing']['billing_state']['priority'] = 70;
        $fields['billing']['billing_state']['required'] = false;
    }

    foreach (['billing_email', 'billing_country', 'billing_postcode', 'billing_company'] as $key) {
        unset($fields['billing'][$key]);
    }

    $fields['shipping'] = [];

    $fields['order']['cg_sender_first_name'] = [
        'type' => 'text',
        'label' => 'Имя отправителя',
        'placeholder' => 'Ваше имя',
        'required' => true,
        'class' => ['cg-checkout-half', 'cg-sender-field'],
        'priority' => 1,
    ];

    $fields['order']['cg_sender_last_name'] = [
        'type' => 'text',
        'label' => 'Фамилия отправителя',
        'placeholder' => 'Фамилия (необязательно)',
        'required' => false,
        'class' => ['cg-checkout-half', 'cg-sender-field'],
        'priority' => 2,
    ];

    $fields['order']['cg_sender_phone'] = [
        'type' => 'tel',
        'label' => 'Телефон отправителя',
        'placeholder' => '+7 (___) ___-__-__',
        'required' => true,
        'class' => ['cg-checkout-half', 'cg-sender-field'],
        'priority' => 3,
    ];

    $fields['order']['cg_sender_email'] = [
        'type' => 'email',
        'label' => 'Email отправителя',
        'placeholder' => 'mail@example.ru',
        'required' => true,
        'class' => ['cg-checkout-half', 'cg-sender-field'],
        'priority' => 4,
        'validate' => ['email'],
    ];

    if (isset($fields['order']['order_comments'])) {
        $fields['order']['order_comments']['label'] = 'Пожелания к заказу';
        $fields['order']['order_comments']['placeholder'] = 'Ориентир для курьера и другие пожелания';
        $fields['order']['order_comments']['priority'] = 30;
    }

    unset($fields['order']['order_comments_upload']);

    return $fields;
}, 20);

/** Build a predictable two-column checkout grid. */
add_filter('woocommerce_checkout_fields', function($fields) {
    $half_width = [
        'billing_first_name',
        'billing_last_name',
        'billing_phone',
        'cg_sender_first_name',
        'cg_sender_last_name',
        'cg_sender_phone',
        'cg_sender_email',
        'cg_delivery_date',
        'cg_delivery_time',
    ];

    foreach ($fields as $group => $items) {
        if (!is_array($items)) continue;

        foreach ($items as $key => $field) {
            $classes = isset($field['class']) && is_array($field['class']) ? $field['class'] : [];
            $classes = array_values(array_diff($classes, ['form-row-first', 'form-row-last', 'form-row-wide', 'cg-checkout-half']));
            $classes[] = in_array($key, $half_width, true) ? 'cg-checkout-half' : 'form-row-wide';
            $field['class'] = array_values(array_unique($classes));
            $fields[$group][$key] = $field;
        }
    }

    return $fields;
}, 30);

/** Save sender contact details on the order. */
function cg_save_sender_checkout_fields($order, $data) {
    $fields = [
        'cg_sender_first_name' => ['_cg_sender_first_name', 'text'],
        'cg_sender_last_name' => ['_cg_sender_last_name', 'text'],
        'cg_sender_phone' => ['_cg_sender_phone', 'text'],
        'cg_sender_email' => ['_cg_sender_email', 'email'],
    ];

    foreach ($fields as $request_key => $settings) {
        if (!isset($_POST[$request_key])) continue;

        $raw_value = wp_unslash($_POST[$request_key]);
        $value = $settings[1] === 'email' ? sanitize_email($raw_value) : sanitize_text_field($raw_value);
        $order->update_meta_data($settings[0], $value);
    }
}
add_action('woocommerce_checkout_create_order', 'cg_save_sender_checkout_fields', 10, 2);

/** Show sender details to the store manager. */
function cg_admin_sender_order_meta($order) {
    $first_name = $order->get_meta('_cg_sender_first_name');
    $last_name = $order->get_meta('_cg_sender_last_name');
    $phone = $order->get_meta('_cg_sender_phone');
    $email = $order->get_meta('_cg_sender_email');

    if (!$first_name && !$last_name && !$phone && !$email) return;

    echo '<div class="cg-order-sender-meta"><h3>Данные отправителя</h3>';
    echo '<p><strong>Имя:</strong> ' . esc_html(trim($first_name . ' ' . $last_name)) . '</p>';
    echo '<p><strong>Телефон:</strong> ' . esc_html($phone) . '</p>';
    echo '<p><strong>Email:</strong> ' . esc_html($email) . '</p>';
    echo '</div>';
}
add_action('woocommerce_admin_order_data_after_billing_address', 'cg_admin_sender_order_meta');

/** Include sender contact details in WooCommerce order emails. */
function cg_sender_email_meta_fields($fields, $sent_to_admin, $order) {
    $name = trim($order->get_meta('_cg_sender_first_name') . ' ' . $order->get_meta('_cg_sender_last_name'));
    $phone = $order->get_meta('_cg_sender_phone');
    $email = $order->get_meta('_cg_sender_email');

    if ($name) $fields['cg_sender_name'] = ['label' => 'Отправитель', 'value' => $name];
    if ($phone) $fields['cg_sender_phone'] = ['label' => 'Телефон отправителя', 'value' => $phone];
    if ($email) $fields['cg_sender_email'] = ['label' => 'Email отправителя', 'value' => $email];

    return $fields;
}
add_filter('woocommerce_email_order_meta_fields', 'cg_sender_email_meta_fields', 20, 3);
