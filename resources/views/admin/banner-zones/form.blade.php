@extends('admin.layout')

@section('title', $zone->exists ? 'Edytuj strefę' : 'Nowa strefa bannerów')

@section('content')
    @php
        $action = $zone->exists
            ? route('admin.strefy-bannerow.update', $zone)
            : route('admin.strefy-bannerow.store');
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

    <form method="POST" action="{{ $action }}" class="max-w-xl">
        @csrf
        @if ($zone->exists) @method('PUT') @endif

        <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
            <div>
                <label for="label" class="mb-1 block text-sm font-bold">
                    Nazwa <span aria-hidden="true" class="text-red-600">*</span>
                </label>
                <input id="label" type="text" name="label"
                    value="{{ old('label', $zone->label) }}"
                    required
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                <p class="mt-1 text-xs text-muted">Wyświetlana w panelu admina (np. „Sidebar aktualności").</p>
            </div>

            <div>
                <label for="slug" class="mb-1 block text-sm font-bold">
                    Slug <span aria-hidden="true" class="text-red-600">*</span>
                </label>
                <input id="slug" type="text" name="slug"
                    value="{{ old('slug', $zone->slug) }}"
                    required pattern="[a-z0-9_]+"
                    {{ $zone->exists ? 'readonly class="w-full rounded border-gray-300 bg-gray-50 text-muted focus:border-brand focus:ring-brand"' : 'class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand"' }}>
                <p class="mt-1 text-xs text-muted">
                    Używany w szablonie: <code class="rounded bg-gray-100 px-1 font-mono">&lt;x-banner-zone name="{{ $zone->slug ?? 'slug' }}" /&gt;</code>.
                    @if ($zone->exists) Slug jest tylko do odczytu po zapisaniu. @else Tylko małe litery, cyfry i podkreślnik. @endif
                </p>
            </div>

            <div>
                <label for="description" class="mb-1 block text-sm font-bold">Opis (opcjonalnie)</label>
                <input id="description" type="text" name="description"
                    value="{{ old('description', $zone->description) }}"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            </div>

            <div>
                <label for="max_concurrent" class="mb-1 block text-sm font-bold">
                    Maks. bannerów jednocześnie <span aria-hidden="true" class="text-red-600">*</span>
                </label>
                <input id="max_concurrent" type="number" name="max_concurrent"
                    value="{{ old('max_concurrent', $zone->max_concurrent ?? 1) }}"
                    min="1" max="10" required
                    class="w-32 rounded border-gray-300 focus:border-brand focus:ring-brand">
            </div>
        </div>

        <div class="mt-5 flex items-center gap-3">
            <button type="submit"
                class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">
                Zapisz
            </button>
            <a href="{{ route('admin.strefy-bannerow.index') }}" class="text-sm text-muted hover:text-ink">Anuluj</a>
        </div>
    </form>
@endsection
