<?php
/**
 * Legal-commerce framework for the Цветочный город storefront.
 *
 * Creates draft legal pages, keeps seller details in one admin screen, connects
 * the public offer to WooCommerce terms after publication, and adds a concise
 * privacy notice to checkout.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

const CG_LEGAL_SETTINGS_OPTION = 'cg_legal_settings';
const CG_LEGAL_PAGES_OPTION = 'cg_legal_pages';

/** Defaults intentionally contain only already-public store contacts. */
function cg_legal_default_settings() {
    return [
        'legal_name' => '',
        'inn' => '',
        'ogrn' => '',
        'registration_address' => '',
        'claim_address' => get_theme_mod('cg_address', 'Нововоронеж, ул. Победы, 1Б'),
        'email' => 'florals-city@yandex.ru',
        'phone' => get_theme_mod('cg_phone', '+7 (930) 411-98-55'),
        'roskomnadzor_notified' => '0',
        'data_localized_ru' => '0',
        'offer_version' => '',
        'privacy_version' => '',
    ];
}

function cg_legal_get_settings() {
    $saved = get_option(CG_LEGAL_SETTINGS_OPTION, []);
    return wp_parse_args(is_array($saved) ? $saved : [], cg_legal_default_settings());
}

/** Fields that must be confirmed before the generated documents may be published. */
function cg_legal_missing_requirements() {
    $settings = cg_legal_get_settings();
    $required = [
        'legal_name' => 'Полное наименование продавца / ИП',
        'inn' => 'ИНН',
        'ogrn' => 'ОГРН / ОГРНИП',
        'registration_address' => 'Адрес регистрации продавца',
        'claim_address' => 'Адрес для претензий',
        'email' => 'E-mail для юридически значимых обращений',
        'phone' => 'Телефон продавца',
    ];

    $missing = [];
    foreach ($required as $key => $label) {
        if (trim((string) ($settings[$key] ?? '')) === '') {
            $missing[$key] = $label;
        }
    }

    if (($settings['roskomnadzor_notified'] ?? '0') !== '1') {
        $missing['roskomnadzor_notified'] = 'Подтверждение уведомления Роскомнадзора об обработке персональных данных';
    }

    if (($settings['data_localized_ru'] ?? '0') !== '1') {
        $missing['data_localized_ru'] = 'Подтверждение первичного хранения/обработки данных граждан РФ в базах на территории РФ';
    }

    return $missing;
}

function cg_legal_is_publish_ready() {
    return cg_legal_missing_requirements() === [];
}

/** Legal pages are created only as drafts and never auto-published. */
function cg_legal_page_definitions() {
    return [
        'privacy' => [
            'title' => 'Политика обработки персональных данных',
            'slug' => 'privacy-policy',
        ],
        'offer' => [
            'title' => 'Публичная оферта',
            'slug' => 'public-offer',
        ],
        'returns' => [
            'title' => 'Возврат и претензии',
            'slug' => 'returns-and-claims',
        ],
        'consent' => [
            'title' => 'Согласие на обработку персональных данных',
            'slug' => 'personal-data-consent',
        ],
    ];
}

/** Find a generated legal page by stored id or shortcode marker. */
function cg_legal_find_page($type) {
    $pages = get_option(CG_LEGAL_PAGES_OPTION, []);
    $page_id = isset($pages[$type]) ? absint($pages[$type]) : 0;
    if ($page_id && get_post($page_id)) return $page_id;

    $query = new WP_Query([
        'post_type' => 'page',
        'post_status' => ['publish', 'draft', 'private', 'pending'],
        'posts_per_page' => 1,
        's' => '[cg_legal_document type="' . $type . '"]',
        'fields' => 'ids',
        'no_found_rows' => true,
    ]);

    return !empty($query->posts) ? absint($query->posts[0]) : 0;
}

function cg_legal_ensure_pages() {
    if (!is_admin() || !current_user_can('manage_options')) return;

    $pages = get_option(CG_LEGAL_PAGES_OPTION, []);
    $changed = false;

    foreach (cg_legal_page_definitions() as $type => $definition) {
        $existing = cg_legal_find_page($type);
        if ($existing) {
            if (($pages[$type] ?? 0) !== $existing) {
                $pages[$type] = $existing;
                $changed = true;
            }
            continue;
        }

        $page_id = wp_insert_post([
            'post_type' => 'page',
            'post_status' => 'draft',
            'post_title' => $definition['title'],
            'post_name' => $definition['slug'],
            'post_content' => '[cg_legal_document type="' . $type . '"]',
            'comment_status' => 'closed',
        ], true);

        if (!is_wp_error($page_id)) {
            $pages[$type] = absint($page_id);
            $changed = true;
        }
    }

    if ($changed) update_option(CG_LEGAL_PAGES_OPTION, $pages, false);
}
add_action('admin_init', 'cg_legal_ensure_pages', 20);

/** Keep incomplete generated legal documents from accidentally becoming public. */
function cg_legal_guard_page_publication($data, $postarr) {
    if (($data['post_type'] ?? '') !== 'page' || ($data['post_status'] ?? '') !== 'publish') return $data;

    $post_id = isset($postarr['ID']) ? absint($postarr['ID']) : 0;
    if (!$post_id) return $data;

    $pages = array_map('absint', (array) get_option(CG_LEGAL_PAGES_OPTION, []));
    if (!in_array($post_id, $pages, true)) return $data;

    if (!cg_legal_is_publish_ready()) {
        $data['post_status'] = 'draft';
        set_transient('cg_legal_publish_blocked_' . get_current_user_id(), 1, MINUTE_IN_SECONDS);
    }

    return $data;
}
add_filter('wp_insert_post_data', 'cg_legal_guard_page_publication', 20, 2);

function cg_legal_publish_blocked_notice() {
    if (!current_user_can('manage_options')) return;
    if (!get_transient('cg_legal_publish_blocked_' . get_current_user_id())) return;

    delete_transient('cg_legal_publish_blocked_' . get_current_user_id());
    echo '<div class="notice notice-error"><p><strong>Юридический документ оставлен черновиком.</strong> Сначала заполните обязательные реквизиты и подтверждения в разделе «Внешний вид → Юридический блок».</p></div>';
}
add_action('admin_notices', 'cg_legal_publish_blocked_notice');

/** Assign WordPress/WooCommerce service pages only after they are deliberately published. */
function cg_legal_sync_service_page_assignments($post_id, $post, $update) {
    if (!$post instanceof WP_Post || $post->post_type !== 'page') return;

    $pages = (array) get_option(CG_LEGAL_PAGES_OPTION, []);
    if (($pages['privacy'] ?? 0) == $post_id && $post->post_status === 'publish') {
        update_option('wp_page_for_privacy_policy', absint($post_id));
    }

    if (($pages['offer'] ?? 0) == $post_id && $post->post_status === 'publish') {
        update_option('woocommerce_terms_page_id', absint($post_id));
    }
}
add_action('save_post_page', 'cg_legal_sync_service_page_assignments', 30, 3);

/** Published legal page URL, or empty string while the document is still a draft. */
function cg_legal_page_url($type, $published_only = true) {
    $page_id = cg_legal_find_page($type);
    if (!$page_id) return '';

    if ($published_only && get_post_status($page_id) !== 'publish') return '';
    return get_permalink($page_id) ?: '';
}

/** Admin screen. */
function cg_legal_admin_menu() {
    add_submenu_page(
        'themes.php',
        'Юридический блок',
        'Юридический блок',
        'manage_options',
        'cg-legal-commerce',
        'cg_legal_admin_page'
    );
}
add_action('admin_menu', 'cg_legal_admin_menu');

function cg_legal_admin_page() {
    if (!current_user_can('manage_options')) return;

    $settings = cg_legal_get_settings();
    $missing = cg_legal_missing_requirements();
    $pages = (array) get_option(CG_LEGAL_PAGES_OPTION, []);
    ?>
    <div class="wrap">
        <h1>Юридический блок интернет-магазина</h1>
        <p style="max-width:900px">Здесь хранятся реквизиты, которые автоматически подставляются в политику персональных данных, публичную оферту и правила возврата. Документы создаются как черновики и не могут быть опубликованы, пока обязательные данные не подтверждены.</p>

        <?php if (isset($_GET['updated'])) : ?>
            <div class="notice notice-success is-dismissible"><p>Настройки сохранены. Черновики юридических страниц созданы или обновлены динамически.</p></div>
        <?php endif; ?>

        <div style="max-width:980px;padding:18px 22px;margin:18px 0;border:1px solid <?php echo $missing ? '#dba7a7' : '#b8d7bd'; ?>;border-radius:12px;background:#fff;">
            <h2 style="margin-top:0"><?php echo $missing ? 'До публикации нужно заполнить' : 'Базовая готовность подтверждена'; ?></h2>
            <?php if ($missing) : ?>
                <ul style="list-style:disc;padding-left:20px">
                    <?php foreach ($missing as $label) : ?><li><?php echo esc_html($label); ?></li><?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p>Можно открыть черновики, проверить фактические условия бизнеса и после проверки опубликовать документы.</p>
            <?php endif; ?>
        </div>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="cg_legal_save_settings">
            <?php wp_nonce_field('cg_legal_save_settings'); ?>
            <table class="form-table" role="presentation">
                <tr><th><label for="cg_legal_name">Продавец</label></th><td><input class="regular-text" id="cg_legal_name" name="legal_name" value="<?php echo esc_attr($settings['legal_name']); ?>" placeholder="Например: ИП Иванов Иван Иванович"><p class="description">Полное наименование точно как в ЕГРИП/ЕГРЮЛ.</p></td></tr>
                <tr><th><label for="cg_legal_inn">ИНН</label></th><td><input class="regular-text" id="cg_legal_inn" name="inn" value="<?php echo esc_attr($settings['inn']); ?>" inputmode="numeric"></td></tr>
                <tr><th><label for="cg_legal_ogrn">ОГРН / ОГРНИП</label></th><td><input class="regular-text" id="cg_legal_ogrn" name="ogrn" value="<?php echo esc_attr($settings['ogrn']); ?>" inputmode="numeric"></td></tr>
                <tr><th><label for="cg_legal_registration_address">Адрес регистрации</label></th><td><textarea class="large-text" rows="2" id="cg_legal_registration_address" name="registration_address"><?php echo esc_textarea($settings['registration_address']); ?></textarea></td></tr>
                <tr><th><label for="cg_legal_claim_address">Адрес для претензий</label></th><td><textarea class="large-text" rows="2" id="cg_legal_claim_address" name="claim_address"><?php echo esc_textarea($settings['claim_address']); ?></textarea></td></tr>
                <tr><th><label for="cg_legal_email">E-mail</label></th><td><input class="regular-text" type="email" id="cg_legal_email" name="email" value="<?php echo esc_attr($settings['email']); ?>"></td></tr>
                <tr><th><label for="cg_legal_phone">Телефон</label></th><td><input class="regular-text" id="cg_legal_phone" name="phone" value="<?php echo esc_attr($settings['phone']); ?>"></td></tr>
                <tr><th>Роскомнадзор</th><td><label><input type="checkbox" name="roskomnadzor_notified" value="1" <?php checked($settings['roskomnadzor_notified'], '1'); ?>> Уведомление об обработке персональных данных подано / сведения оператора актуальны</label><p class="description">Для интернет-магазина с автоматизированным сбором данных это нужно проверить отдельно; отметка не отправляет уведомление автоматически.</p></td></tr>
                <tr><th>Локализация данных</th><td><label><input type="checkbox" name="data_localized_ru" value="1" <?php checked($settings['data_localized_ru'], '1'); ?>> Подтверждено, что сбор данных граждан РФ выполняется с соблюдением требований к базам данных на территории РФ</label><p class="description">Проверьте фактический хостинг, CRM, почту, платежные и иные сервисы до установки отметки.</p></td></tr>
                <tr><th><label for="cg_offer_version">Версия оферты</label></th><td><input class="regular-text" id="cg_offer_version" name="offer_version" value="<?php echo esc_attr($settings['offer_version']); ?>" placeholder="<?php echo esc_attr(wp_date('d.m.Y')); ?>"><p class="description">Если оставить пустым, при сохранении будет установлена текущая дата.</p></td></tr>
                <tr><th><label for="cg_privacy_version">Версия политики</label></th><td><input class="regular-text" id="cg_privacy_version" name="privacy_version" value="<?php echo esc_attr($settings['privacy_version']); ?>" placeholder="<?php echo esc_attr(wp_date('d.m.Y')); ?>"></td></tr>
            </table>
            <?php submit_button('Сохранить юридические настройки'); ?>
        </form>

        <h2>Черновики документов</h2>
        <table class="widefat striped" style="max-width:980px">
            <thead><tr><th>Документ</th><th>Статус</th><th>Действие</th></tr></thead>
            <tbody>
            <?php foreach (cg_legal_page_definitions() as $type => $definition) :
                $page_id = isset($pages[$type]) ? absint($pages[$type]) : 0;
                $status = $page_id ? get_post_status($page_id) : false;
                ?>
                <tr>
                    <td><?php echo esc_html($definition['title']); ?></td>
                    <td><?php echo esc_html($status ?: 'не создан'); ?></td>
                    <td><?php if ($page_id) : ?><a href="<?php echo esc_url(get_edit_post_link($page_id)); ?>">Открыть страницу</a><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div style="max-width:980px;margin-top:20px;padding:16px 20px;border-left:4px solid #dba617;background:#fff;">
            <strong>Важно перед публикацией</strong>
            <p>Проверьте реальные способы оплаты, договоры с сервисами, фактическое место хранения персональных данных, порядок работы с претензиями и статус оператора в реестре Роскомнадзора. Код не может подтвердить эти факты за владельца магазина.</p>
        </div>
    </div>
    <?php
}

function cg_legal_save_settings() {
    if (!current_user_can('manage_options')) wp_die('Недостаточно прав.');
    check_admin_referer('cg_legal_save_settings');

    $current = cg_legal_get_settings();
    $date = wp_date('d.m.Y');

    $settings = [
        'legal_name' => sanitize_text_field(wp_unslash($_POST['legal_name'] ?? '')),
        'inn' => preg_replace('/\D+/', '', (string) wp_unslash($_POST['inn'] ?? '')),
        'ogrn' => preg_replace('/\D+/', '', (string) wp_unslash($_POST['ogrn'] ?? '')),
        'registration_address' => sanitize_textarea_field(wp_unslash($_POST['registration_address'] ?? '')),
        'claim_address' => sanitize_textarea_field(wp_unslash($_POST['claim_address'] ?? '')),
        'email' => sanitize_email(wp_unslash($_POST['email'] ?? '')),
        'phone' => sanitize_text_field(wp_unslash($_POST['phone'] ?? '')),
        'roskomnadzor_notified' => isset($_POST['roskomnadzor_notified']) ? '1' : '0',
        'data_localized_ru' => isset($_POST['data_localized_ru']) ? '1' : '0',
        'offer_version' => sanitize_text_field(wp_unslash($_POST['offer_version'] ?? '')) ?: ($current['offer_version'] ?: $date),
        'privacy_version' => sanitize_text_field(wp_unslash($_POST['privacy_version'] ?? '')) ?: ($current['privacy_version'] ?: $date),
    ];

    update_option(CG_LEGAL_SETTINGS_OPTION, $settings, false);
    cg_legal_ensure_pages();

    wp_safe_redirect(add_query_arg('updated', '1', admin_url('themes.php?page=cg-legal-commerce')));
    exit;
}
add_action('admin_post_cg_legal_save_settings', 'cg_legal_save_settings');

/** Reusable seller details. */
function cg_legal_seller_details_html() {
    $s = cg_legal_get_settings();

    $rows = [
        'Продавец' => $s['legal_name'],
        'ИНН' => $s['inn'],
        'ОГРН / ОГРНИП' => $s['ogrn'],
        'Адрес регистрации' => $s['registration_address'],
        'Адрес для претензий' => $s['claim_address'],
        'E-mail' => $s['email'],
        'Телефон' => $s['phone'],
    ];

    $html = '<dl class="cg-legal-requisites">';
    foreach ($rows as $label => $value) {
        $value = trim((string) $value);
        if ($value === '') $value = 'Не заполнено';
        $html .= '<div><dt>' . esc_html($label) . '</dt><dd>' . esc_html($value) . '</dd></div>';
    }
    $html .= '</dl>';
    return $html;
}

function cg_legal_document_header($title, $version) {
    return '<header class="cg-legal-document__header"><span>Юридическая информация</span><h1>' . esc_html($title) . '</h1><p>Редакция от ' . esc_html($version ?: wp_date('d.m.Y')) . '</p></header>';
}

function cg_legal_privacy_html() {
    $s = cg_legal_get_settings();
    $version = $s['privacy_version'] ?: wp_date('d.m.Y');

    $html = cg_legal_document_header('Политика обработки персональных данных', $version);
    $html .= '<section><h2>1. Общие положения</h2><p>Настоящая Политика определяет порядок обработки и защиты персональных данных посетителей и покупателей интернет-магазина «Цветочный город». Оператором персональных данных является ' . esc_html($s['legal_name'] ?: 'продавец, реквизиты которого не заполнены') . '.</p>' . cg_legal_seller_details_html() . '</section>';
    $html .= '<section><h2>2. Какие данные обрабатываются</h2><p>В зависимости от действий пользователя могут обрабатываться имя, фамилия, номер телефона, адрес электронной почты, адрес доставки, сведения о заказе и оплате, данные учетной записи, обращения в магазин, а также технические данные, необходимые для работы сайта и обеспечения его безопасности.</p><p>Для доставки подарка покупатель может сообщить имя, телефон и адрес получателя. Такие сведения используются только в объеме, необходимом для исполнения заказа и связи по доставке.</p></section>';
    $html .= '<section><h2>3. Цели обработки</h2><ul><li>оформление, подтверждение, оплата и исполнение заказов;</li><li>организация доставки и связь с покупателем или получателем по заказу;</li><li>ведение личного кабинета и истории заказов;</li><li>исполнение требований законодательства, бухгалтерского и налогового учета;</li><li>рассмотрение обращений, претензий и возвратов;</li><li>обеспечение работоспособности и безопасности сайта.</li></ul></section>';
    $html .= '<section><h2>4. Правовые основания</h2><p>Обработка выполняется в случаях, допускаемых Федеральным законом № 152-ФЗ «О персональных данных»: в частности, когда она необходима для заключения и исполнения договора по инициативе покупателя, для выполнения обязанностей продавца по закону и, когда соответствующее основание действительно требуется, на основании отдельного согласия субъекта персональных данных.</p></section>';
    $html .= '<section><h2>5. Действия с персональными данными</h2><p>Оператор может осуществлять сбор, запись, систематизацию, накопление, хранение, уточнение, извлечение, использование, предоставление уполномоченным исполнителям, блокирование, удаление и уничтожение персональных данных в объеме, необходимом для заявленных целей.</p></section>';
    $html .= '<section><h2>6. Передача и обработчики</h2><p>Для исполнения заказа данные могут предоставляться организациям и сервисам, которые фактически участвуют в работе магазина: платежным организациям, службам доставки, поставщикам хостинга и ИТ-инфраструктуры, сервисам электронной почты и связи. Передается только объем данных, необходимый для соответствующей задачи, на основании закона или договорных отношений с оператором.</p></section>';
    $html .= '<section><h2>7. Хранение и локализация</h2><p>Персональные данные хранятся не дольше, чем этого требуют цели обработки, договор и обязательные сроки хранения документов, установленные законодательством. При сборе данных граждан Российской Федерации через Интернет оператор обеспечивает соблюдение установленных законом требований к использованию баз данных на территории Российской Федерации.</p></section>';
    $html .= '<section><h2>8. Cookies и технические данные</h2><p>Сайт использует технические файлы cookies, необходимые для корзины, оформления заказа, авторизации и сохранения пользовательской сессии. Подключение необязательных аналитических или рекламных технологий требует отдельной проверки их правового основания и настоящей Политики.</p></section>';
    $html .= '<section><h2>9. Права субъекта</h2><p>Субъект вправе запрашивать сведения об обработке своих данных, требовать их уточнения, блокирования или уничтожения при наличии предусмотренных законом оснований, а также отозвать ранее данное согласие, если обработка осуществлялась именно на основании согласия. Обращение направляется по адресу ' . esc_html($s['email'] ?: 'e-mail продавца') . ' или по адресу для претензий, указанному выше.</p></section>';
    $html .= '<section><h2>10. Защита данных и изменения Политики</h2><p>Оператор принимает правовые, организационные и технические меры, необходимые для защиты персональных данных. Новая редакция Политики применяется с даты ее публикации на сайте, если в ней не указано иное.</p></section>';
    return $html;
}

function cg_legal_offer_html() {
    $s = cg_legal_get_settings();
    $version = $s['offer_version'] ?: wp_date('d.m.Y');
    $delivery_url = function_exists('cg_delivery_payment_url') ? cg_delivery_payment_url() : home_url('/delivery/');
    $returns_url = cg_legal_page_url('returns', false);

    $html = cg_legal_document_header('Публичная оферта', $version);
    $html .= '<section><h2>1. Продавец и область действия</h2><p>Настоящий документ содержит условия дистанционной розничной продажи товаров через интернет-магазин «Цветочный город».</p>' . cg_legal_seller_details_html() . '</section>';
    $html .= '<section><h2>2. Товар и информация о нем</h2><p>Наименование, состав, цена и доступные характеристики товара указываются в карточке товара. Цветы являются природным товаром: оттенок, степень раскрытия бутонов и отдельные элементы композиции могут незначительно отличаться от фотографии при сохранении общего характера и стоимости заказа. Существенные замены состава согласуются с покупателем.</p></section>';
    $html .= '<section><h2>3. Оформление заказа</h2><p>Заказ оформляется через сайт или иным согласованным с продавцом способом. Покупатель обязан указать достоверные данные, необходимые для связи, оплаты и доставки. Отправляя заказ, покупатель выражает намерение приобрести выбранные товары на указанных условиях. Момент возникновения обязательств и заключения договора определяется законодательством и подтверждением заказа продавцом с учетом выбранного способа оплаты.</p></section>';
    $html .= '<section><h2>4. Цена и оплата</h2><p>Цена товара указывается в рублях. Стоимость доставки рассчитывается отдельно в зависимости от населенного пункта и условий, опубликованных на странице «Доставка и оплата». Доступные способы оплаты показываются покупателю при оформлении заказа.</p><p><a href="' . esc_url($delivery_url) . '">Открыть условия доставки и оплаты</a></p></section>';
    $html .= '<section><h2>5. Доставка</h2><p>Срок, дата и интервал доставки согласуются при оформлении заказа. Покупатель отвечает за корректность адреса и контактных данных. При передаче заказа получателю продавец вправе использовать сообщенные покупателем контактные данные только в целях исполнения доставки.</p></section>';
    $html .= '<section><h2>6. Качество, замены и естественные особенности цветов</h2><p>Продавец передает товар, соответствующий согласованному заказу и обязательным требованиям. Если отдельная позиция недоступна, изменение существенных элементов букета производится после согласования с покупателем. Естественные различия живых цветов, не ухудшающие качество и не меняющие согласованную концепцию композиции, сами по себе не являются недостатком.</p></section>';
    $html .= '<section><h2>7. Отказ от товара, возврат и претензии</h2><p>Права покупателя при дистанционной продаже, при выявлении недостатков и при отказе от товара определяются обязательными нормами законодательства о защите прав потребителей. Условия настоящей оферты не ограничивают права, которые не могут быть ограничены соглашением сторон.</p>';
    if ($returns_url) $html .= '<p><a href="' . esc_url($returns_url) . '">Подробнее о возврате и претензиях</a></p>';
    $html .= '</section>';
    $html .= '<section><h2>8. Персональные данные</h2><p>Данные, необходимые для оформления и исполнения заказа, обрабатываются в соответствии с Политикой обработки персональных данных. Если для отдельной цели закон требует согласия, такое согласие оформляется отдельно от оферты и иных подтверждаемых документов.</p></section>';
    $html .= '<section><h2>9. Претензии и связь</h2><p>Претензии можно направить на e-mail ' . esc_html($s['email'] ?: 'продавца') . ' либо по адресу: ' . esc_html($s['claim_address'] ?: 'адрес для претензий не заполнен') . '. Для идентификации заказа рекомендуется указать номер заказа, дату покупки и суть требования.</p></section>';
    $html .= '<section><h2>10. Заключительные положения</h2><p>К отношениям продавца и покупателя применяется законодательство Российской Федерации. Если отдельное положение оферты противоречит обязательной норме закона, применяется соответствующая норма закона.</p></section>';
    return $html;
}

function cg_legal_returns_html() {
    $s = cg_legal_get_settings();
    $html = cg_legal_document_header('Возврат и претензии', $s['offer_version'] ?: wp_date('d.m.Y'));
    $html .= '<section><h2>1. Заказы, оформленные дистанционно</h2><p>При дистанционной покупке применяются специальные правила статьи 26.1 Закона РФ «О защите прав потребителей» и Правил продажи товаров по договору розничной купли-продажи. Потребитель вправе отказаться от дистанционно заказанного товара до его передачи. После передачи применяются предусмотренные законом условия и сроки возврата с учетом характера конкретного товара.</p></section>';
    $html .= '<section><h2>2. Особенности цветов и индивидуальных композиций</h2><p>Растения включены в перечень непродовольственных товаров надлежащего качества, не подлежащих обмену по основаниям статьи 25 Закона о защите прав потребителей. Для дистанционной продажи дополнительно применяются правила статьи 26.1, включая исключение для товара надлежащего качества с индивидуально-определенными свойствами, который может использоваться исключительно приобретающим его потребителем. Поэтому вопрос о возврате надлежащего качества оценивается по конкретному заказу; настоящий документ не исключает права потребителя, прямо предусмотренные законом.</p></section>';
    $html .= '<section><h2>3. Товар ненадлежащего качества</h2><p>Если цветы или иной товар имеют недостатки, не оговоренные при продаже, покупатель вправе заявить требования, предусмотренные законодательством о защите прав потребителей. Ухудшение качества по вине продавца или привлеченного им курьера также может являться основанием для соответствующих требований.</p></section>';
    $html .= '<section><h2>4. Как направить претензию</h2><p>Претензию можно направить на e-mail ' . esc_html($s['email'] ?: 'продавца') . ' или по адресу: ' . esc_html($s['claim_address'] ?: 'адрес для претензий не заполнен') . '. Укажите номер заказа, дату, описание ситуации и требование. Фотографии товара могут ускорить рассмотрение, но их отсутствие само по себе не отменяет предусмотренные законом права.</p></section>';
    $html .= '<section><h2>5. Возврат денежных средств</h2><p>Если требование о возврате денежных средств подлежит удовлетворению, возврат производится в сроки и порядке, установленные законодательством и правилами соответствующего способа оплаты. При дистанционном отказе от товара надлежащего качества применяются установленные законом правила о расходах на его обратную доставку.</p></section>';
    return $html;
}

function cg_legal_consent_html() {
    $s = cg_legal_get_settings();
    $html = cg_legal_document_header('Согласие на обработку персональных данных', $s['privacy_version'] ?: wp_date('d.m.Y'));
    $html .= '<section><div class="cg-legal-callout"><strong>Этот документ не подменяет Политику и оферту.</strong><p>Он используется только в тех формах сайта, где обработка действительно осуществляется на основании отдельного согласия. Для данных, необходимых непосредственно для заключения и исполнения заказа, законом предусмотрены и другие правовые основания.</p></div></section>';
    $html .= '<section><h2>1. Оператор</h2>' . cg_legal_seller_details_html() . '</section>';
    $html .= '<section><h2>2. Предмет согласия</h2><p>Если пользователь самостоятельно устанавливает отдельную отметку о согласии в форме, ссылающейся на настоящий документ, он дает оператору конкретное и информированное согласие на обработку данных, перечисленных в соответствующей форме, для указанной рядом с этой формой цели.</p></section>';
    $html .= '<section><h2>3. Действия с данными</h2><p>В пределах заявленной цели могут выполняться сбор, запись, систематизация, накопление, хранение, уточнение, извлечение, использование, предоставление лицам, привлекаемым оператором для достижения этой цели, блокирование, удаление и уничтожение данных.</p></section>';
    $html .= '<section><h2>4. Срок и отзыв</h2><p>Согласие действует до достижения указанной в форме цели, истечения установленного для нее срока либо до его отзыва, если оператор не вправе продолжить обработку на ином законном основании. Отзыв можно направить на ' . esc_html($s['email'] ?: 'e-mail оператора') . '.</p></section>';
    return $html;
}

function cg_legal_document_shortcode($atts) {
    $atts = shortcode_atts(['type' => 'privacy'], $atts, 'cg_legal_document');
    $type = sanitize_key($atts['type']);

    $renderers = [
        'privacy' => 'cg_legal_privacy_html',
        'offer' => 'cg_legal_offer_html',
        'returns' => 'cg_legal_returns_html',
        'consent' => 'cg_legal_consent_html',
    ];

    if (!isset($renderers[$type])) return '';

    wp_enqueue_style('cg-legal-pages');
    $html = '<article class="cg-legal-document">';

    if (!cg_legal_is_publish_ready() && current_user_can('manage_options')) {
        $html .= '<div class="cg-legal-admin-warning"><strong>Предпросмотр черновика:</strong> юридические реквизиты или обязательные подтверждения еще не заполнены. Страница не будет опубликована, пока настройка не завершена.</div>';
    }

    $html .= call_user_func($renderers[$type]);
    $html .= '</article>';
    return $html;
}
add_shortcode('cg_legal_document', 'cg_legal_document_shortcode');

function cg_legal_register_assets() {
    $path = get_template_directory() . '/assets/css/legal-pages.css';
    wp_register_style(
        'cg-legal-pages',
        get_template_directory_uri() . '/assets/css/legal-pages.css',
        [],
        file_exists($path) ? filemtime($path) : wp_get_theme()->get('Version')
    );

    if (function_exists('is_checkout') && is_checkout()) {
        wp_enqueue_style('cg-legal-pages');
    }
}
add_action('wp_enqueue_scripts', 'cg_legal_register_assets', 35);

/**
 * Checkout privacy transparency.
 *
 * Core order data is normally processed because it is needed to conclude and
 * perform the purchase/delivery contract. We therefore do not add a redundant
 * pre-ticked or bundled "consent" checkbox. If a future form relies on consent
 * (e.g. marketing), it must use a separate explicit consent referring to the
 * dedicated consent document.
 */
function cg_legal_checkout_privacy_notice() {
    $privacy_url = cg_legal_page_url('privacy');
    if (!$privacy_url) return;

    echo '<div class="cg-checkout-legal-notice">Персональные данные используются для оформления и исполнения заказа в соответствии с <a href="' . esc_url($privacy_url) . '" target="_blank" rel="noopener">Политикой обработки персональных данных</a>.</div>';
}
add_action('woocommerce_review_order_before_submit', 'cg_legal_checkout_privacy_notice', 7);

/** Make WooCommerce's native Terms checkbox explicitly point to the public offer. */
function cg_legal_terms_checkbox_text($text) {
    $offer_url = cg_legal_page_url('offer');
    if (!$offer_url) return $text;

    return 'Я принимаю <a href="' . esc_url($offer_url) . '" class="woocommerce-terms-and-conditions-link" target="_blank" rel="noopener">условия публичной оферты</a>';
}
add_filter('woocommerce_get_terms_and_conditions_checkbox_text', 'cg_legal_terms_checkbox_text', 20);

/** Keep a lightweight order-side audit trail of the legal document versions. */
function cg_legal_record_order_versions($order, $data) {
    if (!$order instanceof WC_Order) return;

    $settings = cg_legal_get_settings();
    $order->update_meta_data('_cg_privacy_version', $settings['privacy_version'] ?: wp_date('d.m.Y'));

    if (!empty($_POST['terms'])) {
        $order->update_meta_data('_cg_offer_accepted', 'yes');
        $order->update_meta_data('_cg_offer_version', $settings['offer_version'] ?: wp_date('d.m.Y'));
        $order->update_meta_data('_cg_offer_accepted_at', gmdate('c'));
    }
}
add_action('woocommerce_checkout_create_order', 'cg_legal_record_order_versions', 30, 2);
