@extends('admin.layout')

@section('title', 'Sprawozdania roczne')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted">Roczne sprawozdania merytoryczne i finansowe (strona <a href="{{ route('reports.index') }}" target="_blank" rel="noopener" class="text-brand underline">/sprawozdania</a>).</p>
        <a href="{{ route('admin.sprawozdania.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj rok
        </a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
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
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">
                                        <i class="fa-solid fa-file-pdf" aria-hidden="true"></i> Plik PDF
                                    </span>
                                @else
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-600">{{ \App\Models\AnnualReport::STATUSES[$report->statusFor($type)] ?? '—' }}</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="px-4 py-3 text-muted">{{ $report->additionalFiles()->count() ?: '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($report->is_published)
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-bold text-green-700">Widoczny</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-600">Ukryty</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('admin.sprawozdania.edit', $report) }}" class="text-brand hover:text-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand rounded" title="Edytuj rok {{ $report->year }}">
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
                        <td colspan="6" class="px-4 py-8 text-center text-muted">Brak sprawozdań. <a href="{{ route('admin.sprawozdania.create') }}" class="text-brand underline">Dodaj pierwszy rok</a>.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
