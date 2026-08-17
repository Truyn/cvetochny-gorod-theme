<?php
/**
 * One simple entry point for occasional store-management helpers.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

function cg_store_tools_register_hub() {
    add_submenu_page(
        'woocommerce',
        'Инструменты магазина',
        'Инструменты магазина',
        'manage_woocommerce',
        'cg-store-tools',
        'cg_store_tools_render_hub'
    );
}
add_action('admin_menu', 'cg_store_tools_register_hub', 24);

function cg_store_tools_catalog_score() {
    if (!function_exists('cg_catalog_quality_report')) return null;
    $report = (array) cg_catalog_quality_report();
    return isset($report['score']) ? (int) $report['score'] : null;
}

function cg_store_tools_manual_progress() {
    if (!function_exists('cg_order_readiness_manual_items') || !function_exists('cg_order_readiness_saved_manual')) return null;
    $items = (array) cg_order_readiness_manual_items();
    $saved = (array) cg_order_readiness_saved_manual();
    $done = 0;
    foreach (array_keys($items) as $key) if (!empty($saved[$key])) $done++;
    return [$done, count($items)];
}

function cg_store_tools_render_hub() {
    if (!current_user_can('manage_woocommerce')) return;

    $indexing = (int) get_option('blog_public') === 1;
    $analytics = trim((string) get_option('cg_analytics_ga4_id', ''));
    $catalog_score = cg_store_tools_catalog_score();
    $manual = cg_store_tools_manual_progress();
    $landing_counts = post_type_exists('cg_landing') ? wp_count_posts('cg_landing') : null;
    $landings = $landing_counts && isset($landing_counts->publish) ? (int) $landing_counts->publish : 0;

    $cards = [
        [
            'title' => 'Поиск и продвижение',
            'text' => 'Простая памятка о том, что помогает людям находить магазин в Яндексе и Google. Техническая часть работает автоматически.',
            'status' => $indexing ? 'Индексация разрешена' : 'Индексация сейчас запрещена',
            'class' => $indexing ? 'is-ok' : 'is-warn',
            'url' => admin_url('admin.php?page=cg-search-promotion-guide'),
            'button' => 'Открыть поиск и продвижение',
        ],
        [
            'title' => 'Качество каталога',
            'text' => 'Проверка фотографий, цен, описаний и наличия. Нужна время от времени, а не каждый день.',
            'status' => $catalog_score === null ? 'Проверка доступна' : 'Готовность каталога: ' . $catalog_score . '%',
            'class' => ($catalog_score !== null && $catalog_score < 80) ? 'is-warn' : 'is-ok',
            'url' => admin_url('admin.php?page=cg-catalog-quality'),
            'button' => 'Проверить каталог',
        ],
        [
            'title' => 'Готовые подборки',
            'text' => 'Страницы вроде «Букеты на день рождения» или «Для мамы». Они полезны и покупателям, и поиску.',
            'status' => 'Опубликовано: ' . $landings,
            'class' => $landings > 0 ? 'is-ok' : 'is-neutral',
            'url' => admin_url('edit.php?post_type=cg_landing'),
            'button' => 'Открыть подборки',
        ],
        [
            'title' => 'Статистика сайта',
            'text' => 'Подключение внешней статистики, если она понадобится. Магазин полностью работает и без неё.',
            'status' => $analytics !== '' ? 'GA4 подключён' : 'Можно настроить позже',
            'class' => $analytics !== '' ? 'is-ok' : 'is-neutral',
            'url' => admin_url('admin.php?page=cg-commerce-analytics'),
            'button' => 'Открыть статистику',
        ],
        [
            'title' => 'Проверка перед запуском',
            'text' => 'Технические проверки и контрольный реальный заказ перед тем, как окончательно открыть магазин для покупателей.',
            'status' => $manual ? ($manual[0] . ' из ' . $manual[1] . ' реальных проверок') : 'Чек-лист готов',
            'class' => ($manual && $manual[0] === $manual[1]) ? 'is-ok' : 'is-neutral',
            'url' => admin_url('admin.php?page=cg-order-readiness'),
            'button' => 'Открыть проверку',
        ],
    ];
    ?>
    <div class="wrap cg-store-tools">
        <div class="cg-store-tools__hero">
            <span>Цветочный город</span>
            <h1>Инструменты магазина</h1>
            <p>Здесь собраны вещи, которые могут пригодиться время от времени. Для обычной работы с несколькими заказами в день достаточно разделов с товарами, заказами и доставками.</p>
        </div>

        <div class="cg-store-tools__search-note <?php echo $indexing ? 'is-ok' : 'is-warn'; ?>">
            <div>
                <strong><?php echo esc_html($indexing ? 'Поисковая индексация включена' : 'Поисковая индексация выключена'); ?></strong>
                <span><?php echo esc_html($indexing ? 'Сайт можно показывать поисковым системам. Базовые SEO-описания, sitemap и локальная разметка работают автоматически.' : 'Перед запуском нужно разрешить поисковым системам индексировать сайт в Настройки → Чтение.'); ?></span>
            </div>
            <a href="<?php echo esc_url(home_url('/wp-sitemap.xml')); ?>" target="_blank" rel="noopener">Открыть sitemap</a>
        </div>

        <div class="cg-store-tools__grid">
            <?php foreach ($cards as $card) : ?>
                <section class="cg-store-tools__card <?php echo esc_attr($card['class']); ?>">
                    <div class="cg-store-tools__card-head">
                        <h2><?php echo esc_html($card['title']); ?></h2>
                        <span><?php echo esc_html($card['status']); ?></span>
                    </div>
                    <p><?php echo esc_html($card['text']); ?></p>
                    <a class="button" href="<?php echo esc_url($card['url']); ?>"><?php echo esc_html($card['button']); ?></a>
                </section>
            <?php endforeach; ?>
        </div>

        <div class="cg-store-tools__seo-simple">
            <strong>Что действительно важно для поиска</strong>
            <p>Понятное название товара, хорошие фотографии, цена, короткое описание, обычное описание и правильная категория. Для локального магазина этого важнее, чем заполнять десятки технических SEO-полей.</p>
        </div>
    </div>
    <style>
        .cg-store-tools{max-width:1120px}.cg-store-tools__hero{margin:22px 0 18px;padding:24px 26px;border:1px solid #eadbd6;border-radius:18px;background:linear-gradient(135deg,#fff8f5,#fff)}.cg-store-tools__hero>span{color:#a15d65;font-size:11px;font-weight:800;letter-spacing:.11em;text-transform:uppercase}.cg-store-tools__hero h1{margin:5px 0 8px;font-size:30px;color:#463633}.cg-store-tools__hero p{max-width:820px;margin:0;color:#74605b;font-size:14px;line-height:1.6}.cg-store-tools__search-note{display:flex;align-items:center;justify-content:space-between;gap:20px;margin:0 0 16px;padding:16px 18px;border:1px solid #d9e7dc;border-radius:14px;background:#f7fcf8}.cg-store-tools__search-note.is-warn{border-color:#ead0b2;background:#fffaf2}.cg-store-tools__search-note strong,.cg-store-tools__search-note span{display:block}.cg-store-tools__search-note strong{color:#493936}.cg-store-tools__search-note span{margin-top:4px;color:#76635e;line-height:1.5}.cg-store-tools__search-note a{white-space:nowrap;font-weight:700;color:#9a555c}.cg-store-tools__grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.cg-store-tools__card{padding:20px;border:1px solid #e2d8d4;border-radius:15px;background:#fff}.cg-store-tools__card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}.cg-store-tools__card h2{margin:0;color:#4c3935;font-size:18px}.cg-store-tools__card-head span{padding:5px 8px;border-radius:999px;background:#f2f0ef;color:#705d58;font-size:11px;font-weight:700;white-space:nowrap}.cg-store-tools__card.is-ok .cg-store-tools__card-head span{background:#eaf7ee;color:#37704b}.cg-store-tools__card.is-warn .cg-store-tools__card-head span{background:#fff1df;color:#8a5d22}.cg-store-tools__card p{min-height:46px;color:#75615c;line-height:1.55}.cg-store-tools__seo-simple{margin:16px 0 30px;padding:18px 20px;border-left:4px solid #d98d94;background:#fff8f6}.cg-store-tools__seo-simple strong{font-size:16px}.cg-store-tools__seo-simple p{margin-bottom:0;color:#6e5b56;line-height:1.6}@media(max-width:782px){.cg-store-tools__grid{grid-template-columns:1fr}.cg-store-tools__search-note{align-items:flex-start;flex-direction:column}.cg-store-tools__card p{min-height:0}}
    </style>
    <?php
}
