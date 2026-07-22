<?php
if (!defined('ABSPATH')) {
    exit;
}

get_header();

if (have_posts()) {
    the_post();
}

if (cg_elementor_is_built(get_the_ID())) {
    ?>
    <main id="primary" class="site-main elementor-front-page">
        <?php the_content(); ?>
    </main>
    <?php
    get_footer();
    return;
}

$slides = function_exists('cg_get_home_slides') ? cg_get_home_slides() : [];
?>
<main id="primary" class="site-main cg-home">
    <?php if (!empty($slides)) : ?>
        <section class="cg-slider" aria-label="Главные предложения">
            <?php if (count($slides) > 1) : ?>
                <button class="cg-slider__arrow cg-slider__arrow--prev" type="button" aria-label="Предыдущий слайд">‹</button>
            <?php endif; ?>

            <?php foreach ($slides as $index => $slide) :
                $media_style = !empty($slide['image'])
                    ? 'background-image:url(' . esc_url($slide['image']) . ');'
                    : '';
                $heading_tag = $index === 0 ? 'h1' : 'h2';
                ?>
                <div class="cg-slide cg-slide--<?php echo esc_attr($slide['number']); ?><?php echo $index === 0 ? ' is-active' : ''; ?>">
                    <div class="container cg-slide__inner">
                        <div class="cg-slide__copy">
                            <?php if (!empty($slide['eyebrow'])) : ?>
                                <div class="eyebrow"><?php echo esc_html($slide['eyebrow']); ?></div>
                            <?php endif; ?>

                            <<?php echo esc_attr($heading_tag); ?>><?php echo esc_html($slide['title']); ?></<?php echo esc_attr($heading_tag); ?>>

                            <?php if (!empty($slide['text'])) : ?>
                                <p><?php echo esc_html($slide['text']); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($slide['button']) && !empty($slide['url'])) : ?>
                                <div class="hero-actions">
                                    <a class="button" href="<?php echo esc_url($slide['url']); ?>"><?php echo esc_html($slide['button']); ?></a>
                                    <?php if ($index === 0) : ?>
                                        <a class="button button--ghost" href="#popular">Популярные букеты</a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="cg-slide__media"<?php echo $media_style ? ' style="' . esc_attr($media_style) . '"' : ''; ?>></div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (count($slides) > 1) : ?>
                <button class="cg-slider__arrow cg-slider__arrow--next" type="button" aria-label="Следующий слайд">›</button>
                <div class="cg-slider__nav" aria-label="Навигация по слайдам">
                    <?php foreach ($slides as $index => $slide) : ?>
                        <button class="cg-slider__dot<?php echo $index === 0 ? ' is-active' : ''; ?>" type="button" aria-label="Слайд <?php echo esc_attr($index + 1); ?>"></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if (get_theme_mod('cg_benefits_enabled', true)) :
        $benefit_defaults = [
            1 => ['🌷', 'Свежие цветы', 'Ежедневные поставки'],
            2 => ['🚚', 'Быстрая доставка', 'По городу и области'],
            3 => ['📷', 'Фото перед отправкой', 'Покажем готовый букет'],
            4 => ['💌', 'Открытка бесплатно', 'Добавим ваши слова'],
        ];
        ?>
        <section class="cg-benefits">
            <div class="container cg-benefits__grid">
                <?php foreach ($benefit_defaults as $number => $defaults) : ?>
                    <div class="cg-benefit">
                        <div class="cg-benefit__icon"><?php echo esc_html(get_theme_mod("cg_benefit_{$number}_icon", $defaults[0])); ?></div>
                        <div>
                            <strong><?php echo esc_html(get_theme_mod("cg_benefit_{$number}_title", $defaults[1])); ?></strong>
                            <span><?php echo esc_html(get_theme_mod("cg_benefit_{$number}_text", $defaults[2])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (get_theme_mod('cg_categories_enabled', true)) :
        $categories = function_exists('cg_get_home_categories') ? cg_get_home_categories() : [];
        ?>
        <section class="section section--cream">
            <div class="container">
                <div class="section-head">
                    <div>
                        <div class="eyebrow"><?php echo esc_html(get_theme_mod('cg_categories_eyebrow', 'Каталог')); ?></div>
                        <h2 class="section-title"><?php echo esc_html(get_theme_mod('cg_categories_title', 'Цветы для любого повода')); ?></h2>
                        <div class="section-subtitle"><?php echo esc_html(get_theme_mod('cg_categories_text', 'Выберите подходящую категорию — товары автоматически подгружаются из WooCommerce.')); ?></div>
                    </div>
                    <a class="text-link" href="<?php echo esc_url(cg_catalog_url()); ?>">Весь каталог →</a>
                </div>

                <div class="cg-category-grid">
                    <?php foreach ($categories as $category) :
                        $category_url = !empty($category['slug'])
                            ? home_url('/product-category/' . $category['slug'] . '/')
                            : cg_catalog_url();
                        ?>
                        <a class="cg-category" href="<?php echo esc_url($category_url); ?>">
                            <div class="cg-category__text">
                                <h3><?php echo esc_html($category['name']); ?></h3>
                                <span>Смотреть товары →</span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if (get_theme_mod('cg_products_enabled', true)) :
        $count = max(1, min(24, absint(get_theme_mod('cg_products_count', 8))));
        $orderby = get_theme_mod('cg_products_orderby', 'popularity');
        if (!in_array($orderby, ['popularity', 'date', 'rating', 'rand'], true)) {
            $orderby = 'popularity';
        }
        ?>
        <section class="section" id="popular">
            <div class="container">
                <div class="section-head">
                    <div>
                        <div class="eyebrow"><?php echo esc_html(get_theme_mod('cg_products_eyebrow', 'Выбор покупателей')); ?></div>
                        <h2 class="section-title"><?php echo esc_html(get_theme_mod('cg_products_title', 'Популярные букеты')); ?></h2>
                        <div class="section-subtitle"><?php echo esc_html(get_theme_mod('cg_products_text', 'Хиты продаж и свежие композиции.')); ?></div>
                    </div>
                    <a class="text-link" href="<?php echo esc_url(cg_catalog_url()); ?>">Смотреть все →</a>
                </div>

                <?php
                if (shortcode_exists('products')) {
                    echo do_shortcode('[products limit="' . $count . '" columns="4" orderby="' . esc_attr($orderby) . '"]');
                } else {
                    echo '<p>Установите и активируйте WooCommerce.</p>';
                }
                ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (get_theme_mod('cg_promo_enabled', true)) :
        $promo_image = get_theme_mod('cg_promo_image', '');
        $promo_style = $promo_image ? 'background-image:url(' . esc_url($promo_image) . ');' : '';
        ?>
        <section class="section section--soft">
            <div class="container">
                <div class="cg-promo">
                    <div class="cg-promo__copy">
                        <div class="eyebrow"><?php echo esc_html(get_theme_mod('cg_promo_eyebrow', 'Особенное предложение')); ?></div>
                        <h2><?php echo esc_html(get_theme_mod('cg_promo_title', 'Букет недели со скидкой 15%')); ?></h2>
                        <p><?php echo esc_html(get_theme_mod('cg_promo_text', 'Каждую неделю мы выбираем одну особенную композицию и предлагаем её по приятной цене. Количество ограничено.')); ?></p>
                        <a class="button" href="<?php echo esc_url(get_theme_mod('cg_promo_url', '') ?: cg_catalog_url()); ?>"><?php echo esc_html(get_theme_mod('cg_promo_button', 'Смотреть предложение')); ?></a>
                    </div>
                    <div class="cg-promo__art"<?php echo $promo_style ? ' style="' . esc_attr($promo_style) . '"' : ''; ?> aria-hidden="true"></div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if (get_theme_mod('cg_about_enabled', true)) :
        $about_image = get_theme_mod('cg_about_image', '');
        $about_style = $about_image ? 'background-image:url(' . esc_url($about_image) . ');' : '';
        ?>
        <section class="section">
            <div class="container">
                <div class="cg-about">
                    <div class="cg-about__image"<?php echo $about_style ? ' style="' . esc_attr($about_style) . '"' : ''; ?> aria-hidden="true"></div>
                    <div class="cg-about__copy">
                        <div class="eyebrow"><?php echo esc_html(get_theme_mod('cg_about_eyebrow', 'О нас')); ?></div>
                        <h2><?php echo esc_html(get_theme_mod('cg_about_title', 'Мы создаём букеты, которые говорят за вас')); ?></h2>
                        <p><?php echo esc_html(get_theme_mod('cg_about_text', 'Каждый букет — это забота, вдохновение и внимание к деталям. Мы бережно собираем композиции и доставляем их по Нововоронежу и Воронежской области.')); ?></p>
                        <a class="button" href="<?php echo esc_url(get_theme_mod('cg_about_url', home_url('/about/'))); ?>"><?php echo esc_html(get_theme_mod('cg_about_button', 'Подробнее о нас')); ?></a>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if (get_theme_mod('cg_newsletter_enabled', true)) :
        $newsletter_shortcode = trim((string) get_theme_mod('cg_newsletter_shortcode', ''));
        ?>
        <section class="section">
            <div class="container">
                <div class="newsletter">
                    <div>
                        <h2><?php echo esc_html(get_theme_mod('cg_newsletter_title', 'Скидка 10% на первый заказ')); ?></h2>
                        <p><?php echo esc_html(get_theme_mod('cg_newsletter_text', 'Оставьте e-mail и получите промокод.')); ?></p>
                    </div>

                    <?php if ($newsletter_shortcode !== '') : ?>
                        <?php echo do_shortcode($newsletter_shortcode); ?>
                    <?php else : ?>
                        <form action="#" method="post">
                            <input type="email" placeholder="Ваш e-mail" aria-label="Ваш e-mail">
                            <button class="button" type="submit">Получить промокод</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>
</main>
<?php get_footer(); ?>
