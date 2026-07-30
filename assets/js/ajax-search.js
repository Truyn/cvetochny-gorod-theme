(function () {
    'use strict';

    var config = window.cgAjaxSearch || {};
    var minChars = Number(config.minChars || 2);
    var delay = Number(config.delay || 260);
    var strings = config.strings || {};

    function createLiveSearch(form, formIndex) {
        if (!form || form.dataset.cgLiveSearchReady === '1') return;

        var input = form.querySelector('input[type="search"]');
        if (!input) return;

        form.dataset.cgLiveSearchReady = '1';

        var wrapper = document.createElement('div');
        wrapper.className = 'cg-live-search';
        form.parentNode.insertBefore(wrapper, form);
        wrapper.appendChild(form);

        var panel = document.createElement('div');
        var panelId = 'cg-live-search-results-' + formIndex;
        panel.className = 'cg-live-search__panel';
        panel.id = panelId;
        panel.setAttribute('role', 'listbox');
        panel.setAttribute('aria-label', 'Результаты поиска товаров');
        panel.hidden = true;
        wrapper.appendChild(panel);

        input.setAttribute('autocomplete', 'off');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-controls', panelId);
        input.setAttribute('aria-expanded', 'false');

        var timer = null;
        var controller = null;
        var activeIndex = -1;
        var cache = new Map();

        function options() {
            return Array.prototype.slice.call(panel.querySelectorAll('.cg-live-search__item'));
        }

        function openPanel() {
            panel.hidden = false;
            input.setAttribute('aria-expanded', 'true');
        }

        function closePanel() {
            panel.hidden = true;
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
            activeIndex = -1;
            options().forEach(function (option) {
                option.classList.remove('is-active');
                option.setAttribute('aria-selected', 'false');
            });
        }

        function setStatus(message, className) {
            panel.innerHTML = '<div class="cg-live-search__status ' + (className || '') + '">' + message + '</div>';
            openPanel();
        }

        function setActive(index) {
            var items = options();
            if (!items.length) return;

            activeIndex = (index + items.length) % items.length;
            items.forEach(function (item, itemIndex) {
                var active = itemIndex === activeIndex;
                item.classList.toggle('is-active', active);
                item.setAttribute('aria-selected', active ? 'true' : 'false');
                if (active) {
                    input.setAttribute('aria-activedescendant', item.id);
                    item.scrollIntoView({ block: 'nearest' });
                }
            });
        }

        function renderResult(data) {
            if (!data || !data.count) {
                setStatus(strings.empty || 'По вашему запросу ничего не найдено.', 'is-empty');
                return;
            }

            panel.innerHTML = '<div class="cg-live-search__items">' + data.html + '</div>' +
                '<a class="cg-live-search__all" href="' + data.allUrl + '">' +
                (strings.allResults || 'Показать все результаты') +
                '<span aria-hidden="true">→</span></a>';
            openPanel();
            activeIndex = -1;
        }

        function runSearch() {
            var query = input.value.trim();

            if (query.length < minChars) {
                closePanel();
                panel.innerHTML = '';
                return;
            }

            if (cache.has(query)) {
                renderResult(cache.get(query));
                return;
            }

            if (!config.ajaxUrl) return;
            if (controller) controller.abort();
            controller = new AbortController();

            setStatus(strings.loading || 'Ищем букеты…', 'is-loading');

            var body = new URLSearchParams();
            body.set('action', 'cg_ajax_product_search');
            body.set('nonce', config.nonce || '');
            body.set('query', query);

            fetch(config.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: body.toString(),
                signal: controller.signal
            })
                .then(function (response) {
                    if (!response.ok) throw new Error('Search request failed');
                    return response.json();
                })
                .then(function (response) {
                    if (!response || !response.success || !response.data) throw new Error('Invalid search response');
                    if (input.value.trim() !== query) return;

                    cache.set(query, response.data);
                    renderResult(response.data);
                })
                .catch(function (error) {
                    if (error.name === 'AbortError') return;
                    setStatus(strings.error || 'Не удалось выполнить поиск. Попробуйте ещё раз.', 'is-error');
                });
        }

        input.addEventListener('input', function () {
            window.clearTimeout(timer);
            timer = window.setTimeout(runSearch, delay);
        });

        input.addEventListener('focus', function () {
            if (input.value.trim().length >= minChars && panel.innerHTML.trim() !== '') {
                openPanel();
            }
        });

        input.addEventListener('keydown', function (event) {
            var items = options();

            if (event.key === 'ArrowDown' && items.length) {
                event.preventDefault();
                if (panel.hidden) openPanel();
                setActive(activeIndex + 1);
            } else if (event.key === 'ArrowUp' && items.length) {
                event.preventDefault();
                if (panel.hidden) openPanel();
                setActive(activeIndex - 1);
            } else if (event.key === 'Enter' && activeIndex >= 0 && items[activeIndex]) {
                event.preventDefault();
                window.location.href = items[activeIndex].href;
            } else if (event.key === 'Escape' && !panel.hidden) {
                event.preventDefault();
                closePanel();
            }
        });

        document.addEventListener('click', function (event) {
            if (!wrapper.contains(event.target)) closePanel();
        });

        var searchToggle = document.querySelector('.search-toggle');
        if (searchToggle) {
            searchToggle.addEventListener('click', function () {
                window.setTimeout(function () {
                    var searchPanel = document.querySelector('.header-search');
                    if (searchPanel && !searchPanel.hidden) input.focus();
                }, 30);
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.cg-product-search').forEach(createLiveSearch);
    });
})();
