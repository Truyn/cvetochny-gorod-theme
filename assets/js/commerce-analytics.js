(function ($) {
    'use strict';

    var cfg = window.cgAnalytics || {};
    window.dataLayer = window.dataLayer || [];

    function normalizeItem(source, quantity) {
        if (!source) return null;
        var item = {
            item_id: String(source.item_id || source.id || ''),
            item_name: String(source.item_name || source.name || ''),
            item_category: String(source.item_category || source.category || ''),
            price: Number(source.price || 0),
            quantity: Math.max(1, Number(quantity || source.quantity || 1))
        };
        Object.keys(item).forEach(function (key) {
            if (item[key] === '' || item[key] === null || Number.isNaN(item[key])) delete item[key];
        });
        return item;
    }

    function emit(payload) {
        if (!payload || !payload.event) return;

        if (payload.event_key) {
            try {
                var key = 'cg_analytics_' + payload.event_key;
                if (window.sessionStorage.getItem(key)) return;
                window.sessionStorage.setItem(key, '1');
            } catch (e) {}
        }

        if (cfg.ga4Id && typeof window.gtag === 'function') {
            var params = payload.ecommerce ? Object.assign({}, payload.ecommerce) : {};
            Object.keys(payload).forEach(function (key) {
                if (key !== 'event' && key !== 'event_key' && key !== 'ecommerce') params[key] = payload[key];
            });
            window.gtag('event', payload.event, params);
        } else {
            window.dataLayer.push(payload);
        }
    }

    function itemFromButton(button) {
        if (!button) return null;
        return normalizeItem({
            item_id: button.getAttribute('data-cg-item-id') || button.getAttribute('data-product_id') || '',
            item_name: button.getAttribute('data-cg-item-name') || '',
            item_category: button.getAttribute('data-cg-item-category') || '',
            price: button.getAttribute('data-cg-item-price') || 0
        }, button.getAttribute('data-quantity') || 1);
    }

    function currentListItems() {
        var items = [];
        document.querySelectorAll('.products .add_to_cart_button[data-cg-item-id]').forEach(function (button, index) {
            var item = itemFromButton(button);
            if (!item) return;
            item.index = index;
            items.push(item);
        });
        return items.slice(0, 24);
    }

    document.addEventListener('DOMContentLoaded', function () {
        emit(cfg.pageEvent);

        if (cfg.catalog || document.querySelector('.products')) {
            var listItems = currentListItems();
            if (listItems.length) {
                emit({
                    event: 'view_item_list',
                    ecommerce: {
                        item_list_name: cfg.catalog ? 'Каталог' : 'Список товаров',
                        items: listItems
                    }
                });
            }
        }

        var singleButton = document.querySelector('button.single_add_to_cart_button');
        if (singleButton && cfg.currentProduct) {
            singleButton.addEventListener('click', function () {
                var quantity = document.querySelector('form.cart input.qty');
                var item = normalizeItem(cfg.currentProduct, quantity ? quantity.value : 1);
                emit({
                    event: 'add_to_cart',
                    ecommerce: {
                        currency: cfg.currency || '',
                        value: item && item.price ? item.price * item.quantity : 0,
                        items: item ? [item] : []
                    }
                });
            });
        }

        document.addEventListener('click', function (event) {
            var favorite = event.target.closest('[data-cg-favorite]');
            if (favorite && favorite.getAttribute('aria-pressed') !== 'true') {
                var productId = favorite.getAttribute('data-product-id') || '';
                var item = cfg.currentProduct && String(cfg.currentProduct.item_id || '') === String(productId)
                    ? normalizeItem(cfg.currentProduct, 1)
                    : normalizeItem({ item_id: productId }, 1);
                emit({
                    event: 'add_to_wishlist',
                    ecommerce: { currency: cfg.currency || '', items: item ? [item] : [] }
                });
            }
        });

        var catalogForm = document.querySelector('.cg-catalog-filter-form');
        if (catalogForm) {
            catalogForm.addEventListener('submit', function () {
                var search = catalogForm.querySelector('input[name="catalog_search"]');
                if (search && search.value.trim()) emit({ event: 'search', search_term: search.value.trim() });
            });
            catalogForm.addEventListener('change', function (event) {
                var field = event.target;
                if (!field || !field.name || field.name === 'catalog_search') return;
                var value = field.type === 'checkbox' && !field.checked ? 'off' : field.value;
                emit({ event: 'catalog_filter', filter_name: field.name.replace(/\[\]$/, ''), filter_value: value });
            });
        }
    });

    $(document.body).on('added_to_cart', function (event, fragments, cartHash, $button) {
        var button = $button && $button[0] ? $button[0] : null;
        var item = itemFromButton(button);
        if (!item) return;
        emit({
            event: 'add_to_cart',
            ecommerce: {
                currency: cfg.currency || '',
                value: item.price ? item.price * item.quantity : 0,
                items: [item]
            }
        });
    });
})(jQuery);
