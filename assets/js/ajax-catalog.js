document.addEventListener('DOMContentLoaded',()=>{
  const shell=document.querySelector('.cg-shop-content');
  if(!shell||typeof cgAjaxCatalog==='undefined') return;

  const products=()=>shell.querySelector('ul.products');
  const category=document.querySelector('[name="cg_category"]');
  const minPrice=document.querySelector('[name="cg_min_price"]');
  const maxPrice=document.querySelector('[name="cg_max_price"]');
  const ordering=document.querySelector('.woocommerce-ordering select');
  let page=1;
  let controller;

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
  ordering?.addEventListener('change',(event)=>{event.preventDefault();resetPage();});

  shell.addEventListener('click',(event)=>{
    const pageButton=event.target.closest('.cg-page-button');
    if(pageButton){page=Number(pageButton.dataset.page||1);load();}
  });
});
