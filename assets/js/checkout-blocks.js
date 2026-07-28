document.addEventListener('DOMContentLoaded', function () {
  function formatOrderOptions(itemData) {
    if (!Array.isArray(itemData) || !itemData.length) return '';

    var allowed = [
      'Текст открытки',
      'Пожелания флористу',
      'Дата доставки',
      'Интервал доставки',
      'Анонимная доставка',
      'Позвонить заранее'
    ];

    var rows = itemData.filter(function (item) {
      return item && allowed.indexOf(item.key) !== -1 && item.value;
    });

    if (!rows.length) return '';

    return '<dl class="cg-block-order-options">' + rows.map(function (item) {
      return '<div><dt>' + String(item.key) + '</dt><dd>' + String(item.value) + '</dd></div>';
    }).join('') + '</dl>';
  }

  function render() {
    document.querySelectorAll('.wc-block-components-order-summary-item').forEach(function (item) {
      if (item.querySelector('.cg-block-order-options')) return;

      var dataNode = item.querySelector('[data-cg-order-options]');
      if (!dataNode) return;

      try {
        var data = JSON.parse(dataNode.getAttribute('data-cg-order-options') || '[]');
        var html = formatOrderOptions(data);
        if (!html) return;

        var target = item.querySelector('.wc-block-components-product-metadata') || item.querySelector('.wc-block-components-order-summary-item__description');
        if (target) target.insertAdjacentHTML('beforeend', html);
      } catch (error) {
        return;
      }
    });
  }

  render();
  new MutationObserver(render).observe(document.body, { childList: true, subtree: true });
});
