@extends('layouts.site')

@section('title', 'Sprawozdania roczne — ' . $siteSettings->site_name)
@section('meta_description', 'Roczne sprawozdania merytoryczne i finansowe ' . $siteSettings->siteNameGenitive() . ' — do pobrania w formacie PDF.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Sprawozdania', 'url' => null],
    ]])
@endsection

@section('content')
    @php
        $feer = ($siteSettings->site_template ?? 'default') === 'feer';
        $latest = null;
        if ($feer && $reports->isNotEmpty()) {
            $first = $reports->first();
            foreach (\App\Models\AnnualReport::TYPES as $type => $label) {
                if ($first->fileUrlFor($type)) { $latest = ['url' => $first->fileUrlFor($type), 'label' => $label, 'year' => $first->year]; break; }
            }
        }
    @endphp
    @if ($feer)
        {{-- Hero FEER: tekst z przyciskiem „najnowsze sprawozdanie” i skokiem do roku + panel z podsumowaniem. Kontrast ink/muted na bieli, biały na #1E6DFF. --}}
        <section>
            <div class="mx-auto grid max-w-6xl gap-8 px-4 pb-6 pt-8 md:pt-10 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-center">
                <div>
                    <h1 class="text-2xl font-bold leading-tight text-ink md:text-3xl">Sprawozdania roczne</h1>
                    <span class="mt-3 block h-1 w-14 bg-brand" aria-hidden="true"></span>
                    <p class="mt-5 max-w-2xl text-lg leading-relaxed text-ink">Publikujemy roczne sprawozdania merytoryczne i finansowe, żeby każdy mógł zobaczyć, co robimy i na co idą środki.</p>
                    @if ($latest)
                        <div class="mt-6 flex flex-wrap items-center gap-3">
                            <a href="{{ $latest['url'] }}" target="_blank" rel="noopener" download class="inline-flex min-h-12 items-center gap-2 rounded-md bg-brand px-6 text-base font-bold text-white transition hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ink focus-visible:ring-offset-2">
                                <i class="fa-solid fa-download" aria-hidden="true"></i>Pobierz najnowsze: {{ $latest['label'] }} {{ $latest['year'] }}<span class="sr-only"> (PDF)</span>
                            </a>
                        </div>
                    @endif
                    @if ($reports->count() > 1)
                        <nav aria-label="Przejdź do roku" class="mt-6">
                            <ul class="flex flex-wrap gap-2" role="list">
                                @foreach ($reports as $r)
                                    <li><a href="#rok-{{ $r->year }}" class="inline-flex min-h-11 items-center rounded-md bg-gray-100 px-4 text-sm font-bold text-ink transition hover:bg-gray-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">{{ $r->year }}</a></li>
                                @endforeach
                            </ul>
                        </nav>
                    @endif
                </div>
                <div class="rounded-md bg-brand-light p-6" aria-hidden="true">
                    <i class="fa-solid fa-file-invoice text-4xl text-brand-dark"></i>
                    <p class="mt-4 text-4xl font-bold leading-none text-ink">{{ $reports->count() }}</p>
                    <p class="mt-1 text-sm font-bold text-ink">{{ trans_choice('rok sprawozdawczy|lata sprawozdawcze|lat sprawozdawczych', $reports->count()) }} do pobrania</p>
                </div>
            </div>
        </section>
    @else
    <div class="border-b border-gray-100 bg-gray-50">
        <div class="mx-auto max-w-6xl px-4 py-12 md:py-16">
            <h1 class="text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-5xl">Sprawozdania roczne</h1>
            <p class="mt-4 max-w-2xl text-lg leading-relaxed text-ink">
                Publikujemy roczne sprawozdania merytoryczne i finansowe, żeby każdy mógł zobaczyć, co robimy i na co idą środki.
            </p>
        </div>
    </div>

    @endif

    <section class="mx-auto max-w-6xl px-4 py-12">

        @forelse ($reports as $report)
            @php($files = $report->additionalFiles())
            <div class="mb-10">
                <h2 id="rok-{{ $report->year }}" class="mb-4 flex scroll-mt-24 items-baseline gap-3 pb-3 text-2xl font-bold text-ink {{ $feer ? '' : 'border-b border-gray-200' }}">
                    <span>{{ $report->year }}</span>
                    <span class="text-base font-medium text-muted">rok sprawozdawczy</span>
                </h2>

                <ul aria-labelledby="rok-{{ $report->year }}" class="grid gap-4 sm:grid-cols-2">
                    {{-- Dwa sprawozdania roczne --}}
                    @foreach (\App\Models\AnnualReport::TYPES as $type => $label)
                        @php($url = $report->fileUrlFor($type))
                        @php($message = $report->messageFor($type))
                        <li>
                            @if ($url)
                                <a href="{{ $url }}" target="_blank" rel="noopener" download
                                    class="group flex h-full items-center gap-4 {{ $feer ? 'rounded-md bg-gray-50 p-5 hover:bg-gray-100' : 'rounded-lg border border-gray-200 bg-white p-5 hover:border-brand hover:shadow-md' }} transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                                    <span class="flex h-10 w-10 flex-none items-center justify-center text-brand-dark" aria-hidden="true">
                                        <i class="fa-solid fa-file-lines text-lg"></i>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block font-bold text-ink">{{ $label }}</span>
                                        <span class="block text-sm text-muted">Kliknij, aby pobrać (PDF)</span>
                                    </span>
                                    <i class="fa-solid fa-download flex-none text-brand-dark" aria-hidden="true"></i>
                                    <span class="sr-only">{{ $label }} za {{ $report->year }} rok (PDF)</span>
                                </a>
                            @else
                                <div class="flex h-full items-center gap-4 {{ $feer ? 'rounded-md bg-gray-50 p-5' : 'rounded-lg border border-dashed border-gray-300 bg-gray-50 p-5' }}">
                                    <span class="flex h-10 w-10 flex-none items-center justify-center text-muted" aria-hidden="true">
                                        <i class="fa-solid fa-file-lines text-lg"></i>
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block font-bold text-ink">{{ $label }}</span>
                                        <span class="block text-sm text-muted">{{ $message }}</span>
                                    </span>
                                </div>
                            @endif
                        </li>
                    @endforeach

                    {{-- Dodatkowe pliki (opcjonalne) --}}
                    @foreach ($files as $media)
                        <li>
                            <a href="{{ $media->getUrl() }}" target="_blank" rel="noopener" download
                                class="group flex h-full items-center gap-4 {{ $feer ? 'rounded-md bg-gray-50 p-5 hover:bg-gray-100' : 'rounded-lg border border-gray-200 bg-white p-5 hover:border-brand hover:shadow-md' }} transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
                                <span class="flex h-10 w-10 flex-none items-center justify-center text-brand-dark" aria-hidden="true">
                                    <i class="fa-solid {{ $report->fileIcon($media) }} text-lg"></i>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block font-bold text-ink">{{ $media->name }}</span>
                                    <span class="block text-sm text-muted">{{ strtoupper($media->extension) }} · {{ $media->human_readable_size }} · pobierz</span>
                                </span>
                                <i class="fa-solid fa-download flex-none text-brand-dark" aria-hidden="true"></i>
                                <span class="sr-only">{{ $media->name }} — plik dodatkowy za {{ $report->year }} rok</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @empty
            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-10 text-center text-muted">
                Nie opublikowaliśmy jeszcze żadnych sprawozdań. Zajrzyj wkrótce.
            </div>
        @endforelse
    </section>
@endsection
