<?php
/**
 * Header and footer settings.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

function cg_site_customize($wp_customize) {
    $wp_customize->add_section('cg_header_settings', [
        'title' => 'Шапка и контакты',
        'description' => 'Телефон, адрес, режим работы, социальные сети и ссылки, которые используются в шапке и подвале сайта.',
        'priority' => 38,
    ]);

    $wp_customize->add_section('cg_footer_settings', [
        'title' => 'Подвал сайта',
        'priority' => 39,
    ]);

    $fields = [
        'cg_phone' => ['Телефон', '+7 (930) 411-98-55', 'cg_header_settings', 'text', 'Отображается в верхней панели и подвале.'],
        'cg_address' => ['Адрес', 'Нововоронеж, ул. Победы, 1Б', 'cg_header_settings', 'text', 'Отображается в верхней панели и подвале.'],
        'cg_address_url' => ['Ссылка адреса в верхней панели', home_url('/contacts/#cg-contacts-map-title'), 'cg_header_settings', 'url', 'Обычно ведёт к карте на странице контактов.'],
        'cg_delivery_url' => ['Ссылка «Доставка и оплата»', home_url('/delivery/'), 'cg_header_settings', 'url', 'Используется и в верхней панели, и в подвале.'],
        'cg_contacts_url' => ['Ссылка «Контакты» в подвале', home_url('/contacts/'), 'cg_header_settings', 'url', 'Укажите адрес вашей страницы контактов.'],
        'cg_worktime' => ['Режим работы', 'Ежедневно с 07:00 до 21:00', 'cg_header_settings', 'text', 'Отображается в подвале сайта.'],
        'cg_brand_title' => ['Название магазина', 'Цветочный город', 'cg_header_settings', 'text', ''],
        'cg_brand_subtitle' => ['Подпись под названием', 'магазин цветов', 'cg_header_settings', 'text', ''],
        'cg_whatsapp_url' => ['Ссылка WhatsApp', 'https://wa.me/79304119855', 'cg_header_settings', 'url', ''],
        'cg_telegram_url' => ['Ссылка Telegram', 'tg://resolve?phone=79304119855', 'cg_header_settings', 'url', ''],
        'cg_vk_url' => ['Ссылка ВКонтакте', 'https://vk.com/floralscity', 'cg_header_settings', 'url', ''],
        'cg_instagram_url' => ['Ссылка Instagram', 'https://www.instagram.com/florals_city_nv/', 'cg_header_settings', 'url', 'Эта ссылка также используется блоком «Наши букеты в жизни», если там не задана отдельная ссылка.'],
        'cg_footer_text' => ['Описание в подвале', 'Букеты и подарки с доставкой по Нововоронежу и Воронежской области.', 'cg_footer_settings', 'textarea', ''],
        'cg_footer_catalog_title' => ['Заголовок колонки каталога', 'Каталог', 'cg_footer_settings', 'text', ''],
        'cg_footer_buyers_title' => ['Заголовок колонки покупателям', 'Покупателям', 'cg_footer_settings', 'text', ''],
        'cg_footer_contacts_title' => ['Заголовок колонки контактов', 'Контакты', 'cg_footer_settings', 'text', ''],
        'cg_footer_copyright' => ['Текст копирайта', 'Цветочный город', 'cg_footer_settings', 'text', ''],
        'cg_footer_legal' => ['Юридический текст', 'Политика конфиденциальности · Публичная оферта', 'cg_footer_settings', 'text', ''],
    ];

    foreach ($fields as $id => $field) {
        $type = $field[3];
        $sanitize = $type === 'textarea' ? 'sanitize_textarea_field' : ($type === 'url' ? 'esc_url_raw' : 'sanitize_text_field');

        if (!$wp_customize->get_setting($id)) {
            $wp_customize->add_setting($id, [
                'default' => $field[1],
                'sanitize_callback' => $sanitize,
            ]);
        }

        if ($wp_customize->get_control($id)) {
            $wp_customize->remove_control($id);
        }

        $wp_customize->add_control($id, [
            'label' => $field[0],
            'description' => $field[4],
            'section' => $field[2],
            'type' => $type,
        ]);
    }

    $wp_customize->add_setting('cg_show_topbar', [
        'default' => true,
        'sanitize_callback' => 'cg_sanitize_checkbox',
    ]);
    $wp_customize->add_control('cg_show_topbar', [
        'label' => 'Показывать верхнюю панель',
        'section' => 'cg_header_settings',
        'type' => 'checkbox',
    ]);
}
add_action('customize_register', 'cg_site_customize', 20);

/**
 * Add a dedicated mobile image for the homepage About block.
 *
 * The existing cg_about_image remains the desktop/tablet source. This keeps
 * current sites backwards compatible while allowing a separate crop for phones.
 */
function cg_home_about_responsive_image_customize($wp_customize) {
    if (!$wp_customize instanceof WP_Customize_Manager) return;

    $desktop_control = $wp_customize->get_control('cg_about_image');
    if ($desktop_control) {
        $desktop_control->label = 'Изображение для компьютера';
        $desktop_control->description = 'Рекомендуемый размер: около 1600 × 1200 px. Используется на компьютерах и планшетах.';
    }

    if (!$wp_customize->get_setting('cg_about_image_mobile')) {
        $wp_customize->add_setting('cg_about_image_mobile', [
            'default' => '',
            'sanitize_callback' => 'esc_url_raw',
        ]);
    }

    if (!$wp_customize->get_control('cg_about_image_mobile')) {
        $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'cg_about_image_mobile', [
            'label' => 'Изображение для телефона',
            'description' => 'Рекомендуемый размер: около 900 × 1200 px (вертикальное 3:4). Если оставить пустым, на телефоне будет использоваться обычное изображение.',
            'section' => 'cg_home_about',
        ]));
    }
}
add_action('customize_register', 'cg_home_about_responsive_image_customize', 30);

/** Use the phone-specific About image below the theme's mobile breakpoint. */
function cg_home_about_responsive_image_css() {
    if (!is_front_page()) return;

    $mobile_image = trim((string) get_theme_mod('cg_about_image_mobile', ''));
    if ($mobile_image === '') return;

    $mobile_image = esc_url_raw($mobile_image);
    if ($mobile_image === '') return;

    $css = '@media(max-width:640px){.cg-about__image{background-image:linear-gradient(180deg,rgba(44,40,39,.02),rgba(44,40,39,.16)),url("' . esc_url($mobile_image) . '")!important;background-position:center center!important;background-size:cover!important;background-repeat:no-repeat!important;}}';
    wp_add_inline_style('cg-homepage', $css);
}
add_action('wp_enqueue_scripts', 'cg_home_about_responsive_image_css', 20);

/** Replace only untouched placeholders with the actual store contacts. */
function cg_upgrade_store_contact_defaults() {
    $version = '2';
    if (get_option('cg_store_contact_defaults_version') === $version) return;

    $replacements = [
        'cg_phone' => [
            'stale' => ['', '+7 (900) 000-00-00'],
            'value' => '+7 (930) 411-98-55',
        ],
        'cg_address' => [
            'stale' => ['', 'Нововоронеж, Воронежская область'],
            'value' => 'Нововоронеж, ул. Победы, 1Б',
        ],
        'cg_worktime' => [
            'stale' => ['', 'Ежедневно с 09:00 до 21:00'],
            'value' => 'Ежедневно с 07:00 до 21:00',
        ],
        'cg_delivery_url' => [
            'stale' => [''],
            'value' => home_url('/delivery/'),
        ],
        'cg_contacts_url' => [
            'stale' => [''],
            'value' => home_url('/contacts/'),
        ],
        'cg_address_url' => [
            'stale' => [''],
            'value' => home_url('/contacts/#cg-contacts-map-title'),
        ],
        'cg_whatsapp_url' => [
            'stale' => [''],
            'value' => 'https://wa.me/79304119855',
        ],
        'cg_vk_url' => [
            'stale' => [''],
            'value' => 'https://vk.com/floralscity',
        ],
        'cg_instagram_url' => [
            'stale' => [''],
            'value' => 'https://www.instagram.com/florals_city_nv/',
        ],
    ];

    foreach ($replacements as $key => $replacement) {
        $current = (string) get_theme_mod($key, '');
        if (in_array($current, $replacement['stale'], true)) {
            set_theme_mod($key, $replacement['value']);
        }
    }

    update_option('cg_store_contact_defaults_version', $version, false);
}
add_action('after_setup_theme', 'cg_upgrade_store_contact_defaults', 31);