<?php
/**
 * Store search and My Account enhancements.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/** Limit the header search to WooCommerce products. */
function cg_product_search_form() {
    $value = get_search_query();
    echo '<form class="cg-product-search" role="search" method="get" action="'.esc_url(home_url('/')).'">';
    echo '<label class="screen-reader-text" for="cg-product-search-field">Поиск товаров</label>';
    echo '<input id="cg-product-search-field" type="search" name="s" value="'.esc_attr($value).'" placeholder="Найти букет или цветы…" autocomplete="off">';
    echo '<input type="hidden" name="post_type" value="product">';
    echo '<button type="submit" aria-label="Искать">⌕</button>';
    echo '</form>';
}

/** Friendly account dashboard content. */
function cg_account_dashboard_intro() {
    $user = wp_get_current_user();
    echo '<section class="cg-account-welcome">';
    echo '<div><span class="cg-account-welcome__eyebrow">Личный кабинет</span><h2>Здравствуйте, '.esc_html($user->display_name ?: $user->user_login).'!</h2><p>Здесь можно проверить заказы, изменить адрес доставки и сохранить контактные данные.</p></div>';
    echo '<a class="button" href="'.esc_url(cg_catalog_url()).'">Перейти в каталог</a>';
    echo '</section>';
    echo '<div class="cg-account-benefits"><div><strong>🚚 Быстрая доставка</strong><span>Согласуем удобное время</span></div><div><strong>📷 Фото букета</strong><span>Пришлём перед отправкой</span></div><div><strong>💌 Открытка</strong><span>Добавим пожелание бесплатно</span></div></div>';
}
add_action('woocommerce_account_dashboard', 'cg_account_dashboard_intro', 5);

/** Flower-store wording in account navigation. */
add_filter('woocommerce_account_menu_items', function ($items) {
    $labels = [
        'dashboard'       => 'Обзор',
        'orders'          => 'Мои заказы',
        'downloads'       => 'Загрузки',
        'edit-address'    => 'Адреса доставки',
        'payment-methods' => 'Способы оплаты',
        'edit-account'    => 'Личные данные',
        'customer-logout' => 'Выйти',
    ];
    foreach ($labels as $key => $label) {
        if (isset($items[$key])) $items[$key] = $label;
    }
    return $items;
});

/** Use a more suitable empty-order message. */
add_filter('woocommerce_my_account_my_orders_query', function ($args) {
    $args['limit'] = 10;
    return $args;
});
