<?php
/**
 * Layout controls for the homepage "Наши букеты в жизни" gallery.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/** Keep the requested gallery item count between one and six. */
function cg_sanitize_home_gallery_count($value) {
    return max(1, min(6, absint($value)));
}

/** Sanitize select values against a fixed list. */
function cg_sanitize_home_gallery_choice($value, $setting) {
    $control = $setting->manager->get_control($setting->id);
    $choices = $control && is_array($control->choices) ? $control->choices : [];

    return array_key_exists($value, $choices)
        ? $value
        : $setting->default;
}

/** Add clear controls for gallery quantity, layout, scale and cropping. */
function cg_home_gallery_layout_customize($wp_customize) {
    if (!$wp_customize->get_section('cg_home_instagram')) return;

    if (!$wp_customize->get_setting('cg_instagram_count')) {
        $wp_customize->add_setting('cg_instagram_count', [
            'default' => 6,
            'sanitize_callback' => 'cg_sanitize_home_gallery_count',
        ]);
    }
    $wp_customize->add_control('cg_instagram_count', [
        'label' => 'Количество фотографий',
        'description' => 'Выберите, сколько загруженных фотографий показывать на главной странице.',
        'section' => 'cg_home_instagram',
        'type' => 'number',
        'input_attrs' => [
            'min' => 1,
            'max' => 6,
            'step' => 1,
        ],
        'priority' => 20,
    ]);

    if (!$wp_customize->get_setting('cg_instagram_layout')) {
        $wp_customize->add_setting('cg_instagram_layout', [
            'default' => 'equal',
            'sanitize_callback' => 'cg_sanitize_home_gallery_choice',
        ]);
    }
    $wp_customize->add_control('cg_instagram_layout', [
        'label' => 'Расположение фотографий',
        'description' => 'В ровной сетке все фотографии одинаковые. В мозаике первая фотография заметно крупнее остальных.',
        'section' => 'cg_home_instagram',
        'type' => 'select',
        'choices' => [
            'equal' => 'Ровная сетка',
            'mosaic' => 'Мозаика — первое фото крупное',
        ],
        'priority' => 21,
    ]);

    if (!$wp_customize->get_setting('cg_instagram_columns')) {
        $wp_customize->add_setting('cg_instagram_columns', [
            'default' => '3',
            'sanitize_callback' => 'cg_sanitize_home_gallery_choice',
        ]);
    }
    $wp_customize->add_control('cg_instagram_columns', [
        'label' => 'Масштаб фотографий в ровной сетке',
        'description' => 'Чем меньше фотографий в строке, тем крупнее каждая карточка. Эта настройка не влияет на режим «Мозаика».',
        'section' => 'cg_home_instagram',
        'type' => 'select',
        'choices' => [
            '2' => 'Крупные — 2 фотографии в строке',
            '3' => 'Средние — 3 фотографии в строке',
            '4' => 'Компактные — 4 фотографии в строке',
        ],
        'priority' => 22,
    ]);

    if (!$wp_customize->get_setting('cg_instagram_fit')) {
        $wp_customize->add_setting('cg_instagram_fit', [
            'default' => 'cover',
            'sanitize_callback' => 'cg_sanitize_home_gallery_choice',
        ]);
    }
    $wp_customize->add_control('cg_instagram_fit', [
        'label' => 'Как показывать фотографию внутри карточки',
        'description' => 'Заполнение выглядит аккуратнее, но может немного обрезать края. Полное фото не обрезается, однако вокруг него могут появиться свободные поля.',
        'section' => 'cg_home_instagram',
        'type' => 'select',
        'choices' => [
            'cover' => 'Заполнять карточку с небольшой обрезкой',
            'contain' => 'Показывать фотографию полностью',
        ],
        'priority' => 23,
    ]);

    $priorities = [
        'cg_instagram_enabled' => 5,
        'cg_instagram_eyebrow' => 6,
        'cg_instagram_title' => 7,
        'cg_instagram_text' => 8,
        'cg_instagram_gallery_url' => 9,
    ];
    foreach ($priorities as $control_id => $priority) {
        $control = $wp_customize->get_control($control_id);
        if ($control) $control->priority = $priority;
    }

    for ($i = 1; $i <= 6; $i++) {
        $control = $wp_customize->get_control('cg_instagram_image_' . $i);
        if ($control) {
            $control->priority = 30 + $i;
            if ($i === 1) {
                $control->description = 'В режиме «Мозаика» эта фотография будет самой крупной. В ровной сетке все фотографии одинакового размера.';
            }
        }
    }

    $products_orderby = $wp_customize->get_control('cg_products_orderby');
    if ($products_orderby) {
        $products_orderby->description = 'Популярные — по количеству продаж; новые — по дате публикации; рейтинг — по средней оценке; случайные — новый порядок при каждой загрузке. Поскольку отзывы о товарах на сайте отключены, сортировка по рейтингу обычно не даёт полезного результата.';
    }
}
add_action('customize_register', 'cg_home_gallery_layout_customize', 40);

/** Apply the selected gallery controls without duplicating homepage markup. */
function cg_home_gallery_layout_assets() {
    if (!is_front_page()) return;

    $count = cg_sanitize_home_gallery_count(get_theme_mod('cg_instagram_count', 6));
    $layout = get_theme_mod('cg_instagram_layout', 'equal');
    $layout = in_array($layout, ['equal', 'mosaic'], true) ? $layout : 'equal';
    $columns = (int) get_theme_mod('cg_instagram_columns', '3');
    $columns = in_array($columns, [2, 3, 4], true) ? $columns : 3;
    $fit = get_theme_mod('cg_instagram_fit', 'cover');
    $fit = in_array($fit, ['cover', 'contain'], true) ? $fit : 'cover';

    $css = '.cg-instagram-grid .cg-instagram-card:nth-child(n+' . ($count + 1) . '){display:none!important;}';

    if ($layout === 'equal') {
        $tablet_columns = min(2, $columns);
        $css .= '.cg-instagram-grid{grid-template-columns:repeat(' . $columns . ',minmax(0,1fr));grid-auto-rows:auto;align-items:stretch;}';
        $css .= '.cg-instagram-grid .cg-instagram-card{grid-column:auto!important;grid-row:auto!important;min-height:0;aspect-ratio:1/1;}';
        $css .= '@media(max-width:980px){.cg-instagram-grid{grid-template-columns:repeat(' . $tablet_columns . ',minmax(0,1fr));grid-auto-rows:auto;}.cg-instagram-grid .cg-instagram-card{grid-column:auto!important;grid-row:auto!important;aspect-ratio:1/1;}}';
        $css .= '@media(max-width:640px){.cg-instagram-grid{grid-template-columns:1fr;}.cg-instagram-grid .cg-instagram-card{aspect-ratio:1/1;}}';
    }

    if ($fit === 'contain') {
        $css .= '.cg-instagram-card__image{background-size:contain!important;background-repeat:no-repeat!important;background-color:#f1e7e3;}';
    }

    wp_add_inline_style('cg-homepage', $css);
}
add_action('wp_enqueue_scripts', 'cg_home_gallery_layout_assets', 35);
