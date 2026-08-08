@extends('layouts.site')

@section('title', 'Subskrypcja potwierdzona — ' . $siteSettings->site_name)

@section('content')
    <section class="mx-auto max-w-xl px-4 py-16 text-center">
        <div class="mb-6 flex justify-center">
            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-green-100 text-3xl text-green-600" aria-hidden="true">
                <i class="fa-solid fa-circle-check"></i>
            </span>
        </div>

        <h1 class="mb-3 text-2xl font-bold text-ink">Subskrypcja potwierdzona!</h1>
        <p class="mb-6 text-muted">
            Będziemy informować Cię o&nbsp;nowościach z&nbsp;wybranych tematów:
        </p>

        <ul class="mb-8 inline-flex flex-wrap justify-center gap-2" aria-label="Wybrane tematy">
            @foreach ($subscriber->topicLabels() as $label)
                <li class="rounded-full bg-brand/10 px-3 py-1 text-sm font-bold text-brand">{{ $label }}</li>
            @endforeach
        </ul>

        <p class="mb-2 text-sm text-muted">
            Chcesz zmienić preferencje? Wypełnij formularz ponownie z&nbsp;tym samym adresem.
        </p>
        <p class="text-sm text-muted">
            Aby się wypisać, użyj linku w&nbsp;każdym e-mailu.
        </p>

        <a href="{{ route('home') }}" class="mt-8 inline-block text-sm font-bold text-brand hover:text-brand-dark">
            ← Przejdź na stronę główną
        </a>
    </section>
@endsection
