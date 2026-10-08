@extends('admin.layout')

@section('title', $quickAction->exists ? 'Edytuj szybką akcję' : 'Nowa szybka akcja')

@section('content')
    <form method="POST" action="{{ $quickAction->exists ? route('admin.szybkie-akcje.update', $quickAction) : route('admin.szybkie-akcje.store') }}" class="max-w-xl space-y-5 rounded-lg border border-gray-200 bg-white p-6">
        @csrf
        @if ($quickAction->exists) @method('PUT') @endif

        <div>
            <label for="label" class="mb-1 block text-sm font-bold">Etykieta</label>
            <input type="text" id="label" name="label" value="{{ old('label', $quickAction->label) }}" required
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="description" class="mb-1 block text-sm font-bold">Krótki opis <span class="font-normal text-muted">(opcjonalnie, do 140 znaków)</span></label>
            <input type="text" id="description" name="description" maxlength="140" value="{{ old('description', $quickAction->description ?? '') }}" placeholder="np. Zaloguj się i wróć do swoich szkoleń"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            <p class="mt-1 text-xs text-muted">Mała linijka pod nazwą kafla (szablon FEER; nie pokazuje się w układzie „Pasek”).</p>
            @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="icon" class="mb-1 block text-sm font-bold">Ikona (Bootstrap Icons lub Material Symbols)</label>
            <div class="flex items-center gap-3">
                <span id="icon-preview" class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-brand-light text-lg text-brand">
                    <span id="icon-preview-glyph">{!! icon_html(old('icon', $quickAction->icon), '', '', 'bi-lightning') !!}</span>
                </span>
                <input type="text" id="icon" name="icon" value="{{ old('icon', $quickAction->icon) }}" placeholder="np. bi-rocket-takeoff" data-icon-picker data-icon-format="name" required
                    oninput="(function (v, box) { v = (v || '').trim() || 'bi-lightning'; box.innerHTML = /^mi-[a-z0-9_]+$/.test(v) ? '<span class=&quot;material-symbols-outlined mi-glyph&quot;>' + v.slice(3) + '</span>' : '<i class=&quot;' + (/(^|\s)fa[srlb]?(-|\s)/.test(v) || v.startsWith('bi ') ? v : 'bi ' + v) + '&quot;></i>'; })(this.value, document.getElementById('icon-preview-glyph'))"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            </div>
            <p class="mt-1 text-xs text-muted">
                Nazwę ikony znajdziesz na <a href="https://icons.getbootstrap.com" target="_blank" rel="noopener" class="text-brand hover:text-brand-dark">icons.getbootstrap.com</a> (np. „bi-heart") albo w zakładce „Material Symbols" selektora (np. „mi-home").
            </p>
            @error('icon') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="url" class="mb-1 block text-sm font-bold">Link</label>
            <input type="text" id="url" name="url" value="{{ old('url', $quickAction->url) }}" placeholder="np. /projekty lub https://..." required
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            @php $qaColor = old('color', $quickAction->color); @endphp
            <label class="mb-1 block text-sm font-bold">Kolor akcentu <span class="font-normal text-muted">(opcjonalnie)</span></label>
            <div x-data="{ color: '{{ $qaColor ?: '#2563eb' }}', enabled: {{ $qaColor ? 'true' : 'false' }} }">
            <div class="flex items-center gap-3">
                <input type="hidden" name="color" :value="enabled ? color : ''">
                <input type="color" x-model="color" :disabled="!enabled" aria-label="Wybierz kolor akcentu"
                    class="h-10 w-14 flex-none cursor-pointer rounded border border-gray-300 disabled:opacity-40">
                <input type="text" x-model="color" :disabled="!enabled" aria-label="Kod koloru (hex)"
                    placeholder="#2563eb" pattern="#[0-9a-fA-F]{6}"
                    class="w-40 rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand disabled:bg-gray-100 disabled:text-muted">
                <label class="flex items-center gap-2 text-sm text-muted">
                    <input type="checkbox" x-model="enabled" class="rounded border-gray-300 text-brand focus:ring-brand">
                    Własny kolor
                </label>
            </div>
            {{-- Paleta brandbooka FEER — jedno kliknięcie ustawia kolor i włącza „Własny kolor". --}}
            <div class="mt-2 flex flex-wrap items-center gap-2" role="group" aria-label="Kolory z brandbooka">
                @foreach (['#1e6dff' => 'Niebieski FEER', '#1d1d1a' => 'Grafit', '#ea8f00' => 'Pomarańcz', '#cbd5e7' => 'Jasnoniebieski'] as $hex => $name)
                    <button type="button" title="{{ $name }}"
                        @click="color = '{{ $hex }}'; enabled = true"
                        class="flex h-8 items-center gap-2 rounded border border-gray-200 bg-white px-2 text-xs font-bold text-ink hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">
                        <span class="h-4 w-4 rounded-sm border border-gray-300" style="background: {{ $hex }}" aria-hidden="true"></span>{{ $name }}
                    </button>
                @endforeach
            </div>
            </div>
            <p class="mt-1 text-xs text-muted">Kolor ikony i obramowania kafelka. Puste = kolor marki.</p>
            @error('color') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="order" class="mb-1 block text-sm font-bold">Kolejność</label>
            <input type="number" id="order" name="order" min="0" value="{{ old('order', $quickAction->order) }}"
                class="w-28 rounded border-gray-300 focus:border-brand focus:ring-brand">
        </div>

        {{-- Styl kafelka --}}
        <fieldset class="space-y-4 rounded-lg border border-gray-200 p-4">
            <legend class="px-1 text-sm font-bold text-ink">Układ kafelka</legend>

            {{-- Szerokość --}}
            <div>
                <label class="mb-1.5 block text-sm font-bold">Szerokość <span class="font-normal text-muted">(kolumny)</span></label>
                <div class="flex gap-2" role="radiogroup" aria-label="Szerokość kafelka">
                    @foreach ([1 => '1 — wąski', 2 => '2 — połowa', 3 => '3 — cały rząd'] as $val => $lbl)
                        <label class="flex cursor-pointer items-center gap-1.5 rounded-lg border px-3 py-2 text-sm transition
                            has-[:checked]:border-brand has-[:checked]:bg-brand-light has-[:checked]:text-brand
                            border-gray-300 hover:border-brand">
                            <input type="radio" name="cols" value="{{ $val }}"
                                {{ (int) old('cols', $quickAction->cols ?? 1) === $val ? 'checked' : '' }}
                                class="sr-only">
                            {{ $lbl }}
                        </label>
                    @endforeach
                </div>
                <p class="mt-1 text-xs text-muted">W siatce 3 kolumn. Na telefonach zawsze 2 kolumny.</p>
            </div>

            {{-- Układ wewnętrzny --}}
            <div>
                <label class="mb-1.5 block text-sm font-bold">Układ wewnętrzny</label>
                <div class="flex gap-2" role="radiogroup" aria-label="Układ wewnętrzny kafelka">
                    <label class="flex cursor-pointer items-center gap-1.5 rounded-lg border px-3 py-2 text-sm transition
                        border-gray-300 hover:border-brand has-[:checked]:border-brand has-[:checked]:bg-brand-light has-[:checked]:text-brand">
                        <input type="radio" name="strip" value="0"
                            {{ ! old('strip', $quickAction->strip ?? false) ? 'checked' : '' }}
                            class="sr-only">
                        <i class="fa-solid fa-square text-[0.7rem]" aria-hidden="true"></i> Karta (ikona nad tekstem)
                    </label>
                    <label class="flex cursor-pointer items-center gap-1.5 rounded-lg border px-3 py-2 text-sm transition
                        border-gray-300 hover:border-brand has-[:checked]:border-brand has-[:checked]:bg-brand-light has-[:checked]:text-brand">
                        <input type="radio" name="strip" value="1"
                            {{ old('strip', $quickAction->strip ?? false) ? 'checked' : '' }}
                            class="sr-only">
                        <i class="fa-solid fa-minus text-[0.7rem]" aria-hidden="true"></i> Pasek (ikona obok tekstu)
                    </label>
                </div>
            </div>

            {{-- Negatyw --}}
            <label class="flex cursor-pointer items-start gap-3 border-t border-gray-100 pt-3">
                <input type="hidden" name="is_negative" value="0">
                <input type="checkbox" name="is_negative" value="1"
                    {{ old('is_negative', $quickAction->is_negative ?? false) ? 'checked' : '' }}
                    class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand focus:ring-brand">
                <div>
                    <span class="text-sm font-bold text-ink">Negatyw</span>
                    <p class="text-xs text-muted">Wypełnione tło kolorem akcentu, ikona i tekst białe.</p>
                </div>
            </label>
        </fieldset>

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz</button>
            <a href="{{ route('admin.szybkie-akcje.index') }}" class="text-sm text-muted hover:text-brand">Anuluj</a>
        </div>
    </form>
@endsection
