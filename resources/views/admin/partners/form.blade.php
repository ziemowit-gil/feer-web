@extends('admin.layout')

@section('title', $partner->exists ? 'Edytuj partnera' : 'Nowy partner')

@section('content')
    <form method="POST" action="{{ $partner->exists ? route('admin.partnerzy.update', $partner) : route('admin.partnerzy.store') }}"
        enctype="multipart/form-data" class="max-w-xl space-y-5 p-6 rounded-md bg-white">
        @csrf
        @if ($partner->exists) @method('PUT') @endif

        @include('admin.partials.library-image-picker', [
            'name' => 'logo',
            'label' => $partner->exists ? 'Logo' : 'Logo',
            'current' => $partner->exists ? $partner->logo_url : null,
            'required' => ! $partner->exists,
            'help' => 'Wgraj plik z dysku albo wybierz logo wgrane wcześniej do biblioteki plików.',
        ])

        <div>
            <label for="name" class="mb-1 block text-sm font-bold">Nazwa</label>
            <input type="text" id="name" name="name" value="{{ old('name', $partner->name) }}" required
                class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
            <p class="mt-1 text-xs text-muted">Używana jako tekst alternatywny logo i etykieta dla czytników ekranu.</p>
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="url" class="mb-1 block text-sm font-bold">Link (opcjonalnie)</label>
            <input type="text" id="url" name="url" value="{{ old('url', $partner->url) }}" placeholder="np. https://epuap.gov.pl"
                class="w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
            @error('url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="order" class="mb-1 block text-sm font-bold">Kolejność</label>
            <input type="number" id="order" name="order" min="0" value="{{ old('order', $partner->order) }}"
                class="w-28 rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
        </div>

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded bg-brand-dark px-5 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">Zapisz</button>
            <a href="{{ route('admin.partnerzy.index') }}" class="text-sm text-muted hover:text-brand-dark">Anuluj</a>
        </div>
    </form>
@endsection
