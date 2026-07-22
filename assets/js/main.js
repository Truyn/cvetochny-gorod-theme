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

  prev?.addEventListener('click',()=>{show(index-1);start();});
  next?.addEventListener('click',()=>{show(index+1);start();});
  dots.forEach((dot,i)=>dot.addEventListener('click',()=>{show(i);start();}));
  slider.addEventListener('mouseenter',stop);
  slider.addEventListener('mouseleave',start);
  slider.addEventListener('focusin',stop);
  slider.addEventListener('focusout',start);

  show(0);
  start();
});