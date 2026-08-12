<?php
/**
 * Pre-launch order-flow diagnostics and a manual end-to-end test checklist.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

const CG_ORDER_READINESS_OPTION = 'cg_order_readiness_manual_checks';

/** Manual checks that can only be confirmed after a real storefront order. */
function cg_order_readiness_manual_items() {
    return [
        'desktop_order' => 'Оформлен контрольный заказ с компьютера',
        'mobile_order' => 'Оформлен контрольный заказ с телефона',
        'delivery_total' => 'Населённый пункт и стоимость доставки совпали в Checkout и заказе',
        'customer_email' => 'Клиентское письмо WooCommerce реально пришло',
        'vk_message' => 'Уведомление о новом заказе реально пришло во ВКонтакте',
        'mobile_app_fields' => 'В приложении WooCommerce видны поля «ЦГ — …»',
        'status_sync' => 'Изменение статуса заказа не теряет данные доставки и отправителя',
    ];
}

function cg_order_readiness_saved_manual() {
    $saved = get_option(CG_ORDER_READINESS_OPTION, []);
    return is_array($saved) ? array_map('boolval', $saved) : [];
}

/** Store only known checkbox keys. */
function cg_order_readiness_handle_manual_save() {
    if (!is_admin() || !current_user_can('manage_woocommerce')) return;
    if (empty($_POST['cg_order_readiness_save'])) return;

    check_admin_referer('cg_order_readiness_save');

    $known = cg_order_readiness_manual_items();
    $posted = isset($_POST['cg_manual']) && is_array($_POST['cg_manual'])
        ? wp_unslash($_POST['cg_manual'])
        : [];
    $clean = [];

    foreach ($known as $key => $label) {
        $clean[$key] = isset($posted[$key]) && (string) $posted[$key] === '1';
    }

    update_option(CG_ORDER_READINESS_OPTION, $clean, false);
    add_settings_error('cg_order_readiness', 'saved', 'Контрольный чек-лист сохранён.', 'updated');
}
add_action('admin_init', 'cg_order_readiness_handle_manual_save');

/** Small normalized diagnostic item. */
function cg_order_readiness_item($label, $level, $details) {
    return [
        'label' => (string) $label,
        'level' => in_array($level, ['ok', 'warn', 'error'], true) ? $level : 'warn',
        'details' => (string) $details,
    ];
}

/** Check whether a WooCommerce attribute with the requested slug exists. */
function cg_order_readiness_has_attribute($slug) {
    if (!function_exists('wc_get_attribute_taxonomies')) return false;

    foreach ((array) wc_get_attribute_taxonomies() as $attribute) {
        if (!is_object($attribute)) continue;
        if (sanitize_title((string) ($attribute->attribute_name ?? '')) === sanitize_title($slug)) return true;
    }

    return false;
}

/** Build automatic checks without creating orders or making external requests. */
function cg_order_readiness_automatic_checks() {
    $checks = [];

    if (!class_exists('WooCommerce') || !function_exists('WC')) {
        return [cg_order_readiness_item('WooCommerce', 'error', 'WooCommerce не активен — оформление заказа недоступно.')];
    }

    $checks[] = cg_order_readiness_item('WooCommerce', 'ok', 'WooCommerce активен.');

    foreach ([
        'shop' => 'Каталог',
        'cart' => 'Корзина',
        'checkout' => 'Оформление заказа',
        'myaccount' => 'Личный кабинет',
    ] as $key => $label) {
        $page_id = function_exists('wc_get_page_id') ? (int) wc_get_page_id($key) : 0;
        $published = $page_id > 0 && get_post_status($page_id) === 'publish';
        $checks[] = cg_order_readiness_item(
            $label . ' WooCommerce',
            $published ? 'ok' : 'error',
            $published ? 'Страница назначена и опубликована.' : 'Страница не назначена или не опубликована.'
        );
    }

    $checkout_template = get_template_directory() . '/woocommerce/checkout/form-checkout.php';
    $checks[] = cg_order_readiness_item(
        'Кастомный Checkout темы',
        file_exists($checkout_template) ? 'ok' : 'error',
        file_exists($checkout_template) ? 'Шаблон оформления заказа темы найден.' : 'Файл кастомного Checkout темы не найден.'
    );

    $checkout_logic_ok = function_exists('cg_render_checkout_delivery_selector')
        && function_exists('cg_final_polish_validate_checkout_contacts');
    $checks[] = cg_order_readiness_item(
        'Поля получателя, отправителя и доставки',
        $checkout_logic_ok ? 'ok' : 'error',
        $checkout_logic_ok ? 'Кастомные поля и финальная проверка телефонов подключены.' : 'Часть кастомной логики Checkout не загружена.'
    );

    $zones = function_exists('cg_get_delivery_zones') ? (array) cg_get_delivery_zones() : [];
    $checks[] = cg_order_readiness_item(
        'Населённые пункты доставки',
        !empty($zones) ? 'ok' : 'error',
        !empty($zones) ? 'Настроено вариантов доставки: ' . count($zones) . '.' : 'Список населённых пунктов/тарифов пуст или модуль не загружен.'
    );

    $enabled_gateways = [];
    if (WC()->payment_gateways()) {
        foreach ((array) WC()->payment_gateways()->payment_gateways() as $gateway) {
            if (!is_object($gateway) || ($gateway->enabled ?? 'no') !== 'yes') continue;
            $enabled_gateways[] = wp_strip_all_tags((string) $gateway->get_title());
        }
    }
    $checks[] = cg_order_readiness_item(
        'Способы оплаты',
        $enabled_gateways ? 'ok' : 'error',
        $enabled_gateways ? 'Включено: ' . implode(', ', $enabled_gateways) . '.' : 'Нет ни одного включённого способа оплаты.'
    );

    $emails = WC()->mailer() ? (array) WC()->mailer()->get_emails() : [];
    $new_order_enabled = isset($emails['WC_Email_New_Order']) && $emails['WC_Email_New_Order']->is_enabled();
    $customer_email_enabled = false;
    foreach (['WC_Email_Customer_Processing_Order', 'WC_Email_Customer_On_Hold_Order', 'WC_Email_Customer_Completed_Order'] as $email_key) {
        if (isset($emails[$email_key]) && $emails[$email_key]->is_enabled()) {
            $customer_email_enabled = true;
            break;
        }
    }
    $checks[] = cg_order_readiness_item(
        'Письмо о новом заказе магазину',
        $new_order_enabled ? 'ok' : 'warn',
        $new_order_enabled ? 'WooCommerce-уведомление «Новый заказ» включено.' : 'Уведомление «Новый заказ» выключено. Проверьте настройки писем WooCommerce.'
    );
    $checks[] = cg_order_readiness_item(
        'Клиентские письма WooCommerce',
        $customer_email_enabled ? 'ok' : 'warn',
        $customer_email_enabled ? 'Есть включённое клиентское письмо по статусу заказа.' : 'Не найдено включённых основных клиентских писем по заказу.'
    );

    $vk_loaded = function_exists('cg_vk_notifications_enabled') && function_exists('cg_vk_configuration_error');
    if (!$vk_loaded) {
        $checks[] = cg_order_readiness_item('Уведомления ВКонтакте', 'error', 'Модуль VK не загружен.');
    } elseif (!cg_vk_notifications_enabled()) {
        $checks[] = cg_order_readiness_item('Уведомления ВКонтакте', 'warn', 'Модуль загружен, но уведомления выключены.');
    } else {
        $vk_error = cg_vk_configuration_error(false);
        $checks[] = cg_order_readiness_item(
            'Уведомления ВКонтакте',
            is_wp_error($vk_error) ? 'error' : 'ok',
            is_wp_error($vk_error) ? $vk_error->get_error_message() : 'Уведомления включены, токен и ID получателя заполнены.'
        );
    }

    $mobile_fields_ok = function_exists('cg_mobile_order_sync_visible_fields')
        && has_action('woocommerce_checkout_create_order', 'cg_mobile_order_sync_checkout_fields');
    $checks[] = cg_order_readiness_item(
        'Поля для мобильного WooCommerce',
        $mobile_fields_ok ? 'ok' : 'error',
        $mobile_fields_ok ? 'Зеркалирование полей «ЦГ — …» подключено к созданию заказа.' : 'Модуль полей для мобильного приложения не подключён полностью.'
    );

    $privacy_id = (int) get_option('wp_page_for_privacy_policy');
    $privacy_ok = $privacy_id > 0 && get_post_status($privacy_id) === 'publish';
    $checks[] = cg_order_readiness_item(
        'Политика обработки персональных данных',
        $privacy_ok ? 'ok' : 'warn',
        $privacy_ok ? 'Политика назначена и опубликована.' : 'Политика ещё не назначена или не опубликована.'
    );

    $terms_id = (int) get_option('woocommerce_terms_page_id');
    $terms_ok = $terms_id > 0 && get_post_status($terms_id) === 'publish';
    $checks[] = cg_order_readiness_item(
        'Публичная оферта в Checkout',
        $terms_ok ? 'ok' : 'warn',
        $terms_ok ? 'Страница условий WooCommerce назначена и опубликована.' : 'Оферта ещё не назначена/не опубликована в WooCommerce.'
    );

    $occasion_ok = cg_order_readiness_has_attribute('povod');
    $holidays_ok = cg_order_readiness_has_attribute('prazdniki');
    $missing_attributes = [];
    if (!$occasion_ok) $missing_attributes[] = '«Повод» (povod)';
    if (!$holidays_ok) $missing_attributes[] = '«Праздники» (prazdniki)';
    $checks[] = cg_order_readiness_item(
        'Фильтры «Повод» и «Праздники»',
        ($occasion_ok && $holidays_ok) ? 'ok' : 'warn',
        ($occasion_ok && $holidays_ok)
            ? 'Оба глобальных атрибута WooCommerce созданы.'
            : 'Осталось создать в Товары → Атрибуты: ' . implode(' и ', $missing_attributes) . '.'
    );

    return $checks;
}

function cg_order_readiness_register_page() {
    add_submenu_page(
        'woocommerce',
        'Контроль заказа',
        'Контроль заказа',
        'manage_woocommerce',
        'cg-order-readiness',
        'cg_order_readiness_render_page'
    );
}
add_action('admin_menu', 'cg_order_readiness_register_page', 30);

function cg_order_readiness_render_page() {
    if (!current_user_can('manage_woocommerce')) return;

    $checks = cg_order_readiness_automatic_checks();
    $ok_count = count(array_filter($checks, static function($item) { return ($item['level'] ?? '') === 'ok'; }));
    $error_count = count(array_filter($checks, static function($item) { return ($item['level'] ?? '') === 'error'; }));
    $manual_items = cg_order_readiness_manual_items();
    $manual_saved = cg_order_readiness_saved_manual();
    $manual_done = 0;
    foreach (array_keys($manual_items) as $key) {
        if (!empty($manual_saved[$key])) $manual_done++;
    }

    settings_errors('cg_order_readiness');
    ?>
    <div class="wrap cg-order-readiness">
        <h1>Контроль заказа перед запуском</h1>
        <p>Автоматическая диагностика проверяет конфигурацию магазина, но <strong>не создаёт заказ, не списывает деньги и не отправляет тестовые сообщения</strong>. Финальный блок ниже отмечается только после реального контрольного заказа.</p>

        <div class="cg-order-readiness__summary">
            <strong><?php echo esc_html($ok_count . ' из ' . count($checks)); ?> автоматических проверок зелёные</strong>
            <span><?php echo esc_html($error_count ? 'Критических пунктов: ' . $error_count : 'Критических ошибок конфигурации не найдено'); ?></span>
        </div>

        <h2>Автоматические проверки</h2>
        <div class="cg-order-readiness__grid">
            <?php foreach ($checks as $item) : ?>
                <article class="cg-order-readiness__item is-<?php echo esc_attr($item['level']); ?>">
                    <span class="cg-order-readiness__mark" aria-hidden="true"><?php echo $item['level'] === 'ok' ? '✓' : ($item['level'] === 'error' ? '!' : '•'); ?></span>
                    <div>
                        <strong><?php echo esc_html($item['label']); ?></strong>
                        <p><?php echo esc_html($item['details']); ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <h2>Реальный контрольный заказ</h2>
        <p>Этот список специально не отмечается автоматически. Ставьте галочку только когда соответствующий шаг действительно проверен на работающем сайте.</p>
        <form method="post" class="cg-order-readiness__manual">
            <?php wp_nonce_field('cg_order_readiness_save'); ?>
            <?php foreach ($manual_items as $key => $label) : ?>
                <label>
                    <input type="checkbox" name="cg_manual[<?php echo esc_attr($key); ?>]" value="1" <?php checked(!empty($manual_saved[$key])); ?>>
                    <span><?php echo esc_html($label); ?></span>
                </label>
            <?php endforeach; ?>
            <p><strong><?php echo esc_html($manual_done . ' из ' . count($manual_items)); ?></strong> ручных проверок подтверждено.</p>
            <p><button type="submit" class="button button-primary" name="cg_order_readiness_save" value="1">Сохранить результаты проверки</button></p>
        </form>

        <div class="cg-order-readiness__next">
            <strong>Что проверять в тестовом заказе</strong>
            <p>Возьмите реальный товар, пройдите корзину и Checkout как покупатель, выберите населённый пункт, заполните получателя и отправителя, оформите заказ, затем проверьте сумму, письмо, VK, заказ в админке и те же поля в мобильном приложении WooCommerce.</p>
            <?php if (current_user_can('manage_options')) : ?>
                <a class="button" href="<?php echo esc_url(admin_url('themes.php?page=cg-launch-readiness')); ?>">Открыть общий чек-лист запуска</a>
            <?php endif; ?>
        </div>

        <style>
            .cg-order-readiness{max-width:1100px}
            .cg-order-readiness>p{max-width:900px;font-size:14px;line-height:1.65}
            .cg-order-readiness__summary{display:flex;flex-wrap:wrap;gap:10px 22px;align-items:center;margin:18px 0 28px;padding:18px 20px;border:1px solid #dcdcde;border-radius:12px;background:#fff}
            .cg-order-readiness__summary strong{font-size:17px}.cg-order-readiness__summary span{color:#646970}
            .cg-order-readiness__grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin:14px 0 30px}
            .cg-order-readiness__item{display:grid;grid-template-columns:34px minmax(0,1fr);gap:12px;padding:16px 17px;border:1px solid #dcdcde;border-radius:12px;background:#fff}
            .cg-order-readiness__mark{display:grid;place-items:center;width:28px;height:28px;border-radius:50%;font-weight:800}
            .cg-order-readiness__item.is-ok .cg-order-readiness__mark{background:#edfaef;color:#137333}.cg-order-readiness__item.is-warn .cg-order-readiness__mark{background:#fff4e5;color:#8a4b00}.cg-order-readiness__item.is-error .cg-order-readiness__mark{background:#fce8e6;color:#b3261e}
            .cg-order-readiness__item strong{display:block;font-size:14px}.cg-order-readiness__item p{margin:5px 0 0;color:#646970;line-height:1.5}
            .cg-order-readiness__manual{max-width:820px;margin:14px 0 30px;padding:18px 20px;border:1px solid #dcdcde;border-radius:12px;background:#fff}
            .cg-order-readiness__manual label{display:flex;align-items:flex-start;gap:10px;padding:11px 0;border-bottom:1px solid #f0f0f1;font-size:14px}.cg-order-readiness__manual label:last-of-type{border-bottom:0}.cg-order-readiness__manual input{margin-top:2px}
            .cg-order-readiness__next{max-width:820px;padding:18px 20px;border-left:4px solid #d98d94;background:#fff7f5}.cg-order-readiness__next>strong{font-size:16px}.cg-order-readiness__next p{line-height:1.6}
            @media(max-width:782px){.cg-order-readiness__grid{grid-template-columns:1fr}.cg-order-readiness__summary{display:grid}}
        </style>
    </div>
    <?php
}
