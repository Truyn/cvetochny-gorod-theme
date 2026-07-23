<?php
/**
 * Enhancements for the WooCommerce single product page.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

require_once get_template_directory() . '/inc/cart-checkout.php';

/** Add a compact product status row below the title. */
function cg_single_product_status() {
    global $product;
    if (!$product instanceof WC_Product) return;

    echo '<div class="cg-product-status">';
    echo $product->is_in_stock()
        ? '<span class="cg-product-status__item cg-product-status__item--stock">В наличии</span>'
        : '<span class="cg-product-status__item cg-product-status__item--out">Нет в наличии</span>';

    if ($product->get_average_rating() > 0) {
        echo '<a class="cg-product-status__item" href="#reviews">'.esc_html(number_format_i18n($product->get_average_rating(), 1)).' из 5 · отзывы</a>';
    }
    echo '</div>';
}
add_action('woocommerce_single_product_summary', 'cg_single_product_status', 6);

/** Inline SVG icon used by the product advantages. */
function cg_product_benefit_icon($name) {
    $icons = [
        'delivery' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h11v9H3z"/><path d="M14 9h4l3 3v3h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>',
        'photo' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h4l2-2h4l2 2h4v12H4z"/><circle cx="12" cy="13" r="4"/></svg>',
        'fresh' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21V10"/><path d="M12 14c-5 0-8-3-8-7 5 0 8 3 8 7Z"/><path d="M12 11c0-4 3-7 8-7 0 4-3 7-8 7Z"/></svg>',
        'card' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="14" rx="2"/><path d="m7 9 5 4 5-4"/></svg>',
    ];

    return isset($icons[$name]) ? $icons[$name] : '';
}

/** Add delivery and quality advantages next to the buy form. */
function cg_single_product_benefits() {
    $items = [
        ['delivery', 'Доставка сегодня', 'По Нововоронежу в удобное время'],
        ['photo', 'Фото перед отправкой', 'Покажем готовый букет до доставки'],
        ['fresh', 'Свежие цветы', 'Собираем букет непосредственно перед отправкой'],
        ['card', 'Открытка бесплатно', 'Добавим ваши пожелания к заказу'],
    ];

    echo '<div class="cg-product-benefits" aria-label="Преимущества заказа">';
    foreach ($items as $item) {
        echo '<div class="cg-product-benefit"><span class="cg-product-benefit__icon" aria-hidden="true">'.cg_product_benefit_icon($item[0]).'</span><span><strong>'.esc_html($item[1]).'</strong><small>'.esc_html($item[2]).'</small></span></div>';
    }
    echo '</div>';
}
add_action('woocommerce_single_product_summary', 'cg_single_product_benefits', 35);

/** Add a reassurance panel after the summary. */
function cg_single_product_guarantee() {
    echo '<section class="cg-product-guarantee">';
    echo '<div><span class="cg-product-guarantee__mark">✓</span><strong>Гарантия качества</strong><p>Если букет при получении не соответствует согласованному виду, мы оперативно решим вопрос.</p></div>';
    echo '<div><span class="cg-product-guarantee__mark">⌚</span><strong>Уточним время доставки</strong><p>После оформления заказа свяжемся с вами для подтверждения состава, адреса и времени.</p></div>';
    echo '</section>';
}
add_action('woocommerce_after_single_product_summary', 'cg_single_product_guarantee', 8);

add_filter('woocommerce_product_related_products_heading', function () { return 'Похожие букеты'; });
add_filter('woocommerce_output_related_products_args', function ($args) { $args['posts_per_page']=4; $args['columns']=4; return $args; });

function cg_product_delivery_tab($tabs) {
    $tabs['cg_delivery']=['title'=>'Доставка и оплата','priority'=>25,'callback'=>'cg_product_delivery_tab_content'];
    return $tabs;
}
add_filter('woocommerce_product_tabs', 'cg_product_delivery_tab');
function cg_product_delivery_tab_content() {
    echo '<h2>Доставка и оплата</h2><p>Доставляем букеты по Нововоронежу и ближайшим районам. Точную стоимость и доступное время подтвердит менеджер после оформления заказа.</p><ul><li>Можно выбрать удобный интервал доставки.</li><li>Перед отправкой пришлём фотографию готового букета.</li><li>Оплата доступна способами, настроенными в WooCommerce.</li></ul>';
}

/** Runtime alignment for desktop WooCommerce markup on the live site. */
function cg_enqueue_single_product_alignment() {
    if (!is_product()) return;

    $path = get_template_directory() . '/assets/js/single-product-align.js';
    $version = file_exists($path) ? filemtime($path) : wp_get_theme()->get('Version');
    wp_enqueue_script(
        'cg-single-product-align',
        get_template_directory_uri() . '/assets/js/single-product-align.js',
        ['jquery'],
        $version,
        true
    );
}
add_action('wp_enqueue_scripts', 'cg_enqueue_single_product_alignment', 30);
