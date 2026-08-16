(function ($) {
    'use strict';

    var phoneSelector = '#billing_phone, #cg_sender_phone';

    function formatRussianPhone(value) {
        var digits = String(value || '').replace(/\D/g, '');
        if (!digits) return '+7 ';

        if (digits.charAt(0) === '8') digits = '7' + digits.slice(1);
        if (digits.charAt(0) !== '7') digits = '7' + digits;
        digits = digits.slice(0, 11);

        var local = digits.slice(1);
        var result = '+7';
        if (local.length) result += ' (' + local.slice(0, 3);
        if (local.length >= 3) result += ')';
        if (local.length > 3) result += ' ' + local.slice(3, 6);
        if (local.length > 6) result += '-' + local.slice(6, 8);
        if (local.length > 8) result += '-' + local.slice(8, 10);
        return result;
    }

    $(document).on('focus', phoneSelector, function () {
        if (!String(this.value || '').trim()) this.value = '+7 ';
    });

    $(document).on('input', phoneSelector, function () {
        var formatted = formatRussianPhone(this.value);
        if (this.value !== formatted) this.value = formatted;
    });

    $(document).on('blur', phoneSelector, function () {
        var digits = String(this.value || '').replace(/\D/g, '');
        if (!digits || digits === '7') this.value = '+7 ';
    });

    function focusFirstInvalidField() {
        var $row = $('.woocommerce-checkout .woocommerce-invalid:visible').first();
        if (!$row.length) {
            $row = $('.woocommerce-checkout .validate-required:visible').filter(function () {
                var $field = $(this).find('input, select, textarea').first();
                return $field.length && !String($field.val() || '').trim();
            }).first();
        }
        if (!$row.length) return;

        $('.cg-field-attention').removeClass('cg-field-attention');
        $row.addClass('cg-field-attention');
        var field = $row.find('input, select, textarea').get(0);
        var target = field || $row.get(0);
        if (!target) return;

        var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (target.scrollIntoView) {
            target.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
        }
        if (field && typeof field.focus === 'function') {
            window.setTimeout(function () { field.focus({ preventScroll: true }); }, reduceMotion ? 0 : 250);
        }
    }

    $(document.body).on('checkout_error', function () {
        window.setTimeout(focusFirstInvalidField, 60);
    });

    $(function () {
        $(phoneSelector).each(function () {
            var value = String(this.value || '').trim();
            if (value) this.value = formatRussianPhone(value);
        });
    });
})(jQuery);
