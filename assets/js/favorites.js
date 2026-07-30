(function () {
    'use strict';

    var storageKey = 'cgFavorites';
    var favorites = [];
    var observerTimer = null;

    function normalize(items) {
        if (!Array.isArray(items)) return [];

        return items
            .map(function (item) { return String(parseInt(item, 10) || ''); })
            .filter(function (item, index, list) { return item !== '0' && list.indexOf(item) === index; })
            .slice(0, 100);
    }

    function readFavorites() {
        try {
            return normalize(JSON.parse(window.localStorage.getItem(storageKey) || '[]'));
        } catch (error) {
            return [];
        }
    }

    function storeFavorites(items) {
        favorites = normalize(items);

        try {
            window.localStorage.setItem(storageKey, JSON.stringify(favorites));
        } catch (error) {
            // The interface still works for the current page when storage is unavailable.
        }

        syncInterface(document);
    }

    function contains(productId) {
        return favorites.indexOf(String(productId)) !== -1;
    }

    function pluralSummary(count) {
        if (count === 1) return '1 сохранённый букет';
        if (count > 1 && count < 5) return count + ' сохранённых букета';
        return count + ' сохранённых букетов';
    }

    function syncCounter() {
        document.querySelectorAll('[data-cg-favorites-count]').forEach(function (counter) {
            var value = String(favorites.length);
            if (counter.textContent !== value) counter.textContent = value;
            if (counter.hidden !== (favorites.length === 0)) counter.hidden = favorites.length === 0;
        });
    }

    function syncButton(button) {
        var productId = String(button.getAttribute('data-product-id') || '');
        if (!productId) return;

        var active = contains(productId);
        var label = button.querySelector('[data-cg-favorite-label], span:not(.screen-reader-text)');
        var isSingleProductAction = button.classList.contains('cg-product-action');
        var addText = isSingleProductAction ? 'В избранное' : 'Добавить в избранное';
        var removeText = isSingleProductAction ? 'В избранном' : 'Удалить из избранного';
        var text = active ? removeText : addText;

        button.classList.toggle('is-active', active);
        if (button.getAttribute('aria-pressed') !== (active ? 'true' : 'false')) {
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        }
        if (button.getAttribute('aria-label') !== text) button.setAttribute('aria-label', text);
        if (label && label.textContent !== text) label.textContent = text;
    }

    function syncInterface(root) {
        syncCounter();
        (root || document).querySelectorAll('[data-cg-favorite]').forEach(syncButton);

        var summary = document.querySelector('[data-cg-favorites-summary]');
        var summaryText = pluralSummary(favorites.length);
        if (summary && summary.textContent !== summaryText) summary.textContent = summaryText;
    }

    function showEmptyState() {
        var page = document.querySelector('[data-cg-favorites-page]');
        if (!page) return;

        var grid = page.querySelector('[data-cg-favorites-grid]');
        var empty = page.querySelector('[data-cg-favorites-empty]');
        var loading = page.querySelector('[data-cg-favorites-loading]');
        var error = page.querySelector('[data-cg-favorites-error]');

        if (grid) grid.innerHTML = '';
        if (loading) loading.hidden = true;
        if (error) error.hidden = true;
        if (empty) empty.hidden = false;
    }

    function refreshPageState() {
        var page = document.querySelector('[data-cg-favorites-page]');
        if (!page) return;

        var cards = page.querySelectorAll('[data-cg-favorite-card]');
        var empty = page.querySelector('[data-cg-favorites-empty]');
        if (empty) empty.hidden = cards.length !== 0;
    }

    function removeFavoriteCard(productId) {
        var card = document.querySelector('[data-cg-favorite-card][data-product-id="' + productId + '"]');
        if (!card) return;

        card.classList.add('is-removing');
        window.setTimeout(function () {
            card.remove();
            refreshPageState();
        }, 180);
    }

    function toggleFavorite(productId) {
        productId = String(productId || '');
        if (!productId) return;

        var next = favorites.slice();
        var index = next.indexOf(productId);

        if (index === -1) {
            next.push(productId);
        } else {
            next.splice(index, 1);
        }

        storeFavorites(next);

        if (!contains(productId)) {
            removeFavoriteCard(productId);
        }
    }

    function loadFavoritesPage() {
        var page = document.querySelector('[data-cg-favorites-page]');
        if (!page) return;

        var grid = page.querySelector('[data-cg-favorites-grid]');
        var loading = page.querySelector('[data-cg-favorites-loading]');
        var empty = page.querySelector('[data-cg-favorites-empty]');
        var error = page.querySelector('[data-cg-favorites-error]');
        var config = window.cgFavoritesConfig || {};

        if (!favorites.length) {
            showEmptyState();
            return;
        }

        if (!config.ajaxUrl || !grid) {
            if (loading) loading.hidden = true;
            if (error) error.hidden = false;
            return;
        }

        if (loading) loading.hidden = false;
        if (empty) empty.hidden = true;
        if (error) error.hidden = true;

        var formData = new FormData();
        formData.append('action', 'cg_load_favorites');
        formData.append('nonce', config.nonce || '');
        favorites.forEach(function (productId) {
            formData.append('ids[]', productId);
        });

        window.fetch(config.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        })
            .then(function (response) {
                if (!response.ok) throw new Error('Request failed');
                return response.json();
            })
            .then(function (response) {
                if (!response || !response.success || !response.data) throw new Error('Invalid response');

                grid.innerHTML = response.data.html || '';
                if (loading) loading.hidden = true;

                var validIds = normalize(response.data.validIds || []);
                if (validIds.join(',') !== favorites.join(',')) {
                    storeFavorites(validIds);
                } else {
                    syncInterface(grid);
                }

                refreshPageState();
            })
            .catch(function () {
                if (loading) loading.hidden = true;
                if (error) error.hidden = false;
            });
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-cg-favorite]');
        if (!button) return;

        event.preventDefault();
        event.stopImmediatePropagation();
        toggleFavorite(button.getAttribute('data-product-id'));
    }, true);

    window.addEventListener('storage', function (event) {
        if (event.key !== storageKey) return;
        favorites = readFavorites();
        syncInterface(document);
        loadFavoritesPage();
    });

    document.addEventListener('DOMContentLoaded', function () {
        favorites = readFavorites();
        syncInterface(document);
        loadFavoritesPage();

        var observer = new MutationObserver(function () {
            window.clearTimeout(observerTimer);
            observerTimer = window.setTimeout(function () {
                syncInterface(document);
            }, 50);
        });

        observer.observe(document.body, { childList: true, subtree: true });
    });
})();
