@extends('admin.layout')

@section('title', 'Kolory')

{{--
    Zarządzanie kolorami serwisu: paleta marki z podglądem na żywo i kontrastem WCAG (liczonym w przeglądarce),
    gotowe palety (jedno kliknięcie wypełnia pola — zapis dopiero po „Zapisz kolory") i narzędzia masowe.
--}}
@section('content')
    <div class="mb-6">
        <h1 class="text-lg font-bold text-ink">Kolory</h1>
        <p class="text-sm text-muted">Paleta marki, podgląd na żywo i kontrast tekstu (WCAG). Kolory firmowe zapisują się dokładnie takie, jak je wpiszesz.</p>
    </div>

    @if (session('status'))
        <div class="mb-5 flex items-center gap-2 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800" role="status">
            <i class="fa-solid fa-circle-check text-green-600" aria-hidden="true"></i>{{ session('status') }}
        </div>
    @endif

    <div x-data="colorManager(@js($values))" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <form method="POST" action="{{ route('admin.kolory.update') }}" class="min-w-0 space-y-6">
            @csrf
            @method('PUT')

            {{-- Gotowe palety --}}
            <section class="rounded-xl border border-gray-200 bg-white p-5" aria-labelledby="presets-h">
                <h2 id="presets-h" class="mb-3 text-sm font-bold text-ink">Gotowe palety</h2>
                <div class="flex flex-wrap gap-3">
                    @foreach ($presets as $key => $preset)
                        <button type="button" @click="apply(@js($preset['colors']))"
                            class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-3 py-2 text-left text-sm font-bold text-ink transition hover:-translate-y-0.5 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                            <span class="flex" aria-hidden="true">
                                @foreach ($preset['colors'] as $hex)
                                    <span class="-ml-1 h-6 w-6 rounded-full border-2 border-white first:ml-0" style="background: {{ $hex }}"></span>
                                @endforeach
                            </span>
                            {{ $preset['label'] }}
                        </button>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-muted">Paleta tylko wypełnia pola poniżej — zmiany zapisują się dopiero po kliknięciu „Zapisz kolory”.</p>
            </section>

            {{-- Pola kolorów --}}
            <section class="space-y-3" aria-label="Kolory marki">
                @foreach ($fields as $key => [$label, $hint])
                    <div class="rounded-xl border border-gray-200 bg-white p-4">
                        <div class="flex flex-wrap items-center gap-4">
                            <span class="h-14 w-14 flex-none rounded-lg border border-gray-200" :style="'background:' + c.{{ $key }}" aria-hidden="true"></span>
                            <div class="min-w-0 flex-1">
                                <label for="{{ $key }}" class="block text-sm font-bold text-ink">{{ $label }}</label>
                                <p class="text-xs text-muted">{{ $hint }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="color" x-model="c.{{ $key }}" aria-label="Wybierz: {{ $label }}" class="h-10 w-12 cursor-pointer rounded border border-gray-300">
                                <input type="text" id="{{ $key }}" name="{{ $key }}" x-model="c.{{ $key }}" pattern="#[0-9a-fA-F]{6}" maxlength="7" {{ $key === 'brand_color' ? 'required' : '' }}
                                    class="w-28 rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">
                            </div>
                        </div>
                        @error($key) <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        <div class="mt-3 flex flex-wrap gap-2 text-xs font-bold" x-show="valid(c.{{ $key }})">
                            <span class="rounded-md px-2 py-1" :style="'background:' + c.{{ $key }} + ';color:#fff'">Biały tekst: <span x-text="ratio('#ffffff', c.{{ $key }})"></span>:1 <span x-text="badge(ratio('#ffffff', c.{{ $key }}))"></span></span>
                            <span class="rounded-md border border-gray-200 bg-white px-2 py-1" :style="'color:' + c.{{ $key }}">Tekst na bieli: <span x-text="ratio('#ffffff', c.{{ $key }})"></span>:1</span>
                            <span class="rounded-md px-2 py-1" :style="'background:' + c.{{ $key }} + ';color:#1d1d1a'">Czarny tekst: <span x-text="ratio('#1d1d1a', c.{{ $key }})"></span>:1 <span x-text="badge(ratio('#1d1d1a', c.{{ $key }}))"></span></span>
                        </div>
                    </div>
                @endforeach
            </section>

            <div class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-3">
                <input type="hidden" name="brand_skip_contrast" value="0">
                <input type="checkbox" id="brand_skip_contrast" name="brand_skip_contrast" value="1" @checked(old('brand_skip_contrast', $settings->brand_skip_contrast)) class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand">
                <label for="brand_skip_contrast" class="text-sm leading-snug">
                    <span class="font-bold">Nie poprawiaj kolorów do WCAG</span>
                    <span class="block text-xs text-muted">Kolory zostaną użyte dokładnie takie, jak ustawione — bez przyciemniania. Użyj, gdy identyfikacja wizualna wymaga konkretnych kolorów; część zestawień może wtedy mieć kontrast poniżej 4,5:1.</span>
                </label>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-brand px-6 text-sm font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>Zapisz kolory
                </button>
                <p class="text-xs text-muted">Wskaźnik: ≥ 4,5:1 = zgodne z WCAG AA, 3–4,5 = tylko duży tekst, poniżej 3 = za niski.</p>
            </div>
        </form>

        <aside class="min-w-0 space-y-6" aria-label="Podgląd i narzędzia">
            {{-- Podgląd na żywo --}}
            <section class="rounded-xl border border-gray-200 bg-white p-5" aria-labelledby="prev-h">
                <h2 id="prev-h" class="mb-4 text-sm font-bold text-ink">Podgląd na żywo</h2>
                {{-- Atrapy elementów — wyłączone z czytników ekranu i z nawigacji klawiaturą (inert), żeby nie udawały prawdziwych kontrolek. --}}
                <div class="space-y-4" inert aria-hidden="true">
                    <button type="button" tabindex="-1" class="inline-flex min-h-11 items-center rounded-md px-5 text-sm font-bold" :style="'background:' + c.brand_color + ';color:#fff'">Przycisk główny</button>
                    <p class="text-sm text-ink">Zwykły tekst oraz <a href="#" tabindex="-1" @click.prevent class="font-bold underline" :style="'color:' + c.brand_color">link w kolorze głównym</a>.</p>
                    <div class="rounded-md px-4 py-3 text-sm font-bold" :style="'border:2px solid ' + c.brand_color + ';color:#1d1d1a'">Kafel „Na skróty” (obramówka)</div>
                    <div class="rounded-md px-4 py-3 text-sm font-bold" :style="'background:' + c.brand_color_3 + ';color:#fff'">Kafel — negatyw (grafit)</div>
                    <div class="rounded-md px-4 py-3 text-sm font-bold" :style="'background:' + c.brand_color_4 + ';color:#1d1d1a'">Jasne tło pomocnicze</div>
                    <div class="flex items-center gap-3 text-sm font-bold">
                        <span class="inline-block h-1 w-14" :style="'background:' + c.brand_color" aria-hidden="true"></span>akcent pod nagłówkiem
                    </div>
                    <div class="rounded-md px-4 py-3 text-sm font-bold" :style="'background:' + c.ngo_color + ';color:#1d1d1a'">Oznaczenie NGO</div>
                </div>
            </section>

            {{-- Paski (moduł FEER Paski): trzy style w bieżącej palecie --}}
            @if ($bandsEnabled)
                <section class="rounded-xl border border-gray-200 bg-white p-5" aria-labelledby="bands-h">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h2 id="bands-h" class="text-sm font-bold text-ink">Paski <span class="font-normal text-muted">({{ $bandsCount }})</span></h2>
                        <a href="{{ route('admin.feer-paski.index') }}" class="text-xs font-bold text-ink underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">Zarządzaj paskami</a>
                    </div>
                    <div class="space-y-2 text-xs font-bold" inert aria-hidden="true">
                        <div class="flex items-center justify-between gap-2 rounded-md px-3 py-3" :style="'background:' + c.brand_color + ';color:#fff'">
                            <span>Styl „Marka”</span><span class="rounded bg-white px-2 py-1" style="color:#1d1d1a">Przycisk</span>
                        </div>
                        <div class="flex items-center justify-between gap-2 rounded-md px-3 py-3" :style="'background:' + c.brand_color_3 + ';color:#fff'">
                            <span>Styl „Ciemny”</span><span class="rounded bg-white px-2 py-1" style="color:#1d1d1a">Przycisk</span>
                        </div>
                        <div class="flex items-center justify-between gap-2 rounded-md px-3 py-3" :style="'background:' + c.brand_color_4 + ';color:#1d1d1a'">
                            <span>Styl „Jasny”</span><span class="rounded px-2 py-1 text-white" :style="'background:' + c.brand_color">Przycisk</span>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-muted">Paski korzystają z kolorów marki: Marka = kolor główny, Ciemny = kolor 3, Jasny = kolor 4 / tło pomocnicze.</p>
                </section>
            @endif

            {{-- Narzędzia masowe --}}
            <section class="rounded-xl border border-gray-200 bg-white p-5" aria-labelledby="tools-h">
                <h2 id="tools-h" class="mb-3 text-sm font-bold text-ink">Narzędzia</h2>
                <div class="space-y-4 text-sm">
                    <form method="POST" action="{{ route('admin.kolory.brandbook-akcje') }}" onsubmit="return confirm('Nadpisać kolory wszystkich szybkich akcji ({{ $actionsCount }})?');">
                        @csrf
                        <button type="submit" @disabled($actionsCount === 0) class="w-full rounded-lg border border-gray-300 px-4 py-2 text-left font-bold text-ink hover:bg-gray-50 disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                            <i class="fa-solid fa-bolt mr-2" aria-hidden="true"></i>Kolory szybkich akcji wg brandbooka
                        </button>
                        <p class="mt-1 text-xs text-muted">Niebieski → grafit → pomarańcz, po kolei ({{ $actionsCount }} akcji).</p>
                    </form>
                    <form method="POST" action="{{ route('admin.kolory.wyczysc-menu') }}" onsubmit="return confirm('Usunąć kolory z {{ $navColored }} pozycji menu?');">
                        @csrf
                        <button type="submit" @disabled($navColored === 0) class="w-full rounded-lg border border-gray-300 px-4 py-2 text-left font-bold text-ink hover:bg-gray-50 disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                            <i class="fa-solid fa-eraser mr-2" aria-hidden="true"></i>Usuń kolory pozycji menu
                        </button>
                        <p class="mt-1 text-xs text-muted">Pozycje z własnym kolorem: {{ $navColored }}. Kolor pozycji jest opcjonalny i ustawia się w Menu.</p>
                    </form>
                </div>
            </section>
        </aside>
    </div>

    <script>
        function colorManager(initial) {
            const lum = (hex) => {
                const v = [1, 3, 5].map(i => parseInt(hex.slice(i, i + 2), 16) / 255)
                    .map(x => x <= 0.03928 ? x / 12.92 : Math.pow((x + 0.055) / 1.055, 2.4));
                return 0.2126 * v[0] + 0.7152 * v[1] + 0.0722 * v[2];
            };
            return {
                c: Object.assign({ brand_color: '#1e6dff', brand_color_2: '#ea8f00', brand_color_3: '#1d1d1a', brand_color_4: '#cbd5e7', ngo_color: '#ea8f00' },
                    Object.fromEntries(Object.entries(initial).filter(([, v]) => v))),
                valid: (hex) => /^#[0-9a-fA-F]{6}$/.test(hex || ''),
                apply(preset) { Object.assign(this.c, preset); },
                ratio(a, b) {
                    if (!this.valid(a) || !this.valid(b)) return '—';
                    const [x, y] = [lum(a), lum(b)].sort((p, q) => q - p);
                    return ((x + 0.05) / (y + 0.05)).toFixed(2);
                },
                badge(r) { r = parseFloat(r); return r >= 4.5 ? '✓ AA' : (r >= 3 ? '≈ duży tekst' : '✗'); },
            };
        }
    </script>
@endsection
