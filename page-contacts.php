<?php
/**
 * Contacts page for the /contacts/ slug.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

$contacts_style_path = get_template_directory() . '/assets/css/contacts-page.css';
wp_enqueue_style(
    'cg-contacts-page',
    get_template_directory_uri() . '/assets/css/contacts-page.css',
    ['cg-style'],
    file_exists($contacts_style_path) ? filemtime($contacts_style_path) : wp_get_theme()->get('Version')
);

$primary_phone_raw = '+79304119855';
$primary_phone_label = '+7 (930) 411-98-55';
$backup_phone_raw = '+79525572949';
$backup_phone_label = '+7 (952) 557-29-49';
$email = 'florals-city@yandex.ru';
$address = 'Нововоронеж, ул. Победы, 1Б';
$whatsapp_url = 'https://wa.me/79304119855?text=' . rawurlencode('Здравствуйте! Хочу уточнить информацию о заказе цветов.');
$telegram_url = 'tg://resolve?phone=79304119855';
$vk_url = 'https://vk.com/floralscity';
$instagram_url = 'https://www.instagram.com/florals_city_nv/';
$yandex_maps_url = 'https://yandex.com/maps/org/florals_city/102742626474/';
$google_maps_url = 'https://share.google/9kwCRZqMhlHw6F1dh';
$twogis_maps_url = 'https://2gis.ru/novovoronezh/firm/70000001053810380';
$yandex_map_embed = 'https://yandex.ru/map-widget/v1/?from=mapframe&oid=102742626474&ol=biz';
$catalog_url = function_exists('cg_catalog_url') ? cg_catalog_url() : home_url('/shop/');
$about_url = home_url('/about/');
$delivery_url = function_exists('cg_delivery_payment_url') ? cg_delivery_payment_url() : home_url('/delivery/');

$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'Florist',
    'name' => 'Цветочный город',
    'url' => home_url('/contacts/'),
    'email' => $email,
    'telephone' => [$primary_phone_raw, $backup_phone_raw],
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
    'contactPoint' => [[
        '@type' => 'ContactPoint',
        'telephone' => $primary_phone_raw,
        'contactType' => 'customer service',
        'availableLanguage' => 'Russian',
    ]],
    'sameAs' => [$vk_url, $instagram_url, $yandex_maps_url, $twogis_maps_url],
    'hasMap' => $yandex_maps_url,
];

get_header();
?>

<main class="cg-contacts-page" id="primary">
    <section class="cg-contacts-hero" aria-labelledby="cg-contacts-title">
        <div class="cg-contacts-container cg-contacts-hero__grid">
            <div class="cg-contacts-hero__copy">
                <span class="cg-contacts-eyebrow">Контакты «Цветочного города»</span>
                <h1 id="cg-contacts-title">Телефоны, мессенджеры и адрес магазина</h1>
                <p>На этой странице собраны только способы связи и карта. Для срочного заказа лучше позвонить, для обычного вопроса можно написать в удобный мессенджер.</p>
                <div class="cg-contacts-hero__actions">
                    <a class="cg-contacts-button" href="tel:<?php echo esc_attr($primary_phone_raw); ?>">Позвонить в магазин</a>
                    <a class="cg-contacts-button cg-contacts-button--light" href="<?php echo esc_url($whatsapp_url); ?>" target="_blank" rel="noopener noreferrer">Написать в WhatsApp</a>
                </div>
                <div class="cg-contacts-hero__status"><span></span> Магазин работает ежедневно с 07:00 до 21:00</div>
            </div>

            <aside class="cg-contacts-hero__card" aria-label="Основные контакты">
                <div class="cg-contacts-hero__flower" aria-hidden="true">✿</div>
                <span>Основной телефон</span>
                <a class="cg-contacts-hero__phone" href="tel:<?php echo esc_attr($primary_phone_raw); ?>"><?php echo esc_html($primary_phone_label); ?></a>
                <p><?php echo esc_html($address); ?></p>
                <div class="cg-contacts-hero__hours"><b>Ежедневно</b><strong>07:00–21:00</strong></div>
            </aside>
        </div>
    </section>

    <section class="cg-contacts-section" aria-labelledby="cg-contacts-details-title">
        <div class="cg-contacts-container">
            <div class="cg-contacts-section__head">
                <span class="cg-contacts-eyebrow">Как связаться</span>
                <h2 id="cg-contacts-details-title">Выберите удобный способ</h2>
                <p>Звонки и сообщения принимаем ежедневно в рабочее время магазина — с 07:00 до 21:00.</p>
            </div>

            <div class="cg-contacts-cards">
                <article class="cg-contacts-card">
                    <span class="cg-contacts-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M6.5 3.5 9.4 8l-2.1 2.1c1.1 2.4 3 4.3 5.4 5.4l2.1-2.1 4.4 2.9-.6 3.6c-.1.8-.8 1.4-1.6 1.4C9.4 21.3 3 14.9 3 7.3c0-.8.6-1.5 1.4-1.6l2.1-.2Z"/></svg>
                    </span>
                    <small>Основной телефон</small>
                    <h3><a href="tel:<?php echo esc_attr($primary_phone_raw); ?>"><?php echo esc_html($primary_phone_label); ?></a></h3>
                    <p>Заказы, доставка и вопросы по уже оформленным заказам.</p>
                </article>

                <article class="cg-contacts-card">
                    <span class="cg-contacts-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M6.5 3.5 9.4 8l-2.1 2.1c1.1 2.4 3 4.3 5.4 5.4l2.1-2.1 4.4 2.9-.6 3.6c-.1.8-.8 1.4-1.6 1.4C9.4 21.3 3 14.9 3 7.3c0-.8.6-1.5 1.4-1.6l2.1-.2Z"/></svg>
                    </span>
                    <small>Запасной телефон</small>
                    <h3><a href="tel:<?php echo esc_attr($backup_phone_raw); ?>"><?php echo esc_html($backup_phone_label); ?></a></h3>
                    <p>Можно использовать, если основной номер временно недоступен.</p>
                </article>

                <article class="cg-contacts-card">
                    <span class="cg-contacts-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><path d="M3 5h18v14H3zM3 7l9 7 9-7"/></svg>
                    </span>
                    <small>Электронная почта</small>
                    <h3><a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></h3>
                    <p>Для сотрудничества, корпоративных заказов и деловой переписки.</p>
                </article>

                <article class="cg-contacts-card">
                    <span class="cg-contacts-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg>
                    </span>
                    <small>Время работы</small>
                    <h3>07:00–21:00</h3>
                    <p>Магазин открыт ежедневно без выходных.</p>
                </article>
            </div>

            <div class="cg-contacts-messengers" aria-label="Мессенджеры и социальные сети">
                <a class="cg-contacts-messenger cg-contacts-messenger--whatsapp" href="<?php echo esc_url($whatsapp_url); ?>" target="_blank" rel="noopener noreferrer"><b>WhatsApp</b><span><?php echo esc_html($primary_phone_label); ?></span></a>
                <a class="cg-contacts-messenger cg-contacts-messenger--telegram" href="<?php echo esc_attr($telegram_url); ?>"><b>Telegram</b><span><?php echo esc_html($primary_phone_label); ?></span></a>
                <a class="cg-contacts-messenger" href="<?php echo esc_url($vk_url); ?>" target="_blank" rel="noopener noreferrer"><b>ВКонтакте</b><span>vk.com/floralscity</span></a>
                <a class="cg-contacts-messenger" href="<?php echo esc_url($instagram_url); ?>" target="_blank" rel="noopener noreferrer"><b>Instagram</b><span>@florals_city_nv</span></a>
            </div>
        </div>
    </section>

    <section class="cg-contacts-section cg-contacts-section--soft" aria-labelledby="cg-contacts-map-title">
        <div class="cg-contacts-container">
            <div class="cg-contacts-map-layout">
                <div class="cg-contacts-map-copy">
                    <span class="cg-contacts-eyebrow">Как нас найти</span>
                    <h2 id="cg-contacts-map-title">Магазин на улице Победы</h2>
                    <p class="cg-contacts-map-address"><?php echo esc_html($address); ?></p>
                    <p>Точная точка магазина и вход отмечены на карте. Рядом есть парковка, поэтому за готовым букетом удобно приехать на автомобиле.</p>

                    <div class="cg-contacts-place-features">
                        <div><b>Самовывоз</b><span>Можно заранее заказать букет и забрать его в магазине.</span></div>
                        <div><b>Парковка</b><span>Рядом с магазином можно припарковать автомобиль.</span></div>
                        <div><b>Готовые букеты</b><span>Можно прийти лично и выбрать композицию из наличия.</span></div>
                    </div>

                    <div class="cg-contacts-map-links">
                        <a href="<?php echo esc_url($yandex_maps_url); ?>" target="_blank" rel="noopener noreferrer">Открыть в Яндекс Картах</a>
                        <a href="<?php echo esc_url($google_maps_url); ?>" target="_blank" rel="noopener noreferrer">Google Карты</a>
                        <a href="<?php echo esc_url($twogis_maps_url); ?>" target="_blank" rel="noopener noreferrer">2ГИС</a>
                    </div>
                </div>

                <div class="cg-contacts-map" aria-label="Карта расположения магазина">
                    <iframe
                        src="<?php echo esc_url($yandex_map_embed); ?>"
                        title="Цветочный город на Яндекс Картах"
                        loading="lazy"
                        allowfullscreen
                    ></iframe>
                </div>
            </div>
        </div>
    </section>

    <section class="cg-contacts-cta">
        <div class="cg-contacts-container">
            <div class="cg-contacts-cta__card">
                <div>
                    <span class="cg-contacts-eyebrow">Другие разделы</span>
                    <h2>История магазина и условия доставки — отдельно</h2>
                    <p>На странице «О нас» рассказываем о семье, флористах и подходе к работе. На странице «Доставка и оплата» собраны цены, сроки и способы оплаты.</p>
                </div>
                <div class="cg-contacts-cta__actions">
                    <a href="<?php echo esc_url($about_url); ?>">О нас</a>
                    <a href="<?php echo esc_url($delivery_url); ?>">Доставка и оплата</a>
                    <a href="<?php echo esc_url($catalog_url); ?>">Перейти в каталог</a>
                </div>
            </div>
        </div>
    </section>
</main>

<script type="application/ld+json"><?php echo wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>

<?php get_footer();