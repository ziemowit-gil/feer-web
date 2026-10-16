@once
<script>
(function () {
  var EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
  var MSG = {
    email: 'Podaj adres e-mail.',
    emailBad: 'Ten adres wygląda na niepoprawny. Sprawdź, czy zawiera znak @ i domenę.',
    topics: 'Wybierz przynajmniej jeden temat.',
    form_answer: 'Odpowiedz na pytanie kontrolne.',
    consent: 'Zaznacz zgodę, aby się zapisać.'
  };

  document.querySelectorAll('[data-nls]').forEach(function (box) {
    var form = box.querySelector('[data-nls-form]');
    if (!form || form.dataset.nlsReady) return;
    form.dataset.nlsReady = '1';

    var head = box.querySelector('[data-nls-heading]'), sub = box.querySelector('[data-nls-sub]');
    var done = box.querySelector('[data-nls-done]'), doneEmail = box.querySelector('[data-nls-done-email]');
    var summary = form.querySelector('[data-nls-summary]'), list = form.querySelector('[data-nls-summary-list]');
    var btn = form.querySelector('[data-nls-submit]'), label = form.querySelector('[data-nls-label]'), status = form.querySelector('[data-nls-status]');
    var fields = {
      email: form.querySelector('[data-nls-email]'),
      topics: form.querySelector('[data-nls-topics]'),
      form_answer: form.querySelector('[data-nls-answer]'),
      consent: form.querySelector('[data-nls-consent]')
    };
    var original = label.textContent;

    function setError(key, msg) {
      var el = fields[key], out = form.querySelector('[data-nls-error="' + key + '"]');
      if (!el || !out) return;
      out.textContent = msg || '';
      out.hidden = !msg;
      if (msg) el.setAttribute('aria-invalid', 'true'); else el.removeAttribute('aria-invalid');
    }

    function check() {
      var errs = [];
      var v = fields.email.value.trim();
      errs.push(['email', !v ? MSG.email : (!EMAIL_RE.test(v) ? MSG.emailBad : '')]);
      if (fields.topics) errs.push(['topics', fields.topics.querySelector('input:checked') ? '' : MSG.topics]);
      errs.push(['form_answer', fields.form_answer.value.trim() ? '' : MSG.form_answer]);
      errs.push(['consent', fields.consent.checked ? '' : MSG.consent]);
      return errs;
    }

    function showSummary(errs) {
      var bad = errs.filter(function (e) { return e[1]; });
      errs.forEach(function (e) { setError(e[0], e[1]); });
      list.innerHTML = '';
      bad.forEach(function (e) {
        var li = document.createElement('li'), a = document.createElement('a');
        a.href = '#' + fields[e[0]].id; a.textContent = e[1];
        a.addEventListener('click', function (ev) { ev.preventDefault(); var t = fields[e[0]]; (t.matches('fieldset') ? t.querySelector('input') : t).focus(); });
        li.appendChild(a); list.appendChild(li);
      });
      summary.querySelector('h2,h3,h4').textContent = 'Popraw ' + (bad.length === 1 ? 'jedno pole' : 'zaznaczone pola') + ', aby się zapisać';
      summary.hidden = bad.length === 0;
      if (bad.length) summary.focus();
      return bad.length === 0;
    }

    // Linki z podsumowania renderowanego przez serwer (bez JS też działają jako kotwice).
    list.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function (ev) {
        var t = document.getElementById(a.getAttribute('href').slice(1)); if (!t) return;
        ev.preventDefault(); (t.matches('fieldset') ? t.querySelector('input') : t).focus();
      });
    });
    if (!summary.hidden) summary.focus();

    // Błąd znika, gdy użytkownik go poprawi (bez krzyczenia w trakcie pisania).
    fields.email.addEventListener('blur', function () { if (fields.email.getAttribute('aria-invalid')) { var e = check()[0]; setError('email', e[1]); } });
    form.addEventListener('change', function (ev) {
      if (fields.topics && fields.topics.contains(ev.target) && fields.topics.getAttribute('aria-invalid')) setError('topics', fields.topics.querySelector('input:checked') ? '' : MSG.topics);
      if (ev.target === fields.consent && fields.consent.checked) setError('consent', '');
    });

    form.addEventListener('submit', function (ev) {
      ev.preventDefault();
      status.textContent = '';
      if (!showSummary(check())) return;
      btn.disabled = true; btn.setAttribute('aria-busy', 'true'); label.textContent = 'Zapisuję…';
      fetch(form.action, { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: new FormData(form), credentials: 'same-origin' })
        .then(function (res) { return res.json().catch(function () { return {}; }).then(function (data) { return { res: res, data: data }; }); })
        .then(function (r) {
          if (r.res.ok) {
            doneEmail.textContent = fields.email.value.trim();
            form.hidden = true; if (head) head.hidden = true; if (sub) sub.hidden = true;
            done.hidden = false; done.focus();
          } else if (r.res.status === 422 && r.data.errors) {
            // Złe pytanie kontrolne: przeładowanie strony da nowe zadanie (serwer pokaże błąd przy polu).
            if (r.data.errors.form_answer) { HTMLFormElement.prototype.submit.call(form); return; }
            var errs = Object.keys(fields).map(function (k) { return [k, r.data.errors[k] ? r.data.errors[k][0] : '']; });
            if (!showSummary(errs)) return;
            status.textContent = Object.values(r.data.errors)[0][0] || 'Popraw dane w formularzu.';
          } else if (r.res.status === 429) {
            status.textContent = 'Zbyt wiele prób. Spróbuj ponownie za minutę.';
          } else { throw new Error('server'); }
        })
        .catch(function () { status.textContent = 'Nie udało się wysłać formularza. Sprawdź połączenie i spróbuj ponownie.'; })
        .finally(function () { btn.disabled = false; btn.removeAttribute('aria-busy'); label.textContent = original; });
    });

    box.querySelector('[data-nls-again]').addEventListener('click', function () {
      done.hidden = true; form.hidden = false; if (head) head.hidden = false; if (sub) sub.hidden = false;
      fields.email.focus(); fields.email.select();
    });
  });
})();
</script>
@endonce
