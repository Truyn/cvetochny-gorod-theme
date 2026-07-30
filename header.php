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
                <?php if (get_theme_mod('cg_whatsapp_url', '')): ?><a class="cg-social-link cg-social-link--whatsapp" href="<?php echo esc_url(get_theme_mod('cg_whatsapp_url')); ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp"><svg class="cg-social-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3.2a8.6 8.6 0 0 0-7.4 13l-1 4.1 4.2-1.1A8.6 8.6 0 1 0 12 3.2Z"/><path d="M8.5 7.8c.2-.4.4-.4.7-.4h.4c.2 0 .4 0 .5.4l.8 1.9c.1.2.1.4-.1.6l-.6.8c-.2.2-.2.4-.1.6.5 1.1 1.6 2.2 2.7 2.7.2.1.4.1.6-.1l.8-1c.2-.2.4-.3.7-.2l1.8.9c.3.1.4.3.4.5 0 .7-.4 1.5-1 1.9-.5.4-1.2.6-1.9.4-1.8-.5-3.5-1.5-4.9-2.9-1.3-1.3-2.2-2.8-2.6-4.4-.2-.6 0-1.3.4-1.7.3-.4.8-.7 1.4-.7Z"/></svg></a><?php endif; ?>
                <?php if (get_theme_mod('cg_telegram_url', '')): ?><a class="cg-social-link cg-social-link--telegram" href="<?php echo esc_url(get_theme_mod('cg_telegram_url')); ?>" target="_blank" rel="noopener noreferrer" aria-label="Telegram"><svg class="cg-social-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m20.8 4.3-3.2 15.1c-.2.9-.8 1.1-1.5.7l-4.8-3.5-2.3 2.2c-.3.3-.5.5-.9.5l.3-4.9 8.9-8c.4-.4-.1-.6-.6-.2L5.7 13.1 1 11.6c-1-.3-1-1 .2-1.5L19.5 3c.8-.3 1.6.2 1.3 1.3Z"/></svg></a><?php endif; ?>
                <?php if (get_theme_mod('cg_vk_url', '')): ?><a class="cg-social-link cg-social-link--vk" href="<?php echo esc_url(get_theme_mod('cg_vk_url')); ?>" target="_blank" rel="noopener noreferrer" aria-label="ВКонтакте"><svg class="cg-social-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12.6 17.5h1.3s.4 0 .6-.3c.2-.2.2-.6.2-.6s0-1.8.8-2.1c.8-.2 1.8 1.7 2.9 2.5.8.6 1.4.5 1.4.5h2.8s1.5-.1.8-1.3c-.1-.1-.4-.9-2.1-2.4-1.8-1.7-1.6-1.4.6-4.3 1.3-1.8 1.9-2.9 1.7-3.4-.2-.4-1.2-.3-1.2-.3h-3.2s-.2 0-.4.1c-.2.1-.3.3-.3.3s-.5 1.4-1.2 2.6c-1.5 2.5-2.1 2.6-2.4 2.4-.6-.4-.4-1.6-.4-2.5 0-2.7.4-3.8-.8-4.1-.4-.1-.7-.2-1.8-.2-1.4 0-2.6 0-3.3.3-.5.2-.8.6-.6.6.2 0 .8.1 1.1.5.4.5.4 1.7.4 1.7s.2 3.2-.5 3.6c-.5.3-1.2-.3-2.6-2.5-.7-1.1-1.2-2.3-1.2-2.3s-.1-.3-.3-.4c-.2-.1-.5-.2-.5-.2H1.4s-.4 0-.6.2c-.2.2 0 .6 0 .6s2.3 5.2 4.9 7.8c2.4 2.3 5 2.1 5 2.1Z"/></svg></a><?php endif; ?>
                <?php if (get_theme_mod('cg_instagram_url', '')): ?><a class="cg-social-link cg-social-link--instagram" href="<?php echo esc_url(get_theme_mod('cg_instagram_url')); ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><svg class="cg-social-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4.2"/><circle cx="17.4" cy="6.7" r="1"/></svg></a><?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<header class="site-header">
    <div class="container header-inner">
        <div class="brand">
            <?php if (has_custom_logo()): ?>
                <?php the_custom_logo(); ?>
            <?php else: ?>
                <a class="brand__fallback" href="<?php echo esc_url(home_url('/')); ?>">
                    <span class="brand-mark" aria-hidden="true">✿</span>
                    <span>
                        <span class="brand-title"><?php echo esc_html(get_theme_mod('cg_brand_title', 'Цветочный город')); ?></span>
                        <span class="brand-subtitle"><?php echo esc_html(get_theme_mod('cg_brand_subtitle', 'магазин цветов')); ?></span>
                    </span>
                </a>
            <?php endif; ?>
        </div>

        <nav class="main-navigation" id="site-menu" aria-label="Главное меню">
            <?php wp_nav_menu(['theme_location' => 'primary', 'container' => false, 'fallback_cb' => 'cg_fallback_menu']); ?>
        </nav>

        <div class="header-actions">
            <?php if (class_exists('WooCommerce')): ?>
                <button class="icon-button search-toggle" type="button" aria-controls="header-search" aria-expanded="false" aria-label="Открыть поиск">
                    <svg class="cg-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.7"/><path d="m15.8 15.8 4.2 4.2"/></svg>
                </button>
                <a class="icon-button cg-favorites-link" href="<?php echo esc_url(function_exists('cg_favorites_url') ? cg_favorites_url() : home_url('/izbrannoe/')); ?>" aria-label="Избранное">
                    <svg class="cg-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.3 5.5a5 5 0 0 0-7.1 0L12 6.7l-1.2-1.2a5 5 0 0 0-7.1 7.1L12 20.7l8.3-8.1a5 5 0 0 0 0-7.1Z"/></svg>
                    <span class="cg-favorites-count" data-cg-favorites-count hidden>0</span>
                </a>
                <button class="icon-button" type="button" data-cg-mini-cart-open aria-controls="cg-mini-cart" aria-label="Открыть корзину">
                    <svg class="cg-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M5.5 8.5h13l-.7 11h-11.6l-.7-11Z"/><path d="M9 8.5V7a3 3 0 0 1 6 0v1.5"/></svg>
                    <span class="cart-count"><?php echo WC()->cart ? WC()->cart->get_cart_contents_count() : 0; ?></span>
                </button>
                <a class="icon-button" href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" aria-label="Личный кабинет">
                    <svg class="cg-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5.5 20a6.5 6.5 0 0 1 13 0"/></svg>
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