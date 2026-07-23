<?php
/**
 * AJAX catalog filtering.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/** Render product loop HTML for AJAX responses. */
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

/** AJAX product filtering endpoint. */
function cg_ajax_catalog_filter() {
    check_ajax_referer('cg_ajax_catalog', 'nonce');

    $paged = max(1, absint($_POST['page'] ?? 1));
    $category = sanitize_title(wp_unslash($_POST['category'] ?? ''));
    $min_price = isset($_POST['min_price']) ? floatval($_POST['min_price']) : 0;
    $max_price = isset($_POST['max_price']) ? floatval($_POST['max_price']) : 0;
    $orderby = sanitize_key(wp_unslash($_POST['orderby'] ?? 'menu_order'));

    $args = [
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => 12,
        'paged' => $paged,
        'tax_query' => WC()->query->get_tax_query(),
        'meta_query' => WC()->query->get_meta_query(),
    ];

    if ($category) {
        $args['tax_query'][] = [
            'taxonomy' => 'product_cat',
            'field' => 'slug',
            'terms' => $category,
        ];
    }

    if ($min_price > 0 || $max_price > 0) {
        $price_filter = [
            'key' => '_price',
            'type' => 'NUMERIC',
        ];
        if ($min_price > 0 && $max_price > 0) {
            $price_filter['value'] = [$min_price, $max_price];
            $price_filter['compare'] = 'BETWEEN';
        } elseif ($min_price > 0) {
            $price_filter['value'] = $min_price;
            $price_filter['compare'] = '>=';
        } else {
            $price_filter['value'] = $max_price;
            $price_filter['compare'] = '<=';
        }
        $args['meta_query'][] = $price_filter;
    }

    switch ($orderby) {
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
        case 'date':
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
            break;
        case 'popularity':
            $args['meta_key'] = 'total_sales';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
            break;
        default:
            $args['orderby'] = ['menu_order' => 'ASC', 'title' => 'ASC'];
    }

    $query = new WP_Query($args);
    $products = cg_ajax_catalog_render_products($args);

    ob_start();
    if ($query->max_num_pages > 1) {
        echo '<div class="cg-ajax-pagination" data-pages="' . esc_attr($query->max_num_pages) . '">';
        for ($i = 1; $i <= $query->max_num_pages; $i++) {
            printf(
                '<button type="button" class="cg-page-button%s" data-page="%d">%d</button>',
                $i === $paged ? ' is-active' : '',
                $i,
                $i
            );
        }
        echo '</div>';
    }
    $pagination = ob_get_clean();

    wp_send_json_success([
        'products' => $products,
        'pagination' => $pagination,
        'found' => (int) $query->found_posts,
    ]);
}
add_action('wp_ajax_cg_filter_products', 'cg_ajax_catalog_filter');
add_action('wp_ajax_nopriv_cg_filter_products', 'cg_ajax_catalog_filter');
