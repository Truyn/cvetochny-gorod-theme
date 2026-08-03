<?php
/**
 * Server-rendered AJAX catalog for WooCommerce.
 *
 * @package Cvetochny_Gorod
 */
if (!defined('ABSPATH')) exit;

function cg_catalog_get_request($key, $default = '') {
    if (!isset($_GET[$key])) return $default;
    $value = wp_unslash($_GET[$key]);
    return is_array($value) ? $default : sanitize_text_field($value);
}

function cg_catalog_get_array_request($key) {
    if (!isset($_GET[$key])) return [];
    $value = wp_unslash($_GET[$key]);
    $value = is_array($value) ? $value : [$value];
    return array_values(array_unique(array_filter(array_map('sanitize_title', $value))));
}

function cg_catalog_price_bounds() {
    global $wpdb;
    if (!class_exists('WooCommerce')) return [0, 10000, 100];

    $table = $wpdb->wc_product_meta_lookup;
    $row = $wpdb->get_row(
        "SELECT FLOOR(MIN(min_price)) AS min_price, CEIL(MAX(max_price)) AS max_price FROM {$table} WHERE min_price IS NOT NULL AND max_price IS NOT NULL",
        ARRAY_A
    );

    $min = isset($row['min_price']) ? max(0, (int) $row['min_price']) : 0;
    $max = isset($row['max_price']) ? max($min + 100, (int) $row['max_price']) : 10000;

    return [$min, $max, $max > 50000 ? 500 : 100];
}

function cg_catalog_current_category_slug() {
    $requested = sanitize_title(cg_catalog_get_request('product_cat'));
    if ($requested !== '') return $requested;

    if (!wp_doing_ajax() && function_exists('is_product_category') && is_product_category()) {
        $term = get_queried_object();
        if ($term instanceof WP_Term && $term->taxonomy === 'product_cat') {
            return sanitize_title($term->slug);
        }
    }

    return '';
}

function cg_catalog_form_action() {
    return cg_catalog_url();
}

function cg_catalog_preserved_query_args() {
    $args = [];
    foreach (['product_cat', 'catalog_search', 'min_price', 'max_price', 'stock_status', 'on_sale', 'cg_orderby'] as $key) {
        $value = $key === 'product_cat' ? cg_catalog_current_category_slug() : cg_catalog_get_request($key);
        if ($value !== '') $args[$key] = $value;
    }

    foreach (wc_get_attribute_taxonomies() as $attribute) {
        $key = 'filter_' . sanitize_title($attribute->attribute_name);
        $values = cg_catalog_get_array_request($key);
        if ($values) $args[$key] = $values;
    }

    return $args;
}

function cg_catalog_url_with_args($args = []) {
    return $args ? add_query_arg($args, cg_catalog_url()) : cg_catalog_url();
}

function cg_catalog_remove_filter_url($key, $value = null) {
    $args = cg_catalog_preserved_query_args();

    if ($value === null || !isset($args[$key]) || !is_array($args[$key])) {
        unset($args[$key]);
    } else {
        $args[$key] = array_values(array_diff($args[$key], [$value]));
        if (!$args[$key]) unset($args[$key]);
    }

    if ($key === 'min_price' || $key === 'max_price') {
        unset($args['min_price'], $args['max_price']);
    }

    return cg_catalog_url_with_args($args);
}

function cg_catalog_active_filter_count() {
    [$catalog_min, $catalog_max] = cg_catalog_price_bounds();
    $count = 0;

    if (cg_catalog_current_category_slug() !== '') $count++;
    if (cg_catalog_get_request('catalog_search') !== '') $count++;
    if ((int) cg_catalog_get_request('min_price', $catalog_min) > $catalog_min || (int) cg_catalog_get_request('max_price', $catalog_max) < $catalog_max) $count++;
    if (cg_catalog_get_request('stock_status') === 'instock') $count++;
    if (cg_catalog_get_request('on_sale') === '1') $count++;

    foreach (wc_get_attribute_taxonomies() as $attribute) {
        $count += count(cg_catalog_get_array_request('filter_' . sanitize_title($attribute->attribute_name)));
    }

    return $count;
}

function cg_catalog_build_query_args($paged = 1) {
    [$catalog_min, $catalog_max] = cg_catalog_price_bounds();
    $min = max($catalog_min, (float) cg_catalog_get_request('min_price', $catalog_min));
    $max = min($catalog_max, (float) cg_catalog_get_request('max_price', $catalog_max));
    if ($min > $max) [$min, $max] = [$max, $min];

    $args = [
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => 12,
        'paged' => max(1, (int) $paged),
        'ignore_sticky_posts' => true,
        'tax_query' => ['relation' => 'AND'],
        'meta_query' => WC()->query->get_meta_query(),
    ];

    $search = cg_catalog_get_request('catalog_search');
    if ($search !== '') $args['s'] = $search;

    $category = cg_catalog_current_category_slug();
    if ($category) {
        $args['tax_query'][] = [
            'taxonomy' => 'product_cat',
            'field' => 'slug',
            'terms' => [$category],
            'include_children' => true,
        ];
    }

    foreach (wc_get_attribute_taxonomies() as $attribute) {
        $taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);
        if (!taxonomy_exists($taxonomy)) continue;

        $key = 'filter_' . sanitize_title($attribute->attribute_name);
        $values = cg_catalog_get_array_request($key);
        if ($values) {
            $args['tax_query'][] = [
                'taxonomy' => $taxonomy,
                'field' => 'slug',
                'terms' => $values,
                'operator' => 'IN',
            ];
        }
    }

    if (cg_catalog_get_request('stock_status') === 'instock') {
        $args['meta_query'][] = ['key' => '_stock_status', 'value' => 'instock'];
    }

    if (cg_catalog_get_request('on_sale') === '1') {
        $sale_ids = wc_get_product_ids_on_sale();
        $args['post__in'] = $sale_ids ?: [0];
    }

    if ($min > $catalog_min || $max < $catalog_max) {
        $args['meta_query'][] = [
            'key' => '_price',
            'value' => [$min, $max],
            'compare' => 'BETWEEN',
            'type' => 'DECIMAL(10,2)',
        ];
    }

    switch (cg_catalog_get_request('cg_orderby', 'menu_order')) {
        case 'date':
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            break;
        case 'price':
            $args['meta_key'] = '_price';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'ASC';
            break;
        case 'price-desc':
            $args['meta_key'] = '_price';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
            break;
        case 'popularity':
            $args['meta_key'] = 'total_sales';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
            break;
        case 'rating':
            $args['meta_key'] = '_wc_average_rating';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
            break;
        default:
            $args['orderby'] = ['menu_order' => 'ASC', 'title' => 'ASC'];
    }

    if (count($args['tax_query']) === 1) unset($args['tax_query']);
    return $args;
}

function cg_catalog_excluded_category_id() {
    return (int) get_option('default_product_cat', 0);
}

function cg_catalog_category_contains_current($term_id, $current) {
    if (!$current) return false;
    $term = get_term_by('slug', $current, 'product_cat');
    if (!$term) return false;

    return (int) $term->term_id === (int) $term_id || term_is_ancestor_of($term_id, $term->term_id, 'product_cat');
}

function cg_catalog_render_category_options($terms, $current, $depth = 0) {
    $excluded = cg_catalog_excluded_category_id();

    foreach ($terms as $term) {
        if ((int) $term->term_id === $excluded) continue;

        $children = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'parent' => $term->term_id,
            'orderby' => 'name',
            'exclude' => $excluded ? [$excluded] : [],
        ]);
        $has_children = !is_wp_error($children) && !empty($children);
        $open = $has_children && cg_catalog_category_contains_current($term->term_id, $current);

        echo '<div class="cg-category-node' . ($open ? ' is-open' : '') . '" style="--cg-depth:' . esc_attr($depth) . '">';
        echo '<div class="cg-category-row">';
        echo '<label class="cg-catalog-category-option' . ($current === $term->slug ? ' is-active' : '') . '"><input type="radio" name="product_cat" value="' . esc_attr($term->slug) . '"' . checked($current, $term->slug, false) . '><span>' . esc_html($term->name) . '</span><b>' . esc_html($term->count) . '</b></label>';
        if ($has_children) {
            echo '<button type="button" class="cg-category-toggle" aria-expanded="' . ($open ? 'true' : 'false') . '" aria-label="Показать подкатегории ' . esc_attr($term->name) . '"><span aria-hidden="true"></span></button>';
        }
        echo '</div>';

        if ($has_children) {
            echo '<div class="cg-category-children"' . ($open ? '' : ' hidden') . '>';
            cg_catalog_render_category_options($children, $current, $depth + 1);
            echo '</div>';
        }
        echo '</div>';
    }
}

function cg_catalog_price_presets($catalog_min, $catalog_max) {
    $presets = [
        ['До 3 000 ₽', $catalog_min, min(3000, $catalog_max)],
        ['3 000–5 000 ₽', max(3000, $catalog_min), min(5000, $catalog_max)],
        ['5 000–10 000 ₽', max(5000, $catalog_min), min(10000, $catalog_max)],
        ['От 10 000 ₽', max(10000, $catalog_min), $catalog_max],
    ];

    foreach ($presets as [$label, $min, $max]) {
        if ($min > $max || ($min === $catalog_min && $max === $catalog_max)) continue;
        echo '<button type="button" class="cg-price-preset" data-cg-price-preset data-min="' . esc_attr($min) . '" data-max="' . esc_attr($max) . '">' . esc_html($label) . '</button>';
    }
}

function cg_catalog_sidebar() {
    [$catalog_min, $catalog_max, $step] = cg_catalog_price_bounds();
    $selected_min = max($catalog_min, (int) cg_catalog_get_request('min_price', $catalog_min));
    $selected_max = min($catalog_max, (int) cg_catalog_get_request('max_price', $catalog_max));
    if ($selected_min > $selected_max) [$selected_min, $selected_max] = [$selected_max, $selected_min];

    $current = cg_catalog_current_category_slug();
    $excluded = cg_catalog_excluded_category_id();
    $active_count = cg_catalog_active_filter_count();
    $terms = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => true,
        'parent' => 0,
        'orderby' => 'name',
        'exclude' => $excluded ? [$excluded] : [],
    ]);

    echo '<button class="cg-catalog-filter-toggle" type="button" aria-expanded="false" aria-controls="cg-catalog-sidebar"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h10M18 7h2M4 17h2M10 17h10M14 4v6M7 14v6"/></svg><span>Фильтры</span><b data-cg-filter-count' . ($active_count ? '' : ' hidden') . '>' . esc_html($active_count) . '</b></button>';
    echo '<div class="cg-catalog-filter-backdrop" data-cg-filter-close hidden></div>';
    echo '<aside id="cg-catalog-sidebar" class="cg-catalog-sidebar" aria-label="Фильтры каталога">';
    echo '<div class="cg-catalog-sidebar__heading"><div><span>Каталог</span><h2>Подобрать букет</h2></div><button type="button" class="cg-catalog-sidebar__close" data-cg-filter-close aria-label="Закрыть фильтры">×</button></div>';
    echo '<form class="cg-catalog-filter-form" method="get" action="' . esc_url(cg_catalog_url()) . '" data-cg-catalog-form>';

    echo '<label class="cg-catalog-product-search"><span>Поиск по каталогу</span><span class="cg-catalog-product-search__field"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.6"/><path d="m15.7 15.7 4.3 4.3"/></svg><input type="search" name="catalog_search" value="' . esc_attr(cg_catalog_get_request('catalog_search')) . '" placeholder="Название букета" autocomplete="off"></span></label>';

    echo '<details class="cg-filter-group" open><summary>Категории <span></span></summary><div class="cg-filter-group__body cg-catalog-category-list">';
    echo '<label class="cg-catalog-category-option' . ($current === '' ? ' is-active' : '') . '"><input type="radio" name="product_cat" value=""' . checked($current, '', false) . '><span>Все товары</span></label>';
    if (!is_wp_error($terms)) cg_catalog_render_category_options($terms, $current);
    echo '</div></details>';

    echo '<details class="cg-filter-group" open><summary>Цена <span></span></summary><div class="cg-filter-group__body cg-catalog-price-filter">';
    echo '<div class="cg-catalog-price-values"><span data-cg-price-min-label>' . wp_strip_all_tags(wc_price($selected_min)) . '</span><span data-cg-price-max-label>' . wp_strip_all_tags(wc_price($selected_max)) . '</span></div>';
    echo '<div class="cg-catalog-price-slider"><div class="cg-catalog-price-track"></div><input type="range" class="cg-catalog-range cg-catalog-range--min" min="' . esc_attr($catalog_min) . '" max="' . esc_attr($catalog_max) . '" step="' . esc_attr($step) . '" value="' . esc_attr($selected_min) . '" aria-label="Минимальная цена"><input type="range" class="cg-catalog-range cg-catalog-range--max" min="' . esc_attr($catalog_min) . '" max="' . esc_attr($catalog_max) . '" step="' . esc_attr($step) . '" value="' . esc_attr($selected_max) . '" aria-label="Максимальная цена"></div>';
    echo '<input type="hidden" name="min_price" value="' . esc_attr($selected_min) . '"><input type="hidden" name="max_price" value="' . esc_attr($selected_max) . '">';
    echo '<div class="cg-price-presets" aria-label="Быстрый выбор цены">';
    cg_catalog_price_presets($catalog_min, $catalog_max);
    echo '</div></div></details>';

    echo '<div class="cg-filter-quick"><label class="cg-catalog-check cg-catalog-switch"><input type="checkbox" name="stock_status" value="instock"' . checked(cg_catalog_get_request('stock_status'), 'instock', false) . '><i aria-hidden="true"></i><span>Только в наличии</span></label><label class="cg-catalog-check cg-catalog-switch"><input type="checkbox" name="on_sale" value="1"' . checked(cg_catalog_get_request('on_sale'), '1', false) . '><i aria-hidden="true"></i><span>Со скидкой</span></label></div>';

    $attributes = wc_get_attribute_taxonomies();
    usort($attributes, function ($a, $b) {
        $priority = ['flowers' => 1, 'cvety' => 1, 'occasion' => 2, 'povod' => 2, 'color' => 3, 'cvet' => 3, 'size' => 4, 'razmer' => 4];
        return ($priority[sanitize_title($a->attribute_name)] ?? 99) <=> ($priority[sanitize_title($b->attribute_name)] ?? 99);
    });

    foreach ($attributes as $attribute) {
        $taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);
        if (!taxonomy_exists($taxonomy)) continue;

        $attribute_terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true, 'orderby' => 'name']);
        if (is_wp_error($attribute_terms) || !$attribute_terms) continue;

        $key = 'filter_' . sanitize_title($attribute->attribute_name);
        $selected = cg_catalog_get_array_request($key);
        $search = count($attribute_terms) > 7;
        $label = $attribute->attribute_label ?: $attribute->attribute_name;

        echo '<details class="cg-filter-group"' . ($selected ? ' open' : '') . '><summary>' . esc_html($label) . '<span></span></summary><div class="cg-filter-group__body">';
        if ($search) {
            echo '<input class="cg-filter-search" type="search" placeholder="Найти вариант" aria-label="Поиск по фильтру ' . esc_attr($label) . '">';
        }
        echo '<div class="cg-catalog-attribute-list">';
        foreach ($attribute_terms as $term) {
            echo '<label class="cg-catalog-check" data-cg-filter-item="' . esc_attr(mb_strtolower($term->name)) . '"><input type="checkbox" name="' . esc_attr($key) . '[]" value="' . esc_attr($term->slug) . '"' . checked(in_array($term->slug, $selected, true), true, false) . '><span>' . esc_html($term->name) . '</span><b>' . esc_html($term->count) . '</b></label>';
        }
        echo '</div></div></details>';
    }

    echo '<input type="hidden" name="cg_orderby" value="' . esc_attr(cg_catalog_get_request('cg_orderby', 'menu_order')) . '">';
    echo '<div class="cg-catalog-filter-actions"><button type="submit" class="button"><span data-cg-filter-submit-label>Показать товары</span></button><a href="' . esc_url(cg_catalog_url()) . '" data-cg-reset>Сбросить всё</a></div>';
    echo '</form></aside>';
}

function cg_catalog_toolbar($query) {
    $current = cg_catalog_get_request('cg_orderby', 'menu_order');
    echo '<div class="cg-shop-toolbar"><p class="cg-catalog-result-count">Найдено: <strong data-cg-result-total>' . esc_html((int) $query->found_posts) . '</strong></p><label class="cg-catalog-ordering"><span>Сортировка</span><select name="cg_orderby" data-cg-orderby>';
    foreach ([
        'menu_order' => 'По умолчанию',
        'popularity' => 'По популярности',
        'rating' => 'По рейтингу',
        'date' => 'Сначала новые',
        'price' => 'Цена: по возрастанию',
        'price-desc' => 'Цена: по убыванию',
    ] as $value => $label) {
        echo '<option value="' . esc_attr($value) . '"' . selected($current, $value, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select></label></div>';
}

function cg_catalog_active_filters() {
    [$catalog_min, $catalog_max] = cg_catalog_price_bounds();
    $min = (int) cg_catalog_get_request('min_price', $catalog_min);
    $max = (int) cg_catalog_get_request('max_price', $catalog_max);
    $chips = [];
    $category = cg_catalog_current_category_slug();
    $search = cg_catalog_get_request('catalog_search');

    if ($search !== '') $chips[] = ['Поиск: ' . $search, cg_catalog_remove_filter_url('catalog_search')];
    if ($category) {
        $term = get_term_by('slug', $category, 'product_cat');
        if ($term) $chips[] = ['Категория: ' . $term->name, cg_catalog_remove_filter_url('product_cat')];
    }
    if ($min > $catalog_min || $max < $catalog_max) {
        $chips[] = [sprintf('Цена: %s — %s', wp_strip_all_tags(wc_price($min)), wp_strip_all_tags(wc_price($max))), cg_catalog_remove_filter_url('min_price')];
    }
    if (cg_catalog_get_request('stock_status') === 'instock') $chips[] = ['В наличии', cg_catalog_remove_filter_url('stock_status')];
    if (cg_catalog_get_request('on_sale') === '1') $chips[] = ['Со скидкой', cg_catalog_remove_filter_url('on_sale')];

    foreach (wc_get_attribute_taxonomies() as $attribute) {
        $taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);
        $key = 'filter_' . sanitize_title($attribute->attribute_name);
        foreach (cg_catalog_get_array_request($key) as $slug) {
            $term = get_term_by('slug', $slug, $taxonomy);
            if ($term) $chips[] = [($attribute->attribute_label ?: $attribute->attribute_name) . ': ' . $term->name, cg_catalog_remove_filter_url($key, $slug)];
        }
    }

    if (!$chips) return;

    echo '<div class="cg-active-filters" aria-label="Выбранные фильтры"><strong>Вы выбрали:</strong>';
    foreach ($chips as [$label, $url]) {
        echo '<a class="cg-filter-chip" href="' . esc_url($url) . '" data-cg-filter-link>' . esc_html($label) . '<span aria-hidden="true">×</span></a>';
    }
    echo '<a class="cg-active-filters__clear" href="' . esc_url(cg_catalog_url()) . '" data-cg-reset>Очистить все</a></div>';
}

function cg_catalog_render_results($query, $paged = 1) {
    cg_catalog_toolbar($query);
    cg_catalog_active_filters();

    if ($query->have_posts()) {
        wc_set_loop_prop('total', $query->found_posts);
        wc_set_loop_prop('per_page', $query->get('posts_per_page'));
        wc_set_loop_prop('current_page', $paged);
        wc_set_loop_prop('total_pages', $query->max_num_pages);

        woocommerce_product_loop_start();
        while ($query->have_posts()) {
            $query->the_post();
            wc_get_template_part('content', 'product');
        }
        woocommerce_product_loop_end();

        if ($query->max_num_pages > 1) {
            echo '<nav class="woocommerce-pagination" aria-label="Навигация по товарам">' . wp_kses_post(paginate_links([
                'base' => add_query_arg('paged', '%#%', cg_catalog_url()),
                'format' => '',
                'current' => $paged,
                'total' => $query->max_num_pages,
                'type' => 'list',
                'add_args' => cg_catalog_preserved_query_args(),
                'prev_text' => '←',
                'next_text' => '→',
            ])) . '</nav>';
        }
    } else {
        echo '<div class="cg-catalog-empty"><h2>Ничего не найдено</h2><p>Попробуйте изменить фильтры или сбросить выбранные параметры.</p></div>';
    }
}

function cg_catalog_ajax_filter() {
    check_ajax_referer('cg_catalog_filter', 'nonce');
    if (!class_exists('WooCommerce')) wp_send_json_error(['message' => 'WooCommerce недоступен'], 503);

    $raw = isset($_POST['filters']) ? wp_unslash($_POST['filters']) : '';
    parse_str($raw, $filters);
    $filters = is_array($filters) ? $filters : [];

    // Категория передаётся отдельно, чтобы вложенная сериализация формы
    // не могла потерять значение radio-поля на мобильных браузерах.
    $posted_category = isset($_POST['category']) ? sanitize_title(wp_unslash($_POST['category'])) : '';
    if ($posted_category !== '') {
        $filters['product_cat'] = $posted_category;
    } else {
        unset($filters['product_cat']);
    }

    $_GET = $filters;
    $paged = max(1, absint($_POST['paged'] ?? 1));
    $query = new WP_Query(cg_catalog_build_query_args($paged));

    ob_start();
    cg_catalog_render_results($query, $paged);
    $html = ob_get_clean();
    $total = (int) $query->found_posts;
    wp_reset_postdata();

    $url_args = cg_catalog_preserved_query_args();
    if ($paged > 1) $url_args['paged'] = $paged;

    wp_send_json_success([
        'html' => $html,
        'url' => cg_catalog_url_with_args($url_args),
        'total' => $total,
        'category' => cg_catalog_current_category_slug(),
        'filterCount' => cg_catalog_active_filter_count(),
    ]);
}
add_action('wp_ajax_cg_catalog_filter', 'cg_catalog_ajax_filter');
add_action('wp_ajax_nopriv_cg_catalog_filter', 'cg_catalog_ajax_filter');

function cg_homepage_regression_fixes_assets() {
    if (!is_front_page()) return;
    $file = get_template_directory() . '/assets/css/homepage-fixes.css';
    wp_enqueue_style('cg-homepage-fixes', get_template_directory_uri() . '/assets/css/homepage-fixes.css', ['cg-homepage'], file_exists($file) ? filemtime($file) : wp_get_theme()->get('Version'));
}
add_action('wp_enqueue_scripts', 'cg_homepage_regression_fixes_assets', 30);
