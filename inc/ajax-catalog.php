<?php
/**
 * Stable catalog sidebar and filters built on WooCommerce public APIs.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

function cg_catalog_get_request($key, $default = '') {
    return isset($_GET[$key]) ? sanitize_text_field(wp_unslash($_GET[$key])) : $default;
}

function cg_catalog_price_bounds() {
    global $wpdb;

    if (!class_exists('WooCommerce')) return [0, 10000, 100];

    $table = $wpdb->wc_product_meta_lookup;
    $row = $wpdb->get_row("SELECT FLOOR(MIN(min_price)) AS min_price, CEIL(MAX(max_price)) AS max_price FROM {$table} WHERE min_price IS NOT NULL AND max_price IS NOT NULL", ARRAY_A);
    $min = isset($row['min_price']) ? max(0, (int) $row['min_price']) : 0;
    $max = isset($row['max_price']) ? max($min + 100, (int) $row['max_price']) : 10000;
    $step = $max > 50000 ? 500 : 100;

    return [$min, $max, $step];
}

function cg_catalog_current_category_slug() {
    $slug = sanitize_title(cg_catalog_get_request('product_cat'));
    if (!$slug && is_product_category()) {
        $term = get_queried_object();
        if ($term && !is_wp_error($term)) $slug = $term->slug;
    }
    return $slug;
}

function cg_catalog_form_action() {
    if (is_product_category()) {
        $term = get_queried_object();
        if ($term && !is_wp_error($term)) {
            $url = get_term_link($term);
            if (!is_wp_error($url)) return $url;
        }
    }
    return cg_catalog_url();
}

function cg_catalog_sidebar() {
    if (!(is_shop() || is_product_taxonomy())) return;

    [$catalog_min, $catalog_max, $step] = cg_catalog_price_bounds();
    $selected_min = max($catalog_min, (int) cg_catalog_get_request('min_price', $catalog_min));
    $selected_max = min($catalog_max, (int) cg_catalog_get_request('max_price', $catalog_max));
    if ($selected_min > $selected_max) [$selected_min, $selected_max] = [$selected_max, $selected_min];

    $current_category = cg_catalog_current_category_slug();
    $terms = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => true,
        'parent' => 0,
        'orderby' => 'name',
    ]);

    echo '<button class="cg-catalog-filter-toggle" type="button" aria-expanded="false" aria-controls="cg-catalog-sidebar">Фильтры и категории</button>';
    echo '<aside id="cg-catalog-sidebar" class="cg-catalog-sidebar" aria-label="Фильтры каталога">';
    echo '<div class="cg-catalog-sidebar__heading"><span>Каталог</span><h2>Фильтры</h2></div>';

    echo '<nav class="cg-catalog-categories" aria-label="Категории товаров"><h3>Категории</h3><ul>';
    echo '<li'.($current_category === '' ? ' class="is-active"' : '').'><a href="'.esc_url(cg_catalog_url()).'"><span>Все букеты</span></a></li>';
    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            $url = get_term_link($term);
            if (is_wp_error($url)) continue;
            echo '<li'.($current_category === $term->slug ? ' class="is-active"' : '').'><a href="'.esc_url($url).'"><span>'.esc_html($term->name).'</span><b>'.esc_html($term->count).'</b></a></li>';
        }
    }
    echo '</ul></nav>';

    echo '<form class="cg-catalog-filter-form" method="get" action="'.esc_url(cg_catalog_form_action()).'">';
    if (is_shop()) {
        $shop_id = wc_get_page_id('shop');
        if ($shop_id > 0 && get_option('permalink_structure') === '') {
            echo '<input type="hidden" name="page_id" value="'.esc_attr($shop_id).'">';
        }
    }

    echo '<section class="cg-catalog-price-filter">';
    echo '<div class="cg-catalog-filter-title">Цена</div>';
    echo '<div class="cg-catalog-price-values"><span data-cg-price-min-label>'.wp_strip_all_tags(wc_price($selected_min)).'</span><span data-cg-price-max-label>'.wp_strip_all_tags(wc_price($selected_max)).'</span></div>';
    echo '<div class="cg-catalog-price-slider">';
    echo '<div class="cg-catalog-price-track"></div>';
    echo '<input type="range" class="cg-catalog-range cg-catalog-range--min" min="'.esc_attr($catalog_min).'" max="'.esc_attr($catalog_max).'" step="'.esc_attr($step).'" value="'.esc_attr($selected_min).'" aria-label="Минимальная цена">';
    echo '<input type="range" class="cg-catalog-range cg-catalog-range--max" min="'.esc_attr($catalog_min).'" max="'.esc_attr($catalog_max).'" step="'.esc_attr($step).'" value="'.esc_attr($selected_max).'" aria-label="Максимальная цена">';
    echo '</div>';
    echo '<input type="hidden" name="min_price" value="'.esc_attr($selected_min).'">';
    echo '<input type="hidden" name="max_price" value="'.esc_attr($selected_max).'">';
    echo '</section>';

    echo '<label class="cg-catalog-check"><input type="checkbox" name="stock_status" value="instock"'.checked(cg_catalog_get_request('stock_status'), 'instock', false).'><span>Только в наличии</span></label>';
    echo '<label class="cg-catalog-check"><input type="checkbox" name="on_sale" value="1"'.checked(cg_catalog_get_request('on_sale'), '1', false).'><span>Со скидкой</span></label>';

    $orderby = wc_clean(cg_catalog_get_request('orderby'));
    if ($orderby) echo '<input type="hidden" name="orderby" value="'.esc_attr($orderby).'">';

    echo '<div class="cg-catalog-filter-actions"><button type="submit" class="button">Показать товары</button><a href="'.esc_url(cg_catalog_form_action()).'">Сбросить</a></div>';
    echo '</form></aside>';
}

function cg_catalog_apply_extra_filters($query) {
    if (is_admin() || !$query->is_main_query() || !(is_shop() || is_product_taxonomy())) return;

    $meta_query = (array) $query->get('meta_query');

    if (cg_catalog_get_request('stock_status') === 'instock') {
        $meta_query[] = [
            'key' => '_stock_status',
            'value' => 'instock',
            'compare' => '=',
        ];
    }

    if (cg_catalog_get_request('on_sale') === '1') {
        $sale_ids = wc_get_product_ids_on_sale();
        $query->set('post__in', $sale_ids ? $sale_ids : [0]);
    }

    if ($meta_query) $query->set('meta_query', $meta_query);
}
add_action('pre_get_posts', 'cg_catalog_apply_extra_filters', 20);

function cg_catalog_active_filters() {
    if (!(is_shop() || is_product_taxonomy())) return;

    [$catalog_min, $catalog_max] = cg_catalog_price_bounds();
    $min = (int) cg_catalog_get_request('min_price', $catalog_min);
    $max = (int) cg_catalog_get_request('max_price', $catalog_max);
    $chips = [];

    if ($min > $catalog_min || $max < $catalog_max) {
        $chips[] = [
            sprintf('Цена: %s — %s', wp_strip_all_tags(wc_price($min)), wp_strip_all_tags(wc_price($max))),
            remove_query_arg(['min_price', 'max_price']),
        ];
    }
    if (cg_catalog_get_request('stock_status') === 'instock') {
        $chips[] = ['В наличии', remove_query_arg('stock_status')];
    }
    if (cg_catalog_get_request('on_sale') === '1') {
        $chips[] = ['Со скидкой', remove_query_arg('on_sale')];
    }

    if (!$chips) return;

    echo '<div class="cg-active-filters" aria-label="Выбранные фильтры">';
    foreach ($chips as [$label, $url]) {
        echo '<a class="cg-filter-chip" href="'.esc_url($url).'">'.esc_html($label).'<span aria-hidden="true">×</span></a>';
    }
    echo '<a class="cg-active-filters__clear" href="'.esc_url(cg_catalog_form_action()).'">Очистить все</a></div>';
}
add_action('woocommerce_before_shop_loop', 'cg_catalog_active_filters', 14);

function cg_catalog_ordering_preserve_filters($fields) {
    foreach (['min_price', 'max_price', 'stock_status', 'on_sale'] as $key) {
        if (isset($_GET[$key])) {
            $fields[$key] = wc_clean(wp_unslash($_GET[$key]));
        }
    }
    return $fields;
}
add_filter('woocommerce_catalog_orderby', 'cg_catalog_ordering_preserve_filters');

function cg_homepage_regression_fixes_assets() {
    if (!is_front_page()) return;
    $file = get_template_directory() . '/assets/css/homepage-fixes.css';
    wp_enqueue_style('cg-homepage-fixes', get_template_directory_uri() . '/assets/css/homepage-fixes.css', ['cg-homepage'], file_exists($file) ? filemtime($file) : wp_get_theme()->get('Version'));
}
add_action('wp_enqueue_scripts', 'cg_homepage_regression_fixes_assets', 30);