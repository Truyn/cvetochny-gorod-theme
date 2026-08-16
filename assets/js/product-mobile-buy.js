(function () {
  'use strict';

  function initMobileBuyBar() {
    var bar = document.querySelector('[data-cg-mobile-buybar]');
    if (!bar) return;

    var button = bar.querySelector('[data-cg-mobile-buy-button]');
    var form = document.querySelector('form.cart');
    var nativeButton = form ? form.querySelector('.single_add_to_cart_button') : null;
    var isSimple = bar.getAttribute('data-simple') === '1';

    if (!button || !form || !nativeButton) return;

    function nativeButtonIsVisible() {
      var rect = nativeButton.getBoundingClientRect();
      return rect.bottom > 72 && rect.top < window.innerHeight - 16;
    }

    function updateBar() {
      var mobile = window.matchMedia('(max-width: 720px)').matches;
      var show = mobile && !nativeButtonIsVisible();
      bar.classList.toggle('is-visible', show);
      bar.setAttribute('aria-hidden', show ? 'false' : 'true');
    }

    button.addEventListener('click', function () {
      if (isSimple && !nativeButton.disabled && !nativeButton.classList.contains('disabled')) {
        nativeButton.click();
        return;
      }

      form.scrollIntoView({ behavior: 'smooth', block: 'center' });
      if (nativeButton.focus) {
        window.setTimeout(function () { nativeButton.focus({ preventScroll: true }); }, 350);
      }
    });

    window.addEventListener('scroll', updateBar, { passive: true });
    window.addEventListener('resize', updateBar);
    document.addEventListener('found_variation', updateBar);
    document.addEventListener('reset_data', updateBar);
    updateBar();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMobileBuyBar);
  } else {
    initMobileBuyBar();
  }
})();
