<?php
/**
 * Custom WooCommerce product archive.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;
defined('WC_VERSION') || exit;

get_header('shop');

$paged = max(1, (int) get_query_var('paged'), (int) get_query_var('product-page'));
$catalog_query = new WP_Query(cg_catalog_build_query_args($paged));
$current_category = is_product_category() ? get_queried_object() : null;
$title = $current_category instanceof WP_Term ? $current_category->name : 'Каталог';
$category_intro = ($current_category instanceof WP_Term && function_exists('cg_seo_stage_two_category_intro'))
    ? cg_seo_stage_two_category_intro($current_category)
    : '';
$subtitle = $current_category instanceof WP_Term
    ? ($category_intro !== '' ? $category_intro : 'Подборка букетов из выбранной категории.')
    : 'Выберите букет по случаю, стилю и бюджету.';
?>
<style>
.cg-custom-catalog .cg-filter-group summary > span {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 30px;
    width: 30px;
    height: 30px;
    padding: 0;
    line-height: 1;
}
.cg-custom-catalog .cg-filter-group summary > span::before {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    margin: 0;
    line-height: 1;
    transform: none;
}
</style>
<main id="primary" class="site-main cg-custom-catalog" data-cg-catalog-template="server-ajax-v2">
    <div class="container content-area cg-woo-wrap">
        <header class="cg-catalog-heading">
            <span>Цветочный город</span>
            <h1><?php echo esc_html($title); ?></h1>
            <p><?php echo esc_html($subtitle); ?></p>
        </header>

        <div class="cg-shop-shell">
            <?php cg_catalog_sidebar(); ?>
            <section id="cg-catalog-results" class="cg-shop-content" aria-label="<?php esc_attr_e('Товары каталога', 'cvetochny-gorod'); ?>" aria-live="polite">
                <?php cg_catalog_render_results($catalog_query, $paged); ?>
            </section>
        </div>

        <?php if (function_exists('cg_seo_landing_catalog_links')) cg_seo_landing_catalog_links(); ?>
        <?php if ($current_category instanceof WP_Term && function_exists('cg_seo_stage_two_category_content')) cg_seo_stage_two_category_content($current_category); ?>
    </div>
</main>
<?php
wp_reset_postdata();
get_footer('shop');
