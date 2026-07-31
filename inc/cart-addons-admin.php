<?php
/**
 * Dedicated WooCommerce admin screen for bouquet additions.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/** Register an obvious management page under the Products menu. */
function cg_cart_addons_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=product',
        'Дополнения к букетам',
        'Дополнения к букетам',
        'manage_woocommerce',
        'cg-cart-addons',
        'cg_cart_addons_admin_page'
    );
}
add_action('admin_menu', 'cg_cart_addons_admin_menu', 30);

/** Load WooCommerce searchable product selects only on our settings screen. */
function cg_cart_addons_admin_assets($hook_suffix) {
    if ($hook_suffix !== 'product_page_cg-cart-addons') return;

    wp_enqueue_style('woocommerce_admin_styles');
    wp_enqueue_script('wc-enhanced-select');

    $css = '
        .cg-addons-admin{max-width:1120px;margin-top:24px}
        .cg-addons-admin__intro{max-width:820px;color:#5f6b76;font-size:14px;line-height:1.65}
        .cg-addons-admin__grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;margin-top:24px}
        .cg-addons-admin__card{padding:24px;border:1px solid #dcdcde;border-radius:14px;background:#fff;box-shadow:0 6px 20px rgba(0,0,0,.04)}
        .cg-addons-admin__card h2{margin:0 0 8px;font-size:20px}
        .cg-addons-admin__card p{margin:0 0 18px;color:#646970;line-height:1.55}
        .cg-addons-admin__field{display:grid;gap:8px;margin:18px 0}
        .cg-addons-admin__field label{font-weight:700}
        .cg-addons-admin__field .select2-container{width:100%!important}
        .cg-addons-admin__note{padding:12px 14px;border-left:4px solid #b7756d;background:#fff8f6;color:#5d4c48}
        .cg-addons-admin__steps{margin:14px 0 0 18px;line-height:1.7}
        @media(max-width:900px){.cg-addons-admin__grid{grid-template-columns:1fr}}
    ';
    wp_add_inline_style('woocommerce_admin_styles', $css);
}
add_action('admin_enqueue_scripts', 'cg_cart_addons_admin_assets');

/** IDs currently marked as additions for every basket. */
function cg_cart_addons_admin_global_ids() {
    return array_map('absint', get_posts([
        'post_type' => 'product',
        'post_status' => ['publish', 'draft', 'private'],
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => [[
            'key' => '_cg_cart_addon',
            'value' => 'yes',
        ]],
        'orderby' => 'title',
        'order' => 'ASC',
        'no_found_rows' => true,
        'suppress_filters' => false,
    ]));
}

/** Keep only simple WooCommerce products suitable for one-click cart adding. */
function cg_cart_addons_admin_sanitize_product_ids($raw_ids) {
    $ids = array_values(array_unique(array_filter(array_map('absint', (array) $raw_ids))));
    $valid_ids = [];

    foreach ($ids as $product_id) {
        $product = wc_get_product($product_id);
        if (!$product instanceof WC_Product || !$product->is_type('simple')) continue;
        $valid_ids[] = $product_id;
    }

    return $valid_ids;
}

/** Output selected options required by WooCommerce's AJAX product search field. */
function cg_cart_addons_admin_selected_options($product_ids) {
    foreach ((array) $product_ids as $product_id) {
        $product = wc_get_product($product_id);
        if (!$product instanceof WC_Product) continue;

        echo '<option value="' . esc_attr($product_id) . '" selected>';
        echo esc_html(wp_strip_all_tags($product->get_formatted_name()));
        echo '</option>';
    }
}

/** Save products displayed as additions for every basket. */
function cg_cart_addons_admin_save_global() {
    if (!current_user_can('manage_woocommerce')) wp_die('Недостаточно прав.');
    check_admin_referer('cg_save_global_cart_addons');

    $selected_ids = cg_cart_addons_admin_sanitize_product_ids(
        isset($_POST['global_addon_ids']) ? wp_unslash($_POST['global_addon_ids']) : []
    );
    $current_ids = cg_cart_addons_admin_global_ids();

    foreach (array_diff($current_ids, $selected_ids) as $product_id) {
        update_post_meta($product_id, '_cg_cart_addon', 'no');
    }

    foreach ($selected_ids as $product_id) {
        update_post_meta($product_id, '_cg_cart_addon', 'yes');
    }

    wp_safe_redirect(add_query_arg([
        'post_type' => 'product',
        'page' => 'cg-cart-addons',
        'cg_updated' => 'global',
    ], admin_url('edit.php')));
    exit;
}
add_action('admin_post_cg_save_global_cart_addons', 'cg_cart_addons_admin_save_global');

/** Save additions linked to one bouquet through WooCommerce cross-sells. */
function cg_cart_addons_admin_save_linked() {
    if (!current_user_can('manage_woocommerce')) wp_die('Недостаточно прав.');
    check_admin_referer('cg_save_linked_cart_addons');

    $bouquet_id = isset($_POST['bouquet_id']) ? absint($_POST['bouquet_id']) : 0;
    $bouquet = $bouquet_id ? wc_get_product($bouquet_id) : false;

    if (!$bouquet instanceof WC_Product) {
        wp_safe_redirect(add_query_arg([
            'post_type' => 'product',
            'page' => 'cg-cart-addons',
            'cg_error' => 'bouquet',
        ], admin_url('edit.php')));
        exit;
    }

    $addon_ids = cg_cart_addons_admin_sanitize_product_ids(
        isset($_POST['linked_addon_ids']) ? wp_unslash($_POST['linked_addon_ids']) : []
    );
    $addon_ids = array_values(array_diff($addon_ids, [$bouquet_id]));

    $bouquet->set_cross_sell_ids($addon_ids);
    $bouquet->save();

    wp_safe_redirect(add_query_arg([
        'post_type' => 'product',
        'page' => 'cg-cart-addons',
        'bouquet_id' => $bouquet_id,
        'cg_updated' => 'linked',
    ], admin_url('edit.php')));
    exit;
}
add_action('admin_post_cg_save_linked_cart_addons', 'cg_cart_addons_admin_save_linked');

/** Render the dedicated additions manager. */
function cg_cart_addons_admin_page() {
    if (!current_user_can('manage_woocommerce')) return;

    $global_ids = cg_cart_addons_admin_global_ids();
    $bouquet_id = isset($_GET['bouquet_id']) ? absint($_GET['bouquet_id']) : 0;
    $bouquet = $bouquet_id ? wc_get_product($bouquet_id) : false;
    $linked_ids = $bouquet instanceof WC_Product ? $bouquet->get_cross_sell_ids('edit') : [];
    $updated = isset($_GET['cg_updated']) ? sanitize_key(wp_unslash($_GET['cg_updated'])) : '';
    $error = isset($_GET['cg_error']) ? sanitize_key(wp_unslash($_GET['cg_error'])) : '';
    ?>
    <div class="wrap cg-addons-admin">
        <h1>Дополнения к букетам</h1>
        <p class="cg-addons-admin__intro">
            Здесь настраиваются конфеты, игрушки, вазы и другие товары, которые покупатель сможет добавить в корзине одной кнопкой. Выбирайте простые товары с указанной ценой и остатком.
        </p>

        <?php if ($updated): ?>
            <div class="notice notice-success is-dismissible"><p>Настройки дополнительных товаров сохранены.</p></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="notice notice-error is-dismissible"><p>Не удалось найти выбранный букет. Выберите товар ещё раз.</p></div>
        <?php endif; ?>

        <div class="cg-addons-admin__grid">
            <section class="cg-addons-admin__card">
                <h2>Для всех букетов</h2>
                <p>Эти товары будут предлагаться в любой непустой корзине, независимо от выбранного букета.</p>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="cg_save_global_cart_addons">
                    <?php wp_nonce_field('cg_save_global_cart_addons'); ?>

                    <div class="cg-addons-admin__field">
                        <label for="cg-global-addon-products">Дополнительные товары</label>
                        <select id="cg-global-addon-products"
                                class="wc-product-search"
                                multiple="multiple"
                                style="width:100%"
                                name="global_addon_ids[]"
                                data-placeholder="Начните вводить название товара…"
                                data-action="woocommerce_json_search_products">
                            <?php cg_cart_addons_admin_selected_options($global_ids); ?>
                        </select>
                    </div>

                    <p class="submit"><button type="submit" class="button button-primary">Сохранить общие дополнения</button></p>
                </form>
            </section>

            <section class="cg-addons-admin__card">
                <h2>Для конкретного букета</h2>
                <p>Такие дополнения показываются первыми, когда выбранный букет находится в корзине.</p>

                <form method="get" action="<?php echo esc_url(admin_url('edit.php')); ?>">
                    <input type="hidden" name="post_type" value="product">
                    <input type="hidden" name="page" value="cg-cart-addons">

                    <div class="cg-addons-admin__field">
                        <label for="cg-addon-bouquet">Букет или основной товар</label>
                        <select id="cg-addon-bouquet"
                                class="wc-product-search"
                                style="width:100%"
                                name="bouquet_id"
                                data-placeholder="Найдите букет…"
                                data-action="woocommerce_json_search_products">
                            <?php if ($bouquet instanceof WC_Product): ?>
                                <option value="<?php echo esc_attr($bouquet_id); ?>" selected><?php echo esc_html(wp_strip_all_tags($bouquet->get_formatted_name())); ?></option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <p class="submit"><button type="submit" class="button">Открыть настройки букета</button></p>
                </form>

                <?php if ($bouquet instanceof WC_Product): ?>
                    <hr>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="cg_save_linked_cart_addons">
                        <input type="hidden" name="bouquet_id" value="<?php echo esc_attr($bouquet_id); ?>">
                        <?php wp_nonce_field('cg_save_linked_cart_addons'); ?>

                        <div class="cg-addons-admin__field">
                            <label for="cg-linked-addon-products">Дополнения для «<?php echo esc_html($bouquet->get_name()); ?>»</label>
                            <select id="cg-linked-addon-products"
                                    class="wc-product-search"
                                    multiple="multiple"
                                    style="width:100%"
                                    name="linked_addon_ids[]"
                                    data-placeholder="Найдите конфеты, игрушку или вазу…"
                                    data-action="woocommerce_json_search_products"
                                    data-exclude="<?php echo esc_attr($bouquet_id); ?>">
                                <?php cg_cart_addons_admin_selected_options($linked_ids); ?>
                            </select>
                        </div>

                        <p class="submit"><button type="submit" class="button button-primary">Сохранить дополнения букета</button></p>
                    </form>
                <?php else: ?>
                    <div class="cg-addons-admin__note">Сначала найдите букет выше и нажмите «Открыть настройки букета».</div>
                <?php endif; ?>
            </section>
        </div>

        <div class="cg-addons-admin__card" style="margin-top:20px">
            <h2>Как это работает</h2>
            <ol class="cg-addons-admin__steps">
                <li>Создайте конфеты, игрушку, вазу или другой подарок как обычный простой товар WooCommerce.</li>
                <li>Укажите цену, фотографию, остаток и опубликуйте товар.</li>
                <li>Добавьте его выше для всех букетов либо свяжите с конкретным букетом.</li>
                <li>Покупатель увидит дополнение в блоке «Дополните подарок» в корзине.</li>
            </ol>
        </div>
    </div>
    <?php
}
