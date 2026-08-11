<?php
/**
 * Customer care and returns memo for flower orders.
 *
 * Adds a printable memo, customer-email copy and a compact order-details card.
 * The printed copy is intended to be handed over with every distance order.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

const CG_BUYER_MEMO_VERSION = '11.08.2026';

/** Contact data used in the memo. */
function cg_buyer_memo_contacts() {
    $legal = function_exists('cg_legal_get_settings') ? cg_legal_get_settings() : [];

    $email = !empty($legal['email']) ? sanitize_email($legal['email']) : 'florals-city@yandex.ru';
    $phone = !empty($legal['phone']) ? (string) $legal['phone'] : get_theme_mod('cg_phone', '+7 (930) 411-98-55');
    $address = !empty($legal['claim_address'])
        ? (string) $legal['claim_address']
        : get_theme_mod('cg_address', 'Нововоронеж, ул. Победы, 1Б');

    return [
        'email' => $email,
        'phone' => $phone,
        'address' => $address,
        'hours' => get_theme_mod('cg_worktime', 'Ежедневно с 07:00 до 21:00'),
    ];
}

/** Public link to the full Returns & Claims page, if it has been published. */
function cg_buyer_memo_returns_url() {
    return function_exists('cg_legal_page_url') ? (string) cg_legal_page_url('returns') : '';
}

/** Public noindex URL of the printable memo. */
function cg_buyer_memo_print_url() {
    return add_query_arg('cg_buyer_memo', '1', home_url('/'));
}

/** Compact memo body shared by customer-facing surfaces. */
function cg_buyer_memo_html($context = 'site') {
    $contacts = cg_buyer_memo_contacts();
    $returns_url = cg_buyer_memo_returns_url();
    $print_url = cg_buyer_memo_print_url();

    ob_start();
    ?>
    <div class="cg-buyer-memo cg-buyer-memo--<?php echo esc_attr($context); ?>">
        <div class="cg-buyer-memo__head">
            <div>
                <span>Цветочный город · памятка покупателю</span>
                <h3>Как сохранить букет свежим и что делать, если есть проблема</h3>
            </div>
            <strong>Редакция <?php echo esc_html(CG_BUYER_MEMO_VERSION); ?></strong>
        </div>

        <div class="cg-buyer-memo__notice">
            <b>При получении осмотрите букет.</b>
            Проверьте свежесть, количество и вид цветов, отсутствие сломанных стеблей и явных повреждений. Если видимый недостаток есть уже при вручении, сообщите магазину как можно быстрее и по возможности сделайте фотографии.
        </div>

        <div class="cg-buyer-memo__grid">
            <section>
                <h4>5 правил ухода</h4>
                <ol>
                    <li><b>Вода.</b> Поставьте букет в чистую прохладную воду как можно скорее. Для композиции на флористической губке регулярно поддерживайте губку влажной.</li>
                    <li><b>Чистота.</b> Используйте чистую вазу, меняйте воду регулярно и убирайте листья, оказавшиеся ниже уровня воды.</li>
                    <li><b>Срез.</b> Для букета в вазе обновляйте срез стеблей чистым острым инструментом, если это подходит конкретным цветам.</li>
                    <li><b>Прохлада.</b> Не ставьте цветы на прямое солнце, у батареи, обогревателя, кондиционера, на сильный сквозняк или мороз.</li>
                    <li><b>Без перегрева.</b> Не оставляйте букет надолго в душной машине, у окна на солнце и рядом с другими источниками сильного тепла.</li>
                </ol>
            </section>

            <section>
                <h4>Возврат и претензии</h4>
                <ul>
                    <li>При дистанционном заказе от товара можно отказаться <b>до его передачи</b>.</li>
                    <li>После передачи от товара надлежащего качества при дистанционной продаже можно отказаться <b>в течение 7 дней</b>, если сохранены его товарный вид и потребительские свойства.</li>
                    <li>Если письменная информация о порядке и сроках возврата не была предоставлена при доставке, срок отказа от товара надлежащего качества составляет <b>3 месяца</b> с момента передачи.</li>
                    <li>Увядание или повреждение после передачи из-за отсутствия воды, прямого солнца, батареи, жары, мороза, сквозняка, неправильной перевозки получателем или иного ненадлежащего ухода <b>само по себе не означает недостаток товара, существовавший при вручении</b>.</li>
                    <li>Если недостаток существовал при передаче или возник по причинам, существовавшим до неё, применяются права покупателя, предусмотренные законодательством РФ.</li>
                </ul>
            </section>
        </div>

        <div class="cg-buyer-memo__claim">
            <h4>Как обратиться</h4>
            <p>Сообщите номер заказа, дату и время получения, опишите проблему и приложите фотографии букета целиком и проблемных цветов, сделанные как можно раньше после обнаружения.</p>
            <p><b>E-mail:</b> <?php echo esc_html($contacts['email']); ?> · <b>Телефон:</b> <?php echo esc_html($contacts['phone']); ?> · <b>Адрес возврата/обращений:</b> <?php echo esc_html($contacts['address']); ?> · <b>Режим работы:</b> <?php echo esc_html($contacts['hours']); ?></p>
            <p>Если требование о возврате денежных средств подлежит удовлетворению, деньги возвращаются в порядке, предусмотренном законом, не позднее 10 дней со дня предъявления соответствующего требования.</p>
        </div>

        <div class="cg-buyer-memo__links">
            <?php if ($returns_url) : ?>
                <a href="<?php echo esc_url($returns_url); ?>">Полные правила возврата и претензий</a>
            <?php endif; ?>
            <?php if ($context !== 'print') : ?>
                <a href="<?php echo esc_url($print_url); ?>" target="_blank" rel="noopener">Открыть печатную памятку</a>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return (string) ob_get_clean();
}

/** Plain-text version for WooCommerce plain-text emails. */
function cg_buyer_memo_plain_text() {
    $contacts = cg_buyer_memo_contacts();
    $returns_url = cg_buyer_memo_returns_url();

    $lines = [
        '',
        'ПАМЯТКА ПОКУПАТЕЛЮ — «ЦВЕТОЧНЫЙ ГОРОД»',
        'Редакция ' . CG_BUYER_MEMO_VERSION,
        '',
        'ПРИ ПОЛУЧЕНИИ: осмотрите свежесть, состав и отсутствие видимых повреждений. Если недостаток заметен при вручении, сообщите магазину как можно быстрее и по возможности сделайте фотографии.',
        '',
        'КАК УХАЖИВАТЬ:',
        '1. Как можно скорее поставьте букет в чистую прохладную воду; флористическую губку поддерживайте влажной.',
        '2. Используйте чистую вазу, регулярно меняйте воду, не оставляйте листья ниже уровня воды.',
        '3. При необходимости обновляйте срез стеблей чистым острым инструментом.',
        '4. Держите цветы вдали от прямого солнца, батарей, обогревателей, сильного холода, кондиционеров и сквозняков.',
        '5. Не оставляйте букет надолго в душной машине или другом перегретом месте.',
        '',
        'ВОЗВРАТ И ПРЕТЕНЗИИ:',
        '- При дистанционном заказе от товара можно отказаться до его передачи.',
        '- После передачи от товара надлежащего качества при дистанционной продаже можно отказаться в течение 7 дней при сохранении товарного вида и потребительских свойств.',
        '- Если письменную информацию о порядке и сроках возврата не предоставили при доставке, срок отказа составляет 3 месяца с момента передачи.',
        '- Ухудшение после передачи из-за отсутствия воды, солнца, батареи, жары, мороза, сквозняка или неправильного ухода само по себе не означает недостаток товара, существовавший при вручении.',
        '- Если недостаток существовал при передаче или возник по причинам, существовавшим до неё, действуют права покупателя по законодательству РФ.',
        '',
        'Для обращения укажите номер заказа, дату и время получения, описание проблемы и по возможности приложите фотографии.',
        'E-mail: ' . $contacts['email'],
        'Телефон: ' . $contacts['phone'],
        'Адрес возврата/обращений: ' . $contacts['address'],
        'Режим работы: ' . $contacts['hours'],
        'Если требование о возврате денег подлежит удовлетворению, возврат производится не позднее 10 дней со дня предъявления соответствующего требования.',
    ];

    if ($returns_url) $lines[] = 'Полные правила: ' . $returns_url;
    $lines[] = 'Печатная памятка: ' . cg_buyer_memo_print_url();
    $lines[] = '';

    return implode("\n", $lines);
}

/** Add the memo to the main customer order emails. */
function cg_buyer_memo_email($order, $sent_to_admin, $plain_text, $email) {
    if ($sent_to_admin || !$order instanceof WC_Order || !is_object($email)) return;

    $email_id = isset($email->id) ? (string) $email->id : '';
    $allowed = ['customer_processing_order', 'customer_completed_order', 'customer_on_hold_order'];
    if (!in_array($email_id, $allowed, true)) return;

    if ($plain_text) {
        echo cg_buyer_memo_plain_text(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        return;
    }

    echo '<div style="margin:28px 0 8px;padding:20px;border:1px solid #ead7d3;border-radius:16px;background:#fff8f5;color:#493a36;">';
    echo '<h2 style="margin:0 0 12px;font-size:20px;line-height:1.25;color:#4b3733;">Памятка по уходу за букетом</h2>';
    echo '<p style="margin:0 0 12px;line-height:1.65;"><strong>При получении осмотрите букет.</strong> Если видимый недостаток есть уже при вручении, сообщите нам как можно быстрее и по возможности сделайте фотографии.</p>';
    echo '<ul style="margin:0 0 12px 18px;padding:0;line-height:1.65;">';
    echo '<li>поставьте букет в чистую прохладную воду как можно скорее;</li>';
    echo '<li>не держите цветы на прямом солнце, у батареи, обогревателя, на морозе или сильном сквозняке;</li>';
    echo '<li>регулярно меняйте воду; флористическую губку поддерживайте влажной;</li>';
    echo '<li>после передачи от товара надлежащего качества при дистанционной продаже можно отказаться в течение 7 дней при сохранении товарного вида и потребительских свойств;</li>';
    echo '<li>если письменную информацию о возврате не предоставили при доставке, срок отказа составляет 3 месяца;</li>';
    echo '<li>ухудшение после передачи из-за неправильного хранения или ухода само по себе не является недостатком, существовавшим при вручении.</li>';
    echo '</ul>';
    echo '<p style="margin:0;line-height:1.6;"><a href="' . esc_url(cg_buyer_memo_print_url()) . '">Открыть полную памятку покупателю</a></p>';
    echo '</div>';
}
add_action('woocommerce_email_after_order_table', 'cg_buyer_memo_email', 25, 4);

/** Show the memo in the customer's order details / thank-you order details. */
function cg_buyer_memo_order_details($order) {
    if (!$order instanceof WC_Order) return;
    echo cg_buyer_memo_html('order'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action('woocommerce_order_details_after_order_table', 'cg_buyer_memo_order_details', 30);

/** Record which memo revision was active when an order was created. */
function cg_buyer_memo_record_version($order, $data) {
    if (!$order instanceof WC_Order) return;
    $order->update_meta_data('_cg_buyer_memo_version', CG_BUYER_MEMO_VERSION);
}
add_action('woocommerce_checkout_create_order', 'cg_buyer_memo_record_version', 35, 2);

/** Display the active memo revision in the order admin without claiming it was physically handed over. */
function cg_buyer_memo_admin_order_version($order) {
    if (!$order instanceof WC_Order) return;
    $version = (string) $order->get_meta('_cg_buyer_memo_version');
    if ($version === '') $version = CG_BUYER_MEMO_VERSION;

    echo '<p><strong>Памятка покупателю:</strong> редакция ' . esc_html($version) . '<br><a href="' . esc_url(cg_buyer_memo_print_url()) . '" target="_blank" rel="noopener">Открыть для печати</a></p>';
}
add_action('woocommerce_admin_order_data_after_order_details', 'cg_buyer_memo_admin_order_version', 25);

/** Front-end styles for the order-details card. */
function cg_buyer_memo_assets() {
    if (!class_exists('WooCommerce')) return;

    $is_order_screen = is_order_received_page() || is_wc_endpoint_url('view-order');
    if (!$is_order_screen) return;

    $path = get_template_directory() . '/assets/css/customer-buyer-memo.css';
    wp_enqueue_style(
        'cg-customer-buyer-memo',
        get_template_directory_uri() . '/assets/css/customer-buyer-memo.css',
        [],
        file_exists($path) ? filemtime($path) : wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'cg_buyer_memo_assets', 80);

/** Manager screen with a one-click printable version. */
function cg_buyer_memo_admin_menu() {
    add_submenu_page(
        'woocommerce',
        'Памятка покупателю',
        'Памятка покупателю',
        'manage_woocommerce',
        'cg-buyer-memo',
        'cg_buyer_memo_admin_page'
    );
}
add_action('admin_menu', 'cg_buyer_memo_admin_menu', 40);

function cg_buyer_memo_admin_page() {
    if (!current_user_can('manage_woocommerce')) return;
    $print_url = cg_buyer_memo_print_url();
    ?>
    <div class="wrap">
        <h1>Памятка покупателю</h1>
        <p style="max-width:900px;font-size:14px;line-height:1.65">Памятка автоматически добавляется в клиентские письма WooCommerce и отображается в деталях заказа. Для дистанционных заказов рекомендуется также <strong>распечатывать и передавать памятку вместе с букетом</strong>, чтобы письменная информация о порядке и сроках возврата была у получателя при доставке.</p>
        <p><a class="button button-primary" href="<?php echo esc_url($print_url); ?>" target="_blank" rel="noopener">Открыть печатную версию</a></p>
        <div style="max-width:980px;margin-top:22px;padding:18px;border:1px solid #ddd;background:#fff;border-radius:12px">
            <h2 style="margin-top:0">Что получает покупатель</h2>
            <ul style="list-style:disc;padding-left:20px;line-height:1.7">
                <li>правила ухода за букетом и композицией;</li>
                <li>просьбу проверить состояние цветов при вручении;</li>
                <li>краткий порядок дистанционного возврата;</li>
                <li>пояснение о неправильном хранении после передачи;</li>
                <li>контакты и порядок подачи претензии.</li>
            </ul>
            <p><strong>Важно:</strong> наличие памятки в письме не заменяет фактическую выдачу письменной информации при доставке. Печатная версия сделана именно для вложения в заказ.</p>
        </div>
    </div>
    <?php
}

/** Standalone A5-friendly printable view. */
function cg_buyer_memo_print_view() {
    if (!isset($_GET['cg_buyer_memo']) || sanitize_text_field(wp_unslash($_GET['cg_buyer_memo'])) !== '1') return;

    nocache_headers();
    status_header(200);
    header('X-Robots-Tag: noindex, nofollow', true);
    header('Content-Type: text/html; charset=' . get_bloginfo('charset'));

    $brand = get_theme_mod('cg_brand_title', 'Цветочный город');
    ?>
    <!doctype html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Памятка покупателю — <?php echo esc_html($brand); ?></title>
        <style>
            *{box-sizing:border-box}html,body{margin:0;padding:0;background:#f6efec;color:#342b28;font-family:Arial,sans-serif}body{padding:24px}.cg-print-actions{max-width:148mm;margin:0 auto 14px;display:flex;gap:8px}.cg-print-actions button{padding:10px 16px;border:0;border-radius:999px;background:#b86e78;color:#fff;font-size:14px;cursor:pointer}.cg-print-sheet{width:min(100%,148mm);margin:0 auto;padding:10mm;background:#fff;border:1px solid #ead8d3;border-radius:18px;box-shadow:0 18px 50px rgba(73,47,44,.12)}.cg-print-brand{margin-bottom:6mm;color:#a65f68;font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase}.cg-buyer-memo__head{display:flex;justify-content:space-between;gap:14px;margin-bottom:5mm}.cg-buyer-memo__head span{display:none}.cg-buyer-memo__head h3{margin:0;color:#3f302d;font-family:Georgia,serif;font-size:22px;line-height:1.12}.cg-buyer-memo__head strong{font-size:9px;color:#8a7470;white-space:nowrap}.cg-buyer-memo__notice{margin-bottom:5mm;padding:3.5mm;border-radius:10px;background:#fff2ee;font-size:10.5px;line-height:1.45}.cg-buyer-memo__grid{display:grid;grid-template-columns:1fr 1fr;gap:5mm}.cg-buyer-memo section{break-inside:avoid}.cg-buyer-memo h4{margin:0 0 2.5mm;color:#684b47;font-size:13px}.cg-buyer-memo ol,.cg-buyer-memo ul{margin:0;padding-left:5mm;font-size:9.6px;line-height:1.45}.cg-buyer-memo li{margin-bottom:1.6mm}.cg-buyer-memo__claim{margin-top:4mm;padding-top:3.5mm;border-top:1px solid #eadbd7}.cg-buyer-memo__claim h4{margin:0 0 1.5mm;color:#684b47;font-size:12px}.cg-buyer-memo__claim p{margin:0 0 1.4mm;font-size:9.5px;line-height:1.45}.cg-buyer-memo__links{display:flex;gap:4mm;flex-wrap:wrap;margin-top:3mm;font-size:9.5px}.cg-buyer-memo__links a{color:#985c65}.cg-print-note{margin-top:4mm;padding-top:3mm;border-top:1px dashed #dfcbc6;color:#7d6964;font-size:8.8px;line-height:1.35}@page{size:A5 portrait;margin:6mm}@media print{html,body{background:#fff}body{padding:0}.cg-print-actions{display:none}.cg-print-sheet{width:auto;max-width:none;margin:0;padding:4mm;border:0;border-radius:0;box-shadow:none}.cg-buyer-memo__head h3{font-size:19px}.cg-buyer-memo__notice{padding:2.5mm}.cg-buyer-memo__grid{gap:4mm}.cg-buyer-memo ol,.cg-buyer-memo ul{font-size:8.7px}.cg-buyer-memo__claim p{font-size:8.7px}.cg-print-note{font-size:8px}}
        </style>
    </head>
    <body>
        <div class="cg-print-actions"><button type="button" onclick="window.print()">Распечатать памятку</button></div>
        <main class="cg-print-sheet">
            <div class="cg-print-brand"><?php echo esc_html($brand); ?></div>
            <?php echo cg_buyer_memo_html('print'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <div class="cg-print-note">Эту памятку рекомендуется передавать вместе с дистанционно заказанным букетом. Она не отменяет права покупателя и обязанности продавца, установленные законодательством РФ.</div>
        </main>
    </body>
    </html>
    <?php
    exit;
}
add_action('template_redirect', 'cg_buyer_memo_print_view', 0);
