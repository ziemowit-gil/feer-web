@extends('layouts.site')

@section('title', 'Wypisanie z newslettera — ' . $siteSettings->site_name)

@section('content')
    <section class="mx-auto max-w-xl px-4 py-16 text-center">
        @if ($done)
            <h1 class="mb-3 text-2xl font-bold text-ink">Zostałeś/-aś wypisany/-a</h1>
            <p class="mb-6 text-ink">Nie będziemy już wysyłać wiadomości na adres <strong>{{ $subscriber->email }}</strong>. Zgody zostały wycofane.</p>
            <p class="text-sm text-muted">Zmieniłeś/-aś zdanie? <a href="{{ route('newsletter.show') }}" class="font-bold text-brand-dark underline">Zapisz się ponownie</a>.</p>
        @else
            <h1 class="mb-3 text-2xl font-bold text-ink">Wypisać z newslettera?</h1>
            <p class="mb-6 text-ink">Adres: <strong>{{ $subscriber->email }}</strong></p>
            <form method="post" action="{{ route('newsletter.unsubscribe.post', ['token' => $subscriber->token]) }}" class="mx-auto max-w-md text-left">
                @csrf
                <label for="reason" class="mb-1 block text-sm font-bold text-ink">Powód (opcjonalnie)</label>
                <select id="reason" name="reason" class="mb-4 w-full rounded border-gray-500 focus:border-brand focus:ring-brand">
                    <option value="">— wybierz —</option>
                    <option>Za dużo wiadomości</option>
                    <option>Treści mnie nie interesują</option>
                    <option>Nie pamiętam, żebym się zapisywał/-a</option>
                    <option>Inny powód</option>
                </select>
                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="rounded-md bg-brand-dark px-6 py-3 font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Tak, wypisz mnie</button>
                    <a href="{{ route('newsletter.preferences', ['token' => $subscriber->token]) }}" class="rounded-md border-2 border-brand-dark px-6 py-3 font-bold text-brand-dark hover:bg-brand-dark hover:text-white">Wolę zmienić tematy</a>
                </div>
            </form>
        @endif
    </section>
@endsection
