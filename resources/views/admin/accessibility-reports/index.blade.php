@extends('admin.layout')

@section('title', 'Zgłoszenia barier dostępności')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted">Zgłoszenia z formularza na stronie <a href="{{ route('accessibility.show') }}" target="_blank" rel="noopener" class="text-brand underline">deklaracji dostępności</a>: <span class="font-bold text-ink">{{ $total }}</span></p>
        @if ($total > 0)
            <a href="{{ route('admin.zgloszenia-barier.export') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
                <i class="fa-solid fa-file-csv"></i> Eksport CSV
            </a>
        @endif
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Imię i nazwisko</th>
                    <th class="px-4 py-3">E-mail</th>
                    <th class="px-4 py-3">Strona / element</th>
                    <th class="px-4 py-3">Opis bariery</th>
                    <th class="px-4 py-3">Data zgłoszenia</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($reports as $report)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $report->name ?: '—' }}</td>
                        <td class="px-4 py-3"><a href="mailto:{{ $report->email }}" class="text-brand hover:text-brand-dark">{{ $report->email }}</a></td>
                        <td class="max-w-[12rem] px-4 py-3 text-muted">
                            @if ($report->page_url)
                                <span class="break-words">{{ \Illuminate\Support\Str::limit($report->page_url, 80) }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="max-w-xs px-4 py-3 text-muted">{{ \Illuminate\Support\Str::limit($report->message, 140) }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-muted">{{ $report->created_at->format('d.m.Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end">
                                <form method="POST" action="{{ route('admin.zgloszenia-barier.destroy', $report) }}" onsubmit="return confirm('Usunąć to zgłoszenie?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-muted">Brak zgłoszeń.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($reports->hasPages())
        <div class="mt-4">{{ $reports->links() }}</div>
    @endif
@endsection
