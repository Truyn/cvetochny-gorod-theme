(function () {
  'use strict';

  function initMobileBuyBar() {
    var bar = document.querySelector('[data-cg-mobile-buybar]');
    if (!bar) return;

    var button = bar.querySelector('[data-cg-mobile-buy-button]');
    var price = bar.querySelector('.cg-mobile-buybar__price strong');
    var form = document.querySelector('form.cart');
    var nativeButton = form ? form.querySelector('.single_add_to_cart_button') : null;
    var isSimple = bar.getAttribute('data-simple') === '1';
    var defaultPriceHtml = price ? price.innerHTML : '';
    var variationReady = false;
    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    if (!button || !form || !nativeButton) return;

    function nativeButtonIsVisible() {
      var rect = nativeButton.getBoundingClientRect();
      return rect.bottom > 72 && rect.top < window.innerHeight - 16;
    }

    function nativeButtonEnabled() {
      return !nativeButton.disabled && !nativeButton.classList.contains('disabled') && !nativeButton.classList.contains('wc-variation-selection-needed');
    }

    function updateButtonState() {
      if (isSimple) {
        button.disabled = !nativeButtonEnabled();
        button.textContent = nativeButtonEnabled() ? 'В корзину' : 'Недоступно';
        return;
      }

      if (variationReady && nativeButtonEnabled()) {
        button.disabled = false;
        button.textContent = 'В корзину';
      } else {
        button.disabled = false;
        button.textContent = 'Выбрать';
      }
    }

    function updateBar() {
      var mobile = window.matchMedia('(max-width: 720px)').matches;
      var show = mobile && !nativeButtonIsVisible();
      bar.classList.toggle('is-visible', show);
      bar.setAttribute('aria-hidden', show ? 'false' : 'true');
      updateButtonState();
    }

    function scrollToForm() {
      form.scrollIntoView({ behavior: reducedMotion.matches ? 'auto' : 'smooth', block: 'center' });
      window.setTimeout(function () {
        var firstChoice = form.querySelector('select, input[type="radio"], input[type="checkbox"], .single_add_to_cart_button');
        if (firstChoice && firstChoice.focus) firstChoice.focus({ preventScroll: true });
      }, reducedMotion.matches ? 0 : 350);
    }

    function onFoundVariation(variation) {
      variationReady = !!variation && variation.is_purchasable !== false && variation.is_in_stock !== false;

      if (price && variation && variation.price_html) {
        price.innerHTML = variation.price_html;
      }

      updateBar();
    }

    function onResetVariation() {
      variationReady = false;
      if (price) price.innerHTML = defaultPriceHtml;
      updateBar();
    }

    button.addEventListener('click', function () {
      if ((isSimple || variationReady) && nativeButtonEnabled()) {
        nativeButton.click();
        return;
      }
      scrollToForm();
    });

    window.addEventListener('scroll', updateBar, { passive: true });
    window.addEventListener('resize', updateBar);

    if (window.jQuery) {
      window.jQuery(form).on('found_variation', function (event, variation) {
        onFoundVariation(variation);
      });
      window.jQuery(form).on('reset_data hide_variation', onResetVariation);
    }

    var observer = new MutationObserver(updateBar);
    observer.observe(nativeButton, { attributes: true, attributeFilter: ['class', 'disabled'] });

    updateBar();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMobileBuyBar);
  } else {
    initMobileBuyBar();
  }
})();
