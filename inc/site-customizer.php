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
        'priority' => 38,
    ]);

    $wp_customize->add_section('cg_footer_settings', [
        'title' => 'Подвал сайта',
        'priority' => 39,
    ]);

    $fields = [
        'cg_phone' => ['Телефон', '+7 (900) 000-00-00', 'cg_header_settings', 'text'],
        'cg_address' => ['Адрес', 'Нововоронеж, Воронежская область', 'cg_header_settings', 'text'],
        'cg_worktime' => ['Режим работы', 'Ежедневно с 09:00 до 21:00', 'cg_header_settings', 'text'],
        'cg_brand_title' => ['Название магазина', 'Цветочный город', 'cg_header_settings', 'text'],
        'cg_brand_subtitle' => ['Подпись под названием', 'магазин цветов', 'cg_header_settings', 'text'],
        'cg_whatsapp_url' => ['Ссылка WhatsApp', '', 'cg_header_settings', 'url'],
        'cg_telegram_url' => ['Ссылка Telegram', '', 'cg_header_settings', 'url'],
        'cg_footer_text' => ['Описание в подвале', 'Букеты и подарки с доставкой по Нововоронежу и Воронежской области.', 'cg_footer_settings', 'textarea'],
        'cg_footer_catalog_title' => ['Заголовок колонки каталога', 'Каталог', 'cg_footer_settings', 'text'],
        'cg_footer_buyers_title' => ['Заголовок колонки покупателям', 'Покупателям', 'cg_footer_settings', 'text'],
        'cg_footer_contacts_title' => ['Заголовок колонки контактов', 'Контакты', 'cg_footer_settings', 'text'],
        'cg_footer_copyright' => ['Текст копирайта', 'Цветочный город', 'cg_footer_settings', 'text'],
        'cg_footer_legal' => ['Юридический текст', 'Политика конфиденциальности · Публичная оферта', 'cg_footer_settings', 'text'],
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
