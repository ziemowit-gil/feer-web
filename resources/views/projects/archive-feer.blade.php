@extends('layouts.site')

@section('title', 'To już zrobiliśmy — ' . $siteSettings->site_name)
@section('meta_description', 'Działania, które już zrealizowaliśmy.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Działania', 'url' => route('projects.index')],
        ['label' => 'To już zrobiliśmy', 'url' => null],
    ]])
@endsection

{{--
    Archiwum działań w układzie FEER: spokojny tytuł z niebieskim akcentem, działania pogrupowane rocznikami jako lekkie wiersze
    (pasek koloru po lewej, miniatura, mikropis, data zakończenia, strzałka) — jak lista projektów. Kontrast: ink/muted na bieli.
--}}
@section('content')
    @php $byYear = $projects->groupBy(fn ($p) => $p->completed_at ? $p->completed_at->format('Y') : 'Wcześniej'); @endphp

    <section>
        <div class="mx-auto max-w-6xl px-4 pb-6 pt-8 md:pb-8 md:pt-10">
            <p class="text-xs font-bold uppercase tracking-widest text-muted">Archiwum</p>
            <h1 class="mt-1 text-2xl font-bold leading-tight text-ink md:text-3xl">To już zrobiliśmy</h1>
            <span class="mt-3 block h-1 w-14 bg-brand" aria-hidden="true"></span>
            @if ($archiveFilter ?? null)
                <p class="mt-4 max-w-2xl text-lg text-ink">Działania {{ $archiveFilter }}.</p>
                <p class="mt-2"><a href="{{ route('projects.archive') }}" class="inline-flex min-h-9 items-center text-sm font-bold text-brand-dark underline underline-offset-4 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">Pokaż wszystkie zrealizowane działania</a></p>
            @else
                <p class="mt-4 max-w-2xl text-lg text-ink">Działania, które już zrealizowaliśmy.</p>
            @endif
        </div>
    </section>

    <div class="mx-auto max-w-6xl px-4 py-10">
        @if ($projects->isEmpty())
            <p class="text-muted">{{ ($archiveFilter ?? null) ? 'Brak działań w tym okresie.' : 'Nie mamy jeszcze zrealizowanych działań do pokazania.' }}</p>
        @else
            <div class="grid gap-10 lg:grid-cols-[10rem_minmax(0,1fr)] lg:gap-14">
                @if ($byYear->count() > 1)
                    <nav aria-label="Lata realizacji" class="lg:sticky lg:top-6 lg:self-start">
                        <p class="mb-3 text-xs font-bold uppercase tracking-widest text-muted">Rok</p>
                        <ul class="feer-pills-row flex gap-2 overflow-x-auto pb-2 lg:flex-col lg:gap-1 lg:overflow-visible lg:pb-0" role="list">
                            @foreach ($byYear as $year => $items)
                                <li class="shrink-0">
                                    <a href="#rok-{{ \Illuminate\Support\Str::slug((string) $year) }}" class="flex min-h-11 items-center justify-between gap-3 rounded-md bg-gray-50 px-4 py-2 text-sm font-bold text-ink transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand lg:bg-transparent lg:px-3">
                                        <span>{{ $year }}</span><span class="text-muted">{{ $items->count() }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                @endif

                <div class="min-w-0 space-y-12 {{ $byYear->count() > 1 ? '' : 'lg:col-span-2' }}">
                    @foreach ($byYear as $year => $items)
                        <section id="rok-{{ \Illuminate\Support\Str::slug((string) $year) }}" class="scroll-mt-24" aria-labelledby="rok-h-{{ \Illuminate\Support\Str::slug((string) $year) }}">
                            <h2 id="rok-h-{{ \Illuminate\Support\Str::slug((string) $year) }}" class="mb-4 text-2xl font-bold text-ink">{{ $year }}</h2>
                            <ul class="space-y-1" role="list">
                                @foreach ($items as $project)
                                    @php
                                        $accent = \App\Support\Color::isValid($project->accent_color ?? null)
                                            ? $project->accent_color
                                            : (($project->audience ?? 'brand') === 'ngo' ? $siteSettings->audienceColor('ngo') : 'var(--color-brand)');
                                    @endphp
                                    <li>
                                        <a href="{{ route('projects.show', $project) }}" class="feer-card group flex items-center gap-4 rounded-md py-4 pl-4 pr-3 transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" style="border-left: 4px solid {{ $accent }}">
                                            @if ($project->image_url)
                                                <img src="{{ $project->image_url }}" alt="" loading="lazy" class="h-14 w-20 flex-none rounded-md object-cover sm:h-16 sm:w-24">
                                            @endif
                                            <span class="min-w-0 flex-1">
                                                <span class="block text-xs font-bold uppercase tracking-widest text-muted">{{ $project->category->name ?? 'Działanie' }}@if ($project->completed_at) · zakończono {{ $project->completed_at->translatedFormat('F Y') }}@endif</span>
                                                <span class="mt-0.5 block text-lg font-bold leading-snug text-ink group-hover:text-brand-dark">{{ $project->title }}</span>
                                                @if ($teaser = $project->teaser())
                                                    <span class="mt-1 line-clamp-2 block text-sm leading-relaxed text-muted">{{ $teaser }}</span>
                                                @endif
                                            </span>
                                            <span class="flex-none text-lg text-brand-dark transition group-hover:translate-x-1" aria-hidden="true">→</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endforeach
                </div>
            </div>
        @endif

        <p class="mt-12 border-t border-gray-200 pt-6">
            <a href="{{ route('projects.index') }}" class="inline-flex min-h-11 items-center text-sm font-bold text-brand-dark underline underline-offset-4 hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand">← Wróć do działań</a>
        </p>
    </div>
@endsection
