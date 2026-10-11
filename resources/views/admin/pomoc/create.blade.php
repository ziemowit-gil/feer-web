@extends('admin.layout')

@section('title', 'Nowe zgłoszenie')

@section('content')
    <div class="mx-auto max-w-2xl">
        <form method="POST" action="{{ route('admin.pomoc.store') }}" class="space-y-6">
            @csrf

            <div class="p-6 space-y-5 rounded-md bg-white">
                <div>
                    <label for="subject" class="block text-sm font-semibold text-ink">Temat <span class="text-red-500" aria-hidden="true">*</span></label>
                    <input type="text" id="subject" name="subject" value="{{ old('subject') }}"
                        required maxlength="255"
                        class="mt-1 block w-full rounded-lg border-gray-500 focus:border-brand-dark focus:ring-brand-dark @error('subject') border-red-400 @enderror">
                    @error('subject')
                        <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="message" class="block text-sm font-semibold text-ink">Opis zgłoszenia <span class="text-red-500" aria-hidden="true">*</span></label>
                    <textarea id="message" name="message" rows="6" required maxlength="10000"
                        class="mt-1 block w-full rounded-lg border-gray-500 focus:border-brand-dark focus:ring-brand-dark @error('message') border-red-400 @enderror">{{ old('message') }}</textarea>
                    @error('message')
                        <p class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('admin.pomoc.index') }}" class="rounded-md px-4 py-2 text-sm font-semibold text-muted bg-white hover:bg-brand-light">
                    Anuluj
                </a>
                <button type="submit" class="rounded-lg bg-brand-dark px-5 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                    Wyślij zgłoszenie
                </button>
            </div>
        </form>
    </div>
@endsection
