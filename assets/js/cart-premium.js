(function ($) {
    'use strict';

    var updateTimer = null;

    function decimalPlaces(value) {
        var text = String(value || '1');
        return text.indexOf('.') === -1 ? 0 : text.split('.')[1].length;
    }

    function normalizeValue(value, min, max, step) {
        var next = Math.max(min, value);

        if (Number.isFinite(max)) {
            next = Math.min(max, next);
        }

        var precision = decimalPlaces(step);
        return Number(next.toFixed(precision));
    }

    function queueCartUpdate() {
        var $button = $('button[name="update_cart"]');
        if (!$button.length) return;

        $button.prop('disabled', false).removeAttr('disabled');
        window.clearTimeout(updateTimer);

        updateTimer = window.setTimeout(function () {
            if (!$button.prop('disabled')) {
                $button.trigger('click');
            }
        }, 550);
    }

    function enhanceQuantityControls(context) {
        $(context).find('.woocommerce-cart-form .quantity').each(function () {
            var $quantity = $(this);
            var $input = $quantity.find('input.qty');

            if (!$input.length || $quantity.hasClass('cg-quantity-ready')) return;

            $quantity.addClass('cg-quantity-ready');
            $('<button type="button" class="cg-qty-button cg-qty-button--minus" aria-label="Уменьшить количество">−</button>').insertBefore($input);
            $('<button type="button" class="cg-qty-button cg-qty-button--plus" aria-label="Увеличить количество">+</button>').insertAfter($input);
        });
    }

    $(document).on('click', '.cg-qty-button', function () {
        var $button = $(this);
        var $input = $button.siblings('input.qty');
        var current = parseFloat($input.val());
        var step = parseFloat($input.attr('step')) || 1;
        var min = parseFloat($input.attr('min'));
        var max = parseFloat($input.attr('max'));

        if (!Number.isFinite(current)) current = 0;
        if (!Number.isFinite(min)) min = 0;

        var next = $button.hasClass('cg-qty-button--plus')
            ? current + step
            : current - step;

        $input.val(normalizeValue(next, min, max, step)).trigger('change');
    });

    $(document).on('change', '.woocommerce-cart-form input.qty', queueCartUpdate);

    $(document.body).on('updated_wc_div', function () {
        enhanceQuantityControls(document);
    });

    $(function () {
        document.documentElement.classList.add('cg-cart-js');
        enhanceQuantityControls(document);
    });
})(jQuery);
