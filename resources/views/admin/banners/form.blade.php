@extends('admin.layout')

@section('title', $banner->exists ? 'Edytuj baner' : 'Nowy baner')

@section('content')
    @php
        $action = $banner->exists
            ? route('admin.banery.update', $banner)
            : route('admin.banery.store');
    @endphp

    @if ($errors->any())
        <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-bold">Popraw poniższe pola:</p>
            <ul class="mt-1 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data"
        x-data="{ type: '{{ old('type', $banner->type ?? 'image') }}' }">
        @csrf
        @if ($banner->exists) @method('PUT') @endif

        <div class="grid gap-6 lg:grid-cols-[1fr_18rem]">

            {{-- Kolumna główna --}}
            <div class="space-y-6">

                {{-- Podstawowe --}}
                <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
                    <h2 class="font-bold text-ink">Podstawowe</h2>

                    <div>
                        <label for="name" class="mb-1 block text-sm font-bold">
                            Nazwa wewnętrzna <span aria-hidden="true" class="text-red-600">*</span>
                        </label>
                        <input id="name" type="text" name="name" value="{{ old('name', $banner->name) }}"
                            required class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        <p class="mt-1 text-xs text-muted">Widoczna tylko w adminie — pomaga identyfikować kreację.</p>
                    </div>

                    <div>
                        <p class="mb-2 text-sm font-bold">Typ kreacji <span aria-hidden="true" class="text-red-600">*</span></p>
                        <div class="flex gap-4">
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="radio" name="type" value="image" x-model="type"
                                    class="border-gray-300 text-brand focus:ring-brand">
                                <span class="text-sm">Obraz (PNG/JPG/WebP/GIF)</span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="radio" name="type" value="html" x-model="type"
                                    class="border-gray-300 text-brand focus:ring-brand">
                                <span class="text-sm">HTML / widget JS</span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Kreacja: obraz --}}
                <div x-show="type === 'image'" class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
                    <h2 class="font-bold text-ink">Kreacja — obraz</h2>

                    <div>
                        <label for="image_file" class="mb-1 block text-sm font-bold">
                            Plik graficzny
                            @if (! $banner->exists) <span aria-hidden="true" class="text-red-600">*</span> @endif
                        </label>
                        <input id="image_file" type="file" name="image_file" accept="image/*"
                            class="block w-full text-sm text-muted file:mr-4 file:rounded file:border-0 file:bg-brand file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-brand-dark">
                        @if ($banner->image_path)
                            <div class="mt-3 flex items-center gap-3">
                                <img src="{{ Storage::url($banner->image_path) }}" alt=""
                                    class="h-16 rounded border border-gray-200 object-contain">
                                <span class="text-xs text-muted">Aktualny plik. Wgraj nowy, aby zastąpić.</span>
                            </div>
                        @endif
                    </div>

                    <div>
                        <label for="image_alt" class="mb-1 block text-sm font-bold">Tekst alternatywny (alt)</label>
                        <input id="image_alt" type="text" name="image_alt"
                            value="{{ old('image_alt', $banner->image_alt) }}"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        <p class="mt-1 text-xs text-muted">Wymagany dla dostępności. Jeśli baner jest czysto dekoracyjny, zostaw puste.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="width" class="mb-1 block text-sm font-bold">Szerokość (px)</label>
                            <input id="width" type="number" name="width" min="1" max="2000"
                                value="{{ old('width', $banner->width) }}"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        </div>
                        <div>
                            <label for="height" class="mb-1 block text-sm font-bold">Wysokość (px)</label>
                            <input id="height" type="number" name="height" min="1" max="2000"
                                value="{{ old('height', $banner->height) }}"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        </div>
                        <p class="col-span-2 -mt-1 text-xs text-muted">
                            Opcjonalne. Zostaw puste, aby użyć naturalnego rozmiaru grafiki. Na wąskich ekranach baner i tak skaluje się do szerokości kontenera.
                        </p>
                    </div>

                    <div>
                        <label for="link_url" class="mb-1 block text-sm font-bold">URL docelowy (kliknięcie)</label>
                        <input id="link_url" type="url" name="link_url"
                            value="{{ old('link_url', $banner->link_url) }}"
                            placeholder="https://"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    </div>

                    <div>
                        <p class="mb-2 text-sm font-bold">Otwieranie linku</p>
                        <div class="flex gap-4">
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="radio" name="link_target" value="_blank"
                                    {{ old('link_target', $banner->link_target ?? '_blank') === '_blank' ? 'checked' : '' }}
                                    class="border-gray-300 text-brand focus:ring-brand">
                                <span class="text-sm">Nowa karta</span>
                            </label>
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="radio" name="link_target" value="_self"
                                    {{ old('link_target', $banner->link_target) === '_self' ? 'checked' : '' }}
                                    class="border-gray-300 text-brand focus:ring-brand">
                                <span class="text-sm">Ta sama karta</span>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Kreacja: HTML --}}
                <div x-show="type === 'html'" class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
                    <h2 class="font-bold text-ink">Kreacja — HTML / widget</h2>

                    <div>
                        <label for="html_content" class="mb-1 block text-sm font-bold">Kod HTML / JS</label>
                        <textarea id="html_content" name="html_content" rows="10"
                            class="w-full rounded border-gray-300 font-mono text-xs focus:border-brand focus:ring-brand">{{ old('html_content', $banner->html_content) }}</textarea>
                        <p class="mt-1 text-xs text-muted">
                            Wklej embed zewnętrznego widgetu lub własny kod HTML. Kod jest renderowany bez filtrowania —
                            wklejaj tylko zaufane treści.
                        </p>
                    </div>
                </div>

            </div>

            {{-- Kolumna boczna --}}
            <div class="space-y-6">

                {{-- Status --}}
                <div class="space-y-4 rounded-lg border border-gray-200 bg-white p-6">
                    <h2 class="font-bold text-ink">Status</h2>

                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                            {{ old('is_active', $banner->is_active ?? true) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-brand focus:ring-brand">
                        <span class="text-sm font-bold">Baner aktywny</span>
                    </label>

                    <div class="border-t border-gray-100 pt-4">
                        <label for="starts_at" class="mb-1 block text-sm font-bold">Emisja od</label>
                        <input id="starts_at" type="datetime-local" name="starts_at"
                            value="{{ old('starts_at', $banner->starts_at?->format('Y-m-d\TH:i')) }}"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    </div>

                    <div>
                        <label for="ends_at" class="mb-1 block text-sm font-bold">Emisja do</label>
                        <input id="ends_at" type="datetime-local" name="ends_at"
                            value="{{ old('ends_at', $banner->ends_at?->format('Y-m-d\TH:i')) }}"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        <p class="mt-1 text-xs text-muted">Zostaw puste dla bezterminowej emisji.</p>
                    </div>
                </div>

                {{-- Strefy --}}
                <div class="space-y-3 rounded-lg border border-gray-200 bg-white p-6">
                    <h2 class="font-bold text-ink">Strefy wyświetlania</h2>
                    <p class="text-xs text-muted">Zaznacz strefy i ustaw priorytet (0–100, wyższy = ważniejszy).</p>

                    @foreach ($zones as $zone)
                        @php $checked = $banner->zones?->contains($zone->id); @endphp
                        <div x-data="{ checked: {{ $checked ? 'true' : 'false' }} }"
                            class="rounded border border-gray-100 p-3">
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="checkbox" name="zones[]" value="{{ $zone->id }}"
                                    x-model="checked"
                                    {{ $checked ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-brand focus:ring-brand">
                                <span class="text-sm font-bold">{{ $zone->label }}</span>
                            </label>
                            <div x-show="checked" class="mt-2 pl-6">
                                <label class="text-xs text-muted" for="priority_{{ $zone->id }}">Priorytet</label>
                                <input id="priority_{{ $zone->id }}" type="number" name="priority[{{ $zone->id }}]"
                                    value="{{ old("priority.{$zone->id}", $banner->zones?->find($zone->id)?->pivot?->priority ?? 0) }}"
                                    min="0" max="100"
                                    class="mt-1 w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Akcje --}}
                <div class="flex items-center gap-3">
                    <button type="submit"
                        class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                        Zapisz
                    </button>
                    <a href="{{ route('admin.banery.index') }}" class="text-sm text-muted hover:text-ink">Anuluj</a>
                </div>

            </div>
        </div>
    </form>
@endsection
