@extends('admin.layout')

@section('title', 'Sprawozdania roczne')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted">Roczne sprawozdania merytoryczne i finansowe (strona <a href="{{ route('reports.index') }}" target="_blank" rel="noopener" class="text-brand-dark underline">/sprawozdania</a>).</p>
        <a href="{{ route('admin.sprawozdania.create') }}" class="rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj rok
        </a>
    </div>

    <div class="overflow-x-auto rounded-md bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-100 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Rok</th>
                    <th class="px-4 py-3">Merytoryczne</th>
                    <th class="px-4 py-3">Finansowe</th>
                    <th class="px-4 py-3">Dodatkowe pliki</th>
                    <th class="px-4 py-3">Widoczność</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($reports as $report)
                    <tr>
                        <th scope="row" class="px-4 py-3 text-base font-bold text-ink">{{ $report->year }}</th>
                        @foreach (\App\Models\AnnualReport::TYPES as $type => $label)
                            <td class="px-4 py-3">
                                @if ($report->fileUrlFor($type))
                                    <span class="inline-flex items-center gap-1.5 text-xs font-bold text-ink">
                                        <i class="fa-solid fa-file-pdf" aria-hidden="true"></i> Plik PDF
                                    </span>
                                @else
                                    <span class="text-xs font-bold text-muted">{{ \App\Models\AnnualReport::STATUSES[$report->statusFor($type)] ?? '—' }}</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="px-4 py-3 text-muted">{{ $report->additionalFiles()->count() ?: '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($report->is_published)
                                <span class="text-xs font-bold text-ink"><i class="fa-solid fa-circle-check mr-1 text-brand-dark" aria-hidden="true"></i>Widoczny</span>
                            @else
                                <span class="text-xs font-bold text-muted">Ukryty</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('admin.sprawozdania.edit', $report) }}" class="text-brand-dark hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark rounded" title="Edytuj rok {{ $report->year }}">
                                    <i class="fa-solid fa-pen" aria-hidden="true"></i><span class="sr-only">Edytuj {{ $report->year }}</span>
                                </a>
                                <form method="POST" action="{{ route('admin.sprawozdania.destroy', $report) }}" onsubmit="return confirm('Usunąć sprawozdania za {{ $report->year }} rok wraz z plikami?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600 rounded" title="Usuń rok {{ $report->year }}">
                                        <i class="fa-solid fa-trash" aria-hidden="true"></i><span class="sr-only">Usuń {{ $report->year }}</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-muted">Brak sprawozdań. <a href="{{ route('admin.sprawozdania.create') }}" class="text-brand-dark underline">Dodaj pierwszy rok</a>.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
