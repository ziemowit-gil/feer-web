@extends('admin.layout')

@section('title', 'Wolontariat — ogłoszenia')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted">Ogłoszenia o wolontariacie (strona <a href="{{ route('volunteer.index') }}" target="_blank" rel="noopener" class="text-brand underline">/wolontariat</a>).</p>
        <a href="{{ route('admin.wolontariat.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
            <i class="fa-solid fa-plus"></i> Dodaj ogłoszenie
        </a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Tytuł</th>
                    <th class="px-4 py-3">Tryb</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Termin zgłoszeń</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($ads as $ad)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $ad->title }}</td>
                        <td class="px-4 py-3 text-muted">{{ $ad->modeLabel() }}</td>
                        <td class="px-4 py-3">
                            @if (! $ad->is_published)
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-600">Szkic</span>
                            @elseif ($ad->isClosed())
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700">Zakończone</span>
                            @else
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">Aktywne</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-muted">{{ $ad->closes_at ? $ad->closes_at->format('d.m.Y') : '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('admin.wolontariat.edit', $ad) }}" class="text-brand hover:text-brand-dark" title="Edytuj"><i class="fa-solid fa-pen"></i></a>
                                <form method="POST" action="{{ route('admin.wolontariat.destroy', $ad) }}" onsubmit="return confirm('Usunąć ogłoszenie &quot;{{ $ad->title }}&quot;?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-muted">Brak ogłoszeń. <a href="{{ route('admin.wolontariat.create') }}" class="text-brand underline">Dodaj pierwsze</a>.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
