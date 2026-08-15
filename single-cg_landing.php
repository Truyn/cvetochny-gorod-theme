<?php
/**
 * Curated SEO landing page.
 *
 * @package Cvetochny_Gorod
 */

if (!defined('ABSPATH')) exit;

get_header();

while (have_posts()) :
    the_post();
    $post_id = get_the_ID();
    $term = function_exists('cg_seo_landing_target') ? cg_seo_landing_target($post_id) : null;
    $excerpt = trim((string) get_post_field('post_excerpt', $post_id));
    $content = trim((string) get_post_field('post_content', $post_id));
    $taxonomy_label = '';
    if ($term instanceof WP_Term) {
        $taxonomy = get_taxonomy($term->taxonomy);
        if ($taxonomy && !empty($taxonomy->labels->singular_name)) $taxonomy_label = (string) $taxonomy->labels->singular_name;
    }
    ?>
    <div id="primary" class="cg-seo-landing woocommerce">
        <div class="container cg-seo-landing__container">
            <nav class="cg-seo-landing__breadcrumbs" aria-label="Хлебные крошки">
                <a href="<?php echo esc_url(home_url('/')); ?>">Главная</a><span>→</span>
                <a href="<?php echo esc_url(cg_catalog_url()); ?>">Каталог</a><span>→</span>
                <strong><?php the_title(); ?></strong>
            </nav>

            <header class="cg-seo-landing__hero<?php echo has_post_thumbnail() ? ' has-image' : ''; ?>">
                <div class="cg-seo-landing__hero-copy">
                    <span class="cg-seo-landing__eyebrow">Цветочный город<?php echo $taxonomy_label ? ' · ' . esc_html($taxonomy_label) : ''; ?></span>
                    <h1><?php the_title(); ?></h1>
                    <?php if ($excerpt !== '') : ?>
                        <p><?php echo esc_html($excerpt); ?></p>
                    <?php endif; ?>
                    <div class="cg-seo-landing__hero-actions">
                        <a class="button" href="#cg-seo-products-title">Смотреть подборку</a>
                        <a class="cg-seo-landing__catalog-link" href="<?php echo esc_url(cg_catalog_url()); ?>">Весь каталог</a>
                    </div>
                </div>
                <?php if (has_post_thumbnail()) : ?>
                    <div class="cg-seo-landing__hero-image"><?php the_post_thumbnail('large', ['loading' => 'eager']); ?></div>
                <?php endif; ?>
            </header>

            <?php if (function_exists('cg_seo_landing_trust_strip')) cg_seo_landing_trust_strip(); ?>

            <?php if ($content !== '') : ?>
                <section class="cg-seo-landing__content">
                    <?php the_content(); ?>
                </section>
            <?php endif; ?>

            <?php if (function_exists('cg_seo_landing_render_products')) cg_seo_landing_render_products($post_id); ?>
            <?php if (function_exists('cg_seo_landing_cta')) cg_seo_landing_cta($post_id); ?>
        </div>
    </div>
    <?php
endwhile;

get_footer();
