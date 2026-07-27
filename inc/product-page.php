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
        'payment' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/></svg>',
        'replace' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 7h10l-2.5-2.5M17 17H7l2.5 2.5"/><path d="M17 7a6 6 0 0 1 1 8M7 17a6 6 0 0 1-1-8"/></svg>',
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
    echo '<div class="cg-order-confidence__step">Флорист связывается с вами</div>';
    echo '<div class="cg-order-confidence__step">Флорист собирает букет</div>';
    echo '<div class="cg-order-confidence__step">Мы отправляем фото</div>';
    echo '<div class="cg-order-confidence__step">Курьер доставляет получателю</div>';
    echo '</div></div>';
}
add_action('woocommerce_single_product_summary', 'cg_single_product_order_confidence', 36);

add_filter('woocommerce_product_related_products_heading', function () { return 'Похожие букеты'; });
add_filter('woocommerce_output_related_products_args', function ($args) { $args['posts_per_page']=4; $args['columns']=4; return $args; });

/** Give default product tabs a premium title and visual hooks. */
function cg_product_tabs($tabs) {
    if (isset($tabs['description'])) $tabs['description']['title'] = 'Описание';
    if (isset($tabs['additional_information'])) $tabs['additional_information']['title'] = 'Детали';
    $tabs['cg_delivery']=['title'=>'Доставка и оплата','priority'=>25,'callback'=>'cg_product_delivery_tab_content'];
    return $tabs;
}
add_filter('woocommerce_product_tabs', 'cg_product_tabs');

/** Wrap the WooCommerce description in a richer content layout. */
function cg_product_description_tab_heading($heading) {
    return 'О букете';
}
add_filter('woocommerce_product_description_heading', 'cg_product_description_tab_heading');

/** Premium delivery and payment cards. */
function cg_product_delivery_tab_content() {
    $items = [
        ['delivery', 'Доставка', 'Доставляем по Нововоронежу и ближайшим районам. Точную стоимость и время подтверждаем после оформления заказа.'],
        ['photo', 'Фото перед отправкой', 'Перед передачей курьеру пришлём фотографию готового букета, чтобы вы увидели результат.'],
        ['payment', 'Оплата', 'Доступны способы оплаты, подключённые в WooCommerce. Менеджер поможет, если потребуется уточнение.'],
        ['fresh', 'Свежесть', 'Букет собирается непосредственно перед доставкой из свежих цветов, доступных в день заказа.'],
        ['replace', 'Сезонная замена', 'Если отдельного цветка нет в наличии, предложим равноценную замену и согласуем её с вами.'],
    ];

    echo '<div class="cg-tab-intro"><span>Сервис магазина</span><h2>Доставка и оплата</h2><p>Всё важное о получении заказа — коротко и без скрытых условий.</p></div>';
    echo '<div class="cg-tab-card-grid">';
    foreach ($items as $item) {
        echo '<article class="cg-tab-card"><span class="cg-tab-card__icon" aria-hidden="true">'.cg_product_benefit_icon($item[0]).'</span><div><h3>'.esc_html($item[1]).'</h3><p>'.esc_html($item[2]).'</p></div></article>';
    }
    echo '</div>';
}

/** Load the custom, self-contained single product layout stylesheets. */
function cg_enqueue_single_product_layout() {
    if (!is_product()) return;

    $layout_path = get_template_directory() . '/assets/css/single-product-layout.css';
    $modern_path = get_template_directory() . '/assets/css/single-product-modern.css';
    $tabs_path = get_template_directory() . '/assets/css/product-tabs-premium.css';
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

    wp_enqueue_style(
        'cg-product-tabs-premium',
        get_template_directory_uri() . '/assets/css/product-tabs-premium.css',
        ['cg-single-product-modern'],
        file_exists($tabs_path) ? filemtime($tabs_path) : $version
    );
}
add_action('wp_enqueue_scripts', 'cg_enqueue_single_product_layout', 30);
