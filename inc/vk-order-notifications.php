<?php
/**
 * VK notifications for new WooCommerce orders.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

const CG_VK_API_DEFAULT_VERSION = '5.199';
const CG_VK_ORDER_SENT_META = '_cg_vk_new_order_sent';
const CG_VK_ORDER_MESSAGE_ID_META = '_cg_vk_message_id';
const CG_VK_ORDER_ATTEMPTS_META = '_cg_vk_notification_attempts';
const CG_VK_ORDER_ERROR_META = '_cg_vk_last_error';

function cg_vk_notifications_enabled() {
    return (bool) get_option('cg_vk_notifications_enabled', false);
}

function cg_vk_access_token() {
    if (defined('CG_VK_ACCESS_TOKEN') && CG_VK_ACCESS_TOKEN) {
        return trim((string) CG_VK_ACCESS_TOKEN);
    }

    return trim((string) get_option('cg_vk_access_token', ''));
}

function cg_vk_peer_id() {
    if (defined('CG_VK_PEER_ID') && CG_VK_PEER_ID) {
        return trim((string) CG_VK_PEER_ID);
    }

    return trim((string) get_option('cg_vk_peer_id', ''));
}

function cg_vk_api_version() {
    $version = trim((string) get_option('cg_vk_api_version', CG_VK_API_DEFAULT_VERSION));
    return preg_match('/^\d+(?:\.\d+)?$/', $version) ? $version : CG_VK_API_DEFAULT_VERSION;
}

function cg_vk_configuration_error($ignore_enabled = false) {
    if (!$ignore_enabled && !cg_vk_notifications_enabled()) {
        return new WP_Error('cg_vk_disabled', 'Уведомления ВКонтакте выключены.');
    }

    if (cg_vk_access_token() === '') {
        return new WP_Error('cg_vk_missing_token', 'Не указан токен сообщества ВКонтакте.');
    }

    $peer_id = cg_vk_peer_id();
    if ($peer_id === '' || !preg_match('/^-?\d+$/', $peer_id) || (int) $peer_id === 0) {
        return new WP_Error('cg_vk_invalid_peer', 'Укажите корректный ID получателя или беседы ВКонтакте.');
    }

    return null;
}

function cg_vk_plain_price($amount, $currency = '') {
    if (!function_exists('wc_price')) {
        return number_format_i18n((float) $amount, 2) . ' ₽';
    }

    $args = [];
    if ($currency !== '') $args['currency'] = $currency;
    $html = wc_price((float) $amount, $args);
    return trim(html_entity_decode(wp_strip_all_tags($html), ENT_QUOTES, 'UTF-8'));
}

function cg_vk_limit_message($message, $limit = 3800) {
    $message = trim((string) $message);
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($message, 'UTF-8') > $limit
            ? rtrim(mb_substr($message, 0, $limit - 1, 'UTF-8')) . '…'
            : $message;
    }

    return strlen($message) > $limit
        ? rtrim(substr($message, 0, $limit - 3)) . '...'
        : $message;
}

function cg_vk_api_request($method, $params = []) {
    $configuration_error = cg_vk_configuration_error(true);
    if (is_wp_error($configuration_error)) return $configuration_error;

    $body = array_merge($params, [
        'access_token' => cg_vk_access_token(),
        'v' => cg_vk_api_version(),
    ]);

    $response = wp_remote_post(
        'https://api.vk.com/method/' . rawurlencode((string) $method),
        [
            'timeout' => 15,
            'redirection' => 0,
            'sslverify' => true,
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8'],
            'body' => $body,
        ]
    );

    if (is_wp_error($response)) {
        return new WP_Error('cg_vk_http_error', 'ВКонтакте недоступен: ' . $response->get_error_message());
    }

    $status = (int) wp_remote_retrieve_response_code($response);
    $decoded = json_decode(wp_remote_retrieve_body($response), true);

    if ($status < 200 || $status >= 300 || !is_array($decoded)) {
        return new WP_Error('cg_vk_invalid_response', 'ВКонтакте вернул некорректный ответ. Код HTTP: ' . $status . '.');
    }

    if (!empty($decoded['error']) && is_array($decoded['error'])) {
        $code = isset($decoded['error']['error_code']) ? (int) $decoded['error']['error_code'] : 0;
        $message = isset($decoded['error']['error_msg'])
            ? sanitize_text_field($decoded['error']['error_msg'])
            : 'Неизвестная ошибка VK API';

        return new WP_Error('cg_vk_api_error', sprintf('Ошибка ВКонтакте %d: %s', $code, $message));
    }

    if (!array_key_exists('response', $decoded)) {
        return new WP_Error('cg_vk_empty_response', 'ВКонтакте не подтвердил отправку сообщения.');
    }

    return $decoded['response'];
}

function cg_vk_send_message($message) {
    $message = cg_vk_limit_message($message);
    if ($message === '') return new WP_Error('cg_vk_empty_message', 'Сообщение ВКонтакте получилось пустым.');

    return cg_vk_api_request('messages.send', [
        'peer_id' => cg_vk_peer_id(),
        'random_id' => wp_rand(1, 2147483647),
        'message' => $message,
        'disable_mentions' => 1,
    ]);
}

function cg_vk_order_admin_url($order) {
    if (is_object($order) && method_exists($order, 'get_edit_order_url')) {
        return (string) $order->get_edit_order_url();
    }

    return admin_url('post.php?post=' . absint($order->get_id()) . '&action=edit');
}

function cg_vk_build_order_message($order) {
    if (!$order instanceof WC_Order) return '';

    $currency = $order->get_currency();
    $created = $order->get_date_created();
    $lines = [
        '🌸 НОВЫЙ ЗАКАЗ №' . $order->get_order_number(),
        $created ? 'Создан: ' . wp_date('d.m.Y H:i', $created->getTimestamp()) : '',
        'Статус: ' . wc_get_order_status_name($order->get_status()),
        'Сумма: ' . cg_vk_plain_price($order->get_total(), $currency),
    ];

    $payment = trim((string) $order->get_payment_method_title());
    if ($payment !== '') $lines[] = 'Оплата: ' . $payment;

    $lines[] = '';
    $lines[] = 'СОСТАВ ЗАКАЗА';

    foreach ($order->get_items('line_item') as $item) {
        if (!$item instanceof WC_Order_Item_Product) continue;
        $quantity = max(1, (int) $item->get_quantity());
        $line_total = (float) $item->get_total() + (float) $item->get_total_tax();
        $lines[] = sprintf('• %s × %d — %s', $item->get_name(), $quantity, cg_vk_plain_price($line_total, $currency));
    }

    $recipient_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
    $recipient_phone = trim((string) $order->get_billing_phone());
    $sender_name = trim($order->get_meta('_cg_sender_first_name') . ' ' . $order->get_meta('_cg_sender_last_name'));
    $sender_phone = trim((string) $order->get_meta('_cg_sender_phone'));
    $sender_email = trim((string) $order->get_meta('_cg_sender_email'));

    $lines[] = '';
    $lines[] = 'КОНТАКТЫ';
    if ($recipient_name !== '') $lines[] = 'Получатель: ' . $recipient_name;
    if ($recipient_phone !== '') $lines[] = 'Телефон получателя: ' . $recipient_phone;
    if ($sender_name !== '') $lines[] = 'Отправитель: ' . $sender_name;
    if ($sender_phone !== '') $lines[] = 'Телефон отправителя: ' . $sender_phone;
    if ($sender_email !== '') $lines[] = 'Email: ' . $sender_email;

    $city = trim((string) $order->get_meta('_cg_delivery_city'));
    if ($city === '') $city = trim((string) $order->get_billing_city());
    $address_parts = array_filter([
        trim((string) $order->get_billing_address_1()),
        trim((string) $order->get_billing_address_2()),
    ]);
    $date = trim((string) $order->get_meta('_cg_delivery_date'));
    $time = trim((string) $order->get_meta('_cg_delivery_time'));
    $price_status = (string) $order->get_meta('_cg_delivery_price_status');
    $delivery_price = (float) $order->get_shipping_total();

    $lines[] = '';
    $lines[] = 'ДОСТАВКА';
    if ($city !== '') $lines[] = 'Населённый пункт: ' . $city;
    if ($address_parts) $lines[] = 'Адрес: ' . implode(', ', $address_parts);
    if ($date !== '') {
        $timestamp = strtotime($date);
        $lines[] = 'Дата: ' . ($timestamp ? wp_date('d.m.Y', $timestamp) : $date);
    }
    if ($time !== '') $lines[] = 'Время: ' . $time;
    $lines[] = $price_status === 'to_confirm'
        ? 'Стоимость доставки: нужно уточнить'
        : 'Стоимость доставки: ' . cg_vk_plain_price($delivery_price, $currency);

    $card = trim((string) $order->get_meta('_cg_card_message'));
    if ($card !== '') {
        $lines[] = '';
        $lines[] = 'ОТКРЫТКА';
        $lines[] = $card;
    }

    $comment = trim((string) $order->get_customer_note());
    if ($comment !== '') {
        $lines[] = '';
        $lines[] = 'КОММЕНТАРИЙ';
        $lines[] = $comment;
    }

    $lines[] = '';
    $lines[] = 'Открыть заказ: ' . cg_vk_order_admin_url($order);

    return cg_vk_limit_message(implode("\n", array_values(array_filter($lines, static function($line) {
        return $line !== null;
    }))));
}

function cg_vk_schedule_order_retry($order_id, $attempt) {
    $delay = max(1, (int) $attempt) * 5 * MINUTE_IN_SECONDS;
    $timestamp = time() + $delay;
    $args = [(int) $order_id];

    if (function_exists('as_next_scheduled_action') && function_exists('as_schedule_single_action')) {
        if (!as_next_scheduled_action('cg_vk_retry_order_notification', $args, 'cg-vk')) {
            as_schedule_single_action($timestamp, 'cg_vk_retry_order_notification', $args, 'cg-vk');
        }
        return;
    }

    if (!wp_next_scheduled('cg_vk_retry_order_notification', $args)) {
        wp_schedule_single_event($timestamp, 'cg_vk_retry_order_notification', $args);
    }
}

function cg_vk_deliver_order_notification($order, $force = false) {
    if (is_numeric($order)) $order = wc_get_order((int) $order);
    if (!$order instanceof WC_Order) return new WP_Error('cg_vk_invalid_order', 'Заказ не найден.');

    $configuration_error = cg_vk_configuration_error(false);
    if (is_wp_error($configuration_error)) return $configuration_error;

    if (!$force && $order->get_meta(CG_VK_ORDER_SENT_META)) {
        return $order->get_meta(CG_VK_ORDER_MESSAGE_ID_META);
    }

    $attempt = $force ? 1 : ((int) $order->get_meta(CG_VK_ORDER_ATTEMPTS_META) + 1);
    $order->update_meta_data(CG_VK_ORDER_ATTEMPTS_META, $attempt);
    $order->save();

    $result = cg_vk_send_message(cg_vk_build_order_message($order));

    if (is_wp_error($result)) {
        $error = sanitize_text_field($result->get_error_message());
        $order->update_meta_data(CG_VK_ORDER_ERROR_META, $error);
        $order->save();

        if (!$force && $attempt < 3) cg_vk_schedule_order_retry($order->get_id(), $attempt);
        return $result;
    }

    $message_id = is_scalar($result) ? (string) $result : wp_json_encode($result);
    $order->update_meta_data(CG_VK_ORDER_SENT_META, current_time('mysql'));
    $order->update_meta_data(CG_VK_ORDER_MESSAGE_ID_META, $message_id);
    $order->delete_meta_data(CG_VK_ORDER_ERROR_META);
    $order->save();
    $order->add_order_note('Уведомление о заказе отправлено ВКонтакте.');

    update_option('cg_vk_last_success', current_time('mysql'), false);
    delete_option('cg_vk_last_error');

    return $result;
}

function cg_vk_notify_checkout_order($order_id, $posted_data = [], $order = null) {
    if (!cg_vk_notifications_enabled()) return;
    if (!$order instanceof WC_Order) $order = wc_get_order($order_id);
    if (!$order instanceof WC_Order) return;

    $result = cg_vk_deliver_order_notification($order);
    if (is_wp_error($result)) update_option('cg_vk_last_error', $result->get_error_message(), false);
}
add_action('woocommerce_checkout_order_processed', 'cg_vk_notify_checkout_order', 35, 3);

function cg_vk_retry_order_notification($order_id) {
    if (!cg_vk_notifications_enabled()) return;
    $result = cg_vk_deliver_order_notification((int) $order_id);
    if (is_wp_error($result)) update_option('cg_vk_last_error', $result->get_error_message(), false);
}
add_action('cg_vk_retry_order_notification', 'cg_vk_retry_order_notification');

function cg_vk_order_actions($actions, $order) {
    if ($order instanceof WC_Order) {
        $actions['cg_vk_resend_notification'] = 'Отправить заказ ВКонтакте повторно';
    }
    return $actions;
}
add_filter('woocommerce_order_actions', 'cg_vk_order_actions', 20, 2);

function cg_vk_resend_order_action($order) {
    if (!$order instanceof WC_Order) return;

    $order->delete_meta_data(CG_VK_ORDER_SENT_META);
    $order->delete_meta_data(CG_VK_ORDER_MESSAGE_ID_META);
    $order->delete_meta_data(CG_VK_ORDER_ERROR_META);
    $order->update_meta_data(CG_VK_ORDER_ATTEMPTS_META, 0);
    $order->save();

    $result = cg_vk_deliver_order_notification($order, true);
    $order->add_order_note(
        is_wp_error($result)
            ? 'Не удалось отправить заказ ВКонтакте: ' . sanitize_text_field($result->get_error_message())
            : 'Заказ повторно отправлен ВКонтакте.'
    );
}
add_action('woocommerce_order_action_cg_vk_resend_notification', 'cg_vk_resend_order_action');

function cg_vk_admin_menu() {
    add_submenu_page(
        'woocommerce',
        'Уведомления ВКонтакте',
        'Уведомления ВКонтакте',
        'manage_woocommerce',
        'cg-vk-notifications',
        'cg_vk_admin_page'
    );
}
add_action('admin_menu', 'cg_vk_admin_menu', 40);

function cg_vk_admin_assets($hook) {
    if ($hook !== 'woocommerce_page_cg-vk-notifications') return;

    $path = get_template_directory() . '/assets/css/vk-notifications-admin.css';
    wp_enqueue_style(
        'cg-vk-notifications-admin',
        get_template_directory_uri() . '/assets/css/vk-notifications-admin.css',
        [],
        file_exists($path) ? filemtime($path) : wp_get_theme()->get('Version')
    );
}
add_action('admin_enqueue_scripts', 'cg_vk_admin_assets');

function cg_vk_admin_flash($type, $message) {
    set_transient('cg_vk_admin_flash_' . get_current_user_id(), [
        'type' => in_array($type, ['success', 'error', 'warning'], true) ? $type : 'info',
        'message' => sanitize_text_field($message),
    ], MINUTE_IN_SECONDS);
}

function cg_vk_admin_redirect() {
    wp_safe_redirect(admin_url('admin.php?page=cg-vk-notifications'));
    exit;
}

function cg_vk_save_settings() {
    if (!current_user_can('manage_woocommerce')) wp_die('Недостаточно прав.');
    check_admin_referer('cg_vk_save_settings');

    update_option('cg_vk_notifications_enabled', !empty($_POST['enabled']) ? 1 : 0, false);

    $peer_id = isset($_POST['peer_id']) ? trim(sanitize_text_field(wp_unslash($_POST['peer_id']))) : '';
    update_option('cg_vk_peer_id', preg_match('/^-?\d+$/', $peer_id) ? $peer_id : '', false);

    $api_version = isset($_POST['api_version']) ? trim(sanitize_text_field(wp_unslash($_POST['api_version']))) : '';
    update_option('cg_vk_api_version', preg_match('/^\d+(?:\.\d+)?$/', $api_version) ? $api_version : CG_VK_API_DEFAULT_VERSION, false);

    if (!empty($_POST['clear_token'])) {
        delete_option('cg_vk_access_token');
    } elseif (!defined('CG_VK_ACCESS_TOKEN') && isset($_POST['access_token'])) {
        $token = trim(sanitize_text_field(wp_unslash($_POST['access_token'])));
        if ($token !== '') update_option('cg_vk_access_token', $token, false);
    }

    cg_vk_admin_flash('success', 'Настройки уведомлений ВКонтакте сохранены.');
    cg_vk_admin_redirect();
}
add_action('admin_post_cg_vk_save_settings', 'cg_vk_save_settings');

function cg_vk_send_test() {
    if (!current_user_can('manage_woocommerce')) wp_die('Недостаточно прав.');
    check_admin_referer('cg_vk_send_test');

    $configuration_error = cg_vk_configuration_error(true);
    if (is_wp_error($configuration_error)) {
        cg_vk_admin_flash('error', $configuration_error->get_error_message());
        cg_vk_admin_redirect();
    }

    $result = cg_vk_send_message(
        "🌸 Тестовое уведомление\n\nСвязь сайта «Цветочный город» с ВКонтакте настроена.\nВремя проверки: " . current_time('d.m.Y H:i')
    );

    if (is_wp_error($result)) {
        update_option('cg_vk_last_error', $result->get_error_message(), false);
        cg_vk_admin_flash('error', $result->get_error_message());
    } else {
        update_option('cg_vk_last_success', current_time('mysql'), false);
        delete_option('cg_vk_last_error');
        cg_vk_admin_flash('success', 'Тестовое сообщение отправлено ВКонтакте.');
    }

    cg_vk_admin_redirect();
}
add_action('admin_post_cg_vk_send_test', 'cg_vk_send_test');

function cg_vk_admin_page() {
    if (!current_user_can('manage_woocommerce')) return;

    $flash_key = 'cg_vk_admin_flash_' . get_current_user_id();
    $flash = get_transient($flash_key);
    if ($flash) delete_transient($flash_key);

    $enabled = cg_vk_notifications_enabled();
    $peer_id = cg_vk_peer_id();
    $api_version = cg_vk_api_version();
    $token_saved = cg_vk_access_token() !== '';
    $token_constant = defined('CG_VK_ACCESS_TOKEN') && CG_VK_ACCESS_TOKEN;
    $peer_constant = defined('CG_VK_PEER_ID') && CG_VK_PEER_ID;
    $last_success = get_option('cg_vk_last_success', '');
    $last_error = get_option('cg_vk_last_error', '');
    ?>
    <div class="wrap cg-vk-admin">
        <div class="cg-vk-admin__hero">
            <div>
                <span>Цветочный город</span>
                <h1>Уведомления о заказах ВКонтакте</h1>
                <p>Новый заказ будет приходить личным сообщением: состав, сумма, получатель, отправитель, адрес, дата, открытка и комментарий.</p>
            </div>
            <div class="cg-vk-admin__state <?php echo $enabled && !$last_error ? 'is-ready' : ''; ?>">
                <strong><?php echo $enabled ? 'Уведомления включены' : 'Уведомления выключены'; ?></strong>
                <span><?php echo $token_saved && $peer_id !== '' ? 'Основные данные заполнены' : 'Нужно завершить настройку'; ?></span>
            </div>
        </div>

        <?php if ($flash) : ?>
            <div class="notice notice-<?php echo esc_attr($flash['type']); ?> is-dismissible"><p><?php echo esc_html($flash['message']); ?></p></div>
        <?php endif; ?>

        <div class="cg-vk-admin__grid">
            <section class="cg-vk-card">
                <h2>Подключение</h2>
                <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                    <input type="hidden" name="action" value="cg_vk_save_settings">
                    <?php wp_nonce_field('cg_vk_save_settings'); ?>

                    <label class="cg-vk-switch">
                        <input type="checkbox" name="enabled" value="1" <?php checked($enabled); ?>>
                        <span aria-hidden="true"></span>
                        <b>Отправлять новые заказы ВКонтакте</b>
                    </label>

                    <label class="cg-vk-field">
                        <span>Токен сообщества</span>
                        <input type="password" name="access_token" value="" autocomplete="new-password" placeholder="<?php echo $token_saved ? 'Токен уже сохранён — оставьте поле пустым' : 'Вставьте токен сообщества'; ?>" <?php disabled($token_constant); ?>>
                        <small><?php echo $token_constant ? 'Токен задан константой CG_VK_ACCESS_TOKEN в wp-config.php.' : 'Токен не показывается повторно после сохранения.'; ?></small>
                    </label>

                    <?php if ($token_saved && !$token_constant) : ?>
                        <label class="cg-vk-check"><input type="checkbox" name="clear_token" value="1"> Удалить сохранённый токен</label>
                    <?php endif; ?>

                    <label class="cg-vk-field">
                        <span>ID получателя или беседы</span>
                        <input type="text" name="peer_id" value="<?php echo esc_attr($peer_id); ?>" inputmode="numeric" placeholder="Например: 123456789" <?php disabled($peer_constant); ?>>
                        <small><?php echo $peer_constant ? 'ID задан константой CG_VK_PEER_ID в wp-config.php.' : 'Для личных уведомлений укажите цифровой ID вашего профиля ВКонтакте.'; ?></small>
                    </label>

                    <label class="cg-vk-field cg-vk-field--small">
                        <span>Версия VK API</span>
                        <input type="text" name="api_version" value="<?php echo esc_attr($api_version); ?>" placeholder="<?php echo esc_attr(CG_VK_API_DEFAULT_VERSION); ?>">
                    </label>

                    <button class="button button-primary button-large" type="submit">Сохранить настройки</button>
                </form>
            </section>

            <section class="cg-vk-card">
                <h2>Проверка связи</h2>
                <p>После сохранения настроек отправьте тест. Реальный заказ создавать не потребуется.</p>

                <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                    <input type="hidden" name="action" value="cg_vk_send_test">
                    <?php wp_nonce_field('cg_vk_send_test'); ?>
                    <button class="button button-secondary button-large" type="submit">Отправить тестовое сообщение</button>
                </form>

                <div class="cg-vk-status-list">
                    <div><span>Токен</span><strong><?php echo $token_saved ? 'Сохранён' : 'Не указан'; ?></strong></div>
                    <div><span>Получатель</span><strong><?php echo $peer_id !== '' ? esc_html($peer_id) : 'Не указан'; ?></strong></div>
                    <div><span>Последняя успешная отправка</span><strong><?php echo $last_success ? esc_html(wp_date('d.m.Y H:i', strtotime($last_success))) : 'Ещё не было'; ?></strong></div>
                </div>

                <?php if ($last_error) : ?>
                    <div class="cg-vk-error"><strong>Последняя ошибка</strong><span><?php echo esc_html($last_error); ?></span></div>
                <?php endif; ?>
            </section>

            <section class="cg-vk-card cg-vk-card--wide">
                <h2>Как подключить</h2>
                <ol class="cg-vk-steps">
                    <li><b>Включите сообщения сообщества.</b><span>В настройках вашей группы ВКонтакте откройте раздел сообщений.</span></li>
                    <li><b>Создайте токен сообщества.</b><span>Токену требуется доступ к сообщениям сообщества.</span></li>
                    <li><b>Напишите со своего профиля в сообщения группы.</b><span>После этого сообщество сможет отправлять вам уведомления.</span></li>
                    <li><b>Укажите цифровой ID профиля и отправьте тест.</b><span>Успешный тест подтверждает готовность системы.</span></li>
                </ol>
                <p class="description">Токен предоставляет доступ к сообщениям сообщества. Не отправляйте его в переписках и не публикуйте в открытом виде.</p>
            </section>
        </div>
    </div>
    <?php
}
