@extends('layouts.site')

@section('title', ($expired ? 'Link wygasł' : 'Zapis potwierdzony') . ' — ' . $siteSettings->site_name)

@section('content')
    <section class="mx-auto max-w-xl px-4 py-16 text-center">
        @if ($expired)
            <h1 class="mb-3 text-2xl font-bold text-ink">Ten link potwierdzający wygasł</h1>
            <p class="mb-6 text-ink">Zapisz się ponownie — wyślemy nowy link.</p>
            <a href="{{ route('newsletter.show') }}" class="inline-block rounded-md bg-brand-dark px-6 py-3 font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Zapisz się ponownie</a>
        @else
            <div class="mb-6 flex justify-center">
                <span class="flex h-16 w-16 items-center justify-center rounded-full bg-green-100 text-3xl text-green-800" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
            </div>
            <h1 class="mb-3 text-2xl font-bold text-ink">Dziękujemy{{ $subscriber->firstName() ? ', ' . $subscriber->firstName() : '' }}! Zapis potwierdzony</h1>
            <p class="mb-2 text-ink">Będziesz otrzymywać wiadomości na temat: <strong>{{ implode(', ', $subscriber->topicLabels()) ?: 'aktualności' }}</strong>.</p>
            <p class="text-sm text-muted">W każdej chwili możesz zmienić tematy lub się wypisać.</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('newsletter.preferences', ['token' => $subscriber->token]) }}" class="inline-block rounded-md border-2 border-brand-dark px-5 py-2.5 font-bold text-brand-dark hover:bg-brand-dark hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Ustaw preferencje</a>
                <a href="{{ route('home') }}" class="inline-block rounded-md bg-brand-dark px-5 py-2.5 font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">Przejdź na stronę główną</a>
            </div>
        @endif
    </section>
@endsection
