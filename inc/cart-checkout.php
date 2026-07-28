<?php
if (!defined('ABSPATH')) exit;

/** Extra reassurance blocks for cart and checkout. */
function cg_cart_reassurance(){
    echo '<aside class="cg-order-reassurance" aria-label="Преимущества заказа">';
    echo '<div><strong>🚚 Доставка по Нововоронежу</strong><span>Согласуем удобное время после оформления.</span></div>';
    echo '<div><strong>📷 Фото перед отправкой</strong><span>Покажем готовый букет до передачи курьеру.</span></div>';
    echo '<div><strong>💐 Свежие цветы</strong><span>Собираем букет непосредственно перед доставкой.</span></div>';
    echo '</aside>';
}
add_action('woocommerce_after_cart_table','cg_cart_reassurance',20);

function cg_checkout_reassurance(){
    static $rendered = false;
    if ($rendered) return;
    $rendered = true;

    echo '<aside class="cg-checkout-reassurance" aria-label="Преимущества оформления">';
    echo '<div><strong>Подтверждение заказа</strong><span>Флорист проверит детали и свяжется с вами.</span></div>';
    echo '<div><strong>Бережная доставка</strong><span>Букет перевозится в защитной упаковке.</span></div>';
    echo '<div><strong>Открытка бесплатно</strong><span>Текст можно указать в форме заказа.</span></div>';
    echo '</aside>';
}
add_action('woocommerce_review_order_after_order_total','cg_checkout_reassurance',20);

/** Russia is the only checkout country for the store. */
add_filter('default_checkout_billing_country', function(){ return 'RU'; });
add_filter('default_checkout_shipping_country', function(){ return 'RU'; });
add_filter('woocommerce_ship_to_different_address_checked', '__return_false');
add_filter('woocommerce_cart_needs_shipping_address', '__return_false');

/** Keep only the fields needed for local flower delivery and sender contact. */
add_filter('woocommerce_checkout_fields', function($fields){
    if (isset($fields['billing']['billing_first_name'])) {
        $fields['billing']['billing_first_name']['label']='Имя получателя';
        $fields['billing']['billing_first_name']['placeholder']='Имя получателя';
        $fields['billing']['billing_first_name']['priority']=10;
    }
    if (isset($fields['billing']['billing_last_name'])) {
        $fields['billing']['billing_last_name']['label']='Фамилия получателя';
        $fields['billing']['billing_last_name']['placeholder']='Фамилия';
        $fields['billing']['billing_last_name']['required']=false;
        $fields['billing']['billing_last_name']['priority']=20;
    }
    if (isset($fields['billing']['billing_phone'])) {
        $fields['billing']['billing_phone']['label']='Телефон получателя';
        $fields['billing']['billing_phone']['placeholder']='+7 (___) ___-__-__';
        $fields['billing']['billing_phone']['required']=true;
        $fields['billing']['billing_phone']['priority']=30;
    }
    if (isset($fields['billing']['billing_email'])) {
        unset($fields['billing']['billing_email']);
    }
    if (isset($fields['billing']['billing_city'])) {
        $fields['billing']['billing_city']['label']='Населённый пункт';
        $fields['billing']['billing_city']['placeholder']='Нововоронеж';
        $fields['billing']['billing_city']['priority']=40;
    }
    if (isset($fields['billing']['billing_address_1'])) {
        $fields['billing']['billing_address_1']['label']='Адрес доставки';
        $fields['billing']['billing_address_1']['placeholder']='Улица, дом, корпус';
        $fields['billing']['billing_address_1']['priority']=50;
    }
    if (isset($fields['billing']['billing_address_2'])) {
        $fields['billing']['billing_address_2']['label']='Квартира, подъезд, этаж';
        $fields['billing']['billing_address_2']['placeholder']='Квартира, подъезд, этаж';
        $fields['billing']['billing_address_2']['priority']=60;
    }
    if (isset($fields['billing']['billing_state'])) {
        $fields['billing']['billing_state']['required']=false;
        $fields['billing']['billing_state']['label']='Область / район';
        $fields['billing']['billing_state']['priority']=70;
    }

    foreach (['billing_country','billing_postcode','billing_company'] as $key) {
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
        'placeholder' => 'Фамилия',
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
        $fields['order']['order_comments']['label']='Пожелания к заказу';
        $fields['order']['order_comments']['placeholder']='Ориентир для курьера и другие пожелания';
        $fields['order']['order_comments']['priority']=10;
    }

    return $fields;
}, 20);

add_filter('woocommerce_checkout_fields', function($fields){
    foreach (['billing','shipping','order'] as $group) {
        if (empty($fields[$group]) || !is_array($fields[$group])) continue;
        foreach ($fields[$group] as $key => $field) {
            $classes = isset($field['class']) && is_array($field['class']) ? $field['class'] : [];
            $classes = array_values(array_diff($classes, ['form-row-first','form-row-last']));
            if (in_array($key, ['billing_first_name','billing_last_name','billing_phone','cg_sender_first_name','cg_sender_last_name','cg_sender_phone','cg_sender_email'], true)) {
                $classes[] = 'cg-checkout-half';
            } else {
                $classes[] = 'form-row-wide';
            }
            $fields[$group][$key]['class'] = array_values(array_unique($classes));
        }
    }
    return $fields;
}, 30);

/** Save sender data as order metadata. */
function cg_save_sender_checkout_fields($order, $data) {
    $fields = [
        'cg_sender_first_name' => '_cg_sender_first_name',
        'cg_sender_last_name'  => '_cg_sender_last_name',
        'cg_sender_phone'      => '_cg_sender_phone',
        'cg_sender_email'      => '_cg_sender_email',
    ];

    foreach ($fields as $request_key => $meta_key) {
        if (!isset($_POST[$request_key])) continue;
        $value = sanitize_text_field(wp_unslash($_POST[$request_key]));
        $order->update_meta_data($meta_key, $value);
    }
}
add_action('woocommerce_checkout_create_order', 'cg_save_sender_checkout_fields', 10, 2);

function cg_admin_sender_order_meta($order) {
    $first_name = $order->get_meta('_cg_sender_first_name');
    $last_name  = $order->get_meta('_cg_sender_last_name');
    $phone      = $order->get_meta('_cg_sender_phone');
    $email      = $order->get_meta('_cg_sender_email');

    if (!$first_name && !$phone && !$email) return;

    echo '<div class="cg-order-sender-meta"><h3>Данные отправителя</h3>';
    echo '<p><strong>Имя:</strong> ' . esc_html(trim($first_name . ' ' . $last_name)) . '</p>';
    echo '<p><strong>Телефон:</strong> ' . esc_html($phone) . '</p>';
    echo '<p><strong>Email:</strong> ' . esc_html($email) . '</p>';
    echo '</div>';
}
add_action('woocommerce_admin_order_data_after_billing_address', 'cg_admin_sender_order_meta');
