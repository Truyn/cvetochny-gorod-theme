<?php
/**
 * Template Name: Elementor — на всю ширину
 * Template Post Type: page
 */
if (!defined('ABSPATH')) exit;
get_header();
?>
<main id="primary" class="site-main elementor-full-width-page">
<?php
while (have_posts()) {
    the_post();
    the_content();
}
?>
</main>
<?php get_footer();
