{{--
    Uniwersalna rama informacyjna na stronach działań: płaskie, jasnoszare tło jak wiersze listy działań, ikona w jasnym
    polu po lewej, opcjonalny pogrubiony nagłówek, tekst i (opcjonalnie) rząd linków pod spodem.

    Użycie:
      <x-proj-frame icon="fa-coins" title="Forma płatna">
          Tekst ramy (może zawierać <strong>pogrubienia</strong>).
          <x-slot:links><a href="…">Link</a></x-slot:links>
      </x-proj-frame>

    Parametry: icon (klasa Font Awesome, domyślnie fa-circle-info), title (opcjonalny), tone: gray (domyślny) albo brand
    (jasnoniebieskie tło dla ważniejszych informacji). Dodatkowe klasy (np. mb-4) przechodzą na element główny.
    Dostępność: ikona jest dekoracyjna (aria-hidden), kontrast ink na #f3f4f6 ≥ 14:1, linki podkreślone; w trybie wysokiego
    kontrastu ramka dostaje obwódkę (layouts/site.blade.php).
--}}
@props(['icon' => 'fa-circle-info', 'title' => null, 'tone' => 'gray'])
@once
    <style>
        .proj-frame { display: flex; align-items: flex-start; gap: .9rem; margin: 0 0 1rem; padding: 1rem 1.25rem; border-radius: .5rem; background: #f3f4f6; color: #1d1d1a; font-size: 1.0625rem; line-height: 1.6; }
        .proj-frame--brand { background: var(--color-brand-light); }
        .proj-frame-ico { display: inline-flex; flex: none; width: 2rem; height: 2rem; align-items: center; justify-content: center; border-radius: .375rem; background: var(--color-brand-light); color: var(--color-brand-dark); font-size: 1rem; }
        .proj-frame--brand .proj-frame-ico { background: #fff; }
        .proj-frame-body { min-width: 0; flex: 1 1 auto; }
        .proj-frame-title { margin: 0 0 .25rem; font-weight: 800; color: #1d1d1a; }
        .proj-frame-text { margin: 0; font-weight: 500; }
        .proj-frame-text a { font-weight: 800; color: var(--color-brand-dark); text-decoration: underline; text-underline-offset: 4px; }
        .proj-frame-text a:hover { text-decoration: none; }
        .proj-frame-links { display: flex; flex-wrap: wrap; gap: .5rem 1.25rem; margin-top: .75rem; }
        .proj-frame-links a { font-weight: 800; color: var(--color-brand-dark); text-decoration: underline; text-underline-offset: 4px; }
        .proj-frame-links a:hover { text-decoration: none; }
        .proj-frame-text a:focus-visible, .proj-frame-links a:focus-visible { outline: 2px solid var(--color-brand-dark); outline-offset: 3px; border-radius: .25rem; }
    </style>
@endonce
<div {{ $attributes->class(['proj-frame', 'proj-frame--brand' => $tone === 'brand']) }}>
    <span class="proj-frame-ico" aria-hidden="true"><i class="fa-solid {{ $icon }}"></i></span>
    <div class="proj-frame-body">
        @if (filled($title))<p class="proj-frame-title">{{ $title }}</p>@endif
        <div class="proj-frame-text">{{ $slot }}</div>
        @isset($links)<div class="proj-frame-links">{{ $links }}</div>@endisset
    </div>
</div>
