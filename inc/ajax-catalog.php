<?php
/**
 * Catalog filters.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

function cg_catalog_request_value($key, $default = '') {
    return isset($_GET[$key]) ? sanitize_text_field(wp_unslash($_GET[$key])) : $default;
}

/** Return the real catalog price boundaries from WooCommerce lookup data. */
function cg_catalog_price_bounds() {
    global $wpdb;

    $table = $wpdb->wc_product_meta_lookup;
    $row = $wpdb->get_row("SELECT FLOOR(MIN(min_price)) AS min_price, CEIL(MAX(max_price)) AS max_price FROM {$table} WHERE min_price IS NOT NULL AND max_price IS NOT NULL", ARRAY_A);

    $min = isset($row['min_price']) ? max(0, (int) $row['min_price']) : 0;
    $max = isset($row['max_price']) ? max($min + 100, (int) $row['max_price']) : 10000;
    $step = $max > 50000 ? 500 : 100;

    return [$min, $max, $step];
}

/** Render filters. They are moved into the real sidebar by catalog JS. */
function cg_catalog_top_filters() {
    if (!(is_shop() || is_product_taxonomy())) return;

    $current = sanitize_title(cg_catalog_request_value('cg_category'));
    if (!$current && is_product_category()) {
        $term = get_queried_object();
        if ($term && !is_wp_error($term)) $current = $term->slug;
    }

    [$catalog_min, $catalog_max, $step] = cg_catalog_price_bounds();
    $min_price = max($catalog_min, (int) cg_catalog_request_value('cg_min_price', $catalog_min));
    $max_price = min($catalog_max, (int) cg_catalog_request_value('cg_max_price', $catalog_max));
    if ($min_price > $max_price) [$min_price, $max_price] = [$max_price, $min_price];

    $in_stock = cg_catalog_request_value('cg_in_stock');
    $on_sale  = cg_catalog_request_value('cg_on_sale');
    $shop_id  = function_exists('wc_get_page_id') ? wc_get_page_id('shop') : 0;
    $terms = get_terms(['taxonomy'=>'product_cat','hide_empty'=>true,'parent'=>0,'orderby'=>'name']);

    echo '<button class="cg-modern-filters__mobile-toggle" type="button" aria-expanded="false" aria-controls="cg-modern-filters">Фильтры и категории</button>';
    echo '<aside id="cg-modern-filters" class="cg-modern-filters" aria-label="Фильтры каталога">';
    echo '<div class="cg-modern-filters__head"><span>Каталог</span><h2>Фильтры</h2></div>';

    echo '<nav class="cg-category-navigation" aria-label="Категории товаров"><h3>Категории</h3><ul>';
    echo '<li'.($current === '' ? ' class="is-active"' : '').'><a href="'.esc_url(cg_catalog_url()).'">Все букеты</a></li>';
    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            $term_url = get_term_link($term);
            if (is_wp_error($term_url)) continue;
            echo '<li'.($current === $term->slug ? ' class="is-active"' : '').'><a href="'.esc_url($term_url).'">'.esc_html($term->name).'<span>'.esc_html($term->count).'</span></a></li>';
        }
    }
    echo '</ul></nav>';

    echo '<form class="cg-filter-form" method="get" action="'.esc_url(home_url('/')).'">';
    if ($shop_id > 0) echo '<input type="hidden" name="page_id" value="'.esc_attr($shop_id).'">';
    if ($current) echo '<input type="hidden" name="cg_category" value="'.esc_attr($current).'">';

    echo '<section class="cg-price-filter">';
    echo '<div class="cg-filter-section-title">Цена</div>';
    echo '<div class="cg-price-values"><span><b data-cg-min-output>'.wc_price($min_price).'</b></span><span><b data-cg-max-output>'.wc_price($max_price).'</b></span></div>';
    echo '<div class="cg-price-slider" style="--min-pos:0%;--max-pos:100%">';
    echo '<div class="cg-price-slider__track"></div>';
    echo '<input type="range" class="cg-price-range cg-price-range--min" min="'.esc_attr($catalog_min).'" max="'.esc_attr($catalog_max).'" step="'.esc_attr($step).'" value="'.esc_attr($min_price).'" aria-label="Минимальная цена">';
    echo '<input type="range" class="cg-price-range cg-price-range--max" min="'.esc_attr($catalog_min).'" max="'.esc_attr($catalog_max).'" step="'.esc_attr($step).'" value="'.esc_attr($max_price).'" aria-label="Максимальная цена">';
    echo '</div>';
    echo '<input type="hidden" name="cg_min_price" value="'.esc_attr($min_price).'">';
    echo '<input type="hidden" name="cg_max_price" value="'.esc_attr($max_price).'">';
    echo '</section>';

    echo '<label class="cg-filter-check"><input type="checkbox" name="cg_in_stock" value="1"'.checked($in_stock, '1', false).'><span>Только в наличии</span></label>';
    echo '<label class="cg-filter-check"><input type="checkbox" name="cg_on_sale" value="1"'.checked($on_sale, '1', false).'><span>Со скидкой</span></label>';
    echo '<div class="cg-filter-actions"><button class="button cg-filter-apply" type="submit">Показать товары</button><a class="cg-filter-reset" href="'.esc_url(cg_catalog_url()).'">Сбросить</a></div>';
    echo '</form></aside>';
}
add_action('woocommerce_before_shop_loop', 'cg_catalog_top_filters', 10);

/** Show selected filters above products, like modern catalog filter chips. */
function cg_catalog_active_filters() {
    if (!(is_shop() || is_product_taxonomy())) return;

    [$catalog_min, $catalog_max] = cg_catalog_price_bounds();
    $category = sanitize_title(cg_catalog_request_value('cg_category'));
    $min = (int) cg_catalog_request_value('cg_min_price', $catalog_min);
    $max = (int) cg_catalog_request_value('cg_max_price', $catalog_max);
    $chips = [];

    if ($category) {
        $term = get_term_by('slug', $category, 'product_cat');
        if ($term && !is_wp_error($term)) $chips[] = [esc_html($term->name), remove_query_arg('cg_category')];
    }
    if ($min > $catalog_min || $max < $catalog_max) {
        $chips[] = [sprintf('Цена: %s — %s', wp_strip_all_tags(wc_price($min)), wp_strip_all_tags(wc_price($max))), remove_query_arg(['cg_min_price','cg_max_price'])];
    }
    if (cg_catalog_request_value('cg_in_stock') === '1') $chips[] = ['В наличии', remove_query_arg('cg_in_stock')];
    if (cg_catalog_request_value('cg_on_sale') === '1') $chips[] = ['Со скидкой', remove_query_arg('cg_on_sale')];

    if (!$chips) return;
    echo '<div class="cg-active-filters" aria-label="Выбранные фильтры"><span class="cg-active-filters__icon" aria-hidden="true">⌁</span>';
    foreach ($chips as [$label, $url]) {
        echo '<a class="cg-filter-chip" href="'.esc_url($url).'">'.esc_html($label).'<span aria-hidden="true">×</span></a>';
    }
    echo '<a class="cg-active-filters__clear" href="'.esc_url(cg_catalog_url()).'">Очистить все</a></div>';
}
add_action('woocommerce_before_shop_loop', 'cg_catalog_active_filters', 14);

/** Apply category, stock and sale filters to the normal WooCommerce query. */
function cg_catalog_apply_get_filters($query) {
    if (is_admin() || !$query->is_main_query() || !(is_shop() || is_product_taxonomy())) return;

    $category = sanitize_title(cg_catalog_request_value('cg_category'));
    $tax_query = (array) $query->get('tax_query');
    $meta_query = (array) $query->get('meta_query');

    if ($category) $tax_query[] = ['taxonomy'=>'product_cat','field'=>'slug','terms'=>$category];
    if (cg_catalog_request_value('cg_in_stock') === '1') $meta_query[] = ['key'=>'_stock_status','value'=>'instock','compare'=>'='];
    if (cg_catalog_request_value('cg_on_sale') === '1') $query->set('post__in', wc_get_product_ids_on_sale() ?: [0]);

    if ($tax_query) $query->set('tax_query', $tax_query);
    if ($meta_query) $query->set('meta_query', $meta_query);
}
add_action('pre_get_posts', 'cg_catalog_apply_get_filters', 20);

/** Filter against WooCommerce's lookup table, including variable products. */
function cg_catalog_price_posts_clauses($clauses, $query) {
    if (is_admin() || !$query->is_main_query() || !(is_shop() || is_product_taxonomy())) return $clauses;

    [$catalog_min, $catalog_max] = cg_catalog_price_bounds();
    $min = max($catalog_min, (float) cg_catalog_request_value('cg_min_price', $catalog_min));
    $max = min($catalog_max, (float) cg_catalog_request_value('cg_max_price', $catalog_max));
    if ($min <= $catalog_min && $max >= $catalog_max) return $clauses;
    if ($min > $max) [$min, $max] = [$max, $min];

    global $wpdb;
    $lookup = $wpdb->wc_product_meta_lookup;
    if (strpos($clauses['join'], 'cg_price_lookup') === false) {
        $clauses['join'] .= " INNER JOIN {$lookup} AS cg_price_lookup ON {$wpdb->posts}.ID = cg_price_lookup.product_id ";
    }
    $clauses['where'] .= $wpdb->prepare(' AND cg_price_lookup.max_price >= %f AND cg_price_lookup.min_price <= %f ', $min, $max);
    $clauses['distinct'] = 'DISTINCT';
    return $clauses;
}
add_filter('posts_clauses', 'cg_catalog_price_posts_clauses', 30, 2);

function cg_homepage_regression_fixes_assets() {
    if (!is_front_page()) return;
    $file = get_template_directory() . '/assets/css/homepage-fixes.css';
    wp_enqueue_style('cg-homepage-fixes', get_template_directory_uri() . '/assets/css/homepage-fixes.css', ['cg-homepage'], file_exists($file) ? filemtime($file) : wp_get_theme()->get('Version'));
}
add_action('wp_enqueue_scripts', 'cg_homepage_regression_fixes_assets', 30);
