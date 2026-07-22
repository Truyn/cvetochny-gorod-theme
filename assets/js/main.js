document.addEventListener('DOMContentLoaded',()=>{
  const button=document.querySelector('.menu-toggle');
  const menu=document.querySelector('.main-navigation');
  if(button&&menu){
    button.addEventListener('click',()=>{
      const open=menu.classList.toggle('is-open');
      button.setAttribute('aria-expanded',open?'true':'false');
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