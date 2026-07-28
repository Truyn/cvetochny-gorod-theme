<?php
/**
 * Delivery options for the WooCommerce checkout.
 */

if (!defined('ABSPATH')) exit;

function cg_delivery_options_assets() {
    if (!is_checkout()) return;
    wp_enqueue_style('cg-delivery-options', get_template_directory_uri() . '/assets/css/delivery-options.css', ['cg-woocommerce'], wp_get_theme()->get('Version'));
}
add_action('wp_enqueue_scripts', 'cg_delivery_options_assets', 20);

function cg_delivery_checkout_fields($fields) {
    $fields['order']['cg_delivery_date'] = [
        'type' => 'date',
        'label' => 'Дата доставки',
        'required' => true,
        'class' => ['cg-checkout-half', 'cg-checkout-field'],
        'priority' => 20,
        'custom_attributes' => ['min' => wp_date('Y-m-d')],
    ];

    $fields['order']['cg_delivery_time'] = [
        'type' => 'select',
        'label' => 'Интервал доставки',
        'required' => true,
        'class' => ['cg-checkout-half', 'cg-checkout-field'],
        'priority' => 21,
        'options' => ['', '09:00–12:00' => '09:00–12:00', '12:00–15:00' => '12:00–15:00', '15:00–18:00' => '15:00–18:00', '18:00–21:00' => '18:00–21:00', 'По согласованию' => 'По согласованию с менеджером'],
    ];

    $fields['order']['cg_card_message'] = [
        'type' => 'textarea',
        'label' => 'Текст для бесплатной открытки',
        'placeholder' => 'Напишите пожелание получателю',
        'required' => false,
        'class' => ['form-row-wide', 'cg-checkout-field'],
        'priority' => 22,
    ];

    $fields['order']['cg_anonymous_delivery'] = [
        'type' => 'checkbox',
        'label' => 'Хочу анонимную доставку',
        'required' => false,
        'class' => ['form-row-wide', 'cg-checkout-checkbox'],
        'priority' => 23,
    ];

    unset($fields['order']['cg_hide_price']);
    return $fields;
}
add_filter('woocommerce_checkout_fields', 'cg_delivery_checkout_fields');

function cg_remove_optional_labels($fields) {
    foreach ($fields as $group => $items) {
        foreach ($items as $key => $field) {
            if (isset($fields[$group][$key]['label'])) {
                $fields[$group][$key]['label'] = str_replace([' (необязательно)', ' (необязательно)'], '', $fields[$group][$key]['label']);
            }
            if (isset($fields[$group][$key]['placeholder'])) {
                $fields[$group][$key]['placeholder'] = str_replace([' (необязательно)', ' (не обязательно)'], '', $fields[$group][$key]['placeholder']);
            }
        }
    }
    return $fields;
}
add_filter('woocommerce_checkout_fields', 'cg_remove_optional_labels', 50);

function cg_validate_delivery_checkout_fields() {
    if (!empty($_POST['cg_delivery_date']) && sanitize_text_field(wp_unslash($_POST['cg_delivery_date'])) < wp_date('Y-m-d')) {
        wc_add_notice('Дата доставки не может быть в прошлом.', 'error');
    }
}
add_action('woocommerce_checkout_process', 'cg_validate_delivery_checkout_fields');

function cg_save_delivery_checkout_fields($order, $data) {
    foreach (['cg_delivery_date', 'cg_delivery_time', 'cg_card_message'] as $field) {
        if (isset($_POST[$field])) $order->update_meta_data('_' . $field, sanitize_text_field(wp_unslash($_POST[$field])));
    }
    $order->update_meta_data('_cg_anonymous_delivery', isset($_POST['cg_anonymous_delivery']) ? 'yes' : 'no');
}
add_action('woocommerce_checkout_create_order', 'cg_save_delivery_checkout_fields', 10, 2);

function cg_change_coupon_text() {
    wc_print_notice('', 'notice');
}
add_filter('woocommerce_checkout_coupon_message', function() {
    return 'Есть промокод? <a href="#" class="showcoupon">Нажмите, чтобы ввести</a>';
});
