<?php
/**
 * Public order-meta mirrors for the official WooCommerce mobile app.
 *
 * The WooCommerce apps intentionally hide metadata keys prefixed with an
 * underscore. The storefront keeps its existing private _cg_* fields as the
 * source of truth and mirrors a small, human-readable subset to public order
 * metadata so store managers can see delivery details in Custom Fields.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/**
 * Build the human-readable fields shown in WooCommerce mobile Custom Fields.
 *
 * @param WC_Order $order         Order being synchronized.
 * @param array    $checkout_data Optional checkout data while the order is being created.
 * @return array<string,string>
 */
function cg_mobile_order_visible_values($order, $checkout_data = []) {
    if (!$order instanceof WC_Order) return [];

    $delivery_city = trim((string) $order->get_meta('_cg_delivery_city'));
    $delivery_date = trim((string) $order->get_meta('_cg_delivery_date'));
    $delivery_time = trim((string) $order->get_meta('_cg_delivery_time'));
    $card_message = trim((string) $order->get_meta('_cg_card_message'));

    $sender_name = trim(implode(' ', array_filter([
        (string) $order->get_meta('_cg_sender_first_name'),
        (string) $order->get_meta('_cg_sender_last_name'),
    ])));
    $sender_phone = trim((string) $order->get_meta('_cg_sender_phone'));
    $sender_email = trim((string) $order->get_meta('_cg_sender_email'));

    if ($delivery_city === '') {
        $delivery_city = trim((string) $order->get_billing_city());
    }

    if ($delivery_date !== '' && strtotime($delivery_date)) {
        $delivery_date = wp_date('d.m.Y', strtotime($delivery_date));
    }

    $customer_note = '';
    if (isset($checkout_data['order_comments'])) {
        $customer_note = trim(sanitize_textarea_field($checkout_data['order_comments']));
    }
    if ($customer_note === '') {
        $customer_note = trim((string) $order->get_customer_note());
    }

    return [
        'ЦГ — Населённый пункт' => $delivery_city,
        'ЦГ — Дата доставки' => $delivery_date,
        'ЦГ — Интервал доставки' => $delivery_time,
        'ЦГ — Открытка' => $card_message,
        'ЦГ — Отправитель' => $sender_name,
        'ЦГ — Телефон отправителя' => $sender_phone,
        'ЦГ — Email отправителя' => $sender_email,
        'ЦГ — Комментарий флористу и курьеру' => $customer_note,
    ];
}

/**
 * Mirror the private storefront order fields into public, app-visible metadata.
 * Empty values are removed so the mobile screen stays compact.
 *
 * @param WC_Order $order         Order being synchronized.
 * @param array    $checkout_data Optional checkout data while creating the order.
 */
function cg_mobile_order_sync_visible_fields($order, $checkout_data = []) {
    if (!$order instanceof WC_Order) return;

    foreach (cg_mobile_order_visible_values($order, $checkout_data) as $label => $value) {
        $value = trim((string) $value);

        if ($value === '') {
            $order->delete_meta_data($label);
            continue;
        }

        $order->update_meta_data($label, $value);
    }
}

/**
 * New checkout orders: private delivery/sender metadata is written at priority
 * 10, so priority 50 can safely mirror the finished values before first save.
 */
function cg_mobile_order_sync_checkout_fields($order, $data) {
    cg_mobile_order_sync_visible_fields($order, is_array($data) ? $data : []);
}
add_action('woocommerce_checkout_create_order', 'cg_mobile_order_sync_checkout_fields', 50, 2);

/**
 * Existing orders: whenever a manager saves an order in wp-admin, refresh the
 * mobile mirrors. This also backfills older orders as they are worked with.
 */
function cg_mobile_order_sync_after_admin_save($order_id) {
    if (!function_exists('wc_get_order')) return;

    $order = wc_get_order($order_id);
    if (!$order instanceof WC_Order) return;

    cg_mobile_order_sync_visible_fields($order);
    $order->save();
}
add_action('woocommerce_process_shop_order_meta', 'cg_mobile_order_sync_after_admin_save', 50, 1);

/**
 * Existing orders also receive the visible fields on their next status change,
 * including status changes made from the WooCommerce mobile app.
 */
function cg_mobile_order_sync_after_status_change($order_id, $from, $to, $order = null) {
    if (!$order instanceof WC_Order && function_exists('wc_get_order')) {
        $order = wc_get_order($order_id);
    }
    if (!$order instanceof WC_Order) return;

    cg_mobile_order_sync_visible_fields($order);
    $order->save();
}
add_action('woocommerce_order_status_changed', 'cg_mobile_order_sync_after_status_change', 50, 4);
