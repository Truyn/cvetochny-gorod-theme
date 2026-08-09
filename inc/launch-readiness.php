<?php
/**
 * Launch-readiness helpers: conservative SEO defaults, private-page indexing
 * guards and an admin checklist for the final pre-launch pass.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/** Detect common SEO plugins so the theme does not duplicate their metadata. */
function cg_launch_has_seo_plugin() {
    return defined('WPSEO_VERSION')
        || defined('RANK_MATH_VERSION')
        || defined('AIOSEO_VERSION')
        || defined('SEOPRESS_VERSION')
        || class_exists('The_SEO_Framework\Load');
}

/** Private, transactional and low-value utility screens must stay out of search. */
function cg_launch_is_noindex_screen() {
    if (is_admin()) return false;

    if (is_search() || is_404()) return true;

    if (class_exists('WooCommerce')) {
        if (function_exists('is_cart') && is_cart()) return true;
        if (function_exists('is_checkout') && is_checkout()) return true;
        if (function_exists('is_account_page') && is_account_page()) return true;
    }

    if (isset($_GET['cg_favorites_page'])) return true;
    if ((string) get_query_var('cg_favorites_page') !== '') return true;

    return false;
}

/** Add explicit noindex robots directives without clobbering other WP directives. */
function cg_launch_robots($robots) {
    if (!cg_launch_is_noindex_screen()) return $robots;

    unset($robots['index']);
    $robots['noindex'] = true;
    $robots['follow'] = true;

    return $robots;
}
add_filter('wp_robots', 'cg_launch_robots', 20);

/** Do not advertise WooCommerce utility pages through the core XML sitemap. */
function cg_launch_exclude_utility_pages_from_sitemap($args, $post_type) {
    if ($post_type !== 'page' || !class_exists('WooCommerce')) return $args;

    $excluded = [];
    foreach (['cart', 'checkout', 'myaccount'] as $page_name) {
        $page_id = function_exists('wc_get_page_id') ? (int) wc_get_page_id($page_name) : 0;
        if ($page_id > 0) $excluded[] = $page_id;
    }

    if (!$excluded) return $args;

    $existing = !empty($args['post__not_in']) && is_array($args['post__not_in'])
        ? array_map('absint', $args['post__not_in'])
        : [];

    $args['post__not_in'] = array_values(array_unique(array_merge($existing, $excluded)));
    return $args;
}
add_filter('wp_sitemaps_posts_query_args', 'cg_launch_exclude_utility_pages_from_sitemap', 20, 2);

/** Keep a little unnecessary WordPress fingerprinting and legacy head output out. */
function cg_launch_clean_head() {
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wp_shortlink_wp_head', 10);
}
add_action('init', 'cg_launch_clean_head');
add_filter('the_generator', '__return_empty_string');

/** Disable the legacy emoji payload on the public site. Modern browsers render emoji natively. */
function cg_launch_disable_frontend_emoji() {
    if (is_admin()) return;

    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('wp_enqueue_scripts', 'wp_enqueue_emoji_styles');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
}
add_action('init', 'cg_launch_disable_frontend_emoji', 20);

/** Build a concise page description for fallback social/search metadata. */
function cg_launch_meta_description() {
    $description = '';

    if (is_front_page()) {
        $description = (string) get_theme_mod('cg_hero_text', '');
        if ($description === '') $description = (string) get_bloginfo('description');
    } elseif (function_exists('is_product') && is_product()) {
        $product = function_exists('wc_get_product') ? wc_get_product(get_queried_object_id()) : false;
        if ($product) {
            $description = (string) $product->get_short_description();
            if ($description === '') $description = (string) $product->get_description();
        }
    } elseif (is_tax() || is_category() || is_tag()) {
        $term = get_queried_object();
        if ($term instanceof WP_Term) $description = (string) term_description($term);
    } elseif (is_singular()) {
        $post = get_queried_object();
        if ($post instanceof WP_Post) {
            $description = has_excerpt($post) ? (string) get_the_excerpt($post) : (string) $post->post_content;
        }
    } elseif (function_exists('is_shop') && is_shop()) {
        $shop_id = function_exists('wc_get_page_id') ? (int) wc_get_page_id('shop') : 0;
        if ($shop_id > 0) {
            $shop = get_post($shop_id);
            if ($shop instanceof WP_Post) {
                $description = has_excerpt($shop) ? (string) get_the_excerpt($shop) : (string) $shop->post_content;
            }
        }
    }

    $description = trim(wp_strip_all_tags(strip_shortcodes($description)));
    $description = preg_replace('/\s+/u', ' ', $description);

    if ($description === '') {
        $description = trim((string) get_bloginfo('description'));
    }

    return wp_html_excerpt($description, 180, '…');
}

/** Return the best available image for lightweight Open Graph metadata. */
function cg_launch_social_image() {
    $image = '';

    if (function_exists('is_product') && is_product()) {
        $product = function_exists('wc_get_product') ? wc_get_product(get_queried_object_id()) : false;
        if ($product && $product->get_image_id()) {
            $image = wp_get_attachment_image_url($product->get_image_id(), 'full');
        }
    }

    if (!$image && is_singular() && has_post_thumbnail()) {
        $image = get_the_post_thumbnail_url(get_queried_object_id(), 'full');
    }

    if (!$image) {
        $logo_id = (int) get_theme_mod('custom_logo');
        if ($logo_id) $image = wp_get_attachment_image_url($logo_id, 'full');
    }

    return $image ? esc_url_raw($image) : '';
}

/**
 * Lightweight metadata fallback. If a dedicated SEO plugin is active, it owns
 * descriptions/Open Graph and the theme stays out of the way.
 */
function cg_launch_output_fallback_meta() {
    if (cg_launch_has_seo_plugin() || cg_launch_is_noindex_screen()) return;

    $title = wp_get_document_title();
    $description = cg_launch_meta_description();
    $url = is_singular() ? get_permalink() : home_url(add_query_arg([], $GLOBALS['wp']->request ?? ''));
    $image = cg_launch_social_image();
    $type = (function_exists('is_product') && is_product()) ? 'product' : (is_singular() ? 'article' : 'website');

    if ($description !== '') {
        echo "\n<meta name=\"description\" content=\"" . esc_attr($description) . "\">";
    }

    echo "\n<meta property=\"og:site_name\" content=\"" . esc_attr(get_bloginfo('name')) . "\">";
    echo "\n<meta property=\"og:title\" content=\"" . esc_attr($title) . "\">";
    echo "\n<meta property=\"og:type\" content=\"" . esc_attr($type) . "\">";
    echo "\n<meta property=\"og:url\" content=\"" . esc_url($url) . "\">";
    if ($description !== '') echo "\n<meta property=\"og:description\" content=\"" . esc_attr($description) . "\">";
    if ($image !== '') echo "\n<meta property=\"og:image\" content=\"" . esc_url($image) . "\">";

    echo "\n<meta name=\"twitter:card\" content=\"" . ($image !== '' ? 'summary_large_image' : 'summary') . "\">";
    echo "\n<meta name=\"twitter:title\" content=\"" . esc_attr($title) . "\">";
    if ($description !== '') echo "\n<meta name=\"twitter:description\" content=\"" . esc_attr($description) . "\">";
    if ($image !== '') echo "\n<meta name=\"twitter:image\" content=\"" . esc_url($image) . "\">";
    echo "\n";
}
add_action('wp_head', 'cg_launch_output_fallback_meta', 4);

/** Collect shipping methods across all zones, including the fallback zone. */
function cg_launch_has_enabled_shipping_method() {
    if (!class_exists('WC_Shipping_Zones')) return false;

    $zones = WC_Shipping_Zones::get_zones();
    $zones[] = ['shipping_methods' => WC_Shipping_Zones::get_zone(0)->get_shipping_methods(true)];

    foreach ($zones as $zone) {
        $methods = isset($zone['shipping_methods']) ? $zone['shipping_methods'] : [];
        foreach ($methods as $method) {
            if (is_object($method) && isset($method->enabled) && $method->enabled === 'yes') return true;
        }
    }

    return false;
}

/** Build the launch checklist shown to an administrator. */
function cg_launch_checklist_items() {
    $checks = [];
    $add = function ($label, $ok, $details) use (&$checks) {
        $checks[] = ['label' => $label, 'ok' => (bool) $ok, 'details' => $details];
    };

    $home_scheme = wp_parse_url(home_url('/'), PHP_URL_SCHEME);
    $add('HTTPS на публичном сайте', $home_scheme === 'https', $home_scheme === 'https' ? 'Сайт открывается по HTTPS.' : 'Публичный адрес WordPress пока не использует HTTPS.');

    $permalink_structure = (string) get_option('permalink_structure');
    $add('Красивые постоянные ссылки', $permalink_structure !== '', $permalink_structure !== '' ? 'Структура постоянных ссылок настроена.' : 'Сейчас используется стандартный формат ?p=123.');

    $blog_public = (int) get_option('blog_public');
    $add('Индексация поисковыми системами', $blog_public === 1, $blog_public === 1 ? 'Запрет на индексацию отключён.' : 'В Настройки → Чтение включён запрет индексировать сайт. Перед запуском его нужно снять.');

    $privacy_id = (int) get_option('wp_page_for_privacy_policy');
    $add('Страница политики конфиденциальности', $privacy_id > 0 && get_post_status($privacy_id) === 'publish', $privacy_id > 0 ? 'Страница назначена в WordPress.' : 'Страница ещё не назначена в Настройки → Конфиденциальность.');

    if (class_exists('WooCommerce')) {
        foreach (['shop' => 'Каталог WooCommerce', 'cart' => 'Корзина WooCommerce', 'checkout' => 'Оформление заказа WooCommerce', 'myaccount' => 'Личный кабинет WooCommerce'] as $key => $label) {
            $page_id = function_exists('wc_get_page_id') ? (int) wc_get_page_id($key) : 0;
            $add($label, $page_id > 0 && get_post_status($page_id) === 'publish', $page_id > 0 ? 'Страница назначена и опубликована.' : 'Страница не назначена или отсутствует.');
        }

        $terms_id = (int) get_option('woocommerce_terms_page_id');
        $add('Условия продажи / оферта', $terms_id > 0 && get_post_status($terms_id) === 'publish', $terms_id > 0 ? 'Страница назначена в WooCommerce.' : 'В WooCommerce не назначена страница условий продажи.');

        $payment_ok = false;
        if (function_exists('WC') && WC()->payment_gateways()) {
            foreach (WC()->payment_gateways()->payment_gateways() as $gateway) {
                if (isset($gateway->enabled) && $gateway->enabled === 'yes') {
                    $payment_ok = true;
                    break;
                }
            }
        }
        $add('Способ оплаты', $payment_ok, $payment_ok ? 'Есть хотя бы один включённый платёжный способ.' : 'Нет включённых способов оплаты.');

        $shipping_ok = cg_launch_has_enabled_shipping_method();
        $add('Способ доставки', $shipping_ok, $shipping_ok ? 'Есть хотя бы один включённый способ доставки.' : 'Нет включённых способов доставки в зонах WooCommerce.');
    }

    $seo_plugin = cg_launch_has_seo_plugin();
    $add('SEO-метаданные', true, $seo_plugin ? 'Обнаружен SEO-плагин — тема не дублирует его метаданные.' : 'SEO-плагин не обнаружен — тема выводит безопасный базовый description/Open Graph fallback.');

    return $checks;
}

/** Add the pre-launch checklist to Appearance without changing storefront UI. */
function cg_launch_register_admin_page() {
    add_theme_page(
        'Готовность к запуску',
        'Готовность к запуску',
        'manage_options',
        'cg-launch-readiness',
        'cg_launch_render_admin_page'
    );
}
add_action('admin_menu', 'cg_launch_register_admin_page');

/** Render a compact, actionable readiness report. */
function cg_launch_render_admin_page() {
    if (!current_user_can('manage_options')) return;

    $checks = cg_launch_checklist_items();
    $passed = count(array_filter($checks, static function ($item) { return !empty($item['ok']); }));
    $total = count($checks);
    ?>
    <div class="wrap cg-launch-readiness">
        <h1>Готовность магазина к запуску</h1>
        <p>Техническая проверка перед публичным запуском. Она не меняет настройки автоматически — только показывает, что уже готово и что требует внимания.</p>
        <p><strong><?php echo esc_html($passed . ' из ' . $total); ?></strong> проверок пройдено.</p>

        <style>
            .cg-launch-readiness__grid{display:grid;gap:12px;max-width:980px;margin-top:20px}
            .cg-launch-readiness__item{display:grid;grid-template-columns:34px 1fr;gap:12px;align-items:start;padding:16px 18px;background:#fff;border:1px solid #dcdcde;border-radius:10px}
            .cg-launch-readiness__mark{display:grid;place-items:center;width:28px;height:28px;border-radius:50%;font-weight:700}
            .cg-launch-readiness__item.is-ok .cg-launch-readiness__mark{background:#edfaef;color:#137333}
            .cg-launch-readiness__item.is-warn .cg-launch-readiness__mark{background:#fff4e5;color:#8a4b00}
            .cg-launch-readiness__item strong{display:block;font-size:14px;margin-bottom:4px}
            .cg-launch-readiness__item p{margin:0;color:#50575e}
        </style>

        <div class="cg-launch-readiness__grid">
            <?php foreach ($checks as $item) : ?>
                <div class="cg-launch-readiness__item <?php echo !empty($item['ok']) ? 'is-ok' : 'is-warn'; ?>">
                    <span class="cg-launch-readiness__mark" aria-hidden="true"><?php echo !empty($item['ok']) ? '✓' : '!'; ?></span>
                    <div>
                        <strong><?php echo esc_html($item['label']); ?></strong>
                        <p><?php echo esc_html($item['details']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <p style="max-width:980px;margin-top:18px">После прохождения этих проверок останется ручной сценарий: открыть сайт как покупатель, оформить тестовый заказ, проверить оплату/доставку, письмо и уведомление ВКонтакте, а затем пройти страницы визуально.</p>
    </div>
    <?php
}
