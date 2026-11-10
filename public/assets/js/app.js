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