@extends('layouts.site')

@section('title', 'Wypisanie z powiadomień — ' . $siteSettings->site_name)

@section('content')
    <section class="mx-auto max-w-md px-4 py-16 text-center">
        <h1 class="mb-3 text-2xl font-bold text-ink">Wypisanie z powiadomień</h1>
        <p class="mb-8 text-muted">
            Adres <strong>{{ $subscriber->email }}</strong> zostanie usunięty z&nbsp;listy subskrybentów.<br>
            Nie będziesz już otrzymywać żadnych powiadomień z&nbsp;tego serwisu.
        </p>

        <form method="POST" action="{{ route('subskrypcje.do-unsubscribe', $subscriber->token) }}">
            @csrf
            @method('DELETE')
            <button type="submit"
                class="rounded bg-red-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-red-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600 focus-visible:ring-offset-2">
                Tak, wypisuję się
            </button>
        </form>

        <a href="{{ route('home') }}" class="mt-6 inline-block text-sm text-muted hover:text-ink">
            Anuluj
        </a>
    </section>
@endsection
