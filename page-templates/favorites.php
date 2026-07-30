<?php
if (!defined('ABSPATH')) exit;

get_header();
?>
<div class="container cg-favorites-page-wrap">
    <?php echo do_shortcode('[cg_favorites]'); ?>
</div>
<?php get_footer();
