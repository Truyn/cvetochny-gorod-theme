document.addEventListener('DOMContentLoaded',()=>{
  const shell=document.querySelector('.cg-shop-content');
  if(!shell||typeof cgAjaxCatalog==='undefined') return;

  const products=()=>shell.querySelector('ul.products');
  const category=document.querySelector('.cg-modern-filters [name="cg_category"]');
  const minPrice=document.querySelector('.cg-modern-filters [name="cg_min_price"]');
  const maxPrice=document.querySelector('.cg-modern-filters [name="cg_max_price"]');
  const inStock=document.querySelector('.cg-modern-filters [name="cg_in_stock"]');
  const onSale=document.querySelector('.cg-modern-filters [name="cg_on_sale"]');
  const ordering=document.querySelector('.woocommerce-ordering select');
  const reset=document.querySelector('.cg-modern-filters .cg-filter-reset');
  const toggle=document.querySelector('.cg-modern-filters__toggle');
  const filterBody=document.querySelector('.cg-modern-filters__body');
  const chips=[...document.querySelectorAll('.cg-category-chip')];
  let page=1;
  let controller;

  const setBusy=(busy)=>{
    shell.classList.toggle('is-loading',busy);
    shell.setAttribute('aria-busy',busy?'true':'false');
  };

  const syncChips=()=>{
    chips.forEach(chip=>chip.classList.toggle('is-active',(category?.value||'')===chip.dataset.category));
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
      const response=await fetch(cgAjaxCatalog.ajaxUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body,signal:controller.signal});
      const json=await response.json();
      if(!json.success) throw new Error('catalog');
      const current=products();
      if(current) current.outerHTML=json.data.products;
      else shell.insertAdjacentHTML('beforeend',json.data.products);
      shell.querySelector('.cg-ajax-pagination')?.remove();
      shell.insertAdjacentHTML('beforeend',json.data.pagination||'');
      const count=document.querySelector('.woocommerce-result-count');
      if(count) count.textContent=`Найдено товаров: ${json.data.found}`;
      syncChips();
      shell.scrollIntoView({behavior:'smooth',block:'start'});
    }catch(error){
      if(error.name!=='AbortError') window.location.reload();
    }finally{
      setBusy(false);
    }
  };

  const resetPage=()=>{page=1;load();};
  category?.addEventListener('change',resetPage);
  minPrice?.addEventListener('change',resetPage);
  maxPrice?.addEventListener('change',resetPage);
  inStock?.addEventListener('change',resetPage);
  onSale?.addEventListener('change',resetPage);
  ordering?.addEventListener('change',(event)=>{event.preventDefault();resetPage();});

  chips.forEach(chip=>chip.addEventListener('click',()=>{
    if(category) category.value=chip.dataset.category||'';
    resetPage();
  }));

  reset?.addEventListener('click',()=>{
    if(category) category.value='';
    if(minPrice) minPrice.value='';
    if(maxPrice) maxPrice.value='';
    if(inStock) inStock.checked=false;
    if(onSale) onSale.checked=false;
    if(ordering) ordering.value='menu_order';
    resetPage();
  });

  toggle?.addEventListener('click',()=>{
    const hidden=filterBody?.classList.toggle('is-collapsed');
    toggle.setAttribute('aria-expanded',hidden?'false':'true');
    toggle.textContent=hidden?'Показать фильтры':'Скрыть фильтры';
  });

  shell.addEventListener('click',(event)=>{
    const pageButton=event.target.closest('.cg-page-button');
    if(pageButton){page=Number(pageButton.dataset.page||1);load();}
  });
});
