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
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout cg-classic-checkout" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data" aria-label="Оформление заказа">
    <div class="cg-classic-checkout__main">
        <?php if ($checkout->get_checkout_fields()) : ?>
            <?php do_action('woocommerce_checkout_before_customer_details'); ?>

            <section class="cg-checkout-card cg-checkout-card--customer">
                <div class="cg-checkout-card__heading">
                    <span>1</span>
                    <div><small>Контакты и адрес</small><h2>Данные получателя</h2></div>
                </div>
                <div id="customer_details" class="col2-set">
                    <div class="col-1"><?php do_action('woocommerce_checkout_billing'); ?></div>
                    <div class="col-2"><?php do_action('woocommerce_checkout_shipping'); ?></div>
                </div>
            </section>

            <?php do_action('woocommerce_checkout_after_customer_details'); ?>
        <?php endif; ?>

        <section class="cg-checkout-card cg-checkout-card--notes">
            <div class="cg-checkout-card__heading">
                <span>2</span>
                <div><small>Дополнительные сведения</small><h2>Пожелания к заказу</h2></div>
            </div>
            <?php do_action('woocommerce_before_order_notes', $checkout); ?>
            <?php if (apply_filters('woocommerce_enable_order_notes_field', 'yes' === get_option('woocommerce_enable_order_comments', 'yes'))) : ?>
                <div class="woocommerce-additional-fields">
                    <?php foreach ($checkout->get_checkout_fields('order') as $key => $field) : ?>
                        <?php woocommerce_form_field($key, $field, $checkout->get_value($key)); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php do_action('woocommerce_after_order_notes', $checkout); ?>
        </section>
    </div>

    <aside class="cg-classic-checkout__sidebar">
        <section class="cg-checkout-card cg-checkout-card--summary">
            <div class="cg-checkout-card__heading">
                <span>3</span>
                <div><small>Проверьте перед оплатой</small><h2><?php esc_html_e('Your order', 'woocommerce'); ?></h2></div>
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
