@extends('layouts.site')

@section('title', 'Wolontariat — ' . $siteSettings->site_name)
@section('meta_description', 'Aktualne ogłoszenia o wolontariacie w ' . $siteSettings->site_name . '.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Wolontariat', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-12">
        <h1 class="mb-2 text-3xl font-bold text-ink">Wolontariat</h1>
        <p class="mb-8 max-w-2xl text-muted">Dołącz do nas w konkretnym działaniu. Poniżej znajdziesz aktualne ogłoszenia — każde odpowiada na to, co warto wiedzieć, zanim się zgłosisz.</p>

        @if ($ads->isEmpty())
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-8 text-center text-muted">
                Obecnie nie prowadzimy naboru. Zajrzyj wkrótce — albo napisz do nas przez <a href="{{ route('contact.show') }}" class="font-bold text-brand hover:text-brand-dark">formularz kontaktowy</a>.
            </div>
        @else
            <ul class="grid gap-5 sm:grid-cols-2">
                @foreach ($ads as $ad)
                    @php $accent = $siteSettings->contrastSafeColor($siteSettings->audienceColor($ad->audience)); @endphp
                    <li class="flex flex-col rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md" style="--accent: {{ $accent }}">
                        <div class="mb-3 flex flex-wrap gap-2 text-xs">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1">
                                <i class="fa-solid fa-location-dot" aria-hidden="true" style="color: var(--accent)"></i>
                                {{ $ad->modeLabel() }}@if ($ad->q_location) · {{ $ad->q_location }}@endif
                            </span>
                            @if ($ad->closes_at)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1">
                                    <i class="fa-solid fa-calendar-day" aria-hidden="true" style="color: var(--accent)"></i>
                                    do {{ $ad->closes_at->locale('pl')->isoFormat('D MMM YYYY') }}
                                </span>
                            @endif
                        </div>
                        <h2 class="text-xl font-bold text-ink">
                            <a href="{{ route('volunteer.show', $ad) }}" class="hover:underline" style="text-decoration-color: var(--accent)">{{ $ad->title }}</a>
                        </h2>
                        <p class="mt-2 flex-1 text-muted">{{ $ad->lead }}</p>
                        <a href="{{ route('volunteer.show', $ad) }}" class="mt-4 inline-flex items-center gap-2 self-start font-bold" style="color: var(--accent)">
                            Zobacz szczegóły <i class="fa-solid fa-arrow-right text-sm" aria-hidden="true"></i>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
