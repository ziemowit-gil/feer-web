{{-- Nagłówek działania: kategoria (z ikoną), tytuł, status i termin, zajawka, pasek najważniejszych informacji i druk. Bez przycisku — kontakt jest w treści i w kolumnie bocznej. Zmienne ze strony działania. --}}
<a href="{{ $project->category->publicUrl() }}" class="group inline-flex items-center gap-2 text-sm font-bold uppercase tracking-widest text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
    <span class="flex h-8 w-8 items-center justify-center rounded-md bg-brand-light text-base normal-case tracking-normal" aria-hidden="true">{!! icon_html($project->category->iconValue()) !!}</span>
    <span class="group-hover:underline group-hover:underline-offset-4">{{ $project->category->name }}</span>
</a>
<h1 class="mt-4 text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-4xl" @if ($canInlineEdit) data-inline-field="title" data-inline-kind="text" @endif>{{ $project->title }}</h1>
@if (($siteSettings->site_template ?? 'default') === 'feer')<span class="mt-4 block h-1 w-14 bg-brand" aria-hidden="true"></span>@endif
@if ($siteSettings->projects_stages_enabled && $project->status)
    {{-- Status zwykłym tekstem (termin jest w pasku najważniejszych informacji). --}}
    <dl class="proj-meta">
        <div><dt>Status:</dt> <dd>{{ \App\Models\Project::STATUSES[$project->status] ?? '' }}</dd></div>
    </dl>
@endif
@if ($project->excerpt)
    <p class="mt-4 max-w-2xl text-lg leading-relaxed text-ink">{{ $project->excerpt }}</p>
@endif
@php
    // Najważniejsze informacje w jednym rzędzie: forma udziału, termin, miejsce/forma, dla kogo, od kiedy.
    $hdrFacts = (array) (($siteSettings->projects_extras_enabled ?? false) ? ($project->quick_facts ?? []) : []);
    $hdrPaid = $project->is_paid || $project->isPaidOffer();
    $hdrTerm = $project->starts_on
        ? $project->starts_on->locale('pl')->isoFormat('D MMMM YYYY').($project->ends_on ? ' – '.$project->ends_on->locale('pl')->isoFormat('D MMMM YYYY') : '')
        : ($hdrFacts['duration'] ?? null);
    $hdrPlace = collect([$hdrFacts['place'] ?? null, isset($hdrFacts['mode']) ? (\App\Models\Project::MODES[$hdrFacts['mode']] ?? null) : null])->filter()->implode(' · ');
    $keyFacts = array_values(array_filter([
        ['icon' => $hdrPaid ? 'fa-coins' : 'fa-gift', 'label' => 'Udział', 'value' => $hdrPaid ? 'Odpłatny' : 'Bezpłatny'],
        $hdrTerm ? ['icon' => 'fa-calendar-days', 'label' => 'Termin', 'value' => $hdrTerm] : null,
        $hdrPlace !== '' ? ['icon' => 'fa-location-dot', 'label' => 'Gdzie', 'value' => $hdrPlace] : null,
        $showForWhom ? ['icon' => 'fa-users', 'label' => 'Dla kogo', 'value' => $project->for_whom, 'inline' => 'for_whom'] : null,
        $project->since ? ['icon' => 'fa-clock-rotate-left', 'label' => 'Od kiedy', 'value' => $project->since] : null,
    ]));
@endphp
@if ($keyFacts)
    {{-- Najważniejsze informacje: jeden spokojny wiersz tekstu z ikonami (bez tła i ramek), zawija się na wąskich ekranach. --}}
    <dl class="mt-6 flex flex-wrap gap-x-6 gap-y-2.5 text-base text-ink" aria-label="Najważniejsze informacje">
        @foreach ($keyFacts as $f)
            <div class="flex items-baseline gap-2">
                <i class="fa-solid {{ $f['icon'] }} w-4 flex-none text-center text-brand-dark" aria-hidden="true"></i>
                <dt>{{ $f['label'] }}:</dt>
                <dd class="font-bold" @if ($canInlineEdit && ! empty($f['inline'])) data-inline-field="{{ $f['inline'] }}" data-inline-kind="text" data-inline-multiline @endif>{{ $f['value'] }}</dd>
            </div>
        @endforeach
    </dl>
@endif
<p class="mt-4 no-print">@include('partials.print-button')</p>
