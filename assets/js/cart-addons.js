(function () {
    'use strict';

    var config = window.cgCartAddons || {};
    var strings = config.strings || {};
    var requestInProgress = false;

    function applyFragments(fragments) {
        if (!fragments) return;

        Object.keys(fragments).forEach(function (selector) {
            var html = fragments[selector];
            document.querySelectorAll(selector).forEach(function (node) {
                var template = document.createElement('template');
                template.innerHTML = String(html || '').trim();
                var replacement = template.content.firstElementChild;
                if (replacement) node.replaceWith(replacement.cloneNode(true));
            });
        });
    }

    function setStatus(message) {
        var status = document.querySelector('[data-cg-cart-addons-status]');
        if (status) status.textContent = message || '';
    }

    function restoreButton(button, originalLabel) {
        var card = button.closest('[data-cg-cart-addon-card]');
        var label = button.querySelector('[data-cg-cart-addon-label]');

        card?.classList.remove('is-adding');
        button.removeAttribute('aria-disabled');
        if (label) label.textContent = originalLabel;
        requestInProgress = false;
    }

    function addProduct(button) {
        if (requestInProgress || !config.ajaxUrl) return;

        var productId = Number(button.getAttribute('data-product-id') || 0);
        if (!productId) return;

        var card = button.closest('[data-cg-cart-addon-card]');
        var label = button.querySelector('[data-cg-cart-addon-label]');
        var originalLabel = label ? label.textContent : 'Добавить';
        var body = new URLSearchParams();

        requestInProgress = true;
        setStatus('');
        card?.classList.add('is-adding');
        button.setAttribute('aria-disabled', 'true');
        if (label) label.textContent = strings.adding || 'Добавляем…';

        body.set('action', 'cg_add_cart_addon');
        body.set('nonce', config.nonce || '');
        body.set('product_id', String(productId));

        fetch(config.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        })
            .then(function (response) {
                if (!response.ok) throw new Error('Cart add-on request failed');
                return response.json();
            })
            .then(function (response) {
                if (!response || !response.success) {
                    var message = response && response.data && response.data.message
                        ? response.data.message
                        : (strings.error || 'Не удалось добавить товар. Попробуйте ещё раз.');
                    throw new Error(message);
                }

                applyFragments(response.data.fragments);
                if (window.jQuery) window.jQuery(document.body).trigger('wc_fragment_refresh');

                card?.classList.remove('is-adding');
                card?.classList.add('is-added');
                if (label) label.textContent = strings.added || 'Добавлено';
                button.setAttribute('aria-disabled', 'true');

                window.setTimeout(function () {
                    var cleanUrl = window.location.pathname + window.location.search + '#cg-cart-addons';
                    window.history.replaceState(null, '', cleanUrl);
                    window.location.reload();
                }, 550);
            })
            .catch(function (error) {
                restoreButton(button, originalLabel);
                setStatus(error && error.message ? error.message : (strings.error || 'Не удалось добавить товар. Попробуйте ещё раз.'));
            });
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-cg-cart-addon]');
        if (!button) return;

        event.preventDefault();
        if (button.getAttribute('aria-disabled') === 'true') return;
        addProduct(button);
    });
})();
