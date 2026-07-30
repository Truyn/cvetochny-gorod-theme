<?php
/**
 * AJAX mini-cart drawer for WooCommerce.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/** Render drawer markup. */
function cg_render_mini_cart_drawer() {
    if (!class_exists('WooCommerce')) return;
    ?>
    <div class="cg-mini-cart-overlay" data-cg-mini-cart-close hidden></div>
    <aside class="cg-mini-cart" id="cg-mini-cart" aria-hidden="true" aria-labelledby="cg-mini-cart-title">
        <div class="cg-mini-cart__header">
            <div>
                <span class="cg-mini-cart__eyebrow">Ваш заказ</span>
                <h2 id="cg-mini-cart-title">Корзина</h2>
            </div>
            <button class="cg-mini-cart__close" type="button" data-cg-mini-cart-close aria-label="Закрыть корзину"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
        </div>
        <div class="cg-mini-cart__body" data-cg-mini-cart-content>
            <?php cg_render_mini_cart_content(); ?>
        </div>
    </aside>
    <?php
}
add_action('wp_footer', 'cg_render_mini_cart_drawer', 20);

/** Render only the dynamic drawer content. */
function cg_render_mini_cart_content() {
    if (!WC()->cart || WC()->cart->is_empty()) {
        echo '<div class="cg-mini-cart__empty">';
        echo '<div class="cg-mini-cart__empty-mark" aria-hidden="true">✿</div>';
        echo '<h3>Корзина пока пуста</h3>';
        echo '<p>Добавьте букет, и он появится здесь.</p>';
        echo '<a class="button" href="' . esc_url(cg_catalog_url()) . '">Перейти в каталог</a>';
        echo '</div>';
        return;
    }

    echo '<div class="cg-mini-cart__items">';
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        if (!$product || !$product->exists() || $cart_item['quantity'] <= 0) continue;

        $product_url = $product->is_visible() ? $product->get_permalink($cart_item) : '';
        $thumbnail = $product->get_image('woocommerce_thumbnail');
        $price = WC()->cart->get_product_price($product);
        $name = $product->get_name();
        $quantity = (int) $cart_item['quantity'];

        echo '<article class="cg-mini-cart__item" data-cart-item-key="' . esc_attr($cart_item_key) . '">';
        echo $product_url ? '<a class="cg-mini-cart__thumb" href="' . esc_url($product_url) . '">' . wp_kses_post($thumbnail) . '</a>' : '<span class="cg-mini-cart__thumb">' . wp_kses_post($thumbnail) . '</span>';
        echo '<div class="cg-mini-cart__item-main">';
        echo $product_url ? '<a class="cg-mini-cart__name" href="' . esc_url($product_url) . '">' . esc_html($name) . '</a>' : '<span class="cg-mini-cart__name">' . esc_html($name) . '</span>';
        echo '<div class="cg-mini-cart__price">' . wp_kses_post($price) . '</div>';
        echo '<div class="cg-mini-cart__controls">';
        echo '<button type="button" data-cg-cart-decrease aria-label="Уменьшить количество">−</button>';
        echo '<input type="number" min="0" step="1" value="' . esc_attr($quantity) . '" inputmode="numeric" data-cg-cart-quantity aria-label="Количество">';
        echo '<button type="button" data-cg-cart-increase aria-label="Увеличить количество">+</button>';
        echo '</div>';
        echo '</div>';
        echo '<button class="cg-mini-cart__remove" type="button" data-cg-cart-remove aria-label="Удалить товар"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>';
        echo '</article>';
    }
    echo '</div>';

    $threshold = function_exists('cg_get_novovoronezh_free_delivery_threshold')
        ? (float) cg_get_novovoronezh_free_delivery_threshold()
        : 10000;
    $subtotal = (float) WC()->cart->get_subtotal();

    if ($threshold > 0) {
        $remaining = max(0, $threshold - $subtotal);
        $progress = min(100, max(0, ($subtotal / $threshold) * 100));
        $progress_rounded = (int) round($progress);
        $delivery_class = $remaining <= 0 ? ' is-complete' : '';

        echo '<div class="cg-mini-cart__delivery' . esc_attr($delivery_class) . '">';
        echo '<div class="cg-mini-cart__delivery-head">';
        echo '<span>Бесплатная доставка по Нововоронежу</span>';
        echo '<strong>' . esc_html($progress_rounded) . '%</strong>';
        echo '</div>';
        echo '<div class="cg-mini-cart__delivery-text">';
        echo $remaining > 0
            ? 'До бесплатной доставки осталось <strong>' . wp_kses_post(wc_price($remaining)) . '</strong>'
            : '<strong>Бесплатная доставка по Нововоронежу доступна</strong>';
        echo '</div>';
        echo '<div class="cg-mini-cart__progress" role="progressbar" aria-label="Прогресс до бесплатной доставки по Нововоронежу" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . esc_attr($progress_rounded) . '"><span style="width:' . esc_attr($progress) . '%"></span></div>';
        echo '<small class="cg-mini-cart__delivery-note">Для других населённых пунктов доставка оплачивается независимо от суммы заказа.</small>';
        echo '</div>';
    }

    echo '<div class="cg-mini-cart__summary">';
    echo '<span>Итого</span><strong>' . wp_kses_post(WC()->cart->get_cart_subtotal()) . '</strong>';
    echo '</div>';
    echo '<div class="cg-mini-cart__actions">';
    echo '<a class="button cg-mini-cart__checkout" href="' . esc_url(wc_get_checkout_url()) . '">Оформить заказ</a>';
    echo '<a class="cg-mini-cart__cart-link" href="' . esc_url(wc_get_cart_url()) . '">Перейти в корзину</a>';
    echo '</div>';
}

/** Add drawer content to WooCommerce fragments. */
function cg_mini_cart_fragments($fragments) {
    ob_start();
    echo '<div class="cg-mini-cart__body" data-cg-mini-cart-content>';
    cg_render_mini_cart_content();
    echo '</div>';
    $fragments['div[data-cg-mini-cart-content]'] = ob_get_clean();
    return $fragments;
}
add_filter('woocommerce_add_to_cart_fragments', 'cg_mini_cart_fragments');

/** Shared AJAX response. */
function cg_mini_cart_ajax_response() {
    WC()->cart->calculate_totals();
    wp_send_json_success([
        'count' => WC()->cart->get_cart_contents_count(),
        'fragments' => apply_filters('woocommerce_add_to_cart_fragments', []),
    ]);
}

/** Update item quantity through AJAX. Quantity zero removes the item. */
function cg_ajax_update_cart_item() {
    check_ajax_referer('cg_mini_cart', 'nonce');
    $key = isset($_POST['cart_item_key']) ? wc_clean(wp_unslash($_POST['cart_item_key'])) : '';
    $quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : 1;

    if (!$key || !WC()->cart->get_cart_item($key)) {
        wp_send_json_error(['message' => 'Товар не найден в корзине.'], 404);
    }

    if ($quantity < 1) {
        if (!WC()->cart->remove_cart_item($key)) {
            wp_send_json_error(['message' => 'Не удалось удалить товар.'], 400);
        }

        cg_mini_cart_ajax_response();
    }

    WC()->cart->set_quantity($key, $quantity, true);
    cg_mini_cart_ajax_response();
}
add_action('wp_ajax_cg_update_cart_item', 'cg_ajax_update_cart_item');
add_action('wp_ajax_nopriv_cg_update_cart_item', 'cg_ajax_update_cart_item');

/** Remove item through AJAX. */
function cg_ajax_remove_cart_item() {
    check_ajax_referer('cg_mini_cart', 'nonce');
    $key = isset($_POST['cart_item_key']) ? wc_clean(wp_unslash($_POST['cart_item_key'])) : '';

    if (!$key || !WC()->cart->remove_cart_item($key)) {
        wp_send_json_error(['message' => 'Не удалось удалить товар.'], 400);
    }

    cg_mini_cart_ajax_response();
}
add_action('wp_ajax_cg_remove_cart_item', 'cg_ajax_remove_cart_item');
add_action('wp_ajax_nopriv_cg_remove_cart_item', 'cg_ajax_remove_cart_item');
