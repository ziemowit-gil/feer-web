@php
    $id = 'nlw-' . $form->id . '-' . substr(md5($source), 0, 6);
    $accent = $form->accent_color ?: null;
    $topics = $form->topicOptions();
    $defaults = $form->default_topics ?: [];
    $old = old('topics');
@endphp
@once
<style>
.nlw{--nlw-text:#1D1D1A;--nlw-brand:var(--color-brand,#1E6DFF);--nlw-brand-dark:#1752BF;--nlw-accent:#EA8F00;--nlw-bg:#F3F6FB;--nlw-card:#fff;--nlw-muted:#4A4A47;--nlw-border:#8E8E8A;--nlw-line:#D6DEEA;--nlw-error:#B3261E;--nlw-ok-bg:#E6F4EA;--nlw-ok:#0F5132;font-family:Montserrat,Arial,Helvetica,sans-serif;color:var(--nlw-text);background:var(--nlw-bg)}
.nlw *{box-sizing:border-box}
.nlw--band{padding:clamp(32px,6vw,72px) 16px}
.nlw--card{padding:0;background:transparent}
.nlw--inline{padding:16px 0;background:transparent}
.nlw__inner{max-width:1080px;margin:0 auto;display:grid;gap:28px;grid-template-columns:1fr}
@media (min-width:900px){.nlw--band .nlw__inner{grid-template-columns:5fr 7fr;align-items:center;gap:48px}}
.nlw__copy{position:relative;padding-left:20px}
.nlw__copy::before{content:"";position:absolute;left:0;top:6px;bottom:6px;width:6px;border-radius:3px;background:var(--nlw-accent)}
.nlw__eyebrow{margin:0 0 10px;font-size:13px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--nlw-brand-dark)}
.nlw__title{margin:0 0 14px;font-size:clamp(26px,3.4vw,40px);line-height:1.1;font-weight:800;color:var(--nlw-text)}
.nlw--card .nlw__title,.nlw--inline .nlw__title{font-size:22px}
.nlw__lead{margin:0;font-size:17px;line-height:1.6;color:var(--nlw-muted)}
.nlw__trust{display:flex;flex-wrap:wrap;gap:14px 22px;margin:22px 0 0;padding:0;list-style:none;font-size:14px;color:var(--nlw-muted)}
.nlw__trust li{display:inline-flex;align-items:center;gap:8px}
.nlw__trust svg{width:18px;height:18px;flex:none;color:var(--nlw-brand-dark)}
.nlw__form{display:grid;gap:18px;padding:28px;background:var(--nlw-card);border:1px solid var(--nlw-line);border-radius:14px;box-shadow:0 10px 30px rgba(29,29,26,.07)}
.nlw--inline .nlw__form{padding:0;border:0;box-shadow:none;background:transparent}
.nlw__row{display:grid;gap:16px;grid-template-columns:1fr}
@media (min-width:560px){.nlw--band .nlw__row,.nlw--inline .nlw__row{grid-template-columns:1fr 1.4fr}}
.nlw__label{display:block;margin-bottom:6px;font-size:15px;font-weight:700;color:var(--nlw-text)}
.nlw__opt{font-weight:400;color:var(--nlw-muted)}
.nlw__input{width:100%;min-height:50px;padding:10px 14px;font:inherit;font-size:16px;color:var(--nlw-text);background:#fff;border:2px solid var(--nlw-border);border-radius:8px;transition:border-color .15s}
.nlw__input:hover{border-color:var(--nlw-text)}
.nlw__input--short{max-width:200px}
.nlw__input:focus-visible,.nlw__btn:focus-visible,.nlw__status:focus-visible{outline:3px solid var(--nlw-brand);outline-offset:3px}
.nlw__input[aria-invalid="true"]{border-color:var(--nlw-error)}
.nlw__help{margin:6px 0 0;font-size:14px;color:var(--nlw-muted)}
.nlw__error{margin:6px 0 0;font-size:14px;font-weight:700;color:var(--nlw-error)}
.nlw__error::before{content:"\26A0  "}
.nlw__topics{border:0;padding:0;margin:0;display:flex;flex-wrap:wrap;gap:10px}
.nlw__topics legend{padding:0;margin-bottom:10px;width:100%}
.nlw__chip{position:relative;display:inline-flex;align-items:center;gap:10px;min-height:46px;padding:8px 16px 8px 12px;border:2px solid var(--nlw-border);border-radius:999px;font-size:15px;font-weight:600;cursor:pointer;background:#fff;color:var(--nlw-text);transition:background .15s,border-color .15s}
.nlw__chip:hover{border-color:var(--nlw-text)}
.nlw__chip input{appearance:none;-webkit-appearance:none;width:22px;height:22px;margin:0;border:2px solid var(--nlw-border);border-radius:6px;background:#fff;display:grid;place-content:center;cursor:pointer;flex:none}
.nlw__chip input::before{content:"";width:12px;height:12px;clip-path:polygon(14% 44%,0 65%,50% 100%,100% 16%,80% 0,43% 62%);background:#fff;transform:scale(0);transition:transform .12s}
.nlw__chip input:checked{background:var(--nlw-brand-dark);border-color:var(--nlw-brand-dark)}
.nlw__chip input:checked::before{transform:scale(1)}
.nlw__chip:has(input:checked){border-color:var(--nlw-brand-dark);background:#EAF1FF;color:var(--nlw-brand-dark);font-weight:700}
.nlw__chip:has(input:focus-visible){outline:3px solid var(--nlw-brand);outline-offset:3px}
.nlw__consent{display:grid;grid-template-columns:24px 1fr;gap:12px;align-items:start;font-size:14px;line-height:1.55;color:var(--nlw-text);padding:14px;border-radius:10px;background:var(--nlw-bg)}
.nlw__consent input{appearance:none;-webkit-appearance:none;width:24px;height:24px;margin-top:1px;border:2px solid var(--nlw-border);border-radius:6px;background:#fff;display:grid;place-content:center;cursor:pointer}
.nlw__consent input::before{content:"";width:13px;height:13px;clip-path:polygon(14% 44%,0 65%,50% 100%,100% 16%,80% 0,43% 62%);background:#fff;transform:scale(0)}
.nlw__consent input:checked{background:var(--nlw-brand-dark);border-color:var(--nlw-brand-dark)}
.nlw__consent input:checked::before{transform:scale(1)}
.nlw__consent input:focus-visible{outline:3px solid var(--nlw-brand);outline-offset:3px}
.nlw__consent a{color:var(--nlw-brand-dark);font-weight:700;text-decoration:underline;text-underline-offset:3px}
.nlw__actions{display:flex;flex-wrap:wrap;align-items:center;gap:14px}
.nlw__btn{display:inline-flex;align-items:center;gap:10px;min-height:54px;padding:12px 30px;font:inherit;font-size:17px;font-weight:800;color:#fff;background:var(--nlw-brand-dark);border:0;border-radius:10px;cursor:pointer;transition:background-color .15s ease,transform .1s}
.nlw__btn svg{width:18px;height:18px}
.nlw__btn:hover{background:#123F94}
.nlw__btn:active{transform:translateY(1px)}
.nlw__btn[aria-busy="true"]{background:#123F94;cursor:progress}
.nlw__btn[disabled]{cursor:not-allowed}
.nlw__note{margin:0;font-size:13px;color:var(--nlw-muted)}
.nlw__status{margin:0;padding:14px 16px;border-radius:10px;font-weight:700;line-height:1.5}
.nlw__status:empty{display:none}
.nlw__status--ok{background:var(--nlw-ok-bg);color:var(--nlw-ok)}
.nlw__status--err{background:#FCEBEA;color:var(--nlw-error)}
.nlw__hp{position:absolute;left:-10000px;width:1px;height:1px;overflow:hidden}
@media (max-width:559px){.nlw__btn{width:100%;justify-content:center}.nlw__form{padding:20px}}
@media (prefers-reduced-motion:reduce){.nlw__btn,.nlw__chip,.nlw__chip input::before,.nlw__input{transition:none}}
</style>
@endonce
<section class="nlw nlw--{{ $style }}" aria-labelledby="{{ $id }}-title" @if($accent) style="--nlw-accent: {{ $accent }}" @endif data-nlw>
  <div class="nlw__inner">
    @if ($heading)
    <div class="nlw__copy">
      @if ($form->eyebrow)<p class="nlw__eyebrow">{{ $form->eyebrow }}</p>@endif
      <h2 id="{{ $id }}-title" class="nlw__title">{{ $form->heading }}</h2>
      @if ($form->lead)<p class="nlw__lead">{{ $form->lead }}</p>@endif
      @if ($style === 'band')
      <ul class="nlw__trust" aria-label="Zasady newslettera">
        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>Dane tylko do wysyłki newslettera</li>
        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 12l5 5L20 6"/></svg>Potwierdzenie e-mailem (double opt-in)</li>
        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>Wypis jednym kliknięciem</li>
      </ul>
      @endif
    </div>
    @else
      <h2 id="{{ $id }}-title" class="sr-only">{{ $form->heading }}</h2>
    @endif

    <form class="nlw__form" action="{{ route('newsletter.subscribe') }}" method="post" novalidate data-nlw-form>
      @csrf
      <input type="hidden" name="form_id" value="{{ $form->id }}">
      <input type="hidden" name="source" value="{{ $source }}">
      <input type="hidden" name="{{ \App\Support\SpamGuard::TOKEN_FIELD }}" value="{{ $challenge['token'] }}">

      <div class="nlw__hp" aria-hidden="true">
        <label for="{{ $id }}-website">Strona WWW</label>
        <input id="{{ $id }}-website" type="text" name="{{ \App\Support\SpamGuard::HONEYPOT_FIELD }}" tabindex="-1" autocomplete="off">
      </div>

      <div class="nlw__row">
        @if ($form->ask_name)
        <div class="nlw__field">
          <label for="{{ $id }}-name" class="nlw__label">Imię <span class="nlw__opt">(opcjonalnie)</span></label>
          <input id="{{ $id }}-name" class="nlw__input" type="text" name="name" value="{{ old('name') }}" autocomplete="given-name" maxlength="100">
        </div>
        @endif
        <div class="nlw__field">
          <label for="{{ $id }}-email" class="nlw__label">Adres e-mail</label>
          <input id="{{ $id }}-email" class="nlw__input" type="email" name="email" value="{{ old('email') }}" autocomplete="email" inputmode="email"
                 required aria-required="true" aria-describedby="{{ $id }}-email-err" @error('email') aria-invalid="true" @enderror data-nlw-email>
          <p id="{{ $id }}-email-err" class="nlw__error" @error('email') @else hidden @enderror data-nlw-email-err>@error('email'){{ $message }}@enderror</p>
        </div>
      </div>

      @if ($form->ask_phone)
      <div class="nlw__field">
        <label for="{{ $id }}-phone" class="nlw__label">Telefon <span class="nlw__opt">(opcjonalnie{{ $form->offer_sms ? ', do powiadomień SMS' : '' }})</span></label>
        <input id="{{ $id }}-phone" class="nlw__input nlw__input--short" type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel" inputmode="tel" maxlength="30">
        @error('phone')<p class="nlw__error">{{ $message }}</p>@enderror
      </div>
      @endif

      @if ($form->show_topics && count($topics) > 0)
      <fieldset class="nlw__topics">
        <legend class="nlw__label">Co Cię interesuje?</legend>
        @foreach ($topics as $key => $label)
          <label class="nlw__chip"><input type="checkbox" name="topics[]" value="{{ $key }}" @checked($old !== null ? in_array($key, (array) $old, true) : in_array($key, $defaults, true))> {{ $label }}</label>
        @endforeach
      </fieldset>
      @error('topics')<p class="nlw__error">{{ $message }}</p>@enderror
      @endif

      <div class="nlw__field">
        <label for="{{ $id }}-answer" class="nlw__label">Zabezpieczenie: {{ $challenge['question'] }}</label>
        <input id="{{ $id }}-answer" class="nlw__input nlw__input--short" type="text" name="{{ \App\Support\SpamGuard::ANSWER_FIELD }}"
               required aria-required="true" autocomplete="off" aria-describedby="{{ $id }}-answer-help" @error('form_answer') aria-invalid="true" @enderror>
        <p id="{{ $id }}-answer-help" class="nlw__help">Wpisz wynik słownie lub cyfrą.</p>
        @error('form_answer')<p class="nlw__error">{{ $message }}</p>@enderror
      </div>

      <label class="nlw__consent">
        <input type="checkbox" name="consent" value="1" required aria-required="true" aria-describedby="{{ $id }}-consent-err" @checked(old('consent')) data-nlw-consent>
        <span>{{ $form->consent_text }}
          @if ($form->privacy_url) <a href="{{ $form->privacy_url }}">Jak przetwarzamy dane</a>.@endif</span>
      </label>
      <p id="{{ $id }}-consent-err" class="nlw__error" @error('consent') @else hidden @enderror data-nlw-consent-err>@error('consent'){{ $message }}@enderror</p>

      @if ($form->offer_sms && $form->ask_phone)
      <label class="nlw__consent">
        <input type="checkbox" name="consent_sms" value="1" @checked(old('consent_sms'))>
        <span>Chcę otrzymywać także krótkie powiadomienia SMS na podany numer (opcjonalnie).</span>
      </label>
      @endif

      <div class="nlw__actions">
        <button type="submit" class="nlw__btn" data-nlw-submit><span data-nlw-label>{{ $form->button_label }}</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
        <p class="nlw__note">Bez spamu. Rezygnacja w każdej chwili.</p>
      </div>

      <p class="nlw__status" role="status" aria-live="polite" tabindex="-1" data-nlw-status data-success="{{ $form->success_message }}"></p>
    </form>
  </div>
</section>
@once
<script>
(function(){
  document.querySelectorAll('[data-nlw-form]').forEach(function(form){
    if (form.dataset.nlwReady) return; form.dataset.nlwReady = '1';
    var email = form.querySelector('[data-nlw-email]'), emailEr = form.querySelector('[data-nlw-email-err]');
    var consent = form.querySelector('[data-nlw-consent]'), consEr = form.querySelector('[data-nlw-consent-err]');
    var btn = form.querySelector('[data-nlw-submit]'), label = form.querySelector('[data-nlw-label]'), status = form.querySelector('[data-nlw-status]');
    var original = label.textContent;
    function showError(el, msgEl, msg){ msgEl.textContent = msg; msgEl.hidden = !msg; el.setAttribute('aria-invalid', msg ? 'true' : 'false'); }
    function validate(){
      var ok = true, v = email.value.trim();
      if (!v) { showError(email, emailEr, 'Podaj adres e-mail.'); ok = false; }
      else if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) { showError(email, emailEr, 'Ten adres wygląda na niepoprawny. Sprawdź, czy zawiera znak @ i domenę.'); ok = false; }
      else showError(email, emailEr, '');
      if (!consent.checked) { showError(consent, consEr, 'Zaznacz zgodę, aby się zapisać.'); ok = false; } else showError(consent, consEr, '');
      if (!ok) (v ? consent : email).focus();
      return ok;
    }
    email.addEventListener('blur', validate);
    form.addEventListener('submit', function(e){
      e.preventDefault();
      status.textContent = ''; status.className = 'nlw__status';
      if (!validate()) return;
      btn.disabled = true; btn.setAttribute('aria-busy', 'true'); label.textContent = 'Zapisuję…';
      fetch(form.action, { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: new FormData(form), credentials: 'same-origin' })
        .then(function(res){ return res.json().catch(function(){ return {}; }).then(function(data){ return { res: res, data: data }; }); })
        .then(function(r){
          if (r.res.ok) {
            form.querySelectorAll('.nlw__row, .nlw__topics, .nlw__consent, .nlw__actions, .nlw__field, .nlw__error').forEach(function(n){ n.hidden = true; });
            status.className = 'nlw__status nlw__status--ok'; status.textContent = status.dataset.success; status.focus();
          } else if (r.res.status === 422 && r.data.errors) {
            var first = ''; Object.keys(r.data.errors).forEach(function(k){ if (!first) first = r.data.errors[k][0]; });
            if (r.data.errors.email) showError(email, emailEr, r.data.errors.email[0]);
            if (r.data.errors.consent) showError(consent, consEr, r.data.errors.consent[0]);
            status.className = 'nlw__status nlw__status--err'; status.textContent = first || 'Popraw dane w formularzu.';
            if (r.data.errors.form_answer) { form.submit(); }
          } else if (r.res.status === 429) {
            status.className = 'nlw__status nlw__status--err'; status.textContent = 'Zbyt wiele prób. Spróbuj ponownie za minutę.';
          } else { throw new Error('server'); }
        })
        .catch(function(){ status.className = 'nlw__status nlw__status--err'; status.textContent = 'Nie udało się wysłać formularza. Spróbuj ponownie za chwilę.'; })
        .finally(function(){ btn.disabled = false; btn.removeAttribute('aria-busy'); label.textContent = original; });
    });
  });
})();
</script>
@endonce
