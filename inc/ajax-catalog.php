<?php
/**
 * AJAX catalog filtering.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

function cg_catalog_request_value($key, $default = '') {
    return isset($_GET[$key]) ? sanitize_text_field(wp_unslash($_GET[$key])) : $default;
}

function cg_catalog_price_meta_query($min_price, $max_price) {
    $min_price = max(0, (float) $min_price);
    $max_price = max(0, (float) $max_price);
    if ($min_price <= 0 && $max_price <= 0) return [];

    if (function_exists('wc_get_min_max_price_meta_query')) {
        return wc_get_min_max_price_meta_query([
            'min_price' => $min_price > 0 ? $min_price : '',
            'max_price' => $max_price > 0 ? $max_price : '',
        ]);
    }

    $filter = ['key'=>'_price','type'=>'DECIMAL(10,2)'];
    if ($min_price > 0 && $max_price > 0) {
        $filter['value'] = [$min_price, $max_price];
        $filter['compare'] = 'BETWEEN';
    } elseif ($min_price > 0) {
        $filter['value'] = $min_price;
        $filter['compare'] = '>=';
    } else {
        $filter['value'] = $max_price;
        $filter['compare'] = '<=';
    }
    return [$filter];
}

function cg_catalog_top_filters() {
    if (!(is_shop() || is_product_taxonomy())) return;

    $current = cg_catalog_request_value('cg_category');
    if (!$current && is_product_category()) {
        $term = get_queried_object();
        if ($term && !is_wp_error($term)) $current = $term->slug;
    }

    $min_price = cg_catalog_request_value('cg_min_price');
    $max_price = cg_catalog_request_value('cg_max_price');
    $in_stock  = cg_catalog_request_value('cg_in_stock');
    $on_sale   = cg_catalog_request_value('cg_on_sale');
    $shop_id   = function_exists('wc_get_page_id') ? wc_get_page_id('shop') : 0;
    $terms = get_terms(['taxonomy'=>'product_cat','hide_empty'=>true,'parent'=>0,'orderby'=>'name']);

    echo '<button class="cg-modern-filters__mobile-toggle" type="button" aria-expanded="false" aria-controls="cg-modern-filters">Фильтры и категории</button>';
    echo '<aside id="cg-modern-filters" class="cg-modern-filters" aria-label="Фильтры каталога">';
    echo '<div class="cg-modern-filters__head"><span>Удобный подбор</span><h2>Фильтры</h2></div>';
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
    echo '<label class="cg-filter-field"><span>Категория</span><select name="cg_category"><option value="">Все категории</option>';
    $all_terms = get_terms(['taxonomy'=>'product_cat','hide_empty'=>true,'orderby'=>'name']);
    if (!is_wp_error($all_terms)) {
        foreach ($all_terms as $term) {
            echo '<option value="'.esc_attr($term->slug).'"'.selected($current, $term->slug, false).'>'.esc_html($term->name).'</option>';
        }
    }
    echo '</select></label>';
    echo '<div class="cg-filter-prices">';
    echo '<label class="cg-filter-field"><span>Цена от</span><input type="number" min="0" step="100" name="cg_min_price" value="'.esc_attr($min_price).'" placeholder="0 ₽"></label>';
    echo '<label class="cg-filter-field"><span>Цена до</span><input type="number" min="0" step="100" name="cg_max_price" value="'.esc_attr($max_price).'" placeholder="10 000 ₽"></label>';
    echo '</div>';
    echo '<label class="cg-filter-check"><input type="checkbox" name="cg_in_stock" value="1"'.checked($in_stock, '1', false).'><span>Только в наличии</span></label>';
    echo '<label class="cg-filter-check"><input type="checkbox" name="cg_on_sale" value="1"'.checked($on_sale, '1', false).'><span>Со скидкой</span></label>';
    echo '<div class="cg-filter-actions"><button class="button cg-filter-apply" type="submit">Применить</button><a class="cg-filter-reset" href="'.esc_url(cg_catalog_url()).'">Сбросить</a></div>';
    echo '</form></aside>';
}
add_action('woocommerce_before_shop_loop', 'cg_catalog_top_filters', 10);

function cg_catalog_apply_get_filters($query) {
    if (is_admin() || !$query->is_main_query() || !(is_shop() || is_product_taxonomy())) return;

    $category  = cg_catalog_request_value('cg_category');
    $min_price = (float) cg_catalog_request_value('cg_min_price', 0);
    $max_price = (float) cg_catalog_request_value('cg_max_price', 0);
    $in_stock  = cg_catalog_request_value('cg_in_stock');
    $on_sale   = cg_catalog_request_value('cg_on_sale');
    $tax_query = (array) $query->get('tax_query');
    $meta_query = (array) $query->get('meta_query');

    if ($category) $tax_query[] = ['taxonomy'=>'product_cat','field'=>'slug','terms'=>$category];
    $meta_query = array_merge($meta_query, cg_catalog_price_meta_query($min_price, $max_price));
    if ($in_stock === '1') $meta_query[] = ['key'=>'_stock_status','value'=>'instock','compare'=>'='];
    if ($on_sale === '1') $query->set('post__in', wc_get_product_ids_on_sale() ?: [0]);

    if ($tax_query) $query->set('tax_query', $tax_query);
    if ($meta_query) $query->set('meta_query', $meta_query);
}
add_action('pre_get_posts', 'cg_catalog_apply_get_filters', 20);

function cg_ajax_catalog_render_products($query_args) {
    $query = new WP_Query($query_args);
    ob_start();
    if ($query->have_posts()) {
        woocommerce_product_loop_start();
        while ($query->have_posts()) {
            $query->the_post();
            wc_get_template_part('content', 'product');
        }
        woocommerce_product_loop_end();
    } else {
        echo '<div class="cg-catalog-empty"><h3>Ничего не найдено</h3><p>Попробуйте изменить фильтры или выбрать другую категорию.</p></div>';
    }
    wp_reset_postdata();
    return ob_get_clean();
}

function cg_ajax_catalog_filter() {
    check_ajax_referer('cg_ajax_catalog', 'nonce');

    $paged = max(1, absint($_POST['page'] ?? 1));
    $category = sanitize_title(wp_unslash($_POST['category'] ?? ''));
    $min_price = isset($_POST['min_price']) ? floatval($_POST['min_price']) : 0;
    $max_price = isset($_POST['max_price']) ? floatval($_POST['max_price']) : 0;
    $orderby = sanitize_key(wp_unslash($_POST['orderby'] ?? 'menu_order'));
    $in_stock = !empty($_POST['in_stock']);
    $on_sale = !empty($_POST['on_sale']);

    $args = [
        'post_type'=>'product','post_status'=>'publish','posts_per_page'=>12,'paged'=>$paged,
        'tax_query'=>WC()->query->get_tax_query(),'meta_query'=>WC()->query->get_meta_query(),
    ];
    if ($category) $args['tax_query'][] = ['taxonomy'=>'product_cat','field'=>'slug','terms'=>$category];
    $args['meta_query'] = array_merge($args['meta_query'], cg_catalog_price_meta_query($min_price, $max_price));
    if ($in_stock) $args['meta_query'][] = ['key'=>'_stock_status','value'=>'instock','compare'=>'='];
    if ($on_sale) $args['post__in'] = wc_get_product_ids_on_sale() ?: [0];

    switch ($orderby) {
        case 'price': $args['meta_key']='_price';$args['orderby']='meta_value_num';$args['order']='ASC';break;
        case 'price-desc': $args['meta_key']='_price';$args['orderby']='meta_value_num';$args['order']='DESC';break;
        case 'date': $args['orderby']='date';$args['order']='DESC';break;
        case 'popularity': $args['meta_key']='total_sales';$args['orderby']='meta_value_num';$args['order']='DESC';break;
        case 'rating': $args['meta_key']='_wc_average_rating';$args['orderby']='meta_value_num';$args['order']='DESC';break;
        default: $args['orderby']=['menu_order'=>'ASC','title'=>'ASC'];
    }

    $query = new WP_Query($args);
    $products = cg_ajax_catalog_render_products($args);
    ob_start();
    if ($query->max_num_pages > 1) {
        echo '<div class="cg-ajax-pagination" data-pages="'.esc_attr($query->max_num_pages).'">';
        for ($i=1;$i<=$query->max_num_pages;$i++) {
            printf('<button type="button" class="cg-page-button%s" data-page="%d">%d</button>',$i===$paged?' is-active':'',$i,$i);
        }
        echo '</div>';
    }
    $pagination = ob_get_clean();
    wp_send_json_success(['products'=>$products,'pagination'=>$pagination,'found'=>(int)$query->found_posts]);
}
add_action('wp_ajax_cg_filter_products', 'cg_ajax_catalog_filter');
add_action('wp_ajax_nopriv_cg_filter_products', 'cg_ajax_catalog_filter');

function cg_homepage_regression_fixes_assets() {
    if (!is_front_page()) return;
    $file = get_template_directory() . '/assets/css/homepage-fixes.css';
    wp_enqueue_style(
        'cg-homepage-fixes',
        get_template_directory_uri() . '/assets/css/homepage-fixes.css',
        ['cg-homepage'],
        file_exists($file) ? filemtime($file) : wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'cg_homepage_regression_fixes_assets', 30);
