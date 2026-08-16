<?php
/**
 * Enhancements for the WooCommerce single product page.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

require_once get_template_directory() . '/inc/cart-checkout.php';
require_once get_template_directory() . '/inc/favorites.php';

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

/** Compact delivery/payment summary close to the purchase button. */
function cg_single_product_delivery_snapshot() {
    $threshold = function_exists('cg_get_novovoronezh_free_delivery_threshold')
        ? (float) cg_get_novovoronezh_free_delivery_threshold()
        : 10000;
    $delivery_url = (string) get_theme_mod('cg_delivery_url', home_url('/delivery/'));

    echo '<div class="cg-product-delivery-snapshot" aria-label="Доставка и оплата">';
    echo '<div class="cg-product-delivery-snapshot__item"><span class="cg-product-delivery-snapshot__icon" aria-hidden="true">'.cg_product_benefit_icon('delivery').'</span><span><strong>Доставка</strong><small>По Нововоронежу бесплатно от '.wp_kses_post(wc_price($threshold, ['decimals' => 0])).'</small></span></div>';
    echo '<div class="cg-product-delivery-snapshot__item"><span class="cg-product-delivery-snapshot__icon" aria-hidden="true">'.cg_product_benefit_icon('payment').'</span><span><strong>Оплата</strong><small>Выберите удобный способ при оформлении заказа</small></span></div>';
    echo '<a class="cg-product-delivery-snapshot__link" href="'.esc_url($delivery_url).'">Все условия доставки и оплаты →</a>';
    echo '</div>';
}
add_action('woocommerce_single_product_summary', 'cg_single_product_delivery_snapshot', 33);

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

/** Real delivery/payment information instead of the old placeholder. */
function cg_product_delivery_tab_content() {
    $threshold = function_exists('cg_get_novovoronezh_free_delivery_threshold')
        ? (float) cg_get_novovoronezh_free_delivery_threshold()
        : 10000;
    $delivery_url = (string) get_theme_mod('cg_delivery_url', home_url('/delivery/'));
    $payment_titles = [];

    if (function_exists('WC') && WC()->payment_gateways()) {
        foreach (WC()->payment_gateways()->payment_gateways() as $gateway) {
            if (!is_object($gateway) || !isset($gateway->enabled) || $gateway->enabled !== 'yes') continue;
            $title = isset($gateway->title) ? trim(wp_strip_all_tags((string) $gateway->title)) : '';
            if ($title !== '') $payment_titles[] = $title;
        }
    }

    echo '<div class="cg-tab-intro"><span>Перед оформлением</span><h2>Доставка и оплата</h2><p>Точная стоимость доставки зависит от населённого пункта и сразу показывается при оформлении заказа.</p></div>';
    echo '<div class="cg-product-delivery-tab-grid">';
    echo '<section><h3>Доставка</h3><ul><li>По Нововоронежу — бесплатно при сумме заказа от '.wp_kses_post(wc_price($threshold, ['decimals' => 0])).'.</li><li>Для других населённых пунктов стоимость рассчитывается по выбранной зоне.</li><li>Доступный интервал доставки согласуем после оформления заказа.</li></ul></section>';
    echo '<section><h3>Оплата</h3>';
    if ($payment_titles) {
        echo '<p>Сейчас в магазине доступны:</p><ul>';
        foreach (array_unique($payment_titles) as $title) echo '<li>'.esc_html($title).'</li>';
        echo '</ul>';
    } else {
        echo '<p>Доступный способ оплаты будет показан на странице оформления заказа.</p>';
    }
    echo '</section></div>';
    echo '<p class="cg-product-delivery-tab-more"><a class="button button--ghost" href="'.esc_url($delivery_url).'">Подробнее о доставке и оплате</a></p>';
}

/** Load the custom, self-contained single product layout stylesheets. */
function cg_enqueue_single_product_layout() {
    if (!is_product()) return;

    $layout_path = get_template_directory() . '/assets/css/single-product-layout.css';
    $modern_path = get_template_directory() . '/assets/css/single-product-modern.css';
    $tabs_path = get_template_directory() . '/assets/css/product-tabs-premium.css';
    $conversion_path = get_template_directory() . '/assets/css/product-conversion-premium.css';
    $growth_path = get_template_directory() . '/assets/css/product-growth.css';
    $growth_script_path = get_template_directory() . '/assets/js/product-mobile-buy.js';
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

    wp_enqueue_style(
        'cg-product-growth',
        get_template_directory_uri() . '/assets/css/product-growth.css',
        ['cg-product-conversion-premium'],
        file_exists($growth_path) ? filemtime($growth_path) : $version
    );

    wp_enqueue_script(
        'cg-product-mobile-buy',
        get_template_directory_uri() . '/assets/js/product-mobile-buy.js',
        [],
        file_exists($growth_script_path) ? filemtime($growth_script_path) : $version,
        true
    );
}
add_action('wp_enqueue_scripts', 'cg_enqueue_single_product_layout', 30);

/** Mobile purchase bar: direct add for simple products, scroll to options otherwise. */
function cg_single_product_mobile_buy_bar() {
    if (!is_product()) return;
    global $product;
    if (!$product instanceof WC_Product || !$product->is_purchasable() || !$product->is_in_stock()) return;

    $simple = $product->is_type('simple');
    echo '<div class="cg-mobile-buybar" data-cg-mobile-buybar data-simple="'.($simple ? '1' : '0').'" aria-hidden="true">';
    echo '<div class="cg-mobile-buybar__price"><small>Цена</small><strong>'.wp_kses_post($product->get_price_html()).'</strong></div>';
    echo '<button type="button" class="cg-mobile-buybar__button" data-cg-mobile-buy-button>'.($simple ? 'В корзину' : 'Выбрать').'</button>';
    echo '</div>';
}
add_action('wp_footer', 'cg_single_product_mobile_buy_bar', 30);

/** Collect simple, owner-friendly catalog quality information. */
function cg_catalog_quality_report() {
    if (!class_exists('WooCommerce')) return ['products' => [], 'score' => 0, 'heavy' => 0, 'occasion' => 0, 'holiday' => 0];

    $products = wc_get_products([
        'status' => 'publish',
        'limit' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);
    $rows = [];
    $checks_total = 0;
    $checks_ok = 0;
    $heavy_count = 0;
    $occasion_count = 0;
    $holiday_count = 0;
    $default_category = (int) get_option('default_product_cat', 0);

    foreach ($products as $product) {
        if (!$product instanceof WC_Product) continue;
        $product_id = $product->get_id();
        $image_id = (int) $product->get_image_id();
        $alt = $image_id ? trim((string) get_post_meta($image_id, '_wp_attachment_image_alt', true)) : '';
        $categories = wp_get_object_terms($product_id, 'product_cat', ['fields' => 'ids']);
        $categories = is_wp_error($categories) ? [] : array_map('intval', $categories);
        $real_categories = array_values(array_diff($categories, [$default_category]));

        $checks = [
            'Главное фото' => $image_id > 0,
            'Подпись к главному фото' => $alt !== '',
            'Цена' => $product->get_price() !== '',
            'Короткое описание' => trim(wp_strip_all_tags($product->get_short_description())) !== '',
            'Полное описание' => mb_strlen(trim(wp_strip_all_tags($product->get_description()))) >= 80,
            'Категория' => !empty($real_categories),
        ];
        $checks_total += count($checks);
        $checks_ok += count(array_filter($checks));
        $issues = [];
        foreach ($checks as $label => $ok) if (!$ok) $issues[] = $label;

        $image_note = '';
        if ($image_id) {
            $image_size = 0;
            $file = get_attached_file($image_id);
            if ($file && is_file($file)) $image_size = (int) @filesize($file);
            $meta = wp_get_attachment_metadata($image_id);
            $width = is_array($meta) && !empty($meta['width']) ? (int) $meta['width'] : 0;
            $height = is_array($meta) && !empty($meta['height']) ? (int) $meta['height'] : 0;
            if ($image_size > (int) (1.5 * MB_IN_BYTES)) {
                $image_note = 'Файл '.size_format($image_size, 1).' — желательно уменьшить';
                $heavy_count++;
            } elseif ($width > 3200 || $height > 3200) {
                $image_note = 'Очень большое разрешение: '.$width.'×'.$height.' px';
                $heavy_count++;
            }
        }

        if (taxonomy_exists('pa_povod')) {
            $terms = wp_get_object_terms($product_id, 'pa_povod', ['fields' => 'ids']);
            if (!is_wp_error($terms) && $terms) $occasion_count++;
        }
        if (taxonomy_exists('pa_prazdniki')) {
            $terms = wp_get_object_terms($product_id, 'pa_prazdniki', ['fields' => 'ids']);
            if (!is_wp_error($terms) && $terms) $holiday_count++;
        }

        $rows[] = [
            'id' => $product_id,
            'name' => $product->get_name(),
            'score' => (int) round((count($checks) - count($issues)) / count($checks) * 100),
            'issues' => $issues,
            'image_note' => $image_note,
        ];
    }

    usort($rows, static function ($a, $b) {
        if ($a['score'] === $b['score']) return strcasecmp($a['name'], $b['name']);
        return $a['score'] <=> $b['score'];
    });

    return [
        'products' => $rows,
        'score' => $checks_total ? (int) round($checks_ok / $checks_total * 100) : 100,
        'heavy' => $heavy_count,
        'occasion' => $occasion_count,
        'holiday' => $holiday_count,
    ];
}

function cg_catalog_quality_admin_menu() {
    add_submenu_page(
        'woocommerce',
        'Качество каталога',
        'Качество каталога',
        'manage_woocommerce',
        'cg-catalog-quality',
        'cg_catalog_quality_admin_page'
    );
}
add_action('admin_menu', 'cg_catalog_quality_admin_menu', 82);

function cg_catalog_quality_admin_assets($hook) {
    if ($hook !== 'woocommerce_page_cg-catalog-quality') return;
    $path = get_template_directory() . '/assets/css/product-growth.css';
    wp_enqueue_style(
        'cg-catalog-quality-admin',
        get_template_directory_uri() . '/assets/css/product-growth.css',
        [],
        file_exists($path) ? filemtime($path) : wp_get_theme()->get('Version')
    );
}
add_action('admin_enqueue_scripts', 'cg_catalog_quality_admin_assets');

function cg_catalog_quality_admin_page() {
    if (!current_user_can('manage_woocommerce')) return;
    $report = cg_catalog_quality_report();
    $rows = $report['products'];
    $total = count($rows);
    $problematic = count(array_filter($rows, static function ($row) { return $row['score'] < 100; }));
    ?>
    <div class="wrap cg-catalog-quality-admin">
        <h1>Качество каталога</h1>
        <p class="cg-catalog-quality-admin__lead">Здесь нет сложного SEO. Страница просто показывает, какие опубликованные товары стоит дополнить, чтобы каталог выглядел аккуратно для покупателей и поисковых систем.</p>

        <div class="cg-quality-cards">
            <div class="cg-quality-card cg-quality-card--score"><span>Готовность каталога</span><strong><?php echo esc_html($report['score']); ?>%</strong><small>по 6 простым пунктам</small></div>
            <div class="cg-quality-card"><span>Опубликовано</span><strong><?php echo esc_html($total); ?></strong><small>товаров</small></div>
            <div class="cg-quality-card"><span>Нужно дополнить</span><strong><?php echo esc_html($problematic); ?></strong><small>товаров</small></div>
            <div class="cg-quality-card"><span>Тяжёлые фото</span><strong><?php echo esc_html($report['heavy']); ?></strong><small>проверить по возможности</small></div>
        </div>

        <div class="cg-quality-progress" aria-label="Готовность каталога <?php echo esc_attr($report['score']); ?> процентов"><span style="width:<?php echo esc_attr($report['score']); ?>%"></span></div>

        <div class="cg-quality-help">
            <h2>Что считается готовым товаром</h2>
            <div class="cg-quality-help__grid">
                <span>✓ Главное фото</span><span>✓ Понятная подпись к фото</span><span>✓ Цена</span><span>✓ Короткое описание</span><span>✓ Нормальное полное описание</span><span>✓ Категория</span>
            </div>
            <p><strong>«Повод» и «Праздники» не обязательны.</strong> Сейчас «Повод» назначен у <?php echo esc_html($report['occasion']); ?> из <?php echo esc_html($total); ?> товаров, «Праздники» — у <?php echo esc_html($report['holiday']); ?>.</p>
        </div>

        <?php if (!$rows) : ?>
            <div class="notice notice-info inline"><p>Опубликованных товаров пока нет.</p></div>
        <?php else : ?>
            <h2>Что стоит исправить</h2>
            <table class="widefat striped cg-quality-table">
                <thead><tr><th>Товар</th><th>Готовность</th><th>Что добавить</th><th>Фото / скорость</th></tr></thead>
                <tbody>
                <?php foreach (array_slice($rows, 0, 100) as $row) : ?>
                    <tr>
                        <td><a href="<?php echo esc_url(get_edit_post_link($row['id'])); ?>"><strong><?php echo esc_html($row['name']); ?></strong></a></td>
                        <td><span class="cg-quality-score <?php echo $row['score'] === 100 ? 'is-ready' : ''; ?>"><?php echo esc_html($row['score']); ?>%</span></td>
                        <td><?php echo $row['issues'] ? esc_html(implode(' · ', $row['issues'])) : '<span class="cg-quality-ready">Готово ✓</span>'; ?></td>
                        <td><?php echo $row['image_note'] ? esc_html($row['image_note']) : 'Нормально'; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($total > 100) : ?><p>Показаны первые 100 товаров с наименьшей готовностью.</p><?php endif; ?>
        <?php endif; ?>

        <div class="cg-quality-speed-note">
            <h2>Про скорость сайта</h2>
            <p>Тема ничего автоматически не пережимает и не отключает. Если здесь отмечена тяжёлая фотография, её можно позже заменить более лёгкой версией. Это безопаснее, чем агрессивно отключать WooCommerce-скрипты.</p>
        </div>
    </div>
    <?php
}
