<?php
/**
 * Custom product catalog powered by WooCommerce products and cart APIs.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;
defined('WC_VERSION') || exit;

get_header('shop');

$paged = max(1, (int) get_query_var('paged'), (int) get_query_var('product-page'));
$query_args = function_exists('cg_catalog_build_query_args')
    ? cg_catalog_build_query_args($paged)
    : ['post_type' => 'product', 'post_status' => 'publish', 'paged' => $paged, 'posts_per_page' => 12];

$catalog_query = new WP_Query($query_args);

$title = is_product_category() ? single_term_title('', false) : 'Каталог букетов';
$subtitle = is_product_category()
    ? 'Подборка букетов из выбранной категории.'
    : 'Выберите букет по случаю, стилю и бюджету.';
?>
<main id="primary" class="site-main cg-custom-catalog">
    <div class="container content-area cg-woo-wrap">
        <header class="cg-catalog-heading">
            <span>Цветочный город</span>
            <h1><?php echo esc_html($title); ?></h1>
            <p><?php echo esc_html($subtitle); ?></p>
        </header>

        <div class="cg-shop-shell">
            <?php if (function_exists('cg_catalog_sidebar')) cg_catalog_sidebar(); ?>

            <section class="cg-shop-content" aria-label="<?php esc_attr_e('Товары каталога', 'cvetochny-gorod'); ?>">
                <?php if (function_exists('cg_catalog_toolbar')) cg_catalog_toolbar($catalog_query); ?>
                <?php if (function_exists('cg_catalog_active_filters')) cg_catalog_active_filters(); ?>

                <?php if ($catalog_query->have_posts()) : ?>
                    <?php
                    wc_set_loop_prop('total', $catalog_query->found_posts);
                    wc_set_loop_prop('per_page', $catalog_query->get('posts_per_page'));
                    wc_set_loop_prop('current_page', $paged);
                    wc_set_loop_prop('total_pages', $catalog_query->max_num_pages);
                    ?>
                    <?php woocommerce_product_loop_start(); ?>
                        <?php while ($catalog_query->have_posts()) : $catalog_query->the_post(); ?>
                            <?php wc_get_template_part('content', 'product'); ?>
                        <?php endwhile; ?>
                    <?php woocommerce_product_loop_end(); ?>

                    <?php if ($catalog_query->max_num_pages > 1) : ?>
                        <nav class="woocommerce-pagination" aria-label="<?php esc_attr_e('Навигация по товарам', 'cvetochny-gorod'); ?>">
                            <?php
                            echo wp_kses_post(paginate_links([
                                'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                                'format' => '?paged=%#%',
                                'current' => $paged,
                                'total' => $catalog_query->max_num_pages,
                                'type' => 'list',
                                'add_args' => function_exists('cg_catalog_preserved_query_args') ? cg_catalog_preserved_query_args() : [],
                                'prev_text' => '←',
                                'next_text' => '→',
                            ]));
                            ?>
                        </nav>
                    <?php endif; ?>
                <?php else : ?>
                    <div class="cg-catalog-empty">
                        <h2>Ничего не найдено</h2>
                        <p>Попробуйте изменить фильтры или сбросить выбранные параметры.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</main>
<?php
wp_reset_postdata();
get_footer('shop');
