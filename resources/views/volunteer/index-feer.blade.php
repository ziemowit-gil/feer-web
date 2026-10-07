@extends('layouts.site')

@section('title', 'Wolontariat — ' . $siteSettings->site_name)
@section('meta_description', 'Aktualne ogłoszenia o wolontariacie w ' . $siteSettings->site_name . '.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Wolontariat', 'url' => null],
    ]])
@endsection

{{-- Wolontariat w stylu FEER: jasny nagłówek, ogłoszenia jako płaskie karty z paskiem koloru grupy docelowej, fakty jako zwykły tekst. --}}
@section('content')
    <section class="bg-gray-50">
        <div class="mx-auto max-w-6xl px-4 py-12 md:py-16">
            <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-5xl">Wolontariat</h1>
            <p class="mt-4 max-w-2xl text-lg leading-relaxed text-ink">Dołącz do nas w konkretnym działaniu. Poniżej znajdziesz aktualne ogłoszenia — każde odpowiada na to, co warto wiedzieć, zanim się zgłosisz.</p>
        </div>
    </section>

    <div class="mx-auto max-w-6xl px-4 py-12">
        @if ($ads->isEmpty())
            <div class="rounded-md bg-gray-50 p-8 text-center text-ink">
                Obecnie nie prowadzimy naboru. Zajrzyj wkrótce — albo napisz do nas przez
                <a href="{{ route('contact.show') }}" class="font-bold text-brand-dark underline underline-offset-4 hover:no-underline">formularz kontaktowy</a>.
            </div>
        @else
            <ul class="grid gap-6 md:grid-cols-2" role="list">
                @foreach ($ads as $ad)
                    @php $accent = $siteSettings->contrastSafeColor($siteSettings->audienceColor($ad->audience)); @endphp
                    <li>
                        <article class="feer-card group relative flex h-full flex-col rounded-lg bg-gray-50 p-6 hover:bg-gray-100 focus-within:ring-2 focus-within:ring-brand" style="border-left: 4px solid {{ $accent }}">
                            <p class="text-xs font-bold uppercase tracking-widest text-muted">
                                {{ $ad->modeLabel() }}@if ($ad->q_location) · {{ $ad->q_location }}@endif
                                @if ($ad->closes_at)<span class="text-ink"> · zgłoszenia do {{ $ad->closes_at->locale('pl')->isoFormat('D MMM YYYY') }}</span>@endif
                            </p>
                            <h2 class="mt-2 text-xl font-bold leading-snug text-ink group-hover:text-brand-dark">
                                <a href="{{ route('volunteer.show', $ad) }}" class="stretched-link focus-visible:outline-none">{{ $ad->title }}</a>
                            </h2>
                            @if ($ad->lead)<p class="mt-2 flex-1 leading-relaxed text-muted">{{ $ad->lead }}</p>@endif
                            <p class="mt-4 text-sm font-bold text-brand-dark" aria-hidden="true">Zobacz szczegóły →</p>
                        </article>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
