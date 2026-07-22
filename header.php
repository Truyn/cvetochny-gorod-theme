<?php if (!defined('ABSPATH')) exit; ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php if (get_theme_mod('cg_show_topbar', true)): ?>
<div class="topbar">
    <div class="container">
        <div class="topbar__group">
            <span>📍 <?php echo esc_html(get_theme_mod('cg_address', 'Нововоронеж, Воронежская область')); ?></span>
            <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', get_theme_mod('cg_phone', '+7 (900) 000-00-00'))); ?>">☎ <?php echo esc_html(get_theme_mod('cg_phone', '+7 (900) 000-00-00')); ?></a>
        </div>
        <div class="topbar__group">
            <span><?php echo esc_html(get_theme_mod('cg_worktime', 'Ежедневно с 09:00 до 21:00')); ?></span>
            <?php if (get_theme_mod('cg_whatsapp_url', '')): ?>
                <a href="<?php echo esc_url(get_theme_mod('cg_whatsapp_url')); ?>" target="_blank" rel="noopener">WhatsApp</a>
            <?php endif; ?>
            <?php if (get_theme_mod('cg_telegram_url', '')): ?>
                <a href="<?php echo esc_url(get_theme_mod('cg_telegram_url')); ?>" target="_blank" rel="noopener">Telegram</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?php echo esc_url(home_url('/')); ?>">
            <?php if (has_custom_logo()): ?>
                <?php the_custom_logo(); ?>
            <?php else: ?>
                <span class="brand-mark">✿</span>
                <span>
                    <span class="brand-title"><?php echo esc_html(get_theme_mod('cg_brand_title', 'Цветочный город')); ?></span>
                    <span class="brand-subtitle"><?php echo esc_html(get_theme_mod('cg_brand_subtitle', 'магазин цветов')); ?></span>
                </span>
            <?php endif; ?>
        </a>

        <div class="header-center">
            <nav class="main-navigation" id="site-menu">
                <?php wp_nav_menu(['theme_location' => 'primary', 'container' => false, 'fallback_cb' => 'cg_fallback_menu']); ?>
            </nav>
            <?php if (class_exists('WooCommerce')) cg_product_search_form(); ?>
        </div>

        <div class="header-actions">
            <button class="menu-toggle" aria-controls="site-menu" aria-expanded="false">☰</button>
            <?php if (class_exists('WooCommerce')): ?>
                <a class="icon-button" href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" aria-label="Личный кабинет">👤</a>
                <a class="icon-button" href="<?php echo esc_url(wc_get_cart_url()); ?>" aria-label="Корзина">🛍<span class="cart-count"><?php echo WC()->cart ? WC()->cart->get_cart_contents_count() : 0; ?></span></a>
            <?php endif; ?>
        </div>
    </div>
</header>
<main class="site-main">
