<?php
/**
 * Backward compatibility for order options saved by older product pages.
 *
 * New orders collect card text, delivery date/time and florist comments only
 * on the checkout page. This avoids duplicate fields and prevents customers
 * from entering the same information twice.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/**
 * Build public item data for carts created before product-page fields were
 * removed. New cart items no longer receive `cg_order_options` data.
 */
function cg_get_product_order_options_item_data($cart_item) {
    if (empty($cart_item['cg_order_options'])) return [];

    $labels = [
        'card_message' => 'Текст открытки',
        'florist_note' => 'Пожелания флористу',
        'delivery_date' => 'Дата доставки',
        'delivery_interval' => 'Интервал доставки',
        'anonymous_delivery' => 'Анонимная доставка',
        'call_recipient' => 'Позвонить заранее',
    ];

    $item_data = [];
    foreach ($labels as $key => $label) {
        if (empty($cart_item['cg_order_options'][$key])) continue;

        $value = $cart_item['cg_order_options'][$key];
        if ($key === 'delivery_date') {
            $timestamp = strtotime($value);
            if ($timestamp) $value = wp_date('d.m.Y', $timestamp);
        }

        $item_data[] = [
            'key' => $label,
            'value' => wp_kses_post(nl2br(esc_html($value))),
        ];
    }

    return $item_data;
}

/** Show legacy options in classic cart and checkout templates. */
function cg_display_product_order_options($item_data, $cart_item) {
    return array_merge($item_data, cg_get_product_order_options_item_data($cart_item));
}
add_filter('woocommerce_get_item_data', 'cg_display_product_order_options', 10, 2);

/** Expose legacy options to Cart and Checkout blocks through the Store API. */
function cg_register_product_order_options_store_api() {
    if (!function_exists('woocommerce_store_api_register_endpoint_data') || !class_exists('Automattic\\WooCommerce\\StoreApi\\Schemas\\V1\\CartItemSchema')) return;

    woocommerce_store_api_register_endpoint_data([
        'endpoint' => Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema::IDENTIFIER,
        'namespace' => 'cvetochny-gorod',
        'data_callback' => function($cart_item) {
            return [
                'order_options' => cg_get_product_order_options_item_data($cart_item),
            ];
        },
        'schema_callback' => function() {
            return [
                'order_options' => [
                    'description' => 'Ранее сохранённые детали букета и доставки.',
                    'type' => 'array',
                    'readonly' => true,
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'key' => ['type' => 'string'],
                            'value' => ['type' => 'string'],
                        ],
                    ],
                ],
            ];
        },
        'schema_type' => ARRAY_A,
    ]);
}
add_action('woocommerce_blocks_loaded', 'cg_register_product_order_options_store_api');

/** Preserve legacy options on an order if they are already present in cart. */
function cg_save_product_order_options_to_order($item, $cart_item_key, $values) {
    if (empty($values['cg_order_options'])) return;

    $labels = [
        'card_message' => 'Текст открытки',
        'florist_note' => 'Пожелания флористу',
        'delivery_date' => 'Дата доставки',
        'delivery_interval' => 'Интервал доставки',
        'anonymous_delivery' => 'Анонимная доставка',
        'call_recipient' => 'Позвонить заранее',
    ];

    foreach ($labels as $key => $label) {
        if (!empty($values['cg_order_options'][$key])) {
            $item->add_meta_data($label, $values['cg_order_options'][$key], true);
        }
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'cg_save_product_order_options_to_order', 10, 3);
