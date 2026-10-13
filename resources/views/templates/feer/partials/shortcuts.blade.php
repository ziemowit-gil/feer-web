{{--
    Szablon FEER — „Na skróty" (moduł quick_actions; „Szybkie akcje" to tylko nazwa administracyjna).
    Wyraźne kafle w jednym rzędzie. Wygląd zależy od ustawień:
      • akcja zwykła  → płaski kafel bez obramówki: tło odcina się od tła sekcji (białe na szarej sekcji,
                        jasnoszare na białej), ikona w kolorze akcji, ciemny tekst,
      • akcja „Negatyw" (is_negative) → wypełnione tłem w kolorze akcji, tekst dobrany pod kontrast ≥ 4,5:1,
      • tło sekcji: białe, gdy włączono „Białe tło sekcji" (Ustawienia → Strona główna), w przeciwnym razie jasnoszare.
    Ankieta (moduł polls) stoi obok kafli, gdy jest aktywna. Obsługiwane: „Negatyw”, „Pasek” (niski kafel), „Kolumny” (szerokość 2–3 kolumn), „Białe tło sekcji”.
    Kolor ikony jest przyciemniany do kontrastu ≥ 4,5:1 na bieli (WCAG 1.4.11).
--}}
@php
    // Kolory nazwane → paleta brandbooka (niebieski #1E6DFF, grafit #1D1D1A, pomarańcz #EA8F00); fioletowy zastąpiony firmowym niebieskim; zielony/czerwony zostają dla starszych wpisów.
    $feerNamed = \App\Support\ThemePalette::named();
    $panelWhite = (bool) ($siteSettings->quick_actions_panel_negative ?? false);
    // Jedna akcja → jeden duży przycisk na całą szerokość (wyższy, większy tekst i ikona).
    $single = ($quickLinks ?? collect())->count() === 1;
    $qaN = min(($quickLinks ?? collect())->count(), 4);
    $qaCols = match ($qaN) {
        1 => 'grid-cols-1',
        2 => 'grid-cols-1 sm:grid-cols-2',
        3 => 'grid-cols-1 sm:grid-cols-3',
        default => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
    };
    // Szerokość kafla („Kolumny” w panelu): zajmuje 2–3 kolumny siatki, ale nie więcej niż jest kolumn.
    $qaSpan = fn (int $cols) => match (true) {
        $cols >= 3 && $qaN >= 3 => 'sm:col-span-2 lg:col-span-'.min($cols, $qaN),
        $cols >= 2 && $qaN >= 2 => 'sm:col-span-2',
        default => '',
    };
@endphp
@php $poll ??= null; $hasLinks = ($quickLinks ?? collect())->isNotEmpty(); @endphp
@if ($hasLinks || $poll)
    <section class="{{ $panelWhite ? 'bg-white' : 'bg-gray-50' }} py-10" aria-labelledby="{{ $hasLinks ? 'feer-shortcuts-heading' : 'feer-poll-heading' }}">
        <div class="mx-auto max-w-6xl px-4 {{ ($hasLinks && $poll) ? 'grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem]' : '' }}">
          @if ($hasLinks)
          <div class="min-w-0">
            <div class="mb-6 flex items-end justify-between gap-4">
                <h2 id="feer-shortcuts-heading" class="text-2xl font-bold text-ink md:text-3xl">Na skróty</h2>
                @if (auth()->check() && auth()->user()->isAdmin())
                    <a href="{{ route('admin.szybkie-akcje.index') }}" class="shrink-0 inline-flex min-h-9 items-center gap-1.5 text-sm font-bold text-ink underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                        <i class="fa-solid fa-gear" aria-hidden="true"></i>Zarządzaj skrótami
                    </a>
                @endif
            </div>

            <nav aria-label="Na skróty">
                <ul class="grid auto-rows-fr gap-4 {{ $qaCols }}" role="list">
                    @foreach ($quickLinks as $qa)
                        @php
                            $base = \App\Support\Color::isValid($qa->color) ? $qa->color : ($feerNamed[$qa->color] ?? $feerNamed['blue']);
                            $safe = $siteSettings->contrastSafeColor($base);
                            $filled = (bool) $qa->is_negative;
                            $strip = (bool) $qa->strip && ! $single; // „Pasek”: niski kafel, ikona obok tekstu
                            $pal = $filled ? \App\Support\ThemePalette::button($base) : null;
                            $iconClass = (str_contains((string) $qa->icon, 'fa-') || str_starts_with((string) $qa->icon, 'bi ')) ? $qa->icon : 'bi '.($qa->icon ?: 'bi-lightning');
                            $external = \Illuminate\Support\Str::startsWith($qa->url, ['http://', 'https://']) && ! \Illuminate\Support\Str::contains($qa->url, request()->getHost());
                        @endphp
                        @php $card = ! $single && ! $strip; // zwykły kafel: układ pionowy, wszystkie kafle w rzędzie równej wysokości @endphp
                        <li class="flex {{ $qaSpan((int) ($qa->cols ?? 1)) }}">
                            <a href="{{ $qa->url }}" @if ($external) target="_blank" rel="noopener" @endif
                               class="group flex w-full {{ $single ? 'min-h-32 items-center gap-6 px-8 py-6' : ($strip ? 'min-h-14 items-center gap-3 px-4 py-2' : 'h-full min-h-44 flex-col gap-3 p-5') }} rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 {{ $filled ? '' : ($panelWhite ? 'bg-gray-50 hover:bg-brand-light' : 'bg-white hover:bg-brand-light') }}"
                               @if ($filled)
                                   style="background-color: {{ $pal['bg'] }}; color: {{ $pal['text'] }}"
                               @else
                                   style="color: #1d1d1a"
                               @endif>
                                {!! icon_html($qa->icon, 'flex-none '.($single ? 'w-12 text-center text-5xl' : ($strip ? 'w-6 text-center text-xl' : 'text-3xl')), $filled ? '' : 'color: '.$safe) !!}
                                <span class="min-w-0 {{ $card ? '' : 'flex-1' }}">
                                    <span class="block font-bold leading-snug group-hover:underline group-hover:underline-offset-4 {{ $single ? 'text-2xl md:text-3xl' : ($strip ? 'text-base' : 'text-lg') }}">{{ $qa->label }}</span>
                                    @if (! $strip && ($qa->description ?? null))
                                        <span class="mt-0.5 block text-sm font-medium leading-snug {{ $filled ? '' : 'text-muted' }}">{{ $qa->description }}</span>
                                    @endif
                                </span>
                                @if ($external)<span class="sr-only">(otwiera się w nowej karcie)</span>@endif
                                <span class="flex-none transition {{ $single ? 'text-3xl' : 'text-xl' }} {{ $card ? 'mt-auto self-end' : '' }} group-hover:translate-x-1 motion-reduce:transition-none" aria-hidden="true">→</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
          </div>
          @endif

          {{-- Ankieta (moduł polls): płaskie pole (bez ramki) z paskami wyników; po oddaniu głosu — procenty i podziękowanie. --}}
          @if ($poll)
              @php
                  $votedOptionId = session("voted_polls.{$poll->id}");
                  $totalVotes = $poll->totalVotes();
              @endphp
              <div id="ankieta" class="min-w-0">
                  <div class="mb-6 flex items-end justify-between gap-4">
                      <h2 id="feer-poll-heading" class="text-2xl font-bold text-ink md:text-3xl">Ankieta</h2>
                      @if (auth()->check() && auth()->user()->isAdmin())
                          <a href="{{ route('admin.ankiety.edit', $poll) }}" class="shrink-0 inline-flex min-h-9 items-center gap-1.5 text-sm font-bold text-ink underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                              <i class="fa-solid fa-gear" aria-hidden="true"></i>Zarządzaj sidebarem<span class="sr-only"> (ankieta)</span>
                          </a>
                      @endif
                  </div>
                  <form action="{{ route('polls.vote', $poll) }}" method="POST" class="rounded-md {{ $panelWhite ? 'bg-gray-50' : 'bg-white' }} p-6">
                      @csrf
                      <fieldset>
                          <legend class="mb-4 text-lg font-bold leading-snug text-ink">{{ $poll->question }}</legend>
                          <div class="space-y-3">
                              @foreach ($poll->options as $i => $option)
                                  <label class="block {{ $votedOptionId ? '' : 'cursor-pointer' }}">
                                      <span class="flex items-center gap-2">
                                          <input type="radio" name="option_id" value="{{ $option->id }}"
                                              {{ $votedOptionId ? ($votedOptionId == $option->id ? 'checked' : 'disabled') : ($i === 0 ? 'checked' : '') }}
                                              class="h-4 w-4 accent-brand">
                                          <span class="text-sm font-medium text-ink">{{ $option->label }} ({{ $option->percent($totalVotes) }}%)</span>
                                      </span>
                                      <span class="mt-1 ml-6 block h-2 max-w-xs overflow-hidden bg-gray-200" aria-hidden="true">
                                          <span class="block h-full bg-brand-dark" style="width: {{ $option->percent($totalVotes) }}%"></span>
                                      </span>
                                  </label>
                              @endforeach
                          </div>
                      </fieldset>
                      @if ($votedOptionId)
                          <p class="mt-4 text-sm font-bold text-ink">Dziękujemy za oddanie głosu.</p>
                      @else
                          <button type="submit" class="mt-5 inline-flex min-h-11 items-center rounded-md bg-brand-dark px-6 text-sm font-bold text-white transition-colors hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Głosuj</button>
                      @endif
                  </form>
              </div>
          @endif
        </div>
    </section>
@endif
