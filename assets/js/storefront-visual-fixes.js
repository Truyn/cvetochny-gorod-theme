(function ($) {
    'use strict';

    function getThumbItems(gallery) {
        return Array.prototype.slice.call(
            gallery.querySelectorAll('.flex-control-thumbs li')
        );
    }

    function getSlides(gallery) {
        return Array.prototype.slice.call(
            gallery.querySelectorAll('.woocommerce-product-gallery__wrapper > .woocommerce-product-gallery__image')
        ).filter(function (slide) {
            return !slide.classList.contains('clone');
        });
    }

    function firstNonEmpty() {
        for (var i = 0; i < arguments.length; i++) {
            if (arguments[i]) return arguments[i];
        }
        return '';
    }

    function readImage(slide) {
        var image = slide && slide.querySelector('img');
        var anchor = slide && slide.querySelector('a');
        if (!image) return null;

        return {
            src: firstNonEmpty(
                image.getAttribute('data-large_image'),
                image.getAttribute('data-large-image'),
                image.getAttribute('data-src'),
                image.getAttribute('data-lazy-src'),
                anchor && anchor.getAttribute('href'),
                image.getAttribute('src')
            ),
            srcset: firstNonEmpty(
                image.getAttribute('data-srcset'),
                image.getAttribute('data-lazy-srcset'),
                image.getAttribute('srcset')
            ),
            sizes: firstNonEmpty(image.getAttribute('data-sizes'), image.getAttribute('sizes')),
            alt: image.getAttribute('alt') || '',
            title: image.getAttribute('title') || '',
            width: firstNonEmpty(
                image.getAttribute('data-large_image_width'),
                image.getAttribute('data-large-image-width'),
                image.naturalWidth ? String(image.naturalWidth) : ''
            ),
            height: firstNonEmpty(
                image.getAttribute('data-large_image_height'),
                image.getAttribute('data-large-image-height'),
                image.naturalHeight ? String(image.naturalHeight) : ''
            )
        };
    }

    function readThumb(thumbItem) {
        var image = thumbItem && thumbItem.querySelector('img');
        if (!image) return null;
        return {
            src: firstNonEmpty(
                image.getAttribute('data-large_image'),
                image.getAttribute('data-large-image'),
                image.getAttribute('data-src'),
                image.getAttribute('data-lazy-src'),
                image.getAttribute('src')
            ),
            srcset: firstNonEmpty(
                image.getAttribute('data-srcset'),
                image.getAttribute('data-lazy-srcset'),
                image.getAttribute('srcset')
            )
        };
    }

    function markActive(gallery, index) {
        getThumbItems(gallery).forEach(function (item, i) {
            var image = item.querySelector('img');
            var active = i === index;
            item.classList.toggle('flex-active-slide', active);
            if (image) {
                image.classList.toggle('flex-active', active);
                image.setAttribute('aria-current', active ? 'true' : 'false');
            }
        });
    }

    function preload(frame) {
        if (!frame || !frame.src) return;
        var image = new Image();
        if (frame.srcset) image.srcset = frame.srcset;
        if (frame.sizes) image.sizes = frame.sizes;
        image.src = frame.src;
    }

    function init(gallery) {
        if (!gallery || gallery._cgSharpGallery) return gallery && gallery._cgSharpGallery;

        var thumbItems = getThumbItems(gallery);
        var slides = getSlides(gallery);
        var stage = slides[0];
        var stageImage = stage && stage.querySelector('img');
        var stageLink = stage && stage.querySelector('a');

        if (!stage || !stageImage || thumbItems.length < 2 || slides.length < 2) return null;

        var frames = slides.map(readImage).filter(Boolean);
        if (frames.length < 2) return null;

        /* Index is the stable contract between WooCommerce's thumbnails and slides. */
        var galleryState = { frames: frames, index: 0 };
        gallery.classList.add('cg-gallery-manual');

        function show(index) {
            index = Math.max(0, Math.min(index, frames.length - 1));
            var frame = frames[index];
            if (!frame || !frame.src) {
                var thumbFrame = readThumb(thumbItems[index]);
                if (thumbFrame && thumbFrame.src) frame = thumbFrame;
            }
            if (!frame || !frame.src) return false;

            /* Keep the same DOM node, but restore the selected image's own
             * responsive candidates. Removing srcset was the cause of the
             * desktop low-resolution image in the previous fix. */
            stageImage.removeAttribute('srcset');
            stageImage.removeAttribute('sizes');
            if (frame.srcset) stageImage.setAttribute('srcset', frame.srcset);
            if (frame.sizes) stageImage.setAttribute('sizes', frame.sizes);
            stageImage.setAttribute('src', frame.src);
            stageImage.setAttribute('data-large_image', frame.src);
            if (frame.width) stageImage.setAttribute('data-large_image_width', frame.width);
            if (frame.height) stageImage.setAttribute('data-large_image_height', frame.height);
            stageImage.setAttribute('loading', 'eager');
            stageImage.setAttribute('decoding', 'async');
            stageImage.setAttribute('fetchpriority', index === 0 ? 'high' : 'auto');
            stageImage.setAttribute('alt', frame.alt || 'Изображение товара');
            if (frame.title) stageImage.setAttribute('title', frame.title);
            else stageImage.removeAttribute('title');

            if (stageLink) stageLink.setAttribute('href', frame.src);

            galleryState.index = index;
            stage.setAttribute('data-cg-gallery-index', String(index));
            markActive(gallery, index);
            $(gallery).trigger('woocommerce_gallery_image_changed', [index]);
            return true;
        }

        frames.forEach(preload);
        gallery._cgSharpGallery = { show: show, frames: frames };

        var activeItem = gallery.querySelector('.flex-control-thumbs img.flex-active');
        var initialIndex = activeItem ? thumbItems.indexOf(activeItem.closest('li')) : 0;
        show(initialIndex >= 0 ? initialIndex : 0);

        return gallery._cgSharpGallery;
    }

    function findGalleryFromEvent(event) {
        var target = event.target;
        if (!target || !target.closest) return null;
        return target.closest('.single-product .woocommerce-product-gallery');
    }

    function handleThumbnail(event) {
        var item = event.target && event.target.closest
            ? event.target.closest('.single-product .flex-control-thumbs li')
            : null;
        if (!item) return;

        var gallery = findGalleryFromEvent(event);
        if (!gallery) return;

        var items = getThumbItems(gallery);
        var index = items.indexOf(item);
        if (index < 0) return;

        var controller = init(gallery);
        if (!controller) return;

        event.preventDefault();
        event.stopPropagation();
        if (event.stopImmediatePropagation) event.stopImmediatePropagation();
        controller.show(index);
    }

    /* Pointer events make this work consistently on touch devices where a
     * synthetic click can be swallowed by the gallery carousel. */
    document.addEventListener('pointerup', handleThumbnail, true);
    document.addEventListener('click', handleThumbnail, true);

    function initAll() {
        document.querySelectorAll('.single-product .woocommerce-product-gallery').forEach(init);
    }

    $(function () {
        initAll();
        var attempts = 0;
        var timer = window.setInterval(function () {
            attempts += 1;
            initAll();
            if (attempts >= 30) window.clearInterval(timer);
        }, 150);
    });

    $(window).on('load', initAll);
    $(document.body).on('wc-product-gallery-after-init', initAll);
})(jQuery);
