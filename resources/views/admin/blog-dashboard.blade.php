@extends('admin.layout')

@section('title', 'Pulpit Wiem FEER')

@section('content')
    @php
        $btn = 'inline-flex min-h-10 items-center gap-2 rounded-md px-4 text-sm font-bold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
        $link = 'font-bold text-brand-dark underline hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
        $date = fn ($d) => $d?->locale('pl')->isoFormat('D MMMM YYYY, HH:mm');
    @endphp

    {{-- ── Nagłówek i szybkie akcje ─────────────────────────────────── --}}
    <header class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-2xl font-extrabold text-ink sm:text-3xl">Wiem FEER</h2>
            <p class="mt-1 text-sm text-muted">Pulpit bloga — wpisy, moderacja i lista rzeczy do poprawienia.</p>
        </div>
        <nav aria-label="Szybkie akcje bloga" class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.wiem-feer.create') }}" class="{{ $btn }} bg-brand-dark text-white hover:bg-ink">
                <i class="fa-solid fa-plus text-xs" aria-hidden="true"></i> Nowy wpis
            </a>
            <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="{{ $btn }} bg-gray-100 text-ink hover:bg-brand-light">
                <i class="fa-solid fa-arrow-up-right-from-square text-xs" aria-hidden="true"></i> Zobacz blog<span class="sr-only"> (otwiera się w nowej karcie)</span>
            </a>
            <a href="{{ route('admin.autorzy-bloga.index') }}" class="{{ $btn }} bg-gray-100 text-ink hover:bg-brand-light">Autorzy</a>
            <a href="{{ route('admin.kategorie-bloga.index') }}" class="{{ $btn }} bg-gray-100 text-ink hover:bg-brand-light">Kategorie</a>
        </nav>
    </header>

    @if ($dbError)
        <div role="alert" class="rounded-md bg-gray-50 px-5 py-4 text-sm text-ink">
            <p class="font-bold">Baza bloga jest niedostępna.</p>
            <p class="mt-1 text-muted">Pulpit nie może pobrać danych. Uruchom migracje bloga (<code>php artisan migrate --database=blog --path=database/migrations/blog</code>) albo skontaktuj się z administratorem.</p>
        </div>
    @else
        {{-- ── Liczniki ─────────────────────────────────────────────── --}}
        <section aria-labelledby="blog-stats" class="mb-8">
            <h2 id="blog-stats" class="sr-only">Liczniki</h2>
            <ul role="list" class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
                @foreach ($stats as $stat)
                    <li>
                        <a href="{{ $stat['url'] }}" class="block h-full rounded-md bg-gray-50 p-4 hover:bg-brand-light focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark">
                            <span class="block text-3xl font-extrabold leading-none text-ink">{{ $stat['count'] }}</span>
                            <span class="mt-2 block text-sm font-bold text-muted">{{ $stat['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- ── Najbliższe dni ───────────────────────────────────────── --}}
        <section aria-labelledby="blog-calendar" class="mb-8">
            <h2 id="blog-calendar" class="mb-3 text-lg font-bold text-ink">Najbliższe 14 dni</h2>
            <ol role="list" class="grid grid-cols-2 gap-2 sm:grid-cols-7">
                @foreach ($calendar as $day)
                    <li class="rounded-md p-2 text-sm {{ $day['articles']->isNotEmpty() ? 'bg-brand-light' : 'bg-gray-50' }}">
                        <span class="block font-bold text-ink">{{ $day['date']->locale('pl')->isoFormat('dd D.MM') }}</span>
                        @forelse ($day['articles'] as $a)
                            <a href="{{ route('admin.wiem-feer.edit', $a) }}" class="mt-1 block truncate {{ $link }}">{{ $a->title }}</a>
                        @empty
                            <span class="mt-1 block text-muted">brak<span class="sr-only"> zaplanowanych wpisów</span></span>
                        @endforelse
                    </li>
                @endforeach
            </ol>
        </section>

        <div class="grid gap-8 lg:grid-cols-2">
            {{-- ── Do zrobienia ─────────────────────────────────────── --}}
            <section aria-labelledby="blog-todo" class="lg:col-span-2">
                <h2 id="blog-todo" class="mb-3 text-lg font-bold text-ink">Do zrobienia</h2>
                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="rounded-md bg-gray-50 p-4">
                        <h3 class="font-bold text-ink">Komentarze do moderacji</h3>
                        @if ($pendingComments->isEmpty())
                            <p class="mt-2 text-sm text-muted">Brak komentarzy czekających na moderację.</p>
                        @else
                            <ul role="list" class="mt-2 space-y-3 text-sm">
                                @foreach ($pendingComments as $comment)
                                    <li>
                                        <p class="text-ink"><span class="font-bold">{{ $comment->author_name }}</span>
                                            @if ($comment->article) pod wpisem „{{ $comment->article->title }}”@endif
                                        </p>
                                        <p class="text-muted">{{ \Illuminate\Support\Str::limit($comment->body, 140) }}</p>
                                    </li>
                                @endforeach
                            </ul>
                            <p class="mt-3 text-sm"><a href="{{ route('admin.komentarze-bloga.index') }}" class="{{ $link }}">Przejdź do moderacji</a></p>
                        @endif
                    </div>

                    <div class="rounded-md bg-gray-50 p-4">
                        <h3 class="font-bold text-ink">Szkice bez zmian od {{ $staleDays }} dni</h3>
                        @if ($staleDrafts->isEmpty())
                            <p class="mt-2 text-sm text-muted">Brak zaległych szkiców.</p>
                        @else
                            <ul role="list" class="mt-2 space-y-2 text-sm">
                                @foreach ($staleDrafts as $a)
                                    <li><a href="{{ route('admin.wiem-feer.edit', $a) }}" class="{{ $link }}">{{ $a->title }}</a>
                                        <span class="text-muted">— ostatnia zmiana {{ $date($a->updated_at) }}</span></li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div class="rounded-md bg-gray-50 p-4">
                        <h3 class="font-bold text-ink">SEO i dostępność opublikowanych wpisów</h3>
                        @if ($checklist->isEmpty())
                            <p class="mt-2 text-sm text-muted">Wszystkie opublikowane wpisy mają komplet opisów.</p>
                        @else
                            <p class="mt-1 text-sm text-muted">Wpisy z brakami: {{ $checklist->count() }}</p>
                            <ul role="list" class="mt-2 space-y-2 text-sm">
                                @foreach ($checklist->take(10) as $row)
                                    <li><a href="{{ route('admin.wiem-feer.edit', $row['article']) }}" class="{{ $link }}">{{ $row['article']->title }}</a>
                                        <span class="text-muted">— {{ implode(', ', $row['issues']) }}</span></li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div class="rounded-md bg-gray-50 p-4">
                        <h3 class="font-bold text-ink">Niepełne profile autorów</h3>
                        @if ($incompleteAuthors->isEmpty())
                            <p class="mt-2 text-sm text-muted">Profile aktywnych autorów są kompletne.</p>
                        @else
                            <ul role="list" class="mt-2 space-y-2 text-sm">
                                @foreach ($incompleteAuthors as $row)
                                    <li><a href="{{ route('admin.autorzy-bloga.edit', $row['author']) }}" class="{{ $link }}">{{ $row['author']->name }}</a>
                                        <span class="text-muted">— brak: {{ implode(', ', $row['missing']) }}</span></li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </section>

            {{-- ── Zaplanowane ──────────────────────────────────────── --}}
            <section aria-labelledby="blog-scheduled">
                <h2 id="blog-scheduled" class="mb-3 text-lg font-bold text-ink">Zaplanowane</h2>
                @if ($scheduled->isEmpty())
                    <p class="rounded-md bg-gray-50 p-4 text-sm text-muted">Brak zaplanowanych publikacji.</p>
                @else
                    <ul role="list" class="divide-y divide-gray-200 rounded-md bg-gray-50 text-sm">
                        @foreach ($scheduled as $a)
                            <li class="p-3"><a href="{{ route('admin.wiem-feer.edit', $a) }}" class="{{ $link }}">{{ $a->title }}</a>
                                <span class="block text-muted">publikacja: <time datetime="{{ $a->published_at->toIso8601String() }}">{{ $date($a->published_at) }}</time></span></li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- ── Ostatnio edytowane ───────────────────────────────── --}}
            <section aria-labelledby="blog-recent">
                <h2 id="blog-recent" class="mb-3 text-lg font-bold text-ink">Ostatnio edytowane</h2>
                @if ($recent->isEmpty())
                    <p class="rounded-md bg-gray-50 p-4 text-sm text-muted">Nie ma jeszcze żadnych wpisów.</p>
                @else
                    <ul role="list" class="divide-y divide-gray-200 rounded-md bg-gray-50 text-sm">
                        @foreach ($recent as $a)
                            <li class="p-3"><a href="{{ route('admin.wiem-feer.edit', $a) }}" class="{{ $link }}">{{ $a->title }}</a>
                                <span class="block text-muted">{{ $a->statusLabel() }} · zmieniono <time datetime="{{ $a->updated_at?->toIso8601String() }}">{{ $date($a->updated_at) }}</time></span></li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    @endif
@endsection
