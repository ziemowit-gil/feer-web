@extends('admin.layout')

@section('title', 'Zaproszenia do strefy')

@section('content')
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h1 class="text-lg font-bold text-ink">Zaproszenia do strefy wewnętrznej</h1>
            <p class="text-sm text-muted">Indywidualne e-maile spoza dozwolonej domeny lub zalogowanie bez konta MS365.</p>
        </div>
        <a href="{{ route('admin.zaproszenia-strefy.create') }}"
            class="rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Nowe zaproszenie
        </a>
    </div>

    @if (session('success'))
        <div role="alert" class="mb-4 rounded-md bg-brand-light px-4 py-3 text-sm text-ink">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-hidden rounded-md bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-100 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">E-mail</th>
                    <th class="px-4 py-3">Notatka</th>
                    <th class="px-4 py-3">Wygasa</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Zaprosił/a</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($invitations as $inv)
                    @php
                        $used    = $inv->isUsed();
                        $expired = ! $used && $inv->isExpired();
                        $valid   = $inv->isValid();
                        $link    = route('member.zaproszenie.show', $inv->token);
                    @endphp
                    <tr class="{{ $used || $expired ? 'opacity-60' : '' }}">
                        <td class="px-4 py-3 font-medium text-ink">{{ $inv->email }}</td>
                        <td class="max-w-xs truncate px-4 py-3 text-muted">{{ $inv->note ?: '—' }}</td>
                        <td class="px-4 py-3 text-muted">
                            @if ($inv->expires_at)
                                {{ $inv->expires_at->locale('pl')->isoFormat('D MMM YYYY') }}
                            @else
                                bezterminowe
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($used)
                                <span class="text-xs font-bold text-muted">Użyte</span>
                            @elseif ($expired)
                                <span class="text-xs font-bold text-ink"><i class="fa-solid fa-circle-xmark mr-1 text-red-700" aria-hidden="true"></i>Wygasłe</span>
                            @else
                                <span class="text-xs font-bold text-ink"><i class="fa-solid fa-circle-check mr-1 text-brand-dark" aria-hidden="true"></i>Aktywne</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $inv->invitedBy->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3">
                                @if ($valid)
                                    <button type="button"
                                        onclick="navigator.clipboard.writeText('{{ $link }}').then(() => this.textContent = 'Skopiowano!')"
                                        title="Kopiuj link"
                                        class="text-xs font-medium text-brand-dark hover:text-ink">
                                        Kopiuj link
                                    </button>
                                @endif
                                <form method="POST" action="{{ route('admin.zaproszenia-strefy.destroy', $inv) }}"
                                    onsubmit="return confirm('Usunąć zaproszenie dla {{ $inv->email }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń" aria-label="Usuń zaproszenie">
                                        <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-muted">Brak zaproszeń.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($invitations->hasPages())
        <div class="mt-4">{{ $invitations->links() }}</div>
    @endif
@endsection
