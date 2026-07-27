{{--
    Wybierak kategorii aktualności jako pionowa lista do bocznej kolumny
    (sidebar), w stylu spójnym z marką (aktywna pozycja na kolorze marki, hover
    w odcieniu marki). Same linki — działa bez JS i jest w pełni dostępny
    (klawiatura, aria-current na aktywnej pozycji).

    Parametry:
      $categories — kolekcja NewsCategory
      $active     — aktywna kategoria (model) lub null („Wszystkie")
      $baseUrl    — adres bazowy (np. route('news.index') albo route('home'))
      $anchor     — opcjonalna kotwica doklejana do linków (np. 'aktualnosci')
--}}
@php
    $anchor ??= null;
    $suffix = $anchor ? '#'.$anchor : '';
    $itemBase = 'flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand';
    $activeClasses = 'bg-brand font-bold text-white';
    $idleClasses = 'text-ink hover:bg-brand-light hover:text-brand';
@endphp

@if ($categories->isNotEmpty())
    <nav aria-label="Kategorie aktualności">
        <p class="mb-2 px-3 text-xs font-bold uppercase tracking-wide text-muted">Kategorie</p>
        <ul class="space-y-1">
            <li>
                <a href="{{ $baseUrl.$suffix }}"
                    @class([$itemBase, $activeClasses => ! $active, $idleClasses => (bool) $active])
                    @unless ($active) aria-current="page" @endunless>
                    <span class="flex h-4 w-4 flex-none items-center justify-center rounded-full {{ ! $active ? 'bg-white/25' : 'bg-gray-200' }}" aria-hidden="true">
                        <i class="fa-solid fa-layer-group text-[0.6rem] {{ ! $active ? 'text-white' : 'text-gray-500' }}"></i>
                    </span>
                    Wszystkie
                </a>
            </li>
            @foreach ($categories as $category)
                @php $isActive = $active && $active->id === $category->id; @endphp
                <li>
                    <a href="{{ $baseUrl }}?kategoria={{ $category->slug }}{{ $suffix }}"
                        @class([$itemBase, $activeClasses => $isActive, $idleClasses => ! $isActive])
                        @if ($isActive) aria-current="page" @endif>
                        <span class="h-4 w-4 flex-none rounded-full {{ $isActive ? 'ring-2 ring-white/70' : '' }}" style="background-color: {{ $category->badgeColor() }}" aria-hidden="true"></span>
                        {{ $category->name }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif
