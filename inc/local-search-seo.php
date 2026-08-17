<?php
/**
 * Lightweight local-search defaults for the Novovoronezh flower shop.
 * Advanced SEO plugins and manually saved snippets still take precedence.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

function cg_local_search_is_managed_elsewhere() {
    return function_exists('cg_launch_has_seo_plugin') && cg_launch_has_seo_plugin();
}

function cg_local_search_title_parts($parts) {
    if (cg_local_search_is_managed_elsewhere()) return $parts;

    if (is_front_page()) {
        $parts['title'] = 'Цветы и букеты с доставкой в Нововоронеже';
        return $parts;
    }

    if (function_exists('is_shop') && is_shop()) {
        $parts['title'] = 'Каталог цветов и букетов в Нововоронеже';
        return $parts;
    }

    if (function_exists('is_product_category') && is_product_category()) {
        $term = get_queried_object();
        if ($term instanceof WP_Term) {
            $name = trim((string) $term->name);
            if ($name !== '' && mb_stripos($name, 'Нововоронеж') === false) {
                $parts['title'] = $name . ' с доставкой в Нововоронеже';
            }
        }
        return $parts;
    }

    if (function_exists('is_product') && is_product()) {
        $product = function_exists('wc_get_product') ? wc_get_product(get_queried_object_id()) : false;
        if ($product) {
            $name = trim((string) $product->get_name());
            if ($name !== '' && mb_stripos($name, 'Нововоронеж') === false && mb_strlen($name) <= 55) {
                $parts['title'] = $name . ' — доставка по Нововоронежу';
            }
        }
    }

    return $parts;
}
add_filter('document_title_parts', 'cg_local_search_title_parts', 20);

function cg_local_search_relevant_screen() {
    return is_front_page()
        || (function_exists('is_shop') && is_shop())
        || (function_exists('is_product') && is_product())
        || (function_exists('is_product_taxonomy') && is_product_taxonomy())
        || is_singular('cg_landing');
}

function cg_local_search_description() {
    $description = function_exists('cg_launch_meta_description') ? cg_launch_meta_description() : '';
    $description = trim((string) $description);

    if (!cg_local_search_relevant_screen()) return $description;

    if ($description === '') {
        $description = 'Свежие цветы и букеты с доставкой по Нововоронежу. Фото готового букета перед отправкой, открытка и удобное время доставки.';
    } elseif (mb_stripos($description, 'Нововоронеж') === false && mb_strlen($description) < 135) {
        $description .= ' Доставка цветов по Нововоронежу и Воронежской области.';
    }

    return wp_html_excerpt(preg_replace('/\s+/u', ' ', $description), 180, '…');
}

function cg_local_search_prepare_fallback_meta() {
    if (cg_local_search_is_managed_elsewhere()) return;
    if (function_exists('cg_launch_is_noindex_screen') && cg_launch_is_noindex_screen()) return;
    if (function_exists('cg_seo_three_custom_description') && cg_seo_three_custom_description() !== '') return;
    if (!cg_local_search_relevant_screen()) return;

    remove_action('wp_head', 'cg_launch_output_fallback_meta', 4);
    add_action('wp_head', 'cg_local_search_output_fallback_meta', 4);
}
add_action('wp', 'cg_local_search_prepare_fallback_meta', 35);

function cg_local_search_output_fallback_meta() {
    $title = wp_get_document_title();
    $description = cg_local_search_description();
    $url = is_singular() ? get_permalink() : home_url(add_query_arg([], $GLOBALS['wp']->request ?? ''));
    $image = function_exists('cg_launch_social_image') ? cg_launch_social_image() : '';
    $type = (function_exists('is_product') && is_product()) ? 'product' : (is_singular() ? 'article' : 'website');

    if ($description !== '') echo "\n<meta name=\"description\" content=\"" . esc_attr($description) . "\">";
    echo "\n<meta property=\"og:site_name\" content=\"" . esc_attr(get_bloginfo('name')) . "\">";
    echo "\n<meta property=\"og:title\" content=\"" . esc_attr($title) . "\">";
    echo "\n<meta property=\"og:type\" content=\"" . esc_attr($type) . "\">";
    echo "\n<meta property=\"og:url\" content=\"" . esc_url($url) . "\">";
    if ($description !== '') echo "\n<meta property=\"og:description\" content=\"" . esc_attr($description) . "\">";
    if ($image !== '') echo "\n<meta property=\"og:image\" content=\"" . esc_url($image) . "\">";
    echo "\n<meta name=\"twitter:card\" content=\"" . ($image !== '' ? 'summary_large_image' : 'summary') . "\">";
    echo "\n<meta name=\"twitter:title\" content=\"" . esc_attr($title) . "\">";
    if ($description !== '') echo "\n<meta name=\"twitter:description\" content=\"" . esc_attr($description) . "\">";
    if ($image !== '') echo "\n<meta name=\"twitter:image\" content=\"" . esc_url($image) . "\">";
    echo "\n";
}
