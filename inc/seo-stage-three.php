<?php
/**
 * SEO stage three: editable snippets and conservative structured data.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

function cg_seo_three_has_plugin() {
    return function_exists('cg_launch_has_seo_plugin') && cg_launch_has_seo_plugin();
}

/** Product snippet fields. */
function cg_seo_three_product_box() {
    add_meta_box('cg-product-seo-snippet', 'SEO-сниппет', 'cg_seo_three_product_box_render', 'product', 'side', 'default');
}
add_action('add_meta_boxes_product', 'cg_seo_three_product_box');

function cg_seo_three_product_box_render($post) {
    wp_nonce_field('cg_seo_three_product_save', 'cg_seo_three_product_nonce');
    $title = (string) get_post_meta($post->ID, '_cg_seo_title', true);
    $description = (string) get_post_meta($post->ID, '_cg_seo_description', true);
    echo '<p><label for="cg_seo_title"><strong>SEO title</strong></label><input class="widefat" id="cg_seo_title" name="cg_seo_title" value="' . esc_attr($title) . '" maxlength="90" placeholder="Если пусто — используется название товара"></p>';
    echo '<p><label for="cg_seo_description"><strong>Meta description</strong></label><textarea class="widefat" id="cg_seo_description" name="cg_seo_description" rows="4" maxlength="220" placeholder="Коротко: что за товар, доставка, главное преимущество">' . esc_textarea($description) . '</textarea></p>';
    echo '<p style="color:#646970;font-size:12px">Поля используются только если отдельный SEO-плагин не управляет этой страницей.</p>';
}

function cg_seo_three_product_save($post_id) {
    if (get_post_type($post_id) !== 'product') return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (empty($_POST['cg_seo_three_product_nonce'])) return;
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cg_seo_three_product_nonce'])), 'cg_seo_three_product_save')) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $title = isset($_POST['cg_seo_title']) ? sanitize_text_field(wp_unslash($_POST['cg_seo_title'])) : '';
    $description = isset($_POST['cg_seo_description']) ? sanitize_textarea_field(wp_unslash($_POST['cg_seo_description'])) : '';
    update_post_meta($post_id, '_cg_seo_title', $title);
    update_post_meta($post_id, '_cg_seo_description', $description);
}
add_action('save_post_product', 'cg_seo_three_product_save', 30);

/** Product-category snippet fields. */
function cg_seo_three_category_add_fields() {
    wp_nonce_field('cg_seo_three_category_save', 'cg_seo_three_category_nonce');
    ?>
    <div class="form-field"><label for="cg_category_seo_title">SEO title</label><input type="text" id="cg_category_seo_title" name="cg_category_seo_title" maxlength="90"><p>Необязательно. Если пусто — используется название категории.</p></div>
    <div class="form-field"><label for="cg_category_seo_description">Meta description</label><textarea id="cg_category_seo_description" name="cg_category_seo_description" rows="3" maxlength="220"></textarea><p>Короткое описание для поискового сниппета, не большой SEO-текст категории.</p></div>
    <?php
}
add_action('product_cat_add_form_fields', 'cg_seo_three_category_add_fields', 30);

function cg_seo_three_category_edit_fields($term) {
    if (!$term instanceof WP_Term) return;
    $title = (string) get_term_meta($term->term_id, '_cg_seo_title', true);
    $description = (string) get_term_meta($term->term_id, '_cg_seo_description', true);
    wp_nonce_field('cg_seo_three_category_save', 'cg_seo_three_category_nonce');
    ?>
    <tr class="form-field"><th scope="row"><label for="cg_category_seo_title">SEO title</label></th><td><input class="large-text" type="text" id="cg_category_seo_title" name="cg_category_seo_title" value="<?php echo esc_attr($title); ?>" maxlength="90"><p class="description">Необязательно. Меняет только title страницы, а не видимый H1.</p></td></tr>
    <tr class="form-field"><th scope="row"><label for="cg_category_seo_description">Meta description</label></th><td><textarea class="large-text" id="cg_category_seo_description" name="cg_category_seo_description" rows="3" maxlength="220"><?php echo esc_textarea($description); ?></textarea><p class="description">Короткий поисковый сниппет. Большой полезный текст оставляйте в стандартном «Описании».</p></td></tr>
    <?php
}
add_action('product_cat_edit_form_fields', 'cg_seo_three_category_edit_fields', 30);

function cg_seo_three_category_save($term_id) {
    if (empty($_POST['cg_seo_three_category_nonce'])) return;
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cg_seo_three_category_nonce'])), 'cg_seo_three_category_save')) return;
    if (!current_user_can('manage_product_terms')) return;
    $title = isset($_POST['cg_category_seo_title']) ? sanitize_text_field(wp_unslash($_POST['cg_category_seo_title'])) : '';
    $description = isset($_POST['cg_category_seo_description']) ? sanitize_textarea_field(wp_unslash($_POST['cg_category_seo_description'])) : '';
    update_term_meta($term_id, '_cg_seo_title', $title);
    update_term_meta($term_id, '_cg_seo_description', $description);
}
add_action('created_product_cat', 'cg_seo_three_category_save', 30);
add_action('edited_product_cat', 'cg_seo_three_category_save', 30);

/** Custom title while the theme owns SEO metadata. */
function cg_seo_three_title_parts($parts) {
    if (cg_seo_three_has_plugin()) return $parts;

    if (is_product()) {
        $title = trim((string) get_post_meta(get_queried_object_id(), '_cg_seo_title', true));
        if ($title !== '') $parts['title'] = $title;
    } elseif (is_product_category()) {
        $term = get_queried_object();
        if ($term instanceof WP_Term) {
            $title = trim((string) get_term_meta($term->term_id, '_cg_seo_title', true));
            if ($title !== '') $parts['title'] = $title;
        }
    }
    return $parts;
}
add_filter('document_title_parts', 'cg_seo_three_title_parts', 30);

function cg_seo_three_description($description) {
    if (cg_seo_three_has_plugin()) return $description;

    if (is_product()) {
        $custom = trim((string) get_post_meta(get_queried_object_id(), '_cg_seo_description', true));
        if ($custom !== '') return wp_html_excerpt(preg_replace('/\s+/u', ' ', wp_strip_all_tags($custom)), 180, '…');
    } elseif (is_product_category()) {
        $term = get_queried_object();
        if ($term instanceof WP_Term) {
            $custom = trim((string) get_term_meta($term->term_id, '_cg_seo_description', true));
            if ($custom !== '') return wp_html_excerpt(preg_replace('/\s+/u', ' ', wp_strip_all_tags($custom)), 180, '…');
        }
    }
    return $description;
}
add_filter('cg_launch_meta_description', 'cg_seo_three_description', 20);

function cg_seo_three_breadcrumb_items() {
    $items = [
        ['name' => 'Главная', 'url' => home_url('/')],
    ];
    $catalog = function_exists('cg_catalog_url') ? cg_catalog_url() : (function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/'));

    if (is_product_category()) {
        $items[] = ['name' => 'Каталог', 'url' => $catalog];
        $term = get_queried_object();
        if ($term instanceof WP_Term) {
            $ancestors = array_reverse(get_ancestors($term->term_id, 'product_cat'));
            foreach ($ancestors as $ancestor_id) {
                $ancestor = get_term($ancestor_id, 'product_cat');
                if (!$ancestor instanceof WP_Term || is_wp_error($ancestor)) continue;
                $url = get_term_link($ancestor);
                if (!is_wp_error($url)) $items[] = ['name' => $ancestor->name, 'url' => $url];
            }
            $items[] = ['name' => $term->name, 'url' => get_term_link($term)];
        }
    } elseif (is_product()) {
        $items[] = ['name' => 'Каталог', 'url' => $catalog];
        $product_id = get_queried_object_id();
        $terms = wp_get_post_terms($product_id, 'product_cat');
        if (!is_wp_error($terms) && $terms) {
            usort($terms, static function ($a, $b) {
                return count(get_ancestors($b->term_id, 'product_cat')) <=> count(get_ancestors($a->term_id, 'product_cat'));
            });
            $term = $terms[0];
            $url = get_term_link($term);
            if (!is_wp_error($url)) $items[] = ['name' => $term->name, 'url' => $url];
        }
        $items[] = ['name' => get_the_title($product_id), 'url' => get_permalink($product_id)];
    } elseif (is_singular('cg_landing')) {
        $items[] = ['name' => 'Каталог', 'url' => $catalog];
        $items[] = ['name' => get_the_title(), 'url' => get_permalink()];
    } else {
        return [];
    }

    return $items;
}

function cg_seo_three_structured_data() {
    if (cg_seo_three_has_plugin()) return;

    $graphs = [];
    $crumbs = cg_seo_three_breadcrumb_items();
    if ($crumbs) {
        $elements = [];
        foreach ($crumbs as $index => $item) {
            $url = is_wp_error($item['url']) ? '' : (string) $item['url'];
            $elements[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $url,
            ];
        }
        $graphs[] = ['@type' => 'BreadcrumbList', 'itemListElement' => $elements];
    }

    if (is_front_page() || is_page('contacts') || is_page_template('page-templates/contacts.php')) {
        $phone = trim((string) get_theme_mod('cg_phone', '+7 (930) 411-98-55'));
        $address = trim((string) get_theme_mod('cg_address', 'Нововоронеж, ул. Победы, 1Б'));
        $worktime = trim((string) get_theme_mod('cg_worktime', 'Ежедневно с 07:00 до 21:00'));
        $opens = '07:00';
        $closes = '21:00';
        if (preg_match('/(\d{1,2}:\d{2}).*?(\d{1,2}:\d{2})/u', $worktime, $matches)) {
            $opens = $matches[1];
            $closes = $matches[2];
        }
        $logo_id = (int) get_theme_mod('custom_logo');
        $logo = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : '';
        $same_as = array_values(array_filter([
            get_theme_mod('cg_vk_url', ''),
            get_theme_mod('cg_instagram_url', ''),
        ]));

        $local = [
            '@type' => 'Florist',
            '@id' => home_url('/#store'),
            'name' => (string) get_theme_mod('cg_brand_title', get_bloginfo('name')),
            'url' => home_url('/'),
            'telephone' => $phone,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $address,
                'addressLocality' => 'Нововоронеж',
                'addressRegion' => 'Воронежская область',
                'addressCountry' => 'RU',
            ],
            'openingHoursSpecification' => [[
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'],
                'opens' => $opens,
                'closes' => $closes,
            ]],
        ];
        if ($logo) $local['image'] = $logo;
        if ($same_as) $local['sameAs'] = $same_as;
        $graphs[] = $local;
    }

    if (!$graphs) return;
    echo "\n<script type=\"application/ld+json\">" . wp_json_encode([
        '@context' => 'https://schema.org',
        '@graph' => $graphs,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</script>\n";
}
add_action('wp_head', 'cg_seo_three_structured_data', 8);
