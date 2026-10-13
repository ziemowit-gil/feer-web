@extends('admin.layout')

@section('title', 'Sklep — kody rabatowe')

@section('content')
    <div class="mb-4 flex items-center justify-between gap-3">
        <a href="{{ route('admin.sklep.orders.index') }}" class="text-sm text-muted hover:text-brand-dark">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Wróć do zamówień
        </a>
        <a href="{{ route('admin.sklep.kody-rabatowe.create') }}"
            class="rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
            <i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj kod
        </a>
    </div>

    @if (session('status'))
        <p class="mb-4 rounded-md bg-brand-light px-4 py-2 text-sm font-bold text-ink">{{ session('status') }}</p>
    @endif

    <div class="overflow-hidden rounded-md bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-100 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Kod</th>
                    <th class="px-4 py-3">Rabat</th>
                    <th class="px-4 py-3">Ważność</th>
                    <th class="px-4 py-3">Użycia</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($codes as $code)
                    <tr>
                        <td class="px-4 py-3 font-mono font-bold">{{ $code->code }}</td>
                        <td class="px-4 py-3 text-muted">
                            @if ($code->type === 'percent') {{ $code->value }}% @else {{ number_format($code->value / 100, 2, ',', ' ') }} zł @endif
                        </td>
                        <td class="px-4 py-3 text-muted">
                            @if ($code->valid_from || $code->valid_until)
                                {{ $code->valid_from?->format('d.m.Y') ?? '…' }} – {{ $code->valid_until?->format('d.m.Y') ?? '…' }}
                            @else
                                bez limitu
                            @endif
                        </td>
                        <td class="px-4 py-3 text-muted">{{ $code->used_count }}{{ $code->max_uses ? ' / ' . $code->max_uses : '' }}</td>
                        <td class="px-4 py-3">
                            @if ($code->is_active)
                                <span class="text-xs font-bold text-ink"><i class="fa-solid fa-circle-check mr-1 text-brand-dark" aria-hidden="true"></i>Aktywny</span>
                            @else
                                <span class="text-xs font-bold text-muted">Nieaktywny</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('admin.sklep.kody-rabatowe.edit', $code) }}" class="text-muted hover:text-brand-dark" title="Edytuj"><i class="fa-solid fa-pen" aria-hidden="true"></i></a>
                                <form method="POST" action="{{ route('admin.sklep.kody-rabatowe.destroy', $code) }}" onsubmit="return confirm('Usunąć kod &quot;{{ $code->code }}&quot;?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-muted hover:text-red-600" title="Usuń" aria-label="Usuń"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                        @include('admin.partials.empty-state', ['icon' => 'fa-ticket', 'title' => 'Nie ma jeszcze kodów rabatowych', 'route' => 'admin.sklep.kody-rabatowe.create', 'action' => 'Dodaj kod', 'colspan' => 6])
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
