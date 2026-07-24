<?php
/**
 * Full custom WooCommerce single product template.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

get_header();
?>
<section id="cg-product-page" class="cg-product-page" data-cg-template="single-product-v2">
    <div class="container cg-product-page__container">
        <?php woocommerce_breadcrumb(); ?>

        <?php while (have_posts()) : the_post(); ?>
            <?php global $product; ?>

            <?php do_action('woocommerce_before_single_product'); ?>

            <?php if (post_password_required()) : ?>
                <?php echo get_the_password_form(); ?>
            <?php else : ?>
                <article id="product-<?php the_ID(); ?>" <?php wc_product_class('cg-product-detail', $product); ?>>
                    <div class="cg-product-detail__top">
                        <section class="cg-product-detail__gallery" aria-label="Фотографии товара">
                            <?php do_action('woocommerce_before_single_product_summary'); ?>
                        </section>

                        <section class="cg-product-detail__summary summary entry-summary" aria-label="Информация о товаре">
                            <?php do_action('woocommerce_single_product_summary'); ?>
                        </section>
                    </div>

                    <div class="cg-product-detail__bottom">
                        <?php do_action('woocommerce_after_single_product_summary'); ?>
                    </div>
                </article>
            <?php endif; ?>

            <?php do_action('woocommerce_after_single_product'); ?>
        <?php endwhile; ?>
    </div>
</section>
<?php
get_footer();
