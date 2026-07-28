<?php
/**
 * Custom classic checkout layout for Цветочный город.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/** @var WC_Checkout $checkout */
do_action('woocommerce_before_checkout_form', $checkout);

if (!$checkout->is_registration_enabled() && $checkout->is_registration_required() && !is_user_logged_in()) {
    echo esc_html(apply_filters('woocommerce_checkout_must_be_logged_in_message', __('You must be logged in to checkout.', 'woocommerce')));
    return;
}

$billing_fields = $checkout->get_checkout_fields('billing');
$order_fields   = $checkout->get_checkout_fields('order');
$sender_keys    = ['cg_sender_first_name', 'cg_sender_last_name', 'cg_sender_phone', 'cg_sender_email'];
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout cg-classic-checkout" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data" aria-label="Оформление заказа">
    <div class="cg-classic-checkout__main">
        <?php do_action('woocommerce_checkout_before_customer_details'); ?>

        <section class="cg-checkout-card cg-checkout-card--recipient">
            <div class="cg-checkout-card__heading">
                <span>1</span>
                <div><small>Кому доставить заказ</small><h2>Данные получателя</h2></div>
            </div>
            <div class="cg-checkout-fields-grid cg-checkout-fields-grid--recipient">
                <?php foreach ($billing_fields as $key => $field) : ?>
                    <?php woocommerce_form_field($key, $field, $checkout->get_value($key)); ?>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="cg-checkout-card cg-checkout-card--sender">
            <div class="cg-checkout-card__heading">
                <span>2</span>
                <div><small>С кем связаться по заказу</small><h2>Данные отправителя</h2></div>
            </div>
            <div class="cg-checkout-fields-grid cg-checkout-fields-grid--sender">
                <?php foreach ($sender_keys as $key) : ?>
                    <?php if (!isset($order_fields[$key])) continue; ?>
                    <?php woocommerce_form_field($key, $order_fields[$key], $checkout->get_value($key)); ?>
                <?php endforeach; ?>
            </div>
        </section>

        <?php do_action('woocommerce_checkout_after_customer_details'); ?>

        <section class="cg-checkout-card cg-checkout-card--notes">
            <div class="cg-checkout-card__heading">
                <span>3</span>
                <div><small>Дата, открытка и детали</small><h2>Пожелания к заказу</h2></div>
            </div>
            <?php do_action('woocommerce_before_order_notes', $checkout); ?>
            <div class="woocommerce-additional-fields cg-checkout-fields-grid cg-checkout-fields-grid--notes">
                <?php foreach ($order_fields as $key => $field) : ?>
                    <?php if (in_array($key, $sender_keys, true)) continue; ?>
                    <?php woocommerce_form_field($key, $field, $checkout->get_value($key)); ?>
                <?php endforeach; ?>
            </div>
            <?php do_action('woocommerce_after_order_notes', $checkout); ?>
        </section>
    </div>

    <aside class="cg-classic-checkout__sidebar">
        <section class="cg-checkout-card cg-checkout-card--summary">
            <div class="cg-checkout-card__heading">
                <span>4</span>
                <div><small>Проверьте товары и сумму</small><h2>Ваш заказ</h2></div>
            </div>

            <?php do_action('woocommerce_checkout_before_order_review_heading'); ?>
            <h3 id="order_review_heading" class="screen-reader-text"><?php esc_html_e('Your order', 'woocommerce'); ?></h3>
            <?php do_action('woocommerce_checkout_before_order_review'); ?>
            <div id="order_review" class="woocommerce-checkout-review-order">
                <?php do_action('woocommerce_checkout_order_review'); ?>
            </div>
            <?php do_action('woocommerce_checkout_after_order_review'); ?>
        </section>
    </aside>
</form>

<?php do_action('woocommerce_after_checkout_form', $checkout); ?>
