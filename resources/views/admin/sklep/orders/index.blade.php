@extends('admin.layout')

@section('title', 'Sklep — zamówienia')

@section('content')
    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.sklep.kody-rabatowe.index') }}"
            class="inline-flex items-center gap-2 rounded border-2 border-brand-dark px-4 py-2 text-sm font-bold text-brand-dark hover:bg-brand-light">
            <i class="fa-solid fa-tags" aria-hidden="true"></i> Kody rabatowe
        </a>
    </div>

    <form method="GET" action="{{ route('admin.sklep.orders.index') }}" class="mb-4 flex flex-wrap items-end gap-3 p-3 rounded-md bg-white">
        <div class="min-w-48 flex-1">
            <label for="filter-q" class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Szukaj</label>
            <input type="text" id="filter-q" name="q" value="{{ $q }}" placeholder="E-mail kupującego…"
                class="w-full rounded border-gray-500 py-1.5 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
        </div>
        <div>
            <label for="filter-status" class="mb-1 block text-xs font-bold uppercase tracking-wide text-muted">Status</label>
            <select id="filter-status" name="status" onchange="this.form.submit()"
                class="rounded border-gray-500 py-1.5 text-sm focus:border-brand-dark focus-visible:ring-2 focus-visible:ring-brand-dark">
                <option value="">Wszystkie</option>
                @foreach (\App\Models\SklepOrder::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="rounded bg-brand-dark px-3 py-1.5 text-sm font-bold text-white hover:bg-ink">Filtruj</button>
        @if ($q || $status)
            <a href="{{ route('admin.sklep.orders.index') }}" class="rounded px-2 py-1.5 text-sm text-muted hover:text-brand-dark">Wyczyść</a>
        @endif
        <span class="ml-auto text-sm text-muted">{{ $orders->total() }} zamówień</span>
    </form>

    <div class="overflow-hidden rounded-md bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-100 text-xs font-bold uppercase text-muted">
                <tr>
                    <th class="px-4 py-3">Data</th>
                    <th class="px-4 py-3">Materiał</th>
                    <th class="px-4 py-3">Kupujący</th>
                    <th class="px-4 py-3">Kwota</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($orders as $order)
                    <tr>
                        <td class="px-4 py-3 text-muted">{{ $order->created_at->format('d.m.Y H:i') }}</td>
                        <td class="px-4 py-3 font-medium">{{ $order->items->pluck('title')->implode(', ') ?: '—' }}</td>
                        <td class="px-4 py-3">
                            {{ $order->buyer_email }}
                            @if ($order->buyer_name)
                                <span class="block text-xs text-muted">{{ $order->buyer_name }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-muted">{{ number_format($order->amount_grosze / 100, 2, ',', ' ') }} {{ $order->currency }}</td>
                        <td class="px-4 py-3">
                            @php
                                $badge = match ($order->status) {
                                    'paid' => 'text-ink',
                                    'failed' => 'text-ink',
                                    'refunded' => 'text-ink',
                                    default => 'text-muted',
                                };
                            @endphp
                            <span class="text-xs font-bold {{ $badge }}">{{ \App\Models\SklepOrder::STATUSES[$order->status] ?? $order->status }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.sklep.orders.show', $order) }}" class="text-muted hover:text-brand-dark" title="Szczegóły"><i class="fa-solid fa-eye" aria-hidden="true"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-muted">Brak zamówień.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $orders->links() }}</div>
@endsection
