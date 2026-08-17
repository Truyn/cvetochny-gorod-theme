<?php
/**
 * Branded customer emails, daily delivery board and final launch summary.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/** Keep WooCommerce email templates update-safe and brand them through the official CSS filter. */
function cg_delivery_launch_email_styles($css, $email) {
    $css .= "\n/* Цветочный город — мягкая фирменная полировка писем */\n";
    $css .= "body{background:#fff8f5!important;color:#4a3935!important;}\n";
    $css .= "#wrapper{background:#fff8f5!important;padding:28px 0!important;}\n";
    $css .= "#template_container{border:1px solid #ead8d3!important;border-radius:18px!important;overflow:hidden!important;box-shadow:0 12px 34px rgba(83,55,52,.08)!important;}\n";
    $css .= "#template_header{background:#d97c87!important;border-radius:0!important;}\n";
    $css .= "#template_header h1{color:#fff!important;font-weight:600!important;letter-spacing:-.01em!important;}\n";
    $css .= "#body_content{background:#fffdfa!important;}\n";
    $css .= "#body_content_inner{color:#66534e!important;font-size:15px!important;line-height:1.65!important;}\n";
    $css .= "#body_content h1,#body_content h2,#body_content h3{color:#4a3834!important;}\n";
    $css .= "#body_content a{color:#a95662!important;}\n";
    $css .= "#body_content table.td,#body_content th.td,#body_content td.td{border-color:#ead8d3!important;}\n";
    $css .= "#body_content th.td{background:#fff4ef!important;color:#6e5751!important;}\n";
    $css .= "#body_content .button,#body_content a.button{background:#d97c87!important;border-color:#d97c87!important;color:#fff!important;border-radius:10px!important;}\n";
    $css .= "#template_footer{background:#fff8f5!important;}\n";
    $css .= "#credit{color:#917b75!important;font-size:12px!important;line-height:1.6!important;text-align:center!important;}\n";
    return $css;
}
add_filter('woocommerce_email_styles', 'cg_delivery_launch_email_styles', 30, 2);

/** Useful branded footer instead of a generic WooCommerce credit line. */
function cg_delivery_launch_email_footer($text, $email = null) {
    $phone = trim((string) get_theme_mod('cg_phone', '+7 (930) 411-98-55'));
    $hours = trim((string) get_theme_mod('cg_worktime', 'Ежедневно с 07:00 до 21:00'));
    $parts = ['Цветочный город · магазин цветов в Нововоронеже'];
    if ($phone !== '') $parts[] = 'Телефон: ' . $phone;
    if ($hours !== '') $parts[] = $hours;
    return implode('<br>', array_map('esc_html', $parts));
}
add_filter('woocommerce_email_footer_text', 'cg_delivery_launch_email_footer', 30, 2);

/** Parse the first time from a delivery interval for stable chronological sorting. */
function cg_delivery_board_time_key($value) {
    $value = (string) $value;
    if (preg_match('/(?:^|\D)([01]?\d|2[0-3])[:.]([0-5]\d)/u', $value, $match)) {
        return sprintf('%02d:%02d', (int) $match[1], (int) $match[2]);
    }
    return '99:99';
}

/** Load delivery rows once through wc_get_orders so the board remains compatible with HPOS. */
function cg_delivery_board_source_rows() {
    static $rows = null;
    if ($rows !== null) return $rows;
    if (!function_exists('wc_get_orders')) return [];

    $orders = wc_get_orders([
        'status' => ['wc-pending', 'wc-on-hold', 'wc-processing', 'wc-completed'],
        'limit' => 250,
        'orderby' => 'date',
        'order' => 'DESC',
        'return' => 'objects',
    ]);

    $rows = [];
    foreach ($orders as $order) {
        if (!$order instanceof WC_Order) continue;

        $date = trim((string) $order->get_meta('_cg_delivery_date'));
        if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) continue;

        $time = trim((string) $order->get_meta('_cg_delivery_time'));
        $city = trim((string) $order->get_meta('_cg_delivery_city'));
        if ($city === '') $city = trim((string) $order->get_billing_city());

        $rows[] = [
            'order' => $order,
            'date' => $date,
            'time' => $time,
            'time_key' => cg_delivery_board_time_key($time),
            'city' => $city,
        ];
    }

    usort($rows, static function ($a, $b) {
        $left = $a['date'] . ' ' . $a['time_key'] . ' ' . str_pad((string) $a['order']->get_id(), 12, '0', STR_PAD_LEFT);
        $right = $b['date'] . ' ' . $b['time_key'] . ' ' . str_pad((string) $b['order']->get_id(), 12, '0', STR_PAD_LEFT);
        return strcmp($left, $right);
    });

    return $rows;
}

function cg_delivery_board_orders($range = 'week') {
    $now = current_datetime();
    $today = $now->format('Y-m-d');
    $tomorrow = $now->modify('+1 day')->format('Y-m-d');
    $week_end = $now->modify('+6 days')->format('Y-m-d');
    $rows = [];

    foreach (cg_delivery_board_source_rows() as $row) {
        $order = $row['order'];
        $date = $row['date'];
        $status = $order->get_status();
        $active = in_array($status, ['pending', 'on-hold', 'processing'], true);
        $include = false;

        if ($range === 'today') {
            $include = $date === $today;
        } elseif ($range === 'tomorrow') {
            $include = $date === $tomorrow && $active;
        } elseif ($range === 'overdue') {
            $include = $date < $today && $active;
        } elseif ($range === 'all') {
            $include = ($date >= $today && $active) || ($date === $today && $status === 'completed');
        } else {
            $include = $date >= $today && $date <= $week_end && ($active || ($date === $today && $status === 'completed'));
        }

        if ($include) $rows[] = $row;
    }

    return $rows;
}

function cg_delivery_board_counts() {
    return [
        'today' => count(cg_delivery_board_orders('today')),
        'tomorrow' => count(cg_delivery_board_orders('tomorrow')),
        'week' => count(cg_delivery_board_orders('week')),
        'overdue' => count(cg_delivery_board_orders('overdue')),
    ];
}

/** Unicode-friendly local search without another order query. */
function cg_delivery_board_text_contains($haystack, $needle) {
    $haystack = (string) $haystack;
    $needle = trim((string) $needle);
    if ($needle === '') return true;

    if (function_exists('mb_stripos')) {
        return mb_stripos($haystack, $needle, 0, 'UTF-8') !== false;
    }
    return stripos($haystack, $needle) !== false;
}

/** Filter the already loaded delivery rows by simple manager-facing controls. */
function cg_delivery_board_filter_rows($rows, $query = '', $status = 'all', $payment = 'all', $sort = 'asc') {
    $query = trim((string) $query);
    $query_digits = preg_replace('/\D+/', '', $query);
    $status_allowed = ['all', 'pending', 'on-hold', 'processing', 'completed'];
    $payment_allowed = ['all', 'paid', 'unpaid'];

    if (!in_array($status, $status_allowed, true)) $status = 'all';
    if (!in_array($payment, $payment_allowed, true)) $payment = 'all';
    if (!in_array($sort, ['asc', 'desc'], true)) $sort = 'asc';

    $filtered = array_values(array_filter((array) $rows, static function ($row) use ($query, $query_digits, $status, $payment) {
        $order = $row['order'] ?? null;
        if (!$order instanceof WC_Order) return false;

        if ($status !== 'all' && $order->get_status() !== $status) return false;
        if ($payment === 'paid' && !$order->is_paid()) return false;
        if ($payment === 'unpaid' && $order->is_paid()) return false;

        if ($query === '') return true;

        $sender_name = trim((string) $order->get_meta('_cg_sender_first_name') . ' ' . (string) $order->get_meta('_cg_sender_last_name'));
        $sender_phone = trim((string) $order->get_meta('_cg_sender_phone'));
        $recipient = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
        $recipient_phone = trim((string) $order->get_billing_phone());

        $haystack = implode(' | ', [
            $order->get_order_number(),
            '#' . $order->get_order_number(),
            '№' . $order->get_order_number(),
            $recipient,
            $recipient_phone,
            $sender_name,
            $sender_phone,
            (string) ($row['city'] ?? ''),
            (string) ($row['date'] ?? ''),
            (string) ($row['time'] ?? ''),
            (string) $order->get_payment_method_title(),
            wc_get_order_status_name($order->get_status()),
        ]);

        if (cg_delivery_board_text_contains($haystack, $query)) return true;

        if ($query_digits !== '') {
            $phone_haystack = preg_replace('/\D+/', '', $recipient_phone . $sender_phone . $order->get_order_number());
            if ($phone_haystack !== '' && strpos($phone_haystack, $query_digits) !== false) return true;
        }

        return false;
    }));

    if ($sort === 'desc') $filtered = array_reverse($filtered);
    return $filtered;
}

function cg_delivery_board_register_page() {
    add_submenu_page(
        'woocommerce',
        'Заказы на доставку',
        'Заказы на доставку',
        'manage_woocommerce',
        'cg-delivery-board',
        'cg_delivery_board_render_page'
    );
}
add_action('admin_menu', 'cg_delivery_board_register_page', 33);

/** Hide the duplicate technical launch link from Appearance; access remains through WooCommerce → Control order. */
function cg_delivery_board_cleanup_duplicate_admin_menu() {
    remove_submenu_page('themes.php', 'cg-launch-readiness');
}
add_action('admin_menu', 'cg_delivery_board_cleanup_duplicate_admin_menu', 999);

function cg_delivery_board_admin_assets($hook) {
    if (!in_array($hook, ['woocommerce_page_cg-delivery-board', 'woocommerce_page_cg-order-readiness'], true)) return;

    $path = get_template_directory() . '/assets/css/delivery-board-admin.css';
    wp_enqueue_style(
        'cg-delivery-board-admin',
        get_template_directory_uri() . '/assets/css/delivery-board-admin.css',
        [],
        file_exists($path) ? filemtime($path) : wp_get_theme()->get('Version')
    );

    $polish_path = get_template_directory() . '/assets/css/delivery-board-admin-polish.css';
    wp_enqueue_style(
        'cg-delivery-board-admin-polish',
        get_template_directory_uri() . '/assets/css/delivery-board-admin-polish.css',
        ['cg-delivery-board-admin'],
        file_exists($polish_path) ? filemtime($polish_path) : wp_get_theme()->get('Version')
    );
}
add_action('admin_enqueue_scripts', 'cg_delivery_board_admin_assets');

function cg_delivery_board_all_orders_url() {
    if (class_exists('\\Automattic\\WooCommerce\\Utilities\\OrderUtil')
        && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()) {
        return admin_url('admin.php?page=wc-orders');
    }
    return admin_url('edit.php?post_type=shop_order');
}

function cg_delivery_board_phone_link($phone) {
    $digits = preg_replace('/\D+/', '', (string) $phone);
    if ($digits === '') return '';
    if (strlen($digits) === 11 && $digits[0] === '8') $digits = '7' . substr($digits, 1);
    return 'tel:+' . ltrim($digits, '+');
}

function cg_delivery_board_render_page() {
    if (!current_user_can('manage_woocommerce')) return;

    $allowed = ['today', 'tomorrow', 'week', 'all', 'overdue'];
    $range = isset($_GET['range']) ? sanitize_key(wp_unslash($_GET['range'])) : 'week';
    if (!in_array($range, $allowed, true)) $range = 'week';

    $query = isset($_GET['q']) ? sanitize_text_field(wp_unslash($_GET['q'])) : '';
    $status = isset($_GET['order_status']) ? sanitize_key(wp_unslash($_GET['order_status'])) : 'all';
    $payment = isset($_GET['payment']) ? sanitize_key(wp_unslash($_GET['payment'])) : 'all';
    $sort = isset($_GET['sort']) ? sanitize_key(wp_unslash($_GET['sort'])) : 'asc';

    $rows = cg_delivery_board_filter_rows(cg_delivery_board_orders($range), $query, $status, $payment, $sort);
    $counts = cg_delivery_board_counts();
    $today = current_datetime()->format('Y-m-d');

    $tabs = [
        'today' => 'Сегодня',
        'tomorrow' => 'Завтра',
        'week' => '7 дней',
        'all' => 'Все будущие',
        'overdue' => 'Просроченные',
    ];

    $persistent_args = [];
    if ($query !== '') $persistent_args['q'] = $query;
    if ($status !== 'all') $persistent_args['order_status'] = $status;
    if ($payment !== 'all') $persistent_args['payment'] = $payment;
    if ($sort !== 'asc') $persistent_args['sort'] = $sort;

    $has_filters = !empty($persistent_args);
    ?>
    <div class="wrap cg-delivery-board">
        <div class="cg-delivery-board__hero">
            <div>
                <span>Ежедневная работа</span>
                <h1>Заказы на доставку</h1>
                <p>Ближайшие доставки в одном месте. Эта страница ничего не меняет в заказах — только помогает быстро видеть дату, время и контакты.</p>
            </div>
            <a class="button" href="<?php echo esc_url(cg_delivery_board_all_orders_url()); ?>">Все заказы WooCommerce</a>
        </div>

        <?php if ($counts['overdue'] > 0) :
            $overdue_url = add_query_arg(['page' => 'cg-delivery-board', 'range' => 'overdue'], admin_url('admin.php'));
            ?>
            <div class="cg-delivery-board__urgent">
                <div><strong>Есть просроченные доставки: <?php echo esc_html($counts['overdue']); ?></strong><span>Это активные заказы с датой доставки раньше сегодняшней. Проверьте их статус.</span></div>
                <a href="<?php echo esc_url($overdue_url); ?>">Показать просроченные</a>
            </div>
        <?php endif; ?>

        <div class="cg-delivery-board__stats">
            <div class="<?php echo $counts['today'] ? 'is-today' : ''; ?>"><span>Сегодня</span><strong><?php echo esc_html($counts['today']); ?></strong></div>
            <div><span>Завтра</span><strong><?php echo esc_html($counts['tomorrow']); ?></strong></div>
            <div><span>На 7 дней</span><strong><?php echo esc_html($counts['week']); ?></strong></div>
            <div class="<?php echo $counts['overdue'] ? 'is-alert' : ''; ?>"><span>Просроченные</span><strong><?php echo esc_html($counts['overdue']); ?></strong></div>
        </div>

        <nav class="cg-delivery-board__tabs" aria-label="Период доставок">
            <?php foreach ($tabs as $key => $label) :
                $url = add_query_arg(array_merge(['page' => 'cg-delivery-board', 'range' => $key], $persistent_args), admin_url('admin.php'));
                ?>
                <a class="<?php echo $range === $key ? 'is-active' : ''; ?>" href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a>
            <?php endforeach; ?>
        </nav>

        <form class="cg-delivery-board__filters" method="get">
            <input type="hidden" name="page" value="cg-delivery-board">
            <input type="hidden" name="range" value="<?php echo esc_attr($range); ?>">
            <label class="cg-delivery-board__search">
                <span>Найти заказ</span>
                <input type="search" name="q" value="<?php echo esc_attr($query); ?>" placeholder="№ заказа, имя, телефон, населённый пункт">
            </label>
            <label>
                <span>Статус</span>
                <select name="order_status">
                    <option value="all" <?php selected($status, 'all'); ?>>Все статусы</option>
                    <option value="processing" <?php selected($status, 'processing'); ?>>В обработке</option>
                    <option value="on-hold" <?php selected($status, 'on-hold'); ?>>На удержании</option>
                    <option value="pending" <?php selected($status, 'pending'); ?>>Ожидает оплаты</option>
                    <option value="completed" <?php selected($status, 'completed'); ?>>Выполнен</option>
                </select>
            </label>
            <label>
                <span>Оплата</span>
                <select name="payment">
                    <option value="all" <?php selected($payment, 'all'); ?>>Любая</option>
                    <option value="paid" <?php selected($payment, 'paid'); ?>>Оплачено</option>
                    <option value="unpaid" <?php selected($payment, 'unpaid'); ?>>Не отмечено как оплачено</option>
                </select>
            </label>
            <label>
                <span>Порядок</span>
                <select name="sort">
                    <option value="asc" <?php selected($sort, 'asc'); ?>>Сначала ближайшие</option>
                    <option value="desc" <?php selected($sort, 'desc'); ?>>Сначала дальние</option>
                </select>
            </label>
            <div class="cg-delivery-board__filter-actions">
                <button class="button button-primary" type="submit">Показать</button>
                <?php if ($has_filters) :
                    $reset_url = add_query_arg(['page' => 'cg-delivery-board', 'range' => $range], admin_url('admin.php'));
                    ?>
                    <a href="<?php echo esc_url($reset_url); ?>">Сбросить</a>
                <?php endif; ?>
            </div>
        </form>

        <div class="cg-delivery-board__result-line">
            <span>Найдено заказов: <strong><?php echo esc_html(count($rows)); ?></strong></span>
            <?php if ($has_filters) : ?><small>Фильтры применены только к текущему периоду.</small><?php endif; ?>
        </div>

        <?php if (!$rows) : ?>
            <div class="cg-delivery-board__empty"><strong>По выбранным условиям доставок нет.</strong><span>Измените период или сбросьте фильтры.</span></div>
        <?php else : ?>
            <div class="cg-delivery-board__table-wrap">
                <table class="widefat striped cg-delivery-board__table">
                    <thead><tr><th>Заказ</th><th>Доставка</th><th>Получатель</th><th>Населённый пункт</th><th>Оплата</th><th>Статус</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $row) :
                        $order = $row['order'];
                        $phone = trim((string) $order->get_billing_phone());
                        $tel = cg_delivery_board_phone_link($phone);
                        $name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
                        $date_label = wp_date('d.m.Y', strtotime($row['date']));
                        $is_today = $row['date'] === $today;
                        $active = in_array($order->get_status(), ['pending', 'on-hold', 'processing'], true);
                        $is_overdue = $row['date'] < $today && $active;
                        $row_class = $is_overdue ? 'is-overdue' : ($is_today ? 'is-today' : '');
                        ?>
                        <tr class="<?php echo esc_attr($row_class); ?>">
                            <td><a class="cg-delivery-board__order" href="<?php echo esc_url($order->get_edit_order_url()); ?>"><strong>№<?php echo esc_html($order->get_order_number()); ?></strong><span><?php echo wp_kses_post($order->get_formatted_order_total()); ?></span></a></td>
                            <td><span class="cg-delivery-board__date <?php echo $is_today ? 'is-today' : ($is_overdue ? 'is-overdue' : ''); ?>"><?php echo esc_html($is_overdue ? 'Просрочено · ' . $date_label : ($is_today ? 'Сегодня · ' . $date_label : $date_label)); ?></span><?php if ($row['time'] !== '') : ?><small><?php echo esc_html($row['time']); ?></small><?php endif; ?></td>
                            <td><strong><?php echo esc_html($name !== '' ? $name : 'Не указано'); ?></strong><?php if ($phone !== '') : ?><a class="cg-delivery-board__phone" href="<?php echo esc_url($tel); ?>"><?php echo esc_html($phone); ?></a><?php endif; ?></td>
                            <td><?php echo esc_html($row['city'] !== '' ? $row['city'] : 'Не указан'); ?></td>
                            <td><span class="cg-delivery-board__payment <?php echo $order->is_paid() ? 'is-paid' : 'is-unpaid'; ?>"><?php echo esc_html($order->is_paid() ? 'Оплачено' : 'Не оплачено'); ?></span><small><?php echo esc_html($order->get_payment_method_title() ?: 'Способ не указан'); ?></small></td>
                            <td><span class="cg-delivery-board__status is-<?php echo esc_attr($order->get_status()); ?>"><?php echo esc_html(wc_get_order_status_name($order->get_status())); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/** One compact summary above the existing order-readiness checks. */
function cg_delivery_launch_final_summary() {
    if (!is_admin() || !current_user_can('manage_woocommerce')) return;
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    if ($page !== 'cg-order-readiness') return;

    $technical = function_exists('cg_launch_checklist_items') ? (array) cg_launch_checklist_items() : [];
    $technical_done = count(array_filter($technical, static function ($item) { return !empty($item['ok']); }));

    $manual_items = function_exists('cg_order_readiness_manual_items') ? (array) cg_order_readiness_manual_items() : [];
    $manual_saved = function_exists('cg_order_readiness_saved_manual') ? (array) cg_order_readiness_saved_manual() : [];
    $manual_done = 0;
    foreach (array_keys($manual_items) as $key) if (!empty($manual_saved[$key])) $manual_done++;

    $catalog = function_exists('cg_catalog_quality_report') ? (array) cg_catalog_quality_report() : [];
    $catalog_score = isset($catalog['score']) ? (int) $catalog['score'] : 0;

    $from_name = trim((string) get_option('woocommerce_email_from_name', get_bloginfo('name')));
    $from_email = trim((string) get_option('woocommerce_email_from_address', get_option('admin_email')));
    $sender_ok = $from_name !== '' && is_email($from_email);

    $technical_ok = !$technical || $technical_done === count($technical);
    $manual_ok = !$manual_items || $manual_done === count($manual_items);
    $catalog_ok = $catalog_score >= 85;
    $ready = $technical_ok && $manual_ok && $catalog_ok && $sender_ok;

    echo '<section class="cg-final-launch-summary ' . ($ready ? 'is-ready' : 'needs-attention') . '">';
    echo '<div class="cg-final-launch-summary__head"><div><span>Общая картина</span><strong>' . ($ready ? 'Основные проверки закрыты' : 'До запуска осталось несколько пунктов') . '</strong></div><b>' . ($ready ? '✓' : '!') . '</b></div>';
    echo '<div class="cg-final-launch-summary__grid">';
    echo '<div><span>Техническая готовность</span><strong>' . esc_html($technical_done . '/' . count($technical)) . '</strong></div>';
    echo '<div><span>Реальный тест заказа</span><strong>' . esc_html($manual_done . '/' . count($manual_items)) . '</strong></div>';
    echo '<div><span>Качество каталога</span><strong>' . esc_html($catalog_score . '%') . '</strong></div>';
    echo '<div><span>Отправитель писем</span><strong>' . esc_html($sender_ok ? 'Готов' : 'Проверить') . '</strong></div>';
    echo '</div>';
    echo '<p>Фактическую доставку писем, оплату, VK и мобильное приложение всё равно подтверждаем только реальным тестовым заказом.</p>';
    echo '<div class="cg-final-launch-summary__links"><a href="' . esc_url(admin_url('themes.php?page=cg-launch-readiness')) . '">Техническая готовность</a><a href="' . esc_url(admin_url('admin.php?page=cg-catalog-quality')) . '">Качество каталога</a><a href="' . esc_url(admin_url('admin.php?page=cg-delivery-board')) . '">Заказы на доставку</a></div>';
    echo '</section>';
}
add_action('admin_notices', 'cg_delivery_launch_final_summary', 5);
