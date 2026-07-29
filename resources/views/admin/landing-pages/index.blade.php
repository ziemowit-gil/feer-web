@extends('admin.layout')

@section('title', 'Landing pages (webinary)')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted">Strony docelowe webinarów pod adresem <code class="rounded bg-gray-100 px-1">/lp/&#123;slug&#125;</code>.</p>
        <a href="{{ route('admin.lp.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Nowy landing page
        </a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Tytuł</th>
                    <th class="px-4 py-3">Adres</th>
                    <th class="px-4 py-3">Zapisy</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($pages as $page)
                    <tr>
                        <td class="px-4 py-3 font-medium text-ink">{{ $page->title }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('lp.show', $page->slug) }}" target="_blank" rel="noopener" class="text-brand hover:text-brand-dark">/lp/{{ $page->slug }}</a>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.lp.registrations', $page) }}" class="font-bold text-brand hover:text-brand-dark">
                                {{ $page->registrations_count }} <span class="font-normal text-muted">zapisów</span>
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            @if ($page->is_published)
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">Opublikowany</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-600">Szkic</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('admin.lp.edit', $page) }}" class="rounded text-brand hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand" title="Edytuj"><i class="fa-solid fa-pen" aria-hidden="true"></i><span class="sr-only">Edytuj {{ $page->title }}</span></a>
                                <form method="POST" action="{{ route('admin.lp.destroy', $page) }}" onsubmit="return confirm('Usunąć landing page „{{ $page->title }}” wraz z zapisami?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded text-muted hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600" title="Usuń"><i class="fa-solid fa-trash" aria-hidden="true"></i><span class="sr-only">Usuń {{ $page->title }}</span></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-muted">Brak landing page’y. <a href="{{ route('admin.lp.create') }}" class="text-brand underline">Utwórz pierwszy</a>.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
