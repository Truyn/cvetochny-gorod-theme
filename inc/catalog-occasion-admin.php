<?php
/**
 * Convenience UI for occasion/holiday attributes and catalog filter ordering.
 *
 * The module never creates WooCommerce attributes or terms automatically. It
 * only works with global attributes that already exist in Products → Attributes.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

/** Attribute taxonomies managed by the compact product-editor panel. */
function cg_product_audience_taxonomies() {
    $taxonomies = [];

    foreach ([
        'pa_povod' => 'Повод',
        'pa_prazdniki' => 'Праздники',
    ] as $taxonomy => $label) {
        if (taxonomy_exists($taxonomy)) $taxonomies[$taxonomy] = $label;
    }

    return $taxonomies;
}

/** Add a clear checkbox panel to the WooCommerce product editor. */
function cg_product_audience_add_meta_box() {
    add_meta_box(
        'cg-product-audience',
        'Повод и праздники',
        'cg_product_audience_render_meta_box',
        'product',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes_product', 'cg_product_audience_add_meta_box');

function cg_product_audience_render_meta_box($post) {
    wp_nonce_field('cg_product_audience_save', 'cg_product_audience_nonce');
    $taxonomies = cg_product_audience_taxonomies();

    echo '<div class="cg-product-audience">';
    echo '<p class="cg-product-audience__intro">Быстро отметьте, для каких поводов и праздников подходит товар. Значения берутся из <strong>Товары → Атрибуты</strong>; тема ничего не создаёт автоматически.</p>';

    if (!$taxonomies) {
        echo '<div class="notice notice-warning inline"><p>Глобальные атрибуты <strong>Повод</strong> и <strong>Праздники</strong> пока не найдены. Создайте нужный атрибут в Товары → Атрибуты — после этого его значения появятся здесь.</p></div>';
        echo '</div>';
        return;
    }

    echo '<div class="cg-product-audience__columns">';
    foreach ($taxonomies as $taxonomy => $label) {
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'orderby' => 'name',
        ]);
        $selected = wp_get_object_terms((int) $post->ID, $taxonomy, ['fields' => 'ids']);
        $selected = is_wp_error($selected) ? [] : array_map('absint', $selected);

        echo '<section class="cg-product-audience__group">';
        echo '<div class="cg-product-audience__head"><h3>' . esc_html($label) . '</h3>';
        if (!is_wp_error($terms)) echo '<span>' . esc_html(count($terms)) . ' вариантов</span>';
        echo '</div>';

        if (is_wp_error($terms) || !$terms) {
            echo '<p class="cg-product-audience__empty">У этого атрибута ещё нет значений. Добавьте их в Товары → Атрибуты → Настройка значений.</p>';
            echo '</section>';
            continue;
        }

        echo '<label class="cg-product-audience__search"><span class="screen-reader-text">Поиск: ' . esc_html($label) . '</span><input type="search" placeholder="Найти значение…" data-cg-audience-search></label>';
        echo '<div class="cg-product-audience__terms">';
        foreach ($terms as $term) {
            $checked = in_array((int) $term->term_id, $selected, true);
            echo '<label class="cg-product-audience__term" data-cg-audience-term="' . esc_attr(mb_strtolower((string) $term->name)) . '">';
            echo '<input type="checkbox" name="cg_product_audience[' . esc_attr($taxonomy) . '][]" value="' . esc_attr($term->term_id) . '"' . checked($checked, true, false) . '>';
            echo '<span>' . esc_html($term->name) . '</span>';
            echo '</label>';
        }
        echo '</div></section>';
    }
    echo '</div></div>';
}

/** Save selected global attributes without disturbing any other product attributes. */
function cg_product_audience_save($product) {
    if (!$product instanceof WC_Product) return;
    if (empty($_POST['cg_product_audience_nonce'])) return;
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cg_product_audience_nonce'])), 'cg_product_audience_save')) return;
    if (!current_user_can('edit_post', $product->get_id())) return;

    $posted = isset($_POST['cg_product_audience']) && is_array($_POST['cg_product_audience'])
        ? wp_unslash($_POST['cg_product_audience'])
        : [];
    $attributes = $product->get_attributes();

    foreach (cg_product_audience_taxonomies() as $taxonomy => $label) {
        $term_ids = isset($posted[$taxonomy]) && is_array($posted[$taxonomy])
            ? array_values(array_unique(array_filter(array_map('absint', $posted[$taxonomy]))))
            : [];

        $term_ids = array_values(array_filter($term_ids, static function($term_id) use ($taxonomy) {
            $term = get_term($term_id, $taxonomy);
            return $term instanceof WP_Term && !is_wp_error($term);
        }));

        if (!$term_ids) {
            unset($attributes[$taxonomy]);
            wp_set_object_terms($product->get_id(), [], $taxonomy, false);
            continue;
        }

        $attribute = isset($attributes[$taxonomy]) && $attributes[$taxonomy] instanceof WC_Product_Attribute
            ? $attributes[$taxonomy]
            : new WC_Product_Attribute();

        if (!$attribute->get_id()) {
            $attribute->set_id((int) wc_attribute_taxonomy_id_by_name($taxonomy));
            $attribute->set_name($taxonomy);
            $attribute->set_position(count($attributes));
            $attribute->set_visible(false);
            $attribute->set_variation(false);
        }

        $attribute->set_options($term_ids);
        $attributes[$taxonomy] = $attribute;
    }

    $product->set_attributes($attributes);
}
add_action('woocommerce_admin_process_product_object', 'cg_product_audience_save', 40);

/** Compact, searchable admin UI. */
function cg_product_audience_admin_assets($hook) {
    if (!in_array($hook, ['post.php', 'post-new.php'], true)) return;
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'product') return;

    $css = '
#cg-product-audience .inside{margin:0;padding:0}.cg-product-audience{padding:18px}.cg-product-audience__intro{margin:0 0 16px;color:#646970;line-height:1.55}.cg-product-audience__columns{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.cg-product-audience__group{min-width:0;padding:16px;border:1px solid #e1e1e1;border-radius:10px;background:#fff}.cg-product-audience__head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px}.cg-product-audience__head h3{margin:0;font-size:15px}.cg-product-audience__head span{color:#757575;font-size:12px}.cg-product-audience__search{display:block;margin-bottom:10px}.cg-product-audience__search input{width:100%;min-height:38px}.cg-product-audience__terms{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:7px;max-height:310px;padding:2px;overflow:auto}.cg-product-audience__term{display:flex;align-items:flex-start;gap:8px;padding:8px 9px;border:1px solid #e7e7e7;border-radius:8px;background:#fafafa;cursor:pointer}.cg-product-audience__term:hover{border-color:#d5a4a4;background:#fff8f7}.cg-product-audience__term input{margin-top:1px}.cg-product-audience__term:has(input:checked){border-color:#d59a9f;background:#fff2f1}.cg-product-audience__empty{margin:0;color:#757575}.cg-product-audience__term[hidden]{display:none!important}@media(max-width:1100px){.cg-product-audience__columns{grid-template-columns:1fr}}';
    wp_add_inline_style('woocommerce_admin_styles', $css);

    $js = "document.addEventListener('input',function(e){if(!e.target.matches('[data-cg-audience-search]'))return;var q=(e.target.value||'').trim().toLocaleLowerCase('ru');var group=e.target.closest('.cg-product-audience__group');if(!group)return;group.querySelectorAll('[data-cg-audience-term]').forEach(function(item){item.hidden=q!==''&&!item.dataset.cgAudienceTerm.includes(q);});});";
    wp_add_inline_script('jquery-core', $js, 'after');
}
add_action('admin_enqueue_scripts', 'cg_product_audience_admin_assets', 40);

/**
 * Keep «Повод» directly after the price block in the catalog. The underlying
 * query/filter behavior is untouched; only the already rendered filter group is
 * moved before the quick stock/sale switches.
 */
function cg_product_audience_catalog_order() {
    if (!class_exists('WooCommerce')) return;
    if (!(is_shop() || is_product_taxonomy())) return;

    $js = <<<'JS'
(function(){
    function moveOccasion(){
        var form=document.querySelector('.cg-catalog-filter-form');
        if(!form)return;
        var input=form.querySelector('input[name="filter_povod[]"],input[name="filter_occasion[]"]');
        var quick=form.querySelector('.cg-filter-quick');
        if(!input||!quick)return;
        var group=input.closest('.cg-filter-group');
        if(group&&group.nextElementSibling!==quick)form.insertBefore(group,quick);
    }
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',moveOccasion);else moveOccasion();
})();
JS;
    wp_add_inline_script('cg-ajax-catalog', $js, 'after');
}
add_action('wp_enqueue_scripts', 'cg_product_audience_catalog_order', 55);
