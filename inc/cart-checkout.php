<?php
if (!defined('ABSPATH')) exit;

function cg_cart_reassurance(){
 echo '<aside class="cg-order-reassurance"><div><strong>🚚 Доставка по Нововоронежу</strong><span>Согласуем удобное время после оформления.</span></div><div><strong>📷 Фото перед отправкой</strong><span>Покажем готовый букет.</span></div><div><strong>💐 Свежие цветы</strong><span>Собираем перед доставкой.</span></div></aside>';
}
add_action('woocommerce_after_cart_table','cg_cart_reassurance',20);
add_filter('default_checkout_billing_country',fn()=> 'RU');
add_filter('woocommerce_ship_to_different_address_checked','__return_false');
add_filter('woocommerce_cart_needs_shipping_address','__return_false');

add_filter('woocommerce_checkout_fields',function($fields){
 foreach(['billing_country','billing_postcode','billing_company'] as $k) unset($fields['billing'][$k]);
 $fields['shipping']=[];
 if(isset($fields['billing']['billing_first_name'])){$fields['billing']['billing_first_name']['label']='Имя получателя';$fields['billing']['billing_first_name']['required']=true;}
 if(isset($fields['billing']['billing_last_name'])){$fields['billing']['billing_last_name']['placeholder']='Фамилия (необязательно)';$fields['billing']['billing_last_name']['required']=false;}
 if(isset($fields['billing']['billing_phone'])){$fields['billing']['billing_phone']['label']='Телефон получателя';$fields['billing']['billing_phone']['required']=true;}
 if(isset($fields['billing']['billing_state'])){$fields['billing']['billing_state']['required']=false;}
 if(isset($fields['order']['order_comments'])){$fields['order']['order_comments']['label']='Пожелания к заказу';}
 unset($fields['order']['order_comments_upload']);
 $fields['order']['cg_sender_name']=['type'=>'text','label'=>'Имя отправителя','required'=>true,'priority'=>80,'class'=>['form-row-wide']];
 $fields['order']['cg_sender_phone']=['type'=>'tel','label'=>'Телефон отправителя','required'=>true,'priority'=>90,'class'=>['form-row-wide']];
 $fields['order']['cg_sender_email']=['type'=>'email','label'=>'Email отправителя','required'=>true,'priority'=>100,'class'=>['form-row-wide']];
 return $fields;
},20);

add_action('woocommerce_checkout_create_order',function($order){
 foreach(['cg_sender_name','cg_sender_phone','cg_sender_email'] as $k){if(isset($_POST[$k])) $order->update_meta_data('_'.$k,sanitize_text_field(wp_unslash($_POST[$k])));}
},10);
