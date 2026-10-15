(function ($) {
    'use strict';

    function getSlider(gallery) {
        var wrapper = gallery.querySelector('.woocommerce-product-gallery__wrapper');
        if (!wrapper) return null;

        return $(wrapper).data('flexslider') ||
            $(wrapper).data('flexSlider') ||
            $(gallery).data('flexslider') ||
            null;
    }

    function getImageSource(slide) {
        var image = slide && slide.querySelector('img');
        var link = slide && slide.querySelector('a');
        if (!image) return null;

        function firstNonEmpty(values) {
            for (var i = 0; i < values.length; i++) {
                if (values[i]) return values[i];
            }
            return '';
        }

        return {
            src: firstNonEmpty([
                image.getAttribute('data-large_image'),
                image.getAttribute('data-large-image'),
                image.getAttribute('data-src'),
                image.getAttribute('data-lazy-src'),
                link && link.getAttribute('href'),
                image.getAttribute('src')
            ]),
            srcset: firstNonEmpty([
                image.getAttribute('data-srcset'),
                image.getAttribute('data-lazy-srcset'),
                image.getAttribute('srcset')
            ]),
            sizes: firstNonEmpty([
                image.getAttribute('data-sizes'),
                image.getAttribute('sizes')
            ]),
            alt: image.getAttribute('alt') || '',
            title: image.getAttribute('title') || '',
            width: image.getAttribute('data-large_image_width') || '',
            height: image.getAttribute('data-large_image_height') || ''
        };
    }

    function markActive(gallery, index, thumbs) {
        thumbs.forEach(function (item, i) {
            var image = item.querySelector('img');
            var active = i === index;
            item.classList.toggle('flex-active-slide', active);
            if (!image) return;
            image.classList.toggle('flex-active', active);
            if (active) image.setAttribute('aria-current', 'true');
            else image.removeAttribute('aria-current');
        });
    }

    /*
     * WooCommerce/FlexSlider remains the primary controller. Some optimized
     * or customized product pages render the native markup without a live
     * FlexSlider instance; in that case, update the first image in-place.
     * A single delegated capture handler prevents a second click handler from
     * racing WooCommerce and keeps this fallback scoped to the gallery only.
     */
    function handleThumbnailClick(event) {
        var target = event.target;
        var thumbImage = target && target.closest
            ? target.closest('.woocommerce-product-gallery .flex-control-thumbs img')
            : null;
        if (!thumbImage) return;

        var gallery = thumbImage.closest('.woocommerce-product-gallery');
        if (!gallery) return;

        var thumbs = Array.prototype.slice.call(
            gallery.querySelectorAll('.flex-control-thumbs li')
        );
        var thumbItem = thumbImage.closest('li');
        var index = thumbs.indexOf(thumbItem);
        if (index < 0) return;

        var wrapper = gallery.querySelector('.woocommerce-product-gallery__wrapper');
        if (!wrapper) return;

        var slides = Array.prototype.slice.call(
            wrapper.querySelectorAll(':scope > .woocommerce-product-gallery__image')
        ).filter(function (slide) {
            return !slide.classList.contains('clone');
        });
        if (index >= slides.length) return;

        var slider = getSlider(gallery);
        if (slider && typeof slider.flexAnimate === 'function') {
            if (event.cancelable) event.preventDefault();
            event.stopPropagation();
            if (event.stopImmediatePropagation) event.stopImmediatePropagation();
            slider.flexAnimate(index, true);
            return;
        }

        var frame = getImageSource(slides[index]);
        var stage = slides[0];
        var stageImage = stage && stage.querySelector('img');
        var stageLink = stage && stage.querySelector('a');
        if (!frame || !frame.src || !stageImage) return;

        if (event.cancelable) event.preventDefault();
        event.stopPropagation();
        if (event.stopImmediatePropagation) event.stopImmediatePropagation();

        stageImage.removeAttribute('srcset');
        stageImage.removeAttribute('sizes');
        if (frame.srcset) stageImage.setAttribute('srcset', frame.srcset);
        if (frame.sizes) stageImage.setAttribute('sizes', frame.sizes);
        stageImage.setAttribute('src', frame.src);
        stageImage.setAttribute('data-large_image', frame.src);
        if (frame.width) stageImage.setAttribute('data-large_image_width', frame.width);
        if (frame.height) stageImage.setAttribute('data-large_image_height', frame.height);
        stageImage.setAttribute('alt', frame.alt || 'Изображение товара');
        if (frame.title) stageImage.setAttribute('title', frame.title);
        else stageImage.removeAttribute('title');
        if (stageLink) stageLink.setAttribute('href', frame.src);

        /* Without FlexSlider, expose only the selected main-image frame. */
        slides.forEach(function (slide, slideIndex) {
            slide.style.display = slideIndex === 0 ? '' : 'none';
        });
        stage.setAttribute('data-cg-gallery-index', String(index));
        markActive(gallery, index, thumbs);
    }

    document.addEventListener('click', handleThumbnailClick, true);
})(jQuery);
