<?php
/**
 * Product archive template.
 *
 * Keeps WooCommerce's public hooks and loop APIs, while providing a stable
 * two-column catalog shell with a real filter sidebar.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

defined('WC_VERSION') || exit;

get_header('shop');

do_action('woocommerce_before_main_content');
?>
<main id="primary" class="site-main">
    <div class="container content-area cg-woo-wrap">
        <div class="cg-shop-shell">
            <?php if (function_exists('cg_catalog_sidebar')) : ?>
                <?php cg_catalog_sidebar(); ?>
            <?php endif; ?>

            <section class="cg-shop-content" aria-label="<?php esc_attr_e('Товары каталога', 'cvetochny-gorod'); ?>">
                <?php
                if (woocommerce_product_loop()) {
                    do_action('woocommerce_before_shop_loop');

                    woocommerce_product_loop_start();

                    if (wc_get_loop_prop('total')) {
                        while (have_posts()) {
                            the_post();
                            do_action('woocommerce_shop_loop');
                            wc_get_template_part('content', 'product');
                        }
                    }

                    woocommerce_product_loop_end();

                    do_action('woocommerce_after_shop_loop');
                } else {
                    do_action('woocommerce_no_products_found');
                }
                ?>
            </section>
        </div>
    </div>
</main>
<?php

do_action('woocommerce_after_main_content');

get_footer('shop');
