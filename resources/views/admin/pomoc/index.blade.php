@extends('admin.layout')

@section('title', 'Pomoc')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-bold text-ink">Pomoc</h1>
            <p class="text-sm text-muted">Zgłoszenia do Helpdesku Centralnego, prowadzone bezpośrednio z tego panelu.</p>
        </div>
        <a href="{{ route('admin.pomoc.create') }}" class="rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Nowe zgłoszenie
        </a>
    </div>

    @unless ($ticketsEnabled)
        <p class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-800">
            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
            Integracja z Helpdeskiem jest wyłączona — nowe zgłoszenia zapiszą się lokalnie i wyślą automatycznie, gdy integracja będzie dostępna.
        </p>
    @endunless

    @if (session('success'))
        <p class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800">{{ session('success') }}</p>
    @endif

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Temat</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Zgłosił(-a)</th>
                    <th class="px-4 py-3">Utworzono</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($tickets as $ticket)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $ticket->subject }}</div>
                            @unless ($ticket->submitted())
                                <div class="mt-0.5 text-xs text-amber-700">
                                    <i class="fa-solid fa-clock" aria-hidden="true"></i> Niewysłane do Helpdesku
                                </div>
                            @endunless
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-bold
                                {{ in_array($ticket->status, ['closed', 'resolved']) ? 'bg-gray-100 text-gray-600' : 'bg-blue-100 text-blue-700' }}">
                                {{ $ticket->statusLabel() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $ticket->submittedBy?->name ?? '—' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-muted">{{ $ticket->created_at->format('d.m.Y H:i') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.pomoc.show', $ticket) }}" class="text-brand hover:text-brand-dark" title="Otwórz">
                                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i> Otwórz
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-muted">
                            Brak zgłoszeń. <a href="{{ route('admin.pomoc.create') }}" class="text-brand underline">Wyślij pierwsze</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $tickets->links() }}
    </div>
@endsection
