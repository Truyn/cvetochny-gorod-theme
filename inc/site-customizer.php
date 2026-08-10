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
 * Add dedicated desktop/mobile image controls for the homepage About block.
 *
 * The portrait 3:4 format now matches the visual layout of the redesigned block
 * and can be positioned separately on desktop and phones without editing files.
 */
function cg_home_about_responsive_image_customize($wp_customize) {
    if (!$wp_customize instanceof WP_Customize_Manager) return;

    $desktop_control = $wp_customize->get_control('cg_about_image');
    if ($desktop_control) {
        $desktop_control->label = 'Изображение для компьютера';
        $desktop_control->description = 'Для нового блока лучше вертикальное фото 3:4, примерно 1200 × 1600 px. Фото девушки с букетом подходит без предварительной обрезки.';
        $desktop_control->priority = 20;
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
            'description' => 'Опционально: вертикальное 3:4, примерно 900 × 1200 px. Можно оставить пустым — тогда телефон использует основное фото.',
            'section' => 'cg_home_about',
            'priority' => 22,
        ]));
    }

    if (!$wp_customize->get_setting('cg_about_image_position')) {
        $wp_customize->add_setting('cg_about_image_position', [
            'default' => 'center',
            'sanitize_callback' => 'sanitize_key',
        ]);
    }
    $wp_customize->add_control('cg_about_image_position', [
        'label' => 'Положение фото на компьютере',
        'description' => 'Если важная часть кадра обрезается, сместите изображение вверх или вниз.',
        'section' => 'cg_home_about',
        'type' => 'select',
        'choices' => [
            'top' => 'Выше — акцент на лице',
            'center' => 'По центру',
            'bottom' => 'Ниже — акцент на букете',
        ],
        'priority' => 21,
    ]);

    if (!$wp_customize->get_setting('cg_about_image_mobile_position')) {
        $wp_customize->add_setting('cg_about_image_mobile_position', [
            'default' => 'center',
            'sanitize_callback' => 'sanitize_key',
        ]);
    }
    $wp_customize->add_control('cg_about_image_mobile_position', [
        'label' => 'Положение фото на телефоне',
        'description' => 'Работает и когда используется отдельное мобильное фото, и когда телефон показывает основное.',
        'section' => 'cg_home_about',
        'type' => 'select',
        'choices' => [
            'top' => 'Выше — акцент на лице',
            'center' => 'По центру',
            'bottom' => 'Ниже — акцент на букете',
        ],
        'priority' => 23,
    ]);
}
add_action('customize_register', 'cg_home_about_responsive_image_customize', 30);

/** Load the approved premium visual treatment for the homepage About block. */
function cg_home_about_premium_assets() {
    if (!is_front_page()) return;

    $style_path = get_template_directory() . '/assets/css/home-about-premium.css';
    wp_enqueue_style(
        'cg-home-about-premium',
        get_template_directory_uri() . '/assets/css/home-about-premium.css',
        ['cg-homepage'],
        file_exists($style_path) ? filemtime($style_path) : wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'cg_home_about_premium_assets', 25);

/** Use the optional phone image and independently chosen crop positions. */
function cg_home_about_responsive_image_css() {
    if (!is_front_page()) return;

    $positions = [
        'top' => 'center 22%',
        'center' => 'center center',
        'bottom' => 'center 78%',
    ];

    $desktop_key = sanitize_key((string) get_theme_mod('cg_about_image_position', 'center'));
    $mobile_key = sanitize_key((string) get_theme_mod('cg_about_image_mobile_position', 'center'));
    $desktop_position = $positions[$desktop_key] ?? $positions['center'];
    $mobile_position = $positions[$mobile_key] ?? $positions['center'];

    $css = '.cg-about__image{background-position:' . $desktop_position . '!important;}';

    $mobile_image = trim((string) get_theme_mod('cg_about_image_mobile', ''));
    $mobile_image = $mobile_image !== '' ? esc_url_raw($mobile_image) : '';

    $css .= '@media(max-width:640px){.cg-about__image{background-position:' . $mobile_position . '!important;';
    if ($mobile_image !== '') {
        $css .= 'background-image:url("' . esc_url($mobile_image) . '")!important;';
    }
    $css .= 'background-size:cover!important;background-repeat:no-repeat!important;}}';

    wp_add_inline_style('cg-home-about-premium', $css);
}
add_action('wp_enqueue_scripts', 'cg_home_about_responsive_image_css', 30);

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
