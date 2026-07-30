<?php
/**
 * Browser-based favorites for WooCommerce products.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/** Public URL used by the header and empty states. */
function cg_favorites_url() {
    return home_url('/izbrannoe/');
}

/** Register a stable virtual page, so no manual WordPress page is required. */
function cg_register_favorites_route() {
    add_rewrite_rule('^izbrannoe/?$', 'index.php?cg_favorites_page=1', 'top');

    $route_version = '1';
    if (get_option('cg_favorites_route_version') !== $route_version) {
        flush_rewrite_rules(false);
        update_option('cg_favorites_route_version', $route_version, false);
    }
}
add_action('init', 'cg_register_favorites_route');

add_filter('query_vars', function($vars) {
    $vars[] = 'cg_favorites_page';
    return $vars;
});

/** Use the dedicated theme template for /izbrannoe/. */
function cg_favorites_template($template) {
    if ((int) get_query_var('cg_favorites_page') !== 1) return $template;

    $favorites_template = get_template_directory() . '/page-templates/favorites.php';
    return file_exists($favorites_template) ? $favorites_template : $template;
}
add_filter('template_include', 'cg_favorites_template', 50);

add_filter('pre_get_document_title', function($title) {
    if ((int) get_query_var('cg_favorites_page') === 1) {
        return 'Избранное — ' . get_bloginfo('name');
    }
    return $title;
});

add_filter('body_class', function($classes) {
    if ((int) get_query_var('cg_favorites_page') === 1) {
        $classes[] = 'cg-favorites-page';
    }
    return $classes;
});

/** Reusable heart control for catalog and related-product cards. */
function cg_favorite_card_button($product_id) {
    $product_id = absint($product_id);
    if (!$product_id) return;

    echo '<button class="cg-favorite-button" type="button" data-cg-favorite data-product-id="' . esc_attr($product_id) . '" aria-pressed="false" aria-label="Добавить в избранное">';
    echo '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.3 5.5a5 5 0 0 0-7.1 0L12 6.7l-1.2-1.2a5 5 0 0 0-7.1 7.1L12 20.7l8.3-8.1a5 5 0 0 0 0-7.1Z"/></svg>';
    echo '<span class="screen-reader-text" data-cg-favorite-label>Добавить в избранное</span>';
    echo '</button>';
}

function cg_loop_favorite_button() {
    global $product;
    if (!$product instanceof WC_Product) return;
    cg_favorite_card_button($product->get_id());
}
add_action('woocommerce_after_shop_loop_item', 'cg_loop_favorite_button', 6);

/** Shell filled by JavaScript using IDs stored in the visitor's browser. */
function cg_favorites_page_content() {
    ob_start();
    ?>
    <section class="cg-favorites" data-cg-favorites-page>
        <header class="cg-favorites__hero">
            <span class="cg-favorites__eyebrow">Сохранённые букеты</span>
            <h1>Избранное</h1>
            <p>Соберите подборку букетов, сравните варианты и вернитесь к ним перед оформлением заказа.</p>
        </header>

        <div class="cg-favorites__toolbar">
            <span data-cg-favorites-summary>Загружаем сохранённые товары…</span>
            <a href="<?php echo esc_url(cg_catalog_url()); ?>">Продолжить выбор</a>
        </div>

        <div class="cg-favorites__loading" data-cg-favorites-loading aria-live="polite">
            <span class="cg-favorites__spinner" aria-hidden="true"></span>
            Загружаем избранное
        </div>

        <div class="cg-favorites__grid" data-cg-favorites-grid></div>

        <div class="cg-favorites__empty" data-cg-favorites-empty hidden>
            <span class="cg-favorites__empty-icon" aria-hidden="true">♡</span>
            <h2>Здесь пока ничего нет</h2>
            <p>Нажимайте на сердечко у понравившихся букетов — они сохранятся на этой странице.</p>
            <a class="button" href="<?php echo esc_url(cg_catalog_url()); ?>">Перейти в каталог</a>
        </div>

        <div class="cg-favorites__error" data-cg-favorites-error hidden>
            <strong>Не удалось загрузить избранное</strong>
            <span>Обновите страницу и попробуйте ещё раз.</span>
        </div>
    </section>
    <?php
    return ob_get_clean();
}
add_shortcode('cg_favorites', 'cg_favorites_page_content');

/** Render one saved product card. */
function cg_render_favorite_product_card($product) {
    if (!$product instanceof WC_Product) return;

    $product_id = $product->get_id();
    $product_url = get_permalink($product_id);
    $image = $product->get_image('woocommerce_thumbnail');
    $price_html = $product->get_price_html();
    $is_direct_add = $product->is_type('simple') && $product->is_purchasable() && $product->is_in_stock();
    ?>
    <article class="cg-favorite-card" data-cg-favorite-card data-product-id="<?php echo esc_attr($product_id); ?>">
        <a class="cg-favorite-card__image" href="<?php echo esc_url($product_url); ?>">
            <?php echo wp_kses_post($image); ?>
            <span class="cg-favorite-card__status <?php echo $product->is_in_stock() ? 'is-stock' : 'is-out'; ?>">
                <?php echo esc_html($product->is_in_stock() ? 'В наличии' : 'Нет в наличии'); ?>
            </span>
        </a>

        <button class="cg-favorite-card__remove" type="button" data-cg-favorite data-product-id="<?php echo esc_attr($product_id); ?>" aria-pressed="true" aria-label="Удалить из избранного">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.3 5.5a5 5 0 0 0-7.1 0L12 6.7l-1.2-1.2a5 5 0 0 0-7.1 7.1L12 20.7l8.3-8.1a5 5 0 0 0 0-7.1Z"/></svg>
            <span class="screen-reader-text" data-cg-favorite-label>Удалить из избранного</span>
        </button>

        <div class="cg-favorite-card__body">
            <a class="cg-favorite-card__title" href="<?php echo esc_url($product_url); ?>"><?php echo esc_html($product->get_name()); ?></a>
            <div class="cg-favorite-card__price"><?php echo $price_html ? wp_kses_post($price_html) : 'Цена по запросу'; ?></div>

            <?php if ($is_direct_add): ?>
                <a href="<?php echo esc_url($product->add_to_cart_url()); ?>"
                   data-quantity="1"
                   class="button product_type_simple add_to_cart_button ajax_add_to_cart cg-favorite-card__action"
                   data-product_id="<?php echo esc_attr($product_id); ?>"
                   data-product_sku="<?php echo esc_attr($product->get_sku()); ?>"
                   aria-label="Добавить <?php echo esc_attr($product->get_name()); ?> в корзину"
                   rel="nofollow">В корзину</a>
            <?php else: ?>
                <a class="button cg-favorite-card__action" href="<?php echo esc_url($product_url); ?>">Выбрать букет</a>
            <?php endif; ?>
        </div>
    </article>
    <?php
}

/** Return saved products in the same order as the browser list. */
function cg_ajax_load_favorites() {
    check_ajax_referer('cg_favorites', 'nonce');

    $raw_ids = isset($_POST['ids']) ? (array) wp_unslash($_POST['ids']) : [];
    $ids = array_slice(array_values(array_unique(array_filter(array_map('absint', $raw_ids)))), 0, 100);
    $valid_ids = [];

    ob_start();
    foreach ($ids as $product_id) {
        $product = wc_get_product($product_id);
        if (!$product instanceof WC_Product || $product->get_status() !== 'publish' || !$product->is_visible()) continue;

        $valid_ids[] = $product_id;
        cg_render_favorite_product_card($product);
    }
    $html = ob_get_clean();

    wp_send_json_success([
        'html' => $html,
        'validIds' => $valid_ids,
        'count' => count($valid_ids),
    ]);
}
add_action('wp_ajax_cg_load_favorites', 'cg_ajax_load_favorites');
add_action('wp_ajax_nopriv_cg_load_favorites', 'cg_ajax_load_favorites');

/** Load favorites behavior wherever the header can be displayed. */
function cg_favorites_assets() {
    if (!class_exists('WooCommerce')) return;

    $version = wp_get_theme()->get('Version');
    $style_path = get_template_directory() . '/assets/css/favorites.css';
    $script_path = get_template_directory() . '/assets/js/favorites.js';

    wp_enqueue_style(
        'cg-favorites',
        get_template_directory_uri() . '/assets/css/favorites.css',
        ['cg-woocommerce'],
        file_exists($style_path) ? filemtime($style_path) : $version
    );

    wp_enqueue_script(
        'cg-favorites',
        get_template_directory_uri() . '/assets/js/favorites.js',
        [],
        file_exists($script_path) ? filemtime($script_path) : $version,
        true
    );

    wp_localize_script('cg-favorites', 'cgFavoritesConfig', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('cg_favorites'),
        'catalogUrl' => cg_catalog_url(),
        'favoritesUrl' => cg_favorites_url(),
        'strings' => [
            'add' => 'Добавить в избранное',
            'remove' => 'Удалить из избранного',
            'singleAdd' => 'В избранное',
            'singleRemove' => 'В избранном',
            'one' => '1 сохранённый букет',
            'many' => '%d сохранённых букетов',
        ],
    ]);
}
add_action('wp_enqueue_scripts', 'cg_favorites_assets', 25);
