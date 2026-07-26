document.addEventListener('DOMContentLoaded',()=>{
  const content=document.querySelector('.cg-shop-content');
  const panel=document.querySelector('#cg-modern-filters');
  const toggle=document.querySelector('.cg-modern-filters__mobile-toggle');
  const shell=content?.closest('.cg-shop-shell');

  /* The filter panel is rendered by a WooCommerce hook inside the content.
     Move it to the shell so it becomes a real left column, not a top block. */
  if(shell&&content&&panel){
    shell.insertBefore(panel,content);
    if(toggle) shell.insertBefore(toggle,panel);
    shell.classList.add('cg-shop-shell--filters-ready');
  }

  toggle?.addEventListener('click',()=>{
    const open=panel?.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded',open?'true':'false');
    toggle.textContent=open?'Скрыть фильтры':'Фильтры и категории';
  });

  const form=panel?.querySelector('.cg-filter-form');
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

  /* Deliberately use a normal GET submit. It is more reliable with WooCommerce,
     keeps pagination/sorting compatible and gives shareable filter URLs. */
  form.addEventListener('submit',()=>{
    form.querySelector('.cg-filter-apply')?.setAttribute('aria-busy','true');
  });
});
