document.addEventListener('DOMContentLoaded',()=>{
  const catalogRoot=document.querySelector('[data-cg-catalog-template="custom-v1"],.cg-custom-catalog');
  if(!catalogRoot) return;

  let requestController=null;
  let submitTimer=null;
  const currency=new Intl.NumberFormat('ru-RU',{style:'currency',currency:'RUB',maximumFractionDigits:0});

  const buildUrl=(form)=>{
    const action=new URL(form.action||window.location.href,window.location.origin);
    const params=new URLSearchParams();
    for(const [key,value] of new FormData(form).entries()){
      if(value==='') continue;
      params.append(key,String(value));
    }
    action.search=params.toString();
    return action;
  };

  const setLoading=(loading)=>{
    document.documentElement.classList.toggle('cg-catalog-is-loading',loading);
    const content=document.querySelector('.cg-shop-content');
    if(content) content.setAttribute('aria-busy',loading?'true':'false');
  };

  const replaceCatalog=(html,url)=>{
    const doc=new DOMParser().parseFromString(html,'text/html');
    const nextShell=doc.querySelector('.cg-shop-shell');
    const currentShell=document.querySelector('.cg-shop-shell');
    if(!nextShell||!currentShell) throw new Error('Catalog shell not found');
    currentShell.replaceWith(nextShell);
    window.history.pushState({},'',url);
    bindCatalog();
  };

  const applyForm=(form,delay=0)=>{
    window.clearTimeout(submitTimer);
    submitTimer=window.setTimeout(async()=>{
      const url=buildUrl(form);
      requestController?.abort();
      requestController=new AbortController();
      setLoading(true);
      try{
        const response=await fetch(url.toString(),{
          credentials:'same-origin',
          headers:{'X-Requested-With':'XMLHttpRequest'},
          signal:requestController.signal
        });
        if(!response.ok) throw new Error(`HTTP ${response.status}`);
        replaceCatalog(await response.text(),url.toString());
      }catch(error){
        if(error.name!=='AbortError') window.location.assign(url.toString());
      }finally{
        setLoading(false);
      }
    },delay);
  };

  const enhanceAccordion=(section,index)=>{
    if(section.classList.contains('cg-filter-section')) return section;
    const heading=section.querySelector(':scope > h3,:scope > .cg-catalog-filter-title');
    if(!heading) return section;

    const body=document.createElement('div');
    body.className='cg-filter-section__body';
    body.dataset.cgFilterBody='';
    const children=[...section.children].filter(child=>child!==heading);
    children.forEach(child=>body.appendChild(child));

    const button=document.createElement('button');
    button.type='button';
    button.className='cg-filter-section__toggle';
    button.dataset.cgFilterToggle='';
    button.setAttribute('aria-expanded',index===0?'true':'false');
    button.innerHTML=`<span>${heading.textContent.trim()}</span><b aria-hidden="true">⌄</b>`;

    heading.replaceWith(button);
    section.appendChild(body);
    section.classList.add('cg-filter-section');
    if(index===0) section.classList.add('is-open');
    else body.hidden=true;
    return section;
  };

  const bindAccordion=(section)=>{
    const button=section.querySelector(':scope > [data-cg-filter-toggle]');
    const body=section.querySelector(':scope > [data-cg-filter-body]');
    if(!button||!body||button.dataset.cgBound==='1') return;
    button.dataset.cgBound='1';
    button.addEventListener('click',()=>{
      const open=section.classList.toggle('is-open');
      button.setAttribute('aria-expanded',open?'true':'false');
      body.hidden=!open;
    });
  };

  const bindCatalog=()=>{
    const panel=document.querySelector('#cg-catalog-sidebar');
    const mobileToggle=document.querySelector('.cg-catalog-filter-toggle');
    const form=panel?.querySelector('.cg-catalog-filter-form');

    if(mobileToggle&&mobileToggle.dataset.cgBound!=='1'){
      mobileToggle.dataset.cgBound='1';
      mobileToggle.addEventListener('click',()=>{
        const open=panel?.classList.toggle('is-open');
        mobileToggle.setAttribute('aria-expanded',open?'true':'false');
        mobileToggle.textContent=open?'Скрыть фильтры':'Фильтры и категории';
      });
    }

    panel?.querySelectorAll('.cg-catalog-categories,.cg-catalog-attribute-filter').forEach((section,index)=>{
      bindAccordion(enhanceAccordion(section,index));
    });

    if(!form) return;
    const minRange=form.querySelector('.cg-catalog-range--min');
    const maxRange=form.querySelector('.cg-catalog-range--max');
    const minInput=form.querySelector('[name="min_price"]');
    const maxInput=form.querySelector('[name="max_price"]');
    const minLabel=form.querySelector('[data-cg-price-min-label]');
    const maxLabel=form.querySelector('[data-cg-price-max-label]');
    const slider=form.querySelector('.cg-catalog-price-slider');

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
    minRange?.addEventListener('change',()=>applyForm(form,180));
    maxRange?.addEventListener('change',()=>applyForm(form,180));

    form.querySelectorAll('input[type="checkbox"],input[type="radio"]').forEach(input=>{
      input.addEventListener('change',()=>applyForm(form,80));
    });

    form.addEventListener('submit',(event)=>{
      event.preventDefault();
      applyForm(form,0);
    });

    const ordering=document.querySelector('.cg-catalog-ordering');
    if(ordering&&ordering.dataset.cgBound!=='1'){
      ordering.dataset.cgBound='1';
      ordering.addEventListener('submit',(event)=>{
        event.preventDefault();
        applyForm(ordering,0);
      });
      ordering.querySelector('select')?.addEventListener('change',()=>applyForm(ordering,0));
    }

    syncSlider();
  };

  window.addEventListener('popstate',()=>window.location.reload());
  bindCatalog();
});