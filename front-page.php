<?php
if (!defined('ABSPATH')) exit;
get_header();
if (have_posts()) { the_post(); }
if (cg_elementor_is_built(get_the_ID())) :
?>
<main id="primary" class="site-main elementor-front-page"><?php the_content(); ?></main>
<?php else: ?>
<main id="primary" class="site-main cg-home">
<section class="cg-slider" aria-label="Главные предложения">
    <button class="cg-slider__arrow cg-slider__arrow--prev" type="button" aria-label="Предыдущий слайд">‹</button>
    <div class="cg-slide cg-slide--one is-active">
        <div class="container cg-slide__inner">
            <div class="cg-slide__copy"><div class="eyebrow">Цветы с доставкой</div><h1><?php echo esc_html(get_theme_mod('cg_hero_title','Дарите эмоции вместе с цветами')); ?></h1><p><?php echo esc_html(get_theme_mod('cg_hero_text','Свежие цветы, стильные букеты и быстрая доставка по Нововоронежу и Воронежской области.')); ?></p><div class="hero-actions"><a class="button" href="<?php echo esc_url(cg_catalog_url()); ?>"><?php echo esc_html(get_theme_mod('cg_hero_button','Смотреть каталог')); ?></a><a class="button button--ghost" href="#popular">Популярные букеты</a></div></div>
            <div class="cg-slide__media"></div>
        </div>
    </div>
    <div class="cg-slide cg-slide--two">
        <div class="container cg-slide__inner">
            <div class="cg-slide__copy"><div class="eyebrow">Авторская флористика</div><h2>Букеты, созданные специально для вас</h2><p>Соберём композицию под повод, настроение и бюджет. Перед доставкой отправим фотографию готового букета.</p><div class="hero-actions"><a class="button" href="<?php echo esc_url(home_url('/product-category/avtorskie/')); ?>">Авторские букеты</a></div></div>
            <div class="cg-slide__media"></div>
        </div>
    </div>
    <div class="cg-slide cg-slide--three">
        <div class="container cg-slide__inner">
            <div class="cg-slide__copy"><div class="eyebrow">Доставка в день заказа</div><h2>Красивый подарок точно ко времени</h2><p>Доставляем по Нововоронежу и Воронежской области. Добавим открытку с вашим текстом бесплатно.</p><div class="hero-actions"><a class="button" href="<?php echo esc_url(cg_catalog_url()); ?>">Выбрать букет</a></div></div>
            <div class="cg-slide__media"></div>
        </div>
    </div>
    <button class="cg-slider__arrow cg-slider__arrow--next" type="button" aria-label="Следующий слайд">›</button>
    <div class="cg-slider__nav" aria-label="Навигация по слайдам"><button class="cg-slider__dot is-active" type="button" aria-label="Слайд 1"></button><button class="cg-slider__dot" type="button" aria-label="Слайд 2"></button><button class="cg-slider__dot" type="button" aria-label="Слайд 3"></button></div>
</section>

<section class="cg-benefits"><div class="container cg-benefits__grid">
    <div class="cg-benefit"><div class="cg-benefit__icon">🌷</div><div><strong>Свежие цветы</strong><span>Ежедневные поставки</span></div></div>
    <div class="cg-benefit"><div class="cg-benefit__icon">🚚</div><div><strong>Быстрая доставка</strong><span>По городу и области</span></div></div>
    <div class="cg-benefit"><div class="cg-benefit__icon">📷</div><div><strong>Фото перед отправкой</strong><span>Покажем готовый букет</span></div></div>
    <div class="cg-benefit"><div class="cg-benefit__icon">💌</div><div><strong>Открытка бесплатно</strong><span>Добавим ваши слова</span></div></div>
</div></section>

<section class="section section--cream"><div class="container"><div class="section-head"><div><div class="eyebrow">Каталог</div><h2 class="section-title">Цветы для любого повода</h2><div class="section-subtitle">Выберите подходящую категорию — товары автоматически подгружаются из WooCommerce.</div></div><a class="text-link" href="<?php echo esc_url(cg_catalog_url()); ?>">Весь каталог →</a></div><div class="cg-category-grid"><?php $cats=[['Свадебные','svadebnye'],['Сладкие','sladkie'],['Авторские','avtorskie'],['Lux','lux'],['До 2000','do-2000'],['До 5000','do-5000'],['До 10000','do-10000'],['Все букеты','']]; foreach($cats as $c): ?><a class="cg-category" href="<?php echo esc_url($c[1]?home_url('/product-category/'.$c[1].'/'):cg_catalog_url()); ?>"><div class="cg-category__text"><h3><?php echo esc_html($c[0]); ?></h3><span>Смотреть товары →</span></div></a><?php endforeach; ?></div></div></section>

<section class="section" id="popular"><div class="container"><div class="section-head"><div><div class="eyebrow">Выбор покупателей</div><h2 class="section-title">Популярные букеты</h2><div class="section-subtitle">Хиты продаж и свежие композиции.</div></div><a class="text-link" href="<?php echo esc_url(cg_catalog_url()); ?>">Смотреть все →</a></div><?php if(shortcode_exists('products')) echo do_shortcode('[products limit="8" columns="4" orderby="popularity"]'); else echo '<p>Установите и активируйте WooCommerce.</p>'; ?></div></section>

<section class="section section--soft"><div class="container"><div class="cg-promo"><div class="cg-promo__copy"><div class="eyebrow">Особенное предложение</div><h2>Букет недели со скидкой 15%</h2><p>Каждую неделю мы выбираем одну особенную композицию и предлагаем её по приятной цене. Количество ограничено.</p><a class="button" href="<?php echo esc_url(cg_catalog_url()); ?>">Смотреть предложение</a></div><div class="cg-promo__art" aria-hidden="true"></div></div></div></section>

<section class="section"><div class="container"><div class="cg-about"><div class="cg-about__image" aria-hidden="true"></div><div class="cg-about__copy"><div class="eyebrow">О нас</div><h2>Мы создаём букеты, которые говорят за вас</h2><p>Каждый букет — это забота, вдохновение и внимание к деталям. Мы бережно собираем композиции и доставляем их по Нововоронежу и Воронежской области.</p><a class="button" href="<?php echo esc_url(home_url('/about/')); ?>">Подробнее о нас</a></div></div></div></section>

<section class="section"><div class="container"><div class="newsletter"><div><h2>Скидка 10% на первый заказ</h2><p>Оставьте e-mail и получите промокод. Форму можно подключить к Contact Form 7 или Fluent Forms.</p></div><form action="#" method="post"><input type="email" placeholder="Ваш e-mail" aria-label="Ваш e-mail"><button class="button" type="submit">Получить промокод</button></form></div></div></section>
</main>
<?php endif; get_footer(); ?>