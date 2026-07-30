<?php
/**
 * Fast AJAX product search for the site header.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/** Load search behavior and presentation on storefront pages. */
function cg_ajax_search_assets() {
    if (!class_exists('WooCommerce')) return;

    $theme_version = wp_get_theme()->get('Version');
    $style_path = get_template_directory() . '/assets/css/ajax-search.css';
    $script_path = get_template_directory() . '/assets/js/ajax-search.js';

    wp_enqueue_style(
        'cg-ajax-search',
        get_template_directory_uri() . '/assets/css/ajax-search.css',
        ['cg-account-search'],
        file_exists($style_path) ? filemtime($style_path) : $theme_version
    );

    wp_enqueue_script(
        'cg-ajax-search',
        get_template_directory_uri() . '/assets/js/ajax-search.js',
        [],
        file_exists($script_path) ? filemtime($script_path) : $theme_version,
        true
    );

    wp_localize_script('cg-ajax-search', 'cgAjaxSearch', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('cg_ajax_product_search'),
        'minChars' => 2,
        'delay' => 260,
        'strings' => [
            'loading' => 'Ищем букеты…',
            'empty' => 'По вашему запросу ничего не найдено.',
            'error' => 'Не удалось выполнить поиск. Попробуйте ещё раз.',
            'allResults' => 'Показать все результаты',
        ],
    ]);
}
add_action('wp_enqueue_scripts', 'cg_ajax_search_assets', 30);

/** IDs of products hidden from catalog search. */
function cg_ajax_search_excluded_visibility_terms() {
    if (!function_exists('wc_get_product_visibility_term_ids')) return [];

    $terms = wc_get_product_visibility_term_ids();
    return !empty($terms['exclude-from-search']) ? [(int) $terms['exclude-from-search']] : [];
}

/** Query product IDs by title/content, SKU and matching product categories. */
function cg_ajax_search_product_ids($search_term, $limit = 8) {
    global $wpdb;

    $limit = max(1, min(12, absint($limit)));
    $ids = [];
    $visibility_terms = cg_ajax_search_excluded_visibility_terms();
    $tax_query = [];

    if ($visibility_terms) {
        $tax_query[] = [
            'taxonomy' => 'product_visibility',
            'field' => 'term_id',
            'terms' => $visibility_terms,
            'operator' => 'NOT IN',
        ];
    }

    $title_query = new WP_Query([
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'fields' => 'ids',
        's' => $search_term,
        'orderby' => 'relevance date',
        'order' => 'DESC',
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
        'tax_query' => $tax_query,
    ]);

    $ids = array_map('absint', $title_query->posts);

    if (count($ids) < $limit) {
        $sku_like = '%' . $wpdb->esc_like($search_term) . '%';
        $sku_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT pm.post_id
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key = '_sku'
               AND pm.meta_value LIKE %s
               AND p.post_type = 'product'
               AND p.post_status = 'publish'
             ORDER BY p.post_date DESC
             LIMIT %d",
            $sku_like,
            $limit
        ));

        $ids = array_merge($ids, array_map('absint', $sku_ids));
    }

    if (count(array_unique($ids)) < $limit) {
        $category_ids = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'search' => $search_term,
            'number' => 5,
            'fields' => 'ids',
        ]);

        if (!is_wp_error($category_ids) && $category_ids) {
            $category_query = new WP_Query([
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => $limit,
                'fields' => 'ids',
                'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
                'no_found_rows' => true,
                'ignore_sticky_posts' => true,
                'tax_query' => array_merge($tax_query, [[
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => array_map('absint', $category_ids),
                ]]),
            ]);

            $ids = array_merge($ids, array_map('absint', $category_query->posts));
        }
    }

    $visible_ids = [];
    foreach (array_values(array_unique(array_filter($ids))) as $product_id) {
        $product = wc_get_product($product_id);
        if (!$product instanceof WC_Product || !$product->is_visible()) continue;

        $visible_ids[] = $product_id;
        if (count($visible_ids) >= $limit) break;
    }

    return $visible_ids;
}

/** Render one compact suggestion row. */
function cg_ajax_search_product_item($product, $index) {
    if (!$product instanceof WC_Product) return;

    $product_id = $product->get_id();
    $category_names = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'names']);
    $categories = is_wp_error($category_names) ? '' : implode(', ', array_slice($category_names, 0, 2));
    $stock_text = $product->is_in_stock() ? 'В наличии' : 'Нет в наличии';
    $stock_class = $product->is_in_stock() ? 'is-in-stock' : 'is-out-of-stock';
    ?>
    <a class="cg-live-search__item" id="cg-live-search-option-<?php echo esc_attr($index); ?>" href="<?php echo esc_url(get_permalink($product_id)); ?>" role="option" aria-selected="false">
        <span class="cg-live-search__image"><?php echo wp_kses_post($product->get_image('woocommerce_thumbnail')); ?></span>
        <span class="cg-live-search__content">
            <span class="cg-live-search__meta">
                <span class="cg-live-search__stock <?php echo esc_attr($stock_class); ?>"><?php echo esc_html($stock_text); ?></span>
                <?php if ($categories): ?><span class="cg-live-search__category"><?php echo esc_html($categories); ?></span><?php endif; ?>
            </span>
            <strong><?php echo esc_html($product->get_name()); ?></strong>
        </span>
        <span class="cg-live-search__price"><?php echo $product->get_price_html() ? wp_kses_post($product->get_price_html()) : 'Цена по запросу'; ?></span>
    </a>
    <?php
}

/** AJAX endpoint for header product suggestions. */
function cg_ajax_product_search() {
    check_ajax_referer('cg_ajax_product_search', 'nonce');

    $search_term = isset($_POST['query'])
        ? trim(sanitize_text_field(wp_unslash($_POST['query'])))
        : '';
    $search_term = function_exists('mb_substr') ? mb_substr($search_term, 0, 80) : substr($search_term, 0, 80);
    $length = function_exists('mb_strlen') ? mb_strlen($search_term) : strlen($search_term);

    if ($length < 2) {
        wp_send_json_success([
            'html' => '',
            'count' => 0,
            'allUrl' => '',
        ]);
    }

    $product_ids = cg_ajax_search_product_ids($search_term, 8);

    ob_start();
    foreach ($product_ids as $index => $product_id) {
        cg_ajax_search_product_item(wc_get_product($product_id), $index);
    }
    $html = ob_get_clean();

    wp_send_json_success([
        'html' => $html,
        'count' => count($product_ids),
        'allUrl' => add_query_arg([
            's' => $search_term,
            'post_type' => 'product',
        ], home_url('/')),
    ]);
}
add_action('wp_ajax_cg_ajax_product_search', 'cg_ajax_product_search');
add_action('wp_ajax_nopriv_cg_ajax_product_search', 'cg_ajax_product_search');
