</main>
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="brand">
                    <span class="brand-mark">✿</span>
                    <span>
                        <span class="brand-title"><?php echo esc_html(get_theme_mod('cg_brand_title', 'Цветочный город')); ?></span>
                        <span class="brand-subtitle"><?php echo esc_html(get_theme_mod('cg_brand_subtitle', 'магазин цветов')); ?></span>
                    </span>
                </div>
                <p style="color:var(--cg-muted);max-width:320px"><?php echo esc_html(get_theme_mod('cg_footer_text', 'Букеты и подарки с доставкой по Нововоронежу и Воронежской области.')); ?></p>
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
                    <?php endif; ?>
                    <a href="<?php echo esc_url(home_url('/delivery/')); ?>">Доставка и оплата</a>
                    <a href="<?php echo esc_url(home_url('/contacts/')); ?>">Контакты</a>
                </div>
            </div>

            <div>
                <div class="footer-title"><?php echo esc_html(get_theme_mod('cg_footer_contacts_title', 'Контакты')); ?></div>
                <div class="footer-links">
                    <span><?php echo esc_html(get_theme_mod('cg_address', 'Нововоронеж, Воронежская область')); ?></span>
                    <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', get_theme_mod('cg_phone', '+7 (900) 000-00-00'))); ?>"><?php echo esc_html(get_theme_mod('cg_phone', '+7 (900) 000-00-00')); ?></a>
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

        <div class="footer-bottom">
            <span>© <?php echo esc_html(wp_date('Y')); ?> <?php echo esc_html(get_theme_mod('cg_footer_copyright', 'Цветочный город')); ?></span>
            <span><?php echo esc_html(get_theme_mod('cg_footer_legal', 'Политика конфиденциальности · Публичная оферта')); ?></span>
        </div>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
