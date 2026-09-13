@extends('admin.layout')

@section('title', $code->exists ? 'Edytuj kod rabatowy' : 'Nowy kod rabatowy')

@section('content')
    <form method="POST"
        action="{{ $code->exists ? route('admin.sklep.kody-rabatowe.update', $code) : route('admin.sklep.kody-rabatowe.store') }}"
        x-data="{ type: '{{ old('type', $code->type ?: 'percent') }}' }"
        class="max-w-xl space-y-5 rounded-lg border border-gray-200 bg-white p-6">
        @csrf
        @if ($code->exists) @method('PUT') @endif

        <div>
            <label for="code" class="mb-1 block text-sm font-bold">Kod</label>
            <input type="text" id="code" name="code" value="{{ old('code', $code->code) }}" required
                class="w-full rounded border-gray-300 font-mono uppercase focus:border-brand focus:ring-brand">
            @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="type" class="mb-1 block text-sm font-bold">Typ rabatu</label>
            <select id="type" name="type" x-model="type" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                @foreach (\App\Models\SklepDiscountCode::TYPES as $value => $label)
                    <option value="{{ $value }}" {{ old('type', $code->type ?: 'percent') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            @error('type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="value" class="mb-1 block text-sm font-bold">
                Wartość <span x-show="type === 'percent'" x-cloak>(%)</span><span x-show="type === 'fixed'" x-cloak>(grosze)</span>
            </label>
            <input type="number" id="value" name="value" min="1" value="{{ old('value', $code->value) }}" required
                class="w-40 rounded border-gray-300 focus:border-brand focus:ring-brand">
            <p class="mt-1 text-xs text-muted" x-show="type === 'fixed'" x-cloak>Kwota w groszach, np. 1000 = 10,00 zł.</p>
            @error('value') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="valid_from" class="mb-1 block text-sm font-bold">Ważny od <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <input type="date" id="valid_from" name="valid_from" value="{{ old('valid_from', $code->valid_from?->format('Y-m-d')) }}"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                @error('valid_from') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="valid_until" class="mb-1 block text-sm font-bold">Ważny do <span class="font-normal text-muted">(opcjonalnie)</span></label>
                <input type="date" id="valid_until" name="valid_until" value="{{ old('valid_until', $code->valid_until?->format('Y-m-d')) }}"
                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                @error('valid_until') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="max_uses" class="mb-1 block text-sm font-bold">Maksymalna liczba użyć <span class="font-normal text-muted">(opcjonalnie)</span></label>
            <input type="number" id="max_uses" name="max_uses" min="1" value="{{ old('max_uses', $code->max_uses) }}"
                class="w-40 rounded border-gray-300 focus:border-brand focus:ring-brand">
            <p class="mt-1 text-xs text-muted">Puste = bez limitu. Dotychczas użyto: {{ $code->used_count }}.</p>
            @error('max_uses') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $code->is_active ?? true) ? 'checked' : '' }}
                class="rounded border-gray-300 text-brand focus:ring-brand">
            <span class="text-sm font-bold">Aktywny</span>
        </label>

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz</button>
            <a href="{{ route('admin.sklep.kody-rabatowe.index') }}" class="text-sm text-muted hover:text-brand">Anuluj</a>
        </div>
    </form>
@endsection
