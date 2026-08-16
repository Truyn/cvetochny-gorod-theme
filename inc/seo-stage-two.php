<?php
/**
 * SEO stage two: category content, curated internal links, faceted noindex and
 * lightweight product-editor guidance.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/** Short commercial intro shown next to the product-category H1. */
function cg_seo_stage_two_category_add_fields() {
    ?>
    <div class="form-field">
        <label for="cg_category_intro">Короткое вступление</label>
        <textarea name="cg_category_intro" id="cg_category_intro" rows="3"></textarea>
        <p>1–2 коротких предложения под заголовком категории. Большой SEO-текст пишите в стандартном поле «Описание» — на сайте он будет показан после товаров.</p>
    </div>
    <?php
}
add_action('product_cat_add_form_fields', 'cg_seo_stage_two_category_add_fields');

function cg_seo_stage_two_category_edit_fields($term) {
    if (!$term instanceof WP_Term) return;
    $intro = (string) get_term_meta($term->term_id, '_cg_category_intro', true);
    ?>
    <tr class="form-field">
        <th scope="row"><label for="cg_category_intro">Короткое вступление</label></th>
        <td>
            <textarea name="cg_category_intro" id="cg_category_intro" rows="3" class="large-text"><?php echo esc_textarea($intro); ?></textarea>
            <p class="description">1–2 предложения под H1. Стандартное поле «Описание» используйте для подробного полезного текста — он выводится после сетки товаров.</p>
        </td>
    </tr>
    <?php
}
add_action('product_cat_edit_form_fields', 'cg_seo_stage_two_category_edit_fields');

function cg_seo_stage_two_save_category_fields($term_id) {
    if (!current_user_can('manage_product_terms')) return;
    $intro = isset($_POST['cg_category_intro'])
        ? sanitize_textarea_field(wp_unslash($_POST['cg_category_intro']))
        : '';
    update_term_meta($term_id, '_cg_category_intro', $intro);
}
add_action('created_product_cat', 'cg_seo_stage_two_save_category_fields');
add_action('edited_product_cat', 'cg_seo_stage_two_save_category_fields');

function cg_seo_stage_two_category_intro($term = null) {
    if (!$term instanceof WP_Term) $term = get_queried_object();
    if (!$term instanceof WP_Term || $term->taxonomy !== 'product_cat') return '';
    return trim((string) get_term_meta($term->term_id, '_cg_category_intro', true));
}

/** Render the standard category description after products, not above shopping. */
function cg_seo_stage_two_category_content($term = null) {
    if (!$term instanceof WP_Term) $term = get_queried_object();
    if (!$term instanceof WP_Term || $term->taxonomy !== 'product_cat') return;

    $description = trim((string) term_description($term->term_id, 'product_cat'));
    if ($description === '') return;

    echo '<section class="cg-category-seo-copy" aria-labelledby="cg-category-seo-title">';
    echo '<span>О подборке</span>';
    echo '<h2 id="cg-category-seo-title">' . esc_html($term->name) . ' — подробнее</h2>';
    echo '<div class="cg-category-seo-copy__text">' . wp_kses_post($description) . '</div>';
    echo '</section>';
}

/** Detect parameter-driven catalog states that should not compete in the index. */
function cg_seo_stage_two_is_faceted_catalog() {
    if (is_admin() || wp_doing_ajax() || !class_exists('WooCommerce')) return false;
    if (!(is_shop() || is_product_taxonomy())) return false;
    if (empty($_GET)) return false;

    $direct = ['catalog_search', 'min_price', 'max_price', 'stock_status', 'on_sale', 'cg_orderby', 'product_cat', 'product_cat_id'];
    foreach (array_keys($_GET) as $key) {
        $key = sanitize_key((string) $key);
        if (in_array($key, $direct, true) || strpos($key, 'filter_') === 0) return true;
    }
    return false;
}

function cg_seo_stage_two_faceted_robots($robots) {
    if (!cg_seo_stage_two_is_faceted_catalog()) return $robots;
    unset($robots['index']);
    $robots['noindex'] = true;
    $robots['follow'] = true;
    return $robots;
}
add_filter('wp_robots', 'cg_seo_stage_two_faceted_robots', 35);

/** Conservative canonical for faceted catalog URLs when no SEO plugin owns it. */
function cg_seo_stage_two_faceted_canonical() {
    if (!cg_seo_stage_two_is_faceted_catalog()) return;
    if (function_exists('cg_launch_has_seo_plugin') && cg_launch_has_seo_plugin()) return;

    $url = function_exists('cg_catalog_url') ? cg_catalog_url() : home_url('/shop/');
    if (function_exists('cg_catalog_current_category_term')) {
        $term = cg_catalog_current_category_term();
        if ($term instanceof WP_Term) {
            $term_url = get_term_link($term);
            if (!is_wp_error($term_url)) $url = $term_url;
        }
    }

    echo "\n<link rel=\"canonical\" href=\"" . esc_url($url) . "\">\n";
}
add_action('wp_head', 'cg_seo_stage_two_faceted_canonical', 3);

/** Find a published curated landing targeting an exact taxonomy term. */
function cg_seo_stage_two_landing_for_term($taxonomy, $term_id) {
    static $cache = [];
    $taxonomy = sanitize_key((string) $taxonomy);
    $term_id = absint($term_id);
    $cache_key = $taxonomy . ':' . $term_id;
    if (array_key_exists($cache_key, $cache)) return $cache[$cache_key];
    if (!$taxonomy || !$term_id || !post_type_exists('cg_landing')) return $cache[$cache_key] = null;

    $posts = get_posts([
        'post_type' => 'cg_landing',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'no_found_rows' => true,
        'meta_query' => [
            'relation' => 'AND',
            ['key' => '_cg_landing_taxonomy', 'value' => $taxonomy],
            ['key' => '_cg_landing_term_id', 'value' => $term_id, 'type' => 'NUMERIC'],
        ],
    ]);

    return $cache[$cache_key] = ($posts ? (int) $posts[0] : null);
}

/** Compact purchase-context chips on product pages. */
function cg_seo_stage_two_product_contexts() {
    global $product;
    if (!$product instanceof WC_Product) return;

    $items = [];
    foreach (['pa_povod' => 'Повод', 'pa_prazdniki' => 'Праздник'] as $taxonomy => $kind) {
        if (!taxonomy_exists($taxonomy)) continue;
        $terms = wp_get_object_terms($product->get_id(), $taxonomy);
        if (is_wp_error($terms)) continue;
        foreach ($terms as $term) {
            if (!$term instanceof WP_Term) continue;
            $landing_id = cg_seo_stage_two_landing_for_term($taxonomy, $term->term_id);
            $items[] = [
                'label' => $term->name,
                'kind' => $kind,
                'url' => $landing_id ? get_permalink($landing_id) : '',
            ];
        }
    }

    if (!$items) return;
    $items = array_slice($items, 0, 8);

    echo '<div class="cg-product-contexts" aria-label="Для кого и к какому событию подходит товар">';
    echo '<span class="cg-product-contexts__title">Подходит для</span>';
    echo '<div class="cg-product-contexts__chips">';
    foreach ($items as $item) {
        if ($item['url']) {
            echo '<a href="' . esc_url($item['url']) . '" title="' . esc_attr($item['kind'] . ': ' . $item['label']) . '">' . esc_html($item['label']) . '</a>';
        } else {
            echo '<span title="' . esc_attr($item['kind'] . ': ' . $item['label']) . '">' . esc_html($item['label']) . '</span>';
        }
    }
    echo '</div></div>';
}
add_action('woocommerce_single_product_summary', 'cg_seo_stage_two_product_contexts', 37);

/** Homepage links to the strongest manually published landing pages. */
function cg_seo_stage_two_home_landings() {
    if (!post_type_exists('cg_landing')) return;
    $landings = get_posts([
        'post_type' => 'cg_landing',
        'post_status' => 'publish',
        'posts_per_page' => 6,
        'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
        'meta_query' => [[
            'key' => '_cg_landing_term_id',
            'value' => 0,
            'compare' => '>',
            'type' => 'NUMERIC',
        ]],
    ]);
    if (!$landings) return;

    $ready = [];
    foreach ($landings as $landing) {
        if (!function_exists('cg_seo_landing_target')) continue;
        $term = cg_seo_landing_target($landing->ID);
        if (!$term || (int) $term->count < 1) continue;
        $ready[] = $landing;
    }
    if (!$ready) return;

    echo '<section class="section cg-home-seo-landings">';
    echo '<div class="container">';
    echo '<div class="section-head"><div><div class="eyebrow">Подбор по ситуации</div><h2 class="section-title">Букеты для важных моментов</h2><div class="section-subtitle">Готовые подборки по получателю, событию и настроению.</div></div><a class="text-link" href="' . esc_url(cg_catalog_url()) . '">Весь каталог →</a></div>';
    echo '<div class="cg-home-seo-landings__grid">';
    foreach ($ready as $landing) {
        $excerpt = trim((string) $landing->post_excerpt);
        echo '<a class="cg-home-seo-landing" href="' . esc_url(get_permalink($landing)) . '">';
        echo '<strong>' . esc_html(get_the_title($landing)) . '</strong>';
        if ($excerpt !== '') echo '<span>' . esc_html(wp_html_excerpt(wp_strip_all_tags($excerpt), 90, '…')) . '</span>';
        echo '<b>Смотреть подборку →</b></a>';
    }
    echo '</div></div></section>';
}

/** Read-only product checklist to keep important SEO/sales content complete. */
function cg_seo_stage_two_product_checklist_box() {
    add_meta_box(
        'cg-product-seo-checklist',
        'SEO и продажа',
        'cg_seo_stage_two_product_checklist_render',
        'product',
        'side',
        'default'
    );
}
add_action('add_meta_boxes_product', 'cg_seo_stage_two_product_checklist_box');

function cg_seo_stage_two_product_checklist_render($post) {
    $product = wc_get_product($post->ID);
    if (!$product) return;

    $image_id = $product->get_image_id();
    $alt = $image_id ? trim((string) get_post_meta($image_id, '_wp_attachment_image_alt', true)) : '';
    $categories = wp_get_object_terms($post->ID, 'product_cat', ['fields' => 'ids']);
    $categories = is_wp_error($categories) ? [] : $categories;
    $default_cat = (int) get_option('default_product_cat', 0);
    $real_categories = array_values(array_diff(array_map('intval', $categories), [$default_cat]));

    $checks = [
        ['Главное фото', $image_id > 0, 'Добавьте качественное основное изображение.'],
        ['Alt у главного фото', $alt !== '', 'Заполните alt в медиатеке понятным описанием изображения.'],
        ['Цена', $product->get_price() !== '', 'Укажите актуальную цену.'],
        ['Короткое описание', trim(wp_strip_all_tags($product->get_short_description())) !== '', 'Оно помогает быстро понять товар и используется как источник краткого SEO-описания.'],
        ['Полное описание', mb_strlen(trim(wp_strip_all_tags($product->get_description()))) >= 80, 'Добавьте полезное описание состава, размера или особенностей.'],
        ['Категория', !empty($real_categories), 'Назначьте товар в реальную категорию, а не только «Без категории».'],
    ];

    echo '<div class="cg-product-seo-checklist">';
    foreach ($checks as [$label, $ok, $hint]) {
        echo '<div class="' . ($ok ? 'is-ok' : 'is-warn') . '"><b>' . ($ok ? '✓' : '!') . '</b><span><strong>' . esc_html($label) . '</strong>';
        if (!$ok) echo '<small>' . esc_html($hint) . '</small>';
        echo '</span></div>';
    }
    echo '<p>Для «Повода» и «Праздников» используйте отдельный блок с чекбоксами в редакторе товара.</p>';
    echo '</div>';
}

/** Group occasion and holiday filters into one prominent shopping block. */
function cg_seo_stage_two_catalog_filter_grouping() {
    if (!class_exists('WooCommerce') || !(is_shop() || is_product_taxonomy())) return;

    $js = <<<'JS'
(function(){
    function buildCluster(){
        var form=document.querySelector('.cg-catalog-filter-form');
        if(!form||form.querySelector('.cg-event-filter-cluster'))return;
        var occasionInput=form.querySelector('input[name="filter_povod[]"],input[name="filter_occasion[]"]');
        var holidayInput=form.querySelector('input[name="filter_prazdniki[]"]');
        var occasion=occasionInput?occasionInput.closest('.cg-filter-group'):null;
        var holiday=holidayInput?holidayInput.closest('.cg-filter-group'):null;
        if(!occasion&&!holiday)return;

        var cluster=document.createElement('section');
        cluster.className='cg-event-filter-cluster';
        cluster.setAttribute('aria-label','Подбор букета по ситуации');
        cluster.innerHTML='<div class="cg-event-filter-cluster__head"><span class="cg-event-filter-cluster__icon" aria-hidden="true">✦</span><div><b>Для кого и к какому событию?</b><small>Выберите повод или праздник</small></div></div><div class="cg-event-filter-cluster__groups"></div>';
        var groups=cluster.querySelector('.cg-event-filter-cluster__groups');
        if(occasion)groups.appendChild(occasion);
        if(holiday)groups.appendChild(holiday);

        var quick=form.querySelector('.cg-filter-quick');
        if(quick)form.insertBefore(cluster,quick);else form.appendChild(cluster);
    }
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',buildCluster);else buildCluster();
})();
JS;
    wp_add_inline_script('cg-ajax-catalog', $js, 'after');
}
add_action('wp_enqueue_scripts', 'cg_seo_stage_two_catalog_filter_grouping', 65);

function cg_seo_stage_two_assets($hook = '') {
    if (is_admin()) {
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'product') return;
        $css = '.cg-product-seo-checklist>div{display:flex;gap:9px;padding:9px 0;border-bottom:1px solid #f0f0f1}.cg-product-seo-checklist>div:last-of-type{border-bottom:0}.cg-product-seo-checklist b{display:grid;place-items:center;flex:0 0 22px;height:22px;border-radius:50%}.cg-product-seo-checklist .is-ok>b{background:#edfaef;color:#137333}.cg-product-seo-checklist .is-warn>b{background:#fff4e5;color:#8a4b00}.cg-product-seo-checklist strong,.cg-product-seo-checklist small{display:block}.cg-product-seo-checklist small{margin-top:3px;color:#646970;line-height:1.35}.cg-product-seo-checklist>p{margin:12px 0 0;color:#646970;font-size:12px;line-height:1.45}';
        wp_add_inline_style('woocommerce_admin_styles', $css);
        return;
    }

    $relevant = is_front_page()
        || (class_exists('WooCommerce') && (is_shop() || is_product_taxonomy() || is_product()));
    if (!$relevant) return;

    $path = get_template_directory() . '/assets/css/seo-stage-two.css';
    wp_enqueue_style(
        'cg-seo-stage-two',
        get_template_directory_uri() . '/assets/css/seo-stage-two.css',
        [],
        file_exists($path) ? filemtime($path) : wp_get_theme()->get('Version')
    );
}
add_action('admin_enqueue_scripts', 'cg_seo_stage_two_assets', 60);
add_action('wp_enqueue_scripts', 'cg_seo_stage_two_assets', 80);
