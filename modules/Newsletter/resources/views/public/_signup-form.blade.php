{{--
    Formularz zapisu stron /newsletter (zwykła i landing). Kolejność: e-mail i imię → tematy → pytanie kontrolne
    → zgoda z informacją o double opt-in → jeden przycisk. Działa bez JS (POST + przekierowanie na „Sprawdź skrzynkę”);
    z JS wysyła w tle i pokazuje stan „Sprawdź skrzynkę” w miejscu formularza.

    Parametry: $form, $source (rozróżnia formularze na jednej stronie — błędy serwera trafiają do tego, który wysłano),
    $uid, $ttlDays, $topicHints, opcjonalnie $panelTitle, $panelSub, $headingLevel (2/3).
--}}
@php
    $challenge = \App\Support\SpamGuard::challenge();
    $topics = $form->topicOptions();
    $defaults = $form->default_topics ?: [];
    // Błędy i stare wartości tylko w formularzu, który faktycznie wysłano.
    $mine = old('source') === $source;
    $err = fn (string $key) => $mine && $errors->has($key) ? $errors->first($key) : null;
    $val = fn (string $key, $default = null) => $mine ? old($key, $default) : $default;
    $oldTopics = $mine ? old('topics') : null;
    $fieldOrder = ['email' => 'email', 'name' => 'name', 'phone' => 'phone', 'topics' => 'topics', 'form_answer' => 'answer', 'consent' => 'consent'];
    $summary = $mine ? collect($fieldOrder)->filter(fn ($id, $key) => $errors->has($key))->map(fn ($id, $key) => ['id' => "{$uid}-{$id}", 'msg' => $errors->first($key)]) : collect();
    $h = 'h' . ($headingLevel ?? 2);
    $hSub = 'h' . (($headingLevel ?? 2) + 1);
    $showTopics = $form->show_topics && count($topics) > 0;
@endphp
<div class="nls-panel" data-nls>
  <{{ $h }} id="{{ $uid }}-title" class="nls-panel__title" data-nls-heading>{{ $panelTitle ?? 'Zapisz się' }}</{{ $h }}>
  <p class="nls-panel__sub" data-nls-sub>{{ $panelSub ?? 'Wystarczy adres e-mail. Zajmie to mniej niż minutę.' }}</p>

  <form class="nls-form" action="{{ route('newsletter.subscribe') }}" method="post" novalidate aria-labelledby="{{ $uid }}-title" data-nls-form>
    @csrf
    <input type="hidden" name="form_id" value="{{ $form->id }}">
    <input type="hidden" name="source" value="{{ $source }}">
    <input type="hidden" name="{{ \App\Support\SpamGuard::TOKEN_FIELD }}" value="{{ $challenge['token'] }}">
    <div class="nls-hp" aria-hidden="true">
      <label for="{{ $uid }}-website">Nie wypełniaj tego pola</label>
      <input id="{{ $uid }}-website" type="text" name="{{ \App\Support\SpamGuard::HONEYPOT_FIELD }}" tabindex="-1" autocomplete="off">
    </div>

    <div class="nls-summary" role="alert" tabindex="-1" data-nls-summary @if($summary->isEmpty()) hidden @endif>
      <{{ $hSub }}>Popraw {{ $summary->count() === 1 ? 'jedno pole' : 'zaznaczone pola' }}, aby się zapisać</{{ $hSub }}>
      <ul data-nls-summary-list>@foreach ($summary as $item)<li><a href="#{{ $item['id'] }}">{{ $item['msg'] }}</a></li>@endforeach</ul>
    </div>

    <div class="nls-row">
      <div class="nls-field">
        <label for="{{ $uid }}-email" class="nls-label">Adres e-mail</label>
        <input id="{{ $uid }}-email" class="nls-input" type="email" name="email" value="{{ $val('email') }}" autocomplete="email" inputmode="email" spellcheck="false"
               required aria-required="true" aria-describedby="{{ $uid }}-email-err {{ $uid }}-email-hint" @if($err('email')) aria-invalid="true" @endif data-nls-email>
        <p id="{{ $uid }}-email-err" class="nls-error" @if(! $err('email')) hidden @endif data-nls-error="email">{{ $err('email') }}</p>
        <p id="{{ $uid }}-email-hint" class="nls-hint">Wyślemy na niego link potwierdzający.</p>
      </div>
      @if ($form->ask_name)
      <div class="nls-field">
        <label for="{{ $uid }}-name" class="nls-label">Imię <span class="nls-opt">(nieobowiązkowe)</span></label>
        <input id="{{ $uid }}-name" class="nls-input" type="text" name="name" value="{{ $val('name') }}" autocomplete="given-name" maxlength="100" aria-describedby="{{ $uid }}-name-hint">
        <p id="{{ $uid }}-name-hint" class="nls-hint">Żebyśmy mogli się zwracać po imieniu.</p>
      </div>
      @endif
    </div>

    @if ($form->ask_phone)
    <div class="nls-field">
      <label for="{{ $uid }}-phone" class="nls-label">Telefon <span class="nls-opt">(nieobowiązkowe{{ $form->offer_sms ? ', do powiadomień SMS' : '' }})</span></label>
      <input id="{{ $uid }}-phone" class="nls-input nls-input--short" type="tel" name="phone" value="{{ $val('phone') }}" autocomplete="tel" inputmode="tel" maxlength="30" @if($err('phone')) aria-invalid="true" aria-describedby="{{ $uid }}-phone-err" @endif>
      @if ($err('phone'))<p id="{{ $uid }}-phone-err" class="nls-error">{{ $err('phone') }}</p>@endif
    </div>
    @endif

    @if ($showTopics)
    <fieldset id="{{ $uid }}-topics" class="nls-topics" tabindex="-1" aria-describedby="{{ $uid }}-topics-hint {{ $uid }}-topics-err" @if($err('topics')) aria-invalid="true" @endif data-nls-topics>
      <legend class="nls-label">O czym chcesz dostawać wiadomości?</legend>
      <p id="{{ $uid }}-topics-hint" class="nls-hint">Zaznacz co najmniej jeden temat. Zmienisz to w każdej chwili przez link w stopce wiadomości.</p>
      <p id="{{ $uid }}-topics-err" class="nls-error" @if(! $err('topics')) hidden @endif data-nls-error="topics">{{ $err('topics') }}</p>
      <div class="nls-topics__list">
        @foreach ($topics as $key => $label)
          <label class="nls-check">
            <input type="checkbox" name="topics[]" value="{{ $key }}" @checked($oldTopics !== null ? in_array($key, (array) $oldTopics, true) : in_array($key, $defaults, true))>
            <span><strong>{{ $label }}</strong>@if (! empty($topicHints[$key]))<small>{{ $topicHints[$key] }}</small>@endif</span>
          </label>
        @endforeach
      </div>
    </fieldset>
    @endif

    <div class="nls-field">
      <label for="{{ $uid }}-answer" class="nls-label">Pytanie kontrolne: {{ $challenge['question'] }}</label>
      <input id="{{ $uid }}-answer" class="nls-input nls-input--short" type="text" name="{{ \App\Support\SpamGuard::ANSWER_FIELD }}" inputmode="numeric"
             required aria-required="true" autocomplete="off" aria-describedby="{{ $uid }}-answer-err {{ $uid }}-answer-hint" @if($err('form_answer')) aria-invalid="true" @endif data-nls-answer>
      <p id="{{ $uid }}-answer-err" class="nls-error" @if(! $err('form_answer')) hidden @endif data-nls-error="form_answer">{{ $err('form_answer') }}</p>
      <p id="{{ $uid }}-answer-hint" class="nls-hint">Chroni przed automatycznymi zapisami. Wpisz wynik cyfrą lub słownie.</p>
    </div>

    <div class="nls-field">
      <label class="nls-consent">
        <input id="{{ $uid }}-consent" type="checkbox" name="consent" value="1" required aria-required="true" aria-describedby="{{ $uid }}-consent-err" @checked($val('consent')) @if($err('consent')) aria-invalid="true" @endif data-nls-consent>
        <span>{{ $form->consent_text }}@if ($form->privacy_url) <a href="{{ $form->privacy_url }}">Jak przetwarzamy dane</a>.@endif</span>
      </label>
      <p id="{{ $uid }}-consent-err" class="nls-error" @if(! $err('consent')) hidden @endif data-nls-error="consent">{{ $err('consent') }}</p>
    </div>

    @if ($form->offer_sms && $form->ask_phone)
    <label class="nls-consent">
      <input type="checkbox" name="consent_sms" value="1" @checked($val('consent_sms'))>
      <span>Chcę dostawać także krótkie powiadomienia SMS na podany numer (nieobowiązkowe).</span>
    </label>
    @endif

    <p class="nls-next" id="{{ $uid }}-next"><strong>Co dalej?</strong>Wyślemy Ci e-mail z linkiem. Zapis zacznie działać dopiero, gdy go klikniesz (link jest ważny {{ $ttlDays }} {{ $ttlDays === 1 ? 'dzień' : 'dni' }}). Bez potwierdzenia nie wyślemy nic więcej.</p>

    <button type="submit" class="nls-btn" aria-describedby="{{ $uid }}-next" data-nls-submit><span data-nls-label>{{ $form->button_label ?: 'Zapisz się' }}</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
    <p class="nls-status" role="status" aria-live="polite" data-nls-status></p>

    <div class="nls-rodo" id="{{ $uid }}-rodo">
      <p>Administratorem danych jest {{ $siteSettings->site_name }}. Adres e-mail{{ $form->ask_name ? ' i imię' : '' }} przetwarzamy tylko po to, by wysyłać newsletter, na podstawie Twojej zgody (art. 6 ust. 1 lit. a RODO), do czasu jej wycofania. Masz prawo dostępu do danych, ich sprostowania, usunięcia, ograniczenia przetwarzania, przenoszenia, sprzeciwu i wycofania zgody w każdej chwili oraz prawo skargi do Prezesa UODO.</p>
      <details>
        <summary>Pełna klauzula informacyjna</summary>
        @foreach ($form->gdprInfoParagraphs() as $paragraph)<p>{{ $paragraph }}</p>@endforeach
      </details>
      @if ($form->privacy_url)<p><a href="{{ $form->privacy_url }}">Polityka prywatności</a></p>@endif
    </div>
  </form>

  <div class="nls-done" tabindex="-1" hidden data-nls-done>
    <span class="nls-done__mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18v12H3z"/><path d="M3 7l9 6 9-6"/></svg></span>
    <{{ $h }} class="nls-done__title">Sprawdź skrzynkę</{{ $h }}>
    <p>Wysłaliśmy link potwierdzający na adres <strong data-nls-done-email></strong>.</p>
    <ol class="nls-steps">
      <li><span>Otwórz wiadomość „Potwierdź zapis na newsletter”.</span></li>
      <li><span>Kliknij „Potwierdzam zapis”. Link jest ważny {{ $ttlDays }} {{ $ttlDays === 1 ? 'dzień' : 'dni' }}.</span></li>
      <li><span>Nie ma wiadomości po kilku minutach? Zajrzyj do folderu spam lub oferty.</span></li>
    </ol>
    <p><button type="button" class="nls-btn nls-btn--ghost" data-nls-again>Popraw adres e-mail</button></p>
  </div>
</div>
