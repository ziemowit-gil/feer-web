@extends('admin.layout')

@section('title', $authorization->exists ? 'Edytuj wpis rejestru' : 'Nowy wpis rejestru')

@section('content')
    <div class="mx-auto max-w-2xl">
        <form method="POST"
              action="{{ $authorization->exists ? route('admin.pelnomocnictwa.update', $authorization) : route('admin.pelnomocnictwa.store') }}"
              class="space-y-6">
            @csrf
            @if ($authorization->exists)
                @method('PUT')
            @endif

            <div class="space-y-5 rounded-lg border border-gray-200 bg-white p-6">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <fieldset>
                        <legend class="block text-sm font-semibold text-ink">Rodzaj dokumentu <span class="text-red-500" aria-hidden="true">*</span></legend>
                        <div class="mt-2 flex gap-4">
                            @foreach (\App\Models\Authorization::TYPES as $key => $label)
                                <label class="inline-flex items-center gap-2 text-sm text-ink">
                                    <input type="radio" name="type" value="{{ $key }}"
                                           @checked(old('type', $authorization->type ?? 'pelnomocnictwo') === $key)
                                           class="border-gray-300 text-brand focus:ring-brand">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        @error('type')
                            <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <div>
                        <label for="document_number" class="block text-sm font-semibold text-ink">Numer dokumentu <span class="text-red-500" aria-hidden="true">*</span></label>
                        <input type="text" id="document_number" name="document_number"
                               value="{{ old('document_number', $authorization->document_number) }}"
                               required maxlength="100" placeholder="np. PEL/2026/12"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand focus:ring-brand @error('document_number') border-red-400 @enderror">
                        @error('document_number')
                            <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="principal" class="block text-sm font-semibold text-ink">Udzielający <span class="text-red-500" aria-hidden="true">*</span></label>
                        <input type="text" id="principal" name="principal"
                               value="{{ old('principal', $authorization->principal ?? 'Zarząd Fundacji') }}"
                               required maxlength="255"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand focus:ring-brand @error('principal') border-red-400 @enderror">
                        @error('principal')
                            <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="grantee_name" class="block text-sm font-semibold text-ink">Osoba / podmiot umocowany <span class="text-red-500" aria-hidden="true">*</span></label>
                        <input type="text" id="grantee_name" name="grantee_name"
                               value="{{ old('grantee_name', $authorization->grantee_name) }}"
                               required maxlength="255" placeholder="imię i nazwisko lub nazwa podmiotu"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand focus:ring-brand @error('grantee_name') border-red-400 @enderror">
                        @error('grantee_name')
                            <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="scope" class="block text-sm font-semibold text-ink">Zakres umocowania <span class="text-red-500" aria-hidden="true">*</span></label>
                    <textarea id="scope" name="scope" rows="3" required maxlength="2000"
                              placeholder="czego dotyczy pełnomocnictwo / upoważnienie"
                              class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand focus:ring-brand @error('scope') border-red-400 @enderror">{{ old('scope', $authorization->scope) }}</textarea>
                    @error('scope')
                        <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Okres ważności — puste „do" oznacza dokument bezterminowy --}}
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="valid_from" class="block text-sm font-semibold text-ink">Ważny od <span class="text-red-500" aria-hidden="true">*</span></label>
                        <input type="date" id="valid_from" name="valid_from"
                               value="{{ old('valid_from', $authorization->valid_from?->format('Y-m-d')) }}" required
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand focus:ring-brand @error('valid_from') border-red-400 @enderror">
                        @error('valid_from')
                            <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="valid_to" class="block text-sm font-semibold text-ink">Ważny do</label>
                        <input type="date" id="valid_to" name="valid_to"
                               value="{{ old('valid_to', $authorization->valid_to?->format('Y-m-d')) }}"
                               aria-describedby="valid-to-hint"
                               class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand focus:ring-brand @error('valid_to') border-red-400 @enderror">
                        <p id="valid-to-hint" class="mt-1 text-xs text-muted">Zostaw puste, jeśli dokument jest bezterminowy.</p>
                        @error('valid_to')
                            <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-ink">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           @checked(old('is_active', $authorization->is_active ?? true))
                           class="rounded border-gray-300 text-brand focus:ring-brand">
                    Dokument obowiązuje (odznacz, aby oznaczyć jako unieważniony)
                </label>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('admin.pelnomocnictwa.index') }}"
                   class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-muted hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">Anuluj</a>
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                    {{ $authorization->exists ? 'Zapisz zmiany' : 'Dodaj wpis' }}
                </button>
            </div>
        </form>
    </div>
@endsection
