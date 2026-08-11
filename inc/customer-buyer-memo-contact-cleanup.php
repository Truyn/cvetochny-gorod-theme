<?php
/**
 * Keep the buyer memo contact block intentionally minimal.
 *
 * The full legal documents retain the seller's formal addresses and e-mail,
 * while the compact buyer memo shows only the store phone and working hours.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/** Remove e-mail and claim/return address from rendered memo HTML. */
function cg_buyer_memo_public_contacts_html($html) {
    if (!is_string($html) || $html === '') return $html;

    $pattern = '~<b>E-mail:</b>.*?·\s*<b>Телефон:</b>\s*(.*?)\s*·\s*<b>Адрес возврата/обращений:</b>.*?·\s*<b>Режим работы:</b>\s*(.*?)</p>~su';
    $replacement = '<b>Телефон:</b> $1 · <b>Режим работы:</b> $2</p>';

    return preg_replace($pattern, $replacement, $html) ?: $html;
}

/** Remove e-mail and claim/return address from plain-text memo copies. */
function cg_buyer_memo_public_contacts_text($text) {
    if (!is_string($text) || $text === '') return $text;

    $text = preg_replace('/^E-mail:.*\R?/miu', '', $text);
    $text = preg_replace('/^Адрес возврата\/обращений:.*\R?/miu', '', $text);

    return is_string($text) ? $text : '';
}

/** Replace the order-details memo renderer with the contact-minimized version. */
remove_action('woocommerce_order_details_after_order_table', 'cg_buyer_memo_order_details', 30);
function cg_buyer_memo_order_details_public_contacts($order) {
    if (!$order instanceof WC_Order || !function_exists('cg_buyer_memo_html')) return;

    echo cg_buyer_memo_public_contacts_html(cg_buyer_memo_html('order')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action('woocommerce_order_details_after_order_table', 'cg_buyer_memo_order_details_public_contacts', 30);

/** Keep customer e-mails free of the formal return address/e-mail as well. */
remove_action('woocommerce_email_after_order_table', 'cg_buyer_memo_email', 25);
function cg_buyer_memo_email_public_contacts($order, $sent_to_admin, $plain_text, $email) {
    if (!function_exists('cg_buyer_memo_email')) return;

    ob_start();
    cg_buyer_memo_email($order, $sent_to_admin, $plain_text, $email);
    $output = (string) ob_get_clean();

    if ($plain_text) {
        $output = cg_buyer_memo_public_contacts_text($output);
    } else {
        $output = cg_buyer_memo_public_contacts_html($output);
    }

    echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action('woocommerce_email_after_order_table', 'cg_buyer_memo_email_public_contacts', 25, 4);

/** Filter the standalone printable A5 page before the original renderer exits. */
function cg_buyer_memo_print_contact_buffer() {
    if (!isset($_GET['cg_buyer_memo']) || sanitize_text_field(wp_unslash($_GET['cg_buyer_memo'])) !== '1') return;

    ob_start('cg_buyer_memo_public_contacts_html');
}
add_action('template_redirect', 'cg_buyer_memo_print_contact_buffer', -1);
