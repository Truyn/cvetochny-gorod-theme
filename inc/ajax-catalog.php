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
    $row = $wpdb->get_row("SELECT FLOOR(MIN(min_price)) AS min_price, CEIL(MAX(max_price)) AS max_price FROM {$table} WHERE min_price IS NOT NULL AND max_price IS NOT NULL", ARRAY_A);
    $min = isset($row['min_price']) ? max(0, (int) $row['min_price']) : 0;
    $max = isset($row['max_price']) ? max($min + 100, (int) $row['max_price']) : 10000;
    return [$min, $max, $max > 50000 ? 500 : 100];
}

function cg_catalog_current_category_slug() {
    return sanitize_title(cg_catalog_get_request('product_cat'));
}

function cg_catalog_form_action() {
    return cg_catalog_url();
}

function cg_catalog_preserved_query_args() {
    $args = [];
    foreach (['product_cat','min_price','max_price','stock_status','on_sale','cg_orderby'] as $key) {
        $value = cg_catalog_get_request($key);
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
    return cg_catalog_url_with_args($args);
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

    $category = cg_catalog_current_category_slug();
    if ($category) {
        $args['tax_query'][] = ['taxonomy'=>'product_cat','field'=>'slug','terms'=>[$category],'include_children'=>true];
    }

    foreach (wc_get_attribute_taxonomies() as $attribute) {
        $taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);
        if (!taxonomy_exists($taxonomy)) continue;
        $key = 'filter_' . sanitize_title($attribute->attribute_name);
        $values = cg_catalog_get_array_request($key);
        if ($values) $args['tax_query'][] = ['taxonomy'=>$taxonomy,'field'=>'slug','terms'=>$values,'operator'=>'IN'];
    }

    if (cg_catalog_get_request('stock_status') === 'instock') {
        $args['meta_query'][] = ['key'=>'_stock_status','value'=>'instock'];
    }
    if (cg_catalog_get_request('on_sale') === '1') {
        $sale_ids = wc_get_product_ids_on_sale();
        $args['post__in'] = $sale_ids ?: [0];
    }
    if ($min > $catalog_min || $max < $catalog_max) {
        $args['meta_query'][] = ['key'=>'_price','value'=>[$min,$max],'compare'=>'BETWEEN','type'=>'DECIMAL(10,2)'];
    }

    switch (cg_catalog_get_request('cg_orderby', 'menu_order')) {
        case 'date': $args['orderby']='date'; $args['order']='DESC'; break;
        case 'price': $args['meta_key']='_price'; $args['orderby']='meta_value_num'; $args['order']='ASC'; break;
        case 'price-desc': $args['meta_key']='_price'; $args['orderby']='meta_value_num'; $args['order']='DESC'; break;
        case 'popularity': $args['meta_key']='total_sales'; $args['orderby']='meta_value_num'; $args['order']='DESC'; break;
        case 'rating': $args['meta_key']='_wc_average_rating'; $args['orderby']='meta_value_num'; $args['order']='DESC'; break;
        default: $args['orderby']=['menu_order'=>'ASC','title'=>'ASC'];
    }

    if (count($args['tax_query']) === 1) unset($args['tax_query']);
    return $args;
}

function cg_catalog_render_category_options($terms, $current, $depth = 0) {
    foreach ($terms as $term) {
        if ($term->slug === 'uncategorized') continue;
        echo '<label class="cg-catalog-category-option'.($current === $term->slug ? ' is-active' : '').'">';
        echo '<input type="radio" name="product_cat" value="'.esc_attr($term->slug).'"'.checked($current, $term->slug, false).'>';
        echo '<span style="--cg-depth:'.esc_attr($depth).'">'.esc_html($term->name).'</span><b>'.esc_html($term->count).'</b></label>';
        $children = get_terms(['taxonomy'=>'product_cat','hide_empty'=>true,'parent'=>$term->term_id,'orderby'=>'name']);
        if (!is_wp_error($children) && $children) cg_catalog_render_category_options($children, $current, $depth + 1);
    }
}

function cg_catalog_sidebar() {
    [$catalog_min, $catalog_max, $step] = cg_catalog_price_bounds();
    $selected_min = max($catalog_min, (int) cg_catalog_get_request('min_price', $catalog_min));
    $selected_max = min($catalog_max, (int) cg_catalog_get_request('max_price', $catalog_max));
    if ($selected_min > $selected_max) [$selected_min, $selected_max] = [$selected_max, $selected_min];
    $current = cg_catalog_current_category_slug();
    $terms = get_terms(['taxonomy'=>'product_cat','hide_empty'=>true,'parent'=>0,'orderby'=>'name']);

    echo '<button class="cg-catalog-filter-toggle" type="button" aria-expanded="false" aria-controls="cg-catalog-sidebar">Фильтры и категории</button>';
    echo '<aside id="cg-catalog-sidebar" class="cg-catalog-sidebar" aria-label="Фильтры каталога">';
    echo '<div class="cg-catalog-sidebar__heading"><span>Каталог</span><h2>Фильтры</h2></div>';
    echo '<form class="cg-catalog-filter-form" method="get" action="'.esc_url(cg_catalog_url()).'" data-cg-catalog-form>';

    echo '<details class="cg-filter-group" open><summary>Категории <span></span></summary><div class="cg-filter-group__body cg-catalog-category-list">';
    echo '<label class="cg-catalog-category-option'.($current === '' ? ' is-active' : '').'"><input type="radio" name="product_cat" value=""'.checked($current, '', false).'><span>Все букеты</span></label>';
    if (!is_wp_error($terms)) cg_catalog_render_category_options($terms, $current);
    echo '</div></details>';

    echo '<details class="cg-filter-group" open><summary>Цена <span></span></summary><div class="cg-filter-group__body cg-catalog-price-filter">';
    echo '<div class="cg-catalog-price-values"><span data-cg-price-min-label>'.wp_strip_all_tags(wc_price($selected_min)).'</span><span data-cg-price-max-label>'.wp_strip_all_tags(wc_price($selected_max)).'</span></div>';
    echo '<div class="cg-catalog-price-slider"><div class="cg-catalog-price-track"></div>';
    echo '<input type="range" class="cg-catalog-range cg-catalog-range--min" min="'.esc_attr($catalog_min).'" max="'.esc_attr($catalog_max).'" step="'.esc_attr($step).'" value="'.esc_attr($selected_min).'" aria-label="Минимальная цена">';
    echo '<input type="range" class="cg-catalog-range cg-catalog-range--max" min="'.esc_attr($catalog_min).'" max="'.esc_attr($catalog_max).'" step="'.esc_attr($step).'" value="'.esc_attr($selected_max).'" aria-label="Максимальная цена">';
    echo '</div><input type="hidden" name="min_price" value="'.esc_attr($selected_min).'"><input type="hidden" name="max_price" value="'.esc_attr($selected_max).'"></div></details>';

    echo '<div class="cg-filter-quick">';
    echo '<label class="cg-catalog-check"><input type="checkbox" name="stock_status" value="instock"'.checked(cg_catalog_get_request('stock_status'), 'instock', false).'><span>Только в наличии</span></label>';
    echo '<label class="cg-catalog-check"><input type="checkbox" name="on_sale" value="1"'.checked(cg_catalog_get_request('on_sale'), '1', false).'><span>Со скидкой</span></label>';
    echo '</div>';

    foreach (wc_get_attribute_taxonomies() as $attribute) {
        $taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);
        if (!taxonomy_exists($taxonomy)) continue;
        $attribute_terms = get_terms(['taxonomy'=>$taxonomy,'hide_empty'=>true,'orderby'=>'name']);
        if (is_wp_error($attribute_terms) || !$attribute_terms) continue;
        $key = 'filter_' . sanitize_title($attribute->attribute_name);
        $selected = cg_catalog_get_array_request($key);
        echo '<details class="cg-filter-group"'.($selected ? ' open' : '').'><summary>'.esc_html($attribute->attribute_label ?: $attribute->attribute_name).' <span></span></summary><div class="cg-filter-group__body cg-catalog-attribute-list">';
        foreach ($attribute_terms as $term) {
            echo '<label class="cg-catalog-check"><input type="checkbox" name="'.esc_attr($key).'[]" value="'.esc_attr($term->slug).'"'.checked(in_array($term->slug, $selected, true), true, false).'><span>'.esc_html($term->name).'</span></label>';
        }
        echo '</div></details>';
    }

    echo '<input type="hidden" name="cg_orderby" value="'.esc_attr(cg_catalog_get_request('cg_orderby', 'menu_order')).'">';
    echo '<div class="cg-catalog-filter-actions"><button type="submit" class="button">Применить</button><a href="'.esc_url(cg_catalog_url()).'" data-cg-reset>Сбросить всё</a></div>';
    echo '</form></aside>';
}

function cg_catalog_toolbar($query) {
    $current = cg_catalog_get_request('cg_orderby', 'menu_order');
    echo '<div class="cg-shop-toolbar"><p class="cg-catalog-result-count">Найдено: '.esc_html((int) $query->found_posts).'</p>';
    echo '<label class="cg-catalog-ordering"><span>Сортировка</span><select name="cg_orderby" data-cg-orderby>';
    $options = ['menu_order'=>'По умолчанию','popularity'=>'По популярности','rating'=>'По рейтингу','date'=>'Сначала новые','price'=>'Цена: по возрастанию','price-desc'=>'Цена: по убыванию'];
    foreach ($options as $value=>$label) echo '<option value="'.esc_attr($value).'"'.selected($current, $value, false).'>'.esc_html($label).'</option>';
    echo '</select></label></div>';
}

function cg_catalog_active_filters() {
    [$catalog_min, $catalog_max] = cg_catalog_price_bounds();
    $min = (int) cg_catalog_get_request('min_price', $catalog_min);
    $max = (int) cg_catalog_get_request('max_price', $catalog_max);
    $chips = [];
    $category = cg_catalog_current_category_slug();
    if ($category) {
        $term = get_term_by('slug', $category, 'product_cat');
        if ($term) $chips[] = ['Категория: '.$term->name, cg_catalog_remove_filter_url('product_cat')];
    }
    if ($min > $catalog_min || $max < $catalog_max) $chips[] = [sprintf('Цена: %s — %s', wp_strip_all_tags(wc_price($min)), wp_strip_all_tags(wc_price($max))), cg_catalog_remove_filter_url('min_price')];
    if (cg_catalog_get_request('stock_status') === 'instock') $chips[] = ['В наличии', cg_catalog_remove_filter_url('stock_status')];
    if (cg_catalog_get_request('on_sale') === '1') $chips[] = ['Со скидкой', cg_catalog_remove_filter_url('on_sale')];
    foreach (wc_get_attribute_taxonomies() as $attribute) {
        $taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);
        $key = 'filter_' . sanitize_title($attribute->attribute_name);
        foreach (cg_catalog_get_array_request($key) as $slug) {
            $term = get_term_by('slug', $slug, $taxonomy);
            if ($term) $chips[] = [($attribute->attribute_label ?: $attribute->attribute_name).': '.$term->name, cg_catalog_remove_filter_url($key, $slug)];
        }
    }
    if (!$chips) return;
    echo '<div class="cg-active-filters" aria-label="Выбранные фильтры"><strong>Вы выбрали:</strong>';
    foreach ($chips as [$label,$url]) echo '<a class="cg-filter-chip" href="'.esc_url($url).'" data-cg-filter-link>'.esc_html($label).'<span aria-hidden="true">×</span></a>';
    echo '<a class="cg-active-filters__clear" href="'.esc_url(cg_catalog_url()).'" data-cg-reset>Очистить все</a></div>';
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
        while ($query->have_posts()) { $query->the_post(); wc_get_template_part('content', 'product'); }
        woocommerce_product_loop_end();
        if ($query->max_num_pages > 1) {
            echo '<nav class="woocommerce-pagination" aria-label="Навигация по товарам">';
            echo wp_kses_post(paginate_links(['base'=>add_query_arg('paged','%#%',cg_catalog_url()),'format'=>'','current'=>$paged,'total'=>$query->max_num_pages,'type'=>'list','add_args'=>cg_catalog_preserved_query_args(),'prev_text'=>'←','next_text'=>'→']));
            echo '</nav>';
        }
    } else {
        echo '<div class="cg-catalog-empty"><h2>Ничего не найдено</h2><p>Попробуйте изменить фильтры или сбросить выбранные параметры.</p></div>';
    }
}

function cg_catalog_ajax_filter() {
    check_ajax_referer('cg_catalog_filter', 'nonce');
    if (!class_exists('WooCommerce')) wp_send_json_error(['message'=>'WooCommerce недоступен'], 503);
    $raw = isset($_POST['filters']) ? wp_unslash($_POST['filters']) : '';
    parse_str($raw, $filters);
    $_GET = is_array($filters) ? $filters : [];
    $paged = max(1, absint($_POST['paged'] ?? 1));
    $query = new WP_Query(cg_catalog_build_query_args($paged));
    ob_start();
    cg_catalog_render_results($query, $paged);
    $html = ob_get_clean();
    wp_reset_postdata();
    wp_send_json_success(['html'=>$html,'url'=>cg_catalog_url_with_args(cg_catalog_preserved_query_args())]);
}
add_action('wp_ajax_cg_catalog_filter', 'cg_catalog_ajax_filter');
add_action('wp_ajax_nopriv_cg_catalog_filter', 'cg_catalog_ajax_filter');

function cg_homepage_regression_fixes_assets() {
    if (!is_front_page()) return;
    $file = get_template_directory() . '/assets/css/homepage-fixes.css';
    wp_enqueue_style('cg-homepage-fixes', get_template_directory_uri() . '/assets/css/homepage-fixes.css', ['cg-homepage'], file_exists($file) ? filemtime($file) : wp_get_theme()->get('Version'));
}
add_action('wp_enqueue_scripts', 'cg_homepage_regression_fixes_assets', 30);