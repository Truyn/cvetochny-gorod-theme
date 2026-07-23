<?php
/**
 * Custom single product content layout.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

global $product;

/**
 * Hook: woocommerce_before_single_product.
 */
do_action('woocommerce_before_single_product');

if (post_password_required()) {
    echo get_the_password_form();
    return;
}
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class('cg-single-product', $product); ?>>
    <div class="cg-single-product__main">
        <div class="cg-single-product__gallery">
            <?php do_action('woocommerce_before_single_product_summary'); ?>
        </div>

        <div class="cg-single-product__summary summary entry-summary">
            <?php do_action('woocommerce_single_product_summary'); ?>
        </div>
    </div>

    <div class="cg-single-product__after">
        <?php do_action('woocommerce_after_single_product_summary'); ?>
    </div>
</div>

<?php do_action('woocommerce_after_single_product'); ?>
