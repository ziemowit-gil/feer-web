{{--
    Szablon FEER — „Na skróty" (moduł quick_actions; „Szybkie akcje" to tylko nazwa administracyjna).
    Wyraźne kafle w jednym rzędzie. Wygląd zależy od ustawień:
      • akcja zwykła  → obramówka 2 px w kolorze akcji (domyślnie kolor FEER), białe tło, ciemny tekst,
      • akcja „Negatyw" (is_negative) → wypełnione tłem w kolorze akcji, tekst dobrany pod kontrast ≥ 4,5:1,
      • tło sekcji: białe, gdy włączono „Białe tło sekcji" (Ustawienia → Strona główna), w przeciwnym razie jasnoszare.
    Ankieta (moduł polls) stoi obok kafli, gdy jest aktywna. Obsługiwane: „Negatyw”, „Pasek” (niski kafel), „Kolumny” (szerokość 2–3 kolumn), „Białe tło sekcji”.
    Kolor obramówki jest przyciemniany do kontrastu ≥ 4,5:1 na bieli (WCAG 1.4.11).
--}}
@php
    // Kolory nazwane → paleta brandbooka (niebieski #1E6DFF, grafit #1D1D1A, pomarańcz #EA8F00); fioletowy zastąpiony firmowym niebieskim; zielony/czerwony zostają dla starszych wpisów.
    $feerNamed = ['blue' => '#1e6dff', 'dark' => '#1d1d1a', 'green' => '#166534', 'purple' => '#1e6dff', 'orange' => '#ea8f00', 'red' => '#b91c1c'];
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
            <h2 id="feer-shortcuts-heading" class="mb-6 text-2xl font-bold text-ink md:text-3xl">Na skróty</h2>

            <nav aria-label="Na skróty">
                <ul class="grid gap-4 {{ $qaCols }}" role="list">
                    @foreach ($quickLinks as $qa)
                        @php
                            $base = \App\Support\Color::isValid($qa->color) ? $qa->color : ($feerNamed[$qa->color] ?? $feerNamed['blue']);
                            $safe = $siteSettings->contrastSafeColor($base);
                            $filled = (bool) $qa->is_negative;
                            $strip = (bool) $qa->strip && ! $single; // „Pasek”: niski kafel, ikona obok tekstu
                            $pal = $filled ? \App\Support\Color::button($base) : null;
                            // Kolor firmowy #1E6DFF zostaje dokładnie taki (biały tekst: 4,48:1 — zaakceptowane przez właściciela marki).
                            if ($filled && strtolower($base) === '#1e6dff') { $pal = ['bg' => '#1e6dff', 'text' => '#ffffff', 'hover' => '#1e6dff']; }
                            $iconClass = (str_contains((string) $qa->icon, 'fa-') || str_starts_with((string) $qa->icon, 'bi ')) ? $qa->icon : 'bi '.($qa->icon ?: 'bi-lightning');
                            $external = \Illuminate\Support\Str::startsWith($qa->url, ['http://', 'https://']) && ! \Illuminate\Support\Str::contains($qa->url, request()->getHost());
                        @endphp
                        <li class="{{ $qaSpan((int) ($qa->cols ?? 1)) }}">
                            <a href="{{ $qa->url }}" @if ($external) target="_blank" rel="noopener" @endif
                               class="feer-card group flex {{ $single ? 'min-h-32 gap-6 px-8 py-6' : ($strip ? 'min-h-14 gap-3 px-4 py-2' : 'min-h-20 gap-4 px-5 py-4') }} items-center rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2 {{ $filled ? 'hover:opacity-90' : ($panelWhite ? 'bg-white' : 'bg-white').' hover:bg-gray-100' }}"
                               @if ($filled)
                                   style="background-color: {{ $pal['bg'] }}; color: {{ $pal['text'] }}"
                               @else
                                   style="border: 2px solid {{ $safe }}; color: #1d1d1a"
                               @endif>
                                <i class="{{ $iconClass }} flex-none text-center {{ $single ? 'w-12 text-5xl' : ($strip ? 'w-6 text-xl' : 'w-7 text-2xl') }}" @unless ($filled) style="color: {{ $safe }}" @endunless aria-hidden="true"></i>
                                <span class="min-w-0 flex-1 font-bold leading-snug {{ $single ? 'text-2xl md:text-3xl' : ($strip ? 'text-base' : 'text-lg') }}">{{ $qa->label }}</span>
                                @if ($external)<span class="sr-only">(otwiera się w nowej karcie)</span>@endif
                                <span class="flex-none transition {{ $single ? 'text-3xl' : 'text-xl' }} group-hover:translate-x-1" aria-hidden="true">→</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
          </div>
          @endif

          {{-- Ankieta (moduł polls): płaska karta z paskami wyników; po oddaniu głosu — procenty i podziękowanie. --}}
          @if ($poll)
              @php
                  $votedOptionId = session("voted_polls.{$poll->id}");
                  $totalVotes = $poll->totalVotes();
              @endphp
              <div id="ankieta" class="min-w-0">
                  <h2 id="feer-poll-heading" class="mb-6 text-2xl font-bold text-ink md:text-3xl">Ankieta</h2>
                  <form action="{{ route('polls.vote', $poll) }}" method="POST" class="rounded-md bg-white p-6" style="border: 2px solid #1d1d1a">
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
                                          <span class="block h-full bg-brand" style="width: {{ $option->percent($totalVotes) }}%"></span>
                                      </span>
                                  </label>
                              @endforeach
                          </div>
                      </fieldset>
                      @if ($votedOptionId)
                          <p class="mt-4 text-sm font-bold text-ink">Dziękujemy za oddanie głosu.</p>
                      @else
                          <button type="submit" class="mt-5 inline-flex min-h-11 items-center rounded-md bg-brand px-6 text-sm font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2">Głosuj</button>
                      @endif
                  </form>
              </div>
          @endif
        </div>
    </section>
@endif
