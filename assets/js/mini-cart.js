(function($){
  'use strict';

  const getDrawer=()=>document.getElementById('cg-mini-cart');
  const getOverlay=()=>document.querySelector('.cg-mini-cart-overlay');
  let lastTrigger=null;

  const focusableSelector='a[href],button:not([disabled]),input:not([disabled]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])';

  /** Keep trailing product numbers such as №15 visually even inside serif names. */
  const polishProductNames=(root=document)=>{
    root.querySelectorAll('.cg-mini-cart__name:not([data-cg-name-polished])').forEach((node)=>{
      const original=(node.textContent||'').trim();
      const match=original.match(/^(.*?)(\s*№\s*\d+)\s*$/u);

      node.setAttribute('data-cg-name-polished','1');
      if(!match) return;

      const label=match[1].trimEnd();
      const code=match[2].replace(/\s+/g,'');
      node.textContent='';
      node.append(document.createTextNode(label+' '));

      const codeNode=document.createElement('span');
      codeNode.className='cg-mini-cart__name-code';
      codeNode.textContent=code;
      node.append(codeNode);
    });
  };

  const drawerFocusables=()=>{
    const drawer=getDrawer();
    return drawer ? Array.from(drawer.querySelectorAll(focusableSelector)).filter((node)=>node.offsetParent!==null) : [];
  };

  const openCart=(trigger=null)=>{
    const drawer=getDrawer();
    const overlay=getOverlay();
    if(!drawer||!overlay) return;
    if(trigger instanceof HTMLElement) lastTrigger=trigger;
    polishProductNames(drawer);
    drawer.classList.add('is-open');
    drawer.setAttribute('aria-hidden','false');
    drawer.setAttribute('role','dialog');
    drawer.setAttribute('aria-modal','true');
    drawer.setAttribute('tabindex','-1');
    overlay.hidden=false;
    window.requestAnimationFrame(()=>overlay.classList.add('is-visible'));
    document.body.classList.add('cg-mini-cart-open');
    (drawer.querySelector('.cg-mini-cart__close')||drawer).focus();
  };

  const closeCart=()=>{
    const drawer=getDrawer();
    const overlay=getOverlay();
    if(!drawer||!overlay) return;
    drawer.classList.remove('is-open');
    drawer.setAttribute('aria-hidden','true');
    drawer.removeAttribute('aria-modal');
    overlay.classList.remove('is-visible');
    document.body.classList.remove('cg-mini-cart-open');
    window.setTimeout(()=>{overlay.hidden=true;},220);

    if(lastTrigger&&document.contains(lastTrigger)&&lastTrigger.focus){
      lastTrigger.focus({preventScroll:true});
    }
    lastTrigger=null;
  };

  const applyFragments=(fragments)=>{
    if(!fragments) return;
    Object.entries(fragments).forEach(([selector,html])=>{
      document.querySelectorAll(selector).forEach((node)=>{
        const template=document.createElement('template');
        template.innerHTML=html.trim();
        const replacement=template.content.firstElementChild;
        if(replacement) node.replaceWith(replacement.cloneNode(true));
      });
    });
    polishProductNames(document);
  };

  const request=(action,key,quantity)=>{
    const drawer=getDrawer();
    if(!drawer||typeof cgMiniCart==='undefined') return $.Deferred().reject().promise();
    const data={action,nonce:cgMiniCart.nonce,cart_item_key:key};
    if(quantity!==undefined) data.quantity=quantity;

    drawer.classList.add('is-loading');
    drawer.setAttribute('aria-busy','true');
    return $.post(cgMiniCart.ajaxUrl,data)
      .done((response)=>{
        if(response?.success){
          applyFragments(response.data.fragments);
          $(document.body).trigger('wc_fragment_refresh');
        }
      })
      .always(()=>{
        const current=getDrawer();
        current?.classList.remove('is-loading');
        current?.removeAttribute('aria-busy');
      });
  };

  document.addEventListener('DOMContentLoaded',()=>polishProductNames(document));

  document.addEventListener('click',(event)=>{
    const openTrigger=event.target.closest('[data-cg-mini-cart-open]');
    if(openTrigger){
      event.preventDefault();
      openCart(openTrigger);
      return;
    }

    if(event.target.closest('[data-cg-mini-cart-close]')){
      closeCart();
      return;
    }

    const item=event.target.closest('[data-cart-item-key]');
    if(!item) return;
    const key=item.dataset.cartItemKey;
    const input=item.querySelector('[data-cg-cart-quantity]');

    if(event.target.closest('[data-cg-cart-remove]')){
      request('cg_remove_cart_item',key);
      return;
    }

    if(event.target.closest('[data-cg-cart-decrease]')){
      const current=Math.max(1,Number(input?.value||1));

      if(current<=1){
        request('cg_remove_cart_item',key);
        return;
      }

      const next=current-1;
      input.value=next;
      request('cg_update_cart_item',key,next);
      return;
    }

    if(event.target.closest('[data-cg-cart-increase]')){
      const next=Math.max(1,Number(input?.value||1)+1);
      if(input) input.value=next;
      request('cg_update_cart_item',key,next);
    }
  });

  document.addEventListener('change',(event)=>{
    const input=event.target.closest('[data-cg-cart-quantity]');
    if(!input) return;
    const item=input.closest('[data-cart-item-key]');
    if(!item) return;
    const quantity=Number(input.value||0);

    if(quantity<=0){
      request('cg_remove_cart_item',item.dataset.cartItemKey);
      return;
    }

    const normalized=Math.max(1,Math.floor(quantity));
    input.value=normalized;
    request('cg_update_cart_item',item.dataset.cartItemKey,normalized);
  });

  document.addEventListener('keydown',(event)=>{
    const drawer=getDrawer();
    if(!drawer?.classList.contains('is-open')) return;

    if(event.key==='Escape'){
      event.preventDefault();
      closeCart();
      return;
    }

    if(event.key!=='Tab') return;
    const focusables=drawerFocusables();
    if(!focusables.length){
      event.preventDefault();
      drawer.focus();
      return;
    }

    const first=focusables[0];
    const last=focusables[focusables.length-1];
    if(event.shiftKey&&document.activeElement===first){
      event.preventDefault();
      last.focus();
    }else if(!event.shiftKey&&document.activeElement===last){
      event.preventDefault();
      first.focus();
    }
  });

  $(document.body).on('wc_fragments_loaded wc_fragments_refreshed',()=>{
    window.requestAnimationFrame(()=>polishProductNames(document));
  });

  $(document.body).on('added_to_cart',()=>{
    window.requestAnimationFrame(()=>polishProductNames(document));
    openCart();
  });
})(jQuery);
