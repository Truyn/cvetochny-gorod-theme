<?php
/**
 * Delivery options for the WooCommerce checkout.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/** Add flower-store delivery fields to checkout. */
function cg_delivery_checkout_fields($fields) {
    $fields['order']['cg_delivery_date'] = [
        'type' => 'date',
        'label' => 'Дата доставки',
        'required' => true,
        'class' => ['form-row-first', 'cg-checkout-field'],
        'priority' => 20,
        'custom_attributes' => [
            'min' => wp_date('Y-m-d'),
        ],
    ];

    $fields['order']['cg_delivery_time'] = [
        'type' => 'select',
        'label' => 'Интервал доставки',
        'required' => true,
        'class' => ['form-row-last', 'cg-checkout-field'],
        'priority' => 21,
        'options' => [
            '' => 'Выберите интервал',
            '09:00–12:00' => '09:00–12:00',
            '12:00–15:00' => '12:00–15:00',
            '15:00–18:00' => '15:00–18:00',
            '18:00–21:00' => '18:00–21:00',
            'По согласованию' => 'По согласованию с менеджером',
        ],
    ];

    $fields['order']['cg_card_message'] = [
        'type' => 'textarea',
        'label' => 'Текст для бесплатной открытки',
        'placeholder' => 'Напишите пожелание получателю',
        'required' => false,
        'class' => ['form-row-wide', 'cg-checkout-field'],
        'priority' => 22,
        'custom_attributes' => [
            'maxlength' => 300,
        ],
    ];

    $fields['order']['cg_anonymous_delivery'] = [
        'type' => 'checkbox',
        'label' => 'Анонимная доставка — не сообщать имя отправителя',
        'required' => false,
        'class' => ['form-row-wide', 'cg-checkout-checkbox'],
        'priority' => 23,
    ];

    $fields['order']['cg_hide_price'] = [
        'type' => 'checkbox',
        'label' => 'Не вкладывать документы с ценой в заказ',
        'required' => false,
        'class' => ['form-row-wide', 'cg-checkout-checkbox'],
        'priority' => 24,
    ];

    return $fields;
}
add_filter('woocommerce_checkout_fields', 'cg_delivery_checkout_fields');

/** Validate delivery date. */
function cg_validate_delivery_checkout_fields() {
    if (empty($_POST['cg_delivery_date'])) return;

    $delivery_date = sanitize_text_field(wp_unslash($_POST['cg_delivery_date']));
    $today = wp_date('Y-m-d');

    if ($delivery_date < $today) {
        wc_add_notice('Дата доставки не может быть в прошлом.', 'error');
    }
}
add_action('woocommerce_checkout_process', 'cg_validate_delivery_checkout_fields');

/** Save checkout options to order meta. */
function cg_save_delivery_checkout_fields($order, $data) {
    $text_fields = [
        'cg_delivery_date',
        'cg_delivery_time',
        'cg_card_message',
    ];

    foreach ($text_fields as $field) {
        if (isset($_POST[$field])) {
            $value = $field === 'cg_card_message'
                ? sanitize_textarea_field(wp_unslash($_POST[$field]))
                : sanitize_text_field(wp_unslash($_POST[$field]));
            $order->update_meta_data('_' . $field, $value);
        }
    }

    $order->update_meta_data('_cg_anonymous_delivery', isset($_POST['cg_anonymous_delivery']) ? 'yes' : 'no');
    $order->update_meta_data('_cg_hide_price', isset($_POST['cg_hide_price']) ? 'yes' : 'no');
}
add_action('woocommerce_checkout_create_order', 'cg_save_delivery_checkout_fields', 10, 2);

/** Show delivery options in the admin order screen. */
function cg_admin_delivery_order_meta($order) {
    $date = $order->get_meta('_cg_delivery_date');
    $time = $order->get_meta('_cg_delivery_time');
    $message = $order->get_meta('_cg_card_message');
    $anonymous = $order->get_meta('_cg_anonymous_delivery');
    $hide_price = $order->get_meta('_cg_hide_price');

    echo '<div class="cg-order-delivery-meta"><h3>Доставка букета</h3>';
    if ($date) echo '<p><strong>Дата:</strong> ' . esc_html(wp_date('d.m.Y', strtotime($date))) . '</p>';
    if ($time) echo '<p><strong>Интервал:</strong> ' . esc_html($time) . '</p>';
    if ($message) echo '<p><strong>Открытка:</strong><br>' . nl2br(esc_html($message)) . '</p>';
    echo '<p><strong>Анонимно:</strong> ' . ($anonymous === 'yes' ? 'Да' : 'Нет') . '</p>';
    echo '<p><strong>Скрыть цену:</strong> ' . ($hide_price === 'yes' ? 'Да' : 'Нет') . '</p>';
    echo '</div>';
}
add_action('woocommerce_admin_order_data_after_shipping_address', 'cg_admin_delivery_order_meta');

/** Add delivery options to order emails and customer order details. */
function cg_delivery_email_meta_fields($fields, $sent_to_admin, $order) {
    $date = $order->get_meta('_cg_delivery_date');
    $time = $order->get_meta('_cg_delivery_time');
    $message = $order->get_meta('_cg_card_message');

    if ($date) {
        $fields['cg_delivery_date'] = [
            'label' => 'Дата доставки',
            'value' => wp_date('d.m.Y', strtotime($date)),
        ];
    }
    if ($time) {
        $fields['cg_delivery_time'] = [
            'label' => 'Интервал доставки',
            'value' => $time,
        ];
    }
    if ($message) {
        $fields['cg_card_message'] = [
            'label' => 'Текст открытки',
            'value' => $message,
        ];
    }

    $fields['cg_anonymous_delivery'] = [
        'label' => 'Анонимная доставка',
        'value' => $order->get_meta('_cg_anonymous_delivery') === 'yes' ? 'Да' : 'Нет',
    ];
    $fields['cg_hide_price'] = [
        'label' => 'Не вкладывать цену',
        'value' => $order->get_meta('_cg_hide_price') === 'yes' ? 'Да' : 'Нет',
    ];

    return $fields;
}
add_filter('woocommerce_email_order_meta_fields', 'cg_delivery_email_meta_fields', 10, 3);
