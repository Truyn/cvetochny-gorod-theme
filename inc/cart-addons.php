<?php
/**
 * Additional gift products offered from the WooCommerce cart.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/** Add a global cart-addon switch to the product editor. */
function cg_cart_addon_product_option() {
    if (!function_exists('woocommerce_wp_checkbox')) return;

    woocommerce_wp_checkbox([
        'id' => '_cg_cart_addon',
        'label' => 'Дополнение к букету',
        'description' => 'Показывать товар в блоке дополнительных покупок в корзине.',
        'desc_tip' => true,
    ]);
}
add_action('woocommerce_product_options_related', 'cg_cart_addon_product_option');

/** Save the product-level cart-addon switch. */
function cg_save_cart_addon_product_option($product) {
    if (!$product instanceof WC_Product) return;

    $product->update_meta_data(
        '_cg_cart_addon',
        isset($_POST['_cg_cart_addon']) ? 'yes' : 'no'
    );
}
add_action('woocommerce_admin_process_product_object', 'cg_save_cart_addon_product_option');

/** Product and parent IDs already represented in the cart. */
function cg_cart_addon_existing_product_ids() {
    $ids = [];

    if (!function_exists('WC') || !WC()->cart) return $ids;

    foreach (WC()->cart->get_cart() as $cart_item) {
        $product_id = isset($cart_item['product_id']) ? absint($cart_item['product_id']) : 0;
        $variation_id = isset($cart_item['variation_id']) ? absint($cart_item['variation_id']) : 0;

        if ($product_id) $ids[] = $product_id;
        if ($variation_id) $ids[] = $variation_id;
    }

    return array_values(array_unique($ids));
}

/** Cross-sells configured for bouquets currently in the cart. */
function cg_cart_addon_cross_sell_ids() {
    $ids = [];

    if (!function_exists('WC') || !WC()->cart) return $ids;

    foreach (WC()->cart->get_cart() as $cart_item) {
        $product = isset($cart_item['data']) ? $cart_item['data'] : false;
        if (!$product instanceof WC_Product) continue;

        if ($product->is_type('variation') && $product->get_parent_id()) {
            $parent = wc_get_product($product->get_parent_id());
            if ($parent instanceof WC_Product) $product = $parent;
        }

        $ids = array_merge($ids, array_map('absint', $product->get_cross_sell_ids()));
    }

    return array_values(array_unique(array_filter($ids)));
}

/** Products globally marked as cart additions in the product editor. */
function cg_cart_addon_global_ids() {
    $ids = get_posts([
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => 24,
        'fields' => 'ids',
        'orderby' => 'menu_order date',
        'order' => 'ASC',
        'meta_query' => [[
            'key' => '_cg_cart_addon',
            'value' => 'yes',
        ]],
        'no_found_rows' => true,
        'suppress_filters' => false,
    ]);

    return array_map('absint', $ids);
}

/**
 * Return simple, purchasable additions, prioritising bouquet cross-sells.
 */
function cg_get_cart_addon_products($limit = 6) {
    $limit = max(1, min(12, absint($limit)));
    $existing_ids = cg_cart_addon_existing_product_ids();
    $candidate_ids = array_values(array_unique(array_merge(
        cg_cart_addon_cross_sell_ids(),
        cg_cart_addon_global_ids()
    )));
    $products = [];

    foreach ($candidate_ids as $product_id) {
        if (in_array($product_id, $existing_ids, true)) continue;

        $product = wc_get_product($product_id);
        if (!$product instanceof WC_Product) continue;
        if ($product->get_status() !== 'publish' || !$product->is_visible()) continue;
        if (!$product->is_type('simple') || !$product->is_purchasable() || !$product->is_in_stock()) continue;

        $products[] = $product;
        if (count($products) >= $limit) break;
    }

    return $products;
}

/** Assets for the cart addition cards and one-click adding. */
function cg_cart_addons_assets() {
    if (!is_cart() || !class_exists('WooCommerce')) return;

    $theme_version = wp_get_theme()->get('Version');
    $style_path = get_template_directory() . '/assets/css/cart-addons.css';
    $script_path = get_template_directory() . '/assets/js/cart-addons.js';

    wp_enqueue_style(
        'cg-cart-addons',
        get_template_directory_uri() . '/assets/css/cart-addons.css',
        ['cg-cart-premium'],
        file_exists($style_path) ? filemtime($style_path) : $theme_version
    );

    wp_enqueue_script(
        'cg-cart-addons',
        get_template_directory_uri() . '/assets/js/cart-addons.js',
        ['jquery', 'wc-cart-fragments'],
        file_exists($script_path) ? filemtime($script_path) : $theme_version,
        true
    );

    wp_localize_script('cg-cart-addons', 'cgCartAddons', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('cg_cart_addons'),
        'strings' => [
            'adding' => 'Добавляем…',
            'added' => 'Добавлено',
            'error' => 'Не удалось добавить товар. Попробуйте ещё раз.',
        ],
    ]);
}
add_action('wp_enqueue_scripts', 'cg_cart_addons_assets', 35);

/** Small category label used on an addition card. */
function cg_cart_addon_category_label($product_id) {
    $terms = get_the_terms($product_id, 'product_cat');
    if (is_wp_error($terms) || empty($terms)) return 'К подарку';

    return (string) reset($terms)->name;
}

/** Render the additional-products block before the cart totals. */
function cg_render_cart_addons() {
    $products = cg_get_cart_addon_products(6);

    if (!$products) {
        if (current_user_can('manage_woocommerce')) {
            echo '<section class="cg-cart-addons cg-cart-addons--setup" aria-label="Настройка дополнительных товаров">';
            echo '<strong>Дополнительные товары пока не настроены</strong>';
            echo '<span>В карточке товара откройте «Сопутствующие» и включите «Дополнение к букету» либо назначьте товар как сопутствующий для букета.</span>';
            echo '</section>';
        }
        return;
    }

    echo '<section class="cg-cart-addons" id="cg-cart-addons" aria-labelledby="cg-cart-addons-title">';
    echo '<div class="cg-cart-addons__heading">';
    echo '<div><span class="cg-cart-addons__eyebrow">Приятные дополнения</span>';
    echo '<h2 id="cg-cart-addons-title">Дополните подарок</h2>';
    echo '<p>Открытка уже бесплатна — добавьте сладости, игрушку, вазу или другой небольшой подарок.</p></div>';
    echo '<span class="cg-cart-addons__hint">Добавление в один клик</span>';
    echo '</div>';
    echo '<div class="cg-cart-addons__grid">';

    foreach ($products as $product) {
        $product_id = $product->get_id();
        echo '<article class="cg-cart-addon" data-cg-cart-addon-card data-product-id="' . esc_attr($product_id) . '">';
        echo '<a class="cg-cart-addon__image" href="' . esc_url(get_permalink($product_id)) . '">' . wp_kses_post($product->get_image('woocommerce_thumbnail')) . '</a>';
        echo '<div class="cg-cart-addon__body">';
        echo '<span class="cg-cart-addon__category">' . esc_html(cg_cart_addon_category_label($product_id)) . '</span>';
        echo '<a class="cg-cart-addon__name" href="' . esc_url(get_permalink($product_id)) . '">' . esc_html($product->get_name()) . '</a>';
        echo '<div class="cg-cart-addon__footer">';
        echo '<span class="cg-cart-addon__price">' . wp_kses_post($product->get_price_html()) . '</span>';
        echo '<a class="cg-cart-addon__button" href="' . esc_url($product->add_to_cart_url()) . '" data-cg-cart-addon data-product-id="' . esc_attr($product_id) . '" aria-label="Добавить ' . esc_attr($product->get_name()) . ' в корзину">';
        echo '<span data-cg-cart-addon-label>Добавить</span><b aria-hidden="true">+</b>';
        echo '</a>';
        echo '</div></div></article>';
    }

    echo '</div>';
    echo '<div class="cg-cart-addons__status" data-cg-cart-addons-status aria-live="polite"></div>';
    echo '</section>';
}
add_action('woocommerce_before_cart_collaterals', 'cg_render_cart_addons', 8);

/** Add one selected addition and return refreshed header/mini-cart fragments. */
function cg_ajax_add_cart_addon() {
    check_ajax_referer('cg_cart_addons', 'nonce');

    if (!function_exists('WC') || !WC()->cart) {
        wp_send_json_error(['message' => 'Корзина недоступна. Обновите страницу.'], 400);
    }

    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
    $product = $product_id ? wc_get_product($product_id) : false;

    if (!$product instanceof WC_Product || $product->get_status() !== 'publish') {
        wp_send_json_error(['message' => 'Товар не найден.'], 404);
    }

    if (!$product->is_type('simple') || !$product->is_purchasable() || !$product->is_in_stock()) {
        wp_send_json_error(['message' => 'Этот товар сейчас нельзя добавить в корзину.'], 400);
    }

    $cart_item_key = WC()->cart->add_to_cart($product_id, 1);
    if (!$cart_item_key) {
        wp_send_json_error(['message' => 'Не удалось добавить товар в корзину.'], 400);
    }

    WC()->cart->calculate_totals();

    wp_send_json_success([
        'count' => WC()->cart->get_cart_contents_count(),
        'fragments' => apply_filters('woocommerce_add_to_cart_fragments', []),
    ]);
}
add_action('wp_ajax_cg_add_cart_addon', 'cg_ajax_add_cart_addon');
add_action('wp_ajax_nopriv_cg_add_cart_addon', 'cg_ajax_add_cart_addon');
