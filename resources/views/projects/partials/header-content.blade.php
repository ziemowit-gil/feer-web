{{-- Nagłówek działania: kategoria, tytuł, status i termin, zajawka, druk, główny przycisk, „Dla kogo”/„Od kiedy”. Zmienne ze strony działania. --}}
<a href="{{ route('categories.show', $project->category) }}" class="inline-block text-sm font-bold uppercase tracking-widest text-brand-dark hover:underline hover:underline-offset-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
    {{ $project->category->name }}
</a>
<h1 class="mt-3 text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-4xl" @if ($canInlineEdit) data-inline-field="title" data-inline-kind="text" @endif>{{ $project->title }}</h1>
@if (($siteSettings->site_template ?? 'default') === 'feer')<span class="mt-4 block h-1 w-14 bg-brand" aria-hidden="true"></span>@endif
@if ($siteSettings->projects_stages_enabled && ($project->status || $project->starts_on))
    {{-- Status i termin jako zwykły tekst (bez plakietek). --}}
    <dl class="proj-meta">
        @if ($project->status)<div><dt>Status:</dt> <dd>{{ \App\Models\Project::STATUSES[$project->status] ?? '' }}</dd></div>@endif
        @if ($project->starts_on)<div><dt>Termin:</dt> <dd>{{ $project->starts_on->locale('pl')->isoFormat('D MMMM YYYY') }}@if ($project->ends_on) – {{ $project->ends_on->locale('pl')->isoFormat('D MMMM YYYY') }}@endif</dd></div>@endif
    </dl>
@endif
@if ($project->excerpt)
    <p class="mt-4 max-w-2xl text-lg leading-relaxed text-ink">{{ $project->excerpt }}</p>
@endif
<p class="mt-4 no-print">@include('partials.print-button')</p>
@if (! empty($mainCta['label']) && ! empty($mainCta['url']))
    <p class="mt-5"><a href="{{ $mainCta['url'] }}" class="proj-cta">{{ $mainCta['label'] }} <span aria-hidden="true">→</span></a></p>
@endif
@if ($showForWhom || $project->since)
    <p class="mt-4 text-base text-ink">
        @if ($showForWhom)<span @if ($canInlineEdit) data-inline-field="for_whom" data-inline-kind="text" data-inline-multiline @endif><i class="fa-solid fa-users mr-1.5 text-brand-dark" aria-hidden="true"></i>Dla kogo: <span class="font-bold">{{ $project->for_whom }}</span></span>@endif
        @if ($showForWhom && $project->since)<span aria-hidden="true"> · </span>@endif
        @if ($project->since)<span><i class="fa-solid fa-calendar-days mr-1.5 text-brand-dark" aria-hidden="true"></i>Od kiedy: <span class="font-bold">{{ $project->since }}</span></span>@endif
    </p>
@endif
