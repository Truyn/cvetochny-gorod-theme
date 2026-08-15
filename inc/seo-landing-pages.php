<?php
/**
 * Curated SEO landing pages with product selections and conversion blocks.
 *
 * Landing pages are created manually. No faceted-filter URL is made indexable
 * and no thin pages are generated automatically.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

const CG_SEO_LANDING_REWRITE_VERSION = '1';

function cg_seo_landing_register_post_type() {
    register_post_type('cg_landing', [
        'labels' => [
            'name' => 'SEO-посадочные',
            'singular_name' => 'SEO-посадочная',
            'add_new' => 'Добавить посадочную',
            'add_new_item' => 'Добавить SEO-посадочную',
            'edit_item' => 'Редактировать посадочную',
            'new_item' => 'Новая посадочная',
            'view_item' => 'Открыть посадочную',
            'search_items' => 'Найти посадочную',
            'not_found' => 'Посадочные не найдены',
            'menu_name' => 'SEO-посадочные',
        ],
        'public' => true,
        'publicly_queryable' => true,
        'exclude_from_search' => false,
        'show_ui' => true,
        'show_in_menu' => 'woocommerce',
        'show_in_rest' => true,
        'has_archive' => false,
        'rewrite' => ['slug' => 'podbor', 'with_front' => false],
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'revisions'],
        'menu_icon' => 'dashicons-search',
    ]);
}
add_action('init', 'cg_seo_landing_register_post_type', 20);

/** Flush the new /podbor/... rewrite once after this module appears. */
function cg_seo_landing_maybe_flush_rewrites() {
    if (!current_user_can('manage_options')) return;
    if ((string) get_option('cg_seo_landing_rewrite_version', '') === CG_SEO_LANDING_REWRITE_VERSION) return;

    cg_seo_landing_register_post_type();
    flush_rewrite_rules(false);
    update_option('cg_seo_landing_rewrite_version', CG_SEO_LANDING_REWRITE_VERSION, false);
}
add_action('admin_init', 'cg_seo_landing_maybe_flush_rewrites');

/** Available manual sources for a landing product selection. */
function cg_seo_landing_source_taxonomies() {
    $sources = ['product_cat' => 'Категория товаров'];
    if (taxonomy_exists('pa_povod')) $sources['pa_povod'] = 'Повод';
    if (taxonomy_exists('pa_prazdniki')) $sources['pa_prazdniki'] = 'Праздники';
    return $sources;
}

function cg_seo_landing_add_meta_box() {
    add_meta_box(
        'cg-seo-landing-settings',
        'Настройка подборки и конверсии',
        'cg_seo_landing_render_meta_box',
        'cg_landing',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes_cg_landing', 'cg_seo_landing_add_meta_box');

function cg_seo_landing_render_meta_box($post) {
    wp_nonce_field('cg_seo_landing_save', 'cg_seo_landing_nonce');

    $saved_taxonomy = sanitize_key((string) get_post_meta($post->ID, '_cg_landing_taxonomy', true));
    $saved_term_id = absint(get_post_meta($post->ID, '_cg_landing_term_id', true));
    $product_count = absint(get_post_meta($post->ID, '_cg_landing_product_count', true));
    if (!in_array($product_count, [4, 8, 12, 16], true)) $product_count = 8;
    $cta_title = (string) get_post_meta($post->ID, '_cg_landing_cta_title', true);
    $cta_text = (string) get_post_meta($post->ID, '_cg_landing_cta_text', true);

    echo '<div class="cg-seo-landing-admin">';
    echo '<p><strong>Как заполнять:</strong> название записи — основной H1 и основа title; поле «Отрывок» — короткое описание для первого экрана и базового meta description; основной редактор — уникальный полезный текст страницы.</p>';
    echo '<p>Посадочные создаются вручную. Комбинации AJAX-фильтров не превращаются в отдельные индексируемые страницы.</p>';

    echo '<div class="cg-seo-landing-admin__grid">';
    echo '<label><strong>Какие товары показать</strong><select name="cg_landing_target">';
    echo '<option value="">— Выберите категорию или атрибут —</option>';

    foreach (cg_seo_landing_source_taxonomies() as $taxonomy => $group_label) {
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'orderby' => 'name',
        ]);
        if (is_wp_error($terms) || !$terms) continue;

        echo '<optgroup label="' . esc_attr($group_label) . '">';
        foreach ($terms as $term) {
            $value = $taxonomy . ':' . (int) $term->term_id;
            $selected = $saved_taxonomy === $taxonomy && $saved_term_id === (int) $term->term_id;
            echo '<option value="' . esc_attr($value) . '"' . selected($selected, true, false) . '>' . esc_html($term->name) . '</option>';
        }
        echo '</optgroup>';
    }
    echo '</select><span>Страница покажет опубликованные товары из выбранной подборки.</span></label>';

    echo '<label><strong>Количество товаров</strong><select name="cg_landing_product_count">';
    foreach ([4, 8, 12, 16] as $count) {
        echo '<option value="' . esc_attr($count) . '"' . selected($product_count, $count, false) . '>' . esc_html($count) . '</option>';
    }
    echo '</select><span>Для большинства посадочных оптимально 8–12 товаров.</span></label>';

    echo '<label><strong>Заголовок нижнего CTA</strong><input type="text" name="cg_landing_cta_title" value="' . esc_attr($cta_title) . '" placeholder="Не нашли подходящий букет?"><span>Необязательно — есть аккуратный текст по умолчанию.</span></label>';
    echo '<label><strong>Текст нижнего CTA</strong><textarea name="cg_landing_cta_text" rows="3" placeholder="Посмотрите весь каталог или свяжитесь с магазином — поможем подобрать вариант.">' . esc_textarea($cta_text) . '</textarea><span>Короткий следующий шаг для покупателя.</span></label>';
    echo '</div></div>';
}

function cg_seo_landing_save($post_id) {
    if (get_post_type($post_id) !== 'cg_landing') return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (empty($_POST['cg_seo_landing_nonce'])) return;
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cg_seo_landing_nonce'])), 'cg_seo_landing_save')) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $target = isset($_POST['cg_landing_target']) ? sanitize_text_field(wp_unslash($_POST['cg_landing_target'])) : '';
    $taxonomy = '';
    $term_id = 0;

    if (strpos($target, ':') !== false) {
        [$candidate_taxonomy, $candidate_term] = array_pad(explode(':', $target, 2), 2, '');
        $candidate_taxonomy = sanitize_key($candidate_taxonomy);
        $candidate_term = absint($candidate_term);
        $allowed = array_keys(cg_seo_landing_source_taxonomies());
        $term = $candidate_term ? get_term($candidate_term, $candidate_taxonomy) : null;

        if (in_array($candidate_taxonomy, $allowed, true) && $term instanceof WP_Term && !is_wp_error($term)) {
            $taxonomy = $candidate_taxonomy;
            $term_id = $candidate_term;
        }
    }

    update_post_meta($post_id, '_cg_landing_taxonomy', $taxonomy);
    update_post_meta($post_id, '_cg_landing_term_id', $term_id);

    $count = isset($_POST['cg_landing_product_count']) ? absint($_POST['cg_landing_product_count']) : 8;
    if (!in_array($count, [4, 8, 12, 16], true)) $count = 8;
    update_post_meta($post_id, '_cg_landing_product_count', $count);

    $cta_title = isset($_POST['cg_landing_cta_title']) ? sanitize_text_field(wp_unslash($_POST['cg_landing_cta_title'])) : '';
    $cta_text = isset($_POST['cg_landing_cta_text']) ? sanitize_textarea_field(wp_unslash($_POST['cg_landing_cta_text'])) : '';
    update_post_meta($post_id, '_cg_landing_cta_title', $cta_title);
    update_post_meta($post_id, '_cg_landing_cta_text', $cta_text);
}
add_action('save_post_cg_landing', 'cg_seo_landing_save', 20);

function cg_seo_landing_target($post_id = 0) {
    $post_id = $post_id ?: get_the_ID();
    $taxonomy = sanitize_key((string) get_post_meta($post_id, '_cg_landing_taxonomy', true));
    $term_id = absint(get_post_meta($post_id, '_cg_landing_term_id', true));
    if (!$taxonomy || !$term_id || !taxonomy_exists($taxonomy)) return null;

    $term = get_term($term_id, $taxonomy);
    return $term instanceof WP_Term && !is_wp_error($term) ? $term : null;
}

function cg_seo_landing_product_query($post_id = 0) {
    $post_id = $post_id ?: get_the_ID();
    $term = cg_seo_landing_target($post_id);
    if (!$term) return new WP_Query(['post_type' => 'product', 'post__in' => [0]]);

    $count = absint(get_post_meta($post_id, '_cg_landing_product_count', true));
    if (!in_array($count, [4, 8, 12, 16], true)) $count = 8;

    $tax_query = [[
        'taxonomy' => $term->taxonomy,
        'field' => 'term_id',
        'terms' => [(int) $term->term_id],
        'include_children' => $term->taxonomy === 'product_cat',
    ]];

    if (function_exists('wc_get_product_visibility_term_ids')) {
        $visibility = wc_get_product_visibility_term_ids();
        if (!empty($visibility['exclude-from-catalog'])) {
            $tax_query[] = [
                'taxonomy' => 'product_visibility',
                'field' => 'term_taxonomy_id',
                'terms' => [(int) $visibility['exclude-from-catalog']],
                'operator' => 'NOT IN',
            ];
        }
    }

    return new WP_Query([
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => $count,
        'ignore_sticky_posts' => true,
        'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
        'tax_query' => $tax_query,
        'meta_query' => [[
            'key' => '_stock_status',
            'value' => 'instock',
        ]],
    ]);
}

/** Unfinished/empty landing targets must not accidentally become indexable. */
function cg_seo_landing_robots($robots) {
    if (!is_singular('cg_landing')) return $robots;
    $term = cg_seo_landing_target();
    if ($term && (int) $term->count > 0) return $robots;

    unset($robots['index']);
    $robots['noindex'] = true;
    $robots['follow'] = true;
    return $robots;
}
add_filter('wp_robots', 'cg_seo_landing_robots', 30);

/** Use the dedicated frontend template for curated landing pages. */
function cg_seo_landing_template($template) {
    if (!is_singular('cg_landing')) return $template;
    $custom = get_template_directory() . '/single-cg_landing.php';
    return file_exists($custom) ? $custom : $template;
}
add_filter('template_include', 'cg_seo_landing_template', 50);

/** Landing and catalog discovery styles. */
function cg_seo_landing_assets() {
    $on_landing = is_singular('cg_landing');
    $on_catalog = class_exists('WooCommerce') && (is_shop() || is_product_taxonomy());
    if (!$on_landing && !$on_catalog) return;

    $path = get_template_directory() . '/assets/css/seo-landings.css';
    wp_enqueue_style(
        'cg-seo-landings',
        get_template_directory_uri() . '/assets/css/seo-landings.css',
        class_exists('WooCommerce') ? ['cg-woocommerce'] : [],
        file_exists($path) ? filemtime($path) : wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'cg_seo_landing_assets', 70);

/** Product loop used by the landing template. */
function cg_seo_landing_render_products($post_id = 0) {
    if (!class_exists('WooCommerce')) return;
    $query = cg_seo_landing_product_query($post_id);

    echo '<section class="cg-seo-landing__products" aria-labelledby="cg-seo-products-title">';
    echo '<div class="cg-seo-landing__section-head"><div><span>Подборка</span><h2 id="cg-seo-products-title">Подходящие букеты и композиции</h2></div><a href="' . esc_url(cg_catalog_url()) . '">Весь каталог →</a></div>';

    if ($query->have_posts()) {
        wc_set_loop_prop('columns', 4);
        wc_set_loop_prop('total', $query->found_posts);
        woocommerce_product_loop_start();
        while ($query->have_posts()) {
            $query->the_post();
            wc_get_template_part('content', 'product');
        }
        woocommerce_product_loop_end();
    } else {
        echo '<div class="cg-seo-landing__empty"><strong>Подборка пока наполняется</strong><p>Посмотрите весь каталог — там доступны актуальные товары.</p><a class="button" href="' . esc_url(cg_catalog_url()) . '">Открыть каталог</a></div>';
    }

    wp_reset_postdata();
    echo '</section>';
}

/** Reusable trust/conversion strip. */
function cg_seo_landing_trust_strip() {
    echo '<div class="cg-seo-landing__trust" aria-label="Преимущества магазина">';
    echo '<div><b>🚚</b><strong>Доставка</strong><span>Нововоронеж и Воронежская область</span></div>';
    echo '<div><b>📷</b><strong>Фото букета</strong><span>согласуем перед отправкой</span></div>';
    echo '<div><b>💌</b><strong>Открытка</strong><span>добавим к заказу бесплатно</span></div>';
    echo '<div><b>💐</b><strong>Свежая сборка</strong><span>собираем перед доставкой</span></div>';
    echo '</div>';
}

/** Bottom CTA that offers a clear next action without interrupting shopping. */
function cg_seo_landing_cta($post_id = 0) {
    $post_id = $post_id ?: get_the_ID();
    $title = trim((string) get_post_meta($post_id, '_cg_landing_cta_title', true));
    $text = trim((string) get_post_meta($post_id, '_cg_landing_cta_text', true));
    if ($title === '') $title = 'Не нашли подходящий букет?';
    if ($text === '') $text = 'Посмотрите весь каталог или позвоните нам — поможем подобрать вариант под получателя, повод и бюджет.';

    $phone = (string) get_theme_mod('cg_phone', '+7 (930) 411-98-55');
    $tel = preg_replace('/[^0-9+]/', '', $phone);

    echo '<section class="cg-seo-landing__cta">';
    echo '<div><span>Поможем с выбором</span><h2>' . esc_html($title) . '</h2><p>' . esc_html($text) . '</p></div>';
    echo '<div class="cg-seo-landing__cta-actions"><a class="button" href="' . esc_url(cg_catalog_url()) . '">Смотреть весь каталог</a><a class="cg-seo-landing__phone" href="tel:' . esc_attr($tel) . '">' . esc_html($phone) . '</a></div>';
    echo '</section>';
}

/** Internal links from the main catalog to manually published landing pages. */
function cg_seo_landing_catalog_links() {
    if (!is_shop()) return;

    $landings = get_posts([
        'post_type' => 'cg_landing',
        'post_status' => 'publish',
        'posts_per_page' => 8,
        'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
        'suppress_filters' => false,
    ]);
    if (!$landings) return;

    echo '<section class="cg-catalog-landings" aria-labelledby="cg-catalog-landings-title">';
    echo '<div class="cg-catalog-landings__head"><span>Готовые подборки</span><h2 id="cg-catalog-landings-title">Выберите букет по ситуации</h2><p>Отдельные подборки с подходящими товарами и полезной информацией.</p></div>';
    echo '<div class="cg-catalog-landings__grid">';
    foreach ($landings as $landing) {
        $excerpt = has_excerpt($landing) ? get_the_excerpt($landing) : '';
        echo '<a class="cg-catalog-landing-card" href="' . esc_url(get_permalink($landing)) . '">';
        echo '<strong>' . esc_html(get_the_title($landing)) . '</strong>';
        if ($excerpt !== '') echo '<span>' . esc_html(wp_html_excerpt(wp_strip_all_tags($excerpt), 105, '…')) . '</span>';
        echo '<b>Открыть подборку →</b></a>';
    }
    echo '</div></section>';
}

/** Helpful admin columns: target and whether the page is ready for indexing. */
function cg_seo_landing_admin_columns($columns) {
    $result = [];
    foreach ($columns as $key => $label) {
        $result[$key] = $label;
        if ($key === 'title') {
            $result['cg_target'] = 'Подборка товаров';
            $result['cg_seo_ready'] = 'SEO-готовность';
        }
    }
    return $result;
}
add_filter('manage_cg_landing_posts_columns', 'cg_seo_landing_admin_columns');

function cg_seo_landing_admin_column_content($column, $post_id) {
    if ($column === 'cg_target') {
        $term = cg_seo_landing_target($post_id);
        echo $term ? esc_html($term->name) : '<span style="color:#b32d2e">Не выбрана</span>';
        return;
    }

    if ($column === 'cg_seo_ready') {
        $post = get_post($post_id);
        $term = cg_seo_landing_target($post_id);
        $has_excerpt = $post instanceof WP_Post && trim((string) $post->post_excerpt) !== '';
        $has_content = $post instanceof WP_Post && mb_strlen(trim(wp_strip_all_tags((string) $post->post_content))) >= 120;
        $ready = $term && (int) $term->count > 0 && $has_excerpt && $has_content;
        echo $ready ? '<strong style="color:#137333">Готова</strong>' : '<span style="color:#996800">Нужно заполнить</span>';
    }
}
add_action('manage_cg_landing_posts_custom_column', 'cg_seo_landing_admin_column_content', 10, 2);

function cg_seo_landing_admin_styles($hook) {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'cg_landing') return;

    $css = '.cg-seo-landing-admin{padding:4px 2px}.cg-seo-landing-admin>p{max-width:940px;color:#646970;line-height:1.55}.cg-seo-landing-admin__grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-top:16px}.cg-seo-landing-admin__grid label{display:grid;gap:7px;padding:14px;border:1px solid #ddd;border-radius:10px;background:#fff}.cg-seo-landing-admin__grid select,.cg-seo-landing-admin__grid input,.cg-seo-landing-admin__grid textarea{width:100%}.cg-seo-landing-admin__grid label>span{color:#757575;font-size:12px}@media(max-width:1100px){.cg-seo-landing-admin__grid{grid-template-columns:1fr}}';
    wp_add_inline_style('wp-admin', $css);
}
add_action('admin_enqueue_scripts', 'cg_seo_landing_admin_styles', 50);
