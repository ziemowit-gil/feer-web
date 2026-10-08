{{-- Typ „Regulamin lub dokument": data obowiązywania i wersja, automatyczny spis treści (H2/H3), poprzednie wersje, druk. --}}
@php
    $td = $page->typeData();
    [$contentHtml, $toc] = \App\Support\TableOfContents::inject(\App\Support\ShortcodeParser::render($page->content));
    $versions = collect($td['versions'])->filter(fn ($v) => filled($v['label'] ?? null) && filled($v['url'] ?? null));
@endphp
@once
    <style>
        .rg-meta { display: flex; flex-wrap: wrap; gap: .5rem 1.5rem; margin: 0 0 1.25rem; padding: .75rem 1rem; border: 2px solid var(--color-brand); border-radius: .5rem; background: #fff; font-weight: 600; }
        .rg-meta dt { display: inline; font-weight: 800; } .rg-meta dd { display: inline; margin: 0; } .rg-meta div { display: inline; }
        .rg-ver { margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #d1d5db; } .rg-ver a { color: var(--color-brand); text-decoration: underline; text-underline-offset: 3px; font-weight: 700; }
    </style>
@endonce
<section class="mx-auto max-w-5xl px-4 py-8">
    <h1 class="mb-3 text-3xl font-bold text-ink">{{ $page->title }}</h1>
    @if (filled($td['valid_from'] ?? null) || filled($td['version'] ?? null))
        <dl class="rg-meta">
            @if (filled($td['valid_from'] ?? null))<div><dt>Obowiązuje od:</dt> <dd>{{ $td['valid_from'] }}</dd></div>@endif
            @if (filled($td['version'] ?? null))<div><dt>Wersja:</dt> <dd>{{ $td['version'] }}</dd></div>@endif
        </dl>
    @endif
    @if (filled($td['lead'] ?? null))<p class="mb-4 max-w-3xl text-lg leading-relaxed text-ink">{{ $td['lead'] }}</p>@endif
    <p class="mb-6 no-print">@include('partials.print-button')</p>

    <div class="grid gap-10 {{ $toc ? 'md:grid-cols-[1fr_240px]' : '' }}">
        <div class="min-w-0">
            @if ($toc)@include('partials.page-toc', ['toc' => $toc, 'variant' => 'mobile'])@endif
            <div class="prose max-w-none text-ink">{!! $contentHtml !!}</div>

            @if ($versions->isNotEmpty())
                <section class="rg-ver" aria-labelledby="rg-ver-h">
                    <h2 id="rg-ver-h" class="mb-2 border-l-4 border-brand pl-3 text-xl font-bold text-ink">Poprzednie wersje</h2>
                    <ul role="list" class="space-y-1">
                        @foreach ($versions as $v)<li><a href="{{ $v['url'] }}">{{ $v['label'] }}</a></li>@endforeach
                    </ul>
                </section>
            @endif
            @include('partials.attachments-list', ['attachments' => $page->attachments])
        </div>
        @if ($toc)
            <div class="md:sticky md:top-24 md:self-start">@include('partials.page-toc', ['toc' => $toc, 'variant' => 'desktop'])</div>
        @endif
    </div>
</section>
