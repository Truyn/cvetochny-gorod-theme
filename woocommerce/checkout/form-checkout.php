<?php
/**
 * Premium classic checkout layout for Цветочный город.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/** @var WC_Checkout $checkout */
do_action('woocommerce_before_checkout_form', $checkout);

if (!$checkout->is_registration_enabled() && $checkout->is_registration_required() && !is_user_logged_in()) {
    echo esc_html(apply_filters(
        'woocommerce_checkout_must_be_logged_in_message',
        __('You must be logged in to checkout.', 'woocommerce')
    ));
    return;
}

$billing_fields = $checkout->get_checkout_fields('billing');
$order_fields   = $checkout->get_checkout_fields('order');

$sender_keys = [
    'cg_sender_first_name',
    'cg_sender_last_name',
    'cg_sender_phone',
    'cg_sender_email',
];

$delivery_keys = [
    'cg_delivery_zone',
    'cg_delivery_custom_city',
    'cg_delivery_date',
    'cg_delivery_time',
    'cg_card_message',
    'order_comments',
];
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout cg-classic-checkout" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data" aria-label="Оформление заказа">
    <div class="cg-classic-checkout__main">
        <section class="cg-checkout-card cg-checkout-card--recipient">
            <div class="cg-checkout-card__heading">
                <span>1</span>
                <div>
                    <small>Кому доставить букет</small>
                    <h2>Данные получателя</h2>
                </div>
            </div>
            <div id="customer_details" class="cg-checkout-fields-grid">
                <?php foreach ($billing_fields as $key => $field) : ?>
                    <?php woocommerce_form_field($key, $field, $checkout->get_value($key)); ?>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="cg-checkout-card cg-checkout-card--sender">
            <div class="cg-checkout-card__heading">
                <span>2</span>
                <div>
                    <small>Кто оформляет заказ</small>
                    <h2>Данные отправителя</h2>
                </div>
            </div>
            <div class="cg-checkout-fields-grid">
                <?php foreach ($sender_keys as $key) : ?>
                    <?php if (isset($order_fields[$key])) : ?>
                        <?php woocommerce_form_field($key, $order_fields[$key], $checkout->get_value($key)); ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="cg-checkout-card cg-checkout-card--delivery">
            <div class="cg-checkout-card__heading">
                <span>3</span>
                <div>
                    <small>Населённый пункт, дата и пожелания</small>
                    <h2>Доставка и пожелания</h2>
                </div>
            </div>
            <div class="cg-checkout-fields-grid cg-checkout-delivery-fields">
                <?php foreach ($delivery_keys as $key) : ?>
                    <?php if (!isset($order_fields[$key])) continue; ?>
                    <?php woocommerce_form_field($key, $order_fields[$key], $checkout->get_value($key)); ?>

                    <?php if ($key === 'cg_delivery_zone') : ?>
                        <div class="cg-delivery-zone-note" id="cg_delivery_zone_note" aria-live="polite">
                            Выберите населённый пункт — стоимость доставки сразу появится в заказе.
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php foreach ($order_fields as $key => $field) : ?>
                    <?php if (in_array($key, array_merge($sender_keys, $delivery_keys, ['cg_anonymous_delivery', 'cg_hide_price', 'order_comments_upload']), true)) continue; ?>
                    <?php woocommerce_form_field($key, $field, $checkout->get_value($key)); ?>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <aside class="cg-classic-checkout__sidebar">
        <section class="cg-checkout-card cg-checkout-card--summary">
            <div class="cg-checkout-card__heading">
                <span>4</span>
                <div>
                    <small>Проверьте состав и сумму</small>
                    <h2>Ваш заказ</h2>
                </div>
            </div>

            <?php do_action('woocommerce_checkout_before_order_review'); ?>
            <div id="order_review" class="woocommerce-checkout-review-order">
                <?php do_action('woocommerce_checkout_order_review'); ?>
            </div>
            <?php do_action('woocommerce_checkout_after_order_review'); ?>
        </section>
    </aside>
</form>

<?php do_action('woocommerce_after_checkout_form', $checkout); ?>
