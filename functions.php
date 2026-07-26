<?php
if (!defined('ABSPATH')) exit;

require_once get_template_directory() . '/inc/product-page.php';
require_once get_template_directory() . '/inc/account-search.php';
require_once get_template_directory() . '/inc/home-slider.php';
require_once get_template_directory() . '/inc/home-sections.php';
require_once get_template_directory() . '/inc/site-customizer.php';
require_once get_template_directory() . '/inc/delivery-options.php';
require_once get_template_directory() . '/inc/mini-cart.php';
require_once get_template_directory() . '/inc/ajax-catalog.php';

function cg_setup() {
    load_theme_textdomain('cvetochny-gorod', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', ['height'=>90,'width'=>260,'flex-height'=>true,'flex-width'=>true]);
    add_theme_support('woocommerce');
    add_theme_support('align-wide');
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');
    add_editor_style('style.css');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
    add_theme_support('html5', ['search-form','comment-form','comment-list','gallery','caption','style','script']);
    register_nav_menus(['primary'=>'Главное меню','footer'=>'Меню в подвале']);
}
add_action('after_setup_theme','cg_setup');

function cg_assets() {
    $version = wp_get_theme()->get('Version');
    wp_enqueue_style('cg-style', get_stylesheet_uri(), [], $version);
    if (is_front_page()) {
        $homepage_css = get_template_directory() . '/assets/css/homepage.css';
        wp_enqueue_style('cg-homepage', get_template_directory_uri().'/assets/css/homepage.css', ['cg-style'], file_exists($homepage_css) ? filemtime($homepage_css) : $version);
    }
    if (class_exists('WooCommerce')) {
        wp_enqueue_style('cg-woocommerce', get_template_directory_uri().'/assets/css/woocommerce.css', ['cg-style'], $version);
        wp_enqueue_style('cg-product-hotfix', get_template_directory_uri().'/assets/css/hotfix-products.css', ['cg-woocommerce'], $version);
        wp_enqueue_style('cg-mini-cart', get_template_directory_uri().'/assets/css/mini-cart.css', ['cg-woocommerce'], $version);
    }
    wp_enqueue_script('cg-main', get_template_directory_uri().'/assets/js/main.js', [], $version, true);
    if (class_exists('WooCommerce')) {
        wp_enqueue_script('cg-mini-cart', get_template_directory_uri().'/assets/js/mini-cart.js', ['jquery'], $version, true);
        wp_localize_script('cg-mini-cart', 'cgMiniCart', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cg_mini_cart'),
        ]);

        if (is_shop() || is_product_taxonomy()) {
            $catalog_css = get_template_directory() . '/assets/css/ajax-catalog.css';
            $catalog_js = get_template_directory() . '/assets/js/ajax-catalog.js';
            wp_enqueue_style('cg-ajax-catalog', get_template_directory_uri().'/assets/css/ajax-catalog.css', ['cg-woocommerce'], file_exists($catalog_css) ? filemtime($catalog_css) : $version);
            wp_enqueue_script('cg-ajax-catalog', get_template_directory_uri().'/assets/js/ajax-catalog.js', [], file_exists($catalog_js) ? filemtime($catalog_js) : $version, true);
        }
    }
}
add_action('wp_enqueue_scripts','cg_assets');

function cg_widgets() {
    register_sidebar(['name'=>'Подвал: колонка 1','id'=>'footer-1','before_widget'=>'<div class="footer-widget">','after_widget'=>'</div>','before_title'=>'<div class="footer-title">','after_title'=>'</div>']);
}
add_action('widgets_init','cg_widgets');

function cg_customize($wp_customize) {
    $wp_customize->add_section('cg_home',['title'=>'Цветочный город: Главная','priority'=>30]);
    $fields = [
      'cg_hero_title'=>['Заголовок первого экрана','Дарите эмоции вместе с цветами','text'],
      'cg_hero_text'=>['Текст первого экрана','Свежие цветы, стильные букеты и быстрая доставка по Нововоронежу и Воронежской области.','textarea'],
      'cg_hero_button'=>['Текст кнопки','Смотреть каталог','text'],
      'cg_phone'=>['Телефон','+7 (900) 000-00-00','text'],
      'cg_address'=>['Адрес','Нововоронеж, Воронежская область','text'],
      'cg_worktime'=>['Режим работы','Ежедневно с 09:00 до 21:00','text']
    ];
    foreach($fields as $id=>$f){$wp_customize->add_setting($id,['default'=>$f[1],'sanitize_callback'=>$f[2]==='textarea'?'sanitize_textarea_field':'sanitize_text_field']);$wp_customize->add_control($id,['label'=>$f[0],'section'=>'cg_home','type'=>$f[2]]);}    
    $wp_customize->add_setting('cg_hero_image',['sanitize_callback'=>'esc_url_raw']);
    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize,'cg_hero_image',['label'=>'Изображение первого экрана','section'=>'cg_home']));
}
add_action('customize_register','cg_customize');

function cg_cart_count_fragment($fragments) {
    ob_start(); ?><span class="cart-count"><?php echo WC()->cart ? WC()->cart->get_cart_contents_count() : 0; ?></span><?php
    $fragments['.cart-count'] = ob_get_clean(); return $fragments;
}
add_filter('woocommerce_add_to_cart_fragments','cg_cart_count_fragment');

function cg_catalog_url(){ return class_exists('WooCommerce') ? wc_get_page_permalink('shop') : home_url('/'); }

function cg_fallback_menu(){ echo '<ul><li><a href="'.esc_url(home_url('/')).'">Главная</a></li><li><a href="'.esc_url(cg_catalog_url()).'">Каталог</a></li><li><a href="'.esc_url(home_url('/about/')).'">О нас</a></li><li><a href="'.esc_url(home_url('/contacts/')).'">Контакты</a></li></ul>'; }

add_filter('loop_shop_columns', fn()=>4);
add_filter('loop_shop_per_page', fn($n)=>12, 20);

function cg_register_elementor_locations($elementor_theme_manager) {
    if (method_exists($elementor_theme_manager, 'register_all_core_location')) {
        $elementor_theme_manager->register_all_core_location();
    }
}
add_action('elementor/theme/register_locations', 'cg_register_elementor_locations');

function cg_elementor_is_built($post_id = 0) {
    if (!did_action('elementor/loaded')) return false;
    $post_id = $post_id ?: get_the_ID();
    return $post_id && \Elementor\Plugin::$instance->db->is_built_with_elementor($post_id);
}

function cg_elementor_content_width() { return 1240; }
add_filter('elementor/settings/kit_default_settings', function($settings){
    $settings['container_width'] = 1240;
    $settings['space_between_widgets'] = 20;
    return $settings;
});

function cg_body_classes($classes) {
    if (cg_elementor_is_built()) $classes[] = 'cg-page-built-with-elementor';
    if (class_exists('WooCommerce')) $classes[] = 'cg-woocommerce-active';
    return $classes;
}
add_filter('body_class', 'cg_body_classes');

/**
 * Keep WooCommerce's wrappers untouched for archive-product.php.
 * The theme archive template owns the catalog shell directly.
 */
remove_action('woocommerce_sidebar','woocommerce_get_sidebar',10);
add_filter('woocommerce_show_page_title', '__return_false');

function cg_catalog_heading() {
    if (!(is_shop() || is_product_taxonomy())) return;
    $title = is_shop() ? 'Каталог букетов' : single_term_title('', false);
    $subtitle = is_shop()
        ? 'Выберите букет по случаю, стилю и бюджету.'
        : 'Подборка букетов из выбранной категории.';
    echo '<header class="cg-catalog-heading"><span>Цветочный город</span><h1>'.esc_html($title).'</h1><p>'.esc_html($subtitle).'</p></header>';
}
add_action('woocommerce_before_shop_loop', 'cg_catalog_heading', 5);

function cg_shop_toolbar_start(){ echo '<div class="cg-shop-toolbar">'; }
function cg_shop_toolbar_end(){ echo '</div>'; }
add_action('woocommerce_before_shop_loop','cg_shop_toolbar_start',15);
add_action('woocommerce_before_shop_loop','cg_shop_toolbar_end',35);

function cg_loop_product_media(){
    global $product;
    if (!$product) return;
    echo '<div class="cg-product-image-wrap"><div class="cg-product-badges">';
    if ($product->is_on_sale()) echo '<span class="cg-product-badge cg-product-badge--sale">Скидка</span>';
    $created = $product->get_date_created();
    if ($created && (time() - $created->getTimestamp()) < DAY_IN_SECONDS * 30) echo '<span class="cg-product-badge cg-product-badge--new">Новинка</span>';
    if ($product->is_featured()) echo '<span class="cg-product-badge cg-product-badge--hit">Хит</span>';
    echo '</div>'.woocommerce_get_product_thumbnail('woocommerce_thumbnail').'</div>';
}
remove_action('woocommerce_before_shop_loop_item_title','woocommerce_show_product_loop_sale_flash',10);
remove_action('woocommerce_before_shop_loop_item_title','woocommerce_template_loop_product_thumbnail',10);
add_action('woocommerce_before_shop_loop_item_title','cg_loop_product_media',10);

function cg_loop_product_meta(){
    global $product;
    if (!$product) return;
    $parts = [];
    if ($product->is_in_stock()) $parts[] = 'В наличии';
    if ($product->get_shipping_class()) $parts[] = 'Доставка сегодня';
    if (!$parts) return;
    echo '<div class="cg-product-meta"><span>'.implode('</span><span>', array_map('esc_html', $parts)).'</span></div>';
}
add_action('woocommerce_after_shop_loop_item_title','cg_loop_product_meta',7);

function cg_admin_notice() {
    if (!current_user_can('manage_options') || get_option('cg_setup_notice_dismissed')) return;
    echo '<div class="notice notice-info is-dismissible"><p><strong>Цветочный город:</strong> для визуального редактирования установите Elementor и WooCommerce. Для главной страницы выберите шаблон «Elementor — на всю ширину».</p></div>';
}
add_action('admin_notices','cg_admin_notice');
