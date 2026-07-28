<?php
/** Custom checkout layout for Цветочный город. */
if (!defined('ABSPATH')) exit;

do_action('woocommerce_before_checkout_form', $checkout);

$billing_fields = $checkout->get_checkout_fields('billing');
$order_fields = $checkout->get_checkout_fields('order');
$sender_keys = ['cg_sender_first_name', 'cg_sender_last_name', 'cg_sender_phone', 'cg_sender_email'];
?>
<form name="checkout" method="post" class="checkout woocommerce-checkout cg-classic-checkout" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data">
<div class="cg-classic-checkout__main">
<div id="customer_details" class="cg-checkout-customer-details">
<section class="cg-checkout-card cg-checkout-card--recipient">
<h2>Данные получателя</h2>
<div class="cg-checkout-fields-grid">
<?php foreach ($billing_fields as $key => $field) woocommerce_form_field($key,$field,$checkout->get_value($key)); ?>
</div>
</section>
<section class="cg-checkout-card cg-checkout-card--sender">
<h2>Данные отправителя</h2>
<div class="cg-checkout-fields-grid">
<?php foreach ($sender_keys as $key) if(isset($order_fields[$key])) woocommerce_form_field($key,$order_fields[$key],$checkout->get_value($key)); ?>
</div>
</section>
<section class="cg-checkout-card cg-checkout-card--notes">
<h2>Пожелания к заказу</h2>
<div class="woocommerce-additional-fields cg-checkout-fields-grid">
<?php foreach ($order_fields as $key=>$field): if(in_array($key,$sender_keys,true)) continue; woocommerce_form_field($key,$field,$checkout->get_value($key)); endforeach; ?>
</div>
</section>
</div>
</div>
<aside class="cg-classic-checkout__sidebar">
<section class="cg-checkout-card cg-checkout-card--summary">
<h2>Ваш заказ</h2>
<div id="order_review" class="woocommerce-checkout-review-order">
<?php do_action('woocommerce_checkout_order_review'); ?>
</div>
</section>
</aside>
</form>
<?php do_action('woocommerce_after_checkout_form', $checkout); ?>