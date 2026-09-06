(function ($) {
    'use strict';

    function thumbs(gallery) {
        return Array.prototype.slice.call(gallery.querySelectorAll('.flex-control-thumbs img'));
    }

    function sourceFromThumb(thumb) {
        var src = thumb.getAttribute('data-large_image') || thumb.getAttribute('data-large-image') || thumb.getAttribute('data-src') || thumb.getAttribute('data-full-src') || '';
        return {
            src: src || thumb.getAttribute('src') || thumb.currentSrc || '',
            alt: thumb.getAttribute('alt') || '',
            title: thumb.getAttribute('title') || '',
            width: thumb.getAttribute('data-large_image_width') || thumb.getAttribute('data-large-image-width') || '',
            height: thumb.getAttribute('data-large_image_height') || thumb.getAttribute('data-large-image-height') || ''
        };
    }

    function setActive(gallery, index) {
        thumbs(gallery).forEach(function (thumb, i) {
            thumb.classList.toggle('flex-active', i === index);
            thumb.setAttribute('aria-current', i === index ? 'true' : 'false');
        });
    }

    function init(gallery) {
        if (!gallery || gallery._cgGalleryFix) return gallery && gallery._cgGalleryFix;

        var list = thumbs(gallery);
        var stage = gallery.querySelector('.woocommerce-product-gallery__image');
        var image = stage && stage.querySelector('img');
        var link = stage && stage.querySelector('a');
        if (!stage || !image || list.length < 2) return null;

        gallery.classList.add('cg-gallery-manual');

        var viewport = gallery.querySelector('.flex-viewport');
        var wrapper = gallery.querySelector('.woocommerce-product-gallery__wrapper');
        if (viewport) {
            viewport.style.setProperty('overflow', 'hidden', 'important');
            viewport.style.setProperty('height', 'auto', 'important');
            viewport.style.setProperty('min-height', '0', 'important');
        }
        if (wrapper) {
            wrapper.style.setProperty('display', 'block', 'important');
            wrapper.style.setProperty('width', '100%', 'important');
            wrapper.style.setProperty('transform', 'none', 'important');
            wrapper.style.setProperty('margin-left', '0', 'important');
        }

        function show(index) {
            var thumb = list[index];
            var data = sourceFromThumb(thumb);
            if (!data || !data.src) return;

            /* The key fix: use the selected thumbnail's data-large_image URL.
             * Do not reuse the old image's srcset/currentSrc. */
            image.removeAttribute('srcset');
            image.removeAttribute('sizes');
            image.setAttribute('src', data.src);
            image.setAttribute('data-large_image', data.src);
            if (data.width) image.setAttribute('data-large_image_width', data.width);
            if (data.height) image.setAttribute('data-large_image_height', data.height);
            image.setAttribute('alt', data.alt);
            if (data.title) image.setAttribute('title', data.title);
            else image.removeAttribute('title');
            if (link) link.setAttribute('href', data.src);

            setActive(gallery, index);
            stage.setAttribute('data-cg-gallery-index', String(index));
            $(gallery).trigger('woocommerce_gallery_image_changed', [index]);
        }

        list.forEach(function (thumb) {
            thumb.style.cursor = 'pointer';
            var data = sourceFromThumb(thumb);
            if (data && data.src) {
                var preload = new Image();
                preload.src = data.src;
            }
        });

        gallery._cgGalleryFix = { show: show };
        return gallery._cgGalleryFix;
    }

    function initAll() {
        document.querySelectorAll('.single-product .woocommerce-product-gallery').forEach(init);
    }

    document.addEventListener('click', function (event) {
        var thumb = event.target.closest('.single-product .flex-control-thumbs img');
        if (!thumb) return;

        var gallery = thumb.closest('.woocommerce-product-gallery');
        if (!gallery) return;
        var list = thumbs(gallery);
        var index = list.indexOf(thumb);
        if (index < 0) return;

        var controller = init(gallery);
        if (!controller) return;

        event.preventDefault();
        event.stopPropagation();
        if (event.stopImmediatePropagation) event.stopImmediatePropagation();
        controller.show(index);
    }, true);

    $(function () {
        initAll();
        var attempts = 0;
        var timer = window.setInterval(function () {
            attempts += 1;
            initAll();
            if (attempts >= 20) window.clearInterval(timer);
        }, 150);
    });

    $(window).on('load', initAll);
    $(document.body).on('wc-product-gallery-after-init', initAll);
})(jQuery);
