(function(){
 /* floating promo */
 const close=document.getElementById('close-promo'),promo=document.getElementById('float-promo');
 if(close&&promo)close.addEventListener('click',()=>promo.remove());

 /* mobile nav */
 const hamb=document.getElementById('hamb'),nav=document.getElementById('mainnav');
 if(hamb&&nav)hamb.addEventListener('click',()=>nav.classList.toggle('open'));

  /* hero slider — plain CSS fallback. Runs ONLY when the Swiper CDN failed to load (home.js owns the Swiper build),
     so the two never compete for the same markup. */
  const track=document.getElementById('hero-track');
  if(track && typeof window.Swiper==='undefined'){
  const slides=[].slice.call(track.children),total=slides.length;
  const dots=[].slice.call(document.querySelectorAll('.hero-dot'));
  const count=document.getElementById('hero-count');
  const reduce=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let index=0,timer=null;
  track.style.transition=reduce?'none':'transform .65s cubic-bezier(.22,.61,.36,1)';
  const render=()=>{
   track.style.transform='translateX('+(-index*100)+'%)';
   dots.forEach((d,i)=>{d.classList.toggle('on',i===index);if(i===index)d.setAttribute('aria-current','true');else d.removeAttribute('aria-current');});
   if(count)count.textContent=(index+1)+' / '+total;
  };
  const go=(i)=>{index=(i+total)%total;render();};
  const start=()=>{stop();if(!reduce)timer=setInterval(()=>go(index+1),4000);};
  const stop=()=>{if(timer){clearInterval(timer);timer=null;}};
  document.getElementById('hero-prev')?.addEventListener('click',()=>{go(index-1);start();});
  document.getElementById('hero-next')?.addEventListener('click',()=>{go(index+1);start();});
  dots.forEach(d=>d.addEventListener('click',()=>{go(Number(d.dataset.slide)||0);start();}));
  const heroMain=track.closest('.hero-main');
  if(heroMain){
   heroMain.addEventListener('mouseenter',stop);heroMain.addEventListener('mouseleave',start);
   heroMain.addEventListener('focusin',stop);heroMain.addEventListener('focusout',start);
   /* touch swipe */
   let sx=null,sy=null;
   heroMain.addEventListener('touchstart',e=>{sx=e.touches[0].clientX;sy=e.touches[0].clientY;stop();},{passive:true});
   heroMain.addEventListener('touchend',e=>{
    if(sx===null)return;
    const dx=e.changedTouches[0].clientX-sx,dy=e.changedTouches[0].clientY-sy;
    if(Math.abs(dx)>40&&Math.abs(dx)>Math.abs(dy)*1.4)go(index+(dx<0?1:-1));
    sx=sy=null;start();
   },{passive:true});
  }
  render();start();
 }

  /* horizontal carousels (products + categories) — arrows + seamless autoplay.
     Autoplay steps one card every 3.5s, pauses on hover / focus / touch and
     loops invisibly by cloning the set once and snapping back inside the clone
     zone (no abrupt reset). Disabled when the user prefers reduced motion. */
  (function carousels(){
   const REDUCE=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;
   const DELAY=3500;
   const visible=el=>!!(el&&el.offsetParent);
   const silentJump=(sc,to)=>{
    const prev=sc.style.scrollSnapType;
    sc.style.scrollSnapType='none';
    sc.scrollLeft=to;
    requestAnimationFrame(()=>{sc.style.scrollSnapType=prev;});
   };
   function setupTrack(sc,root){
    if(sc.dataset.autoLoop)return;
    sc.dataset.autoLoop='1';
    const originals=[].slice.call(sc.children);
    if(originals.length<2)return;
    let clones=null,loopW=0;
    function ensure(){
     if(loopW>0)return true;
     if(loopW<0)return false;
     if(!visible(sc))return false;
     if(!clones){
      if(sc.scrollWidth<=sc.clientWidth+2){loopW=-1;return false;}   /* content fits — nothing to autoplay */
      clones=originals.map(node=>{
       const c=node.cloneNode(true);
       c.setAttribute('aria-hidden','true');
       [].forEach.call(c.querySelectorAll('a,button,input,select,textarea,[tabindex]'),x=>x.setAttribute('tabindex','-1'));
       sc.appendChild(c);
       return c;
      });
     }
     loopW=clones[0].offsetLeft-originals[0].offsetLeft;
     return loopW>0;
    }
    sc.addEventListener('scroll',()=>{
     if(loopW<=0)return;
     const l=sc.scrollLeft;
     if(l>=loopW-1)silentJump(sc,l-loopW);          /* crossed into the clone set — snap back invisibly */
     else if(l<-1)silentJump(sc,l+loopW);
    },{passive:true});
    function step(){
     if(REDUCE||!visible(sc)||!ensure())return;
     const d=originals[1]?originals[1].offsetLeft-originals[0].offsetLeft:Math.max(180,Math.round(sc.clientWidth*0.82));
     sc.scrollBy({left:d,behavior:'smooth'});
    }
    let timer=null,held=0;
    const stop=()=>{if(timer){clearInterval(timer);timer=null;}};
    const start=()=>{
     stop();
     if(REDUCE||document.hidden)return;
     timer=setInterval(()=>{if(!held&&visible(sc)&&!document.hidden)step();},DELAY);
    };
    root.addEventListener('mouseenter',()=>{held++;stop();});
    root.addEventListener('mouseleave',()=>{held=0;start();});
    root.addEventListener('focusin',()=>{held++;stop();});
    root.addEventListener('focusout',()=>{held=0;setTimeout(start,0);});
    sc.addEventListener('touchstart',()=>{held++;stop();},{passive:true});
    sc.addEventListener('touchend',()=>{held=0;setTimeout(start,400);},{passive:true});
    document.addEventListener('visibilitychange',()=>{if(document.hidden)stop();else if(!held)start();});
    start();
    (root._autoRestarts=root._autoRestarts||[]).push(start);           /* arrows reset the countdown */
   }
   [].forEach.call(document.querySelectorAll('.carousel,.cats-carousel'),root=>{
    const scrollers=[].slice.call(root.querySelectorAll('.track, .cats-viewport'));
    [].forEach.call(root.querySelectorAll('.arrow'),btn=>{
     btn.addEventListener('click',()=>{
      const dir=Number(btn.dataset.dir)||1;
      const sc=scrollers.filter(visible)[0];
      if(!sc)return;
      const step=Math.max(180,Math.round(sc.clientWidth*0.82));
      sc.scrollBy({left:dir*step,behavior:'smooth'});
      (root._autoRestarts||[]).forEach(f=>f());
     });
    });
    scrollers.forEach(sc=>setupTrack(sc,root));
   });
  })();

 /* best seller tabs */
 [].forEach.call(document.querySelectorAll('.tabs[data-tabs]'),tabs=>{
  tabs.addEventListener('click',e=>{
   const b=e.target.closest('.tab');if(!b)return;
   const cat=b.dataset.cat;if(!cat)return;
   [].forEach.call(tabs.querySelectorAll('.tab'),x=>x.classList.toggle('on',x===b));
   const section=tabs.closest('.sec')||document;
   [].forEach.call(section.querySelectorAll('[data-tabpanel]'),p=>{p.hidden=p.dataset.tabpanel!==cat;});
   const sc=section.querySelector('.track:not([hidden])');
   if(sc)sc.scrollTo({left:0,behavior:'smooth'});
  });
 });

 /* flash deals countdown (rolls to midnight) */
 const ch=document.getElementById('cd-h'),cm=document.getElementById('cd-m'),cs=document.getElementById('cd-s');
 if(ch&&cm&&cs){
  const pad=n=>String(n).padStart(2,'0');
  const tick=()=>{
   const now=new Date(),end=new Date(now);end.setHours(24,0,0,0);
   let s=Math.max(0,Math.floor((end-now)/1000));
   ch.textContent=pad(Math.floor(s/3600));s%=3600;
   cm.textContent=pad(Math.floor(s/60));
   cs.textContent=pad(s%60);
  };
  tick();setInterval(tick,1000);
 }

 /* support widget */
 const fab=document.getElementById('sup-fab'),panel=document.getElementById('sup-panel'),supClose=document.getElementById('sup-close');
 const setPanel=open=>{if(!panel||!fab)return;panel.hidden=!open;fab.classList.toggle('open',open);};
 if(fab)fab.addEventListener('click',()=>setPanel(panel.hidden));
 if(supClose)supClose.addEventListener('click',()=>setPanel(false));

 /* newsletter */
 const news=document.getElementById('news-form');
 if(news)news.addEventListener('submit',e=>{
  e.preventDefault();
  const wrap=news.parentElement;
  news.remove();
  const ok=document.createElement('p');
  ok.className='news-ok';
  ok.textContent='Thanks for subscribing — your exclusive offers are on the way.';
  wrap.appendChild(ok);
 });

 /* listing sort */
 const sort=document.getElementById('sort-products');
 const priceOf=el=>parseFloat((el.querySelector('.pprice b')?.textContent||'').replace(/[^0-9.]/g,''))||0;
 const discOf=el=>{
  const old=parseFloat((el.querySelector('.pprice del')?.textContent||'').replace(/[^0-9.]/g,''))||0;
  const now=priceOf(el);
  return old>now?((old-now)/old*100):0;
 };
 const syncSortInputs=value=>{
  [].forEach.call(document.querySelectorAll('form input[name="sort"]'),h=>{h.value=value;});
 };
 if(sort){
  syncSortInputs(sort.value);
  sort.addEventListener('change',()=>{
   const grid=document.getElementById('listing-grid');if(!grid)return;
   syncSortInputs(sort.value);
   const cards=[].slice.call(grid.children);
   cards.sort((a,b)=>{
    const name=x=>x.querySelector('.pname')?.textContent||'';
    switch(sort.value){
     case'low':return priceOf(a)-priceOf(b);
     case'high':return priceOf(b)-priceOf(a);
     case'name':return name(a).localeCompare(name(b));
     case'rate':return(parseFloat(b.querySelector('.rating b')?.textContent||0)-parseFloat(a.querySelector('.rating b')?.textContent||0));
     case'disc':return discOf(b)-discOf(a);
     default:return 0;
    }
   });
   cards.forEach(c=>grid.appendChild(c));
  });
 }

 /* ------------------------------------------------------------
    filter sidebar: accordion form, price pills, applied chips,
    mobile filter drawer (category + brand pages)
    ------------------------------------------------------------ */
 (function filters(){
  const form=document.getElementById('cat-filter-form')||document.getElementById('brand-filters');
  if(!form)return;
  const submit=()=>{
   const side=document.querySelector('.filter-side');
   if(side&&side.classList.contains('open'))setOpen(false);
   form.submit();
  };
  [].forEach.call(form.querySelectorAll('input[type="checkbox"],input[type="radio"]'),inp=>{
   inp.addEventListener('change',submit);
  });
  [].forEach.call(form.querySelectorAll('.bf-price-pills .pill'),btn=>{
   btn.addEventListener('click',()=>{
    const min=form.querySelector('input[name="min"]'),max=form.querySelector('input[name="max"]');
    if(min)min.value=btn.dataset.min||'';
    if(max)max.value=btn.dataset.max||'';
    submit();
   });
  });
  [].forEach.call(document.querySelectorAll('[data-drop]'),btn=>{
   btn.addEventListener('click',()=>{
    const nodes=form.querySelectorAll('input[name="'+btn.dataset.drop+'[]"]');
    [].forEach.call(nodes,n=>{if(n.value===btn.dataset.val)n.checked=false;});
    submit();
   });
  });

  const side=document.querySelector('.filter-side'),tgl=document.getElementById('filters-toggle'),bd=document.getElementById('cat-side-backdrop');
  function setOpen(open){
   if(!side)return;
   side.classList.toggle('open',open);
   if(tgl)tgl.setAttribute('aria-expanded',open?'true':'false');
   if(bd)bd.hidden=!open;
   document.body.style.overflow=open?'hidden':'';
  }
  if(side&&tgl){
   tgl.addEventListener('click',()=>setOpen(!side.classList.contains('open')));
   if(bd)bd.addEventListener('click',()=>setOpen(false));
   document.addEventListener('keydown',e=>{if(e.key==='Escape'&&side.classList.contains('open'))setOpen(false);});
   [].forEach.call(side.querySelectorAll('.bf-group>summary'),s=>{
    s.addEventListener('click',()=>{if(window.innerWidth<=1000)setOpen(true);});
   });
  }
 })();
})();
