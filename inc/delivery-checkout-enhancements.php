<?php
/**
 * Resilient checkout delivery selector and free-delivery rules.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/** Free delivery is available only for Novovoronezh from this cart subtotal. */
function cg_get_novovoronezh_free_delivery_threshold() {
    return max(0, (float) apply_filters('cg_novovoronezh_free_delivery_threshold', 10000));
}

/** Normalize a delivery method title for reliable settlement matching. */
function cg_normalize_delivery_title($title) {
    $title = trim(wp_strip_all_tags((string) $title));
    $title = function_exists('mb_strtolower')
        ? mb_strtolower($title, 'UTF-8')
        : strtolower($title);

    return str_replace('ё', 'е', $title);
}

/** Detect the Novovoronezh method by the title configured in WooCommerce. */
function cg_delivery_zone_is_novovoronezh($zone) {
    if (!is_array($zone) || empty($zone['label'])) return false;

    return strpos(cg_normalize_delivery_title($zone['label']), 'нововоронеж') !== false;
}

/** Cart subtotal used by the free-delivery rule. */
function cg_delivery_cart_subtotal() {
    if (!function_exists('WC') || !WC()->cart) return 0;

    return max(0, (float) WC()->cart->get_subtotal());
}

/** Check whether the current cart has earned free delivery in Novovoronezh. */
function cg_novovoronezh_free_delivery_available() {
    $threshold = cg_get_novovoronezh_free_delivery_threshold();

    return $threshold > 0 && cg_delivery_cart_subtotal() >= $threshold;
}

/**
 * Apply the threshold to the same zone data used by cart, checkout and order.
 * Other settlements always retain their configured WooCommerce price.
 */
function cg_apply_novovoronezh_free_delivery_to_zones($zones) {
    if (!is_array($zones) || !cg_novovoronezh_free_delivery_available()) return $zones;

    foreach ($zones as $key => $zone) {
        if (!cg_delivery_zone_is_novovoronezh($zone)) continue;

        $zones[$key]['configured_price'] = isset($zone['price']) ? (float) $zone['price'] : 0;
        $zones[$key]['price'] = 0;
        $zones[$key]['free_by_threshold'] = true;
    }

    return $zones;
}
add_filter('cg_delivery_zones', 'cg_apply_novovoronezh_free_delivery_to_zones', 20);

/** The selector is rendered explicitly, so remove its duplicate checkout fields. */
function cg_remove_duplicate_delivery_selector_fields($fields) {
    unset($fields['order']['cg_delivery_zone'], $fields['order']['cg_delivery_custom_city']);
    return $fields;
}
add_filter('woocommerce_checkout_fields', 'cg_remove_duplicate_delivery_selector_fields', 40);

/** Read the selector value from the current request first and session second. */
function cg_checkout_delivery_value($key, $default = '') {
    if (isset($_POST[$key])) {
        return sanitize_text_field(wp_unslash($_POST[$key]));
    }

    if (function_exists('WC') && WC()->session) {
        return (string) WC()->session->get($key, $default);
    }

    return $default;
}

/** Build selector choices from enabled WooCommerce flat-rate methods. */
function cg_checkout_delivery_options() {
    $options = ['' => 'Выберите населённый пункт'];

    foreach (cg_get_delivery_zones() as $key => $zone) {
        $is_free = !empty($zone['free_by_threshold']) || ((float) $zone['price'] <= 0 && cg_delivery_zone_is_novovoronezh($zone) && cg_novovoronezh_free_delivery_available());
        $price_text = $is_free
            ? 'бесплатно'
            : number_format_i18n((float) $zone['price'], 0) . ' ₽';

        $options[$key] = sprintf('%s — %s', $zone['label'], $price_text);
    }

    $options['other'] = 'Другой населённый пункт — стоимость уточним';

    return $options;
}

/** Build the explanatory state below the checkout selector. */
function cg_checkout_delivery_note_state($zone_key, $custom_city = '') {
    $zones = cg_get_delivery_zones();

    if (isset($zones[$zone_key])) {
        $zone = $zones[$zone_key];
        $is_free = !empty($zone['free_by_threshold']) || ((float) $zone['price'] <= 0 && cg_delivery_zone_is_novovoronezh($zone) && cg_novovoronezh_free_delivery_available());

        if ($is_free) {
            return [
                'class' => 'is-free',
                'text' => 'Бесплатная доставка по Нововоронежу доступна для этого заказа.',
            ];
        }

        return [
            'class' => 'is-priced',
            'text' => 'Стоимость доставки: ' . number_format_i18n((float) $zone['price'], 0) . ' ₽.',
        ];
    }

    if ($zone_key === 'other') {
        return [
            'class' => 'is-custom',
            'text' => $custom_city !== ''
                ? 'Стоимость доставки в «' . $custom_city . '» уточним после оформления заказа.'
                : 'Введите населённый пункт — стоимость доставки уточним после оформления заказа.',
        ];
    }

    return [
        'class' => '',
        'text' => 'Выберите населённый пункт — стоимость доставки сразу появится в заказе.',
    ];
}

/**
 * Render the settlement selector independently of WooCommerce additional fields.
 * This keeps it available when a customer opens checkout directly from mini-cart.
 */
function cg_render_checkout_delivery_selector() {
    if (!is_checkout() && !is_page_template('page-templates/premium-checkout.php')) return;
    if (!empty($GLOBALS['cg_checkout_delivery_selector_rendered'])) return;

    $GLOBALS['cg_checkout_delivery_selector_rendered'] = true;

    $zone_key = sanitize_key(cg_checkout_delivery_value('cg_delivery_zone'));
    $custom_city = cg_checkout_delivery_value('cg_delivery_custom_city');
    $note = cg_checkout_delivery_note_state($zone_key, $custom_city);
    $custom_classes = ['form-row-wide', 'cg-delivery-custom-city'];

    if ($zone_key !== 'other') {
        $custom_classes[] = 'is-hidden';
    }

    echo '<section class="cg-checkout-delivery-selector" aria-labelledby="cg-checkout-delivery-selector-title">';
    echo '<div class="cg-checkout-delivery-selector__head">';
    echo '<span class="cg-checkout-delivery-selector__eyebrow">Доставка</span>';
    echo '<h3 id="cg-checkout-delivery-selector-title">Куда доставить заказ?</h3>';
    echo '<p>Выберите населённый пункт. Цена пересчитается автоматически.</p>';
    echo '</div>';

    woocommerce_form_field('cg_delivery_zone', [
        'type' => 'select',
        'label' => 'Населённый пункт',
        'required' => true,
        'class' => ['form-row-wide', 'cg-delivery-zone-field'],
        'input_class' => ['cg-delivery-zone-select'],
        'options' => cg_checkout_delivery_options(),
    ], $zone_key);

    woocommerce_form_field('cg_delivery_custom_city', [
        'type' => 'text',
        'label' => 'Ваш населённый пункт',
        'placeholder' => 'Введите название населённого пункта',
        'required' => false,
        'class' => $custom_classes,
        'autocomplete' => 'address-level2',
    ], $custom_city);

    echo '<div class="cg-delivery-zone-note ' . esc_attr($note['class']) . '" id="cg_delivery_zone_note" aria-live="polite">';
    echo esc_html($note['text']);
    echo '</div>';
    echo '</section>';
}

/** Fallback for standard or third-party classic checkout templates. */
function cg_render_checkout_delivery_selector_fallback() {
    cg_render_checkout_delivery_selector();
}
add_action('woocommerce_checkout_before_order_review', 'cg_render_checkout_delivery_selector_fallback', 2);

/** Ensure the generated shipping rate is free only for eligible Novovoronezh orders. */
function cg_enforce_novovoronezh_free_delivery_rate($rates, $package) {
    if (!cg_novovoronezh_free_delivery_available() || !function_exists('WC') || !WC()->session) return $rates;

    $zone_key = (string) WC()->session->get('cg_delivery_zone', '');
    $zones = cg_get_delivery_zones();

    if (!isset($zones[$zone_key]) || !cg_delivery_zone_is_novovoronezh($zones[$zone_key])) return $rates;
    if (!isset($rates['cg_delivery_zone']) || !is_object($rates['cg_delivery_zone'])) return $rates;

    if (method_exists($rates['cg_delivery_zone'], 'set_cost')) {
        $rates['cg_delivery_zone']->set_cost(0);
    }
    if (method_exists($rates['cg_delivery_zone'], 'set_taxes')) {
        $rates['cg_delivery_zone']->set_taxes([]);
    }

    return $rates;
}
add_filter('woocommerce_package_rates', 'cg_enforce_novovoronezh_free_delivery_rate', 120, 2);

/** Clearly label the earned free delivery in totals. */
function cg_label_novovoronezh_free_delivery($label, $method) {
    if (!is_object($method) || $method->get_id() !== 'cg_delivery_zone') return $label;
    if (!cg_novovoronezh_free_delivery_available() || !function_exists('WC') || !WC()->session) return $label;

    $zone_key = (string) WC()->session->get('cg_delivery_zone', '');
    $zones = cg_get_delivery_zones();

    if (!isset($zones[$zone_key]) || !cg_delivery_zone_is_novovoronezh($zones[$zone_key])) return $label;

    return esc_html($zones[$zone_key]['label']) . ' — бесплатно';
}
add_filter('woocommerce_cart_shipping_method_full_label', 'cg_label_novovoronezh_free_delivery', 120, 2);
