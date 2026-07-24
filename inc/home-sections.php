<?php
/**
 * Customizer settings for homepage sections.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

function cg_sanitize_checkbox($checked) {
    return (bool) $checked;
}

function cg_home_sections_customize($wp_customize) {
    $sections = [
        'cg_home_benefits' => ['Преимущества', 32],
        'cg_home_categories' => ['Категории товаров', 33],
        'cg_home_products' => ['Популярные букеты', 34],
        'cg_home_promo' => ['Акционный баннер', 35],
        'cg_home_about' => ['Блок «О нас»', 36],
        'cg_home_newsletter' => ['Подписка', 37],
    ];

    foreach ($sections as $id => $data) {
        $wp_customize->add_section($id, [
            'title' => $data[0],
            'priority' => $data[1],
        ]);
    }

    $add_checkbox = function($id, $label, $section, $default = true) use ($wp_customize) {
        $wp_customize->add_setting($id, [
            'default' => $default,
            'sanitize_callback' => 'cg_sanitize_checkbox',
        ]);
        $wp_customize->add_control($id, [
            'label' => $label,
            'section' => $section,
            'type' => 'checkbox',
        ]);
    };

    $add_text = function($id, $label, $section, $default = '', $type = 'text') use ($wp_customize) {
        $sanitize = $type === 'textarea' ? 'sanitize_textarea_field' : ($type === 'url' ? 'esc_url_raw' : 'sanitize_text_field');
        $wp_customize->add_setting($id, [
            'default' => $default,
            'sanitize_callback' => $sanitize,
        ]);
        $wp_customize->add_control($id, [
            'label' => $label,
            'section' => $section,
            'type' => $type,
        ]);
    };

    $add_checkbox('cg_benefits_enabled', 'Показывать блок преимуществ', 'cg_home_benefits');
    $benefits = [
        ['🌷', 'Свежие цветы', 'Ежедневные поставки'],
        ['🚚', 'Быстрая доставка', 'По городу и области'],
        ['📷', 'Фото перед отправкой', 'Покажем готовый букет'],
        ['💌', 'Открытка бесплатно', 'Добавим ваши слова'],
    ];
    foreach ($benefits as $index => $benefit) {
        $n = $index + 1;
        $add_text("cg_benefit_{$n}_icon", "Преимущество {$n}: иконка", 'cg_home_benefits', $benefit[0]);
        $add_text("cg_benefit_{$n}_title", "Преимущество {$n}: заголовок", 'cg_home_benefits', $benefit[1]);
        $add_text("cg_benefit_{$n}_text", "Преимущество {$n}: описание", 'cg_home_benefits', $benefit[2]);
    }

    $add_checkbox('cg_categories_enabled', 'Показывать категории', 'cg_home_categories');
    $add_text('cg_categories_eyebrow', 'Надзаголовок', 'cg_home_categories', 'Каталог');
    $add_text('cg_categories_title', 'Заголовок', 'cg_home_categories', 'Цветы для любого повода');
    $add_text('cg_categories_text', 'Описание', 'cg_home_categories', 'Выберите подходящую категорию — товары автоматически подгружаются из WooCommerce.', 'textarea');
    $add_text('cg_categories_items', 'Категории: название|slug, по одной в строке', 'cg_home_categories', "Свадебные|svadebnye\nСладкие|sladkie\nАвторские|avtorskie\nLux|lux\nДо 2000|do-2000\nДо 5000|do-5000\nДо 10000|do-10000\nВсе букеты|", 'textarea');

    $add_checkbox('cg_products_enabled', 'Показывать товары', 'cg_home_products');
    $add_text('cg_products_eyebrow', 'Надзаголовок', 'cg_home_products', 'Выбор покупателей');
    $add_text('cg_products_title', 'Заголовок', 'cg_home_products', 'Популярные букеты');
    $add_text('cg_products_text', 'Описание', 'cg_home_products', 'Хиты продаж и свежие композиции.', 'textarea');
    $add_text('cg_products_count', 'Количество товаров', 'cg_home_products', '8');
    $wp_customize->add_setting('cg_products_orderby', ['default' => 'popularity', 'sanitize_callback' => 'sanitize_key']);
    $wp_customize->add_control('cg_products_orderby', [
        'label' => 'Какие товары показывать',
        'section' => 'cg_home_products',
        'type' => 'select',
        'choices' => [
            'popularity' => 'Популярные',
            'date' => 'Новые',
            'rating' => 'По рейтингу',
            'rand' => 'Случайные',
        ],
    ]);

    $add_checkbox('cg_promo_enabled', 'Показывать акционный баннер', 'cg_home_promo');
    $add_text('cg_promo_eyebrow', 'Надзаголовок', 'cg_home_promo', 'Особенное предложение');
    $add_text('cg_promo_title', 'Заголовок', 'cg_home_promo', 'Букет недели со скидкой 15%');
    $add_text('cg_promo_text', 'Описание', 'cg_home_promo', 'Каждую неделю мы выбираем одну особенную композицию и предлагаем её по приятной цене. Количество ограничено.', 'textarea');
    $add_text('cg_promo_button', 'Текст кнопки', 'cg_home_promo', 'Смотреть предложение');
    $add_text('cg_promo_url', 'Ссылка кнопки', 'cg_home_promo', '', 'url');
    $wp_customize->add_setting('cg_promo_image', ['sanitize_callback' => 'esc_url_raw']);
    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'cg_promo_image', [
        'label' => 'Изображение баннера',
        'section' => 'cg_home_promo',
    ]));

    $add_checkbox('cg_about_enabled', 'Показывать блок «О нас»', 'cg_home_about');
    $add_text('cg_about_eyebrow', 'Надзаголовок', 'cg_home_about', 'О нас');
    $add_text('cg_about_title', 'Заголовок', 'cg_home_about', 'Мы создаём букеты, которые говорят за вас');
    $add_text('cg_about_text', 'Описание', 'cg_home_about', 'Каждый букет — это забота, вдохновение и внимание к деталям. Мы бережно собираем композиции и доставляем их по Нововоронежу и Воронежской области.', 'textarea');
    $add_text('cg_about_button', 'Текст кнопки', 'cg_home_about', 'Подробнее о нас');
    $add_text('cg_about_url', 'Ссылка кнопки', 'cg_home_about', home_url('/about/'), 'url');
    $wp_customize->add_setting('cg_about_image', ['sanitize_callback' => 'esc_url_raw']);
    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'cg_about_image', [
        'label' => 'Изображение',
        'section' => 'cg_home_about',
    ]));

    $add_checkbox('cg_newsletter_enabled', 'Показывать блок подписки', 'cg_home_newsletter');
    $add_text('cg_newsletter_title', 'Заголовок', 'cg_home_newsletter', 'Скидка 10% на первый заказ');
    $add_text('cg_newsletter_text', 'Описание', 'cg_home_newsletter', 'Оставьте e-mail и получите промокод.', 'textarea');
    $add_text('cg_newsletter_shortcode', 'Шорткод формы', 'cg_home_newsletter', '', 'text');
}
add_action('customize_register', 'cg_home_sections_customize');

function cg_get_home_categories() {
    $raw = get_theme_mod('cg_categories_items', "Свадебные|svadebnye\nСладкие|sladkie\nАвторские|avtorskie\nLux|lux\nДо 2000|do-2000\nДо 5000|do-5000\nДо 10000|do-10000\nВсе букеты|");
    $items = [];
    foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
        if (!trim($line)) continue;
        $parts = array_map('trim', explode('|', $line, 2));
        $items[] = [
            'name' => sanitize_text_field($parts[0]),
            'slug' => isset($parts[1]) ? sanitize_title($parts[1]) : '',
        ];
    }
    return array_slice($items, 0, 12);
}

/**
 * Convert legacy hard-coded category paths into the actual WooCommerce term URL.
 * This respects both plain query links and pretty permalink settings.
 */
function cg_resolve_home_product_category_url($url, $path, $orig_scheme, $blog_id) {
    if (!is_string($path) || !taxonomy_exists('product_cat')) {
        return $url;
    }

    if (!preg_match('#^/?product-category/([^/]+)/?$#', $path, $matches)) {
        return $url;
    }

    static $resolving = false;
    if ($resolving) {
        return $url;
    }

    $term = get_term_by('slug', sanitize_title($matches[1]), 'product_cat');
    if (!$term || is_wp_error($term)) {
        return $url;
    }

    $resolving = true;
    $term_url = get_term_link($term);
    $resolving = false;

    return is_wp_error($term_url) ? $url : $term_url;
}
add_filter('home_url', 'cg_resolve_home_product_category_url', 10, 4);
