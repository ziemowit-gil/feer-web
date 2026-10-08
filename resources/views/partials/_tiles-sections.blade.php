{{--
    Siatka kafelków z podziałem na sekcje (wiersze `heading` w tablicy kafelków).
    Parametry: $tiles — tablica kafelków i nagłówków sekcji, $label — nazwa dla czytników ekranu, $headingClass (opcjonalnie).
--}}
@php $groups = \App\Support\TileSections::groups(is_array($tiles ?? null) ? $tiles : collect($tiles ?? [])->all()); @endphp
@foreach ($groups as $g)
    @php $hid = $g['heading'] ? 'tiles-h-'.\Illuminate\Support\Str::random(8) : null; @endphp
    {{-- Grupa kafelków jest oznaczona nagłówkiem (aria-labelledby), więc czytnik ekranu zapowiada jej nazwę. --}}
    <section @if ($hid) aria-labelledby="{{ $hid }}" @endif>
        @if ($g['heading'])
            <h2 id="{{ $hid }}" class="{{ $headingClass ?? 'mb-3 mt-10 text-2xl font-bold text-ink' }}">{{ $g['heading'] }}</h2>
        @endif
        @include('partials._tiles-grid', ['tiles' => collect($g['tiles']), 'label' => $label ?? 'Kafelki', 'labelledby' => $hid])
    </section>
@endforeach
