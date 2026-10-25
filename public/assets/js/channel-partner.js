/* Channel partner portal. No dependencies. */
(function () {
  'use strict';

  var CP = window.CP || {};
  var $ = function (sel, root) { return (root || document).querySelector(sel); };
  var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };

  /* ---------- Toasts ---------- */
  CP.toast = function (message, level) {
    if (!message) return;
    var wrap = $('#cp-toasts');
    if (!wrap) return;
    var el = document.createElement('div');
    el.className = 'cp-toast ' + (level === 'danger' || level === 'error' ? 'error' : (level === 'warning' ? 'warning' : ''));
    el.textContent = message;
    wrap.appendChild(el);
    setTimeout(function () { el.style.opacity = '0'; el.style.transition = 'opacity .3s'; }, 3500);
    setTimeout(function () { el.remove(); }, 3900);
  };

  /* ---------- Fetch helper ---------- */
  CP.request = function (url, options) {
    options = options || {};
    var headers = { 'X-CSRF-TOKEN': CP.csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
    var body = options.body;
    if (body && !(body instanceof FormData)) {
      headers['Content-Type'] = 'application/json';
      body = JSON.stringify(body);
    }
    return fetch(url, { method: options.method || 'GET', headers: headers, body: body, credentials: 'same-origin' })
      .then(function (res) {
        return res.json().catch(function () { return {}; }).then(function (data) {
          if (!res.ok) {
            var msg = data.message || CP.i18n.error;
            if (data.errors) { var first = Object.keys(data.errors)[0]; msg = data.errors[first][0]; }
            if (res.status === 419) { msg = CP.i18n.error; setTimeout(function () { location.reload(); }, 1200); }
            var err = new Error(msg); err.data = data; throw err;
          }
          return data;
        });
      }, function () { throw new Error(navigator.onLine === false ? CP.i18n.offline : CP.i18n.error); });
  };

  CP.setCartCount = function (count) {
    $$('[data-cart-count]').forEach(function (el) { el.textContent = count; el.setAttribute('data-count', count); });
  };

  function debounce(fn, ms) {
    var t; return function () { var a = arguments, c = this; clearTimeout(t); t = setTimeout(function () { fn.apply(c, a); }, ms); };
  }

  /* ---------- Menus ---------- */
  document.addEventListener('click', function (e) {
    var menu = e.target.closest('[data-cp-menu]');
    $$('[data-cp-menu].open').forEach(function (m) { if (m !== menu) { m.classList.remove('open'); } });
    if (menu && e.target.closest('button[aria-haspopup]')) {
      var open = menu.classList.toggle('open');
      menu.querySelector('button[aria-haspopup]').setAttribute('aria-expanded', open ? 'true' : 'false');
    }
  });

  /* ---------- Password visibility ---------- */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-cp-toggle-password]');
    if (!btn) return;
    var input = btn.parentNode.querySelector('input');
    var show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.setAttribute('aria-pressed', show ? 'true' : 'false');
    $$('svg', btn).forEach(function (svg, i) { svg.style.display = (i === 0) === !show ? '' : 'none'; });
  });

  /* ---------- Prevent double submits ---------- */
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form.hasAttribute('data-cp-ajax') || form.hasAttribute('data-cp-add')) return;
    if (form.dataset.submitting === '1') { e.preventDefault(); return; }
    form.dataset.submitting = '1';
    var btn = form.querySelector('[type="submit"]');
    if (btn) btn.classList.add('is-loading');
    setTimeout(function () { form.dataset.submitting = '0'; if (btn) btn.classList.remove('is-loading'); }, 8000);
  });

  /* ---------- Quantity steppers ---------- */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-cp-step]');
    if (!btn) return;
    var wrap = btn.closest('.cp-qty');
    var input = wrap.querySelector('input');
    var min = parseInt(input.min || '1', 10);
    var step = parseInt(btn.getAttribute('data-cp-step'), 10);
    var value = Math.max(min, (parseInt(input.value, 10) || min) + step);
    if (input.max) value = Math.min(value, parseInt(input.max, 10));
    input.value = value;
    input.dispatchEvent(new Event('change', { bubbles: true }));
  });

  /* ---------- Live price quotes (catalog cards + product page) ---------- */
  var requestQuote = debounce(function (scope) {
    if (!CP.routes.quote) return;
    var qty = parseInt($('[name="quantity"]', scope).value, 10) || 1;
    var variationEl = $('[name="variation"]:checked', scope) || $('[name="variation"]', scope);
    CP.request(CP.routes.quote, {
      method: 'POST',
      body: { product_id: scope.getAttribute('data-product-id'), variation: variationEl ? variationEl.value : '', quantity: qty }
    }).then(function (q) {
      $$('[data-cp-unit]', scope).forEach(function (el) { el.textContent = q.unit_price_formatted; });
      $$('[data-cp-line]', scope).forEach(function (el) { el.textContent = q.line_net_formatted; });
      $$('[data-cp-list]', scope).forEach(function (el) {
        el.textContent = q.list_price_formatted; el.style.display = q.discount_per_unit > 0 ? '' : 'none';
      });
      $$('[data-cp-save]', scope).forEach(function (el) {
        el.textContent = q.line_discount_formatted; (el.closest('[data-cp-save-wrap]') || el).style.display = q.line_discount > 0 ? '' : 'none';
      });
      $$('[data-cp-next]', scope).forEach(function (el) {
        el.textContent = q.next_tier_formatted || ''; el.style.display = q.next_tier_formatted ? '' : 'none';
      });
      $$('[data-cp-stock]', scope).forEach(function (el) { el.textContent = q.stock; });
      $$('[data-cp-tier]', scope).forEach(function (row) {
        var minQty = parseInt(row.getAttribute('data-cp-tier'), 10);
        var next = row.nextElementSibling ? parseInt(row.nextElementSibling.getAttribute('data-cp-tier'), 10) : Infinity;
        row.classList.toggle('active', qty >= minQty && qty < next);
      });
    }).catch(function () { /* keep last good price */ });
  }, 250);

  document.addEventListener('change', function (e) {
    var scope = e.target.closest('[data-cp-quote]');
    if (scope && (e.target.name === 'quantity' || e.target.name === 'variation')) {
      if (e.target.name === 'variation') {
        var qtyInput = $('[name="quantity"]', scope);
        var max = e.target.getAttribute('data-stock');
        if (max && qtyInput) qtyInput.max = max;
      }
      requestQuote(scope);
    }
  });
  document.addEventListener('input', function (e) {
    var scope = e.target.closest('[data-cp-quote]');
    if (scope && e.target.name === 'quantity' && e.target.value) requestQuote(scope);
  });

  /* ---------- Add to cart (AJAX) ---------- */
  document.addEventListener('submit', function (e) {
    var form = e.target.closest('form[data-cp-add]');
    if (!form) return;
    e.preventDefault();
    var btn = form.querySelector('[type="submit"]');
    btn && btn.classList.add('is-loading');
    CP.request(form.action, { method: 'POST', body: new FormData(form) })
      .then(function (data) {
        CP.setCartCount(data.cart_count);
        CP.toast(data.message || CP.i18n.added, 'success');
        var go = form.getAttribute('data-cp-then');
        if (go) location.href = go;
      })
      .catch(function (err) { CP.toast(err.message, 'error'); })
      .finally(function () { btn && btn.classList.remove('is-loading'); });
  });

  /* ---------- Cart page ---------- */
  function applyCart(data) {
    CP.setCartCount(data.cart_count);
    var t = data.totals || {};
    $$('[data-cart-total]').forEach(function (el) {
      var key = el.getAttribute('data-cart-total');
      if (t[key + '_formatted'] !== undefined) el.textContent = t[key + '_formatted'];
      else if (t[key] !== undefined) el.textContent = t[key];
    });
    $$('[data-cart-savings]').forEach(function (el) { el.style.display = t.discount > 0 ? '' : 'none'; });
    Object.keys(data.lines || {}).forEach(function (id) {
      var line = $('[data-cart-line="' + id + '"]');
      if (!line) return;
      var info = data.lines[id];
      var issue = $('[data-line-issue]', line);
      line.classList.toggle('has-issue', !!info.issue);
      if (issue) { issue.textContent = info.issue || ''; issue.style.display = info.issue ? '' : 'none'; }
      if (info.quote) {
        $$('[data-line-unit]', line).forEach(function (el) { el.textContent = info.quote.unit_price_formatted; });
        $$('[data-line-total]', line).forEach(function (el) { el.textContent = info.quote.line_net_formatted; });
        $$('[data-line-list]', line).forEach(function (el) { el.textContent = info.quote.list_price_formatted; el.style.display = info.quote.discount_per_unit > 0 ? '' : 'none'; });
        $$('[data-line-rule]', line).forEach(function (el) { el.textContent = info.quote.rule_label || ''; el.style.display = info.quote.rule_label ? '' : 'none'; });
        $$('[data-line-next]', line).forEach(function (el) { el.textContent = info.quote.next_tier_formatted || ''; el.style.display = info.quote.next_tier_formatted ? '' : 'none'; });
      }
    });
    $$('[data-cart-line]').forEach(function (line) {
      if (!data.lines || !data.lines[line.getAttribute('data-cart-line')]) line.remove();
    });
    var blocked = data.has_issues || data.min_order_shortfall > 0 || data.cart_count === 0;
    $$('[data-cart-checkout]').forEach(function (btn) {
      btn.classList.toggle('disabled', blocked);
      btn.setAttribute('aria-disabled', blocked ? 'true' : 'false');
    });
    $$('[data-cart-min]').forEach(function (el) {
      el.style.display = data.min_order_shortfall > 0 ? '' : 'none';
      var amt = $('[data-cart-min-amount]', el); if (amt) amt.textContent = data.min_order_shortfall_formatted;
    });
    if (data.cart_count === 0) location.reload();
  }

  var updateLine = debounce(function (line, qty) {
    CP.request(line.getAttribute('data-update-url'), { method: 'PATCH', body: { quantity: qty } })
      .then(applyCart)
      .catch(function (err) { CP.toast(err.message, 'error'); });
  }, 350);

  document.addEventListener('change', function (e) {
    var line = e.target.closest('[data-cart-line]');
    if (line && e.target.name === 'line_quantity') {
      var qty = parseInt(e.target.value, 10);
      if (!isNaN(qty) && qty >= 0) updateLine(line, qty);
    }
  });
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-cart-remove]');
    if (!btn) return;
    var line = btn.closest('[data-cart-line]');
    line.style.opacity = '.5';
    CP.request(line.getAttribute('data-update-url'), { method: 'DELETE' })
      .then(function (data) { CP.toast(data.message, 'success'); applyCart(data); })
      .catch(function (err) { line.style.opacity = ''; CP.toast(err.message, 'error'); });
  });
  document.addEventListener('click', function (e) {
    var a = e.target.closest('[data-cart-checkout]');
    if (a && a.classList.contains('disabled')) { e.preventDefault(); }
  });

  /* ---------- Onboarding: document upload ---------- */
  var MAX_IMAGE_EDGE = 2000;

  // Phone photos are often 4-12 MB; shrink them on-device before upload.
  function compressImage(file) {
    return new Promise(function (resolve) {
      if (!/^image\/(jpe?g|png|webp|heic|heif)$/i.test(file.type) || file.size < 900 * 1024 || !window.createImageBitmap) {
        return resolve(file);
      }
      createImageBitmap(file).then(function (bmp) {
        var scale = Math.min(1, MAX_IMAGE_EDGE / Math.max(bmp.width, bmp.height));
        var canvas = document.createElement('canvas');
        canvas.width = Math.round(bmp.width * scale);
        canvas.height = Math.round(bmp.height * scale);
        canvas.getContext('2d').drawImage(bmp, 0, 0, canvas.width, canvas.height);
        canvas.toBlob(function (blob) {
          if (!blob || blob.size >= file.size) return resolve(file);
          var name = file.name.replace(/\.[^.]+$/, '') + '.jpg';
          resolve(new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() }));
        }, 'image/jpeg', 0.82);
      }).catch(function () { resolve(file); });
    });
  }

  function uploadWithProgress(url, formData, onProgress) {
    return new Promise(function (resolve, reject) {
      var xhr = new XMLHttpRequest();
      xhr.open('POST', url);
      xhr.setRequestHeader('X-CSRF-TOKEN', CP.csrf);
      xhr.setRequestHeader('Accept', 'application/json');
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
      xhr.upload.onprogress = function (e) { if (e.lengthComputable) onProgress(Math.round(e.loaded / e.total * 100)); };
      xhr.onload = function () {
        var data = {}; try { data = JSON.parse(xhr.responseText); } catch (err) { /* ignore */ }
        if (xhr.status >= 200 && xhr.status < 300) return resolve(data);
        var msg = data.message || CP.i18n.error;
        if (data.errors) { var k = Object.keys(data.errors)[0]; msg = data.errors[k][0]; }
        if (xhr.status === 413) msg = CP.i18n.error;
        reject(new Error(msg));
      };
      xhr.onerror = function () { reject(new Error(navigator.onLine === false ? CP.i18n.offline : CP.i18n.error)); };
      xhr.send(formData);
    });
  }

  function fileRow(doc) {
    var li = document.createElement('li');
    li.className = 'cp-file';
    li.setAttribute('data-doc-id', doc.id);
    var thumb = doc.is_image
      ? '<img class="cp-file-thumb" src="' + doc.url + '" alt="" loading="lazy">'
      : '<span class="cp-file-thumb"><svg class="cp-icon" viewBox="0 0 24 24"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/></svg></span>';
    li.innerHTML = thumb +
      '<div style="min-width:0;flex:1"><a class="cp-file-name" target="_blank" rel="noopener"></a><div class="cp-file-meta"></div></div>' +
      '<button type="button" class="cp-icon-btn" data-doc-delete aria-label="Remove"><svg class="cp-icon" viewBox="0 0 24 24"><path d="M4 7h16M10 11v6M14 11v6"/><path d="M6 7l1 13a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1l1-13M9 7V4h6v3"/></svg></button>';
    var link = $('.cp-file-name', li);
    link.textContent = doc.name; link.href = doc.url;
    $('.cp-file-meta', li).textContent = doc.size;
    $('[data-doc-delete]', li).setAttribute('data-url', doc.delete_url);
    return li;
  }

  function refreshRequirements(missing) {
    if (!Array.isArray(missing)) return;
    $$('[data-doc-type]').forEach(function (tile) {
      var hasFiles = $$('.cp-file', tile).length > 0;
      tile.classList.toggle('is-complete', hasFiles);
      var state = $('[data-doc-state]', tile);
      if (state) state.style.display = hasFiles ? '' : 'none';
      var pending = $('[data-doc-pending]', tile);
      if (pending) pending.style.display = hasFiles ? 'none' : '';
    });
    var counter = $('[data-docs-missing]');
    if (counter) {
      counter.textContent = missing.length;
      var wrap = counter.closest('[data-docs-missing-wrap]');
      if (wrap) wrap.style.display = missing.length ? '' : 'none';
    }
    $$('[data-docs-next]').forEach(function (btn) {
      btn.classList.toggle('disabled', missing.length > 0);
      btn.setAttribute('aria-disabled', missing.length > 0 ? 'true' : 'false');
    });
  }

  function handleFiles(tile, files) {
    var url = tile.getAttribute('data-upload-url');
    var list = $('.cp-files', tile);
    Array.prototype.forEach.call(files, function (file) {
      var pending = document.createElement('li');
      pending.className = 'cp-file';
      pending.innerHTML = '<span class="cp-file-thumb"><svg class="cp-icon" viewBox="0 0 24 24"><path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/></svg></span>' +
        '<div style="min-width:0;flex:1"><div class="cp-file-name"></div><div class="cp-progress"><span></span></div></div>';
      $('.cp-file-name', pending).textContent = CP.i18n.uploading + ' ' + file.name;
      list.appendChild(pending);
      var bar = $('.cp-progress > span', pending);

      compressImage(file).then(function (ready) {
        var fd = new FormData();
        fd.append('document_type', tile.getAttribute('data-doc-type'));
        fd.append('file', ready, ready.name);
        return uploadWithProgress(url, fd, function (p) { bar.style.width = p + '%'; });
      }).then(function (data) {
        pending.replaceWith(fileRow(data.document));
        refreshRequirements(data.missing);
      }).catch(function (err) {
        pending.remove();
        CP.toast(err.message, 'error');
      });
    });
  }

  $$('[data-doc-type]').forEach(function (tile) {
    $$('input[type="file"]', tile).forEach(function (input) {
      input.addEventListener('change', function () {
        if (input.files && input.files.length) handleFiles(tile, input.files);
        input.value = '';
      });
    });
    ['dragenter', 'dragover'].forEach(function (ev) {
      tile.addEventListener(ev, function (e) { e.preventDefault(); tile.classList.add('is-dragover'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
      tile.addEventListener(ev, function (e) { e.preventDefault(); tile.classList.remove('is-dragover'); });
    });
    tile.addEventListener('drop', function (e) { if (e.dataTransfer && e.dataTransfer.files.length) handleFiles(tile, e.dataTransfer.files); });
  });

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-doc-delete]');
    if (!btn) return;
    if (!window.confirm(CP.i18n.remove_confirm)) return;
    var row = btn.closest('.cp-file');
    row.style.opacity = '.5';
    CP.request(btn.getAttribute('data-url'), { method: 'DELETE' })
      .then(function (data) { row.remove(); refreshRequirements(data.missing); })
      .catch(function (err) { row.style.opacity = ''; CP.toast(err.message, 'error'); });
  });

  document.addEventListener('click', function (e) {
    var a = e.target.closest('[data-docs-next]');
    if (a && a.classList.contains('disabled')) {
      e.preventDefault();
      var first = $('[data-doc-type][data-required="1"]:not(.is-complete)');
      if (first) { first.scrollIntoView({ behavior: 'smooth', block: 'center' }); first.classList.add('is-dragover'); setTimeout(function () { first.classList.remove('is-dragover'); }, 1200); }
    }
  });

  /* ---------- Onboarding: business form saves via AJAX ---------- */
  document.addEventListener('submit', function (e) {
    var form = e.target.closest('form[data-cp-ajax]');
    if (!form) return;
    e.preventDefault();
    var btn = form.querySelector('[type="submit"]');
    btn && btn.classList.add('is-loading');
    $$('.cp-error[data-for]', form).forEach(function (el) { el.remove(); });
    $$('.is-invalid', form).forEach(function (el) { el.classList.remove('is-invalid'); });
    CP.request(form.action, { method: 'POST', body: new FormData(form) })
      .then(function () { location.href = form.getAttribute('data-cp-next'); })
      .catch(function (err) {
        var errors = (err.data && err.data.errors) || {};
        var firstField = null;
        Object.keys(errors).forEach(function (name) {
          var field = form.querySelector('[name="' + name + '"]');
          if (!field) return;
          field.classList.add('is-invalid');
          var msg = document.createElement('div');
          msg.className = 'cp-error'; msg.setAttribute('data-for', name); msg.textContent = errors[name][0];
          field.closest('.cp-field').appendChild(msg);
          firstField = firstField || field;
        });
        if (firstField) { firstField.focus({ preventScroll: true }); firstField.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
        CP.toast(err.message, 'error');
      })
      .finally(function () { btn && btn.classList.remove('is-loading'); });
  });

  /* ---------- Init ---------- */
  document.addEventListener('DOMContentLoaded', function () {
    (CP.flash || []).forEach(function (m) { CP.toast(m.message, m.level); });
  });
  window.addEventListener('offline', function () { CP.toast(CP.i18n.offline, 'warning'); });

  window.CP = CP;
})();
