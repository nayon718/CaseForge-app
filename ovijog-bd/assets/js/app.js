(function () {
  "use strict";

  /* =============================================
   * ওভিজোগ বিডি — SPA Frontend Engine
   * ============================================= */
  const APP = window.OVJOG_APP || { base: "", isAdmin: false };

  /* ---------- ছোট হেল্পার ---------- */
  function escapeHtml(s) {
    return String(s || "")
      .replace(/[&<>"']/g, function (c) {
        return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
      });
  }
  function normalizePath(path) {
    var u = new URL(path, location.origin);
    return u.pathname.replace(/\/+$/, "") + u.search || "/";
  }
  function toast(msg, type) {
    type = type || "success";
    var box = document.getElementById("toastBox");
    if (!box || !msg) return;
    var el = document.createElement("div");
    el.className = "toast " + type;
    el.innerHTML =
      '<i class="fa-solid ' + (type === "error" ? "fa-circle-exclamation" : "fa-circle-check") + '"></i>' +
      "<div>" + escapeHtml(msg) + "</div>" +
      '<button class="toast-close"><i class="fa-solid fa-xmark"></i></button>';
    box.appendChild(el);
    el.querySelector(".toast-close").addEventListener("click", function () { el.remove(); });
    setTimeout(function () { el.remove(); }, 5200);
  }

  /* ---------- প্রগ্রেস ও লোডার ---------- */
  var progressBar = document.getElementById("topProgressBar");
  var progress = {
    value: 0,
    set: function (v) {
      if (!progressBar) return;
      this.value = Math.max(this.value, Math.min(100, v));
      progressBar.style.opacity = "1";
      progressBar.style.width = this.value + "%";
      if (this.value >= 100) {
        var self = this;
        setTimeout(function () {
          self.value = 0;
          progressBar.style.width = "0%";
          progressBar.style.opacity = "0";
        }, 240);
      }
    }
  };
  function loader(show) {
    var el = document.getElementById("appLoader");
    if (!el) return;
    if (show) el.classList.add("show");
    else setTimeout(function () { el.classList.remove("show"); }, 180);
  }

  /* ---------- SPA cache & history ---------- */
  var cache = new Map();
  var pageEl = document.getElementById("pageContent");
  var oldPushState = history.pushState;
  var oldReplaceState = history.replaceState;

  function needsSpa(path) {
    if (APP.isAdmin) return false;
    var p = normalizePath(path);
    if (/^(\/admin|\/elola)\b/.test(p)) return false;
    return true;
  }

  history.pushState = function (state, title, url) {
    oldPushState.call(this, state, title, url);
    appLocationChanged(url, false);
  };
  history.replaceState = function (state, title, url) {
    oldReplaceState.call(this, state, title, url);
    appLocationChanged(url, true);
  };

  function appLocationChanged(rawUrl, replace) {
    var path = normalizePath(rawUrl);
    if (!needsSpa(path)) return;
    if (cache.has(path)) {
      showContent(cache.get(path).html, cache.get(path).title);
      return;
    }
    loadPage(path, true, replace);
  }

  async function loadPage(path, attach, replace, noCache) {
    if (!needsSpa(path)) { location.href = path; return false; }
    progress.set(10);
    var url = (APP.base || "") + path + (path.indexOf("?") > -1 ? "&" : "?") + "_ajax=1";
    try {
      var res = await fetch(url, { headers: { "X-Requested-With": "XMLHttpRequest" } });
      if (res.status === 404) {
        pageEl.innerHTML = await res.text();
        return true;
      }
      if (!res.ok) throw new Error("server");
      var text = await res.text();
      cache.set(path, { html: text, title: document.title });
      showContent(text, document.title);
      return true;
    } catch (err) {
      toast("পেজ লোড করা যায়নি।", "error");
      return false;
    } finally {
      progress.set(100);
      loader(false);
    }
  }

  function showContent(html, title) {
    pageEl.innerHTML = html;
    var holder = pageEl.parentElement;
    if (holder) holder.classList.add("fade-out");
    setTimeout(function () {
      if (holder) {
        holder.classList.remove("fade-out");
        holder.classList.add("fade-in");
      }
      pageEl.dispatchEvent(new Event("page:loaded", { bubbles: true }));
      initPage();
      setTimeout(function () {
        if (holder) holder.classList.remove("fade-in");
      }, 400);
    }, 120);
    if (title) document.title = title;
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  window.addEventListener("popstate", function () {
    var path = normalizePath(location.href);
    if (cache.has(path)) {
      showContent(cache.get(path).html, cache.get(path).title);
    } else {
      loadPage(path, false, true);
    }
  });

  function navigateTo(path) {
    if (!needsSpa(path)) { location.href = path; return; }
    var clean = normalizePath(path);
    if (clean === normalizePath(location.href)) { initPage(); return; }
    history.pushState(null, "", clean);
    appLocationChanged(clean, false);
  }

  document.addEventListener("click", function (e) {
    var el = e.target.closest("[data-link]");
    if (!el) return;
    var href = el.getAttribute("href");
    if (!href) return;
    var u = new URL(href, location.origin);
    if (u.origin !== location.origin) return;
    if (el.getAttribute("target") === "_blank") return;
    e.preventDefault();
    navigateTo(u.pathname + u.search);
  });

  /* ---------- পেজ ইনিশিয়ালাইজ ---------- */
  function initPage() {
    bindMenus();
    bindReadMore();
    bindShare();
    bindGallery();
    bindUploads();
    bindLazyImages();
    bindCopyButtons();
    bindSearch();
    bindSelectedPayment();
    bindScrollSpy();
    document.querySelectorAll("[data-ajax-form]").forEach(bindAjaxForm);
    document.querySelectorAll("[data-idle-reset-form]").forEach(function (b) {
      b.addEventListener("click", function () {
        var f = b.closest("form");
        if (f) f.reset();
        document.querySelectorAll(".upload-preview").forEach(function (p) { p.innerHTML = ""; });
        f.querySelectorAll('input[name="report_images"], input[name="payment_screenshot"]').forEach(function (h) { h.remove(); });
      });
    });
    document.querySelectorAll("[data-search-reset]").forEach(function (b) {
      b.addEventListener("click", function () {
        var f = b.closest("#searchForm");
        if (f) f.reset();
        var r = document.getElementById("searchResults");
        if (r) r.innerHTML = "";
      });
    });
  }

  function bindMenus() {
    var trigger = document.getElementById("menuTrigger");
    var side = document.getElementById("siteSidebar") || document.getElementById("adminSidebar");
    if (trigger && side) {
      trigger.onclick = function () {
        side.classList.add("open");
        document.body.classList.add("menu-open");
      };
    }
    if (side) {
      side.querySelectorAll("[data-close-side]").forEach(function (b) {
        b.addEventListener("click", function () {
          side.classList.remove("open");
          document.body.classList.remove("menu-open");
        });
      });
    }
  }

  function bindReadMore() {
    document.querySelectorAll(".read-more-btn").forEach(function (btn) {
      if (btn.dataset.bound) return;
      btn.dataset.bound = "1";
      btn.addEventListener("click", function () {
        var card = btn.closest(".report-card");
        if (!card) return;
        var details = card.querySelector(".report-details");
        if (!details) return;
        var open = details.classList.toggle("collapsed");
        btn.classList.toggle("open", !open);
        var span = btn.querySelector("span");
        if (span) span.textContent = open ? "আরও পড়ুন" : "কম দেখুন";
      });
    });
  }

  function bindShare() {
    var triggers = document.querySelectorAll(".share-btn");
    triggers.forEach(function (btn) {
      if (btn.dataset.bound) return;
      btn.dataset.bound = "1";
      btn.addEventListener("click", function () {
        var card = btn.closest(".report-card");
        if (!card) return;
        var pop = card.querySelector(".share-popup");
        if (pop) pop.hidden = !pop.hidden;
        if (document.activeElement) document.activeElement.blur();
      });
    });
    document.addEventListener("click", function (e) {
      if (!e.target.closest(".share-btn") && !e.target.closest(".share-popup")) {
        document.querySelectorAll(".share-popup").forEach(function (p) { p.hidden = true; });
      }
    });
  }

  function bindCopyButtons() {
    document.querySelectorAll("[data-copy]").forEach(function (btn) {
      if (btn.dataset.bound) return;
      btn.dataset.bound = "1";
      btn.addEventListener("click", function () {
        copyText(btn.getAttribute("data-copy") || "");
        toast("কপি হয়েছে");
      });
    });
  }

  function copyText(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).catch(function () {});
    } else {
      var t = document.createElement("textarea");
      t.value = text;
      t.style.position = "fixed";
      t.style.opacity = "0";
      document.body.appendChild(t);
      t.select();
      try { document.execCommand("copy"); } catch (e) {}
      t.remove();
    }
  }

  function collectGalleryImages(g) {
    var imgs = g.querySelectorAll(".gallery-main img, .gallery-thumb img");
    var out = [];
    imgs.forEach(function (i) { if (i.src) out.push(i.src); });
    return out;
  }

  function bindGallery() {
    var open = document.getElementById("lightbox");
    if (!open) return;
    var lightboxImg = document.getElementById("lightboxImg");
    var list = [], idx = 0;
    function show(i) {
      idx = (i + list.length) % list.length;
      lightboxImg.src = list[idx];
      lightboxImg.alt = "ছবি " + (idx + 1);
    }
    document.querySelectorAll(".report-gallery").forEach(function (g) {
      if (g.dataset.bound) return;
      g.dataset.bound = "1";
      g.querySelectorAll("[data-lightbox]").forEach(function (b) {
        b.addEventListener("click", function () {
          list = collectGalleryImages(g);
          idx = parseInt(b.getAttribute("data-lightbox") || "0", 10) || 0;
          show(idx);
          open.classList.add("show");
        });
      });
    });
    open.addEventListener("click", function (e) {
      if (e.target === open || e.target.classList.contains("lb-close")) open.classList.remove("show");
    });
    document.querySelectorAll("[data-lb-close]").forEach(function (b) {
      b.addEventListener("click", function () { open.classList.remove("show"); });
    });
    document.querySelectorAll("[data-lb-prev]").forEach(function (b) {
      b.addEventListener("click", function () { show(idx - 1); });
    });
    document.querySelectorAll("[data-lb-next]").forEach(function (b) {
      b.addEventListener("click", function () { show(idx + 1); });
    });
    document.addEventListener("keydown", function (e) {
      if (!open.classList.contains("show")) return;
      if (e.key === "Escape") open.classList.remove("show");
      if (e.key === "ArrowLeft") show(idx - 1);
      if (e.key === "ArrowRight") show(idx + 1);
    });
  }

  function bindLazyImages() {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        var i = en.target;
        if (i.dataset.src) {
          i.src = i.dataset.src;
          i.removeAttribute("data-src");
        }
        io.unobserve(i);
      });
    }, { rootMargin: "120px" });
    document.querySelectorAll("img[data-src]").forEach(function (i) { io.observe(i); });
  }

  function bindAjaxForm(form) {
    if (form.dataset.bound) return;
    form.dataset.bound = "1";
    form.addEventListener("submit", async function (e) {
      e.preventDefault();
      if (form.querySelectorAll("input[required]").length && !form.checkValidity()) {
        form.reportValidity();
        return;
      }
      var submit = form.querySelector("button[type=submit]");
      if (submit) {
        submit.disabled = true;
        submit.dataset.old = submit.innerHTML;
        submit.innerHTML = '<span class="loader-spinner" style="width:18px;height:18px;border-width:2px;display:inline-block;vertical-align:middle"></span> অপেক্ষা করুন...';
      }
      var fd = new FormData(form);
      var csrf = form.querySelector('[name="csrf_token"]');
      if (csrf) fd.append("csrf_token", csrf.value);
      var url = form.getAttribute("data-url") || form.getAttribute("action") || location.href;
      var submitUrl = (url.indexOf("http") === 0 ? "" : (APP.base || "")) + url;
      try {
        var res = await fetch(submitUrl, {
          method: "POST",
          headers: { "X-Requested-With": "XMLHttpRequest" },
          body: fd
        });
        var json = await res.json().catch(function () {
          return { ok: false, message: "সার্ভার থেকে ভুল উত্তর এসেছে" };
        });
        if (json.ok) {
          toast(json.message || "সফল হয়েছে");
          var rep = json.data && json.data.report_no;
          if (rep && form.id === "reportForm") {
            form.reset();
            document.querySelectorAll(".upload-preview").forEach(function (p) { p.innerHTML = ""; });
            var success = document.createElement("div");
            success.className = "card";
            success.style.cssText = "text-align:center;padding:34px;margin-top:20px";
            success.innerHTML =
              '<i class="fa-solid fa-circle-check" style="font-size:48px;color:var(--success)"></i>' +
              "<h2 style=\"margin:12px 0\">রিপোর্ট জমা সফল হয়েছে</h2>" +
              "<p style=\"color:var(--muted)\">আপনার রিপোর্ট নম্বর: <strong style=\"font-family:var(--font-en)\">" +
              escapeHtml(rep) + "</strong></p>" +
              "<div style=\"display:flex;gap:10px;justify-content:center;margin-top:18px;flex-wrap:wrap\">" +
              '<a href="' + (APP.base || "") + '/" data-link class="btn btn-primary">হোম এ ফিরে যান</a>' +
              '<a href="' + (APP.base || "") + '/report" data-link class="btn btn-soft">নতুন রিপোর্ট</a></div>';
            form.replaceWith(success);
            initPage();
            return;
          }
          if (json.data && json.data.redirect) {
            location.href = json.data.redirect;
            return;
          }
        } else {
          toast(json.message || "সমস্যা হয়েছে", "error");
        }
      } catch (err) {
        toast("নেটওয়ার্ক ত্রুটি হয়েছে", "error");
      } finally {
        if (submit) {
          submit.disabled = false;
          submit.innerHTML = submit.dataset.old;
        }
      }
    });
  }

  /* ---------- ছবি আপলোড + কম্প্রেস ---------- */
  function compressImage(file, maxDim) {
    return new Promise(function (resolve) {
      var reader = new FileReader();
      reader.onload = function () {
        var img = new Image();
        img.onload = function () {
          var w = img.width, h = img.height;
          if (Math.max(w, h) > maxDim) {
            var scale = maxDim / Math.max(w, h);
            w = Math.round(w * scale);
            h = Math.round(h * scale);
          }
          var canvas = document.createElement("canvas");
          canvas.width = w;
          canvas.height = h;
          var ctx = canvas.getContext("2d");
          ctx.drawImage(img, 0, 0, w, h);
          resolve(canvas.toDataURL("image/jpeg", 0.78));
        };
        img.src = reader.result;
      };
      reader.readAsDataURL(file);
    });
  }

  function bindUploads() {
    var reportInput = document.getElementById("reportPhotos");
    if (reportInput && !reportInput.dataset.bound) {
      reportInput.dataset.bound = "1";
      reportInput.addEventListener("change", async function () {
        var files = Array.prototype.slice.call(reportInput.files).slice(0, 5);
        var preview = document.getElementById("reportPreview");
        var form = reportInput.closest("form");
        if (!form) return;
        var compressed = [];
        if (preview) preview.innerHTML = "";
        for (var i = 0; i < files.length; i++) {
          var f = files[i];
          if (f.type.indexOf("image/") !== 0 || f.size > 5242880) {
            toast("শুধুমাত্র ১-৫টি ছবি (প্রতিটি সর্বোচ্চ ৫MB) অনুমোদিত", "error");
            continue;
          }
          if (f.size > 5242880) {
            toast("একটি ছবির সাইজ ৫MB এর বেশি", "error");
            continue;
          }
          var data = await compressImage(f, 900);
          compressed.push(data);
          if (preview) {
            var t = document.createElement("div");
            t.className = "up-thumb";
            t.innerHTML = '<img src="' + data + '"><button type="button" class="up-remove"><i class="fa-solid fa-xmark"></i></button>';
            t.querySelector(".up-remove").addEventListener("click", function () { t.remove(); });
            preview.appendChild(t);
          }
        }
        if (compressed.length) {
          var old = form.querySelector('input[name="report_images"]');
          if (old) old.remove();
          var hidden = document.createElement("input");
          hidden.type = "hidden";
          hidden.name = "report_images";
          hidden.value = JSON.stringify(compressed);
          form.appendChild(hidden);
        }
      });
    }

    var payInput = document.getElementById("paymentScreenshot");
    if (payInput && !payInput.dataset.bound) {
      payInput.dataset.bound = "1";
      payInput.addEventListener("change", async function () {
        var f = payInput.files[0];
        if (!f) return;
        if (f.size > 2097152) { toast("পেমেন্ট স্ক্রিনশট সর্বোচ্চ ২MB", "error"); return; }
        if (f.type.indexOf("image/") !== 0) { toast("ছবি ফাইল দিন", "error"); return; }
        var data = await compressImage(f, 700);
        var preview = document.getElementById("paymentPreview");
        if (preview) preview.innerHTML = '<div class="up-thumb"><img src="' + data + '"></div>';
        var form = payInput.closest("form");
        if (form) {
          var old = form.querySelector('input[name="payment_screenshot"]');
          if (old) old.remove();
          var hidden = document.createElement("input");
          hidden.type = "hidden";
          hidden.name = "payment_screenshot";
          hidden.value = data;
          form.appendChild(hidden);
        }
      });
    }
  }

  function bindSearch() {
    var form = document.getElementById("searchForm");
    if (!form || form.dataset.searchBound) return;
    form.dataset.searchBound = "1";
    form.addEventListener("submit", async function (e) {
      e.preventDefault();
      var btn = form.querySelector("button[type=submit]");
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="loader-spinner" style="width:18px;height:18px;border-width:2px"></span> খোঁজা হচ্ছে...';
      }
      var fd = new FormData(form);
      try {
        var searchUrl = form.getAttribute("action") || form.getAttribute("data-url") || "/api/search";
        if (searchUrl.indexOf("http") !== 0) searchUrl = (APP.base || "") + searchUrl;
        var res = await fetch(searchUrl, {
          method: "POST",
          headers: { "X-Requested-With": "XMLHttpRequest" },
          body: fd
        });
        var json = await res.json();
        var el = document.getElementById("searchResults");
        if (json.ok) {
          if (el) el.innerHTML = json.html || (json.data && json.data.html) || "";
          bindReadMore();
          bindShare();
          bindGallery();
          bindLazyImages();
          toast(json.message || "সার্চ শেষ");
        } else {
          if (el) el.innerHTML = '<div class="results-empty"><i class="fa-solid fa-magnifying-glass"></i><h3>' + escapeHtml(json.message || "রিপোর্ট খুঁজে পাওয়া যায় নাই") + "</h3></div>";
          toast(json.message || "রিপোর্ট খুঁজে পাওয়া যায় নাই", "error");
        }
      } catch (err) {
        toast("সার্চ করা যায়নি", "error");
      } finally {
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = '<i class="fa-solid fa-magnifying-glass-location"></i> তথ্য খুঁজুন';
        }
      }
    });
  }

  function bindSelectedPayment() {
    var sel = document.querySelector('select[name="payment_method_id"]');
    var box = document.getElementById("selectedPayment");
    if (!sel || !box) return;
    sel.addEventListener("change", function () {
      var card = document.querySelector('.pay-card[data-method-id="' + sel.value + '"]');
      if (card) {
        box.innerHTML =
          '<i class="fa-solid fa-circle-check"></i> ' + escapeHtml(card.dataset.name) +
          " এ পেমেন্ট করুন: <strong>" + escapeHtml(card.dataset.address) + "</strong>";
        box.classList.add("show");
      } else {
        box.classList.remove("show");
      }
    });
  }

  function bindScrollSpy() {
    var paths = ["/", "/report", "/search", "/leaderboard", "/donation"];
    var cur = normalizePath(location.href);
    paths.forEach(function (p) {
      var selA = '[href="' + p + '"]';
      document.querySelectorAll(".bottom-link" + selA + ", .side-link" + selA).forEach(function (el) {
        var active = (p === "/") ? (cur === "/" || cur === "") : cur.indexOf(p) === 0;
        el.classList.toggle("active", active);
      });
    });
  }

  /* ---------- Preload / Prefetch ---------- */
  document.addEventListener("mouseover", function (e) {
    var a = e.target.closest("[data-link]");
    if (!a) return;
    var href = a.getAttribute("href");
    if (!href) return;
    var path = normalizePath(href);
    if (!needsSpa(path)) return;
    if (cache.has(path)) return;
    setTimeout(function () {
      fetch((APP.base || "") + path + (path.indexOf("?") > -1 ? "&" : "?") + "_ajax=1", {
        headers: { "X-Requested-With": "XMLHttpRequest" }
      }).then(function (r) { return r.text(); }).then(function (t) {
        cache.set(path, { html: t, title: document.title });
      }).catch(function () {});
    }, 120);
  });

  /* ---------- স্টার্ট ---------- */
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initPage);
  } else {
    initPage();
  }
})();
