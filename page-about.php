<?php
/**
 * About page for the /about/ slug.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

$about_style_path = get_template_directory() . '/assets/css/about-page.css';
wp_enqueue_style(
    'cg-about-page',
    get_template_directory_uri() . '/assets/css/about-page.css',
    ['cg-style'],
    file_exists($about_style_path) ? filemtime($about_style_path) : wp_get_theme()->get('Version')
);

$catalog_url = function_exists('cg_catalog_url') ? cg_catalog_url() : home_url('/shop/');
$delivery_url = function_exists('cg_delivery_payment_url') ? cg_delivery_payment_url() : home_url('/delivery/');
$contacts_url = get_theme_mod('cg_contacts_url', home_url('/contacts/')) ?: home_url('/contacts/');
$phone_raw = '+79304119855';
$yandex_maps_url = 'https://yandex.com/maps/org/florals_city/102742626474/';
$google_maps_url = 'https://share.google/9kwCRZqMhlHw6F1dh';
$twogis_maps_url = 'https://2gis.ru/novovoronezh/firm/70000001053810380';

$about_products = [];
if (function_exists('wc_get_products')) {
    $about_products = wc_get_products([
        'status' => 'publish',
        'featured' => true,
        'limit' => 3,
        'orderby' => 'date',
        'order' => 'DESC',
        'return' => 'objects',
    ]);

    if (count($about_products) < 3) {
        $fallback_products = wc_get_products([
            'status' => 'publish',
            'limit' => 8,
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'objects',
        ]);

        $known_ids = array_map(function($product) {
            return $product instanceof WC_Product ? $product->get_id() : 0;
        }, $about_products);

        foreach ($fallback_products as $product) {
            if (!$product instanceof WC_Product) continue;
            if (in_array($product->get_id(), $known_ids, true)) continue;
            $about_products[] = $product;
            $known_ids[] = $product->get_id();
            if (count($about_products) >= 3) break;
        }
    }
}

$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'Florist',
    'name' => 'Цветочный город',
    'url' => home_url('/about/'),
    'telephone' => $phone_raw,
    'priceRange' => '₽₽',
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => 'ул. Победы, 1Б',
        'addressLocality' => 'Нововоронеж',
        'addressRegion' => 'Воронежская область',
        'addressCountry' => 'RU',
    ],
    'openingHoursSpecification' => [[
        '@type' => 'OpeningHoursSpecification',
        'dayOfWeek' => [
            'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday',
        ],
        'opens' => '07:00',
        'closes' => '21:00',
    ]],
];

get_header();
?>

<main class="cg-about-page" id="primary">
    <section class="cg-about-hero" aria-labelledby="cg-about-title">
        <div class="cg-about-container cg-about-hero__grid">
            <div class="cg-about-hero__copy">
                <span class="cg-about-eyebrow">Семейная флористика в Нововоронеже</span>
                <h1 id="cg-about-title">Цветы, в которые мы вкладываем заботу</h1>
                <p class="cg-about-hero__lead">
                    «Цветочный город» — семейный магазин, где каждый букет собирают не по шаблону, а для конкретного человека, повода и настроения. Мы внимательно относимся к вашим пожеланиям, бюджету и каждой детали заказа.
                </p>
                <div class="cg-about-hero__actions">
                    <a class="cg-about-button" href="<?php echo esc_url($catalog_url); ?>">Выбрать букет</a>
                    <a class="cg-about-button cg-about-button--light" href="tel:<?php echo esc_attr($phone_raw); ?>">Позвонить флористу</a>
                </div>
                <div class="cg-about-hero__note">Можно прийти в магазин и выбрать готовый букет лично</div>
            </div>

            <div class="cg-about-mosaic" aria-label="Букеты магазина Цветочный город">
                <?php if ($about_products): ?>
                    <?php foreach (array_slice($about_products, 0, 3) as $index => $product): ?>
                        <?php
                        if (!$product instanceof WC_Product) continue;
                        $image_id = $product->get_image_id();
                        $image_html = $image_id
                            ? wp_get_attachment_image($image_id, $index === 0 ? 'large' : 'woocommerce_thumbnail', false, [
                                'loading' => $index === 0 ? 'eager' : 'lazy',
                                'fetchpriority' => $index === 0 ? 'high' : 'auto',
                                'alt' => $product->get_name(),
                            ])
                            : wc_placeholder_img('woocommerce_thumbnail');
                        ?>
                        <a class="cg-about-mosaic__item" href="<?php echo esc_url(get_permalink($product->get_id())); ?>" aria-label="<?php echo esc_attr($product->get_name()); ?>">
                            <?php echo wp_kses_post($image_html); ?>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="cg-about-mosaic__fallback" aria-hidden="true"><span>✿</span></div>
                <?php endif; ?>
                <div class="cg-about-mosaic__label">Свежие поставки несколько раз в неделю</div>
            </div>
        </div>
    </section>

    <section class="cg-about-stats" aria-label="Цветочный город в цифрах">
        <div class="cg-about-container cg-about-stats__grid">
            <div class="cg-about-stat"><strong>10+</strong><span>лет семейной истории с цветами</span></div>
            <div class="cg-about-stat"><strong>15+</strong><span>лет опыта у наших флористов</span></div>
            <div class="cg-about-stat"><strong>5000+</strong><span>выполненных заказов</span></div>
            <div class="cg-about-stat"><strong>7:00–21:00</strong><span>ежедневно принимаем заказы</span></div>
        </div>
    </section>

    <section class="cg-about-section">
        <div class="cg-about-container cg-about-story">
            <div class="cg-about-story__copy">
                <span class="cg-about-eyebrow">Наша история</span>
                <div class="cg-about-section__head">
                    <h2>Семейное дело, выросшее из любви к цветам</h2>
                </div>
                <p>
                    Наша семейная история с цветами началась больше десяти лет назад. Около семи лет назад магазин открылся в нынешнем формате и стал местом, куда приходят не только за букетом, но и за помощью в выборе правильных слов и настроения.
                </p>
                <p>
                    У наших флористов больше пятнадцати лет профессионального опыта. Они умеют собрать и лёгкий нежный букет, и выразительную авторскую композицию, подобрать цветы к важному событию или создать решение в рамках заданного бюджета.
                </p>
                <p>
                    Мы не стремимся просто продать цветы. Нам важно, чтобы подарок действительно порадовал получателя, а покупатель был уверен в результате ещё до доставки.
                </p>
            </div>

            <aside class="cg-about-story__quote">
                <div>
                    <blockquote>Каждый заказ для нас — это чья-то важная встреча, признание, благодарность или праздник.</blockquote>
                </div>
                <footer>Команда «Цветочного города»</footer>
            </aside>
        </div>
    </section>

    <section class="cg-about-section cg-about-section--soft" aria-labelledby="cg-about-values-title">
        <div class="cg-about-container">
            <div class="cg-about-section__head">
                <span class="cg-about-eyebrow">Почему нам доверяют</span>
                <h2 id="cg-about-values-title">Забота видна в деталях</h2>
                <p>Мы выстроили работу так, чтобы заказ был понятным, спокойным и предсказуемым на каждом этапе.</p>
            </div>

            <div class="cg-about-values">
                <article class="cg-about-value">
                    <div class="cg-about-value__icon" aria-hidden="true">✿</div>
                    <h3>Свежие поставки</h3>
                    <p>Цветы приходят несколько раз в неделю от московских и импортных поставщиков.</p>
                </article>
                <article class="cg-about-value">
                    <div class="cg-about-value__icon" aria-hidden="true">❄</div>
                    <h3>Правильное хранение</h3>
                    <p>У нас есть собственное холодильное оборудование и условия для бережного хранения цветов.</p>
                </article>
                <article class="cg-about-value">
                    <div class="cg-about-value__icon" aria-hidden="true">◎</div>
                    <h3>Фото перед отправкой</h3>
                    <p>Показываем готовый букет до передачи курьеру, чтобы вы видели результат заранее.</p>
                </article>
                <article class="cg-about-value">
                    <div class="cg-about-value__icon" aria-hidden="true">♡</div>
                    <h3>Под ваш запрос</h3>
                    <p>Учитываем повод, пожелания, настроение и бюджет, а не предлагаем одно и то же всем.</p>
                </article>
                <article class="cg-about-value">
                    <div class="cg-about-value__icon" aria-hidden="true">↺</div>
                    <h3>Замены только по согласованию</h3>
                    <p>Если нужного цветка нет, предложим похожий вариант и обязательно согласуем замену.</p>
                </article>
                <article class="cg-about-value">
                    <div class="cg-about-value__icon" aria-hidden="true">✉</div>
                    <h3>Открытка бесплатно</h3>
                    <p>Добавим к заказу открытку с вашим текстом без дополнительной оплаты.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="cg-about-section" aria-labelledby="cg-about-services-title">
        <div class="cg-about-container">
            <div class="cg-about-section__head">
                <span class="cg-about-eyebrow">Что мы создаём</span>
                <h2 id="cg-about-services-title">Букеты для важных моментов</h2>
                <p>Поможем выбрать готовый вариант или соберём композицию специально для вашего случая.</p>
            </div>

            <div class="cg-about-services">
                <article class="cg-about-service"><span>01</span><h3>Авторские букеты</h3><p>Композиции по настроению, палитре, поводу и бюджету.</p></article>
                <article class="cg-about-service"><span>02</span><h3>Свадебная флористика</h3><p>Букеты и цветочные решения для одного из самых важных дней.</p></article>
                <article class="cg-about-service"><span>03</span><h3>Дни рождения</h3><p>От нежных знаков внимания до ярких праздничных букетов.</p></article>
                <article class="cg-about-service"><span>04</span><h3>Цветы для организаций</h3><p>Поздравления сотрудников, партнёров и оформление деловых поводов.</p></article>
            </div>
        </div>
    </section>

    <section class="cg-about-section cg-about-section--soft" aria-labelledby="cg-about-trust-title">
        <div class="cg-about-container cg-about-trust">
            <div class="cg-about-trust__copy">
                <span class="cg-about-eyebrow">Честный сервис</span>
                <h2 id="cg-about-trust-title">Мы остаёмся на связи после заказа</h2>
                <p>
                    Нам важно не просто выполнить заказ, а сохранить ваше доверие. Отзывы покупателей о «Цветочном городе» можно найти на Яндекс Картах, Google Картах и в 2ГИС. Если что-то вас не устроило, свяжитесь с нами — мы разберёмся в ситуации и постараемся найти справедливое решение.
                </p>
                <div class="cg-about-trust__links">
                    <a href="<?php echo esc_url($yandex_maps_url); ?>" target="_blank" rel="noopener noreferrer">Отзывы на Яндекс Картах</a>
                    <a href="<?php echo esc_url($google_maps_url); ?>" target="_blank" rel="noopener noreferrer">Отзывы в Google Картах</a>
                    <a href="<?php echo esc_url($twogis_maps_url); ?>" target="_blank" rel="noopener noreferrer">Отзывы в 2ГИС</a>
                </div>
            </div>

            <aside class="cg-about-promise" aria-label="Наши обязательства">
                <div class="cg-about-promise__item"><b>✓</b><div><strong>Не скрываем результат</strong><span>Перед доставкой отправляем фотографию готового букета.</span></div></div>
                <div class="cg-about-promise__item"><b>✓</b><div><strong>Не меняем цветы молча</strong><span>Любую существенную замену обсуждаем с покупателем.</span></div></div>
                <div class="cg-about-promise__item"><b>✓</b><div><strong>Не оставляем проблему без ответа</strong><span>Выслушаем, разберёмся и предложим вариант решения.</span></div></div>
            </aside>
        </div>
    </section>

    <section class="cg-about-contact">
        <div class="cg-about-container">
            <div class="cg-about-contact__card">
                <div class="cg-about-contact__copy">
                    <span class="cg-about-eyebrow">Полезная информация</span>
                    <h2>Практические условия вынесены на отдельные страницы</h2>
                    <p>Здесь мы рассказываем о магазине, команде и подходе к работе. Стоимость доставки, способы оплаты, телефоны, мессенджеры и карта находятся в профильных разделах.</p>
                </div>

                <div class="cg-about-contact__details">
                    <div class="cg-about-contact__row"><span>Доставка и оплата</span><strong>цены, сроки, ночная доставка и способы оплаты</strong></div>
                    <div class="cg-about-contact__row"><span>Контакты</span><strong>телефоны, мессенджеры, адрес и карта магазина</strong></div>
                    <div class="cg-about-contact__actions">
                        <a href="<?php echo esc_url($delivery_url); ?>">Доставка и оплата</a>
                        <a href="<?php echo esc_url($contacts_url); ?>">Открыть контакты</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script type="application/ld+json"><?php echo wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>

<?php get_footer();