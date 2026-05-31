/* SKYPETS — main.js */
document.addEventListener('DOMContentLoaded', () => {

  // Nav scroll shadow
  const nav = document.getElementById('nav');
  if (nav) window.addEventListener('scroll', () => nav.classList.toggle('scrolled', window.scrollY > 30));

  // Reveal on scroll
  document.querySelectorAll('.reveal').forEach(el =>
    new IntersectionObserver(e => { if(e[0].isIntersecting) e[0].target.classList.add('visible'); },{threshold:.1}).observe(el));

  // Stat counters
  const counters = document.querySelectorAll('[data-target]');
  const statsEl  = document.querySelector('.stats-strip,.stats-bar,.stats-band');
  if (counters.length && statsEl) {
    let done = false;
    new IntersectionObserver(e => {
      if (e[0].isIntersecting && !done) {
        done = true;
        counters.forEach(el => {
          const t = +el.dataset.target, step = t/80; let v = 0;
          const id = setInterval(() => { v+=step; if(v>=t){el.textContent='+'+t;clearInterval(id);}else el.textContent='+'+Math.round(v); },16);
        });
      }
    },{threshold:.5}).observe(statsEl);
  }

  // Hero slider
  const slides = document.querySelectorAll('.hero-slide');
  const dots   = document.querySelectorAll('.hero-dot');
  if (slides.length > 1) {
    let cur=0, timer;
    const goTo = n => {
      slides[cur].classList.remove('active'); dots[cur]?.classList.remove('active');
      cur = (n+slides.length)%slides.length;
      slides[cur].classList.add('active'); dots[cur]?.classList.add('active');
    };
    dots.forEach(d => d.addEventListener('click',()=>{clearInterval(timer);goTo(+d.dataset.idx);timer=setInterval(()=>goTo(cur+1),4800);}));
    timer = setInterval(()=>goTo(cur+1),4800);
  }

  // FAQ accordion
  document.querySelectorAll('.faq-q').forEach(btn =>
    btn.addEventListener('click',()=>{
      const item=btn.closest('.faq-item'), open=item.classList.contains('open');
      document.querySelectorAll('.faq-item.open').forEach(i=>i.classList.remove('open'));
      if(!open) item.classList.add('open');
    }));

  // Filter buttons
  document.querySelectorAll('.filter-btn').forEach(btn =>
    btn.addEventListener('click',()=>{
      document.querySelectorAll('.filter-btn').forEach(b=>b.classList.remove('active'));
      btn.classList.add('active');
      const cat=btn.dataset.cat;
      document.querySelectorAll('[data-cat]').forEach(c=>c.dataset.hidden=(cat!=='todos'&&c.dataset.cat!==cat)?'true':'false');
    }));

  // TOC active (blog articles)
  const tocLinks = document.querySelectorAll('.toc-link[href^="#"]');
  if (tocLinks.length) {
    document.querySelectorAll('[id]').forEach(s =>
      new IntersectionObserver(e=>{
        if(e[0].isIntersecting){
          tocLinks.forEach(l=>l.classList.remove('active'));
          document.querySelector(`.toc-link[href="#${e[0].target.id}"]`)?.classList.add('active');
        }
      },{rootMargin:'-15% 0px -75% 0px'}).observe(s));
  }
});
