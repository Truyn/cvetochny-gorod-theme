(function($){
  'use strict';

  const drawer=document.getElementById('cg-mini-cart');
  const overlay=document.querySelector('.cg-mini-cart-overlay');
  const openButtons=document.querySelectorAll('[data-cg-mini-cart-open]');

  if(!drawer||!overlay||typeof cgMiniCart==='undefined') return;

  const openCart=()=>{
    drawer.classList.add('is-open');
    drawer.setAttribute('aria-hidden','false');
    overlay.hidden=false;
    window.requestAnimationFrame(()=>overlay.classList.add('is-visible'));
    document.body.classList.add('cg-mini-cart-open');
    drawer.querySelector('.cg-mini-cart__close')?.focus();
  };

  const closeCart=()=>{
    drawer.classList.remove('is-open');
    drawer.setAttribute('aria-hidden','true');
    overlay.classList.remove('is-visible');
    document.body.classList.remove('cg-mini-cart-open');
    window.setTimeout(()=>{overlay.hidden=true;},220);
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
  };

  const request=(action,key,quantity)=>{
    const data={action,nonce:cgMiniCart.nonce,cart_item_key:key};
    if(quantity!==undefined) data.quantity=quantity;

    drawer.classList.add('is-loading');
    return $.post(cgMiniCart.ajaxUrl,data)
      .done((response)=>{
        if(response?.success){
          applyFragments(response.data.fragments);
          $(document.body).trigger('wc_fragment_refresh');
        }
      })
      .always(()=>drawer.classList.remove('is-loading'));
  };

  openButtons.forEach((button)=>button.addEventListener('click',(event)=>{
    event.preventDefault();
    openCart();
  }));

  document.addEventListener('click',(event)=>{
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
      const next=Math.max(1,Number(input.value||1)-1);
      input.value=next;
      request('cg_update_cart_item',key,next);
      return;
    }

    if(event.target.closest('[data-cg-cart-increase]')){
      const next=Math.max(1,Number(input.value||1)+1);
      input.value=next;
      request('cg_update_cart_item',key,next);
    }
  });

  document.addEventListener('change',(event)=>{
    const input=event.target.closest('[data-cg-cart-quantity]');
    if(!input) return;
    const item=input.closest('[data-cart-item-key]');
    const quantity=Math.max(1,Number(input.value||1));
    input.value=quantity;
    request('cg_update_cart_item',item.dataset.cartItemKey,quantity);
  });

  document.addEventListener('keydown',(event)=>{
    if(event.key==='Escape'&&drawer.classList.contains('is-open')) closeCart();
  });

  $(document.body).on('added_to_cart',()=>openCart());
})(jQuery);
