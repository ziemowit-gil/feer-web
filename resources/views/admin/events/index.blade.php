@extends('admin.layout')

@section('title', 'Szkolenia i wydarzenia')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted">Szkolenia i wydarzenia (strona <a href="{{ route('events.index') }}" target="_blank" rel="noopener" class="text-brand underline">/wydarzenia</a>).</p>
        <a href="{{ route('admin.wydarzenia.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
            <i class="fa-solid fa-plus"></i> Dodaj wydarzenie
        </a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Tytuł</th>
                    <th class="px-4 py-3">Rodzaj</th>
                    <th class="px-4 py-3">Termin</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($events as $event)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $event->title }}</td>
                        <td class="px-4 py-3 text-muted">
                            <i class="fa-solid {{ $event->typeIcon() }} mr-1 text-gray-400" aria-hidden="true"></i>{{ $event->typeLabel() }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-muted">{{ $event->shortDateLabel() }}</td>
                        <td class="px-4 py-3">
                            @if (! $event->is_published)
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-600">Szkic</span>
                            @elseif ($event->isPast())
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700">Zakończone</span>
                            @else
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">Nadchodzące</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3">
                                @if ($siteSettings->isModuleEnabled('news'))
                                    <form method="POST" action="{{ route('admin.wydarzenia.na-aktualnosc', $event) }}" onsubmit="return confirm('Utworzyć aktualność na podstawie „{{ $event->title }}”? Powstanie szkic do przejrzenia.');">
                                        @csrf
                                        <button type="submit" class="text-muted hover:text-brand" title="Utwórz aktualność na podstawie wydarzenia"><i class="fa-solid fa-newspaper"></i></button>
                                    </form>
                                @endif
                                @if ($siteSettings->isModuleEnabled('landing'))
                                    <form method="POST" action="{{ route('admin.wydarzenia.na-landing', $event) }}" onsubmit="return confirm('Utworzyć landing page na podstawie „{{ $event->title }}”? Powstanie szkic do przejrzenia.');">
                                        @csrf
                                        <button type="submit" class="text-muted hover:text-brand" title="Utwórz landing page na podstawie wydarzenia"><i class="fa-solid fa-bullhorn"></i></button>
                                    </form>
                                @endif
                                <a href="{{ route('admin.wydarzenia.edit', $event) }}" class="text-brand hover:text-brand-dark" title="Edytuj"><i class="fa-solid fa-pen"></i></a>
                                <form method="POST" action="{{ route('admin.wydarzenia.destroy', $event) }}" onsubmit="return confirm('Usunąć wydarzenie &quot;{{ $event->title }}&quot;?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-muted">Brak wydarzeń. <a href="{{ route('admin.wydarzenia.create') }}" class="text-brand underline">Dodaj pierwsze</a>.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
