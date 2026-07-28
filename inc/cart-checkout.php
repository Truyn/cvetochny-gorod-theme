<?php
if (!defined('ABSPATH')) exit;

function cg_cart_reassurance(){
    echo '<aside class="cg-order-reassurance" aria-label="Преимущества заказа">';
    echo '<div><strong>🚚 Доставка по Нововоронежу</strong><span>Согласуем удобное время после оформления.</span></div>';
    echo '<div><strong>📷 Фото перед отправкой</strong><span>Покажем готовый букет до передачи курьеру.</span></div>';
    echo '<div><strong>💐 Свежие цветы</strong><span>Собираем букет непосредственно перед доставкой.</span></div>';
    echo '</aside>';
}
add_action('woocommerce_after_cart_table','cg_cart_reassurance',20);

/* Disable old duplicate reassurance block in checkout. */
function cg_checkout_reassurance(){ }
add_action('woocommerce_review_order_after_order_total','cg_checkout_reassurance',20);

add_filter('default_checkout_billing_country', function(){ return 'RU'; });
add_filter('default_checkout_shipping_country', function(){ return 'RU'; });
add_filter('woocommerce_ship_to_different_address_checked', '__return_false');
add_filter('woocommerce_cart_needs_shipping_address', '__return_false');

add_filter('woocommerce_checkout_fields', function($fields){
    if (isset($fields['billing']['billing_first_name'])) {
        $fields['billing']['billing_first_name']['label']='Имя получателя';
        $fields['billing']['billing_first_name']['placeholder']='Имя получателя';
        $fields['billing']['billing_first_name']['priority']=10;
        $fields['billing']['billing_first_name']['required']=true;
    }
    if (isset($fields['billing']['billing_last_name'])) {
        $fields['billing']['billing_last_name']['placeholder']='Фамилия (необязательно)';
        $fields['billing']['billing_last_name']['required']=false;
    }
    if (isset($fields['billing']['billing_phone'])) {
        $fields['billing']['billing_phone']['label']='Телефон получателя';
        $fields['billing']['billing_phone']['required']=true;
    }
    if (isset($fields['billing']['billing_email'])) {
        $fields['billing']['billing_email']['required']=true;
    }
    if (isset($fields['billing']['billing_city'])) {
        $fields['billing']['billing_city']['label']='Населённый пункт';
    }
    if (isset($fields['billing']['billing_state'])) {
        $fields['billing']['billing_state']['required']=false;
        $fields['billing']['billing_state']['label']='Область / район (необязательно)';
    }
    foreach (['billing_country','billing_postcode','billing_company'] as $key) unset($fields['billing'][$key]);
    $fields['shipping']=[];
    if (isset($fields['order']['order_comments'])) {
        $fields['order']['order_comments']['label']='Пожелания к заказу';
    }
    unset($fields['order']['order_comments_upload']);
    return $fields;
},20);

add_filter('woocommerce_checkout_fields', function($fields){
    foreach ($fields as $group=>$items) {
        if (!is_array($items)) continue;
        foreach ($items as $key=>$field) {
            $field['class']=array_values(array_unique(array_merge((array)($field['class']??[]), ['form-row-wide'])));
            $fields[$group][$key]=$field;
        }
    }
    return $fields;
},30);
