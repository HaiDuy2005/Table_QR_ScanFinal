/* RestaurantQR – JavaScript nhỏ: hiệu ứng, mã QR, tự làm mới dữ liệu, gửi form không tải lại trang. */
(function () {
  'use strict';
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

  /* ----- Mã QR ----- */
  function renderQR(root) {
    $$('[data-qr]', root).forEach(function (el) {
      if (el.getAttribute('data-done') === el.getAttribute('data-qr')) return;
      el.innerHTML = '';
      if (window.QRCode) {
        new QRCode(el, { text: el.getAttribute('data-qr'), width: +(el.getAttribute('data-size') || 104), height: +(el.getAttribute('data-size') || 104), colorDark: '#050f16', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M });
      }
      el.setAttribute('data-done', el.getAttribute('data-qr'));
    });
  }

  /* ----- Hiện dần khi cuộn tới + số chạy lên ----- */
  function setupReveal() {
    var els = $$('.reveal');
    if (!('IntersectionObserver' in window)) { els.forEach(function (e) { e.classList.add('in'); }); return; }
    var io = new IntersectionObserver(function (es) {
      es.forEach(function (en) {
        if (!en.isIntersecting) return;
        en.target.classList.add('in');
        $$('[data-count]', en.target).concat(en.target.hasAttribute('data-count') ? [en.target] : []).forEach(countUp);
        io.unobserve(en.target);
      });
    }, { threshold: 0.15 });
    els.forEach(function (e) { io.observe(e); });
  }
  function countUp(el) {
    var to = +el.getAttribute('data-count'), t0 = null, dur = 1200;
    function step(t) {
      if (!t0) t0 = t;
      var p = Math.min(1, (t - t0) / dur);
      el.textContent = Math.round(to * (1 - Math.pow(1 - p, 3)));
      if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }
  $$('[data-count-now]').forEach(function (el) { var to = +el.getAttribute('data-count-now'); el.setAttribute('data-count', to); countUp(el); });

  /* ----- Thanh tiến độ cuộn (trang chủ) ----- */
  var bar = $('.scroll-bar');
  if (bar) window.addEventListener('scroll', function () {
    var h = document.documentElement; bar.style.width = (h.scrollTop / (h.scrollHeight - h.clientHeight) * 100) + '%';
  }, { passive: true });

  /* ----- Thẻ nghiêng nhẹ theo chuột ----- */
  var tk = $('.ticket');
  if (tk && matchMedia('(hover:hover)').matches) tk.addEventListener('mousemove', function (e) {
    var r = tk.getBoundingClientRect(), x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5;
    $('.ticket-in', tk).style.transform = 'perspective(1200px) rotateY(' + (x * 8) + 'deg) rotateX(' + (-y * 6) + 'deg)';
  });
  if (tk) tk.addEventListener('mouseleave', function () { $('.ticket-in', tk).style.transform = ''; });

  /* ----- Hộp thoại ----- */
  document.addEventListener('click', function (e) {
    var o = e.target.closest('[data-open]');
    if (o) { var d = document.getElementById(o.getAttribute('data-open')); if (d && d.showModal) { window.pickTable(o.dataset.table || (T[cur] && T[cur].code)); d.showModal(); renderQR(d); } }
    var c = e.target.closest('[data-close]');
    if (c) c.closest('dialog').close();
    var tb = e.target.closest('[data-tab]');
    if (tb) {
      var box = tb.closest('dialog');
      $$('[data-tab]', box).forEach(function (b) { b.classList.toggle('on', b === tb); });
      $$('[data-pane]', box).forEach(function (p) { p.hidden = p.getAttribute('data-pane') !== tb.getAttribute('data-tab'); });
    }
  });
  $$('dialog').forEach(function (d) { d.addEventListener('click', function (e) { if (e.target === d) d.close(); }); });

  /* ----- Chọn bàn trên thẻ ở đầu trang chủ ----- */
  var T = window.TABLES || [];
  var cur = 0;
  function showTable(i) {
    if (!T.length) return;
    cur = (i + T.length) % T.length;
    var t = T[cur];
    var n = $('#pk-num'); if (n) n.textContent = t.num;
    var s = $('#pk-state'); if (s) s.textContent = t.open ? 'Bàn đang mở, vào được ngay' : 'Bàn chưa mở phiên';
    var q = $('#pk-qr'); if (q) { q.setAttribute('data-qr', t.url); renderQR(q.parentNode); }
    var b = $('#pk-try'); if (b) b.dataset.table = t.code;
  }
  window.pickTable = function (code) {
    var t = T.filter(function (x) { return x.code === code; })[0]; if (!t) return;
    $('#dlg-title').textContent = t.name + ' · ' + t.seats + ' chỗ';
    $('#dlg-table').value = t.code;
    $('#dlg-state').innerHTML = t.open ? '<span class="pill pill-pos">Bàn đang mở</span>' : '<span class="pill pill-neu">Chưa mở phiên</span>';
    $('#dlg-qrlink').href = t.join;
    $('#dlg-qr').setAttribute('data-qr', t.url); $('#dlg-qr').removeAttribute('data-done');
    $('#dlg-closed').hidden = t.open; $('#dlg-form').hidden = !t.open;
  };
  $$('[data-step]').forEach(function (b) { b.addEventListener('click', function () { showTable(cur + (+b.getAttribute('data-step'))); }); });
  showTable(0);

  /* ----- Đĩa món ở trang chủ ----- */
  $$('.dtabs button').forEach(function (b) {
    b.addEventListener('click', function () {
      $$('.dtabs button').forEach(function (x) { x.classList.toggle('on', x === b); });
      $$('[data-dish]').forEach(function (p) { p.hidden = p.getAttribute('data-dish') !== b.getAttribute('data-for'); });
    });
  });

  /* ----- Xác nhận trước khi làm việc quan trọng, chống bấm đúp ----- */
  document.addEventListener('submit', function (e) {
    var f = e.target;
    var msg = f.getAttribute('data-confirm') || (e.submitter && e.submitter.getAttribute('data-confirm'));
    if (msg && !window.confirm(msg)) { e.preventDefault(); return; }
    if (f.hasAttribute('data-ajax')) { e.preventDefault(); ajaxPost(f, e.submitter); return; }
    $$('button[type=submit]', f).forEach(function (b) { setTimeout(function () { b.disabled = true; }, 0); });
  });

  /* ----- Làm mới vùng [data-live] mà không tải lại cả trang ----- */
  function swapFrom(html) {
    var doc = new DOMParser().parseFromString(html, 'text/html');
    var nl = $('[data-live]', doc), ol = $('[data-live]');
    if (nl && ol) { ol.innerHTML = nl.innerHTML; renderQR(ol); }
    var nf = $('#flash', doc), of = $('#flash');
    if (nf && of) of.innerHTML = nf.innerHTML;
    var nh = $('[data-live-head]', doc), oh = $('[data-live-head]');
    if (nh && oh) oh.innerHTML = nh.innerHTML;
  }
  function ajaxPost(form, submitter) {
    var fd = new FormData(form);
    if (submitter && submitter.name) fd.append(submitter.name, submitter.value);
    fetch(form.action || location.href, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.text(); }).then(swapFrom).catch(function () { form.removeAttribute('data-ajax'); form.submit(); });
  }
  var sec = +(document.body.getAttribute('data-refresh') || 0);
  function busy() {
    var a = document.activeElement;
    return (a && /^(INPUT|TEXTAREA|SELECT)$/.test(a.tagName) && a.closest('[data-live]')) || $('[data-live] details[open]') || $('dialog[open]');
  }
  if (sec) setInterval(function () {
    if (document.hidden || busy()) return;
    fetch(location.href, { credentials: 'same-origin' }).then(function (r) { return r.text(); }).then(function (h) { if (!busy()) swapFrom(h); }).catch(function () {});
  }, sec * 1000);

  /* ----- Sao chép ----- */
  document.addEventListener('click', function (e) {
    var c = e.target.closest('[data-copy]'); if (!c) return;
    if (navigator.clipboard) navigator.clipboard.writeText(c.getAttribute('data-copy')).then(function () { c.textContent = 'Đã sao chép'; });
  });

  setupReveal();
  renderQR(document);
})();
