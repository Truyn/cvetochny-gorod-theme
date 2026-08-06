(function () {
    'use strict';

    var config = window.cgFavoritesAccountSync || {};
    var storageKey = 'cgFavorites';
    var updatedKey = 'cgFavoritesUpdatedAt';
    var lastObserved = '';
    var lastSubmitted = '';
    var syncTimer = null;
    var retryTimer = null;
    var controller = null;
    var statusNode = null;
    var applyingServerState = false;

    function normalize(items) {
        if (!Array.isArray(items)) return [];

        return items
            .map(function (item) { return String(parseInt(item, 10) || ''); })
            .filter(function (item, index, list) {
                return item !== '0' && list.indexOf(item) === index;
            })
            .slice(0, 100);
    }

    function serialize(items) {
        return normalize(items).join(',');
    }

    function readLocal() {
        try {
            return normalize(JSON.parse(window.localStorage.getItem(storageKey) || '[]'));
        } catch (error) {
            return [];
        }
    }

    function readUpdatedAt() {
        try {
            return Math.max(0, parseInt(window.localStorage.getItem(updatedKey) || '0', 10) || 0);
        } catch (error) {
            return 0;
        }
    }

    function writeUpdatedAt(value) {
        value = Math.max(0, parseInt(value, 10) || 0);
        try {
            window.localStorage.setItem(updatedKey, String(value));
        } catch (error) {
            // Favorites still work in memory when storage is unavailable.
        }
        return value;
    }

    function dispatchStorageUpdate(value) {
        try {
            window.dispatchEvent(new StorageEvent('storage', {
                key: storageKey,
                newValue: value,
                storageArea: window.localStorage,
                url: window.location.href
            }));
        } catch (error) {
            try {
                var fallback = document.createEvent('Event');
                fallback.initEvent('storage', false, false);
                Object.defineProperty(fallback, 'key', { value: storageKey });
                window.dispatchEvent(fallback);
            } catch (ignored) {
                // Older browsers will refresh the interface on the next page view.
            }
        }
    }

    function writeLocal(items, notifyInterface, updatedAt, fromServer) {
        var normalized = normalize(items);
        var value = JSON.stringify(normalized);

        try {
            window.localStorage.setItem(storageKey, value);
        } catch (error) {
            return normalized;
        }

        if (updatedAt) writeUpdatedAt(updatedAt);
        lastObserved = serialize(normalized);

        if (notifyInterface) {
            applyingServerState = Boolean(fromServer);
            dispatchStorageUpdate(value);
            applyingServerState = false;
        }

        return normalized;
    }

    function mergeLists(serverIds, localIds) {
        return normalize(normalize(serverIds).concat(normalize(localIds)));
    }

    function statusText(key) {
        var strings = config.strings || {};
        return strings[key] || '';
    }

    function setStatus(state, text) {
        if (!statusNode) return;
        statusNode.className = 'cg-favorites-sync-status is-' + state;

        var textNode = statusNode.querySelector('[data-cg-favorites-sync-text]');
        if (textNode) textNode.textContent = text || statusText(state);
    }

    function injectStatus() {
        var page = document.querySelector('[data-cg-favorites-page]');
        if (!page || page.querySelector('[data-cg-favorites-sync-status]')) return;

        var toolbar = page.querySelector('.cg-favorites__toolbar');
        if (!toolbar) return;

        statusNode = document.createElement('div');
        statusNode.setAttribute('data-cg-favorites-sync-status', '');

        if (config.loggedIn) {
            statusNode.className = 'cg-favorites-sync-status is-ready';
            statusNode.innerHTML = '<span class="cg-favorites-sync-status__icon" aria-hidden="true">✓</span>'
                + '<span data-cg-favorites-sync-text></span>';
            toolbar.insertAdjacentElement('afterend', statusNode);
            setStatus('ready', statusText('ready'));
            return;
        }

        statusNode.className = 'cg-favorites-sync-status is-guest';
        statusNode.innerHTML = '<span class="cg-favorites-sync-status__icon" aria-hidden="true">♡</span>'
            + '<span data-cg-favorites-sync-text></span>'
            + '<a href="' + String(config.accountUrl || '#') + '"></a>';
        toolbar.insertAdjacentElement('afterend', statusNode);

        var guestText = statusNode.querySelector('[data-cg-favorites-sync-text]');
        var loginLink = statusNode.querySelector('a');
        if (guestText) guestText.textContent = statusText('guest');
        if (loginLink) loginLink.textContent = statusText('login');
    }

    function scheduleRetry() {
        window.clearTimeout(retryTimer);
        retryTimer = window.setTimeout(function () {
            queueSync(readLocal(), readUpdatedAt(), 0, true);
        }, 10000);
    }

    function performSync(items, updatedAt, force) {
        if (!config.loggedIn || !config.ajaxUrl || !config.nonce) return;

        var normalized = normalize(items);
        var signature = serialize(normalized);
        var clientUpdatedAt = Math.max(1, parseInt(updatedAt, 10) || Date.now());

        if (!force && signature === lastSubmitted) {
            setStatus('saved', statusText('saved'));
            return;
        }

        controller && controller.abort();
        controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
        setStatus('syncing', statusText('syncing'));

        var body = new FormData();
        body.append('action', 'cg_sync_account_favorites');
        body.append('nonce', config.nonce);
        body.append('updated_at', String(clientUpdatedAt));
        normalized.forEach(function (productId) {
            body.append('ids[]', productId);
        });

        window.fetch(config.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: body,
            signal: controller ? controller.signal : undefined
        })
            .then(function (response) {
                if (!response.ok) throw new Error('Favorites sync failed');
                return response.json();
            })
            .then(function (response) {
                if (!response || !response.success || !response.data) {
                    throw new Error('Invalid favorites sync response');
                }

                var savedIds = normalize(response.data.ids || []);
                var savedSignature = serialize(savedIds);
                var serverUpdatedAt = Math.max(0, parseInt(response.data.updatedAt, 10) || 0);
                lastSubmitted = savedSignature;
                window.clearTimeout(retryTimer);

                if (savedSignature !== serialize(readLocal())) {
                    writeLocal(savedIds, true, serverUpdatedAt, true);
                } else {
                    lastObserved = savedSignature;
                    if (serverUpdatedAt) writeUpdatedAt(serverUpdatedAt);
                }

                setStatus('saved', statusText('saved'));
            })
            .catch(function (error) {
                if (error && error.name === 'AbortError') return;
                setStatus('error', statusText('error'));
                scheduleRetry();
            });
    }

    function queueSync(items, updatedAt, delay, force) {
        if (!config.loggedIn) return;
        window.clearTimeout(syncTimer);
        syncTimer = window.setTimeout(function () {
            performSync(items, updatedAt, Boolean(force));
        }, typeof delay === 'number' ? delay : 300);
    }

    function observeLocalChanges(force) {
        var items = readLocal();
        var signature = serialize(items);
        var changed = signature !== lastObserved;

        if (changed && !applyingServerState) {
            writeUpdatedAt(Date.now());
        }

        if (!force && !changed) return;
        lastObserved = signature;

        if (config.loggedIn) {
            queueSync(items, readUpdatedAt(), 250, false);
        }
    }

    /**
     * This script is enqueued before favorites.js. Resolve account and browser
     * state now, so the standard interface initializes with the correct list.
     */
    function primeAccountFavorites() {
        var localIds = readLocal();
        var localUpdatedAt = readUpdatedAt();
        lastObserved = serialize(localIds);

        if (!config.loggedIn) return;

        var serverIds = normalize(config.serverIds || []);
        var serverUpdatedAt = Math.max(0, parseInt(config.serverUpdatedAt, 10) || 0);
        var serverSignature = serialize(serverIds);
        var chosenIds = localIds;
        var chosenUpdatedAt = localUpdatedAt;
        var shouldSync = false;

        lastSubmitted = serverSignature;

        // Legacy browser favorites have no timestamp. Merge them once so an
        // existing guest selection is not lost on the first signed-in visit.
        if (localIds.length && localUpdatedAt === 0) {
            chosenIds = mergeLists(serverIds, localIds);
            chosenUpdatedAt = Date.now();
            shouldSync = serialize(chosenIds) !== serverSignature;
        } else if (serverUpdatedAt > localUpdatedAt) {
            chosenIds = serverIds;
            chosenUpdatedAt = serverUpdatedAt;
        } else if (localUpdatedAt > serverUpdatedAt) {
            chosenIds = localIds;
            chosenUpdatedAt = localUpdatedAt;
            shouldSync = serialize(chosenIds) !== serverSignature;
        } else if (serialize(localIds) !== serverSignature) {
            chosenIds = mergeLists(serverIds, localIds);
            chosenUpdatedAt = Date.now();
            shouldSync = true;
        } else {
            chosenIds = serverIds;
            chosenUpdatedAt = serverUpdatedAt || localUpdatedAt;
        }

        if (serialize(chosenIds) !== serialize(localIds) || chosenUpdatedAt !== localUpdatedAt) {
            writeLocal(chosenIds, false, chosenUpdatedAt, true);
        } else {
            lastObserved = serialize(chosenIds);
        }

        if (shouldSync) {
            queueSync(chosenIds, chosenUpdatedAt, 50, true);
        }
    }

    primeAccountFavorites();

    window.addEventListener('click', function (event) {
        var target = event.target;
        var button = target && target.closest ? target.closest('[data-cg-favorite]') : null;
        if (!button) return;

        window.setTimeout(function () {
            observeLocalChanges(true);
        }, 80);
    }, true);

    window.addEventListener('storage', function (event) {
        if (event.key !== storageKey || applyingServerState) return;
        observeLocalChanges(true);
    });

    window.addEventListener('online', function () {
        observeLocalChanges(true);
    });

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) observeLocalChanges(false);
    });

    document.addEventListener('DOMContentLoaded', function () {
        injectStatus();
        observeLocalChanges(false);

        window.setInterval(function () {
            observeLocalChanges(false);
        }, 1500);
    });
})();
