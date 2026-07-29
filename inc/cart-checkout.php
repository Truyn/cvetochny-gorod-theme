<?php
if (!defined('ABSPATH')) exit;

/** Load the dedicated cart presentation only on the cart page. */
function cg_cart_assets() {
    if (!is_cart()) return;

    $version = wp_get_theme()->get('Version');
    $cart_css = get_template_directory() . '/assets/css/cart-premium.css';
    $cart_js = get_template_directory() . '/assets/js/cart-premium.js';

    wp_enqueue_style(
        'cg-cart-premium',
        get_template_directory_uri() . '/assets/css/cart-premium.css',
        ['cg-woocommerce'],
        file_exists($cart_css) ? filemtime($cart_css) : $version
    );

    wp_enqueue_script(
        'cg-cart-premium',
        get_template_directory_uri() . '/assets/js/cart-premium.js',
        ['jquery', 'wc-cart'],
        file_exists($cart_js) ? filemtime($cart_js) : $version,
        true
    );
}
add_action('wp_enqueue_scripts', 'cg_cart_assets', 30);

/** Premium cart heading and a compact order progress indicator. */
function cg_cart_intro() {
    $count = function_exists('WC') && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;

    echo '<section class="cg-cart-intro" aria-labelledby="cg-cart-title">';
    echo '<div class="cg-cart-intro__copy">';
    echo '<span class="cg-cart-intro__eyebrow">Ваш заказ</span>';
    echo '<h1 id="cg-cart-title">Корзина</h1>';
    echo '<p>Проверьте состав заказа и количество. Населённый пункт, дату и время доставки выберете на следующем шаге.</p>';
    echo '<strong class="cg-cart-intro__count">Товаров в корзине: ' . esc_html($count) . '</strong>';
    echo '</div>';
    echo '<div class="cg-cart-progress" aria-label="Этапы оформления заказа">';
    echo '<span class="is-active"><b>1</b>Корзина</span>';
    echo '<span><b>2</b>Оформление</span>';
    echo '<span><b>3</b>Готово</span>';
    echo '</div>';
    echo '</section>';
}
add_action('woocommerce_before_cart', 'cg_cart_intro', 5);
add_action('woocommerce_cart_is_empty', 'cg_cart_intro', 5);

function cg_cart_reassurance() {
    echo '<aside class="cg-order-reassurance" aria-label="Преимущества заказа">';
    echo '<div><strong>🚚 Удобная доставка</strong><span>Выберите населённый пункт и увидите стоимость при оформлении.</span></div>';
    echo '<div><strong>📷 Фото перед отправкой</strong><span>Покажем готовый букет до передачи курьеру.</span></div>';
    echo '<div><strong>💐 Свежая сборка</strong><span>Собираем букет непосредственно перед доставкой.</span></div>';
    echo '</aside>';
}
add_action('woocommerce_after_cart', 'cg_cart_reassurance', 20);

/** Explanatory note shown immediately above the main checkout button. */
function cg_cart_checkout_note() {
    echo '<div class="cg-cart-checkout-note">';
    echo '<strong>Доставка рассчитывается дальше</strong>';
    echo '<span>На следующем шаге выберите населённый пункт — стоимость автоматически появится в итоговой сумме.</span>';
    echo '</div>';
}
add_action('woocommerce_proceed_to_checkout', 'cg_cart_checkout_note', 5);

/** Replace the standard English-oriented CTA with a clear local-store action. */
remove_action('woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20);
function cg_cart_checkout_button() {
    echo '<a href="' . esc_url(wc_get_checkout_url()) . '" class="checkout-button button alt wc-forward cg-cart-checkout-button">Перейти к оформлению</a>';
}
add_action('woocommerce_proceed_to_checkout', 'cg_cart_checkout_button', 20);

function cg_cart_continue_shopping() {
    echo '<a class="cg-cart-continue" href="' . esc_url(cg_catalog_url()) . '">← Продолжить покупки</a>';
}
add_action('woocommerce_proceed_to_checkout', 'cg_cart_continue_shopping', 30);

add_filter('default_checkout_billing_country', function() { return 'RU'; });
add_filter('default_checkout_shipping_country', function() { return 'RU'; });
add_filter('woocommerce_ship_to_different_address_checked', '__return_false');
add_filter('woocommerce_cart_needs_shipping_address', '__return_false');

/** Keep only fields needed for a local flower order. */
add_filter('woocommerce_checkout_fields', function($fields) {
    if (isset($fields['billing']['billing_first_name'])) {
        $fields['billing']['billing_first_name']['label'] = 'Имя получателя';
        $fields['billing']['billing_first_name']['placeholder'] = 'Имя получателя';
        $fields['billing']['billing_first_name']['priority'] = 10;
        $fields['billing']['billing_first_name']['required'] = true;
    }

    if (isset($fields['billing']['billing_last_name'])) {
        $fields['billing']['billing_last_name']['label'] = 'Фамилия получателя';
        $fields['billing']['billing_last_name']['placeholder'] = 'Фамилия';
        $fields['billing']['billing_last_name']['priority'] = 20;
        $fields['billing']['billing_last_name']['required'] = false;
    }

    if (isset($fields['billing']['billing_phone'])) {
        $fields['billing']['billing_phone']['label'] = 'Телефон получателя';
        $fields['billing']['billing_phone']['placeholder'] = '+7 (___) ___-__-__';
        $fields['billing']['billing_phone']['priority'] = 30;
        $fields['billing']['billing_phone']['required'] = true;
    }

    if (isset($fields['billing']['billing_address_1'])) {
        $fields['billing']['billing_address_1']['label'] = 'Адрес доставки';
        $fields['billing']['billing_address_1']['placeholder'] = 'Улица, дом, корпус';
        $fields['billing']['billing_address_1']['priority'] = 50;
        $fields['billing']['billing_address_1']['required'] = true;
    }

    if (isset($fields['billing']['billing_address_2'])) {
        $fields['billing']['billing_address_2']['label'] = 'Квартира, подъезд, этаж';
        $fields['billing']['billing_address_2']['placeholder'] = 'Квартира, подъезд, этаж';
        $fields['billing']['billing_address_2']['priority'] = 60;
        $fields['billing']['billing_address_2']['required'] = false;
    }

    foreach (['billing_email', 'billing_country', 'billing_postcode', 'billing_company', 'billing_state', 'billing_city'] as $key) {
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
        'autocomplete' => 'given-name',
    ];

    $fields['order']['cg_sender_last_name'] = [
        'type' => 'text',
        'label' => 'Фамилия отправителя',
        'placeholder' => 'Фамилия',
        'required' => false,
        'class' => ['cg-checkout-half', 'cg-sender-field'],
        'priority' => 2,
        'autocomplete' => 'family-name',
    ];

    $fields['order']['cg_sender_phone'] = [
        'type' => 'tel',
        'label' => 'Телефон отправителя',
        'placeholder' => '+7 (___) ___-__-__',
        'required' => true,
        'class' => ['cg-checkout-half', 'cg-sender-field'],
        'priority' => 3,
        'autocomplete' => 'tel',
    ];

    $fields['order']['cg_sender_email'] = [
        'type' => 'email',
        'label' => 'Email отправителя',
        'placeholder' => 'mail@example.ru',
        'required' => true,
        'class' => ['cg-checkout-half', 'cg-sender-field'],
        'priority' => 4,
        'validate' => ['email'],
        'autocomplete' => 'email',
    ];

    if (isset($fields['order']['order_comments'])) {
        $fields['order']['order_comments']['label'] = 'Комментарий флористу и курьеру';
        $fields['order']['order_comments']['placeholder'] = 'Ориентир, код домофона и другие важные детали';
        $fields['order']['order_comments']['priority'] = 30;
    }

    foreach (['cg_hide_price', 'order_comments_upload'] as $key) {
        unset($fields['order'][$key]);
    }

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

/** Copy sender email to WooCommerce billing email for order emails and gateways. */
add_filter('woocommerce_checkout_posted_data', function($data) {
    if (isset($_POST['cg_sender_email'])) {
        $data['billing_email'] = sanitize_email(wp_unslash($_POST['cg_sender_email']));
    }
    return $data;
});

/** Explicit validation for sender contacts. */
function cg_validate_sender_checkout_fields() {
    $first_name = isset($_POST['cg_sender_first_name']) ? trim(wp_unslash($_POST['cg_sender_first_name'])) : '';
    $phone = isset($_POST['cg_sender_phone']) ? trim(wp_unslash($_POST['cg_sender_phone'])) : '';
    $email = isset($_POST['cg_sender_email']) ? sanitize_email(wp_unslash($_POST['cg_sender_email'])) : '';

    if ($first_name === '') wc_add_notice('Укажите имя отправителя.', 'error');
    if ($phone === '') wc_add_notice('Укажите телефон отправителя.', 'error');
    if ($email === '' || !is_email($email)) wc_add_notice('Укажите корректный email отправителя.', 'error');
}
add_action('woocommerce_checkout_process', 'cg_validate_sender_checkout_fields');

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

    if (isset($_POST['cg_sender_email'])) {
        $order->set_billing_email(sanitize_email(wp_unslash($_POST['cg_sender_email'])));
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
