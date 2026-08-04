@extends('admin.layout')

@section('title', 'Raport: brakujące alt-teksty')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-ink">Brakujące opisy alternatywne zdjęć</h1>
            <p class="mt-1 text-sm text-muted">Treści ze zdjęciem, ale bez wypełnionego pola alt — wymagane przez WCAG 1.1.1.</p>
        </div>
        <span class="rounded-full bg-red-100 px-3 py-1 text-sm font-bold text-red-700">
            {{ $newsNoAlt->count() + $pagesNoAlt->count() }} do uzupełnienia
        </span>
    </div>

    @if ($newsNoAlt->isEmpty() && $pagesNoAlt->isEmpty())
        <div class="rounded-xl border border-green-200 bg-green-50 px-6 py-10 text-center">
            <i class="fa-solid fa-circle-check mb-3 text-3xl text-green-500" aria-hidden="true"></i>
            <p class="font-bold text-green-700">Wszystkie zdjęcia mają opisy alternatywne.</p>
        </div>
    @endif

    @if ($newsNoAlt->isNotEmpty())
        <section class="mb-8">
            <h2 class="mb-3 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-muted">
                <i class="fa-solid fa-newspaper" aria-hidden="true"></i>
                Aktualności ({{ $newsNoAlt->count() }})
            </h2>
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                        <tr>
                            <th class="w-16 px-4 py-3">Foto</th>
                            <th class="px-4 py-3">Tytuł</th>
                            <th class="px-4 py-3">Data</th>
                            <th class="px-4 py-3 text-right">Akcja</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($newsNoAlt as $item)
                            <tr>
                                <td class="px-4 py-2">
                                    <img src="{{ $item->image_url }}" alt="" class="h-10 w-14 rounded object-cover" loading="lazy">
                                </td>
                                <td class="px-4 py-3 font-medium">{{ $item->title }}</td>
                                <td class="px-4 py-3 text-muted">{{ $item->published_at?->format('d.m.Y') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.newsy.edit', $item) }}#image_alt"
                                        class="text-sm font-bold text-brand hover:text-brand-dark">
                                        Uzupełnij alt →
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($pagesNoAlt->isNotEmpty())
        <section>
            <h2 class="mb-3 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-muted">
                <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                Strony ({{ $pagesNoAlt->count() }})
            </h2>
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                        <tr>
                            <th class="w-16 px-4 py-3">Foto</th>
                            <th class="px-4 py-3">Tytuł</th>
                            <th class="px-4 py-3">Slug</th>
                            <th class="px-4 py-3 text-right">Akcja</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($pagesNoAlt as $page)
                            <tr>
                                <td class="px-4 py-2">
                                    <img src="{{ $page->content_image }}" alt="" class="h-10 w-14 rounded object-cover" loading="lazy">
                                </td>
                                <td class="px-4 py-3 font-medium">{{ $page->title }}</td>
                                <td class="px-4 py-3 text-muted">/{{ $page->slug }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.podstrony.edit', $page) }}"
                                        class="text-sm font-bold text-brand hover:text-brand-dark">
                                        Uzupełnij alt →
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
