<?php
/**
 * AJAX catalog filtering and product quick view.
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

/** Add quick-view button to product cards. */
function cg_loop_quick_view_button() {
    global $product;
    if (!$product) return;

    echo '<button type="button" class="cg-quick-view-button" data-product-id="' . esc_attr($product->get_id()) . '">Быстрый просмотр</button>';
}
add_action('woocommerce_after_shop_loop_item', 'cg_loop_quick_view_button', 12);

/** AJAX quick-view endpoint. */
function cg_ajax_quick_view() {
    check_ajax_referer('cg_ajax_catalog', 'nonce');

    $product_id = absint($_POST['product_id'] ?? 0);
    $product = wc_get_product($product_id);
    if (!$product || !$product->is_visible()) {
        wp_send_json_error(['message' => 'Товар не найден.'], 404);
    }

    $image = $product->get_image('woocommerce_single');
    $description = $product->get_short_description();
    if (!$description) {
        $description = wp_trim_words(wp_strip_all_tags($product->get_description()), 28);
    }

    ob_start();
    ?>
    <article class="cg-quick-view-card">
        <div class="cg-quick-view-media"><?php echo wp_kses_post($image); ?></div>
        <div class="cg-quick-view-copy">
            <span class="cg-quick-view-eyebrow"><?php echo $product->is_in_stock() ? 'В наличии' : 'Нет в наличии'; ?></span>
            <h2><?php echo esc_html($product->get_name()); ?></h2>
            <div class="cg-quick-view-price"><?php echo wp_kses_post($product->get_price_html()); ?></div>
            <?php if ($description): ?><div class="cg-quick-view-description"><?php echo wp_kses_post(wpautop($description)); ?></div><?php endif; ?>
            <div class="cg-quick-view-actions">
                <?php if ($product->is_type('simple') && $product->is_purchasable() && $product->is_in_stock()): ?>
                    <button type="button" class="button cg-quick-add" data-product-id="<?php echo esc_attr($product_id); ?>">Добавить в корзину</button>
                <?php endif; ?>
                <a class="button button--ghost" href="<?php echo esc_url($product->get_permalink()); ?>">Подробнее</a>
            </div>
        </div>
    </article>
    <?php
    wp_send_json_success(['html' => ob_get_clean()]);
}
add_action('wp_ajax_cg_quick_view', 'cg_ajax_quick_view');
add_action('wp_ajax_nopriv_cg_quick_view', 'cg_ajax_quick_view');
