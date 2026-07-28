(function () {
  var checkout = window.wc && window.wc.blocksCheckout;
  if (!checkout || typeof checkout.registerCheckoutFilters !== 'function') return;

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function getOptions(extensions) {
    if (!extensions || !extensions['cvetochny-gorod']) return [];
    var data = extensions['cvetochny-gorod'].order_options;
    return Array.isArray(data) ? data : [];
  }

  function renderOptions(extensions) {
    var options = getOptions(extensions);
    if (!options.length) return '';

    return '<dl class="cg-block-order-options">' + options.map(function (item) {
      if (!item || !item.key || !item.value) return '';
      return '<div><dt>' + escapeHtml(item.key) + '</dt><dd>' + escapeHtml(item.value) + '</dd></div>';
    }).join('') + '</dl>';
  }

  checkout.registerCheckoutFilters('cvetochny-gorod', {
    itemName: function (defaultValue) {
      return defaultValue;
    },
    cartItemClass: function (defaultValue, extensions) {
      return getOptions(extensions).length ? (defaultValue + ' cg-cart-item-has-options').trim() : defaultValue;
    },
    itemNameAfter: function (defaultValue, extensions) {
      return defaultValue + renderOptions(extensions);
    }
  });
}());
