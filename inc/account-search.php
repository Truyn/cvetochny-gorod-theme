<?php
/**
 * Store search, customer account and post-order enhancements.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/** Load search, account and order-success styles after the main theme stylesheet. */
function cg_account_search_assets() {
    $path = get_template_directory() . '/assets/css/account-search.css';
    $version = file_exists($path) ? filemtime($path) : wp_get_theme()->get('Version');

    wp_enqueue_style(
        'cg-account-search',
        get_template_directory_uri() . '/assets/css/account-search.css',
        ['cg-style'],
        $version
    );
}
add_action('wp_enqueue_scripts', 'cg_account_search_assets', 20);

/** Stable page and endpoint classes without overriding WooCommerce templates. */
function cg_account_body_classes($classes) {
    if (function_exists('is_account_page') && is_account_page()) {
        $classes[] = 'cg-account-page';
        $classes[] = is_user_logged_in() ? 'cg-account-logged-in' : 'cg-account-auth';

        if (function_exists('is_wc_endpoint_url')) {
            if (!is_wc_endpoint_url()) $classes[] = 'cg-account-dashboard';

            foreach (['orders', 'edit-address', 'payment-methods', 'edit-account', 'view-order', 'lost-password'] as $endpoint) {
                if (is_wc_endpoint_url($endpoint)) {
                    $classes[] = 'cg-account-endpoint-' . sanitize_html_class($endpoint);
                }
            }
        }
    }

    if (function_exists('is_order_received_page') && is_order_received_page()) {
        $classes[] = 'cg-order-received-page';
    }

    return $classes;
}
add_filter('body_class', 'cg_account_body_classes');

/** Limit the header search to WooCommerce products. */
function cg_product_search_form() {
    $value = get_search_query();
    echo '<form class="cg-product-search" role="search" method="get" action="' . esc_url(home_url('/')) . '">';
    echo '<label class="screen-reader-text" for="cg-product-search-field">Поиск товаров</label>';
    echo '<input id="cg-product-search-field" type="search" name="s" value="' . esc_attr($value) . '" placeholder="Найти букет или цветы…" autocomplete="off">';
    echo '<input type="hidden" name="post_type" value="product">';
    echo '<button type="submit" aria-label="Искать">⌕</button>';
    echo '</form>';
}

/** Small reusable icons for account cards. */
function cg_account_icon($name) {
    $icons = [
        'orders' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h10l2 4v13H5V7l2-4Z"/><path d="M8 10h8M8 14h8"/></svg>',
        'status' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg>',
        'address' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-6.1 7-12a7 7 0 1 0-14 0c0 5.9 7 12 7 12Z"/><circle cx="12" cy="9" r="2.5"/></svg>',
        'profile' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4.5 21a7.5 7.5 0 0 1 15 0"/></svg>',
        'flower' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="2"/><path d="M12 10c-3-1-4.5-3.3-3.2-5.2C10 3 12 5.2 12 10Zm2 2c1-3 3.3-4.5 5.2-3.2C21 10 18.8 12 14 12Zm-2 2c3 1 4.5 3.3 3.2 5.2C14 21 12 18.8 12 14Zm-2-2c-1 3-3.3 4.5-5.2 3.2C3 14 5.2 12 10 12Z"/></svg>',
    ];

    return isset($icons[$name]) ? $icons[$name] : $icons['flower'];
}

/** Flower-store wording and a useful order for account navigation. */
function cg_account_menu_items($items) {
    unset($items['downloads']);

    $labels = [
        'dashboard'       => 'Обзор',
        'orders'          => 'Мои заказы',
        'edit-address'    => 'Адреса доставки',
        'payment-methods' => 'Способы оплаты',
        'edit-account'    => 'Личные данные',
        'customer-logout' => 'Выйти',
    ];

    $ordered = [];
    foreach ($labels as $key => $label) {
        if (isset($items[$key])) $ordered[$key] = $label;
    }

    foreach ($items as $key => $label) {
        if (!isset($ordered[$key])) $ordered[$key] = $label;
    }

    return $ordered;
}
add_filter('woocommerce_account_menu_items', 'cg_account_menu_items');

/** Friendly intro above the login and registration forms. */
function cg_account_auth_intro() {
    if (is_user_logged_in()) return;

    echo '<section class="cg-account-auth-intro">';
    echo '<span class="cg-account-auth-intro__icon">' . cg_account_icon('flower') . '</span>';
    echo '<div><span class="cg-account-eyebrow">Личный кабинет</span>';
    echo '<h1>Ваши заказы всегда под рукой</h1>';
    echo '<p>Войдите, чтобы следить за заказами, повторять любимые букеты и хранить контактные данные.</p></div>';
    echo '</section>';
}
add_action('woocommerce_before_customer_login_form', 'cg_account_auth_intro', 5);

/** Friendly account dashboard content. */
function cg_account_dashboard_intro() {
    $user = wp_get_current_user();

    echo '<section class="cg-account-welcome">';
    echo '<div><span class="cg-account-eyebrow">Личный кабинет</span>';
    echo '<h1>Здравствуйте, ' . esc_html($user->display_name ?: $user->user_login) . '!</h1>';
    echo '<p>Здесь можно проверить статус заказа, открыть его состав, изменить адрес доставки и контактные данные.</p></div>';
    echo '<a class="button" href="' . esc_url(cg_catalog_url()) . '">Перейти в каталог</a>';
    echo '</section>';
}
add_action('woocommerce_account_dashboard', 'cg_account_dashboard_intro', 5);

/** Dashboard cards with useful customer shortcuts and the latest order state. */
function cg_account_dashboard_cards() {
    $customer_id = get_current_user_id();
    $order_count = function_exists('wc_get_customer_order_count') ? (int) wc_get_customer_order_count($customer_id) : 0;
    $last_orders = function_exists('wc_get_orders') ? wc_get_orders([
        'customer_id' => $customer_id,
        'limit'       => 1,
        'orderby'     => 'date',
        'order'       => 'DESC',
        'return'      => 'objects',
    ]) : [];
    $last_order = !empty($last_orders) ? $last_orders[0] : false;

    $last_title = $last_order
        ? 'Заказ №' . $last_order->get_order_number()
        : 'Заказов пока нет';
    $last_text = $last_order
        ? wc_get_order_status_name($last_order->get_status())
        : 'Выберите букет, и первый заказ появится здесь.';
    $last_url = $last_order
        ? $last_order->get_view_order_url()
        : cg_catalog_url();

    echo '<section class="cg-account-cards" aria-label="Разделы личного кабинета">';

    echo '<a class="cg-account-card" href="' . esc_url(wc_get_account_endpoint_url('orders')) . '">';
    echo '<span class="cg-account-card__icon">' . cg_account_icon('orders') . '</span>';
    echo '<span><small>История покупок</small><strong>Мои заказы</strong><em>' . esc_html($order_count) . ' ' . esc_html(_n('заказ', 'заказов', $order_count, 'cvetochny-gorod')) . '</em></span>';
    echo '</a>';

    echo '<a class="cg-account-card" href="' . esc_url($last_url) . '">';
    echo '<span class="cg-account-card__icon">' . cg_account_icon('status') . '</span>';
    echo '<span><small>Последний заказ</small><strong>' . esc_html($last_title) . '</strong><em>' . esc_html($last_text) . '</em></span>';
    echo '</a>';

    echo '<a class="cg-account-card" href="' . esc_url(wc_get_account_endpoint_url('edit-address')) . '">';
    echo '<span class="cg-account-card__icon">' . cg_account_icon('address') . '</span>';
    echo '<span><small>Для быстрой покупки</small><strong>Адреса доставки</strong><em>Проверить или изменить адрес</em></span>';
    echo '</a>';

    echo '<a class="cg-account-card" href="' . esc_url(wc_get_account_endpoint_url('edit-account')) . '">';
    echo '<span class="cg-account-card__icon">' . cg_account_icon('profile') . '</span>';
    echo '<span><small>Контактные данные</small><strong>Личные данные</strong><em>Имя, email и пароль</em></span>';
    echo '</a>';

    echo '</section>';
}
add_action('woocommerce_account_dashboard', 'cg_account_dashboard_cards', 10);

/** Heading above the order history table. */
function cg_account_orders_intro($has_orders) {
    echo '<section class="cg-account-section-heading">';
    echo '<span class="cg-account-eyebrow">История покупок</span>';
    echo '<h1>Мои заказы</h1>';
    echo '<p>' . ($has_orders ? 'Откройте заказ, чтобы посмотреть состав, статус и данные доставки.' : 'Здесь появятся ваши оформленные заказы.') . '</p>';
    echo '</section>';
}
add_action('woocommerce_before_account_orders', 'cg_account_orders_intro', 5, 1);

/** Keep the order history compact. */
add_filter('woocommerce_my_account_my_orders_query', function ($args) {
    $args['limit'] = 10;
    return $args;
});

/** Render a labelled detail without duplicating empty fields. */
function cg_account_order_detail($label, $value, $multiline = false) {
    if ($value === '' || $value === null) return;

    echo '<div class="cg-order-info__item">';
    echo '<span>' . esc_html($label) . '</span>';
    echo '<strong>' . ($multiline ? nl2br(esc_html($value)) : esc_html($value)) . '</strong>';
    echo '</div>';
}

/** Delivery, recipient and sender cards on order view and order-success pages. */
function cg_account_order_delivery_details($order) {
    if (!$order instanceof WC_Order) return;

    $order_user_id = (int) $order->get_user_id();
    if ($order_user_id > 0 && get_current_user_id() !== $order_user_id && !current_user_can('edit_shop_orders')) {
        return;
    }

    $delivery_city = (string) $order->get_meta('_cg_delivery_city');
    $delivery_date = (string) $order->get_meta('_cg_delivery_date');
    $delivery_time = (string) $order->get_meta('_cg_delivery_time');
    $card_message = (string) $order->get_meta('_cg_card_message');
    $delivery_price_status = (string) $order->get_meta('_cg_delivery_price_status');

    $sender_name = trim((string) $order->get_meta('_cg_sender_first_name') . ' ' . (string) $order->get_meta('_cg_sender_last_name'));
    $sender_phone = (string) $order->get_meta('_cg_sender_phone');
    $sender_email = (string) $order->get_meta('_cg_sender_email');

    $recipient_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
    $recipient_phone = $order->get_billing_phone();
    $recipient_address = implode(', ', array_filter([
        $order->get_billing_address_1(),
        $order->get_billing_address_2(),
        $delivery_city ?: $order->get_billing_city(),
    ]));

    if ($delivery_date !== '' && strtotime($delivery_date)) {
        $delivery_date = wp_date('d.m.Y', strtotime($delivery_date));
    }

    echo '<section class="cg-order-info" aria-labelledby="cg-order-info-title">';
    echo '<div class="cg-account-section-heading cg-order-info__heading">';
    echo '<span class="cg-account-eyebrow">Информация о заказе</span>';
    echo '<h2 id="cg-order-info-title">Доставка и контакты</h2>';
    echo '</div>';
    echo '<div class="cg-order-info__grid">';

    echo '<article class="cg-order-info__card"><h3>Получатель</h3>';
    cg_account_order_detail('Имя', $recipient_name);
    cg_account_order_detail('Телефон', $recipient_phone);
    cg_account_order_detail('Адрес', $recipient_address);
    echo '</article>';

    echo '<article class="cg-order-info__card"><h3>Доставка</h3>';
    cg_account_order_detail('Населённый пункт', $delivery_city ?: $order->get_billing_city());
    cg_account_order_detail('Дата', $delivery_date);
    cg_account_order_detail('Интервал', $delivery_time);
    if ($delivery_price_status === 'to_confirm') {
        cg_account_order_detail('Стоимость', 'Уточняется после оформления');
    }
    echo '</article>';

    echo '<article class="cg-order-info__card"><h3>Отправитель</h3>';
    cg_account_order_detail('Имя', $sender_name);
    cg_account_order_detail('Телефон', $sender_phone);
    cg_account_order_detail('Email', $sender_email);
    echo '</article>';

    if ($card_message !== '') {
        echo '<article class="cg-order-info__card cg-order-info__card--wide"><h3>Текст бесплатной открытки</h3>';
        cg_account_order_detail('Пожелание', $card_message, true);
        echo '</article>';
    }

    echo '</div></section>';
}
add_action('woocommerce_order_details_after_order_table', 'cg_account_order_delivery_details', 15, 1);

/** Warm, concise text on the order-success page. */
function cg_thankyou_received_text($text, $order) {
    if (!$order instanceof WC_Order) return $text;
    return 'Спасибо! Заказ принят и уже передан в работу.';
}
add_filter('woocommerce_thankyou_order_received_text', 'cg_thankyou_received_text', 10, 2);

/** What happens next after a successful order. */
function cg_thankyou_next_steps($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;

    echo '<section class="cg-thankyou-next" aria-labelledby="cg-thankyou-next-title">';
    echo '<div class="cg-thankyou-next__head">';
    echo '<span class="cg-account-eyebrow">Заказ №' . esc_html($order->get_order_number()) . '</span>';
    echo '<h2 id="cg-thankyou-next-title">Что будет дальше</h2>';
    echo '<span class="cg-order-status-badge">' . esc_html(wc_get_order_status_name($order->get_status())) . '</span>';
    echo '</div>';
    echo '<div class="cg-thankyou-steps">';
    echo '<div><b>1</b><span><strong>Проверим детали</strong><small>Флорист увидит заказ и проверит выбранную дату, адрес и пожелания.</small></span></div>';
    echo '<div><b>2</b><span><strong>Свяжемся с вами</strong><small>Уточним оплату и детали доставки по указанным контактам.</small></span></div>';
    echo '<div><b>3</b><span><strong>Соберём и доставим</strong><small>Подготовим свежий букет и передадим его курьеру.</small></span></div>';
    echo '</div>';
    echo '<div class="cg-thankyou-actions">';
    echo '<a class="button" href="' . esc_url(cg_catalog_url()) . '">Вернуться в каталог</a>';
    if (is_user_logged_in() && (int) $order->get_user_id() === get_current_user_id()) {
        echo '<a class="button button--ghost" href="' . esc_url($order->get_view_order_url()) . '">Открыть заказ</a>';
    }
    echo '</div>';
    echo '</section>';
}
add_action('woocommerce_thankyou', 'cg_thankyou_next_steps', 5, 1);
