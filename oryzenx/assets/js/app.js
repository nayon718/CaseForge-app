/* Oryzenx — AH NAYON portfolio front-end */
(function () {
  'use strict';
  const $ = (s, c) => (c || document).querySelector(s);
  const $$ = (s, c) => Array.from((c || document).querySelectorAll(s));
  const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- Header, nav, progress ---------- */
  const header = $('#siteHeader'), nav = $('#navMenu'), toggle = $('#navToggle'), toTop = $('#toTop'), bar = $('#scrollProgress');

  function onScroll() {
    const y = scrollY;
    header && header.classList.toggle('scrolled', y > 30);
    toTop && toTop.classList.toggle('show', y > 500);
    if (bar) {
      const h = document.documentElement.scrollHeight - innerHeight;
      bar.style.width = (h > 0 ? (y / h) * 100 : 0) + '%';
    }
    // active link
    let cur = 'home';
    $$('section[id]').forEach(s => { if (y >= s.offsetTop - 140) cur = s.id; });
    $$('.nav-link').forEach(a => a.classList.toggle('active', a.dataset.nav === cur));
  }
  addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  toggle && toggle.addEventListener('click', () => {
    const open = nav.classList.toggle('open');
    toggle.classList.toggle('open', open);
    toggle.setAttribute('aria-expanded', String(open));
  });
  $$('.nav a').forEach(a => a.addEventListener('click', () => {
    nav.classList.remove('open'); toggle && toggle.classList.remove('open');
  }));

  const yearEl = $('#year'); if (yearEl) yearEl.textContent = new Date().getFullYear();

  /* ---------- Reveal on scroll ---------- */
  const io = new IntersectionObserver(es => es.forEach(e => {
    if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
  }), { threshold: 0.12, rootMargin: '0px 0px -60px' });
  $$('.reveal').forEach(el => io.observe(el));

  /* ---------- Counters ---------- */
  const cio = new IntersectionObserver(es => es.forEach(e => {
    if (!e.isIntersecting) return;
    const el = e.target, target = +el.dataset.count, t0 = performance.now(), dur = 1400;
    (function step(t) {
      const p = Math.min((t - t0) / dur, 1);
      el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3)));
      if (p < 1) requestAnimationFrame(step);
    })(t0);
    cio.unobserve(el);
  }), { threshold: 0.4 });
  $$('[data-count]').forEach(el => cio.observe(el));

  /* ---------- Skill bars ---------- */
  const bio = new IntersectionObserver(es => es.forEach(e => {
    if (e.isIntersecting) { e.target.classList.add('on'); bio.unobserve(e.target); }
  }), { threshold: 0.5 });
  $$('.bar-rail').forEach(el => bio.observe(el));

  /* ---------- Typing effect ---------- */
  const typedEl = $('#typedText');
  if (typedEl) {
    const words = ['Mechanical Engineering', 'Cybersecurity', 'Web Development', 'Programming', 'UI / UX Design'];
    let w = 0, i = 0, del = false;
    (function tick() {
      const word = words[w];
      typedEl.textContent = word.slice(0, i);
      if (!del && i < word.length) { i++; setTimeout(tick, 75); }
      else if (!del) { del = true; setTimeout(tick, 1500); }
      else if (i > 0) { i--; setTimeout(tick, 38); }
      else { del = false; w = (w + 1) % words.length; setTimeout(tick, 300); }
    })();
  }

  /* ---------- Marquee (duplicate for seamless loop) ---------- */
  $$('.marquee').forEach(m => {
    const track = $('.marquee-track', m);
    track.innerHTML += track.innerHTML;
    track.style.setProperty('--dur', (m.dataset.speed || 38) + 's');
  });

  /* ---------- Contact form (AJAX, no reload) ---------- */
  const form = $('#contactForm'), note = $('#formNote');
  form && form.addEventListener('submit', async ev => {
    ev.preventDefault();
    const btn = $('button[type=submit]', form);
    note.className = 'form-note'; note.textContent = '';
    if (!form.name.value.trim() || !form.email.value.trim() || !form.message.value.trim()) {
      note.className = 'form-note err'; note.textContent = 'Please fill in name, email and message.'; return;
    }
    btn.disabled = true;
    const old = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Sending...';
    try {
      const res = await fetch(form.action, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'fetch' },
        body: new FormData(form)
      });
      const data = await res.json().catch(() => ({ ok: false, message: 'Unexpected server response.' }));
      note.className = 'form-note ' + (data.ok ? 'ok' : 'err');
      note.textContent = data.message || (data.ok ? 'Message sent!' : 'Could not send message.');
      if (data.ok) form.reset();
    } catch (err) {
      note.className = 'form-note err';
      note.textContent = 'Network error. Please use WhatsApp or email instead.';
    } finally { btn.disabled = false; btn.innerHTML = old; }
  });

  /* ---------- Premium Leaflet map ---------- */
  const mapEl = $('#map');
  if (mapEl && window.L) {
    const LAT = 25.3419, LNG = 89.5085;
    const layers = {
      street: L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
        { maxZoom: 19, attribution: '© OpenStreetMap, © CARTO' }),
      satellite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        { maxZoom: 19, attribution: '© Esri' }),
      terrain: L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
        { maxZoom: 17, attribution: '© OpenTopoMap' })
    };
    const map = L.map(mapEl, { center: [LAT, LNG], zoom: 13, scrollWheelZoom: false, layers: [layers.street] });
    map.on('click', () => map.scrollWheelZoom.enable());
    map.on('mouseout', () => map.scrollWheelZoom.disable());
    L.control.scale({ imperial: false }).addTo(map);

    const icon = L.divIcon({ className: '', html: '<div class="pin"><i class="fa-solid fa-location-dot"></i></div>', iconSize: [38, 38], iconAnchor: [19, 19] });
    L.marker([LAT, LNG], { icon, title: 'Sundarganj, Gaibandha' }).addTo(map)
      .bindPopup('<b>AH NAYON</b><br>Sundarganj, Gaibandha<br>Rangpur, Bangladesh<br><a href="https://www.google.com/maps/dir/?api=1&destination=' + LAT + ',' + LNG + '" target="_blank" rel="noopener">Get Directions →</a>')
      .openPopup();
    L.circle([LAT, LNG], { radius: 2500, color: '#2563eb', weight: 1.5, fillColor: '#2563eb', fillOpacity: .08 }).addTo(map);

    $$('.mt[data-map]').forEach(b => b.addEventListener('click', () => {
      $$('.mt[data-map]').forEach(x => x.setAttribute('aria-pressed', 'false'));
      b.setAttribute('aria-pressed', 'true');
      Object.values(layers).forEach(l => map.removeLayer(l));
      map.addLayer(layers[b.dataset.map]);
    }));
    $('#mapLocate') && $('#mapLocate').addEventListener('click', () => map.flyTo([LAT, LNG], 14, { duration: 1 }));
    const wrap = $('.map-wrap'), fsBtn = $('#mapFull');
    fsBtn && fsBtn.addEventListener('click', () => {
      wrap.classList.toggle('fs');
      fsBtn.innerHTML = wrap.classList.contains('fs') ? '<i class="fa-solid fa-compress"></i>' : '<i class="fa-solid fa-expand"></i>';
      setTimeout(() => map.invalidateSize(), 260);
    });
    addEventListener('keydown', e => {
      if (e.key === 'Escape' && wrap.classList.contains('fs')) fsBtn.click();
    });
    new IntersectionObserver(es => es.forEach(e => e.isIntersecting && setTimeout(() => map.invalidateSize(), 100)))
      .observe(mapEl);
  }

  /* ---------- 3D animated background (canvas, no libs) ---------- */
  const cv = $('#bg3d');
  if (cv && !reduced) {
    const ctx = cv.getContext('2d');
    let W, H, DPR, t = 0;
    const mouse = { x: 0, y: 0 };
    addEventListener('mousemove', e => { mouse.x = (e.clientX / innerWidth - .5); mouse.y = (e.clientY / innerHeight - .5); }, { passive: true });

    function resize() {
      DPR = Math.min(devicePixelRatio || 1, 2);
      W = cv.width = innerWidth * DPR; H = cv.height = innerHeight * DPR;
      cv.style.width = innerWidth + 'px'; cv.style.height = innerHeight + 'px';
    }
    resize(); addEventListener('resize', resize);

    // objects: gears (mechanical), chips (computer), code brackets (programming)
    const rnd = (a, b) => a + Math.random() * (b - a);
    const kinds = ['gear', 'chip', 'code', 'node'];
    const items = Array.from({ length: 26 }, (_, i) => ({
      kind: kinds[i % kinds.length],
      x: Math.random(), y: Math.random(), z: rnd(.35, 1.4),
      r: rnd(16, 52), a: Math.random() * Math.PI * 2, sp: rnd(-.006, .006),
      vy: rnd(-.00022, -.00006), teeth: Math.round(rnd(8, 14)),
      hue: [216, 190, 25][i % 3]
    }));

    function gear(x, y, r, a, teeth, col) {
      ctx.save(); ctx.translate(x, y); ctx.rotate(a);
      ctx.strokeStyle = col; ctx.lineWidth = Math.max(1, r * .09); ctx.beginPath();
      for (let i = 0; i < teeth; i++) {
        const a0 = (i / teeth) * Math.PI * 2, a1 = a0 + Math.PI / teeth * .55, a2 = a0 + Math.PI / teeth;
        ctx.lineTo(Math.cos(a0) * r, Math.sin(a0) * r);
        ctx.lineTo(Math.cos(a1) * r * 1.24, Math.sin(a1) * r * 1.24);
        ctx.lineTo(Math.cos(a2) * r, Math.sin(a2) * r);
      }
      ctx.closePath(); ctx.stroke();
      ctx.beginPath(); ctx.arc(0, 0, r * .38, 0, Math.PI * 2); ctx.stroke();
      ctx.restore();
    }
    function chip(x, y, r, a, col) {
      ctx.save(); ctx.translate(x, y); ctx.rotate(a);
      ctx.strokeStyle = col; ctx.lineWidth = Math.max(1, r * .07);
      ctx.strokeRect(-r * .7, -r * .7, r * 1.4, r * 1.4);
      ctx.strokeRect(-r * .34, -r * .34, r * .68, r * .68);
      for (let i = -2; i <= 2; i++) {
        const p = i * r * .27;
        ctx.beginPath(); ctx.moveTo(p, -r * .7); ctx.lineTo(p, -r); ctx.stroke();
        ctx.beginPath(); ctx.moveTo(p, r * .7); ctx.lineTo(p, r); ctx.stroke();
        ctx.beginPath(); ctx.moveTo(-r * .7, p); ctx.lineTo(-r, p); ctx.stroke();
        ctx.beginPath(); ctx.moveTo(r * .7, p); ctx.lineTo(r, p); ctx.stroke();
      }
      ctx.restore();
    }
    function code(x, y, r, a, col) {
      ctx.save(); ctx.translate(x, y); ctx.rotate(a * .3);
      ctx.strokeStyle = col; ctx.lineWidth = Math.max(1.2, r * .1); ctx.lineCap = 'round';
      ctx.beginPath(); ctx.moveTo(-r * .25, -r * .6); ctx.lineTo(-r * .8, 0); ctx.lineTo(-r * .25, r * .6); ctx.stroke();
      ctx.beginPath(); ctx.moveTo(r * .25, -r * .6); ctx.lineTo(r * .8, 0); ctx.lineTo(r * .25, r * .6); ctx.stroke();
      ctx.beginPath(); ctx.moveTo(r * .1, -r * .7); ctx.lineTo(-r * .1, r * .7); ctx.stroke();
      ctx.restore();
    }
    function node(x, y, r, a, col) {
      ctx.save(); ctx.translate(x, y); ctx.rotate(a);
      ctx.strokeStyle = col; ctx.fillStyle = col; ctx.lineWidth = Math.max(1, r * .06);
      for (let i = 0; i < 3; i++) {
        const ang = a + i * Math.PI * 2 / 3, px = Math.cos(ang) * r * .7, py = Math.sin(ang) * r * .7;
        ctx.beginPath(); ctx.moveTo(0, 0); ctx.lineTo(px, py); ctx.stroke();
        ctx.beginPath(); ctx.arc(px, py, r * .13, 0, Math.PI * 2); ctx.fill();
      }
      ctx.beginPath(); ctx.arc(0, 0, r * .18, 0, Math.PI * 2); ctx.fill();
      ctx.restore();
    }

    let last = 0;
    function frame(now) {
      requestAnimationFrame(frame);
      if (now - last < 26) return;         // ~38fps cap, keeps it light
      last = now; t += 1;
      ctx.clearRect(0, 0, W, H);
      items.forEach(o => {
        o.a += o.sp; o.y += o.vy;
        if (o.y < -.15) { o.y = 1.15; o.x = Math.random(); }
        const depth = o.z;
        const px = (o.x * W) + mouse.x * 70 * depth * DPR;
        const py = (o.y * H) + mouse.y * 50 * depth * DPR;
        const r = o.r * depth * DPR;
        const alpha = 0.05 + 0.11 * (1.4 - depth);
        const col = `hsla(${o.hue},85%,52%,${alpha.toFixed(3)})`;
        if (o.kind === 'gear') gear(px, py, r, o.a, o.teeth, col);
        else if (o.kind === 'chip') chip(px, py, r, o.a, col);
        else if (o.kind === 'code') code(px, py, r, o.a, col);
        else node(px, py, r, o.a, col);
      });
    }
    requestAnimationFrame(frame);
  }
})();
