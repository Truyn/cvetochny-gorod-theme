<?php
/**
 * Final visual and checkout polish based on the pre-launch screenshots.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/** Detect generated legal-document pages before rendering their content. */
function cg_final_polish_is_legal_page() {
    if (!is_page()) return false;

    $post = get_queried_object();
    return $post instanceof WP_Post
        && isset($post->post_content)
        && has_shortcode((string) $post->post_content, 'cg_legal_document');
}

/** Add a stable body class so the outer WordPress page title can share the legal-document axis. */
function cg_final_polish_body_classes($classes) {
    if (cg_final_polish_is_legal_page()) $classes[] = 'cg-legal-page';
    return $classes;
}
add_filter('body_class', 'cg_final_polish_body_classes', 40);

/** Prefill Russian phone fields with the country code without overwriting saved/customer values. */
function cg_final_polish_checkout_phone_prefix($value, $input) {
    if (!is_checkout() || is_order_received_page()) return $value;

    if (in_array($input, ['billing_phone', 'cg_sender_phone'], true) && trim((string) $value) === '') {
        return '+7 ';
    }

    return $value;
}
add_filter('woocommerce_checkout_get_value', 'cg_final_polish_checkout_phone_prefix', 20, 2);

/** Number of digits entered in a phone field. */
function cg_final_polish_phone_digits($value) {
    return strlen((string) preg_replace('/\D+/', '', (string) $value));
}

/**
 * Replace the old sender-only required check with a full phone-length check for
 * both recipient and sender. This prevents the prefilled "+7" from passing as
 * a complete phone number.
 */
remove_action('woocommerce_checkout_process', 'cg_validate_sender_checkout_fields');
function cg_final_polish_validate_checkout_contacts() {
    $first_name = isset($_POST['cg_sender_first_name']) ? trim((string) wp_unslash($_POST['cg_sender_first_name'])) : '';
    $sender_phone = isset($_POST['cg_sender_phone']) ? trim((string) wp_unslash($_POST['cg_sender_phone'])) : '';
    $recipient_phone = isset($_POST['billing_phone']) ? trim((string) wp_unslash($_POST['billing_phone'])) : '';
    $email = isset($_POST['cg_sender_email']) ? sanitize_email(wp_unslash($_POST['cg_sender_email'])) : '';

    if ($first_name === '') {
        wc_add_notice('Укажите имя отправителя.', 'error');
    }

    if (cg_final_polish_phone_digits($sender_phone) < 11) {
        wc_add_notice('Укажите полный телефон отправителя.', 'error');
    }

    if (cg_final_polish_phone_digits($recipient_phone) < 11) {
        wc_add_notice('Укажите полный телефон получателя.', 'error');
    }

    if ($email === '' || !is_email($email)) {
        wc_add_notice('Укажите корректный email отправителя.', 'error');
    }
}
add_action('woocommerce_checkout_process', 'cg_final_polish_validate_checkout_contacts', 10);

/** Late, narrowly scoped CSS fixes for the screenshots from the final audit. */
function cg_final_polish_inline_styles() {
    if ((function_exists('is_shop') && is_shop()) || (function_exists('is_product_taxonomy') && is_product_taxonomy())) {
        $catalog_css = '
@media(min-width:1121px){
body.post-type-archive-product .topbar .container,
body.post-type-archive-product .site-header>.container,
body.post-type-archive-product .site-header .header-search>.container,
body.tax-product_cat .topbar .container,
body.tax-product_cat .site-header>.container,
body.tax-product_cat .site-header .header-search>.container,
body.tax-product_tag .topbar .container,
body.tax-product_tag .site-header>.container,
body.tax-product_tag .site-header .header-search>.container{
    width:min(calc(100% - 48px),1720px)!important;
    max-width:1720px!important;
}
}
.cg-custom-catalog .cg-catalog-heading{width:100%!important;max-width:none!important}
';
        wp_add_inline_style('cg-premium-filters', $catalog_css);
    }

    if (function_exists('is_checkout') && is_checkout() && !is_order_received_page()) {
        $checkout_css = '
.woocommerce-checkout .cg-classic-checkout .woocommerce-checkout-review-order-table tfoot th{width:36%!important}
.woocommerce-checkout .cg-classic-checkout .woocommerce-checkout-review-order-table tfoot td{width:64%!important;text-align:right!important}
.woocommerce-checkout .cg-classic-checkout .woocommerce-checkout-review-order-table .shipping th{padding-right:16px!important;vertical-align:middle!important;white-space:nowrap}
.woocommerce-checkout .cg-classic-checkout .woocommerce-checkout-review-order-table .shipping td{padding-left:16px!important;vertical-align:middle!important;text-align:right!important;white-space:normal!important;line-height:1.4!important;overflow-wrap:anywhere}
.woocommerce-checkout .cg-classic-checkout .woocommerce-checkout-review-order-table .shipping td .woocommerce-Price-amount{white-space:nowrap}
.woocommerce-checkout .cg-classic-checkout .woocommerce-checkout-review-order-table .shipping td ul{margin:0!important;padding:0!important;list-style:none!important;text-align:right!important}
';
        wp_add_inline_style('cg-classic-checkout-template', $checkout_css);
    }

    if (cg_final_polish_is_legal_page()) {
        $legal_css = '
body.cg-legal-page .site-main>.container>.page-header{
    width:min(100%,1040px)!important;
    margin-left:auto!important;
    margin-right:auto!important;
}
@media(max-width:640px){
    body.cg-legal-page .site-main>.container>.page-header{width:calc(100% - 22px)!important}
}
';
        wp_add_inline_style('cg-legal-pages', $legal_css);
    }
}
add_action('wp_enqueue_scripts', 'cg_final_polish_inline_styles', 100);
