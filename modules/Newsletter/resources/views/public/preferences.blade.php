@extends('layouts.site')

@section('title', 'Preferencje newslettera — ' . $siteSettings->site_name)

@section('content')
@include('newsletter::public._style')
@php
    $stateCls = match ($subscriber->status) { 'confirmed' => 'np__state--ok', 'pending' => 'np__state--warn', default => 'np__state--off' };
    $rows = $topicRows->isNotEmpty() ? $topicRows->map(fn ($t) => ['key' => $t->key, 'label' => $t->label, 'description' => $t->description, 'icon' => $t->icon])->all()
        : collect($topics)->map(fn ($l, $k) => ['key' => $k, 'label' => $l, 'description' => null, 'icon' => null])->values()->all();
    $selected = old('topics', $subscriber->topics ?? []);
@endphp
<div class="np">
    <div class="np__head">
        <div>
            <p class="np__eyebrow">Newsletter {{ $siteSettings->site_name }}</p>
            <h1 class="np__title">Twoje preferencje</h1>
            <p class="np__lead">Zdecyduj, o czym chcesz czytać i jak mamy się z Tobą kontaktować. Zmiany działają od razu.<br>Adres: <strong>{{ $subscriber->email }}</strong></p>
        </div>
        <span class="np__state {{ $stateCls }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">@if($subscriber->status === 'confirmed')<path d="M4 12l5 5L20 6"/>@elseif($subscriber->status === 'pending')<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>@else<path d="M6 6l12 12M18 6L6 18"/>@endif</svg>{{ $subscriber->statusLabel() }}</span>
    </div>

    @if (session('status'))<p class="np__flash" role="status">{{ session('status') }}</p>@endif
    @if ($errors->any())<p class="np__err" role="alert">{{ $errors->first() }}</p>@endif
    @if ($subscriber->status === 'unsubscribed')<p class="np__err" role="status" style="background:#FEF3C7;color:#78350F">Jesteś wypisany/-a. Zapisanie preferencji zapisze Cię ponownie — wyślemy e-mail z linkiem potwierdzającym.</p>@endif

    <form method="post" action="{{ route('newsletter.preferences.update', ['token' => $subscriber->token]) }}" class="np__grid">
        @csrf @method('PUT')
        <div>
            <section class="np__card" aria-labelledby="np-topics">
                <h2 id="np-topics" class="np__h2"><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h10M4 18h7"/></svg></span>O czym chcesz czytać</h2>
                <p class="np__hint">Zaznacz przynajmniej jeden temat. Wysyłamy tylko to, co wybierzesz.</p>
                <div class="np__tiles">
                    @foreach ($rows as $t)
                        <label class="np__tile">
                            <input type="checkbox" name="topics[]" value="{{ $t['key'] }}" @checked(in_array($t['key'], $selected, true))>
                            <span><strong>@if($t['icon'])<i class="fa-solid {{ $t['icon'] }}" aria-hidden="true"></i>@endif{{ $t['label'] }}</strong>@if($t['description'])<small>{{ $t['description'] }}</small>@endif</span>
                        </label>
                    @endforeach
                </div>
            </section>

            <section class="np__card" aria-labelledby="np-channels">
                <h2 id="np-channels" class="np__h2"><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16v12H4z"/><path d="M4 7l8 6 8-6"/></svg></span>Jak się kontaktujemy</h2>
                <p class="np__hint">E-mail jest podstawowym kanałem. Pozostałe włączasz dodatkowo.</p>
                <label class="np__check"><input type="checkbox" checked disabled><span>E-mail na {{ $subscriber->email }}<small>Podstawowy kanał newslettera.</small></span></label>
                @if ($pushEnabled)
                <label class="np__check"><input type="checkbox" name="channels[]" value="webpush" @checked($subscriber->hasChannel('webpush')) data-nl-push-toggle><span>Powiadomienia push w tej przeglądarce<small data-nl-push-status>Krótkie powiadomienie o nowym newsletterze, bez otwierania poczty.</small></span></label>
                @endif
                @if (filled($subscriber->phone))
                <label class="np__check"><input type="checkbox" name="channels[]" value="sms" @checked($subscriber->hasChannel('sms'))><span>SMS na numer {{ substr((string) $subscriber->phone, 0, 6) }}…<small>Tylko najważniejsze informacje, rzadziej niż e-mail.</small></span></label>
                @endif
            </section>

            @if ($lists->isNotEmpty())
            <section class="np__card" aria-labelledby="np-lists">
                <h2 id="np-lists" class="np__h2"><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg></span>Listy specjalne</h2>
                <p class="np__hint">Dodatkowe wysyłki dla wybranych grup.</p>
                @foreach ($lists as $list)
                    <label class="np__check"><input type="checkbox" name="lists[]" value="{{ $list->id }}" @checked($subscriber->lists->contains($list->id))><span>{{ $list->name }}@if($list->description)<small>{{ $list->description }}</small>@endif</span></label>
                @endforeach
            </section>
            @endif

            <div class="np__actions">
                <button type="submit" class="np__btn np__btn--primary">Zapisz preferencje <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M4 12l5 5L20 6"/></svg></button>
                <a href="{{ route('newsletter.unsubscribe', ['token' => $subscriber->token]) }}" class="np__btn np__btn--ghost">Wypisz mnie całkowicie</a>
            </div>
        </div>

        <aside>
            <section class="np__card" aria-labelledby="np-profile">
                <h2 id="np-profile" class="np__h2"><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/></svg></span>Twoje dane</h2>
                <label for="name" class="np__label">Imię <span style="font-weight:400;color:var(--np-muted)">(jak mamy się zwracać)</span></label>
                <input id="name" name="name" type="text" value="{{ old('name', $subscriber->name) }}" maxlength="100" autocomplete="given-name" class="np__input">
                <ul class="np__meta" style="margin-top:18px">
                    <li><span>Zapisano</span><b>{{ $subscriber->created_at?->format('d.m.Y') }}</b></li>
                    <li><span>Potwierdzono</span><b>{{ $subscriber->confirmed_at?->format('d.m.Y') ?? '—' }}</b></li>
                    <li><span>Ostatnia wiadomość</span><b>{{ $subscriber->last_sent_at?->format('d.m.Y') ?? 'jeszcze żadnej' }}</b></li>
                </ul>
            </section>

            <section class="np__card np__rodo" aria-labelledby="np-rodo">
                <h2 id="np-rodo" class="np__h2"><span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/></svg></span>Twoje prawa (RODO)</h2>
                <p class="np__hint">Przechowujemy tylko to, co widzisz na tej stronie, oraz historię wysyłek. Możesz to pobrać albo usunąć.</p>
                <p style="margin:0 0 12px"><a href="{{ route('newsletter.preferences.export', ['token' => $subscriber->token]) }}" class="np__link">Pobierz moje dane (JSON)</a></p>
                <p style="margin:0;font-size:14px;color:var(--np-muted)">Usunięcie danych jest nieodwracalne: adres, imię i telefon zostaną zanonimizowane, a na 30 dni trafią na listę blokad, byśmy przypadkiem nic nie wysłali.</p>
            </section>
        </aside>
    </form>

    <form method="post" action="{{ route('newsletter.preferences.destroy', ['token' => $subscriber->token]) }}" style="max-width:1040px;margin:12px 0 0" onsubmit="return confirm('Usunąć wszystkie Twoje dane z newslettera? Tej operacji nie można cofnąć.');">
        @csrf @method('DELETE')
        <button type="submit" class="np__btn np__btn--danger">Usuń moje dane</button>
    </form>
</div>
@if ($pushEnabled)
<script>
(function(){
  var toggle = document.querySelector('[data-nl-push-toggle]'), status = document.querySelector('[data-nl-push-status]');
  if (!toggle) return;
  if (!('serviceWorker' in navigator) || !('PushManager' in window)) { status.textContent = 'Ta przeglądarka nie obsługuje powiadomień push.'; toggle.disabled = true; return; }
  toggle.addEventListener('change', function(){
    if (!toggle.checked) return;
    navigator.serviceWorker.ready.then(function(reg){
      return reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: '{{ config('webpush.vapid.public_key') }}' });
    }).then(function(sub){
      return fetch('{{ route('newsletter.preferences.push', ['token' => $subscriber->token]) }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify(sub.toJSON()) });
    }).then(function(){ status.textContent = 'Powiadomienia push włączone w tej przeglądarce.'; })
      .catch(function(){ status.textContent = 'Nie udało się włączyć powiadomień — sprawdź uprawnienia przeglądarki.'; toggle.checked = false; });
  });
})();
</script>
@endif
@endsection
