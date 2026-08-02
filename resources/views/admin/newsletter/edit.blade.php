@extends('admin.layout')

@section('title', 'Newsletter')

@section('content')
    <form method="POST" action="{{ route('admin.newsletter.update') }}" class="max-w-3xl space-y-6 rounded-lg border border-gray-200 bg-white p-6">
        @csrf
        @method('PUT')

        <div>
            <label for="newsletter_code" class="mb-1 block text-sm font-bold">Kod formularza zapisu (np. z GetResponse)</label>
            <p class="mb-2 text-xs text-muted">
                Wklej tu kod osadzenia (HTML/JS) formularza zapisu na newsletter. Zostanie wyświetlony bez zmian na stronie
                <a href="{{ route('newsletter.show') }}" target="_blank" rel="noopener" class="text-brand underline">/newsletter</a>.
            </p>
            <textarea id="newsletter_code" name="newsletter_code" rows="16"
                class="w-full rounded border-gray-300 font-mono text-sm focus:border-brand focus:ring-brand">{{ old('newsletter_code', $settings->newsletter_code) }}</textarea>
            @error('newsletter_code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-3 border-t border-gray-100 pt-5">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz</button>
        </div>
    </form>
@endsection
