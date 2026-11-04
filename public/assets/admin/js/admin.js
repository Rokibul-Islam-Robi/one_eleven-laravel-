/* Admin panel behaviour: sidebar, dropdowns, copy buttons, confirms. */
(function () {
  'use strict';

  /* ---------- mobile sidebar ---------- */
  var burger = document.getElementById('adm-burger');
  var side = document.getElementById('adm-side');
  var back = document.getElementById('adm-side-back');
  function closeSide() {
    if (side) side.classList.remove('open');
    if (back) back.hidden = true;
  }
  if (burger && side && back) {
    burger.addEventListener('click', function () {
      side.classList.add('open');
      back.hidden = false;
    });
    back.addEventListener('click', closeSide);
  }

  /* ---------- user dropdown ---------- */
  var userBtn = document.getElementById('adm-user-btn');
  var userMenu = document.getElementById('adm-user-menu');
  if (userBtn && userMenu) {
    userBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      userMenu.hidden = !userMenu.hidden;
    });
    document.addEventListener('click', function () { userMenu.hidden = true; });
    userMenu.addEventListener('click', function (e) { e.stopPropagation(); });
  }

  /* ---------- copy buttons (data-copy="TEXT") ---------- */
  document.querySelectorAll('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var text = btn.getAttribute('data-copy');
      var done = function () {
        var old = btn.getAttribute('title') || '';
        btn.setAttribute('title', 'Copied!');
        setTimeout(function () { btn.setAttribute('title', old); }, 1200);
      };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(done);
      } else {
        var ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); done(); } catch (err) { /* ignore */ }
        document.body.removeChild(ta);
      }
    });
  });

  /* ---------- select-all checkboxes ---------- */
  document.querySelectorAll('[data-check-all]').forEach(function (master) {
    master.addEventListener('change', function () {
      var name = master.getAttribute('data-check-all');
      document.querySelectorAll('input[data-check-item="' + name + '"]').forEach(function (c) {
        c.checked = master.checked;
      });
    });
  });

  /* ---------- autosubmit selects (data-autosubmit) ---------- */
  document.querySelectorAll('select[data-autosubmit]').forEach(function (sel) {
    sel.addEventListener('change', function () { sel.form && sel.form.submit(); });
  });
})();
