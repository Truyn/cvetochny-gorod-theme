<?php
/**
 * Enhancements for the WooCommerce single product page.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

require_once get_template_directory() . '/inc/cart-checkout.php';

/** Add a compact product status row above the title. */
function cg_single_product_status() {
    global $product;
    if (!$product instanceof WC_Product) return;

    echo '<div class="cg-product-status">';
    echo $product->is_in_stock()
        ? '<span class="cg-product-status__item cg-product-status__item--stock">В наличии</span>'
        : '<span class="cg-product-status__item cg-product-status__item--out">Нет в наличии</span>';
    echo '</div>';
}
add_action('woocommerce_single_product_summary', 'cg_single_product_status', 4);

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

/** Product badges displayed over the gallery. */
function cg_single_product_gallery_badges() {
    global $product;
    if (!$product instanceof WC_Product) return;

    $badges = [];
    if ($product->is_on_sale()) $badges[] = ['sale', 'Скидка'];
    $created = $product->get_date_created();
    if ($created && (time() - $created->getTimestamp()) < DAY_IN_SECONDS * 30) $badges[] = ['new', 'Новинка'];
    if ($product->is_featured()) $badges[] = ['hit', 'Хит'];
    if (!$badges) return;

    echo '<div class="cg-product-gallery-badges" aria-label="Метки товара">';
    foreach ($badges as $badge) {
        echo '<span class="cg-product-gallery-badge cg-product-gallery-badge--'.esc_attr($badge[0]).'">'.esc_html($badge[1]).'</span>';
    }
    echo '</div>';
}
add_action('woocommerce_before_single_product_summary', 'cg_single_product_gallery_badges', 18);

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

/** Quick actions under the purchase form. */
function cg_single_product_actions() {
    global $product;
    if (!$product instanceof WC_Product) return;

    $product_id = $product->get_id();
    $share_url = get_permalink($product_id);
    $share_text = get_the_title($product_id);
    $whatsapp = 'https://wa.me/?text=' . rawurlencode($share_text . ' ' . $share_url);
    $telegram = 'https://t.me/share/url?url=' . rawurlencode($share_url) . '&text=' . rawurlencode($share_text);
    $vk = 'https://vk.com/share.php?url=' . rawurlencode($share_url) . '&title=' . rawurlencode($share_text);
    $max = 'https://max.ru/share?url=' . rawurlencode($share_url) . '&text=' . rawurlencode($share_text);

    echo '<div class="cg-product-actions" aria-label="Действия с товаром">';
    echo '<button class="cg-product-action" type="button" data-cg-favorite data-product-id="'.esc_attr($product_id).'" aria-pressed="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/></svg><span>В избранное</span></button>';
    echo '<div class="cg-share" data-cg-share>';
    echo '<button class="cg-product-action" type="button" data-cg-share-toggle aria-expanded="false"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.7 6.8-4M8.6 13.3l6.8 4"/></svg><span>Поделиться</span></button>';
    echo '<div class="cg-share-menu" data-cg-share-menu hidden>';
    echo '<a href="'.esc_url($whatsapp).'" target="_blank" rel="noopener noreferrer">WhatsApp</a>';
    echo '<a href="'.esc_url($telegram).'" target="_blank" rel="noopener noreferrer">Telegram</a>';
    echo '<a href="'.esc_url($max).'" target="_blank" rel="noopener noreferrer">MAX</a>';
    echo '<a href="'.esc_url($vk).'" target="_blank" rel="noopener noreferrer">ВКонтакте</a>';
    echo '<button type="button" data-cg-copy-link data-url="'.esc_url($share_url).'">Скопировать ссылку</button>';
    echo '</div></div></div>';
}
add_action('woocommerce_single_product_summary', 'cg_single_product_actions', 31);

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

/** Temporary plain placeholder until final delivery/payment copy is provided. */
function cg_product_delivery_tab_content() {
    echo '<div class="cg-tab-intro cg-tab-intro--plain"><span>Информация</span><h2>Доставка и оплата</h2><p>Здесь будет размещена подробная информация о доставке и способах оплаты.</p></div>';
}

/** Load the custom, self-contained single product layout stylesheets. */
function cg_enqueue_single_product_layout() {
    if (!is_product()) return;

    $layout_path = get_template_directory() . '/assets/css/single-product-layout.css';
    $modern_path = get_template_directory() . '/assets/css/single-product-modern.css';
    $tabs_path = get_template_directory() . '/assets/css/product-tabs-premium.css';
    $conversion_path = get_template_directory() . '/assets/css/product-conversion-premium.css';
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

    wp_enqueue_style(
        'cg-product-conversion-premium',
        get_template_directory_uri() . '/assets/css/product-conversion-premium.css',
        ['cg-product-tabs-premium'],
        file_exists($conversion_path) ? filemtime($conversion_path) : $version
    );
}
add_action('wp_enqueue_scripts', 'cg_enqueue_single_product_layout', 30);