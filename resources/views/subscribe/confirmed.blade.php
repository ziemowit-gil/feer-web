@extends('layouts.site')

@section('title', 'Subskrypcja potwierdzona — ' . $siteSettings->site_name)

@section('content')
    @php
        $feer = ($siteSettings->site_template ?? 'default') === 'feer';
        $inp = $feer ? 'w-full rounded-t-md rounded-b-none border-0 border-b-2 border-gray-500 bg-gray-100 text-base text-ink focus:border-brand focus:bg-white focus:outline-none focus:ring-0' : 'w-full rounded border-gray-300 focus:border-brand focus:ring-brand';
        $btn = $feer ? 'rounded-md' : 'rounded';
        $linkCls = $feer ? 'text-brand-dark underline underline-offset-4 hover:text-ink' : 'text-brand hover:text-brand-dark';
    @endphp
    <section class="mx-auto max-w-xl px-4 py-16 text-center">
        <div class="mb-6 flex justify-center">
            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-green-100 text-3xl text-green-600" aria-hidden="true">
                <i class="fa-solid fa-circle-check"></i>
            </span>
        </div>

        <h1 class="mb-3 text-2xl font-bold text-ink">Subskrypcja potwierdzona!</h1>
        @if ($feer)<span class="mb-5 mt-3 block h-1 w-14 bg-brand" aria-hidden="true"></span>@endif
        <p class="mb-6 text-muted">
            Będziemy informować Cię o&nbsp;nowościach z&nbsp;wybranych tematów:
        </p>

        <ul class="mb-8 inline-flex flex-wrap justify-center gap-2" aria-label="Wybrane tematy">
            @foreach ($subscriber->topicLabels() as $label)
                <li class="{{ $feer ? 'rounded bg-brand-light text-ink' : 'rounded-full bg-brand/10 text-brand' }} px-3 py-1 text-sm font-bold">{{ $label }}</li>
            @endforeach
        </ul>

        <p class="mb-2 text-sm text-muted">
            Chcesz zmienić preferencje? Wypełnij formularz ponownie z&nbsp;tym samym adresem.
        </p>
        <p class="text-sm text-muted">
            Aby się wypisać, użyj linku w&nbsp;każdym e-mailu.
        </p>

        <a href="{{ route('home') }}" class="mt-8 inline-block text-sm font-bold {{ $linkCls }}">
            ← Przejdź na stronę główną
        </a>
    </section>
@endsection
