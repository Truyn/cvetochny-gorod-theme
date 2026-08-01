(function ($) {
    'use strict';

    function gallerySlides(gallery) {
        return Array.prototype.slice.call(
            gallery.querySelectorAll('.woocommerce-product-gallery__wrapper > .woocommerce-product-gallery__image')
        );
    }

    function galleryThumbs(gallery) {
        return Array.prototype.slice.call(
            gallery.querySelectorAll('.flex-control-thumbs img')
        );
    }

    function markActiveThumbnail(gallery, index) {
        galleryThumbs(gallery).forEach(function (thumb, thumbIndex) {
            var active = thumbIndex === index;
            thumb.classList.toggle('flex-active', active);
            thumb.setAttribute('aria-current', active ? 'true' : 'false');
        });
    }

    /**
     * Use the first WooCommerce slide as a stable visible stage.
     *
     * Several theme and FlexSlider width rules can disagree with one another.
     * Copying the selected slide markup into one fixed stage avoids transforms
     * completely while preserving the correct large-image link and srcset.
     */
    function initializeManualGallery(gallery) {
        if (!gallery) return null;
        if (gallery._cgManualGallery) return gallery._cgManualGallery;

        var slides = gallerySlides(gallery);
        var thumbs = galleryThumbs(gallery);

        if (slides.length < 2 || thumbs.length < 2) return null;

        var frames = slides.map(function (slide) {
            return {
                html: slide.innerHTML,
                label: (slide.querySelector('img') || {}).alt || ''
            };
        });
        var stage = slides[0];
        var wrapper = gallery.querySelector('.woocommerce-product-gallery__wrapper');

        gallery.classList.add('cg-gallery-manual');

        if (wrapper) {
            wrapper.style.setProperty('width', '100%', 'important');
            wrapper.style.setProperty('transform', 'none', 'important');
            wrapper.style.setProperty('margin-left', '0', 'important');
        }

        slides.forEach(function (slide, slideIndex) {
            slide.style.setProperty('display', slideIndex === 0 ? 'block' : 'none', 'important');
            slide.style.setProperty('width', '100%', 'important');
            slide.style.setProperty('min-width', '100%', 'important');
            slide.style.setProperty('float', 'none', 'important');
        });

        function show(index) {
            if (!frames[index]) return;

            stage.innerHTML = frames[index].html;
            stage.setAttribute('data-cg-gallery-index', String(index));
            stage.setAttribute('aria-label', frames[index].label || ('Изображение товара ' + (index + 1)));
            markActiveThumbnail(gallery, index);

            /* Notify WooCommerce extensions that the visible product image changed. */
            $(gallery).trigger('woocommerce_gallery_image_changed', [index]);
        }

        gallery._cgManualGallery = {
            show: show,
            frames: frames
        };

        var initiallyActive = gallery.querySelector('.flex-control-thumbs img.flex-active');
        var initialIndex = initiallyActive ? thumbs.indexOf(initiallyActive) : 0;
        show(Math.max(0, initialIndex));

        return gallery._cgManualGallery;
    }

    function initializeAllGalleries() {
        document.querySelectorAll('.single-product .woocommerce-product-gallery').forEach(function (gallery) {
            initializeManualGallery(gallery);
        });
    }

    /* Capture the click before FlexSlider receives it. This prevents the slider
     * from restoring the old transform after the selected image is rendered. */
    document.addEventListener('click', function (event) {
        var thumbnail = event.target.closest('.single-product .flex-control-thumbs img');
        if (!thumbnail) return;

        var gallery = thumbnail.closest('.woocommerce-product-gallery');
        if (!gallery) return;

        var thumbs = galleryThumbs(gallery);
        var index = thumbs.indexOf(thumbnail);
        if (index < 0) return;

        var manualGallery = initializeManualGallery(gallery);
        if (!manualGallery) return;

        event.preventDefault();
        event.stopPropagation();
        if (typeof event.stopImmediatePropagation === 'function') {
            event.stopImmediatePropagation();
        }

        manualGallery.show(index);
    }, true);

    $(function () {
        var attempts = 0;
        var timer = window.setInterval(function () {
            attempts += 1;
            initializeAllGalleries();

            var pending = document.querySelector(
                '.single-product .woocommerce-product-gallery:not(.cg-gallery-manual) .flex-control-thumbs img'
            );

            if (!pending || attempts >= 20) {
                window.clearInterval(timer);
            }
        }, 150);
    });

    $(window).on('load', initializeAllGalleries);
    $(document.body).on('wc-product-gallery-after-init', initializeAllGalleries);
})(jQuery);
