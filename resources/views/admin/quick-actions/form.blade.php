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
            <label for="icon" class="mb-1 block text-sm font-bold">Ikona (Bootstrap Icons)</label>
            <div class="flex items-center gap-3">
                <span id="icon-preview" class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-brand-light text-lg text-brand">
                    <i class="bi {{ old('icon', $quickAction->icon) ?: 'bi-lightning' }}" id="icon-preview-glyph"></i>
                </span>
                <input type="text" id="icon" name="icon" value="{{ old('icon', $quickAction->icon) }}" placeholder="np. bi-rocket-takeoff" required
                    oninput="document.getElementById('icon-preview-glyph').className = 'bi ' + (this.value || 'bi-lightning')"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            </div>
            <p class="mt-1 text-xs text-muted">
                Nazwę ikony znajdziesz na <a href="https://icons.getbootstrap.com" target="_blank" rel="noopener" class="text-brand hover:text-brand-dark">icons.getbootstrap.com</a> (np. „bi-heart").
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
            <div class="flex items-center gap-3" x-data="{ color: '{{ $qaColor ?: '#2563eb' }}', enabled: {{ $qaColor ? 'true' : 'false' }} }">
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
            <p class="mt-1 text-xs text-muted">Kolor ikony i obramowania kafelka. Puste = kolor marki.</p>
            @error('color') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="order" class="mb-1 block text-sm font-bold">Kolejność</label>
            <input type="number" id="order" name="order" min="0" value="{{ old('order', $quickAction->order) }}"
                class="w-28 rounded border-gray-300 focus:border-brand focus:ring-brand">
        </div>

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz</button>
            <a href="{{ route('admin.szybkie-akcje.index') }}" class="text-sm text-muted hover:text-brand">Anuluj</a>
        </div>
    </form>
@endsection
