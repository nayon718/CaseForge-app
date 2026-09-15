(function(){
  "use strict";
  const APP = window.CAKRI || {base:"", isAdmin:false, siteName:"চাকরি সার্কুলার"};
  const cache = new Map();
  const pageEl = document.getElementById("pageContent");
  const progressBar = document.getElementById("topProgressBar");
  const appMain = document.getElementById("appMain");
  const noticeBadge = document.getElementById("noticeBadge");

  function escapeHtml(s){ return String(s||"").replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[c])); }
  function normalizePath(path){
    try{ var u=new URL(path, location.origin); return (u.pathname.replace(/\/+$/,"")||"/") + u.search; }catch(e){ return path; }
  }
  function toast(msg,type){
    type=type||"success"; var box=document.getElementById("toastBox"); if(!box||!msg) return;
    var el=document.createElement("div"); el.className="toast "+type;
    el.innerHTML='<i class="fa-solid '+(type==="error"?"fa-circle-exclamation":"fa-circle-check")+'"></i><div>'+escapeHtml(msg)+'</div><button class="toast-close"><i class="fa-solid fa-xmark"></i></button>';
    box.appendChild(el);
    el.querySelector(".toast-close").addEventListener("click",()=>el.remove());
    setTimeout(()=>el.remove(),5000);
  }
  // Progress
  var progress={value:0,set:function(v){ if(!progressBar) return; this.value=Math.max(this.value,Math.min(100,v)); progressBar.style.opacity="1"; progressBar.style.width=this.value+"%"; if(this.value>=100){ var self=this; setTimeout(function(){ self.value=0; progressBar.style.width="0%"; progressBar.style.opacity="0"; },300);} }};
  function loader(show){ var el=document.getElementById("appLoader"); if(!el) return; if(show) el.classList.add("show"); else setTimeout(()=>el.classList.remove("show"),180); }

  function needsSpa(path){
    if(APP.isAdmin) return false;
    var p=normalizePath(path);
    if(/^\/(admin|v2admin)\b/.test(p)) return false;
    if(/\.(jpg|jpeg|png|gif|webp|svg|pdf|xml|txt)$/i.test(p)) return false;
    return true;
  }

  async function loadPage(path, push){
    if(!needsSpa(path)){ location.href=path; return false; }
    var clean=normalizePath(path);
    if(cache.has(clean)){
      showContent(cache.get(clean), false);
      if(push) history.pushState(null,"",clean);
      track(clean);
      return true;
    }
    progress.set(12); loader(true);
    var url=(APP.base||"")+clean+(clean.indexOf("?")>-1?"&":"?")+"_ajax=1";
    try{
      var res=await fetch(url,{headers:{"X-Requested-With":"XMLHttpRequest"}});
      if(!res.ok) throw new Error("server");
      var html=await res.text();
      cache.set(clean, html);
      if(cache.size>60){ var firstKey=cache.keys().next().value; cache.delete(firstKey); }
      showContent(html, true);
      if(push) history.pushState(null,"",clean);
      track(clean);
      return true;
    }catch(err){
      toast("পেজ লোড করা যায়নি","error");
      return false;
    }finally{
      progress.set(100); loader(false);
    }
  }

  function showContent(html, animate){
    if(!pageEl) return;
    if(animate && appMain){ appMain.classList.add("fade-out"); setTimeout(function(){
      pageEl.innerHTML=html;
      appMain.classList.remove("fade-out");
      appMain.style.animation="none"; appMain.offsetHeight; appMain.style.animation="";
      initPage();
      window.scrollTo({top:0,behavior:"smooth"});
    },160); } else {
      pageEl.innerHTML=html;
      initPage();
      window.scrollTo({top:0,behavior:"smooth"});
    }
  }

  function track(page){
    var tUrl=(APP.base||"")+page+(page.indexOf("?")>-1?"&":"?")+"_ajax=1&track=1&page="+encodeURIComponent(page);
    fetch(tUrl,{headers:{"X-Requested-With":"XMLHttpRequest"}}).catch(()=>{});
  }

  window.addEventListener("popstate",function(){
    var path=normalizePath(location.href);
    if(cache.has(path)){ showContent(cache.get(path), false); }
    else { loadPage(path,false); }
  });

  function navigateTo(path){
    if(!needsSpa(path)){ location.href=path; return; }
    var clean=normalizePath(path);
    if(clean===normalizePath(location.href)){ initPage(); return; }
    loadPage(clean,true);
  }

  document.addEventListener("click",function(e){
    var el=e.target.closest("[data-link]");
    if(!el) return;
    var href=el.getAttribute("href");
    if(!href||href==="#"||href.startsWith("javascript:")) return;
    var u; try{ u=new URL(href, location.origin);}catch{return;}
    if(u.origin!==location.origin) return;
    if(el.getAttribute("target")==="_blank") return;
    if(el.hasAttribute("data-no-spa")) return;
    e.preventDefault();
    navigateTo(u.pathname+u.search);
    closeSidebars();
  });

  // Sidebar
  function closeSidebars(){
    document.querySelectorAll(".site-sidebar.open, .admin-sidebar.open").forEach(function(s){ s.classList.remove("open"); });
    document.body.classList.remove("menu-open");
  }
  function bindMenus(){
    var trigger=document.getElementById("menuTrigger");
    var side=document.getElementById("siteSidebar")||document.getElementById("adminSidebar");
    if(trigger&&side&&!trigger.dataset.bound){
      trigger.dataset.bound="1";
      trigger.addEventListener("click",function(){ side.classList.add("open"); document.body.classList.add("menu-open"); });
    }
    document.querySelectorAll("[data-close-side]").forEach(function(b){
      if(b.dataset.bound) return; b.dataset.bound="1";
      b.addEventListener("click",closeSidebars);
    });
    document.querySelectorAll("[data-open-sidebar]").forEach(function(b){
      if(b.dataset.bound) return; b.dataset.bound="1";
      b.addEventListener("click",function(e){ e.preventDefault(); var s=document.getElementById("siteSidebar"); if(s){ s.classList.add("open"); document.body.classList.add("menu-open"); } });
    });
  }

  function bindShare(){
    document.querySelectorAll(".share-btn").forEach(function(btn){
      if(btn.dataset.bound) return; btn.dataset.bound="1";
      btn.addEventListener("click",function(e){
        e.stopPropagation();
        var card=btn.closest(".job-card, .card, .job-detail, article");
        var popup=card?card.querySelector(".share-popup"):document.querySelector(".share-popup");
        if(!popup){
          // create generic popup near btn
          var url=btn.getAttribute("data-url")||location.href;
          var title=btn.getAttribute("data-title")||document.title;
          var wrap=document.createElement("div");
          wrap.innerHTML='<div class="share-popup"><div class="share-head"><strong><i class="fa-solid fa-share-nodes"></i> Share</strong><button class="share-close" data-share-close><i class="fa-solid fa-xmark"></i></button></div><div class="share-title">'+escapeHtml(title)+'</div><div class="share-linkbox"><input value="'+escapeHtml(url)+'" readonly><button data-copy="'+escapeHtml(url)+'"><i class="fa-solid fa-copy"></i> কপি</button></div><div class="share-grid"><a href="https://www.facebook.com/sharer/sharer.php?u='+encodeURIComponent(url)+'" target="_blank" class="sh-fb"><i class="fa-brands fa-facebook"></i> Facebook</a><a href="https://twitter.com/intent/tweet?url='+encodeURIComponent(url)+'&text='+encodeURIComponent(title)+'" target="_blank" class="sh-tw"><i class="fa-brands fa-x-twitter"></i> Twitter</a><a href="https://wa.me/?text='+encodeURIComponent(title+' '+url)+'" target="_blank" class="sh-wa"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a><a href="https://t.me/share/url?url='+encodeURIComponent(url)+'&text='+encodeURIComponent(title)+'" target="_blank" class="sh-tg"><i class="fa-brands fa-telegram"></i> Telegram</a></div></div>';
          var pop=wrap.firstChild;
          pop.style.position="absolute"; pop.style.right="12px"; pop.style.top="48px"; pop.style.zIndex="30";
          btn.parentElement.style.position="relative";
          btn.parentElement.appendChild(pop);
          popup=pop;
          bindCopy(pop);
          pop.querySelectorAll("[data-share-close]").forEach(function(c){ c.addEventListener("click",function(){ pop.hidden=true; }); });
        } else {
          popup.hidden=!popup.hidden;
        }
      });
    });
    document.addEventListener("click",function(e){
      if(!e.target.closest(".share-btn") && !e.target.closest(".share-popup")){
        document.querySelectorAll(".share-popup").forEach(function(p){ p.hidden=true; });
      }
    });
    document.querySelectorAll("[data-share-close]").forEach(function(b){
      if(b.dataset.bound) return; b.dataset.bound="1";
      b.addEventListener("click",function(){ var p=b.closest(".share-popup"); if(p) p.hidden=true; });
    });
  }

  function bindCopy(root){
    root=root||document;
    root.querySelectorAll("[data-copy]").forEach(function(btn){
      if(btn.dataset.bound) return; btn.dataset.bound="1";
      btn.addEventListener("click",function(){
        var txt=btn.getAttribute("data-copy")||"";
        if(navigator.clipboard&&navigator.clipboard.writeText){ navigator.clipboard.writeText(txt).then(()=>toast("লিংক কপি হয়েছে")); }
        else { var t=document.createElement("textarea"); t.value=txt; t.style.position="fixed"; t.style.opacity="0"; document.body.appendChild(t); t.select(); try{document.execCommand("copy"); toast("লিংক কপি হয়েছে");}catch(e){} t.remove(); }
      });
    });
  }

  function bindLazy(){
    if(!("IntersectionObserver" in window)) return;
    var io=new IntersectionObserver(function(entries){ entries.forEach(function(en){ if(!en.isIntersecting) return; var img=en.target; if(img.dataset.src){ img.src=img.dataset.src; img.removeAttribute("data-src"); } io.unobserve(img); }); },{rootMargin:"200px"});
    document.querySelectorAll("img[data-src]").forEach(function(i){ io.observe(i); });
    document.querySelectorAll("img[loading=lazy]").forEach(function(img){
      if(img.complete) return;
      img.addEventListener("load",function(){ img.style.opacity="1"; });
    });
  }

  function bindLightbox(){
    var lb=document.getElementById("lightbox"); if(!lb) return;
    var lbImg=document.getElementById("lightboxImg");
    var list=[], idx=0;
    function show(i){ idx=(i+list.length)%list.length; lbImg.src=list[idx]; lb.classList.add("show"); }
    document.querySelectorAll(".detail-gallery-main, [data-lightbox]").forEach(function(el){
      if(el.dataset.bound) return; el.dataset.bound="1";
      el.addEventListener("click",function(){
        var gallery=el.closest(".detail-gallery")||document;
        var imgs=gallery.querySelectorAll(".detail-thumbs img, [data-full]");
        list=[]; imgs.forEach(function(im){ var src=im.getAttribute("data-full")||im.src; if(src) list.push(src); });
        if(list.length===0){ var main=document.getElementById("detailMain"); if(main) list=[main.src]; }
        var src=el.getAttribute("data-full")||el.querySelector("img")?.src||el.src;
        idx=list.indexOf(src); if(idx<0) idx=0;
        show(idx);
      });
    });
    document.querySelectorAll(".detail-thumbs img").forEach(function(th){
      if(th.dataset.boundTh) return; th.dataset.boundTh="1";
      th.addEventListener("click",function(){
        var main=document.getElementById("detailMain"); if(main){ main.src=th.getAttribute("data-full")||th.src; }
        document.querySelectorAll(".detail-thumbs img").forEach(function(i){ i.classList.remove("active"); });
        th.classList.add("active");
      });
    });
    lb.addEventListener("click",function(e){ if(e.target===lb) lb.classList.remove("show"); });
    document.querySelectorAll("[data-lb-close]").forEach(function(b){ b.addEventListener("click",function(){ lb.classList.remove("show"); }); });
    document.querySelectorAll("[data-lb-prev]").forEach(function(b){ b.addEventListener("click",function(){ show(idx-1); }); });
    document.querySelectorAll("[data-lb-next]").forEach(function(b){ b.addEventListener("click",function(){ show(idx+1); }); });
    document.addEventListener("keydown",function(e){ if(!lb.classList.contains("show")) return; if(e.key==="Escape") lb.classList.remove("show"); if(e.key==="ArrowLeft") show(idx-1); if(e.key==="ArrowRight") show(idx+1); });
  }

  function bindBanner(){
    var track=document.getElementById("bannerTrack"); if(!track) return;
    var dots=document.getElementById("bannerDots");
    var prev=document.getElementById("bannerPrev"); var next=document.getElementById("bannerNext");
    var cards=track.querySelectorAll(".banner-card"); if(cards.length<=1) return;
    var index=0; var auto;
    function go(i){ index=(i+cards.length)%cards.length; track.scrollTo({left:cards[index].offsetLeft - track.offsetLeft, behavior:"smooth"}); updateDots(); }
    function updateDots(){ if(!dots) return; dots.innerHTML=""; cards.forEach(function(_,i){ var b=document.createElement("button"); b.className="banner-dot"+(i===index?" active":""); b.addEventListener("click",function(){ go(i); resetAuto(); }); dots.appendChild(b); }); }
    function resetAuto(){ clearInterval(auto); auto=setInterval(function(){ go(index+1); },4000); }
    if(prev) prev.addEventListener("click",function(){ go(index-1); resetAuto(); });
    if(next) next.addEventListener("click",function(){ go(index+1); resetAuto(); });
    updateDots(); resetAuto();
    // Pause on hover
    track.addEventListener("mouseenter",function(){ clearInterval(auto); });
    track.addEventListener("mouseleave",function(){ resetAuto(); });
    // Drag
    var isDown=false, startX, scrollLeft;
    track.addEventListener("mousedown",function(e){ isDown=true; startX=e.pageX - track.offsetLeft; scrollLeft=track.scrollLeft; });
    track.addEventListener("mouseleave",function(){ isDown=false; });
    track.addEventListener("mouseup",function(){ isDown=false; });
    track.addEventListener("mousemove",function(e){ if(!isDown) return; e.preventDefault(); var x=e.pageX - track.offsetLeft; var walk=(x-startX)*1.5; track.scrollLeft=scrollLeft-walk; });
  }

  function bindAjaxForm(form){
    if(form.dataset.bound) return; form.dataset.bound="1";
    form.addEventListener("submit",async function(e){
      e.preventDefault();
      if(form.querySelectorAll("[required]").length && !form.checkValidity()){ form.reportValidity(); return; }
      var btn=form.querySelector("button[type=submit]"); var oldHtml="";
      if(btn){ oldHtml=btn.innerHTML; btn.disabled=true; btn.innerHTML='<span class="loader-spinner" style="width:18px;height:18px;border-width:2px;display:inline-block;vertical-align:middle"></span> অপেক্ষা করুন...'; }
      var fd=new FormData(form);
      var url=form.getAttribute("data-url")||form.getAttribute("action")||location.href;
      var fullUrl=(url.indexOf("http")===0?"":(APP.base||""))+url;
      try{
        var res=await fetch(fullUrl,{method:"POST",headers:{"X-Requested-With":"XMLHttpRequest"},body:fd});
        var json=await res.json().catch(()=>({ok:false,message:"সার্ভার ত্রুটি"}));
        if(json.ok){
          toast(json.message||"সফল হয়েছে");
          if(json.data&&json.data.redirect){ location.href=json.data.redirect; return; }
          if(form.id==="wrongReportForm"){ form.reset(); }
        } else { toast(json.message||"ব্যর্থ হয়েছে","error"); }
      }catch(err){ toast("নেটওয়ার্ক ত্রুটি","error"); }
      finally{ if(btn){ btn.disabled=false; btn.innerHTML=oldHtml; } }
    });
  }

  function bindSearchLive(){
    var input=document.getElementById("searchInput");
    if(!input||input.dataset.bound) return; input.dataset.bound="1";
    var timeout;
    input.addEventListener("input",function(){
      clearTimeout(timeout);
      timeout=setTimeout(function(){
        var q=input.value.trim();
        if(q.length<2) return;
        // prefetch search
        var url=(APP.base||"")+"/search?q="+encodeURIComponent(q)+"&_ajax=1";
        fetch(url,{headers:{"X-Requested-With":"XMLHttpRequest"}}).then(r=>r.text()).then(html=>{ cache.set("/search?q="+encodeURIComponent(q), html); }).catch(()=>{});
      },400);
    });
  }

  function bindNotices(){
    // Sound for new notices (if unread)
    var badge=document.getElementById("noticeBadge");
    if(badge){
      fetch((APP.base||"")+"/notices?_ajax=1",{headers:{"X-Requested-With":"XMLHttpRequest"}}).then(r=>r.text()).then(()=>{
        // mock unread count from localStorage
        var lastSeen=localStorage.getItem("cakri_notice_seen")||0;
        var now=Date.now();
        // If notices page visited recently, hide badge else show
        // For demo, show badge if not seen in 1 day
        if(now - parseInt(lastSeen) > 86400000){
          badge.textContent="1"; badge.hidden=false;
          // play sound (optional)
          try{ var audio=new Audio("data:audio/wav;base64,UklGRigAAABXQVZFZm10IBAAAAABAAEARKwAAIhYAQACABAAZGF0YQQAAAAAAA=="); audio.volume=0.3; audio.play().catch(()=>{}); }catch(e){}
        }
      }).catch(()=>{});
      // When notices link clicked, mark seen
      document.querySelectorAll('a[href*="/notices"]').forEach(function(a){
        a.addEventListener("click",function(){ localStorage.setItem("cakri_notice_seen", Date.now().toString()); if(badge) badge.hidden=true; });
      });
    }
  }

  function bindScrollSpy(){
    var cur=normalizePath(location.href);
    document.querySelectorAll(".bottom-link, .side-link, .scroll-link").forEach(function(el){
      var href=el.getAttribute("href"); if(!href) return;
      var path=normalizePath(href);
      var active=(path==="/" ? cur==="/" : cur.indexOf(path)===0);
      if(path!=="/") el.classList.toggle("active", active);
      else el.classList.toggle("active", cur==="/");
    });
  }

  // Preload on hover
  document.addEventListener("mouseover",function(e){
    var a=e.target.closest("[data-link]"); if(!a) return;
    var href=a.getAttribute("href"); if(!href) return;
    var path=normalizePath(href); if(!needsSpa(path)) return; if(cache.has(path)) return;
    setTimeout(function(){
      fetch((APP.base||"")+path+(path.indexOf("?")>-1?"&":"?")+"_ajax=1",{headers:{"X-Requested-With":"XMLHttpRequest"}}).then(r=>r.text()).then(t=>{ cache.set(path,t); }).catch(()=>{});
    },120);
  });

  function initPage(){
    bindMenus();
    bindShare();
    bindCopy();
    bindLazy();
    bindLightbox();
    bindBanner();
    bindSearchLive();
    bindNotices();
    bindScrollSpy();
    document.querySelectorAll("[data-ajax-form]").forEach(bindAjaxForm);
    document.querySelectorAll("[data-link-form]").forEach(function(f){
      if(f.dataset.bound) return; f.dataset.bound="1";
      f.addEventListener("submit",function(e){
        e.preventDefault();
        var fd=new FormData(f);
        var qs=new URLSearchParams(fd).toString();
        var action=f.getAttribute("action")||"/search";
        navigateTo(action+"?"+qs);
      });
    });
    // Close sidebar on page load (mobile)
    if(window.innerWidth<768) closeSidebars();
  }

  if(document.readyState==="loading") document.addEventListener("DOMContentLoaded",initPage); else initPage();
})();
