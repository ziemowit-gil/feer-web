@extends('admin.layout')

@section('title', 'Portale')

@section('content')
    @php
        $btn = 'inline-flex items-center gap-2 rounded px-3 py-1.5 text-sm font-bold text-brand-dark hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark';
    @endphp

    <p class="mb-4 max-w-3xl text-sm text-ink">Portal to osobna strona jednej dziedziny pracy fundacji (np. szkolenia, edukacja) z leadem, wstępem, menu, szybkimi akcjami, aktualnościami i kontaktem. Każdy portal powstaje z kategorii działań i ma krótki adres <span class="font-mono">/portal/{adres}</span>.</p>

    @unless ($enabled)
        <p class="mb-4 rounded bg-gray-50 px-4 py-3 text-sm text-ink" role="note"><strong>Portale są wyłączone.</strong> Nie są widoczne dla odwiedzających — włączysz je w <a href="{{ route('admin.ustawienia.edit') }}" class="font-bold text-brand-dark underline underline-offset-2 hover:text-ink">ustawieniach witryny</a> (Działania → Portale kategorii).</p>
    @endunless

    <div class="overflow-hidden rounded-md bg-white">
        <table class="w-full text-left text-sm">
            <caption class="sr-only">Portale</caption>
            <thead class="bg-gray-100 text-xs font-bold uppercase text-muted">
                <tr>
                    <th scope="col" class="px-4 py-3">Portal</th>
                    <th scope="col" class="px-4 py-3">Adres</th>
                    <th scope="col" class="px-4 py-3">Kategoria</th>
                    <th scope="col" class="px-4 py-3">Pozycje</th>
                    <th scope="col" class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($portals as $portal)
                    <tr>
                        <td class="px-4 py-3 font-bold text-ink">{{ $portal->portal_title ?: $portal->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-ink">/portal/{{ $portal->portal_slug }}</td>
                        <td class="px-4 py-3 text-ink">{{ $portal->name }}</td>
                        <td class="px-4 py-3 text-ink">{{ $portal->projects_count }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1">
                                <a href="{{ route('admin.portale.edit', $portal) }}" class="{{ $btn }}"><i class="fa-solid fa-pen" aria-hidden="true"></i> Edytuj<span class="sr-only"> portal {{ $portal->portal_title ?: $portal->name }}</span></a>
                                @if ($enabled)
                                    <a href="{{ route('portal.show', $portal->portal_slug) }}" target="_blank" rel="noopener" class="{{ $btn }}"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Zobacz<span class="sr-only"> portal {{ $portal->portal_title ?: $portal->name }} (nowa karta)</span></a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-ink">Nie ma jeszcze portali — utwórz pierwszy z kategorii poniżej.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($withoutPortal->isNotEmpty())
        <section aria-labelledby="portal-new" class="mt-8">
            <h2 id="portal-new" class="text-base font-bold text-ink">Utwórz portal z kategorii</h2>
            <ul role="list" class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($withoutPortal as $category)
                    <li class="flex items-center justify-between gap-3 rounded-md bg-white px-4 py-3">
                        <span class="min-w-0 text-sm text-ink"><span class="font-bold">{{ $category->name }}</span> <span class="text-muted">· {{ $category->projects_count }}</span></span>
                        <a href="{{ route('admin.portale.edit', $category) }}" class="{{ $btn }} flex-none"><i class="fa-solid fa-plus" aria-hidden="true"></i> Utwórz portal<span class="sr-only">: {{ $category->name }}</span></a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection
