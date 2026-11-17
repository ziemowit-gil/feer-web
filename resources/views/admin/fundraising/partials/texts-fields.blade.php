{{--
    Pola tekstów modułu zbiórek, budowane z rejestru App\Support\FundraisingTexts.
    Każda grupa to rozwijana sekcja; puste pole = wartość odziedziczona (pokazana jako podpowiedź).

    Parametry:
      $groups  — klucze grup do pokazania (FundraisingTexts::GROUPS)
      $values  — zapisane wartości (klucz => tekst)
      $inherit — callable(string $key): string — wartość dziedziczona (podpowiedź w polu)
      $inheritLabel — np. „domyślnie” albo „z ustawień globalnych”
--}}
@php use App\Support\FundraisingTexts; @endphp
<p class="text-sm text-ink">
    Zmienne, które możesz wstawić w tekst: <code>{kwota}</code>, <code>{kampania}</code>, <code>{imie}</code>,
    <code>{organizacja}</code>, <code>{data}</code>, <code>{konto}</code>, <code>{cel}</code>, <code>{zebrano}</code>.
    Puste pole oznacza tekst {{ $inheritLabel }}. W dłuższych tekstach pusta linia zaczyna nowy akapit.
</p>
<div class="mt-4 space-y-3">
    @foreach ($groups as $group)
        @php
            [$groupLabel, $groupHelp] = FundraisingTexts::GROUPS[$group];
            $fields = FundraisingTexts::fields($group);
            $filled = collect($fields)->keys()->filter(fn ($k) => filled($values[$k] ?? null))->count();
            $hasError = collect($fields)->keys()->contains(fn ($k) => $errors->has('texts.'.$k));
        @endphp
        <details class="rounded-md bg-gray-50" @if ($hasError) open @endif>
            <summary class="flex min-h-11 cursor-pointer items-center justify-between gap-3 px-4 py-2 text-sm font-bold text-ink hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                <span>{{ $groupLabel }} <span class="font-normal">— {{ $groupHelp }}</span></span>
                <span class="flex-none font-normal">{{ $filled ? 'zmienione: '.$filled : 'bez zmian' }}</span>
            </summary>
            <div class="space-y-4 px-4 pb-4 pt-2">
                @foreach ($fields as $key => [$label, $type])
                    @php
                        $id = 'texts-'.$key;
                        $value = old('texts.'.$key, $values[$key] ?? '');
                        $placeholder = \Illuminate\Support\Str::limit((string) $inherit($key), 180);
                    @endphp
                    <div>
                        <label for="{{ $id }}" class="mb-1 block text-sm font-bold text-ink">{{ $label }}</label>
                        @if ($type === 'text')
                            <textarea id="{{ $id }}" name="texts[{{ $key }}]" rows="3" maxlength="4000" placeholder="{{ $placeholder }}"
                                @error('texts.'.$key) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
                                class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">{{ $value }}</textarea>
                        @else
                            <input type="{{ $type === 'url' ? 'url' : 'text' }}" id="{{ $id }}" name="texts[{{ $key }}]" value="{{ $value }}"
                                maxlength="{{ $type === 'url' ? 500 : 255 }}" placeholder="{{ $placeholder }}"
                                @error('texts.'.$key) aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror
                                class="w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
                        @endif
                        @error('texts.'.$key) <p id="{{ $id }}-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>
        </details>
    @endforeach
</div>
