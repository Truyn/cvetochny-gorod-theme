(function ($) {
    'use strict';

    function gallerySlides(gallery) {
        return Array.prototype.slice.call(
            gallery.querySelectorAll('.woocommerce-product-gallery__wrapper > .woocommerce-product-gallery__image')
        );
    }

    function galleryThumbs(gallery) {
        return Array.prototype.slice.call(gallery.querySelectorAll('.flex-control-thumbs img'));
    }

    function markActiveThumbnail(gallery, index) {
        galleryThumbs(gallery).forEach(function (thumb, thumbIndex) {
            var active = thumbIndex === index;
            thumb.classList.toggle('flex-active', active);
            thumb.setAttribute('aria-current', active ? 'true' : 'false');
        });
    }

    function getImageData(slide) {
        var image = slide ? slide.querySelector('img') : null;
        var link = slide ? slide.querySelector('a') : null;
        if (!image) return null;

        return {
            src: image.currentSrc || image.getAttribute('src') || '',
            srcset: image.getAttribute('srcset') || '',
            sizes: image.getAttribute('sizes') || '',
            alt: image.getAttribute('alt') || '',
            title: image.getAttribute('title') || '',
            href: link ? (link.getAttribute('href') || '') : '',
            dataLargeImage: image.getAttribute('data-large_image') || '',
            dataLargeImageWidth: image.getAttribute('data-large_image_width') || '',
            dataLargeImageHeight: image.getAttribute('data-large_image_height') || ''
        };
    }

    function preloadFrame(frame) {
        if (!frame || !frame.src) return;
        var image = new Image();
        if (frame.srcset) image.srcset = frame.srcset;
        if (frame.sizes) image.sizes = frame.sizes;
        image.src = frame.src;
    }

    /**
     * Keep one stable DOM image as the visible stage.
     *
     * The previous implementation replaced stage.innerHTML on every thumbnail
     * click. That made the browser recalculate the image height while the new
     * image was loading; FlexSlider could then briefly collapse the viewport and
     * restore it again. We now update attributes on the existing image instead.
     * The CSS also gives the viewport a fixed 3:4 stage, matching the product
     * presentation used by the store, so changing source dimensions cannot move
     * the surrounding layout.
     */
    function initializeManualGallery(gallery) {
        if (!gallery) return null;
        if (gallery._cgManualGallery) return gallery._cgManualGallery;

        var slides = gallerySlides(gallery);
        var thumbs = galleryThumbs(gallery);
        if (slides.length < 2 || thumbs.length < 2) return null;

        var frames = slides.map(getImageData).filter(Boolean);
        var stage = slides[0];
        var stageLink = stage.querySelector('a');
        var stageImage = stage.querySelector('img');
        var wrapper = gallery.querySelector('.woocommerce-product-gallery__wrapper');
        var viewport = gallery.querySelector('.flex-viewport');

        if (!stageImage) return null;

        gallery.classList.add('cg-gallery-manual');

        if (wrapper) {
            wrapper.style.setProperty('width', '100%', 'important');
            wrapper.style.setProperty('height', '100%', 'important');
            wrapper.style.setProperty('transform', 'none', 'important');
            wrapper.style.setProperty('margin-left', '0', 'important');
        }

        if (viewport) {
            viewport.style.setProperty('height', 'auto', 'important');
            viewport.style.setProperty('min-height', '0', 'important');
            viewport.style.setProperty('overflow', 'hidden', 'important');
        }

        slides.forEach(function (slide, slideIndex) {
            slide.style.setProperty('display', slideIndex === 0 ? 'block' : 'none', 'important');
            slide.style.setProperty('width', '100%', 'important');
            slide.style.setProperty('min-width', '100%', 'important');
            slide.style.setProperty('height', '100%', 'important');
            slide.style.setProperty('float', 'none', 'important');
            slide.style.setProperty('margin', '0', 'important');
        });

        frames.forEach(preloadFrame);

        function show(index) {
            var frame = frames[index];
            if (!frame) return;

            /* Update the existing nodes instead of replacing innerHTML. */
            if (stageLink && frame.href) {
                stageLink.setAttribute('href', frame.href);
            }
            stageImage.setAttribute('src', frame.src);
            if (frame.srcset) stageImage.setAttribute('srcset', frame.srcset);
            else stageImage.removeAttribute('srcset');
            if (frame.sizes) stageImage.setAttribute('sizes', frame.sizes);
            else stageImage.removeAttribute('sizes');
            stageImage.setAttribute('alt', frame.alt);
            if (frame.title) stageImage.setAttribute('title', frame.title);
            else stageImage.removeAttribute('title');
            if (frame.dataLargeImage) stageImage.setAttribute('data-large_image', frame.dataLargeImage);
            if (frame.dataLargeImageWidth) stageImage.setAttribute('data-large_image_width', frame.dataLargeImageWidth);
            if (frame.dataLargeImageHeight) stageImage.setAttribute('data-large_image_height', frame.dataLargeImageHeight);

            stage.setAttribute('data-cg-gallery-index', String(index));
            stage.setAttribute('aria-label', frame.alt || ('Изображение товара ' + (index + 1)));
            markActiveThumbnail(gallery, index);

            $(gallery).trigger('woocommerce_gallery_image_changed', [index]);
        }

        gallery._cgManualGallery = { show: show, frames: frames };

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

    /* Capture thumbnail clicks before FlexSlider. */
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

            if (!pending || attempts >= 20) window.clearInterval(timer);
        }, 150);
    });

    $(window).on('load', initializeAllGalleries);
    $(document.body).on('wc-product-gallery-after-init', initializeAllGalleries);
})(jQuery);
