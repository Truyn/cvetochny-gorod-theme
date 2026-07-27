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
        'handmade' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 12V6a2 2 0 0 1 4 0v5"/><path d="M12 11V5a2 2 0 0 1 4 0v7"/><path d="M16 12V8a2 2 0 0 1 4 0v6c0 5-3 8-8 8H9c-3 0-5-2-5-5v-4a2 2 0 0 1 4 0v2"/></svg>',
        'payment' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 9h18"/><path d="M7 15h4"/></svg>',
        'replace' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h11l-3-3"/><path d="m15 7-3 3"/><path d="M20 17H9l3 3"/><path d="m9 17 3-3"/></svg>',
        'support' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v12H8l-3 3z"/><path d="M8 8h8"/><path d="M8 12h5"/></svg>',
        'care' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-7-4.2-7-10a4 4 0 0 1 7-2.7A4 4 0 0 1 19 11c0 5.8-7 10-7 10Z"/></svg>',
    ];

    return isset($icons[$name]) ? $icons[$name] : '';
}

/** Add only the quickest purchase assurances next to the buy form. */
function cg_single_product_benefits() {
    $items = [
        ['fresh', 'Свежие цветы', 'Собираем непосредственно перед доставкой'],
        ['photo', 'Фото перед отправкой', 'Покажем готовый букет до передачи курьеру'],
        ['delivery', 'Удобное время', 'Согласуем доступный интервал после оформления'],
        ['card', 'Открытка бесплатно', 'Добавим ваши слова к заказу'],
    ];

    echo '<div class="cg-product-benefits" aria-label="Ключевые преимущества заказа">';
    foreach ($items as $item) {
        echo '<div class="cg-product-benefit"><span class="cg-product-benefit__icon" aria-hidden="true">'.cg_product_benefit_icon($item[0]).'</span><span><strong>'.esc_html($item[1]).'</strong><small>'.esc_html($item[2]).'</small></span></div>';
    }
    echo '</div>';
}
add_action('woocommerce_single_product_summary', 'cg_single_product_benefits', 35);

/** Explain the next steps after adding a bouquet to the cart. */
function cg_single_product_order_confidence() {
    echo '<div class="cg-order-confidence" aria-label="Как проходит оформление заказа">';
    echo '<div class="cg-order-confidence__title">Как проходит заказ</div>';
    echo '<div class="cg-order-confidence__steps">';
    echo '<div class="cg-order-confidence__step">Вы оформляете заказ</div>';
    echo '<div class="cg-order-confidence__step">Флорист собирает букет</div>';
    echo '<div class="cg-order-confidence__step">Мы отправляем фото</div>';
    echo '<div class="cg-order-confidence__step">Курьер доставляет получателю</div>';
    echo '</div></div>';
}
add_action('woocommerce_single_product_summary', 'cg_single_product_order_confidence', 36);

/** Add trust details that complement, rather than repeat, the compact benefits above. */
function cg_single_product_trust_section() {
    $items = [
        ['handmade', 'Ручная сборка', 'Каждый букет флорист собирает специально под ваш заказ, а не берёт готовую композицию с витрины.'],
        ['replace', 'Замены только с согласованием', 'Если какого-то цветка временно нет, сначала предложим равноценный вариант и дождёмся вашего решения.'],
        ['care', 'Бережная упаковка', 'Подготовим композицию к перевозке, чтобы она сохранила форму и аккуратный внешний вид.'],
        ['payment', 'Понятная оплата', 'На оформлении заказа показываются только способы оплаты, реально подключённые в магазине.'],
        ['support', 'Поддержка по заказу', 'Можно уточнить детали состава, доставки и пожеланий до передачи букета получателю.'],
        ['fresh', 'Гарантия качества', 'Если полученный букет не соответствует согласованному виду, мы оперативно разберёмся в ситуации.'],
    ];

    echo '<section class="cg-product-trust" aria-labelledby="cg-product-trust-title">';
    echo '<div class="cg-product-trust__head"><span class="cg-product-trust__eyebrow">Дополнительные гарантии</span><h2 id="cg-product-trust-title">Почему нам можно доверить букет</h2><p>Здесь только условия, которые дополняют информацию рядом с кнопкой покупки и помогают понять, как мы работаем с заказом.</p></div>';
    echo '<div class="cg-product-trust__grid">';
    foreach ($items as $item) {
        echo '<article class="cg-product-trust__item"><span class="cg-product-trust__icon" aria-hidden="true">'.cg_product_benefit_icon($item[0]).'</span><div><h3>'.esc_html($item[1]).'</h3><p>'.esc_html($item[2]).'</p></div></article>';
    }
    echo '</div>';
    echo '</section>';
}
add_action('woocommerce_after_single_product_summary', 'cg_single_product_trust_section', 8);

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

/** Load the custom, self-contained single product layout stylesheets. */
function cg_enqueue_single_product_layout() {
    if (!is_product()) return;

    $layout_path = get_template_directory() . '/assets/css/single-product-layout.css';
    $modern_path = get_template_directory() . '/assets/css/single-product-modern.css';
    $version = wp_get_theme()->get('Version');

    wp_enqueue_style(
        'cg-single-product-layout',
        get_template_directory_uri() . '/assets/css/single-product-layout.css',
        ['cg-product-hotfix'],
        file_exists($layout_path) ? filemtime($layout_path) : $version
    );

    wp_enqueue_style(
        'cg-single-product-modern',
        get_template_directory_uri() . '/assets/css/single-product-modern.css',
        ['cg-single-product-layout'],
        file_exists($modern_path) ? filemtime($modern_path) : $version
    );
}
add_action('wp_enqueue_scripts', 'cg_enqueue_single_product_layout', 30);
