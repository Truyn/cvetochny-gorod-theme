document.addEventListener('DOMContentLoaded',()=>{
  const root=document.querySelector('[data-cg-catalog-template="server-ajax-v2"]');
  const form=root?.querySelector('[data-cg-catalog-form]');
  const results=root?.querySelector('#cg-catalog-results');
  if(!root||!form||!results||typeof cgCatalog==='undefined') return;

  const currency=new Intl.NumberFormat('ru-RU',{style:'currency',currency:'RUB',maximumFractionDigits:0});
  let controller=null;
  let timer=null;
  const minRange=form.querySelector('.cg-catalog-range--min');
  const maxRange=form.querySelector('.cg-catalog-range--max');
  const minInput=form.querySelector('[name="min_price"]');
  const maxInput=form.querySelector('[name="max_price"]');
  const minLabel=form.querySelector('[data-cg-price-min-label]');
  const maxLabel=form.querySelector('[data-cg-price-max-label]');
  const slider=form.querySelector('.cg-catalog-price-slider');

  const syncSlider=(changed)=>{
    if(!minRange||!maxRange||!minInput||!maxInput||!slider) return;
    const floor=Number(minRange.min),ceiling=Number(minRange.max),step=Number(minRange.step)||1;
    let min=Number(minRange.value),max=Number(maxRange.value);
    if(min>max-step){if(changed===minRange)min=Math.max(floor,max-step);else max=Math.min(ceiling,min+step);}
    minRange.value=String(min);maxRange.value=String(max);minInput.value=String(min);maxInput.value=String(max);
    if(minLabel)minLabel.textContent=currency.format(min);if(maxLabel)maxLabel.textContent=currency.format(max);
    const span=Math.max(1,ceiling-floor);slider.style.setProperty('--min-pos',`${((min-floor)/span)*100}%`);slider.style.setProperty('--max-pos',`${((max-floor)/span)*100}%`);
  };
  const serialize=()=>{const data=new FormData(form),params=new URLSearchParams();for(const[key,value]of data.entries())if(value!==''&&key!=='page_id')params.append(key,String(value));return params;};
  const setLoading=(loading)=>{root.classList.toggle('is-loading',loading);results.setAttribute('aria-busy',loading?'true':'false');form.querySelectorAll('input:not([type="search"]),button[type="submit"]').forEach(el=>{el.disabled=loading;});};
  const updateActiveCategory=()=>form.querySelectorAll('.cg-catalog-category-option').forEach(label=>label.classList.toggle('is-active',Boolean(label.querySelector('input:checked'))));

  const request=(paged=1,historyMode='push')=>{
    window.clearTimeout(timer);timer=window.setTimeout(async()=>{
      controller?.abort();controller=new AbortController();const filters=serialize();
      const body=new URLSearchParams({action:'cg_catalog_filter',nonce:cgCatalog.nonce,filters:filters.toString(),paged:String(paged)});setLoading(true);
      try{
        const response=await fetch(cgCatalog.ajaxUrl,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:body.toString(),signal:controller.signal});
        const payload=await response.json();if(!response.ok||!payload.success||!payload.data?.html)throw new Error('Invalid AJAX response');
        results.innerHTML=payload.data.html;const url=payload.data.url||`${cgCatalog.shopUrl}?${filters.toString()}`;window.history[historyMode==='replace'?'replaceState':'pushState']({},'',url);bindResults();updateActiveCategory();
      }catch(error){if(error.name!=='AbortError')results.insertAdjacentHTML('afterbegin',`<div class="cg-catalog-error" role="alert">${cgCatalog.errorText}</div>`);}finally{setLoading(false);}
    },0);
  };
  const schedule=(delay=100)=>{window.clearTimeout(timer);timer=window.setTimeout(()=>request(1),delay);};
  const applyUrlToForm=(url)=>{const params=new URL(url,window.location.origin).searchParams;form.reset();form.querySelectorAll('input,select').forEach(input=>{if(!input.name)return;const values=params.getAll(input.name);if(input.type==='checkbox'||input.type==='radio')input.checked=values.includes(input.value);else if(values.length)input.value=values[0];});if(minRange&&minInput)minRange.value=minInput.value;if(maxRange&&maxInput)maxRange.value=maxInput.value;syncSlider();updateActiveCategory();};
  const bindResults=()=>{
    results.querySelector('[data-cg-orderby]')?.addEventListener('change',event=>{const hidden=form.querySelector('[name="cg_orderby"]');if(hidden)hidden.value=event.target.value;request(1);});
    results.querySelectorAll('[data-cg-filter-link],[data-cg-reset]').forEach(link=>link.addEventListener('click',event=>{event.preventDefault();applyUrlToForm(link.href);request(1);}));
    results.querySelectorAll('.woocommerce-pagination a').forEach(link=>link.addEventListener('click',event=>{event.preventDefault();const page=Number(new URL(link.href,window.location.origin).searchParams.get('paged'))||1;request(page);root.scrollIntoView({behavior:'smooth',block:'start'});}));
  };

  root.querySelector('.cg-catalog-filter-toggle')?.addEventListener('click',event=>{const sidebar=root.querySelector('#cg-catalog-sidebar');const open=sidebar.classList.toggle('is-open');event.currentTarget.setAttribute('aria-expanded',open?'true':'false');event.currentTarget.textContent=open?'Скрыть фильтры':'Фильтры и категории';});

  form.querySelectorAll('.cg-category-toggle').forEach(button=>button.addEventListener('click',event=>{
    event.preventDefault();event.stopPropagation();const node=button.closest('.cg-category-node');const children=node?.querySelector(':scope > .cg-category-children');if(!children)return;const open=node.classList.toggle('is-open');children.hidden=!open;button.setAttribute('aria-expanded',open?'true':'false');
  }));

  form.querySelectorAll('.cg-filter-group').forEach(group=>group.addEventListener('toggle',()=>{
    if(!group.open)return;form.querySelectorAll('.cg-filter-group[open]').forEach(other=>{if(other!==group&&!other.querySelector('input:checked'))other.open=false;});
  }));

  form.querySelectorAll('.cg-filter-search').forEach(search=>search.addEventListener('input',()=>{
    const query=search.value.trim().toLocaleLowerCase('ru-RU');const list=search.parentElement?.querySelector('.cg-catalog-attribute-list');list?.querySelectorAll('[data-cg-filter-item]').forEach(item=>{item.hidden=query!==''&&!item.dataset.cgFilterItem.includes(query);});
  }));

  minRange?.addEventListener('input',()=>syncSlider(minRange));maxRange?.addEventListener('input',()=>syncSlider(maxRange));minRange?.addEventListener('change',()=>schedule(250));maxRange?.addEventListener('change',()=>schedule(250));
  form.querySelectorAll('input[type="checkbox"],input[type="radio"]').forEach(input=>input.addEventListener('change',()=>{updateActiveCategory();schedule(80);}));
  form.addEventListener('submit',event=>{event.preventDefault();request(1);});
  form.querySelector('[data-cg-reset]')?.addEventListener('click',event=>{event.preventDefault();applyUrlToForm(cgCatalog.shopUrl);request(1);});
  window.addEventListener('popstate',()=>{applyUrlToForm(window.location.href);request(Number(new URL(window.location.href).searchParams.get('paged'))||1,'replace');});
  syncSlider();updateActiveCategory();bindResults();
});