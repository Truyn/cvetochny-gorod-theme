<?php
/**
 * Template Name: Оформление заказа — Premium
 * Template Post Type: page
 */

if (!defined('ABSPATH')) exit;

get_header();

if (!class_exists('WooCommerce')) {
    echo '<main class="container cg-premium-checkout-page"><p>Для оформления заказа необходимо активировать WooCommerce.</p></main>';
    get_footer();
    return;
}
?>
<main class="cg-premium-checkout-page">
    <div class="container">
        <header class="cg-premium-checkout-hero">
            <span>Цветочный город</span>
            <h1>Оформление заказа</h1>
            <p>Проверьте состав заказа, укажите данные получателя и выберите удобный способ доставки и оплаты.</p>
            <div class="cg-premium-checkout-steps" aria-label="Этапы оформления заказа">
                <div><b>1</b><span>Контакты</span></div>
                <div><b>2</b><span>Доставка</span></div>
                <div><b>3</b><span>Оплата</span></div>
            </div>
        </header>

        <section class="cg-premium-checkout-shell">
            <?php echo do_shortcode('[woocommerce_checkout]'); ?>
        </section>
    </div>
</main>
<?php get_footer();
