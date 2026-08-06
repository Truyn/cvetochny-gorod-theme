<?php
/**
 * Delivery and payment information page.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

$style_path = get_template_directory() . '/assets/css/delivery-payment-page.css';
wp_enqueue_style(
    'cg-delivery-payment-page',
    get_template_directory_uri() . '/assets/css/delivery-payment-page.css',
    ['cg-style'],
    file_exists($style_path) ? filemtime($style_path) : wp_get_theme()->get('Version')
);

$catalog_url = function_exists('cg_catalog_url') ? cg_catalog_url() : home_url('/shop/');
$checkout_url = class_exists('WooCommerce') ? wc_get_checkout_url() : $catalog_url;
$phone_label = get_theme_mod('cg_phone', '+7 (930) 411-98-55');
$phone_raw = preg_replace('/[^0-9+]/', '', $phone_label);
if (!$phone_raw) $phone_raw = '+79304119855';
$address = get_theme_mod('cg_address', 'Нововоронеж, ул. Победы, 1Б');
$threshold = function_exists('cg_get_novovoronezh_free_delivery_threshold')
    ? (float) cg_get_novovoronezh_free_delivery_threshold()
    : 10000;
$delivery_methods = function_exists('cg_delivery_payment_methods') ? cg_delivery_payment_methods() : [];
$payment_gateways = function_exists('cg_delivery_payment_gateways') ? cg_delivery_payment_gateways() : [];

$format_price = static function($price) {
    if (function_exists('wc_price')) {
        return wp_strip_all_tags(wc_price((float) $price, ['decimals' => 0]));
    }
    return number_format_i18n((float) $price, 0) . ' ₽';
};

$gateway_icon = static function($gateway_id) {
    if (strpos($gateway_id, 'bacs') !== false || strpos($gateway_id, 'bank') !== false) {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 9h18M5 9v8m4-8v8m6-8v8m4-8v8M3 18h18M12 3l9 5H3l9-5Z"/></svg>';
    }
    if (strpos($gateway_id, 'cod') !== false) {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v12H4zM8 10h8M8 14h5"/><circle cx="18" cy="16" r="3"/></svg>';
    }
    if (strpos($gateway_id, 'card') !== false || strpos($gateway_id, 'stripe') !== false || strpos($gateway_id, 'yookassa') !== false) {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/></svg>';
    }
    return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2v20M17 6.5c0-1.7-2-3-5-3S7 4.8 7 6.5 9 9.2 12 9.2s5 1.3 5 3-2 3.3-5 3.3-5-1.6-5-3.3"/></svg>';
};

$faq_items = [
    [
        'question' => 'Можно ли заказать доставку в день оформления?',
        'answer' => 'Да, при наличии нужных цветов и свободного времени у флориста и курьера. Лучше оформить заказ в рабочее время и не откладывать его до закрытия магазина.',
    ],
    [
        'question' => 'Можно ли доставить букет ночью?',
        'answer' => 'Да, ночная доставка возможна по предварительному согласованию. Оформите заказ с 7:00 до 21:00, чтобы мы подтвердили время и стоимость.',
    ],
    [
        'question' => 'Когда курьер звонит получателю?',
        'answer' => 'Курьер связывается с получателем, когда его нет по указанному адресу или требуется уточнить вручение. Анонимную доставку можно отметить при оформлении.',
    ],
    [
        'question' => 'Что будет, если нужного цветка нет?',
        'answer' => 'Флорист предложит максимально похожую замену по оттенку, форме и стоимости. Любую существенную замену мы согласуем до сборки.',
    ],
    [
        'question' => 'Когда становится известна точная стоимость доставки?',
        'answer' => 'Для населённых пунктов из списка цена сразу добавляется к заказу. Для другого адреса за пределами списка стоимость подтверждает менеджер после оформления.',
    ],
];

$schema = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Service',
            'name' => 'Доставка цветов «Цветочный город»',
            'provider' => [
                '@type' => 'Florist',
                'name' => 'Цветочный город',
                'telephone' => $phone_raw,
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => 'ул. Победы, 1Б',
                    'addressLocality' => 'Нововоронеж',
                    'addressRegion' => 'Воронежская область',
                    'addressCountry' => 'RU',
                ],
            ],
            'areaServed' => ['Нововоронеж', 'Воронежская область'],
            'url' => function_exists('cg_delivery_payment_url') ? cg_delivery_payment_url() : home_url('/delivery/'),
        ],
        [
            '@type' => 'FAQPage',
            'mainEntity' => array_map(static function($item) {
                return [
                    '@type' => 'Question',
                    'name' => $item['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $item['answer'],
                    ],
                ];
            }, $faq_items),
        ],
    ],
];

get_header();
?>

<main class="cg-delivery-page" id="primary">
    <section class="cg-delivery-hero" aria-labelledby="cg-delivery-title">
        <div class="cg-delivery-container cg-delivery-hero__grid">
            <div class="cg-delivery-hero__copy">
                <span class="cg-delivery-eyebrow">Доставка и оплата</span>
                <h1 id="cg-delivery-title">Доставим цветы бережно и точно ко времени</h1>
                <p class="cg-delivery-hero__lead">Принимаем заказы ежедневно с 7:00 до 21:00, доставляем по Нововоронежу и Воронежской области. Стоимость выбранного направления сразу появится в корзине и оформлении заказа.</p>
                <div class="cg-delivery-hero__actions">
                    <a class="cg-delivery-button" href="<?php echo esc_url($catalog_url); ?>">Выбрать букет</a>
                    <a class="cg-delivery-button cg-delivery-button--light" href="tel:<?php echo esc_attr($phone_raw); ?>">Уточнить доставку</a>
                </div>
                <div class="cg-delivery-hero__meta">
                    <span><b>7:00–21:00</b> приём заказов</span>
                    <span><b>В день заказа</b> при наличии интервала</span>
                </div>
            </div>

            <aside class="cg-delivery-hero__card" aria-label="Главное условие бесплатной доставки">
                <div class="cg-delivery-hero__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M3 6h11v9H3zM14 9h4l3 3v3h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg>
                </div>
                <span>По Нововоронежу</span>
                <strong>Бесплатно от <?php echo esc_html($format_price($threshold)); ?></strong>
                <p>Для других населённых пунктов доставка остаётся платной независимо от суммы заказа.</p>
            </aside>
        </div>
    </section>

    <section class="cg-delivery-highlights" aria-label="Условия доставки">
        <div class="cg-delivery-container cg-delivery-highlights__grid">
            <article><span aria-hidden="true">◎</span><div><strong>Фото готового букета</strong><p>Отправим фотографию перед передачей курьеру.</p></div></article>
            <article><span aria-hidden="true">☾</span><div><strong>Ночная доставка</strong><p>Возможна по предварительному согласованию.</p></div></article>
            <article><span aria-hidden="true">♡</span><div><strong>Анонимное вручение</strong><p>Не сообщим получателю имя отправителя.</p></div></article>
            <article><span aria-hidden="true">✉</span><div><strong>Открытка бесплатно</strong><p>Добавим ваш текст к заказу без доплаты.</p></div></article>
        </div>
    </section>

    <section class="cg-delivery-section cg-delivery-section--soft" aria-labelledby="cg-delivery-rates-title">
        <div class="cg-delivery-container">
            <header class="cg-delivery-section__head">
                <span class="cg-delivery-eyebrow">Стоимость доставки</span>
                <h2 id="cg-delivery-rates-title">Выберите населённый пункт</h2>
                <p>Цены ниже загружаются из активных способов доставки WooCommerce. Когда стоимость меняется в настройках магазина, она автоматически обновляется и на этой странице.</p>
            </header>

            <?php if ($delivery_methods): ?>
                <div class="cg-delivery-rates">
                    <?php foreach ($delivery_methods as $method): ?>
                        <article class="cg-delivery-rate<?php echo $method['is_novovoronezh'] ? ' is-local' : ''; ?>">
                            <div class="cg-delivery-rate__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M12 21s7-5.4 7-12a7 7 0 1 0-14 0c0 6.6 7 12 7 12Z"/><circle cx="12" cy="9" r="2.5"/></svg>
                            </div>
                            <div class="cg-delivery-rate__copy">
                                <h3><?php echo esc_html($method['label']); ?></h3>
                                <?php if ($method['is_novovoronezh']): ?>
                                    <p>Бесплатно при сумме товаров от <?php echo esc_html($format_price($threshold)); ?>.</p>
                                <?php else: ?>
                                    <p>Стоимость не зависит от суммы заказа.</p>
                                <?php endif; ?>
                            </div>
                            <strong><?php echo esc_html($format_price($method['price'])); ?></strong>
                        </article>
                    <?php endforeach; ?>

                    <article class="cg-delivery-rate is-custom">
                        <div class="cg-delivery-rate__icon" aria-hidden="true">＋</div>
                        <div class="cg-delivery-rate__copy">
                            <h3>Другой населённый пункт</h3>
                            <p>Укажите его при оформлении — менеджер рассчитает маршрут и свяжется с вами.</p>
                        </div>
                        <strong>Уточним</strong>
                    </article>
                </div>
            <?php else: ?>
                <div class="cg-delivery-empty">
                    <strong>Стоимость рассчитывается индивидуально</strong>
                    <p>Укажите населённый пункт при оформлении заказа или позвоните флористу по номеру <?php echo esc_html($phone_label); ?>.</p>
                </div>
            <?php endif; ?>

            <div class="cg-delivery-rate-note">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 10v6M12 7h.01"/></svg>
                <p>Итоговая цена заказа складывается из стоимости товаров и выбранной доставки. Бесплатное условие действует только по Нововоронежу.</p>
            </div>
        </div>
    </section>

    <section class="cg-delivery-section" aria-labelledby="cg-delivery-process-title">
        <div class="cg-delivery-container">
            <header class="cg-delivery-section__head">
                <span class="cg-delivery-eyebrow">Как проходит заказ</span>
                <h2 id="cg-delivery-process-title">От выбора букета до вручения</h2>
            </header>

            <div class="cg-delivery-process">
                <article><span>1</span><div><h3>Оформляете заказ</h3><p>Выбираете букет, населённый пункт, дату, интервал, адрес и оставляете контакты.</p></div></article>
                <article><span>2</span><div><h3>Флорист подтверждает детали</h3><p>Проверяет наличие, учитывает пожелания и согласовывает возможные замены.</p></div></article>
                <article><span>3</span><div><h3>Получаете фото</h3><p>Перед отправкой мы показываем готовую композицию.</p></div></article>
                <article><span>4</span><div><h3>Курьер вручает заказ</h3><p>При необходимости связывается с получателем, если его нет по адресу.</p></div></article>
            </div>

            <div class="cg-delivery-important">
                <div>
                    <span class="cg-delivery-eyebrow">Доставка в день заказа</span>
                    <h3>Оформляйте заказ заранее</h3>
                    <p>Доставка в день обращения возможна, но зависит от наличия цветов и свободных интервалов. Заказ, оформленный незадолго до закрытия, может быть перенесён на следующий день.</p>
                </div>
                <div>
                    <span class="cg-delivery-eyebrow">Ночное время</span>
                    <h3>Согласуем отдельно</h3>
                    <p>Ночной интервал нужно подтвердить с флористом в рабочее время магазина. Стоимость может зависеть от адреса и времени выезда.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="cg-delivery-section cg-delivery-section--dark" aria-labelledby="cg-payment-title">
        <div class="cg-delivery-container">
            <header class="cg-delivery-section__head">
                <span class="cg-delivery-eyebrow">Оплата</span>
                <h2 id="cg-payment-title">Доступные способы оплаты</h2>
                <p>При оформлении заказа показываются только включённые в WooCommerce способы. Инструкции и реквизиты отображаются после выбора соответствующего варианта.</p>
            </header>

            <div class="cg-payment-grid">
                <?php if ($payment_gateways): ?>
                    <?php foreach ($payment_gateways as $gateway): ?>
                        <article class="cg-payment-card">
                            <div class="cg-payment-card__icon"><?php echo wp_kses($gateway_icon($gateway['id']), ['svg' => ['viewBox' => true, 'aria-hidden' => true], 'path' => ['d' => true], 'rect' => ['x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true], 'circle' => ['cx' => true, 'cy' => true, 'r' => true]]); ?></div>
                            <h3><?php echo esc_html($gateway['title'] ?: 'Оплата заказа'); ?></h3>
                            <p><?php echo esc_html($gateway['description'] ?: 'Подробные инструкции появятся при оформлении заказа.'); ?></p>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <article class="cg-payment-card">
                        <div class="cg-payment-card__icon"><?php echo wp_kses($gateway_icon('bank'), ['svg' => ['viewBox' => true, 'aria-hidden' => true], 'path' => ['d' => true]]); ?></div>
                        <h3>Оплата после подтверждения</h3>
                        <p>После оформления флорист свяжется с вами, подтвердит состав, доставку и удобный способ оплаты.</p>
                    </article>
                <?php endif; ?>

                <article class="cg-payment-card is-accent">
                    <div class="cg-payment-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M12 3 4 7v5c0 4.6 3.3 7.8 8 9 4.7-1.2 8-4.4 8-9V7l-8-4Z"/><path d="m8.5 12 2.2 2.2 4.8-5"/></svg>
                    </div>
                    <h3>Сначала подтверждаем заказ</h3>
                    <p>Флорист проверит наличие цветов и важные детали. При замене состава или изменении доставки всё согласуется с вами.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="cg-delivery-section" aria-labelledby="cg-delivery-faq-title">
        <div class="cg-delivery-container cg-delivery-faq-layout">
            <header class="cg-delivery-section__head">
                <span class="cg-delivery-eyebrow">Частые вопросы</span>
                <h2 id="cg-delivery-faq-title">Всё важное до оформления</h2>
                <p>Не нашли ответ — позвоните флористу. Мы работаем ежедневно с 7:00 до 21:00.</p>
                <a class="cg-delivery-button cg-delivery-button--light" href="tel:<?php echo esc_attr($phone_raw); ?>"><?php echo esc_html($phone_label); ?></a>
            </header>

            <div class="cg-delivery-faq">
                <?php foreach ($faq_items as $index => $item): ?>
                    <details<?php echo $index === 0 ? ' open' : ''; ?>>
                        <summary><?php echo esc_html($item['question']); ?><span aria-hidden="true"></span></summary>
                        <p><?php echo esc_html($item['answer']); ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="cg-delivery-cta">
        <div class="cg-delivery-container cg-delivery-cta__inner">
            <div>
                <span class="cg-delivery-eyebrow">Готовы оформить заказ?</span>
                <h2>Выберите букет — доставку рассчитаем сразу</h2>
                <p>Магазин: <?php echo esc_html($address); ?>. Заказы и доставка ежедневно с 7:00 до 21:00.</p>
            </div>
            <div class="cg-delivery-cta__actions">
                <a class="cg-delivery-button" href="<?php echo esc_url($catalog_url); ?>">Перейти в каталог</a>
                <a class="cg-delivery-button cg-delivery-button--light" href="<?php echo esc_url($checkout_url); ?>">Оформить заказ</a>
            </div>
        </div>
    </section>
</main>

<script type="application/ld+json"><?php echo wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>

<?php get_footer(); ?>
