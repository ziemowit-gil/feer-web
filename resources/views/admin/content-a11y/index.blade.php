@extends('admin.layout')

@section('title', 'Dostępność treści')

@section('content')
    @php
        $btn = 'inline-flex min-h-10 items-center gap-2 rounded-md px-4 text-sm font-bold';
        $rules = \App\Services\Content\ContentA11yChecker::RULES;
        $q = fn (array $extra) => route('admin.content-a11y.index', array_filter(array_merge(['typ' => $type, 'regula' => $rule], $extra)));
    @endphp

    <div class="mb-6 max-w-3xl">
        <h1 class="text-2xl font-bold text-ink">Dostępność treści</h1>
        <p class="mt-1 text-sm text-ink">
            Treści stron, aktualności i działań sprawdzone pod kątem tego, co redaktor może poprawić sam: kolejność nagłówków,
            teksty linków, opisy obrazów, nagłówki tabel i tytuły osadzeń. Te same uwagi widać w panelu bocznym podczas edycji.
            Obrazy i dokumenty z biblioteki sprawdzisz w
            <a href="{{ route('admin.multimedia.alt-audit') }}" class="font-bold text-brand-dark underline underline-offset-2 hover:no-underline">audycie opisów alternatywnych</a> i
            <a href="{{ route('admin.multimedia.documents') }}" class="font-bold text-brand-dark underline underline-offset-2 hover:no-underline">dostępności dokumentów</a>.
        </p>
    </div>

    <dl class="mb-6 grid gap-3 sm:grid-cols-3">
        <div class="rounded-md bg-white p-4">
            <dt class="text-xs font-bold uppercase tracking-widest text-muted">Treści z uwagami</dt>
            <dd class="mt-1 text-2xl font-bold text-ink">{{ $totals['records'] }}</dd>
        </div>
        <div class="rounded-md bg-white p-4">
            <dt class="text-xs font-bold uppercase tracking-widest text-muted">Błędy</dt>
            <dd class="mt-1 text-2xl font-bold text-ink">{{ $totals['errors'] }}</dd>
            <dd class="text-sm text-ink">wykluczają część odbiorców</dd>
        </div>
        <div class="rounded-md bg-white p-4">
            <dt class="text-xs font-bold uppercase tracking-widest text-muted">Uwagi</dt>
            <dd class="mt-1 text-2xl font-bold text-ink">{{ $totals['warnings'] }}</dd>
            <dd class="text-sm text-ink">utrudniają korzystanie</dd>
        </div>
    </dl>

    <div class="mb-6 space-y-3">
        <nav class="flex flex-wrap gap-2" aria-label="Rodzaj treści">
            <a href="{{ $q(['typ' => null]) }}" @if (! $type) aria-current="page" @endif class="{{ $btn }} {{ ! $type ? 'bg-brand-dark text-white' : 'bg-white text-ink hover:bg-brand-light' }}">Wszystkie treści</a>
            @foreach ($sources as $key => $source)
                <a href="{{ $q(['typ' => $key]) }}" @if ($type === $key) aria-current="page" @endif class="{{ $btn }} {{ $type === $key ? 'bg-brand-dark text-white' : 'bg-white text-ink hover:bg-brand-light' }}">{{ $source['label'] }}</a>
            @endforeach
        </nav>
        @if ($byRule->isNotEmpty() || $rule)
            <nav class="flex flex-wrap gap-2" aria-label="Rodzaj problemu">
                <a href="{{ $q(['regula' => null]) }}" @if (! $rule) aria-current="page" @endif class="{{ $btn }} {{ ! $rule ? 'bg-brand-dark text-white' : 'bg-white text-ink hover:bg-brand-light' }}">Wszystkie problemy</a>
                @foreach ($byRule as $key => $count)
                    <a href="{{ $q(['regula' => $key]) }}" @if ($rule === $key) aria-current="page" @endif class="{{ $btn }} {{ $rule === $key ? 'bg-brand-dark text-white' : 'bg-white text-ink hover:bg-brand-light' }}">{{ $rules[$key] }} ({{ $count }})</a>
                @endforeach
            </nav>
        @endif
    </div>

    @if ($totals['fixable'] > 0)
        <section class="mb-6 rounded-md bg-white p-5" aria-labelledby="a11y-fixer-h">
            <h2 id="a11y-fixer-h" class="text-base font-bold text-ink">Poprawiator</h2>
            <p class="mt-1 max-w-3xl text-sm text-ink">
                Poprawi automatycznie to, co nie wymaga decyzji: usunie puste nagłówki, ułoży poziomy nagłówków (H2, H3…),
                zamieni pogrubione śródtytuły na nagłówki, akapity z myślnikami na listy, oznaczy pierwszy wiersz tabeli jako nagłówek
                i nada tytuły osadzeniom. Tekstów linków i opisów obrazów nie zmienia — te popraw ręcznie.
                Każda poprawiona treść dostaje nową wersję, więc zmianę cofniesz w jej historii.
            </p>
            <form method="POST" action="{{ route('admin.content-a11y.fix') }}" class="mt-3">
                @csrf
                <input type="hidden" name="typ" value="{{ $type }}">
                <input type="hidden" name="regula" value="{{ $rule }}">
                <input type="hidden" name="back_typ" value="{{ $type }}">
                <input type="hidden" name="back_regula" value="{{ $rule }}">
                <button type="submit" class="{{ $btn }} bg-brand-dark text-white hover:bg-ink">
                    <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
                    Popraw automatycznie {{ $totals['fixable'] }} {{ trans_choice('treść|treści|treści', $totals['fixable']) }}{{ $rule || $type ? ' z wybranych filtrów' : '' }}
                </button>
            </form>
        </section>
    @endif

    @if ($rows->isEmpty())
        <div class="rounded-md bg-white p-10 text-center">
            <i class="fa-solid fa-circle-check mb-2 text-3xl text-brand-dark" aria-hidden="true"></i>
            <p class="font-bold text-ink">Nie znaleziono problemów{{ $rule || $type ? ' dla wybranych filtrów' : '' }}.</p>
        </div>
    @else
        <ul class="space-y-3" role="list">
            @foreach ($rows as $row)
                <li class="rounded-md bg-white p-5" x-data="{ open: {{ $loop->index < 3 ? 'true' : 'false' }} }">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-widest text-muted">{{ $row['type'] }}{{ $row['published'] ? '' : ' · szkic' }}</p>
                            <h2 class="text-base font-bold text-ink">{{ $row['title'] }}</h2>
                            <p class="text-sm text-ink">
                                Błędy: <strong>{{ $row['summary']['errors'] }}</strong> · uwagi: <strong>{{ $row['summary']['warnings'] }}</strong>
                            </p>
                        </div>
                        <div class="flex flex-none flex-wrap gap-2">
                            <button type="button" @click="open = !open" :aria-expanded="open" class="{{ $btn }} bg-gray-100 text-ink hover:bg-brand-light">
                                <span x-text="open ? 'Zwiń' : 'Pokaż uwagi'">Pokaż uwagi</span><span class="sr-only"> — {{ $row['title'] }}</span>
                            </button>
                            @if ($row['fixable'])
                                <form method="POST" action="{{ route('admin.content-a11y.fix') }}">
                                    @csrf
                                    <input type="hidden" name="typ" value="{{ $row['key'] }}">
                                    <input type="hidden" name="id" value="{{ $row['id'] }}">
                                    <input type="hidden" name="regula" value="{{ $rule }}">
                                    <input type="hidden" name="back_typ" value="{{ $type }}">
                                    <input type="hidden" name="back_regula" value="{{ $rule }}">
                                    <button type="submit" class="{{ $btn }} bg-brand-dark text-white hover:bg-ink">Popraw automatycznie<span class="sr-only"> — {{ $row['title'] }}</span></button>
                                </form>
                            @endif
                            @if ($row['edit_url'])
                                <a href="{{ $row['edit_url'] }}" class="{{ $btn }} {{ $row['fixable'] ? 'bg-gray-100 text-ink hover:bg-brand-light' : 'bg-brand-dark text-white hover:bg-ink' }}">Edytuj<span class="sr-only"> — {{ $row['title'] }}</span></a>
                            @endif
                        </div>
                    </div>
                    @if ($row['fixable'])
                        <p class="mt-2 text-sm text-ink">
                            <span class="font-bold">Poprawiator zrobi:</span>
                            {{ collect($row['fixable'])->map(fn ($n, $k) => \App\Services\Content\ContentA11yFixer::FIXES[$k].' ('.$n.')')->implode(', ') }}.
                        </p>
                    @endif
                    <div x-show="open" x-cloak class="mt-4 rounded-md bg-gray-50 p-4">
                        @include('admin.content-a11y.issues', ['issues' => $row['issues']])
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
@endsection
