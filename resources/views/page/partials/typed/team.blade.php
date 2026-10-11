{{--
    Typ „Zespół": karty osób (imię i nazwisko, rola, opis, e-mail).
    Płasko: karty na szarym tle, bez obramowań. Menu działu w kolumnie bocznej, gdy jest.
    Dane: $page->typeData().
--}}
@php
    $td = $page->typeData();
    $members = collect($td['members'])->filter(fn ($m) => filled($m['name'] ?? null));
    $localNav = ($menuSiblings ?? collect())->isNotEmpty();
    $link = 'font-bold text-brand-dark underline underline-offset-4 hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
@endphp
<section class="mx-auto max-w-6xl px-4 py-12" data-page-type="team">
    @include('page.partials.typed._head')

    <div class="grid gap-10 {{ $localNav ? 'lg:grid-cols-[minmax(0,1fr)_22rem]' : '' }}">
        @if ($localNav)
            <aside class="lg:order-last" aria-label="Menu działu">
                <div class="lg:sticky lg:top-24">@include('partials.page-local-nav', ['menuSiblings' => $menuSiblings])</div>
            </aside>
        @endif

        <div class="min-w-0">
            @if ($page->content)
                <div class="prose mb-8 max-w-none text-ink">{!! \App\Support\ShortcodeParser::render($page->content) !!}</div>
            @endif

            @if ($members->isEmpty())
                <p class="text-muted">Lista osób zostanie wkrótce uzupełniona.</p>
            @else
                <ul role="list" class="grid gap-4 sm:grid-cols-2 {{ $localNav ? '' : 'lg:grid-cols-3' }}">
                    @foreach ($members as $m)
                        <li class="flex flex-col rounded-md bg-gray-50 p-5">
                            <h2 class="text-lg font-bold text-ink">{{ $m['name'] }}</h2>
                            @if (filled($m['role'] ?? null))<p class="font-semibold text-brand-dark">{{ $m['role'] }}</p>@endif
                            @if (filled($m['text'] ?? null))<p class="mt-2 leading-relaxed text-ink">{{ $m['text'] }}</p>@endif
                            @if (filled($m['email'] ?? null))
                                <p class="mt-auto pt-3"><a class="{{ $link }} break-all" href="mailto:{{ $m['email'] }}"><i class="fa-solid fa-envelope mr-1.5" aria-hidden="true"></i>{{ $m['email'] }}<span class="sr-only"> — napisz do: {{ $m['name'] }}</span></a></p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            @include('partials.attachments-list', ['attachments' => $page->attachments])
        </div>
    </div>
</section>
