document.addEventListener('DOMContentLoaded',()=>{
  const shell=document.querySelector('.cg-shop-content');
  const form=document.querySelector('.cg-filter-form');
  if(!shell||!form) return;

  const products=()=>shell.querySelector('ul.products');
  const category=form.querySelector('[name="cg_category"]');
  const minPrice=form.querySelector('[name="cg_min_price"]');
  const maxPrice=form.querySelector('[name="cg_max_price"]');
  const inStock=form.querySelector('[name="cg_in_stock"]');
  const onSale=form.querySelector('[name="cg_on_sale"]');
  const ordering=document.querySelector('.woocommerce-ordering select');
  const reset=form.querySelector('.cg-filter-reset');
  const mobileToggle=document.querySelector('.cg-modern-filters__mobile-toggle');
  const panel=document.querySelector('#cg-modern-filters');
  let page=1;
  let controller;

  mobileToggle?.addEventListener('click',()=>{
    const open=panel?.classList.toggle('is-open');
    mobileToggle.setAttribute('aria-expanded',open?'true':'false');
    mobileToggle.textContent=open?'Скрыть фильтры':'Фильтры и категории';
  });

  if(typeof cgAjaxCatalog==='undefined') return;

  const setBusy=(busy)=>{
    shell.classList.toggle('is-loading',busy);
    shell.setAttribute('aria-busy',busy?'true':'false');
  };

  const load=async()=>{
    controller?.abort();
    controller=new AbortController();
    setBusy(true);

    const body=new URLSearchParams({
      action:'cg_filter_products',
      nonce:cgAjaxCatalog.nonce,
      page:String(page),
      category:category?.value||'',
      min_price:minPrice?.value||'',
      max_price:maxPrice?.value||'',
      in_stock:inStock?.checked?'1':'',
      on_sale:onSale?.checked?'1':'',
      orderby:ordering?.value||'menu_order'
    });

    try{
      const response=await fetch(cgAjaxCatalog.ajaxUrl,{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
        body,
        signal:controller.signal
      });
      if(!response.ok) throw new Error('http');
      const json=await response.json();
      if(!json.success) throw new Error('catalog');

      const current=products();
      if(current) current.outerHTML=json.data.products;
      else shell.insertAdjacentHTML('beforeend',json.data.products);
      shell.querySelector('.cg-ajax-pagination')?.remove();
      shell.insertAdjacentHTML('beforeend',json.data.pagination||'');
      const count=document.querySelector('.woocommerce-result-count');
      if(count) count.textContent=`Найдено товаров: ${json.data.found}`;
      shell.scrollIntoView({behavior:'smooth',block:'start'});
    }catch(error){
      if(error.name!=='AbortError') form.submit();
    }finally{
      setBusy(false);
    }
  };

  form.addEventListener('submit',(event)=>{
    event.preventDefault();
    page=1;
    load();
  });

  ordering?.addEventListener('change',(event)=>{
    event.preventDefault();
    page=1;
    load();
  });

  reset?.addEventListener('click',(event)=>{
    if(!event.ctrlKey&&!event.metaKey){
      event.preventDefault();
      if(category) category.value='';
      if(minPrice) minPrice.value='';
      if(maxPrice) maxPrice.value='';
      if(inStock) inStock.checked=false;
      if(onSale) onSale.checked=false;
      if(ordering) ordering.value='menu_order';
      page=1;
      load();
    }
  });

  shell.addEventListener('click',(event)=>{
    const pageButton=event.target.closest('.cg-page-button');
    if(pageButton){
      page=Number(pageButton.dataset.page||1);
      load();
    }
  });
});
