<?php
/**
 * Owner-friendly layer over the technical SEO/analytics modules.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/** Add one plain-language help page under WooCommerce. */
function cg_seo_owner_friendly_menu() {
    add_submenu_page(
        'woocommerce',
        'Поиск и продвижение',
        'Поиск и продвижение',
        'manage_woocommerce',
        'cg-search-promotion-guide',
        'cg_seo_owner_friendly_page'
    );
}
add_action('admin_menu', 'cg_seo_owner_friendly_menu', 95);

function cg_seo_owner_friendly_page() {
    if (!current_user_can('manage_woocommerce')) return;

    $analytics_id = trim((string) get_option('cg_analytics_ga4_id', ''));
    $landing_count = (int) wp_count_posts('cg_landing')->publish;
    ?>
    <div class="wrap cg-owner-guide">
        <h1>Поиск и продвижение — что вам нужно делать</h1>
        <p class="cg-owner-guide__lead">Техническая часть уже работает автоматически. Вам не нужно разбираться в SEO-терминах, коде или специальных настройках.</p>

        <div class="cg-owner-guide__cards">
            <section class="cg-owner-guide__card is-main">
                <span class="cg-owner-guide__badge">Главное</span>
                <h2>Когда добавляете товар</h2>
                <ol>
                    <li><strong>Дайте понятное название.</strong> Например: «Букет из роз и хризантем №15».</li>
                    <li><strong>Добавьте хорошее главное фото</strong> и несколько дополнительных фотографий.</li>
                    <li><strong>Заполните короткое описание.</strong> 1–3 предложения: состав, размер или главное отличие букета.</li>
                    <li><strong>Заполните обычное описание.</strong> Можно коротко: состав, упаковка, размер, особенности.</li>
                    <li><strong>Выберите категорию, Повод и Праздники</strong>, если они подходят товару.</li>
                </ol>
                <p><strong>Этого уже достаточно для нормальной работы сайта.</strong> Остальные поля можно не трогать.</p>
            </section>

            <section class="cg-owner-guide__card">
                <span class="cg-owner-guide__badge is-optional">Необязательно</span>
                <h2>Поля «для поиска» в товаре</h2>
                <p>Если хотите — можете отдельно написать, как товар будет называться и описываться в Google/Яндексе. Если оставить эти поля пустыми, сайт сам использует название и описание товара.</p>
                <p><strong>То есть пустые поля — это нормально.</strong></p>
            </section>

            <section class="cg-owner-guide__card">
                <span class="cg-owner-guide__badge is-useful">Полезно</span>
                <h2>Категории</h2>
                <p>В <strong>Товары → Категории</strong> желательно заполнить:</p>
                <p><strong>Короткое вступление</strong> — 1–2 предложения, которые покупатель увидит сверху.</p>
                <p><strong>Описание</strong> — более подробный текст; он выводится после товаров и не мешает выбирать букет.</p>
            </section>

            <section class="cg-owner-guide__card">
                <span class="cg-owner-guide__badge is-useful">По мере наполнения</span>
                <h2>Готовые подборки</h2>
                <p>SEO-посадочные — это просто отдельные страницы вроде «Букеты на день рождения» или «Букеты для мамы».</p>
                <p>Сейчас опубликовано: <strong><?php echo esc_html($landing_count); ?></strong>.</p>
                <p><a class="button" href="<?php echo esc_url(admin_url('edit.php?post_type=cg_landing')); ?>">Открыть готовые подборки</a></p>
            </section>

            <section class="cg-owner-guide__card">
                <span class="cg-owner-guide__badge is-later">Можно потом</span>
                <h2>Статистика</h2>
                <?php if ($analytics_id !== '') : ?>
                    <p>Google Analytics уже подключён. Дополнительно ничего делать не нужно.</p>
                <?php else : ?>
                    <p>Сейчас внешняя аналитика не подключена — это нормально. Магазин работает без неё.</p>
                    <p>Когда понадобится статистика посещений и продаж, мы отдельно подключим её и проверим настройки.</p>
                <?php endif; ?>
            </section>

            <section class="cg-owner-guide__card is-auto">
                <span class="cg-owner-guide__badge is-auto">Автоматически</span>
                <h2>Что сайт делает сам</h2>
                <p>Технические адреса, подсказки поисковикам, защита страниц фильтров от дублей, служебная разметка магазина, ускорение главных изображений и другие технические вещи работают без вашего участия.</p>
                <p><strong>Менять их вручную не нужно.</strong></p>
            </section>
        </div>
    </div>
    <?php
}

/** Rename technical menu wording to something less intimidating. */
function cg_seo_owner_friendly_menu_labels() {
    global $submenu;
    if (empty($submenu['woocommerce']) || !is_array($submenu['woocommerce'])) return;

    foreach ($submenu['woocommerce'] as &$item) {
        $slug = isset($item[2]) ? (string) $item[2] : '';
        if ($slug === 'cg-commerce-analytics') $item[0] = 'Статистика сайта';
        if ($slug === 'edit.php?post_type=cg_landing') $item[0] = 'Готовые подборки';
    }
    unset($item);
}
add_action('admin_menu', 'cg_seo_owner_friendly_menu_labels', 999);

/** Replace technical labels on product/category screens without changing storage or SEO logic. */
function cg_seo_owner_friendly_admin_assets($hook) {
    $screen = get_current_screen();
    if (!$screen) return;

    $is_product = in_array($hook, ['post.php', 'post-new.php'], true) && $screen->post_type === 'product';
    $is_category = $screen->taxonomy === 'product_cat';
    if (!$is_product && !$is_category) return;

    $css = '
.cg-owner-seo-help{margin:10px 0 14px;padding:11px 12px;border:1px solid #dcdcde;border-radius:8px;background:#f6f7f7;color:#50575e;line-height:1.5}.cg-owner-seo-help strong{color:#1d2327}.cg-owner-seo-example{display:block;margin-top:5px;color:#646970;font-size:12px;line-height:1.45}
';
    wp_register_style('cg-seo-owner-friendly-admin', false);
    wp_enqueue_style('cg-seo-owner-friendly-admin');
    wp_add_inline_style('cg-seo-owner-friendly-admin', $css);

    $js = <<<'JS'
document.addEventListener('DOMContentLoaded',function(){
    var box=document.getElementById('cg-product-seo-snippet');
    if(box){
        var title=box.querySelector('.postbox-header h2,.hndle');
        if(title)title.textContent='Как товар будет выглядеть в поиске';
        var titleLabel=box.querySelector('label[for="cg_seo_title"]');
        var descLabel=box.querySelector('label[for="cg_seo_description"]');
        if(titleLabel)titleLabel.innerHTML='<strong>Заголовок для поиска</strong> <small>(необязательно)</small>';
        if(descLabel)descLabel.innerHTML='<strong>Короткое описание для поиска</strong> <small>(необязательно)</small>';
        var titleInput=box.querySelector('#cg_seo_title');
        var descInput=box.querySelector('#cg_seo_description');
        if(titleInput)titleInput.placeholder='Можно оставить пустым — возьмём название товара';
        if(descInput)descInput.placeholder='Можно оставить пустым — возьмём описание товара';
        var first=box.querySelector('.inside');
        if(first){
            var help=document.createElement('div');
            help.className='cg-owner-seo-help';
            help.innerHTML='<strong>Можно ничего здесь не заполнять.</strong> Сайт сам использует название и описание товара. Эти поля нужны только если позже захотите отдельно изменить текст для поисковика.';
            first.insertBefore(help,first.firstChild);
        }
    }

    var titleField=document.querySelector('label[for="cg_category_seo_title"]');
    var descField=document.querySelector('label[for="cg_category_seo_description"]');
    if(titleField)titleField.textContent='Заголовок для поиска — необязательно';
    if(descField)descField.textContent='Короткое описание для поиска — необязательно';
});
JS;
    wp_add_inline_script('jquery-core', $js, 'after');
}
add_action('admin_enqueue_scripts', 'cg_seo_owner_friendly_admin_assets', 100);

/** Add a friendly shortcut from product editor to the guide. */
function cg_seo_owner_friendly_product_notice() {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'product') return;
    echo '<div class="notice notice-info"><p><strong>Не хотите разбираться в SEO?</strong> Это не обязательно. Заполняйте товар как обычно. <a href="' . esc_url(admin_url('admin.php?page=cg-search-promotion-guide')) . '">Открыть простую памятку</a>.</p></div>';
}
add_action('admin_notices', 'cg_seo_owner_friendly_product_notice');

function cg_seo_owner_friendly_page_assets($hook) {
    if ($hook !== 'woocommerce_page_cg-search-promotion-guide') return;
    $css = '
.cg-owner-guide{max-width:1100px}.cg-owner-guide__lead{font-size:16px;max-width:820px;color:#50575e}.cg-owner-guide__cards{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-top:22px}.cg-owner-guide__card{padding:20px;border:1px solid #dcdcde;border-radius:14px;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.03)}.cg-owner-guide__card.is-main{grid-column:1/-1;border-color:#e4b8bd;background:#fffafa}.cg-owner-guide__card h2{margin:8px 0 12px;font-size:19px}.cg-owner-guide__card p,.cg-owner-guide__card li{font-size:14px;line-height:1.65}.cg-owner-guide__badge{display:inline-block;padding:4px 9px;border-radius:999px;background:#f4d8dc;color:#8b4850;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.04em}.cg-owner-guide__badge.is-optional,.cg-owner-guide__badge.is-later{background:#f0f0f1;color:#50575e}.cg-owner-guide__badge.is-useful{background:#e7f4ea;color:#206b36}.cg-owner-guide__badge.is-auto{background:#e9f2fb;color:#245b8a}@media(max-width:782px){.cg-owner-guide__cards{grid-template-columns:1fr}.cg-owner-guide__card.is-main{grid-column:auto}}
';
    wp_register_style('cg-seo-owner-guide', false);
    wp_enqueue_style('cg-seo-owner-guide');
    wp_add_inline_style('cg-seo-owner-guide', $css);
}
add_action('admin_enqueue_scripts', 'cg_seo_owner_friendly_page_assets', 110);
