document.addEventListener('DOMContentLoaded', function () {
  function getFavorites() {
    try {
      var parsed = JSON.parse(localStorage.getItem('cgFavorites') || '[]');
      return Array.isArray(parsed) ? parsed.map(String) : [];
    } catch (error) {
      return [];
    }
  }

  function saveFavorites(items) {
    localStorage.setItem('cgFavorites', JSON.stringify(items));
    document.querySelectorAll('[data-cg-favorites-count]').forEach(function (counter) {
      counter.textContent = String(items.length);
      counter.hidden = items.length === 0;
    });
  }

  var favorites = getFavorites();
  saveFavorites(favorites);

  document.querySelectorAll('.single-product form.cart .quantity').forEach(function (quantity) {
    var input = quantity.querySelector('input.qty');
    if (!input || quantity.querySelector('.cg-qty-btn')) return;

    quantity.classList.add('cg-quantity-ready');

    var minus = document.createElement('button');
    minus.type = 'button';
    minus.className = 'cg-qty-btn cg-qty-btn--minus';
    minus.setAttribute('aria-label', 'Уменьшить количество');
    minus.textContent = '−';

    var plus = document.createElement('button');
    plus.type = 'button';
    plus.className = 'cg-qty-btn cg-qty-btn--plus';
    plus.setAttribute('aria-label', 'Увеличить количество');
    plus.textContent = '+';

    quantity.insertBefore(minus, input);
    quantity.appendChild(plus);

    function update(direction) {
      var min = input.min !== '' ? parseFloat(input.min) : 1;
      var max = input.max !== '' ? parseFloat(input.max) : Infinity;
      var increment = input.step !== '' ? parseFloat(input.step) : 1;
      var value = input.value !== '' ? parseFloat(input.value) : min;
      var decimals = (String(increment).split('.')[1] || '').length;
      var next = Math.min(max, Math.max(min, value + direction * increment));

      input.value = decimals ? next.toFixed(decimals) : String(Math.round(next));
      input.dispatchEvent(new Event('input', { bubbles: true }));
      input.dispatchEvent(new Event('change', { bubbles: true }));
      minus.disabled = next <= min;
      plus.disabled = Number.isFinite(max) && next >= max;
    }

    minus.addEventListener('click', function () { update(-1); });
    plus.addEventListener('click', function () { update(1); });
    input.addEventListener('change', function () { update(0); });
    update(0);
  });

  document.querySelectorAll('[data-cg-favorite]').forEach(function (button) {
    var productId = String(button.getAttribute('data-product-id') || '');
    var label = button.querySelector('span');

    function render() {
      var active = favorites.indexOf(productId) !== -1;
      button.classList.toggle('is-active', active);
      button.setAttribute('aria-pressed', active ? 'true' : 'false');
      if (label) label.textContent = active ? 'В избранном' : 'В избранное';
    }

    render();
    button.addEventListener('click', function () {
      if (!productId) return;
      var index = favorites.indexOf(productId);
      if (index === -1) favorites.push(productId);
      else favorites.splice(index, 1);
      saveFavorites(favorites);
      render();
    });
  });

  document.querySelectorAll('[data-cg-share]').forEach(function (share) {
    var toggle = share.querySelector('[data-cg-share-toggle]');
    var menu = share.querySelector('[data-cg-share-menu]');
    if (!toggle || !menu) return;

    toggle.addEventListener('click', function () {
      var open = menu.hasAttribute('hidden');
      document.querySelectorAll('[data-cg-share-menu]').forEach(function (otherMenu) {
        otherMenu.setAttribute('hidden', '');
      });
      document.querySelectorAll('[data-cg-share-toggle]').forEach(function (otherToggle) {
        otherToggle.setAttribute('aria-expanded', 'false');
      });
      if (open) {
        menu.removeAttribute('hidden');
        toggle.setAttribute('aria-expanded', 'true');
      }
    });
  });

  document.querySelectorAll('[data-cg-copy-link]').forEach(function (button) {
    button.addEventListener('click', function () {
      var url = button.getAttribute('data-url') || window.location.href;
      var original = button.textContent;
      var done = function () {
        button.textContent = 'Ссылка скопирована';
        window.setTimeout(function () { button.textContent = original; }, 1800);
      };

      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(url).then(done);
      } else {
        var field = document.createElement('textarea');
        field.value = url;
        field.style.position = 'fixed';
        field.style.opacity = '0';
        document.body.appendChild(field);
        field.select();
        document.execCommand('copy');
        field.remove();
        done();
      }
    });
  });

  document.addEventListener('click', function (event) {
    if (event.target.closest('[data-cg-share]')) return;
    document.querySelectorAll('[data-cg-share-menu]').forEach(function (menu) {
      menu.setAttribute('hidden', '');
    });
    document.querySelectorAll('[data-cg-share-toggle]').forEach(function (toggle) {
      toggle.setAttribute('aria-expanded', 'false');
    });
  });
});