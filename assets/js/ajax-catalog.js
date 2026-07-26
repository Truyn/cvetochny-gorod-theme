document.addEventListener('DOMContentLoaded',()=>{
  const panel=document.querySelector('#cg-catalog-sidebar');
  const toggle=document.querySelector('.cg-catalog-filter-toggle');
  const form=panel?.querySelector('.cg-catalog-filter-form');

  toggle?.addEventListener('click',()=>{
    const open=panel?.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded',open?'true':'false');
    toggle.textContent=open?'Скрыть фильтры':'Фильтры и категории';
  });

  if(!form) return;

  const minRange=form.querySelector('.cg-catalog-range--min');
  const maxRange=form.querySelector('.cg-catalog-range--max');
  const minInput=form.querySelector('[name="min_price"]');
  const maxInput=form.querySelector('[name="max_price"]');
  const minLabel=form.querySelector('[data-cg-price-min-label]');
  const maxLabel=form.querySelector('[data-cg-price-max-label]');
  const slider=form.querySelector('.cg-catalog-price-slider');
  const currency=new Intl.NumberFormat('ru-RU',{style:'currency',currency:'RUB',maximumFractionDigits:0});
  let submitTimer;

  const submitForm=(delay=0)=>{
    window.clearTimeout(submitTimer);
    submitTimer=window.setTimeout(()=>form.requestSubmit(),delay);
  };

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

    if(minLabel) minLabel.textContent=currency.format(min);
    if(maxLabel) maxLabel.textContent=currency.format(max);

    const span=Math.max(1,ceiling-floor);
    slider.style.setProperty('--min-pos',`${((min-floor)/span)*100}%`);
    slider.style.setProperty('--max-pos',`${((max-floor)/span)*100}%`);
  };

  minRange?.addEventListener('input',()=>syncSlider(minRange));
  maxRange?.addEventListener('input',()=>syncSlider(maxRange));
  minRange?.addEventListener('change',()=>submitForm(250));
  maxRange?.addEventListener('change',()=>submitForm(250));

  form.querySelectorAll('input[type="checkbox"],input[type="radio"]').forEach(input=>{
    input.addEventListener('change',()=>submitForm(120));
  });

  syncSlider();
});
