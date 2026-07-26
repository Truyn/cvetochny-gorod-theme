document.addEventListener('DOMContentLoaded',()=>{
  const menuButton=document.querySelector('.menu-toggle');
  const menu=document.querySelector('.main-navigation');
  const searchButton=document.querySelector('.search-toggle');
  const searchPanel=document.querySelector('.header-search');

  if(menuButton&&menu){
    menuButton.addEventListener('click',()=>{
      const open=menu.classList.toggle('is-open');
      menuButton.classList.toggle('is-active',open);
      menuButton.setAttribute('aria-expanded',open?'true':'false');
      if(open&&searchPanel&&!searchPanel.hidden){
        searchPanel.hidden=true;
        searchButton?.setAttribute('aria-expanded','false');
      }
    });
  }

  if(searchButton&&searchPanel){
    searchButton.addEventListener('click',()=>{
      const open=searchPanel.hidden;
      searchPanel.hidden=!open;
      searchButton.setAttribute('aria-expanded',open?'true':'false');
      if(open){
        menu?.classList.remove('is-open');
        menuButton?.classList.remove('is-active');
        menuButton?.setAttribute('aria-expanded','false');
        window.setTimeout(()=>searchPanel.querySelector('input[type="search"]')?.focus(),50);
      }
    });

    document.addEventListener('keydown',(event)=>{
      if(event.key==='Escape'&&!searchPanel.hidden){
        searchPanel.hidden=true;
        searchButton.setAttribute('aria-expanded','false');
        searchButton.focus();
      }
    });
  }

  const filterButton=document.querySelector('.cg-filter-toggle');
  const shopSidebar=document.querySelector('.cg-shop-sidebar');
  if(filterButton&&shopSidebar){
    filterButton.addEventListener('click',()=>{
      const open=shopSidebar.classList.toggle('is-open');
      filterButton.setAttribute('aria-expanded',open?'true':'false');
      filterButton.textContent=open?'Скрыть фильтры':'Показать фильтры';
    });
  }

  const slider=document.querySelector('.cg-slider');
  if(!slider) return;
  const slides=[...slider.querySelectorAll('.cg-slide')];
  const dots=[...slider.querySelectorAll('.cg-slider__dot')];
  const prev=slider.querySelector('.cg-slider__arrow--prev');
  const next=slider.querySelector('.cg-slider__arrow--next');
  let index=0;
  let timer;
  let touchStartX=0;
  let touchStartY=0;
  let touchCurrentX=0;
  let touchCurrentY=0;
  let isTouching=false;

  const show=(newIndex)=>{
    index=(newIndex+slides.length)%slides.length;
    slides.forEach((slide,i)=>{
      slide.classList.toggle('is-active',i===index);
      slide.setAttribute('aria-hidden',i===index?'false':'true');
    });
    dots.forEach((dot,i)=>{
      dot.classList.toggle('is-active',i===index);
      dot.setAttribute('aria-current',i===index?'true':'false');
    });
  };

  const stop=()=>window.clearInterval(timer);
  const start=()=>{
    stop();
    timer=window.setInterval(()=>show(index+1),6500);
  };

  const resetTouch=()=>{
    touchStartX=0;
    touchStartY=0;
    touchCurrentX=0;
    touchCurrentY=0;
    isTouching=false;
  };

  prev?.addEventListener('click',()=>{show(index-1);start();});
  next?.addEventListener('click',()=>{show(index+1);start();});
  dots.forEach((dot,i)=>dot.addEventListener('click',()=>{show(i);start();}));
  slider.addEventListener('mouseenter',stop);
  slider.addEventListener('mouseleave',start);
  slider.addEventListener('focusin',stop);
  slider.addEventListener('focusout',start);

  slider.addEventListener('touchstart',(event)=>{
    if(event.touches.length!==1) return;
    const touch=event.touches[0];
    touchStartX=touch.clientX;
    touchStartY=touch.clientY;
    touchCurrentX=touch.clientX;
    touchCurrentY=touch.clientY;
    isTouching=true;
    stop();
  },{passive:true});

  slider.addEventListener('touchmove',(event)=>{
    if(!isTouching||event.touches.length!==1) return;
    const touch=event.touches[0];
    touchCurrentX=touch.clientX;
    touchCurrentY=touch.clientY;
  },{passive:true});

  slider.addEventListener('touchend',()=>{
    if(!isTouching){
      start();
      return;
    }
    const deltaX=touchCurrentX-touchStartX;
    const deltaY=touchCurrentY-touchStartY;
    const horizontalSwipe=Math.abs(deltaX)>45&&Math.abs(deltaX)>Math.abs(deltaY)*1.2;

    if(horizontalSwipe){
      show(deltaX<0?index+1:index-1);
    }

    resetTouch();
    start();
  },{passive:true});

  slider.addEventListener('touchcancel',()=>{
    resetTouch();
    start();
  },{passive:true});

  show(0);
  start();
});