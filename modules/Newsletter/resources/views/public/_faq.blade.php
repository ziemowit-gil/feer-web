@php
    // Pytania z panelu + stałe odpowiedzi o potwierdzeniu i danych (gdy panel ich nie ma).
    $faqItems = collect($form->faq ?? []);
    $has = fn (string $needle) => $faqItems->contains(fn ($i) => str_contains(mb_strtolower($i['q']), $needle));
    if (! $has('potwierdz')) {
        $faqItems->push(['q' => 'Dlaczego muszę potwierdzić zapis?', 'a' => "Dzięki temu nikt nie zapisze Cię bez Twojej wiedzy. Po wysłaniu formularza dostaniesz e-mail z linkiem — kliknij go w ciągu {$ttlDays} dni. Bez potwierdzenia nie wyślemy Ci nic więcej, a dane wygasłego zapisu usuwamy."]);
    }
    if (! $has('wypis')) {
        $faqItems->push(['q' => 'Jak się wypisać lub zmienić tematy?', 'a' => 'W stopce każdej wiadomości są dwa linki: do zmiany tematów i do wypisu. Wystarczy jedno kliknięcie, bez logowania.']);
    }
    if (! $has('dane')) {
        $faqItems->push(['q' => 'Co robicie z moimi danymi?', 'a' => 'Adresu e-mail i imienia używamy tylko do wysyłki newslettera. Nie przekazujemy ich nikomu do celów reklamowych. W rejestrze zgód przechowujemy datę i treść zgody, żeby móc wykazać, że zapis był dobrowolny.']);
    }
@endphp
<div class="nls-faq">
  @foreach ($faqItems as $item)
    <details><summary>{{ $item['q'] }}</summary><p>{{ $item['a'] }}</p></details>
  @endforeach
</div>
@if ($form->privacy_url)<p class="nls-note" style="margin-top:12px">Szczegóły: <a href="{{ $form->privacy_url }}">polityka prywatności</a>.</p>@endif
