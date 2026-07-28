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

function cg_checkout_intro(){
    echo '<section class="cg-checkout-intro" aria-label="Этапы оформления заказа">';
    echo '<div class="cg-checkout-intro__copy"><span>Безопасное оформление</span><strong>Остался последний шаг</strong><p>Укажите контакты, данные доставки и проверьте заказ. После оформления флорист свяжется с вами для подтверждения.</p></div>';
    echo '<div class="cg-checkout-steps" aria-hidden="true">';
    echo '<div class="cg-checkout-step"><b>1</b>Контакты</div>';
    echo '<div class="cg-checkout-step"><b>2</b>Доставка</div>';
    echo '<div class="cg-checkout-step"><b>3</b>Оплата</div>';
    echo '</div></section>';
}
add_action('woocommerce_before_checkout_form','cg_checkout_intro',8);

function cg_checkout_reassurance(){
    echo '<aside class="cg-checkout-reassurance" aria-label="Преимущества оформления">';
    echo '<div><strong>Подтверждение заказа</strong><span>Флорист проверит детали и свяжется с вами.</span></div>';
    echo '<div><strong>Бережная доставка</strong><span>Букет перевозится в защитной упаковке.</span></div>';
    echo '<div><strong>Открытка бесплатно</strong><span>Текст можно указать в комментарии к заказу.</span></div>';
    echo '</aside>';
}
add_action('woocommerce_review_order_after_order_total','cg_checkout_reassurance',20);

add_filter('woocommerce_checkout_fields', function($fields){
    if (isset($fields['billing']['billing_first_name'])) {
        $fields['billing']['billing_first_name']['placeholder']='Ваше имя';
        $fields['billing']['billing_first_name']['priority']=10;
    }
    if (isset($fields['billing']['billing_last_name'])) {
        $fields['billing']['billing_last_name']['placeholder']='Фамилия';
        $fields['billing']['billing_last_name']['priority']=20;
    }
    if (isset($fields['billing']['billing_phone'])) {
        $fields['billing']['billing_phone']['placeholder']='+7 (___) ___-__-__';
        $fields['billing']['billing_phone']['priority']=30;
    }
    if (isset($fields['billing']['billing_email'])) {
        $fields['billing']['billing_email']['placeholder']='mail@example.ru';
        $fields['billing']['billing_email']['priority']=40;
    }
    if (isset($fields['billing']['billing_city'])) $fields['billing']['billing_city']['placeholder']='Город доставки';
    if (isset($fields['billing']['billing_address_1'])) $fields['billing']['billing_address_1']['placeholder']='Улица, дом, корпус';
    if (isset($fields['billing']['billing_address_2'])) $fields['billing']['billing_address_2']['placeholder']='Квартира, подъезд, этаж';
    if (isset($fields['order']['order_comments'])) {
        $fields['order']['order_comments']['label']='Пожелания к заказу';
        $fields['order']['order_comments']['placeholder']='Текст открытки, ориентир для курьера и другие пожелания';
    }
    return $fields;
});

add_filter('woocommerce_checkout_fields', function($fields){
    foreach (['billing','shipping','order'] as $group) {
        if (empty($fields[$group]) || !is_array($fields[$group])) continue;
        foreach ($fields[$group] as $key => $field) {
            $classes = isset($field['class']) && is_array($field['class']) ? $field['class'] : [];
            $classes = array_values(array_diff($classes, ['form-row-first','form-row-last']));
            if (in_array($key, ['billing_first_name','billing_last_name','billing_phone','billing_email'], true)) {
                $classes[] = 'cg-checkout-half';
            } else {
                $classes[] = 'form-row-wide';
            }
            $fields[$group][$key]['class'] = array_values(array_unique($classes));
        }
    }
    return $fields;
}, 30);
