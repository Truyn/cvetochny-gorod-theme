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
            thumb.classList.toggle('flex-active', thumbIndex === index);
            thumb.setAttribute('aria-current', thumbIndex === index ? 'true' : 'false');
        });
    }

    function fallbackToStaticSlide(gallery, index) {
        var slides = gallerySlides(gallery);
        if (!slides[index]) return;

        gallery.classList.add('cg-gallery-static-fallback');

        var wrapper = gallery.querySelector('.woocommerce-product-gallery__wrapper');
        if (wrapper) {
            wrapper.style.setProperty('width', '100%', 'important');
            wrapper.style.setProperty('transform', 'none', 'important');
        }

        slides.forEach(function (slide, slideIndex) {
            slide.style.setProperty('display', slideIndex === index ? 'block' : 'none', 'important');
            slide.style.setProperty('width', '100%', 'important');
            slide.style.setProperty('float', 'none', 'important');
        });

        markActiveThumbnail(gallery, index);
    }

    function openGallerySlide(gallery, index) {
        if (!gallery || index < 0) return;

        var instance = $(gallery).data('flexslider');
        if (instance && typeof instance.flexAnimate === 'function') {
            gallery.classList.remove('cg-gallery-static-fallback');
            instance.flexAnimate(index, true);
            markActiveThumbnail(gallery, index);
            return;
        }

        fallbackToStaticSlide(gallery, index);
    }

    document.addEventListener('click', function (event) {
        var thumbnail = event.target.closest('.single-product .flex-control-thumbs img');
        if (!thumbnail) return;

        var gallery = thumbnail.closest('.woocommerce-product-gallery');
        if (!gallery) return;

        var thumbnails = galleryThumbs(gallery);
        var index = thumbnails.indexOf(thumbnail);
        if (index < 0) return;

        /* Run after WooCommerce's own thumbnail handler. This repairs galleries
         * where visual CSS previously prevented FlexSlider from moving. */
        window.setTimeout(function () {
            openGallerySlide(gallery, index);
        }, 0);
    });

    $(window).on('load', function () {
        document.querySelectorAll('.single-product .woocommerce-product-gallery').forEach(function (gallery) {
            var active = gallery.querySelector('.flex-control-thumbs img.flex-active');
            var index = active ? galleryThumbs(gallery).indexOf(active) : 0;
            markActiveThumbnail(gallery, Math.max(0, index));
        });
    });
})(jQuery);
