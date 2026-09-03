/**
 * Aracari — UI Interaction Layer
 * -------------------------------------------------------------
 * Vanilla JS engine for the Aracari design system in Laravel Blade.
 *
 * Responsibilities:
 *  - Modal open/close (overlay, ESC, data attributes)
 *  - AJAX form submit inside modals (no full page reload)
 *  - Display Laravel 422 validation errors inside the modal
 *  - Delete confirmation modal (replaces window.confirm)
 *  - Refresh the data table partial (#data-table-card) after success
 *  - Toast notifications
 *  - Dual-mode search engine (client | server) — see "SEARCH ENGINE" below
 *  - Live form preview sync (data-preview targets)
 *
 * SEARCH ENGINE (declarative — built ONCE, pages only declare mode):
 *  The search mode is declared in Blade on #data-table-card:
 *    <div id="data-table-card" data-search-mode="client|server">
 *  - server (default): debounced → update URL → refreshRegion.
 *      Use when the controller paginates or data is large.
 *  - client: filters <tr> rows locally using ONLY <td data-searchable> cells.
 *      Action buttons / images / hidden IDs / icons are excluded automatically.
 *      Use when the controller loads all rows (no pagination).
 *  Searchable cells are marked in Blade:
 *    <td data-searchable>{{ $country->name }}</td>
 *
 * Design note: This file is UI-only. No business logic lives here.
 * All backend logic stays in Laravel Controllers / FormRequests / Services.
 */
(function () {
  'use strict';

  // ===== Configuration =====
  var AJAX_TIMEOUT_MS = 30000; // 30 seconds — prevents hanging modals forever

  // ===== Active request tracking (for abort on modal close) =====
  var activeControllers = {};

  // ===== CSRF token (read once from meta tag injected by layouts) =====
  function csrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  }

  // ===== Toast container (lazy-init) =====
  function ensureToastContainer() {
    var c = document.getElementById('aracari-toasts');
    if (!c) {
      c = document.createElement('div');
      c.id = 'aracari-toasts';
      c.className = 'fixed top-5 left-1/2 -translate-x-1/2 z-[100] space-y-2 pointer-events-none w-full max-w-sm px-4';
      document.body.appendChild(c);
    }
    return c;
  }

  /**
   * Show a toast notification.
   * @param {string} message
   * @param {'success'|'error'|'info'|'warning'} [type='success']
   */
  function toast(message, type) {
    type = type || 'success';
    var c = ensureToastContainer();
    var colors = {
      success: 'bg-emerald-600 border-emerald-700',
      error: 'bg-rose-600 border-rose-700',
      info: 'bg-slate-800 border-slate-900',
      warning: 'bg-amber-500 border-amber-600',
    };
    var icons = {
      success: 'check-circle',
      error: 'alert-circle',
      info: 'info',
      warning: 'alert-triangle',
    };
    var el = document.createElement('div');
    el.className =
      'pointer-events-auto flex items-center gap-2.5 text-white text-sm font-bold px-4 py-3 rounded-xl shadow-lg border ' +
      (colors[type] || colors.success) +
      ' opacity-0 -translate-y-2 transition-all duration-300';
    el.innerHTML =
      '<i data-lucide="' + (icons[type] || icons.success) + '" class="w-4 h-4 flex-shrink-0"></i>' +
      '<span class="flex-1 text-right">' + escapeHtml(message) + '</span>' +
      '<button class="text-white/70 hover:text-white" aria-label="إغلاق"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>';
    c.appendChild(el);
    if (window.lucide) window.lucide.createIcons();
    // animate in
    requestAnimationFrame(function () {
      el.classList.remove('opacity-0', '-translate-y-2');
    });
    // close button
    el.querySelector('button').addEventListener('click', function () {
      closeToast(el);
    });
    // auto dismiss
    setTimeout(function () {
      closeToast(el);
    }, 4000);
  }
  function closeToast(el) {
    el.classList.add('opacity-0', '-translate-y-2');
    setTimeout(function () {
      if (el.parentNode) el.parentNode.removeChild(el);
    }, 300);
  }

  // ===== HTML escape (for safe text injection) =====
  function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  // ============================================================
  // MODAL ENGINE
  // ============================================================
  /**
   * Open a modal by id. Fills it from data-* attributes on the trigger.
   * @param {string} modalId
   * @param {Object} [data] optional pre-filled data keyed by field name
   */
  function openModal(modalId, data) {
    var modal = document.getElementById(modalId);
    if (!modal) return;
    // show
    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    // animate in
    var overlay = modal.querySelector('.aracari-modal-overlay');
    var panel = modal.querySelector('.aracari-modal-panel');
    if (overlay) {
      overlay.classList.remove('opacity-0');
      overlay.classList.add('opacity-100');
    }
    if (panel) {
      panel.classList.remove('opacity-0', 'scale-95', 'translate-y-4');
      panel.classList.add('opacity-100', 'scale-100', 'translate-y-0');
    }
    // fill fields from data
    if (data) {
      Object.keys(data).forEach(function (key) {
        var field = modal.querySelector('[name="' + key + '"]');
        if (field) {
          if (field.type === 'checkbox') field.checked = !!data[key];
          else if (field.type === 'select-multiple' && Array.isArray(data[key])) {
            Array.prototype.forEach.call(field.options, function (opt) {
              opt.selected = data[key].indexOf(opt.value) !== -1;
            });
          } else field.value = data[key] !== null ? data[key] : '';
        }
        // also fill ALL data-preview elements matching this key
        var previews = modal.querySelectorAll('[data-preview="' + key + '"]');
        var val = (data[key] !== null && data[key] !== undefined && data[key] !== '') ? data[key] : '—';
        Array.prototype.forEach.call(previews, function (preview) {
          if (key === 'is_approved') {
            var badgeSpan = preview.querySelector('span');
            if (badgeSpan) {
              badgeSpan.textContent = val === 1 || val === true || val === '1' ? 'معتمد من الإدارة' : 'قيد المراجعة';
              if (val === 1 || val === true || val === '1') {
                preview.className = preview.className.replace(/bg-amber-500\/10 text-amber-600 border-amber-500\/20/g, 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20');
              } else {
                preview.className = preview.className.replace(/bg-emerald-500\/10 text-emerald-600 border-emerald-500\/20/g, 'bg-amber-500/10 text-amber-600 border-amber-500/20');
              }
            } else {
              preview.textContent = val === 1 || val === true || val === '1' ? 'معتمد' : 'قيد المراجعة';
            }
          } else {
            preview.textContent = val;
          }
        });
      });
    }
    // clear previous errors
    clearErrors(modal);
    // focus first input
    setTimeout(function () {
      var first = modal.querySelector('input:not([type=hidden]):not([disabled]), select, textarea');
      if (first) first.focus();
    }, 100);
    // fire open event for custom hooks
    modal.dispatchEvent(new CustomEvent('aracari:modal-open', { detail: { data: data }, bubbles: true }));
  }

  function closeModal(modalId) {
    var modal = typeof modalId === 'string' ? document.getElementById(modalId) : modalId;
    if (!modal) return;

    // Abort any active request for this modal
    if (activeControllers[modal.id]) {
      try { activeControllers[modal.id].abort(); } catch (e) { /* ignore */ }
      delete activeControllers[modal.id];
    }

    var overlay = modal.querySelector('.aracari-modal-overlay');
    var panel = modal.querySelector('.aracari-modal-panel');
    if (overlay) {
      overlay.classList.add('opacity-0');
      overlay.classList.remove('opacity-100');
    }
    if (panel) {
      panel.classList.add('opacity-0', 'scale-95', 'translate-y-4');
      panel.classList.remove('opacity-100', 'scale-100', 'translate-y-0');
    }
    setTimeout(function () {
      modal.classList.add('hidden');
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      // reset form unless it's a details/view modal (data-no-reset)
      if (!modal.hasAttribute('data-no-reset')) {
        var form = modal.querySelector('form');
        if (form && !modal.hasAttribute('data-keep-values')) {
          form.reset();
          // Remove dynamically injected _method hidden inputs (added during edit mode)
          var dynamicMethods = form.querySelectorAll('input[name="_method"][data-dynamic]');
          Array.prototype.forEach.call(dynamicMethods, function(el) { el.remove(); });
          // Also remove any _method that was injected by openModal handlers
          // (keep the original @method('DELETE') in delete-confirm)
          var allMethods = form.querySelectorAll('input[name="_method"]');
          if (allMethods.length > 1) {
            // Keep the first one (original from Blade), remove the rest
            for (var i = 1; i < allMethods.length; i++) {
              allMethods[i].remove();
            }
          }
        }
        // Restore submit buttons that may be stuck in loading state
        var submitBtns = modal.querySelectorAll('[type="submit"]');
        Array.prototype.forEach.call(submitBtns, function(btn) {
          btn.disabled = false;
        });
      }
      clearErrors(modal);
    }, 200);
  }

  function clearErrors(modal) {
    var banner = modal.querySelector('.aracari-error-banner');
    if (banner) banner.classList.add('hidden');
    // clear field-level errors
    var fields = modal.querySelectorAll('[name]');
    Array.prototype.forEach.call(fields, function (field) {
      field.classList.remove('border-rose-500', 'ring-rose-500');
      var msg = modal.querySelector('[data-error-for="' + (field.name || field.id) + '"]');
      if (msg) msg.textContent = '';
    });
  }

  function showErrors(modal, errors) {
    clearErrors(modal);
    var hasErrors = errors && Object.keys(errors).length > 0;
    if (!hasErrors) return;
    // banner
    var banner = modal.querySelector('.aracari-error-banner');
    if (banner) {
      var firstKey = Object.keys(errors)[0];
      var firstMsg = Array.isArray(errors[firstKey]) ? errors[firstKey][0] : errors[firstKey];
      banner.querySelector('[data-banner-message]').textContent = firstMsg;
      banner.classList.remove('hidden');
    }
    // field-level
    Object.keys(errors).forEach(function (key) {
      var field = modal.querySelector('[name="' + key + '"]');
      if (field) {
        field.classList.add('border-rose-500');
        var msg = modal.querySelector('[data-error-for="' + key + '"]');
        if (msg) {
          var arr = Array.isArray(errors[key]) ? errors[key] : [errors[key]];
          msg.textContent = arr.join(' • ');
        }
      }
    });
  }

  // ============================================================
  // AJAX FORM SUBMIT (works inside modal or standalone)
  // ============================================================
  /**
   * Detect the appropriate loading text based on the form's HTTP method.
   * @param {HTMLFormElement} form
   * @returns {string}
   */
  function getLoadingText(form) {
    var methodEl = form.querySelector('input[name="_method"]');
    var method = methodEl ? (methodEl.value || '').toUpperCase() : form.getAttribute('method');
    if (method === 'DELETE') return 'جارٍ الحذف...';
    if (method === 'PUT' || method === 'PATCH') return 'جارٍ التحديث...';
    return 'جارٍ الحفظ...';
  }

  /**
   * Submit a form via fetch. Handles:
   *  - 422 validation: show errors inside modal
   *  - 302 redirect (success): close modal, refresh #data-table-card, toast
   *  - 419 CSRF mismatch: force page reload (session expired)
   *  - generic errors: toast + banner
   *  - network timeout (30s): toast error, restore button
   * @param {HTMLFormElement} form
   */
  function ajaxSubmit(form) {
    var modal = form.closest('[data-aracari-modal]');
    var modalId = modal ? modal.id : null;
    var submitBtn = form.querySelector('[type="submit"]');
    var originalHtml = submitBtn ? submitBtn.innerHTML : '';

    // Set loading state
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML =
        '<svg class="animate-spin w-4 h-4 inline-block" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> <span>' + getLoadingText(form) + '</span>';
    }

    var url = form.getAttribute('action');
    var method = (form.getAttribute('method') || 'POST').toUpperCase();
    var formData = new FormData(form);
    // Check if _method is already in the form
    var existingMethod = formData.get('_method');
    if (existingMethod) {
      existingMethod = String(existingMethod).toUpperCase();
      if (window.AracariDebug) console.log('[aracari] ajaxSubmit — form has _method=' + existingMethod + ', HTTP method will be POST (spoofed)');
      method = 'POST';
    } else if (method === 'PUT' || method === 'DELETE' || method === 'PATCH') {
      formData.append('_method', method);
      method = 'POST';
    }
    if (window.AracariDebug) console.log('[aracari] ajaxSubmit — url:', url, '| method:', method, '| has _method:', !!existingMethod);

    // AbortController for timeout + modal close cancellation
    var controller = new AbortController();
    if (modalId) activeControllers[modalId] = controller;
    var timeoutId = setTimeout(function () {
      controller.abort();
    }, AJAX_TIMEOUT_MS);

    fetch(url, {
      method: method,
      headers: {
        'X-CSRF-TOKEN': csrfToken(),
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: formData,
      credentials: 'same-origin',
      signal: controller.signal,
    })
      .then(function (res) {
        clearTimeout(timeoutId);
        if (modalId) delete activeControllers[modalId];

        // 419 CSRF token mismatch — session expired
        if (res.status === 419) {
          toast('انتهت صلاحية الجلسة. سيتم إعادة تحميل الصفحة...', 'warning');
          setTimeout(function () { window.location.reload(); }, 1500);
          restoreBtn();
          return;
        }

        // Laravel returns 422 JSON for validation failures
        if (res.status === 422) {
          return res.json().then(function (body) {
            return { __status: 422, __body: body };
          });
        }
        // Error responses (405, 403, 500, 404, etc.)
        if (!res.ok && !res.redirected) {
          return res.text().then(function (text) {
            var msg = 'حدث خطأ غير متوقع (HTTP ' + res.status + ')';
            try {
              var body = JSON.parse(text);
              if (body.message) msg = body.message;
              if (body.errors) {
                return { __status: 422, __body: body };
              }
            } catch (e) {
              if (res.status === 405) msg = 'طريقة الطلب غير مسموح بها (405). تأكد من إعداد الـ route.';
              else if (res.status === 403) msg = 'غير مصرح لك بهذا الإجراء (403).';
              else if (res.status === 404) msg = 'العنصر غير موجود (404).';
              else if (res.status >= 500) msg = 'خطأ في الخادم (' + res.status + '). يرجى المحاولة لاحقاً.';
            }
            return { __status: res.status, __error: msg };
          }).catch(function () {
            return { __status: res.status, __error: 'حدث خطأ غير متوقع (HTTP ' + res.status + ')' };
          });
        }

        // Redirect detection — Laravel redirects on success (302).
        // We treat it as success ONLY if it's an AJAX request that was
        // supposed to return JSON but the server redirected instead.
        if (res.redirected || res.ok) {
          // Check if the final URL is a login page — means session expired
          var finalUrl = res.url || '';
          if (finalUrl.indexOf('/login') !== -1 || finalUrl.indexOf('/password/reset') !== -1) {
            toast('انتهت صلاحية الجلسة. سيتم إعادة تحميل الصفحة...', 'warning');
            setTimeout(function () { window.location.href = finalUrl; }, 1500);
            restoreBtn();
            return { __status: 0, __aborted: true };
          }

          // Check if the response is JSON (controller returned JSON for AJAX)
          var contentType = res.headers.get('content-type') || '';
          if (contentType.indexOf('application/json') !== -1) {
            return res.json().then(function (json) {
              if (json.success === false || json.success === 0) {
                return { __status: 422, __body: { message: json.message || 'حدث خطأ غير متوقع' }, __isJson: true };
              }
              return { __status: res.status, __json: json, __ok: true, __isJson: true };
            }).catch(function () {
              // JSON parse failed, fall through to HTML refresh
              return { __status: res.status, __redirected: res.redirected, __ok: res.ok, __url: res.url };
            });
          }

          return { __status: res.status, __redirected: res.redirected, __ok: res.ok, __url: res.url };
        }

        // Fallback
        return { __status: res.status, __ok: false };
      })
      .then(function (result) {
        if (!result || result.__aborted) return;

        if (result.__status === 422) {
          if (result.__isJson) {
            // JSON error response from controller (e.g. delete guard)
            var errorMsg = (result.__body && result.__body.message) || 'يرجى تصحيح الأخطاء';
            toast(errorMsg, 'error');
            if (modal) {
              var banner = modal.querySelector('.aracari-error-banner');
              if (banner) {
                var msgEl = banner.querySelector('[data-banner-message]');
                if (msgEl) msgEl.textContent = errorMsg;
                banner.classList.remove('hidden');
              }
            }
            restoreBtn();
            return;
          }
          var errors = (result.__body && result.__body.errors) || {};
          if (modal) showErrors(modal, errors);
          toast(result.__body && result.__body.message ? result.__body.message : 'يرجى تصحيح الأخطاء', 'error');
          restoreBtn();
          return;
        }
        // hard error (405, 403, 500, etc.)
        if (result.__error) {
          if (window.AracariDebug) console.error('[aracari] submit error:', result.__status, result.__error);
          toast(result.__error, 'error');
          if (modal) {
            var banner = modal.querySelector('.aracari-error-banner');
            if (banner) {
              var msgEl = banner.querySelector('[data-banner-message]');
              if (msgEl) msgEl.textContent = result.__error;
              banner.classList.remove('hidden');
            }
          }
          restoreBtn();
          return;
        }
        // success — restore button, close modal, refresh table, toast
        var successMsg = form.getAttribute('data-success-message') || 'تم الحفظ بنجاح';
        // If controller returned JSON with a message, use it
        if (result.__isJson && result.__json && result.__json.message) {
          successMsg = result.__json.message;
        }
        restoreBtn();
        if (modal) closeModal(modal);
        toast(successMsg, 'success');
        // refresh the broadest refreshable region if present
        if (document.getElementById('aracari-refreshable')) {
          refreshRegion('aracari-refreshable');
        } else if (document.getElementById('data-table-card')) {
          refreshRegion('data-table-card');
        } else {
          setTimeout(function () {
            window.location.reload();
          }, 600);
        }
      })
      .catch(function (err) {
        clearTimeout(timeoutId);
        if (modalId) delete activeControllers[modalId];

        // Ignore abort errors (user closed modal or timeout)
        if (err.name === 'AbortError') {
          if (window.AracariDebug) console.log('[aracari] request aborted (timeout or modal close)');
          // Only show timeout toast if it wasn't a user-initiated modal close
          if (modal && !modal.classList.contains('hidden')) {
            toast('انتهت مهلة الطلب. يرجى المحاولة مرة أخرى.', 'error');
          }
          restoreBtn();
          return;
        }

        console.error('[aracari] submit error', err);
        toast('حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى.', 'error');
        restoreBtn();
      });

    function restoreBtn() {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalHtml;
        if (window.lucide) window.lucide.createIcons();
      }
    }
  }

  /**
   * Refresh a region of the page by id
   * @param {string} elementId
   */
  function refreshRegion(elementId) {
    var region = document.getElementById(elementId);
    if (!region) return;
    var url = window.location.pathname + window.location.search;
    fetch(url, {
      headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
      credentials: 'same-origin',
    })
      .then(function (res) { return res.text(); })
      .then(function (html) {
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');
        var fresh = doc.getElementById(elementId);
        if (fresh) {
          region.innerHTML = fresh.innerHTML;
          if (window.lucide) window.lucide.createIcons();
          bindRowActions();
          initLivePreviews();
        }
      })
      .catch(function (err) {
        console.error('[aracari] refresh error', err);
      });
  }

  /**
   * Backward-compatible alias for refreshRegion.
   */
  function refreshTableCard() {
    if (document.getElementById('aracari-refreshable')) {
      refreshRegion('aracari-refreshable');
    } else {
      refreshRegion('data-table-card');
    }
  }

  // ============================================================
  // DELETE CONFIRMATION (replaces window.confirm)
  // ============================================================
  function openDeleteConfirm(url, label, extra) {
    var modal = document.getElementById('aracari-delete-modal');
    if (!modal) {
      console.error('[aracari] delete-confirm modal #aracari-delete-modal not found in DOM');
      return;
    }
    if (!url) {
      console.error('[aracari] openDeleteConfirm called with empty URL');
      return;
    }
    if (window.AracariDebug) {
      console.log('[aracari] openDeleteConfirm url:', url, 'label:', label);
    }
    extra = extra || {};
    var titleEl = modal.querySelector('[data-delete-title]');
    var msgEl = modal.querySelector('[data-delete-message]');
    var labelEl = modal.querySelector('[data-delete-label]');
    if (titleEl) titleEl.textContent = extra.title || 'تأكيد الحذف';
    if (msgEl)
      msgEl.textContent =
        extra.message ||
        'هل أنت متأكد من حذف "' + (label || 'هذا العنصر') + '"؟ لا يمكن التراجع عن هذا الإجراء.';
    if (labelEl) labelEl.textContent = label || '';
    var form = modal.querySelector('form');
    if (form) {
      form.setAttribute('action', url);
      // Ensure _method is DELETE for the delete-confirm form
      var methodInput = form.querySelector('input[name="_method"]');
      if (methodInput) {
        methodInput.value = 'DELETE';
      } else {
        var m = document.createElement('input');
        m.type = 'hidden'; m.name = '_method'; m.value = 'DELETE';
        form.appendChild(m);
      }
    }
    openModal('aracari-delete-modal');
  }

  // ============================================================
  // SEARCH ENGINE — dual mode (client | server)
  // ============================================================
  function resolveSearchMode() {
    var card = document.getElementById('data-table-card');
    if (!card) return 'server';
    var mode = (card.getAttribute('data-search-mode') || 'server').toLowerCase();
    return mode === 'client' ? 'client' : 'server';
  }

  function initSearch() {
    var input = document.getElementById('aracari-search-input');
    var form = document.getElementById('aracari-search-form');
    var resetBtn = document.getElementById('aracari-search-reset');
    if (!input || !form) return;

    var mode = resolveSearchMode();
    var timer = null;

    function trigger() {
      if (mode === 'client') {
        runClientSearch(input.value);
      } else {
        runServerSearch();
      }
    }

    input.addEventListener('input', function () {
      clearTimeout(timer);
      timer = setTimeout(trigger, mode === 'client' ? 200 : 350);
    });

    if (resetBtn) {
      resetBtn.addEventListener('click', function () {
        input.value = '';
        if (mode === 'client') {
          runClientSearch('');
        } else {
          window.history.replaceState({}, '', window.location.pathname);
          refreshTableCard();
        }
      });
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      clearTimeout(timer);
      trigger();
    });

    function runClientSearch(query) {
      var card = document.getElementById('data-table-card');
      if (!card) return;
      var tbody = card.querySelector('table tbody');
      if (!tbody) return;

      var rows = tbody.querySelectorAll('tr');
      var q = (query || '').trim().toLowerCase();
      var visibleCount = 0;
      var colCount = 0;

      Array.prototype.forEach.call(rows, function (row) {
        if (row.hasAttribute('data-search-empty-row')) return;
        if (!colCount) colCount = row.querySelectorAll('td').length;
        var cells = row.querySelectorAll('td[data-searchable]');
        var matches = false;
        if (!q) { matches = true; }
        else if (cells.length === 0) { matches = false; }
        else {
          Array.prototype.forEach.call(cells, function (cell) {
            if (matches) return;
            var text = (cell.textContent || '').trim().toLowerCase();
            if (text.indexOf(q) !== -1) matches = true;
          });
        }
        row.style.display = matches ? '' : 'none';
        if (matches) visibleCount++;
      });

      var emptyRow = tbody.querySelector('[data-search-empty-row]');
      if (visibleCount === 0) {
        if (!emptyRow) {
          emptyRow = document.createElement('tr');
          emptyRow.setAttribute('data-search-empty-row', '');
          var td = document.createElement('td');
          td.setAttribute('colspan', String(colCount || 5));
          td.className = 'px-6 py-16 text-center';
          td.innerHTML =
            '<div class="w-20 h-20 rounded-full bg-slate-50 mx-auto flex items-center justify-center text-slate-300 mb-4">' +
              '<i data-lucide="search-x" class="w-9 h-9"></i>' +
            '</div>' +
            '<h3 class="text-lg font-bold text-slate-700 mb-1">لا توجد نتائج مطابقة</h3>' +
            '<p class="text-slate-400 text-sm">جرّب تعديل كلمات البحث أو إعادة التعيين.</p>';
          emptyRow.appendChild(td);
          tbody.appendChild(emptyRow);
          if (window.lucide) window.lucide.createIcons();
        }
        emptyRow.style.display = '';
      } else if (emptyRow) {
        emptyRow.style.display = 'none';
      }

      var paginationWrap = card.querySelector('[data-pagination-wrap]');
      if (paginationWrap) paginationWrap.style.display = q ? 'none' : '';

      var footerInfo = card.querySelector('[data-footer-info]');
      if (footerInfo) {
        if (!footerInfo.hasAttribute('data-original-text')) {
          footerInfo.setAttribute('data-original-text', footerInfo.textContent || '');
        }
        if (q) {
          footerInfo.textContent = visibleCount === 0 ? 'لا توجد نتائج' : 'عرض ' + visibleCount + ' نتيجة مطابقة';
        } else {
          footerInfo.textContent = footerInfo.getAttribute('data-original-text') || '';
        }
      }
    }

    function runServerSearch() {
      var fd = new FormData(form);
      var params = new URLSearchParams();
      Array.prototype.forEach.call(fd.entries(), function (e) {
        if (e[1] !== '' && e[1] !== null) params.append(e[0], e[1]);
      });
      var qs = params.toString();
      var newUrl = qs ? window.location.pathname + '?' + qs : window.location.pathname;
      window.history.replaceState({}, '', newUrl);
      refreshTableCard();
    }
  }

  // ============================================================
  // ROW ACTION BINDING
  // ============================================================
  function bindRowActions() {
    var editBtns = document.querySelectorAll('[data-edit-modal]');
    editBtns.forEach(function (btn) {
      if (btn.__aracariBound) return;
      btn.__aracariBound = true;
      btn.addEventListener('click', function () {
        var modalId = btn.getAttribute('data-edit-modal');
        var json = btn.getAttribute('data-edit-json');
        var data = {};
        try { data = JSON.parse(json); } catch (e) { data = {}; }
        openModal(modalId, data);
      });
    });
    var viewBtns = document.querySelectorAll('[data-view-modal]');
    viewBtns.forEach(function (btn) {
      if (btn.__aracariBound) return;
      btn.__aracariBound = true;
      btn.addEventListener('click', function () {
        var modalId = btn.getAttribute('data-view-modal');
        var json = btn.getAttribute('data-view-json') || '{}';
        var data = {};
        try { data = JSON.parse(json); } catch (e) { data = {}; }
        openModal(modalId, data);
      });
    });
    var deleteBtns = document.querySelectorAll('[data-delete-url]');
    deleteBtns.forEach(function (btn) {
      if (btn.__aracariBound) return;
      btn.__aracariBound = true;
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var url = btn.getAttribute('data-delete-url');
        var label = btn.getAttribute('data-delete-label') || '';
        openDeleteConfirm(url, label);
      });
    });

    // Restore buttons (for soft-deleted records) — direct AJAX, no confirmation needed
    var restoreBtns = document.querySelectorAll('[data-restore-url]');
    restoreBtns.forEach(function (btn) {
      if (btn.__aracariBound) return;
      btn.__aracariBound = true;
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var url = btn.getAttribute('data-restore-url');
        if (!url) return;
        // Set loading state on the button
        var originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>';
        var token = csrfToken();
        fetch(url, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          credentials: 'same-origin',
        })
          .then(function (res) {
            if (res.status === 419) {
              toast('انتهت صلاحية الجلسة. سيتم إعادة تحميل الصفحة...', 'warning');
              setTimeout(function () { window.location.reload(); }, 1500);
              return null;
            }
            var ct = res.headers.get('content-type') || '';
            if (ct.indexOf('application/json') !== -1) {
              return res.json().then(function (j) { return { __json: j, __status: res.status }; });
            }
            // Redirect (success 302) or HTML — treat as success
            return { __json: { success: true, message: 'تمت الاستعادة بنجاح' }, __status: res.status };
          })
          .then(function (result) {
            if (!result) return;
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            if (window.lucide) window.lucide.createIcons();
            var j = result.__json || {};
            if (j.success === false) {
              toast(j.message || 'تعذرت الاستعادة', 'error');
              return;
            }
            toast(j.message || 'تمت الاستعادة بنجاح', 'success');
            // refresh the broadest refreshable region
            if (document.getElementById('aracari-refreshable')) {
              refreshRegion('aracari-refreshable');
            } else if (document.getElementById('data-table-card')) {
              refreshRegion('data-table-card');
            } else {
              setTimeout(function () { window.location.reload(); }, 600);
            }
          })
          .catch(function (err) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            if (window.lucide) window.lucide.createIcons();
            console.error('[aracari] restore error', err);
            toast('حدث خطأ أثناء الاستعادة. يرجى المحاولة مرة أخرى.', 'error');
          });
      });
    });

    var createBtns = document.querySelectorAll('[data-create-modal]');
    createBtns.forEach(function (btn) {
      if (btn.__aracariBound) return;
      btn.__aracariBound = true;
      btn.addEventListener('click', function () {
        openModal(btn.getAttribute('data-create-modal'), {});
      });
    });
    if (window.AracariDebug) {
      console.log('[aracari] bindRowActions:', editBtns.length, 'edit,', viewBtns.length, 'view,', deleteBtns.length, 'delete,', createBtns.length, 'create,', restoreBtns.length, 'restore buttons');
    }
  }

  // ============================================================
  // LIVE PREVIEW SYNC
  // ============================================================
  function initLivePreviews() {
    document.querySelectorAll('[data-preview-for]').forEach(function (target) {
      var sourceId = target.getAttribute('data-preview-for');
      var source = document.getElementById(sourceId);
      if (!source) return;
      var handler = function () {
        var val = source.value;
        target.textContent = val && val.trim() ? val : target.getAttribute('data-preview-default') || '—';
      };
      source.addEventListener('input', handler);
      handler();
    });
  }

  // ============================================================
  // GLOBAL EVENT DELEGATION
  // ============================================================
  function initGlobalHandlers() {
    document.addEventListener('click', function (e) {
      var closeBtn = e.target.closest('[data-close-modal]');
      if (closeBtn) {
        var modal = closeBtn.closest('[data-aracari-modal]');
        if (modal) closeModal(modal);
        return;
      }
      if (e.target.classList && e.target.classList.contains('aracari-modal-overlay')) {
        var m = e.target.closest('[data-aracari-modal]');
        if (m && !m.hasAttribute('data-static')) closeModal(m);
        return;
      }
      var pageLink = e.target.closest('[data-partial-link]');
      if (pageLink) {
        e.preventDefault();
        var href = pageLink.getAttribute('href');
        window.history.replaceState({}, '', href);
        refreshTableCard();
        var card = document.getElementById('data-table-card');
        if (card) card.scrollIntoView({ behavior: 'smooth', block: 'start' });
        return;
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        var open = document.querySelectorAll('[data-aracari-modal]:not(.hidden)');
        open.forEach(function (m) {
          if (!m.hasAttribute('data-static')) closeModal(m);
        });
      }
    });

    document.addEventListener('submit', function (e) {
      var form = e.target;
      if (form.hasAttribute('data-aracari-ajax')) {
        e.preventDefault();
        ajaxSubmit(form);
      }
    });
  }

  // ============================================================
  // INIT
  // ============================================================
  function init() {
    if (window.AracariDebug === undefined) window.AracariDebug = false;

    console.log('[aracari] init — version v2.3, debug:', window.AracariDebug);
    console.log('[aracari] aracari.js loaded. delete-confirm modal in DOM:', !!document.getElementById('aracari-delete-modal'));
    console.log('[aracari] aracari-search-input present:', !!document.getElementById('aracari-search-input'));
    bindRowActions();
    initSearch();
    initLivePreviews();
    initGlobalHandlers();
    if (window.lucide) window.lucide.createIcons();
  }

  window.Aracari = {
    openModal: openModal,
    closeModal: closeModal,
    toast: toast,
    refreshTableCard: refreshTableCard,
    openDeleteConfirm: openDeleteConfirm,
    ajaxSubmit: ajaxSubmit,
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
