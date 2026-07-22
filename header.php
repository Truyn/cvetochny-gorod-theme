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
    <div class="container topbar__inner">
        <div class="topbar__group topbar__group--left">
            <span class="topbar__item">
                <svg class="cg-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s6-5.4 6-11A6 6 0 0 0 6 10c0 5.6 6 11 6 11Zm0-8.5A2.5 2.5 0 1 1 12 7a2.5 2.5 0 0 1 0 5.5Z"/></svg>
                <?php echo esc_html(get_theme_mod('cg_address', 'Нововоронеж, Воронежская область')); ?>
            </span>
            <span class="topbar__item">
                <svg class="cg-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h11v9H3zM14 10h3l4 4v2h-7zM6.5 19a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm11 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z"/></svg>
                Доставка и оплата
            </span>
        </div>
        <div class="topbar__group topbar__group--right">
            <a class="topbar__phone" href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', get_theme_mod('cg_phone', '+7 (900) 000-00-00'))); ?>">
                <svg class="cg-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 2.8 9.4 7l-2.1 2.1c1.1 2.4 3.1 4.4 5.5 5.5l2.1-2.1 4.2 2.8-.6 4.1c-.1.8-.8 1.4-1.6 1.4C9.1 20.8 3.2 14.9 3.2 7.1c0-.8.6-1.5 1.4-1.6l2-.3Z"/></svg>
                <?php echo esc_html(get_theme_mod('cg_phone', '+7 (900) 000-00-00')); ?>
            </a>
            <div class="topbar__socials" aria-label="Социальные сети">
                <?php if (get_theme_mod('cg_whatsapp_url', '')): ?><a href="<?php echo esc_url(get_theme_mod('cg_whatsapp_url')); ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp">WA</a><?php endif; ?>
                <?php if (get_theme_mod('cg_telegram_url', '')): ?><a href="<?php echo esc_url(get_theme_mod('cg_telegram_url')); ?>" target="_blank" rel="noopener noreferrer" aria-label="Telegram">TG</a><?php endif; ?>
                <?php if (get_theme_mod('cg_vk_url', '')): ?><a href="<?php echo esc_url(get_theme_mod('cg_vk_url')); ?>" target="_blank" rel="noopener noreferrer" aria-label="ВКонтакте">VK</a><?php endif; ?>
                <?php if (get_theme_mod('cg_instagram_url', '')): ?><a href="<?php echo esc_url(get_theme_mod('cg_instagram_url')); ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram">IG</a><?php endif; ?>
            </div>
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
                <span class="brand-mark" aria-hidden="true">✿</span>
                <span>
                    <span class="brand-title"><?php echo esc_html(get_theme_mod('cg_brand_title', 'Цветочный город')); ?></span>
                    <span class="brand-subtitle"><?php echo esc_html(get_theme_mod('cg_brand_subtitle', 'магазин цветов')); ?></span>
                </span>
            <?php endif; ?>
        </a>

        <nav class="main-navigation" id="site-menu" aria-label="Главное меню">
            <?php wp_nav_menu(['theme_location' => 'primary', 'container' => false, 'fallback_cb' => 'cg_fallback_menu']); ?>
        </nav>

        <div class="header-actions">
            <?php if (class_exists('WooCommerce')): ?>
                <button class="icon-button search-toggle" type="button" aria-controls="header-search" aria-expanded="false" aria-label="Открыть поиск">
                    <svg class="cg-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m21 21-4.7-4.7M10.8 18a7.2 7.2 0 1 1 0-14.4 7.2 7.2 0 0 1 0 14.4Z"/></svg>
                </button>
                <a class="icon-button" href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" aria-label="Личный кабинет">
                    <svg class="cg-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0"/></svg>
                </a>
                <a class="icon-button" href="<?php echo esc_url(wc_get_cart_url()); ?>" aria-label="Корзина">
                    <svg class="cg-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 8h12l1 12H5L6 8Zm3 0a3 3 0 0 1 6 0"/></svg>
                    <span class="cart-count"><?php echo WC()->cart ? WC()->cart->get_cart_contents_count() : 0; ?></span>
                </a>
            <?php endif; ?>
            <button class="menu-toggle" type="button" aria-controls="site-menu" aria-expanded="false" aria-label="Открыть меню">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
    <?php if (class_exists('WooCommerce')): ?>
        <div class="header-search" id="header-search" hidden>
            <div class="container"><?php cg_product_search_form(); ?></div>
        </div>
    <?php endif; ?>
</header>
<main class="site-main">