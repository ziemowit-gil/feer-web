{{-- Nagłówek działania: kategoria (z ikoną), tytuł, status i termin, zajawka, „Dla kogo”/„Od kiedy” i druk. Bez przycisku — kontakt jest w treści i w kolumnie bocznej. Zmienne ze strony działania. --}}
<a href="{{ route('categories.show', $project->category) }}" class="group inline-flex items-center gap-2 text-sm font-bold uppercase tracking-widest text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
    <span class="flex h-8 w-8 items-center justify-center rounded-md bg-brand-light text-base normal-case tracking-normal" aria-hidden="true">{!! icon_html($project->category->iconValue()) !!}</span>
    <span class="group-hover:underline group-hover:underline-offset-4">{{ $project->category->name }}</span>
</a>
<h1 class="mt-4 text-3xl font-extrabold leading-tight tracking-tight text-ink md:text-4xl" @if ($canInlineEdit) data-inline-field="title" data-inline-kind="text" @endif>{{ $project->title }}</h1>
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
@if ($showForWhom || $project->since)
    {{-- Najważniejsze fakty jako spokojne wiersze z ikoną (jasnoszare tło, bez ramek). --}}
    <dl class="mt-5 grid gap-2 sm:grid-cols-[repeat(auto-fit,minmax(14rem,1fr))]">
        @if ($showForWhom)
            <div class="flex items-start gap-3 rounded-md bg-gray-50 px-4 py-3">
                <i class="fa-solid fa-users mt-1 text-brand-dark" aria-hidden="true"></i>
                <div><dt class="text-xs font-bold uppercase tracking-widest text-ink">Dla kogo</dt><dd class="font-bold text-ink" @if ($canInlineEdit) data-inline-field="for_whom" data-inline-kind="text" data-inline-multiline @endif>{{ $project->for_whom }}</dd></div>
            </div>
        @endif
        @if ($project->since)
            <div class="flex items-start gap-3 rounded-md bg-gray-50 px-4 py-3">
                <i class="fa-solid fa-calendar-days mt-1 text-brand-dark" aria-hidden="true"></i>
                <div><dt class="text-xs font-bold uppercase tracking-widest text-ink">Od kiedy</dt><dd class="font-bold text-ink">{{ $project->since }}</dd></div>
            </div>
        @endif
    </dl>
@endif
<p class="mt-4 no-print">@include('partials.print-button')</p>
