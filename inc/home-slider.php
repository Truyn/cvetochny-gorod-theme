<?php
/**
 * Homepage slider settings and helpers.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

function cg_slider_defaults() {
    return [
        1 => [
            'eyebrow' => 'Цветы с доставкой',
            'title' => 'Дарите эмоции вместе с цветами',
            'text' => 'Свежие цветы, стильные букеты и быстрая доставка по Нововоронежу и Воронежской области.',
            'button' => 'Смотреть каталог',
            'url' => cg_catalog_url(),
        ],
        2 => [
            'eyebrow' => 'Авторская флористика',
            'title' => 'Букеты, созданные специально для вас',
            'text' => 'Соберём композицию под повод, настроение и бюджет. Перед доставкой отправим фотографию готового букета.',
            'button' => 'Авторские букеты',
            'url' => home_url('/product-category/avtorskie/'),
        ],
        3 => [
            'eyebrow' => 'Доставка в день заказа',
            'title' => 'Красивый подарок точно ко времени',
            'text' => 'Доставляем по Нововоронежу и Воронежской области. Добавим открытку с вашим текстом бесплатно.',
            'button' => 'Выбрать букет',
            'url' => cg_catalog_url(),
        ],
    ];
}

function cg_register_slider_customizer($wp_customize) {
    $wp_customize->add_section('cg_home_slider', [
        'title' => 'Слайдер главной',
        'description' => 'Настройте изображения, тексты и кнопки трёх слайдов главной страницы.',
        'priority' => 29,
    ]);

    $defaults = cg_slider_defaults();
    foreach ($defaults as $number => $slide) {
        $prefix = 'cg_slide_' . $number . '_';

        $wp_customize->add_setting($prefix . 'enabled', [
            'default' => true,
            'sanitize_callback' => 'wp_validate_boolean',
        ]);
        $wp_customize->add_control($prefix . 'enabled', [
            'label' => 'Показывать слайд ' . $number,
            'section' => 'cg_home_slider',
            'type' => 'checkbox',
        ]);

        $fields = [
            'eyebrow' => ['Надзаголовок', $slide['eyebrow'], 'text', 'sanitize_text_field'],
            'title' => ['Заголовок', $slide['title'], 'text', 'sanitize_text_field'],
            'text' => ['Описание', $slide['text'], 'textarea', 'sanitize_textarea_field'],
            'button' => ['Текст кнопки', $slide['button'], 'text', 'sanitize_text_field'],
            'url' => ['Ссылка кнопки', $slide['url'], 'url', 'esc_url_raw'],
        ];

        foreach ($fields as $key => $field) {
            $wp_customize->add_setting($prefix . $key, [
                'default' => $field[1],
                'sanitize_callback' => $field[3],
            ]);
            $wp_customize->add_control($prefix . $key, [
                'label' => 'Слайд ' . $number . ': ' . $field[0],
                'section' => 'cg_home_slider',
                'type' => $field[2],
            ]);
        }

        $wp_customize->add_setting($prefix . 'image', [
            'default' => '',
            'sanitize_callback' => 'esc_url_raw',
        ]);
        $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, $prefix . 'image', [
            'label' => 'Слайд ' . $number . ': изображение',
            'section' => 'cg_home_slider',
        ]));
    }
}
add_action('customize_register', 'cg_register_slider_customizer');

function cg_get_home_slides() {
    $slides = [];
    foreach (cg_slider_defaults() as $number => $defaults) {
        if (!get_theme_mod('cg_slide_' . $number . '_enabled', true)) continue;
        $prefix = 'cg_slide_' . $number . '_';
        $slides[] = [
            'number' => $number,
            'eyebrow' => get_theme_mod($prefix . 'eyebrow', $defaults['eyebrow']),
            'title' => get_theme_mod($prefix . 'title', $defaults['title']),
            'text' => get_theme_mod($prefix . 'text', $defaults['text']),
            'button' => get_theme_mod($prefix . 'button', $defaults['button']),
            'url' => get_theme_mod($prefix . 'url', $defaults['url']),
            'image' => get_theme_mod($prefix . 'image', ''),
        ];
    }
    return $slides;
}

require_once get_template_directory() . '/inc/home-sections.php';
