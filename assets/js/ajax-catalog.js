document.addEventListener('DOMContentLoaded', () => {
  if (typeof cgCatalog === 'undefined') return;

  document.querySelectorAll('[data-cg-catalog-template="server-ajax-v2"]').forEach((root) => {
    if (root.dataset.cgCatalogReady === '1') return;
    root.dataset.cgCatalogReady = '1';

    const form = root.querySelector('[data-cg-catalog-form]');
    const results = root.querySelector('#cg-catalog-results');
    const sidebar = root.querySelector('#cg-catalog-sidebar');
    const toggle = root.querySelector('.cg-catalog-filter-toggle');
    const backdrop = root.querySelector('.cg-catalog-filter-backdrop');

    if (!form || !results || !sidebar) return;

    const currency = new Intl.NumberFormat('ru-RU', {
      style: 'currency',
      currency: 'RUB',
      maximumFractionDigits: 0,
    });

    let controller = null;
    let timer = null;
    let requestSerial = 0;

    const minRange = form.querySelector('.cg-catalog-range--min');
    const maxRange = form.querySelector('.cg-catalog-range--max');
    const minInput = form.querySelector('[name="min_price"]');
    const maxInput = form.querySelector('[name="max_price"]');
    const minLabel = form.querySelector('[data-cg-price-min-label]');
    const maxLabel = form.querySelector('[data-cg-price-max-label]');
    const slider = form.querySelector('.cg-catalog-price-slider');
    const submitLabel = form.querySelector('[data-cg-filter-submit-label]');
    const filterCountBadges = root.querySelectorAll('[data-cg-filter-count]');
    const productSearch = form.querySelector('[name="catalog_search"]');

    const selectedCategoryInput = () => form.querySelector('input[name="product_cat"]:checked');
    const selectedCategory = () => selectedCategoryInput()?.value || '';
    const selectedCategoryId = () => selectedCategoryInput()?.dataset.categoryId || '0';

    const getValues = (params, fieldName) => {
      if (!fieldName.endsWith('[]')) return params.getAll(fieldName);
      const base = fieldName.slice(0, -2);
      const values = [];
      params.forEach((value, key) => {
        if (key === fieldName || key === base || key.startsWith(`${base}[`)) values.push(value);
      });
      return values;
    };

    const syncSlider = (changed = null) => {
      if (!minRange || !maxRange || !minInput || !maxInput || !slider) return;

      const floor = Number(minRange.min);
      const ceiling = Number(minRange.max);
      const step = Number(minRange.step) || 1;
      let min = Number(minRange.value);
      let max = Number(maxRange.value);

      if (min > max - step) {
        if (changed === minRange) min = Math.max(floor, max - step);
        else max = Math.min(ceiling, min + step);
      }

      minRange.value = String(min);
      maxRange.value = String(max);
      minInput.value = String(min);
      maxInput.value = String(max);
      if (minLabel) minLabel.textContent = currency.format(min);
      if (maxLabel) maxLabel.textContent = currency.format(max);

      const span = Math.max(1, ceiling - floor);
      slider.style.setProperty('--min-pos', `${((min - floor) / span) * 100}%`);
      slider.style.setProperty('--max-pos', `${((max - floor) / span) * 100}%`);

      form.querySelectorAll('[data-cg-price-preset]').forEach((button) => {
        button.classList.toggle(
          'is-active',
          Number(button.dataset.min) === min && Number(button.dataset.max) === max,
        );
      });
    };

    const getActiveFilterCount = () => {
      let count = 0;
      if (Number(selectedCategoryId()) > 0) count += 1;
      if (productSearch?.value.trim()) count += 1;

      if (minRange && maxRange) {
        if (Number(minRange.value) > Number(minRange.min) || Number(maxRange.value) < Number(maxRange.max)) count += 1;
      }

      form.querySelectorAll('input[type="checkbox"]:checked').forEach(() => { count += 1; });
      return count;
    };

    const updateFilterCount = (provided = null) => {
      const hasProvided = provided !== null && provided !== undefined && Number.isFinite(Number(provided));
      const count = hasProvided ? Number(provided) : getActiveFilterCount();
      filterCountBadges.forEach((badge) => {
        badge.textContent = String(count);
        badge.hidden = count < 1;
      });
    };

    const serialize = () => {
      const data = new FormData(form);
      const params = new URLSearchParams();
      const floor = minRange ? Number(minRange.min) : null;
      const ceiling = maxRange ? Number(maxRange.max) : null;

      for (const [key, value] of data.entries()) {
        if (value === '' || key === 'page_id' || key === 'product_cat' || key === 'product_cat_id') continue;
        if (key === 'min_price' && floor !== null && Number(value) <= floor) continue;
        if (key === 'max_price' && ceiling !== null && Number(value) >= ceiling) continue;
        params.append(key, String(value));
      }

      const category = selectedCategory();
      const categoryId = selectedCategoryId();
      if (category && Number(categoryId) > 0) {
        params.set('product_cat', category);
        params.set('product_cat_id', categoryId);
      }

      return params;
    };

    const setLoading = (loading) => {
      root.classList.toggle('is-loading', loading);
      results.setAttribute('aria-busy', loading ? 'true' : 'false');
      const submit = form.querySelector('button[type="submit"]');
      if (submit) submit.disabled = loading;
      if (loading && submitLabel) submitLabel.textContent = 'Подбираем товары…';
    };

    const updateActiveCategory = () => {
      form.querySelectorAll('.cg-catalog-category-option').forEach((label) => {
        label.classList.toggle('is-active', Boolean(label.querySelector('input:checked')));
      });
    };

    const productWord = (number) => {
      const value = Math.abs(Number(number)) % 100;
      const last = value % 10;
      if (value > 10 && value < 20) return 'товаров';
      if (last === 1) return 'товар';
      if (last > 1 && last < 5) return 'товара';
      return 'товаров';
    };

    const updateSubmitLabel = (total = null) => {
      if (!submitLabel) return;
      const number = Number(total);
      submitLabel.textContent = Number.isFinite(number)
        ? `Показать ${number} ${productWord(number)}`
        : 'Показать товары';
    };

    const closeDrawer = () => {
      sidebar.classList.remove('is-open');
      toggle?.setAttribute('aria-expanded', 'false');
      if (backdrop) backdrop.hidden = true;
      document.body.classList.remove('cg-filter-drawer-open');
    };

    const openDrawer = () => {
      sidebar.classList.add('is-open');
      toggle?.setAttribute('aria-expanded', 'true');
      if (backdrop) backdrop.hidden = false;
      document.body.classList.add('cg-filter-drawer-open');
    };

    const request = async (paged = 1, historyMode = 'push') => {
      window.clearTimeout(timer);
      controller?.abort();
      controller = new AbortController();

      const serial = ++requestSerial;
      const filters = serialize();
      const body = new URLSearchParams({
        action: 'cg_catalog_filter',
        nonce: cgCatalog.nonce,
        filters: filters.toString(),
        category: selectedCategory(),
        category_id: selectedCategoryId(),
        paged: String(paged),
      });

      setLoading(true);
      try {
        const response = await fetch(cgCatalog.ajaxUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
          body: body.toString(),
          signal: controller.signal,
        });
        const payload = await response.json();
        if (!response.ok || !payload.success || !payload.data?.html) throw new Error('Invalid AJAX response');
        if (serial !== requestSerial) return;

        results.innerHTML = payload.data.html;
        const url = payload.data.url || `${cgCatalog.shopUrl}?${filters.toString()}`;
        window.history[historyMode === 'replace' ? 'replaceState' : 'pushState']({}, '', url);
        bindResults();
        updateActiveCategory();
        updateFilterCount(payload.data.filterCount);
        updateSubmitLabel(payload.data.total);
      } catch (error) {
        if (error.name !== 'AbortError' && serial === requestSerial) {
          results.insertAdjacentHTML('afterbegin', `<div class="cg-catalog-error" role="alert">${cgCatalog.errorText}</div>`);
          updateSubmitLabel();
        }
      } finally {
        if (serial === requestSerial) setLoading(false);
      }
    };

    const schedule = (delay = 180) => {
      window.clearTimeout(timer);
      timer = window.setTimeout(() => request(1), delay);
    };

    const applyUrlToForm = (url) => {
      const params = new URL(url, window.location.origin).searchParams;
      const categoryId = params.get('product_cat_id');
      const categorySlug = params.get('product_cat');

      form.querySelectorAll('input,select').forEach((input) => {
        if (!input.name) return;

        if (input.type === 'radio' && input.name === 'product_cat') {
          input.checked = categoryId
            ? input.dataset.categoryId === categoryId
            : categorySlug
              ? input.value === categorySlug
              : input.value === '';
          return;
        }

        const values = getValues(params, input.name);
        if (input.type === 'checkbox' || input.type === 'radio') {
          input.checked = values.includes(input.value);
        } else if (input.type !== 'range') {
          input.value = values.length ? values[0] : '';
        }
      });

      if (minRange && minInput) {
        minInput.value = params.get('min_price') || minRange.min;
        minRange.value = minInput.value;
      }
      if (maxRange && maxInput) {
        maxInput.value = params.get('max_price') || maxRange.max;
        maxRange.value = maxInput.value;
      }

      syncSlider();
      updateActiveCategory();
      updateFilterCount();
    };

    const bindResults = () => {
      results.querySelector('[data-cg-orderby]')?.addEventListener('change', (event) => {
        const hidden = form.querySelector('[name="cg_orderby"]');
        if (hidden) hidden.value = event.target.value;
        request(1);
      });

      results.querySelectorAll('[data-cg-filter-link],[data-cg-reset]').forEach((link) => {
        link.addEventListener('click', (event) => {
          event.preventDefault();
          applyUrlToForm(link.href);
          request(1);
        });
      });

      results.querySelectorAll('.woocommerce-pagination a').forEach((link) => {
        link.addEventListener('click', (event) => {
          event.preventDefault();
          const page = Number(new URL(link.href, window.location.origin).searchParams.get('paged')) || 1;
          request(page);
          root.scrollIntoView({behavior: 'smooth', block: 'start'});
        });
      });
    };

    toggle?.addEventListener('click', () => {
      if (sidebar.classList.contains('is-open')) closeDrawer();
      else openDrawer();
    });

    root.querySelectorAll('[data-cg-filter-close]').forEach((element) => {
      element.addEventListener('click', closeDrawer);
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && sidebar.classList.contains('is-open')) closeDrawer();
    });

    const desktopMedia = window.matchMedia('(min-width: 981px)');
    const closeOnDesktop = (event) => {
      if (event.matches) closeDrawer();
    };
    if (typeof desktopMedia.addEventListener === 'function') desktopMedia.addEventListener('change', closeOnDesktop);
    else if (typeof desktopMedia.addListener === 'function') desktopMedia.addListener(closeOnDesktop);

    form.querySelectorAll('.cg-category-toggle').forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        const node = button.closest('.cg-category-node');
        const children = node?.querySelector(':scope > .cg-category-children');
        if (!children) return;
        const open = node.classList.toggle('is-open');
        children.hidden = !open;
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    });

    form.querySelectorAll('.cg-filter-search').forEach((search) => {
      search.addEventListener('input', () => {
        const query = search.value.trim().toLocaleLowerCase('ru-RU');
        const list = search.parentElement?.querySelector('.cg-catalog-attribute-list');
        list?.querySelectorAll('[data-cg-filter-item]').forEach((item) => {
          item.hidden = query !== '' && !item.dataset.cgFilterItem.includes(query);
        });
      });
    });

    form.querySelectorAll('[data-cg-price-preset]').forEach((button) => {
      button.addEventListener('click', () => {
        if (!minRange || !maxRange) return;
        minRange.value = button.dataset.min;
        maxRange.value = button.dataset.max;
        syncSlider();
        updateFilterCount();
        schedule(120);
      });
    });

    minRange?.addEventListener('input', () => {
      syncSlider(minRange);
      updateFilterCount();
    });
    maxRange?.addEventListener('input', () => {
      syncSlider(maxRange);
      updateFilterCount();
    });
    minRange?.addEventListener('change', () => schedule(260));
    maxRange?.addEventListener('change', () => schedule(260));

    productSearch?.addEventListener('input', () => {
      updateFilterCount();
      schedule(360);
    });

    // A direct click listener makes the category selector reliable even on
    // mobile browsers that occasionally delay or omit a radio change event.
    form.addEventListener('click', (event) => {
      const option = event.target.closest?.('.cg-catalog-category-option');
      if (!option) return;
      const input = option.querySelector('input[name="product_cat"]');
      if (!input) return;
      input.checked = true;
      updateActiveCategory();
      updateFilterCount();
      schedule(0);
    });

    form.addEventListener('change', (event) => {
      const input = event.target;
      if (!input || typeof input.matches !== 'function' || !input.matches('input')) return;

      updateActiveCategory();
      updateFilterCount();

      if (input.name === 'product_cat') {
        schedule(0);
        return;
      }

      if (input.type === 'checkbox' || input.type === 'radio') schedule(100);
    });

    form.addEventListener('submit', (event) => {
      event.preventDefault();
      request(1);
      if (window.matchMedia('(max-width: 980px)').matches) closeDrawer();
    });

    form.querySelector('[data-cg-reset]')?.addEventListener('click', (event) => {
      event.preventDefault();
      applyUrlToForm(cgCatalog.shopUrl);
      request(1);
    });

    window.addEventListener('popstate', () => {
      applyUrlToForm(window.location.href);
      request(Number(new URL(window.location.href).searchParams.get('paged')) || 1, 'replace');
    });

    syncSlider();
    updateActiveCategory();
    updateFilterCount();
    bindResults();

    const initialTotal = results.querySelector('[data-cg-result-total]')?.textContent;
    updateSubmitLabel(initialTotal ? Number(initialTotal) : null);
  });
});
