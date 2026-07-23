(function () {
  'use strict';

  function setImportant(element, property, value) {
    if (!element) return;
    element.style.setProperty(property, value, 'important');
  }

  function alignProductColumns() {
    if (window.matchMedia('(max-width: 980px)').matches) return;

    var product = document.querySelector('.single-product .woocommerce div.product');
    if (!product) return;

    var gallery = product.querySelector(':scope > div.images, :scope > .woocommerce-product-gallery');
    var summary = product.querySelector(':scope > div.summary');
    if (!gallery || !summary) return;

    setImportant(gallery, 'transform', 'none');
    setImportant(gallery, 'margin-top', '0');
    setImportant(gallery, 'padding-top', '0');

    var galleryTop = gallery.getBoundingClientRect().top;
    var summaryTop = summary.getBoundingClientRect().top;
    var difference = summaryTop - galleryTop;

    if (Math.abs(difference) > 1 && Math.abs(difference) < 300) {
      setImportant(gallery, 'transform', 'translateY(' + difference + 'px)');
    }
  }

  function alignCartControls() {
    var forms = document.querySelectorAll('.single-product .woocommerce div.product form.cart');

    forms.forEach(function (form) {
      var row = form.querySelector('.woocommerce-variation-add-to-cart') || form;
      var quantity = row.querySelector('.quantity');
      var input = quantity ? quantity.querySelector('.qty') : null;
      var button = row.querySelector('.single_add_to_cart_button');

      if (!quantity || !button) return;

      setImportant(row, 'display', 'flex');
      setImportant(row, 'align-items', 'stretch');
      setImportant(row, 'gap', '12px');
      setImportant(row, 'flex-wrap', 'nowrap');
      setImportant(row, 'width', '100%');
      setImportant(row, 'height', '54px');
      setImportant(row, 'min-height', '54px');
      setImportant(row, 'padding', '0');
      setImportant(row, 'border', '0');
      setImportant(row, 'background', 'transparent');

      setImportant(quantity, 'flex', '0 0 104px');
      setImportant(quantity, 'width', '104px');
      setImportant(quantity, 'min-width', '104px');
      setImportant(quantity, 'max-width', '104px');
      setImportant(quantity, 'height', '54px');
      setImportant(quantity, 'margin', '0');
      setImportant(quantity, 'padding', '0');

      if (input) {
        setImportant(input, 'width', '104px');
        setImportant(input, 'height', '54px');
        setImportant(input, 'min-height', '54px');
        setImportant(input, 'margin', '0');
        setImportant(input, 'box-sizing', 'border-box');
      }

      setImportant(button, 'display', 'inline-flex');
      setImportant(button, 'align-items', 'center');
      setImportant(button, 'justify-content', 'center');
      setImportant(button, 'flex', '1 1 auto');
      setImportant(button, 'width', 'auto');
      setImportant(button, 'height', '54px');
      setImportant(button, 'min-height', '54px');
      setImportant(button, 'margin', '0');
      setImportant(button, 'box-sizing', 'border-box');
    });
  }

  function applyFixes() {
    alignProductColumns();
    alignCartControls();
  }

  document.addEventListener('DOMContentLoaded', function () {
    applyFixes();
    window.setTimeout(applyFixes, 250);
    window.setTimeout(applyFixes, 1000);
  });

  window.addEventListener('load', applyFixes);
  window.addEventListener('resize', applyFixes);

  if (window.jQuery) {
    window.jQuery(document.body).on('found_variation reset_data wc_fragments_loaded', applyFixes);
  }
}());
