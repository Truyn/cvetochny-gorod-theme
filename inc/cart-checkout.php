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
    echo '<div class="cg-checkout-intro"><span>Безопасное оформление</span><strong>Остался последний шаг</strong><p>Заполните данные получателя, выберите доставку и способ оплаты.</p></div>';
}
add_action('woocommerce_before_checkout_form','cg_checkout_intro',8);

function cg_checkout_reassurance(){
    echo '<aside class="cg-checkout-reassurance">';
    echo '<div><strong>✓ Подтверждение заказа</strong><span>Свяжемся с вами после оформления.</span></div>';
    echo '<div><strong>✓ Бережная доставка</strong><span>Букет перевозится в защитной упаковке.</span></div>';
    echo '<div><strong>✓ Открытка бесплатно</strong><span>Текст можно указать в примечании к заказу.</span></div>';
    echo '</aside>';
}
add_action('woocommerce_review_order_after_order_total','cg_checkout_reassurance',20);

add_filter('woocommerce_checkout_fields', function($fields){
    if (isset($fields['billing']['billing_first_name'])) $fields['billing']['billing_first_name']['placeholder']='Ваше имя';
    if (isset($fields['billing']['billing_phone'])) $fields['billing']['billing_phone']['placeholder']='+7 (___) ___-__-__';
    if (isset($fields['billing']['billing_email'])) $fields['billing']['billing_email']['placeholder']='mail@example.ru';
    if (isset($fields['order']['order_comments'])) $fields['order']['order_comments']['placeholder']='Время доставки, текст открытки и другие пожелания';
    return $fields;
});
