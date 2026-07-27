document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.single-product form.cart .quantity').forEach(function (quantity) {
    var input = quantity.querySelector('input.qty');
    if (!input || quantity.querySelector('.cg-qty-btn')) return;

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

    function update(step) {
      var min = parseFloat(input.min || '0');
      var max = input.max ? parseFloat(input.max) : Infinity;
      var increment = parseFloat(input.step || '1');
      var value = parseFloat(input.value || '0');
      var next = Math.min(max, Math.max(min, value + step * increment));
      input.value = String(next);
      input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    minus.addEventListener('click', function () { update(-1); });
    plus.addEventListener('click', function () { update(1); });
  });

  document.querySelectorAll('[data-cg-favorite]').forEach(function (button) {
    button.addEventListener('click', function (event) {
      event.preventDefault();
      var active = button.classList.toggle('is-active');
      var label = button.querySelector('span');
      button.setAttribute('aria-pressed', active ? 'true' : 'false');
      if (label) label.textContent = active ? 'В избранном' : 'В избранное';
    });
  });
});
