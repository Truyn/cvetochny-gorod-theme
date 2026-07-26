document.addEventListener('DOMContentLoaded',()=>{
  const panel=document.querySelector('#cg-modern-filters');
  const toggle=document.querySelector('.cg-modern-filters__mobile-toggle');
  const form=panel?.querySelector('.cg-filter-form');

  toggle?.addEventListener('click',()=>{
    const open=panel?.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded',open?'true':'false');
    toggle.textContent=open?'Скрыть фильтры':'Фильтры и категории';
  });

  if(!form) return;

  const minRange=form.querySelector('.cg-price-range--min');
  const maxRange=form.querySelector('.cg-price-range--max');
  const minInput=form.querySelector('[name="cg_min_price"]');
  const maxInput=form.querySelector('[name="cg_max_price"]');
  const minOutput=form.querySelector('[data-cg-min-output]');
  const maxOutput=form.querySelector('[data-cg-max-output]');
  const slider=form.querySelector('.cg-price-slider');
  const currency=new Intl.NumberFormat('ru-RU',{style:'currency',currency:'RUB',maximumFractionDigits:0});

  const syncSlider=(changed)=>{
    if(!minRange||!maxRange||!minInput||!maxInput||!slider) return;
    const floor=Number(minRange.min);
    const ceiling=Number(minRange.max);
    const step=Number(minRange.step)||1;
    let min=Number(minRange.value);
    let max=Number(maxRange.value);

    if(min>max-step){
      if(changed===minRange) min=Math.max(floor,max-step);
      else max=Math.min(ceiling,min+step);
    }

    minRange.value=String(min);
    maxRange.value=String(max);
    minInput.value=String(min);
    maxInput.value=String(max);
    if(minOutput) minOutput.textContent=currency.format(min);
    if(maxOutput) maxOutput.textContent=currency.format(max);

    const span=Math.max(1,ceiling-floor);
    slider.style.setProperty('--min-pos',`${((min-floor)/span)*100}%`);
    slider.style.setProperty('--max-pos',`${((max-floor)/span)*100}%`);
  };

  minRange?.addEventListener('input',()=>syncSlider(minRange));
  maxRange?.addEventListener('input',()=>syncSlider(maxRange));
  syncSlider();

  form.addEventListener('submit',(event)=>{
    event.preventDefault();
    syncSlider();

    const params=new URLSearchParams();
    const pageId=form.querySelector('[name="page_id"]')?.value;
    const category=form.querySelector('[name="cg_category"]')?.value;
    const inStock=form.querySelector('[name="cg_in_stock"]')?.checked;
    const onSale=form.querySelector('[name="cg_on_sale"]')?.checked;

    if(pageId) params.set('page_id',pageId);
    if(category) params.set('cg_category',category);
    if(minInput?.value) params.set('cg_min_price',minInput.value);
    if(maxInput?.value) params.set('cg_max_price',maxInput.value);
    if(inStock) params.set('cg_in_stock','1');
    if(onSale) params.set('cg_on_sale','1');

    const orderby=document.querySelector('.woocommerce-ordering select')?.value;
    if(orderby&&orderby!=='menu_order') params.set('orderby',orderby);

    const button=form.querySelector('.cg-filter-apply');
    button?.setAttribute('aria-busy','true');
    window.location.assign(`${window.location.origin}/?${params.toString()}`);
  });
});