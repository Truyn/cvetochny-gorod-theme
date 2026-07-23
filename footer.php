</main>
<footer class="site-footer">
    <div class="container">
        <div class="footer-newsline">
            <div>
                <span class="eyebrow">Цветочный город</span>
                <strong>Букеты, которые помогают говорить о важном</strong>
            </div>
            <a class="button" href="<?php echo esc_url(cg_catalog_url()); ?>">Выбрать букет</a>
        </div>

        <div class="footer-grid">
            <div class="footer-brand">
                <a class="brand" href="<?php echo esc_url(home_url('/')); ?>">
                    <?php if (has_custom_logo()): ?>
                        <?php the_custom_logo(); ?>
                    <?php else: ?>
                        <span class="brand-mark" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M12 21c-1.5-3.7-1-6.7 1.6-9.1 2.2-2 4.7-2.6 7.4-1.8-1.2 4.8-4.2 8.2-9 10.9ZM11 21C6 19.4 3 16.1 2 11.2c2.9-.9 5.5-.2 7.7 2 2.3 2.3 2.7 4.9 1.3 7.8ZM12 12C8.7 9.8 7.3 7 8 3.4c3.2.7 5.4 2.5 6.5 5.4-1 1-1.8 2-2.5 3.2Z"/></svg>
                        </span>
                        <span>
                            <span class="brand-title"><?php echo esc_html(get_theme_mod('cg_brand_title', 'Цветочный город')); ?></span>
                            <span class="brand-subtitle"><?php echo esc_html(get_theme_mod('cg_brand_subtitle', 'магазин цветов')); ?></span>
                        </span>
                    <?php endif; ?>
                </a>
                <p><?php echo esc_html(get_theme_mod('cg_footer_text', 'Букеты и подарки с доставкой по Нововоронежу и Воронежской области.')); ?></p>
                <div class="footer-socials" aria-label="Социальные сети">
                    <?php if (get_theme_mod('cg_whatsapp_url', '')): ?><a href="<?php echo esc_url(get_theme_mod('cg_whatsapp_url')); ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp"><svg class="cg-social-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a9.5 9.5 0 0 0-8.2 14.3L2.5 21.5l5.3-1.4A9.5 9.5 0 1 0 12 2Zm5.4 13.6c-.2.6-1.3 1.2-1.8 1.3-.5.1-1.2.2-1.9 0-.4-.1-1-.3-1.7-.6-3-1.3-4.9-4.4-5.1-4.6-.1-.2-1.2-1.6-1.2-3.1s.8-2.2 1-2.5c.3-.3.6-.4.8-.4h.6c.2 0 .5-.1.7.5.3.6.9 2.2 1 2.4.1.2.1.4 0 .6-.1.2-.2.4-.4.6l-.6.6c-.2.2-.4.4-.2.7.2.3.8 1.3 1.8 2.1 1.2 1.1 2.3 1.5 2.6 1.7.3.1.5.1.7-.1l.9-1.1c.2-.3.4-.3.7-.2l2.2 1c.3.1.5.2.6.4.1.1.1.6-.1 1.2Z"/></svg></a><?php endif; ?>
                    <?php if (get_theme_mod('cg_telegram_url', '')): ?><a href="<?php echo esc_url(get_theme_mod('cg_telegram_url')); ?>" target="_blank" rel="noopener noreferrer" aria-label="Telegram"><svg class="cg-social-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21.4 4.6 18.5 19c-.2 1-.8 1.2-1.6.8l-4.4-3.2-2.1 2.1c-.2.2-.4.4-.9.4l.3-4.5 8.2-7.4c.4-.3-.1-.5-.5-.2L7.3 13.4 3 12c-.9-.3-.9-.9.2-1.3l16.7-6.4c.8-.3 1.5.2 1.5.3Z"/></svg></a><?php endif; ?>
                    <?php if (get_theme_mod('cg_vk_url', '')): ?><a href="<?php echo esc_url(get_theme_mod('cg_vk_url')); ?>" target="_blank" rel="noopener noreferrer" aria-label="ВКонтакте"><svg class="cg-social-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12.8 17.8C6.7 17.8 3.2 13.6 3 6.5h3.1c.1 5.2 2.4 7.4 4.2 7.8V6.5h2.9V11c1.8-.2 3.8-2.2 4.5-4.5h2.9c-.5 2.9-2.5 4.9-3.9 5.7 1.4.7 3.7 2.4 4.6 5.6h-3.2c-.8-2.2-2.6-3.9-4.9-4.2v4.2h-.4Z"/></svg></a><?php endif; ?>
                    <?php if (get_theme_mod('cg_instagram_url', '')): ?><a href="<?php echo esc_url(get_theme_mod('cg_instagram_url')); ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><svg class="cg-social-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M7.5 2h9A5.5 5.5 0 0 1 22 7.5v9a5.5 5.5 0 0 1-5.5 5.5h-9A5.5 5.5 0 0 1 2 16.5v-9A5.5 5.5 0 0 1 7.5 2Zm0 2A3.5 3.5 0 0 0 4 7.5v9A3.5 3.5 0 0 0 7.5 20h9a3.5 3.5 0 0 0 3.5-3.5v-9A3.5 3.5 0 0 0 16.5 4h-9ZM17 5.5A1.5 1.5 0 1 1 17 8a1.5 1.5 0 0 1 0-2.5ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z"/></svg></a><?php endif; ?>
                </div>
            </div>

            <div>
                <div class="footer-title"><?php echo esc_html(get_theme_mod('cg_footer_catalog_title', 'Каталог')); ?></div>
                <div class="footer-links">
                    <a href="<?php echo esc_url(cg_catalog_url()); ?>">Все букеты</a>
                    <a href="<?php echo esc_url(home_url('/product-category/avtorskie/')); ?>">Авторские</a>
                    <a href="<?php echo esc_url(home_url('/product-category/lux/')); ?>">Lux</a>
                    <a href="<?php echo esc_url(home_url('/product-category/sladkie/')); ?>">Сладкие</a>
                </div>
            </div>

            <div>
                <div class="footer-title"><?php echo esc_html(get_theme_mod('cg_footer_buyers_title', 'Покупателям')); ?></div>
                <div class="footer-links">
                    <?php if (class_exists('WooCommerce')): ?>
                        <a href="<?php echo esc_url(wc_get_cart_url()); ?>">Корзина</a>
                        <a href="<?php echo esc_url(wc_get_checkout_url()); ?>">Оформление заказа</a>
                        <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>">Личный кабинет</a>
                    <?php endif; ?>
                    <a href="<?php echo esc_url(home_url('/delivery/')); ?>">Доставка и оплата</a>
                    <a href="<?php echo esc_url(home_url('/contacts/')); ?>">Контакты</a>
                </div>
            </div>

            <div>
                <div class="footer-title"><?php echo esc_html(get_theme_mod('cg_footer_contacts_title', 'Контакты')); ?></div>
                <div class="footer-links footer-contacts">
                    <span><?php echo esc_html(get_theme_mod('cg_address', 'Нововоронеж, Воронежская область')); ?></span>
                    <a class="footer-phone" href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', get_theme_mod('cg_phone', '+7 (900) 000-00-00'))); ?>"><?php echo esc_html(get_theme_mod('cg_phone', '+7 (900) 000-00-00')); ?></a>
                    <span><?php echo esc_html(get_theme_mod('cg_worktime', 'Ежедневно с 09:00 до 21:00')); ?></span>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <span>© <?php echo esc_html(wp_date('Y')); ?> <?php echo esc_html(get_theme_mod('cg_footer_copyright', 'Цветочный город')); ?></span>
            <span><?php echo esc_html(get_theme_mod('cg_footer_legal', 'Политика конфиденциальности · Публичная оферта')); ?></span>
        </div>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>