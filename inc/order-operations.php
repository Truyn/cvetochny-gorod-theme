<?php
/**
 * Customer-friendly order status explanations and manager order workflow helpers.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/** Human explanation of a WooCommerce order status without changing the real status. */
function cg_order_ops_status_copy($order) {
    if (!$order instanceof WC_Order) {
        return ['title' => 'Статус заказа', 'text' => 'Проверьте актуальный статус заказа.', 'tone' => 'neutral'];
    }

    $map = [
        'pending' => [
            'title' => 'Ожидаем подтверждение оплаты',
            'text' => 'Заказ сохранён. После подтверждения оплаты или способа расчёта мы сможем продолжить его обработку.',
            'tone' => 'wait',
        ],
        'on-hold' => [
            'title' => 'Заказ принят',
            'text' => 'Мы проверяем детали заказа. Если потребуется уточнение по оплате, адресу или времени доставки, свяжемся с отправителем.',
            'tone' => 'work',
        ],
        'processing' => [
            'title' => 'Заказ в работе',
            'text' => 'Заказ принят в работу. Флорист видит детали, а перед отправкой готовую композицию можно согласовать по фото.',
            'tone' => 'work',
        ],
        'completed' => [
            'title' => 'Заказ завершён',
            'text' => 'Заказ отмечен как выполненный. Спасибо, что выбрали «Цветочный город».',
            'tone' => 'done',
        ],
        'cancelled' => [
            'title' => 'Заказ отменён',
            'text' => 'Заказ отменён. Если это произошло неожиданно или нужен новый заказ, свяжитесь с магазином.',
            'tone' => 'stop',
        ],
        'refunded' => [
            'title' => 'Заказ возвращён',
            'text' => 'В WooCommerce заказ отмечен как возвращённый. Детали возврата зависят от способа оплаты.',
            'tone' => 'neutral',
        ],
        'failed' => [
            'title' => 'Оплата не завершена',
            'text' => 'Оплата заказа не была успешно завершена. Можно повторить попытку или связаться с магазином.',
            'tone' => 'stop',
        ],
    ];

    $status = $order->get_status();
    return $map[$status] ?? [
        'title' => wc_get_order_status_name($status),
        'text' => 'Статус заказа обновлён. Все детали заказа сохраняются в личном кабинете.',
        'tone' => 'neutral',
    ];
}

/** Reusable delivery values for admin surfaces. */
function cg_order_ops_delivery_values($order) {
    if (!$order instanceof WC_Order) return [];

    $city = trim((string) $order->get_meta('_cg_delivery_city'));
    if ($city === '') $city = trim((string) $order->get_billing_city());

    $date = trim((string) $order->get_meta('_cg_delivery_date'));
    if ($date !== '' && strtotime($date)) $date = wp_date('d.m.Y', strtotime($date));

    return [
        'city' => $city,
        'date' => $date,
        'time' => trim((string) $order->get_meta('_cg_delivery_time')),
        'recipient_name' => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
        'recipient_phone' => trim((string) $order->get_billing_phone()),
        'sender_name' => trim((string) $order->get_meta('_cg_sender_first_name') . ' ' . (string) $order->get_meta('_cg_sender_last_name')),
        'sender_phone' => trim((string) $order->get_meta('_cg_sender_phone')),
        'sender_email' => trim((string) $order->get_meta('_cg_sender_email')),
        'card' => trim((string) $order->get_meta('_cg_card_message')),
        'note' => trim((string) $order->get_customer_note()),
    ];
}

/** Status card on the customer's saved order page. */
function cg_order_ops_customer_status_card($order) {
    if (!$order instanceof WC_Order) return;
    if (!function_exists('is_wc_endpoint_url') || !is_wc_endpoint_url('view-order')) return;

    $copy = cg_order_ops_status_copy($order);
    echo '<section class="cg-customer-order-status is-' . esc_attr($copy['tone']) . '" aria-label="Статус заказа">';
    echo '<span class="cg-customer-order-status__mark" aria-hidden="true"></span>';
    echo '<div><small>Текущий статус</small><h2>' . esc_html($copy['title']) . '</h2><p>' . esc_html($copy['text']) . '</p></div>';
    echo '</section>';
}
add_action('woocommerce_order_details_before_order_table', 'cg_order_ops_customer_status_card', 8, 1);

/** The same plain-language status inside main customer order emails. */
function cg_order_ops_customer_email_status($order, $sent_to_admin, $plain_text, $email) {
    if ($sent_to_admin || !$order instanceof WC_Order || !is_object($email)) return;

    $email_id = isset($email->id) ? (string) $email->id : '';
    $allowed = [
        'customer_processing_order',
        'customer_on_hold_order',
        'customer_completed_order',
        'customer_invoice',
    ];
    if (!in_array($email_id, $allowed, true)) return;

    $copy = cg_order_ops_status_copy($order);

    if ($plain_text) {
        echo "\nСТАТУС ЗАКАЗА: " . wp_strip_all_tags($copy['title']) . "\n";
        echo wp_strip_all_tags($copy['text']) . "\n\n";
        return;
    }

    echo '<div style="margin:0 0 22px;padding:18px 20px;border:1px solid #ead7d3;border-radius:14px;background:#fff8f5;color:#4b3a36;">';
    echo '<div style="margin:0 0 6px;color:#9a625b;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">Статус заказа</div>';
    echo '<strong style="display:block;margin:0 0 6px;font-size:18px;line-height:1.3;color:#493733;">' . esc_html($copy['title']) . '</strong>';
    echo '<p style="margin:0;line-height:1.6;color:#6f5b56;">' . esc_html($copy['text']) . '</p>';
    echo '</div>';
}
add_action('woocommerce_email_before_order_table', 'cg_order_ops_customer_email_status', 8, 4);

/** Manager quick reference inside the order edit screen. */
function cg_order_ops_admin_summary($order) {
    if (!$order instanceof WC_Order) return;

    $v = cg_order_ops_delivery_values($order);
    $copy = cg_order_ops_status_copy($order);
    $payment = trim((string) $order->get_payment_method_title());

    $phone_link = static function($phone) {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        return $digits !== '' ? 'tel:+' . ltrim($digits, '+') : '';
    };

    echo '<div class="cg-order-admin-summary">';
    echo '<div class="cg-order-admin-summary__head"><strong>Главное по заказу</strong><span>' . esc_html($copy['title']) . '</span></div>';
    echo '<div class="cg-order-admin-summary__grid">';

    echo '<section><b>Получатель</b>';
    if ($v['recipient_name'] !== '') echo '<span>' . esc_html($v['recipient_name']) . '</span>';
    if ($v['recipient_phone'] !== '') {
        $tel = $phone_link($v['recipient_phone']);
        echo '<a href="' . esc_url($tel) . '">' . esc_html($v['recipient_phone']) . '</a>';
    }
    echo '</section>';

    echo '<section><b>Доставка</b>';
    if ($v['date'] !== '') echo '<span><strong>' . esc_html($v['date']) . '</strong>' . ($v['time'] !== '' ? ' · ' . esc_html($v['time']) : '') . '</span>';
    if ($v['city'] !== '') echo '<span>' . esc_html($v['city']) . '</span>';
    echo '</section>';

    echo '<section><b>Отправитель</b>';
    if ($v['sender_name'] !== '') echo '<span>' . esc_html($v['sender_name']) . '</span>';
    if ($v['sender_phone'] !== '') {
        $tel = $phone_link($v['sender_phone']);
        echo '<a href="' . esc_url($tel) . '">' . esc_html($v['sender_phone']) . '</a>';
    }
    if ($v['sender_email'] !== '' && is_email($v['sender_email'])) {
        echo '<a href="mailto:' . esc_attr($v['sender_email']) . '">' . esc_html($v['sender_email']) . '</a>';
    }
    echo '</section>';

    echo '<section><b>Оплата</b>';
    echo '<span>' . esc_html($payment !== '' ? $payment : 'Способ не указан') . '</span>';
    echo '<span>' . esc_html($order->is_paid() ? 'Оплата подтверждена' : 'Оплата не отмечена как полученная') . '</span>';
    echo '</section>';

    echo '</div>';

    if ($v['card'] !== '' || $v['note'] !== '') {
        echo '<div class="cg-order-admin-summary__notes">';
        if ($v['card'] !== '') echo '<p><b>Открытка:</b> ' . nl2br(esc_html($v['card'])) . '</p>';
        if ($v['note'] !== '') echo '<p><b>Комментарий:</b> ' . nl2br(esc_html($v['note'])) . '</p>';
        echo '</div>';
    }

    echo '</div>';
}
add_action('woocommerce_admin_order_data_after_order_details', 'cg_order_ops_admin_summary', 12, 1);

/** Add a compact delivery column to both HPOS and legacy order lists. */
function cg_order_ops_add_delivery_column($columns) {
    if (!is_array($columns)) return $columns;

    $new = [];
    $inserted = false;
    foreach ($columns as $key => $label) {
        $new[$key] = $label;
        if ($key === 'order_status') {
            $new['cg_delivery_brief'] = 'Доставка';
            $inserted = true;
        }
    }
    if (!$inserted) $new['cg_delivery_brief'] = 'Доставка';
    return $new;
}
add_filter('manage_woocommerce_page_wc-orders_columns', 'cg_order_ops_add_delivery_column', 25);
add_filter('manage_edit-shop_order_columns', 'cg_order_ops_add_delivery_column', 25);

/** Resolve either HPOS order object or legacy post ID. */
function cg_order_ops_resolve_order($order_or_id) {
    if ($order_or_id instanceof WC_Order) return $order_or_id;
    if (is_object($order_or_id) && is_callable([$order_or_id, 'get_id'])) {
        $order_or_id = $order_or_id->get_id();
    }
    return function_exists('wc_get_order') ? wc_get_order(absint($order_or_id)) : false;
}

function cg_order_ops_render_delivery_column($column, $order_or_id = 0) {
    if ($column !== 'cg_delivery_brief') return;

    $order = cg_order_ops_resolve_order($order_or_id);
    if (!$order instanceof WC_Order) {
        echo '—';
        return;
    }

    $v = cg_order_ops_delivery_values($order);
    if ($v['date'] === '' && $v['city'] === '') {
        echo '—';
        return;
    }

    echo '<div class="cg-order-list-delivery">';
    if ($v['date'] !== '') echo '<strong>' . esc_html($v['date']) . '</strong>';
    if ($v['time'] !== '') echo '<span>' . esc_html($v['time']) . '</span>';
    if ($v['city'] !== '') echo '<small>' . esc_html($v['city']) . '</small>';
    echo '</div>';
}
add_action('manage_woocommerce_page_wc-orders_custom_column', 'cg_order_ops_render_delivery_column', 20, 2);
add_action('manage_shop_order_posts_custom_column', 'cg_order_ops_render_delivery_column', 20, 2);

/** Extra mail readiness summary on the existing WooCommerce order-check page. */
function cg_order_ops_readiness_mail_notice() {
    if (!is_admin() || !current_user_can('manage_woocommerce')) return;
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    if ($page !== 'cg-order-readiness' || !function_exists('WC') || !WC()->mailer()) return;

    $emails = (array) WC()->mailer()->get_emails();
    $new_order = $emails['WC_Email_New_Order'] ?? null;
    $new_order_enabled = $new_order && $new_order->is_enabled();
    $recipient = $new_order && is_callable([$new_order, 'get_recipient']) ? trim((string) $new_order->get_recipient()) : '';

    $customer_enabled = [];
    foreach ([
        'WC_Email_Customer_Processing_Order' => 'Заказ в обработке',
        'WC_Email_Customer_On_Hold_Order' => 'Заказ на удержании',
        'WC_Email_Customer_Completed_Order' => 'Заказ выполнен',
    ] as $key => $label) {
        if (isset($emails[$key]) && $emails[$key]->is_enabled()) $customer_enabled[] = $label;
    }

    $ok = $new_order_enabled && $recipient !== '' && !empty($customer_enabled);
    $class = $ok ? 'notice-success' : 'notice-warning';

    echo '<div class="notice ' . esc_attr($class) . ' inline cg-order-mail-readiness"><p>';
    echo '<strong>Письма заказов:</strong> ';
    if ($new_order_enabled && $recipient !== '') {
        echo 'уведомление магазину включено, получатель: ' . esc_html($recipient) . '. ';
    } else {
        echo 'проверьте письмо «Новый заказ» и его получателя. ';
    }
    echo $customer_enabled
        ? 'Клиентские письма включены: ' . esc_html(implode(', ', $customer_enabled)) . '.'
        : 'Не найдено включённых основных клиентских писем.';
    echo '</p></div>';
}
add_action('admin_notices', 'cg_order_ops_readiness_mail_notice', 22);

/** Styles for order account and manager screens. */
function cg_order_ops_front_assets() {
    if (!function_exists('is_account_page') || !is_account_page()) return;
    $path = get_template_directory() . '/assets/css/order-operations.css';
    wp_enqueue_style(
        'cg-order-operations',
        get_template_directory_uri() . '/assets/css/order-operations.css',
        ['cg-account-search'],
        file_exists($path) ? filemtime($path) : wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'cg_order_ops_front_assets', 85);

function cg_order_ops_admin_assets() {
    if (!is_admin() || !current_user_can('manage_woocommerce')) return;
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen) return;

    $id = (string) $screen->id;
    $post_type = (string) $screen->post_type;
    if ($post_type !== 'shop_order' && strpos($id, 'wc-orders') === false) return;

    $path = get_template_directory() . '/assets/css/order-operations.css';
    wp_enqueue_style(
        'cg-order-operations-admin',
        get_template_directory_uri() . '/assets/css/order-operations.css',
        [],
        file_exists($path) ? filemtime($path) : wp_get_theme()->get('Version')
    );
}
add_action('admin_enqueue_scripts', 'cg_order_ops_admin_assets', 30);
