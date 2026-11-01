{{--
    Formularz zapisu (Blade + Alpine.js). Walidacja po stronie serwera pokazuje błędy przy polach (aria-describedby, aria-invalid)
    i w podsumowaniu na górze, na którym ląduje fokus. Alpine pokazuje tylko, czy wybrany termin ma wolne miejsca
    (zgłoszenie na pełny termin trafia na listę rezerwową) oraz blokuje podwójne wysłanie. Style pól: zwykły CSS (bez przebudowy Tailwinda).
--}}
@php
    $openEditions = $editions->filter(fn ($e) => $e->acceptsRegistrations())->values();
    $meta = $openEditions->mapWithKeys(fn ($e) => [$e->id => ['full' => $e->isFull(), 'free' => $e->free_seats]]);
    $field = fn (string $n) => $errors->has($n) ? 'aria-invalid="true" aria-describedby="err-'.$n.'"' : '';
@endphp

<style>
    .cr-input { display:block; width:100%; border:0; border-radius:.75rem; background:#fff; padding:.75rem 1rem; font-size:1rem; line-height:1.5; color:#1d1d1a; box-shadow: inset 0 0 0 2px #6b7280; }
    .cr-input:focus { outline:3px solid #1752bf; outline-offset:2px; }
    .cr-input[aria-invalid="true"] { box-shadow: inset 0 0 0 2px #b91c1c; }
    textarea.cr-input { min-height:6rem; }
</style>

<form method="POST" action="{{ route('courses.register', $course) }}" novalidate class="mt-4 space-y-5"
    x-data="{ meta: @js($meta), edition: '{{ old('edition_id', $selected->id) }}', busy: false }"
    @submit="busy = true">
    @csrf

    @if ($errors->any())
        <div role="alert" tabindex="-1" x-init="$el.focus()" class="rounded-md bg-white p-4 text-ink" style="outline:2px solid #b91c1c">
            <p class="font-bold">Popraw zaznaczone pola:</p>
            <ul class="mt-1 list-inside list-disc">
                @foreach ($errors->messages() as $key => $messages)
                    {{-- Komunikat prowadzi do pola: klik przenosi fokus na nie. --}}
                    <li>@if ($key !== 'spam')<a href="#{{ $key }}" class="font-bold underline" @click.prevent="document.getElementById('{{ $key }}')?.focus()">{{ $messages[0] }}</a>@else{{ $messages[0] }}@endif</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div>
        <label for="edition_id" class="mb-1 block font-bold text-ink">Termin <span aria-hidden="true">*</span></label>
        <select id="edition_id" name="edition_id" required aria-required="true" x-model="edition" class="cr-input" {!! $field('edition_id') !!}>
            @foreach ($openEditions as $e)
                <option value="{{ $e->id }}">{{ $e->datesLabel() }}@if ($e->schedule), {{ $e->schedule }}@endif</option>
            @endforeach
        </select>
        @error('edition_id')<p id="err-edition_id" class="mt-1 font-bold text-red-700">{{ $message }}</p>@enderror
        <p class="mt-2 rounded-md bg-white p-3 text-sm text-ink" role="status" aria-live="polite">
            <template x-if="meta[edition] && meta[edition].full"><span>Ten termin jest pełny. Możesz zapisać się na listę rezerwową i damy znać, gdy zwolni się miejsce.</span></template>
            <template x-if="meta[edition] && !meta[edition].full"><span>Wolnych miejsc: <strong x-text="meta[edition].free"></strong>.</span></template>
        </p>
    </div>

    @foreach ([['first_name', 'Imię', 'text', 'given-name', true], ['last_name', 'Nazwisko', 'text', 'family-name', true], ['email', 'Adres e-mail', 'email', 'email', true], ['phone', 'Telefon (niewymagany)', 'tel', 'tel', false]] as [$n, $label, $type, $ac, $req])
        <div>
            <label for="{{ $n }}" class="mb-1 block font-bold text-ink">{{ $label }}@if ($req) <span aria-hidden="true">*</span>@endif</label>
            <input id="{{ $n }}" name="{{ $n }}" type="{{ $type }}" autocomplete="{{ $ac }}" value="{{ old($n) }}" @if ($req) required aria-required="true" @endif class="cr-input" {!! $field($n) !!}>
            @error($n)<p id="err-{{ $n }}" class="mt-1 font-bold text-red-700">{{ $message }}</p>@enderror
        </div>
    @endforeach

    <div class="rounded-md bg-white p-4" style="outline:2px solid #1d1d1a">
        <label for="accessibility_needs" class="mb-1 block text-lg font-bold text-ink">Potrzeby i dostosowania: powiedz, czego potrzebujesz (niewymagane)</label>
        <p id="needs-help" class="mb-2 text-sm text-ink">Powiedz nam, co ułatwi Ci udział. Na przykład: czytnik ekranu, powiększenie, napisy, tłumacz języka migowego, dostępny dojazd, asystent. Przygotujemy się przed zajęciami.</p>
        <textarea id="accessibility_needs" name="accessibility_needs" maxlength="2000" aria-describedby="needs-help @error('accessibility_needs') err-accessibility_needs @enderror" class="cr-input">{{ old('accessibility_needs') }}</textarea>
        @error('accessibility_needs')<p id="err-accessibility_needs" class="mt-1 font-bold text-red-700">{{ $message }}</p>@enderror
    </div>

    <div class="flex items-start gap-3">
        <input id="consent" name="consent" type="checkbox" value="1" required aria-required="true" @checked(old('consent')) class="mt-1 h-5 w-5 rounded border-gray-500" {!! $field('consent') !!}>
        <label for="consent" class="text-sm leading-relaxed text-ink">Zgadzam się na przetwarzanie moich danych w celu obsługi zapisu na szkolenie. <span aria-hidden="true">*</span></label>
    </div>
    @error('consent')<p id="err-consent" class="font-bold text-red-700">{{ $message }}</p>@enderror

    @include('partials.spam-protection', ['spamScope' => 'szkolenie', 'flat' => false])

    <button type="submit" :disabled="busy" class="inline-flex min-h-12 w-full items-center justify-center rounded-md bg-brand-dark px-6 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2 disabled:opacity-100">
        <span x-text="busy ? 'Wysyłanie…' : (meta[edition] && meta[edition].full ? 'Zapisz na listę rezerwową' : 'Wyślij zgłoszenie')">Wyślij zgłoszenie</span>
    </button>
</form>
