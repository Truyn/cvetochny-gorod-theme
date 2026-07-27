<?php
/**
 * Product order options: card text, florist note and delivery preferences.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/** Render optional order details below the main product area. */
function cg_product_order_options_fields() {
    global $product;
    if (!$product instanceof WC_Product || !$product->is_purchasable()) return;

    echo '<section class="cg-product-options" aria-labelledby="cg-product-options-title">';
    echo '<div class="cg-product-options__head"><span>Персонализируйте заказ</span><h3 id="cg-product-options-title">Детали букета и доставки</h3><p>Заполните только нужные поля. Все пожелания сохранятся в корзине и будут видны при оформлении заказа.</p></div>';

    echo '<div class="cg-product-options__grid">';

    echo '<label class="cg-product-option cg-product-option--wide">';
    echo '<span class="cg-product-option__title">Текст для бесплатной открытки</span>';
    echo '<textarea name="cg_card_message" maxlength="300" rows="4" placeholder="Например: С днём рождения! Пусть каждый день будет наполнен радостью."></textarea>';
    echo '<small>Оставьте поле пустым, если открытка не нужна.</small>';
    echo '</label>';

    echo '<label class="cg-product-option cg-product-option--wide">';
    echo '<span class="cg-product-option__title">Пожелания флористу</span>';
    echo '<textarea name="cg_florist_note" maxlength="400" rows="4" placeholder="Например: сделать букет более нежным, не использовать лилии, добавить больше зелени."></textarea>';
    echo '<small>Точный состав зависит от наличия цветов; важные замены согласуем.</small>';
    echo '</label>';

    echo '<label class="cg-product-option">';
    echo '<span class="cg-product-option__title">Желаемая дата доставки</span>';
    echo '<input type="date" name="cg_delivery_date" min="'.esc_attr(wp_date('Y-m-d')).'">';
    echo '</label>';

    echo '<label class="cg-product-option">';
    echo '<span class="cg-product-option__title">Удобный интервал</span>';
    echo '<select name="cg_delivery_interval">';
    echo '<option value="">Уточнить после заказа</option>';
    echo '<option value="07:00–09:00">07:00–09:00</option>';
    echo '<option value="09:00–11:00">09:00–11:00</option>';
    echo '<option value="11:00–13:00">11:00–13:00</option>';
    echo '<option value="13:00–15:00">13:00–15:00</option>';
    echo '<option value="15:00–17:00">15:00–17:00</option>';
    echo '<option value="17:00–19:00">17:00–19:00</option>';
    echo '<option value="19:00–21:00">19:00–21:00</option>';
    echo '<option value="21:00–22:00">21:00–22:00</option>';
    echo '</select>';
    echo '</label>';

    echo '</div>';

    echo '<div class="cg-product-options__toggles">';
    echo '<label><input type="checkbox" name="cg_anonymous_delivery" value="yes"><span><strong>Анонимная доставка</strong><small>Не сообщать получателю имя отправителя.</small></span></label>';
    echo '<label><input type="checkbox" name="cg_call_recipient" value="yes"><span><strong>Позвонить получателю заранее</strong><small>Курьер уточнит удобство получения перед приездом.</small></span></label>';
    echo '</div>';

    echo '</section>';
}
add_action('woocommerce_after_single_product_summary', 'cg_product_order_options_fields', 7);

/** Validate user-entered date and interval. */
function cg_validate_product_order_options($passed) {
    if (!empty($_POST['cg_delivery_date'])) {
        $date = sanitize_text_field(wp_unslash($_POST['cg_delivery_date']));
        $parsed = DateTime::createFromFormat('Y-m-d', $date);
        $today = new DateTime(wp_date('Y-m-d'));

        if (!$parsed || $parsed->format('Y-m-d') !== $date || $parsed < $today) {
            wc_add_notice('Пожалуйста, выберите корректную дату доставки.', 'error');
            return false;
        }
    }

    if (!empty($_POST['cg_delivery_interval'])) {
        $allowed_intervals = [
            '07:00–09:00',
            '09:00–11:00',
            '11:00–13:00',
            '13:00–15:00',
            '15:00–17:00',
            '17:00–19:00',
            '19:00–21:00',
            '21:00–22:00',
        ];
        $interval = sanitize_text_field(wp_unslash($_POST['cg_delivery_interval']));
        if (!in_array($interval, $allowed_intervals, true)) {
            wc_add_notice('Пожалуйста, выберите доступный интервал доставки.', 'error');
            return false;
        }
    }

    return $passed;
}
add_filter('woocommerce_add_to_cart_validation', 'cg_validate_product_order_options', 10, 1);

/** Store the options in the cart item. */
function cg_add_product_order_options_to_cart($cart_item_data) {
    $text_fields = [
        'cg_card_message' => 'card_message',
        'cg_florist_note' => 'florist_note',
        'cg_delivery_date' => 'delivery_date',
        'cg_delivery_interval' => 'delivery_interval',
    ];

    foreach ($text_fields as $request_key => $cart_key) {
        if (!empty($_POST[$request_key])) {
            $cart_item_data['cg_order_options'][$cart_key] = sanitize_textarea_field(wp_unslash($_POST[$request_key]));
        }
    }

    if (!empty($_POST['cg_anonymous_delivery'])) $cart_item_data['cg_order_options']['anonymous_delivery'] = 'Да';
    if (!empty($_POST['cg_call_recipient'])) $cart_item_data['cg_order_options']['call_recipient'] = 'Да';

    if (!empty($cart_item_data['cg_order_options'])) {
        $cart_item_data['cg_order_options_key'] = wp_generate_uuid4();
    }

    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'cg_add_product_order_options_to_cart', 10, 1);

/** Show options in cart and checkout. */
function cg_display_product_order_options($item_data, $cart_item) {
    if (empty($cart_item['cg_order_options'])) return $item_data;

    $labels = [
        'card_message' => 'Текст открытки',
        'florist_note' => 'Пожелания флористу',
        'delivery_date' => 'Дата доставки',
        'delivery_interval' => 'Интервал доставки',
        'anonymous_delivery' => 'Анонимная доставка',
        'call_recipient' => 'Позвонить заранее',
    ];

    foreach ($labels as $key => $label) {
        if (!empty($cart_item['cg_order_options'][$key])) {
            $value = $cart_item['cg_order_options'][$key];
            if ($key === 'delivery_date') {
                $timestamp = strtotime($value);
                if ($timestamp) $value = wp_date('d.m.Y', $timestamp);
            }
            $item_data[] = ['key' => $label, 'value' => wp_kses_post(nl2br(esc_html($value)))];
        }
    }

    return $item_data;
}
add_filter('woocommerce_get_item_data', 'cg_display_product_order_options', 10, 2);

/** Persist options on the WooCommerce order item. */
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
