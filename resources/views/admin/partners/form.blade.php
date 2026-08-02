@extends('admin.layout')

@section('title', $partner->exists ? 'Edytuj partnera' : 'Nowy partner')

@section('content')
    <form method="POST" action="{{ $partner->exists ? route('admin.partnerzy.update', $partner) : route('admin.partnerzy.store') }}"
        enctype="multipart/form-data" class="max-w-xl space-y-5 rounded-lg border border-gray-200 bg-white p-6">
        @csrf
        @if ($partner->exists) @method('PUT') @endif

        @if ($partner->exists && $partner->logo_url)
            <div>
                <p class="mb-1 text-sm font-bold">Obecne logo</p>
                <img src="{{ $partner->logo_url }}" alt="{{ $partner->name ?: 'Aktualne logo partnera' }}" class="h-16 max-w-[12rem] rounded border border-gray-100 object-contain p-2">
            </div>
        @endif

        <div>
            <label for="logo" class="mb-1 block text-sm font-bold">{{ $partner->exists ? 'Zmień logo' : 'Logo' }}</label>
            <input type="file" id="logo" name="logo" accept="image/*" {{ $partner->exists ? '' : 'required' }}
                class="block w-full cursor-pointer text-sm text-muted file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-brand file:px-4 file:py-2 file:text-sm file:font-bold file:text-white hover:file:bg-brand-dark">
            @error('logo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="name" class="mb-1 block text-sm font-bold">Nazwa</label>
            <input type="text" id="name" name="name" value="{{ old('name', $partner->name) }}" required
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            <p class="mt-1 text-xs text-muted">Używana jako tekst alternatywny logo i etykieta dla czytników ekranu.</p>
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="url" class="mb-1 block text-sm font-bold">Link (opcjonalnie)</label>
            <input type="text" id="url" name="url" value="{{ old('url', $partner->url) }}" placeholder="np. https://epuap.gov.pl"
                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
            @error('url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="order" class="mb-1 block text-sm font-bold">Kolejność</label>
            <input type="number" id="order" name="order" min="0" value="{{ old('order', $partner->order) }}"
                class="w-28 rounded border-gray-300 focus:border-brand focus:ring-brand">
        </div>

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz</button>
            <a href="{{ route('admin.partnerzy.index') }}" class="text-sm text-muted hover:text-brand">Anuluj</a>
        </div>
    </form>
@endsection
