(function ($) {
    'use strict';

    var updateTimer = null;
    var deliveryTimer = null;
    var deliveryRequest = null;

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

    function syncDeliveryFields() {
        var $select = $('#cg_cart_delivery_zone');
        var $custom = $('.cg-cart-delivery__custom');
        var $customInput = $('#cg_cart_delivery_custom_city');

        if (!$select.length) return;

        var isOther = $select.val() === 'other';
        $custom.toggleClass('is-hidden', !isOther);
        $customInput.prop('required', isOther);
    }

    function showDeliveryError(message) {
        var $status = $('.cg-cart-delivery__status');
        if (!$status.length) return;

        $status.removeClass('is-priced is-custom').addClass('is-error');
        $status.find('strong').text('Не удалось обновить доставку');
        $status.find('span').text(message || 'Обновите страницу и попробуйте ещё раз.');
    }

    function replaceCartTotals(html) {
        if (!html) return;

        var $current = $('.cart_totals').first();
        if ($current.length) {
            $current.replaceWith(html);
        } else {
            $('.cart-collaterals').append(html);
        }

        syncDeliveryFields();
        $(document.body).trigger('updated_cart_totals');
    }

    function saveDeliveryZone() {
        var config = window.cgCartDelivery || {};
        var $select = $('#cg_cart_delivery_zone');
        var $customInput = $('#cg_cart_delivery_custom_city');
        var $delivery = $('.cg-cart-delivery');

        if (!$select.length || !config.ajaxUrl) return;

        if (deliveryRequest && deliveryRequest.readyState !== 4) {
            deliveryRequest.abort();
        }

        $delivery.addClass('is-loading');

        deliveryRequest = $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                security: config.nonce || '',
                zone: $select.val() || '',
                custom_city: $customInput.val() || ''
            }
        }).done(function (response) {
            if (!response || !response.success || !response.data) {
                showDeliveryError(config.errorText);
                return;
            }

            replaceCartTotals(response.data.cartTotals);
        }).fail(function (xhr, status) {
            if (status !== 'abort') {
                showDeliveryError(config.errorText);
            }
        }).always(function () {
            $('.cg-cart-delivery').removeClass('is-loading');
        });
    }

    function queueDeliveryUpdate() {
        window.clearTimeout(deliveryTimer);
        deliveryTimer = window.setTimeout(saveDeliveryZone, 420);
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

    $(document).on('change', '#cg_cart_delivery_zone', function () {
        syncDeliveryFields();
        saveDeliveryZone();
    });

    $(document).on('input', '#cg_cart_delivery_custom_city', queueDeliveryUpdate);
    $(document).on('change', '#cg_cart_delivery_custom_city', saveDeliveryZone);

    $(document.body).on('updated_wc_div', function () {
        enhanceQuantityControls(document);
        syncDeliveryFields();
    });

    $(function () {
        document.documentElement.classList.add('cg-cart-js');
        enhanceQuantityControls(document);
        syncDeliveryFields();
    });
})(jQuery);
