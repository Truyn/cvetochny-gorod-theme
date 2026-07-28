(function ($) {
    'use strict';

    function getConfig() {
        return window.cgDeliveryZones || { zones: {}, messages: {} };
    }

    function updateDeliveryZoneUi() {
        var config = getConfig();
        var $select = $('#cg_delivery_zone');
        var $customField = $('#cg_delivery_custom_city_field');
        var $customInput = $('#cg_delivery_custom_city');
        var $note = $('#cg_delivery_zone_note');

        if (!$select.length) return;

        var selected = $select.val() || '';
        var isOther = selected === 'other';
        var zone = config.zones && config.zones[selected] ? config.zones[selected] : null;

        $customField.toggleClass('is-hidden', !isOther);
        $customField.attr('aria-hidden', isOther ? 'false' : 'true');
        $customInput.prop('required', isOther);

        if (!$note.length) return;

        $note.removeClass('is-priced is-custom');

        if (isOther) {
            $note.addClass('is-custom').text(
                config.messages.other || 'Стоимость доставки уточним после оформления заказа.'
            );
            return;
        }

        if (zone) {
            var message = config.messages.known || 'Стоимость доставки: %s.';
            $note.addClass('is-priced').text(message.replace('%s', zone.price));
            return;
        }

        $note.text(
            config.messages.empty || 'Выберите населённый пункт — стоимость доставки сразу появится в заказе.'
        );
    }

    $(document.body).on('change', '#cg_delivery_zone', function () {
        updateDeliveryZoneUi();
        $(document.body).trigger('update_checkout');
    });

    $(document.body).on('updated_checkout', updateDeliveryZoneUi);

    $(updateDeliveryZoneUi);
})(jQuery);
