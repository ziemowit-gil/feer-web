@extends('admin.layout')

@section('title', 'Zamówienie #' . $order->id)

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.sklep.orders.index') }}" class="text-sm text-muted hover:text-brand-dark">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Wróć do listy
        </a>
    </div>

    @if (session('status'))
        <p class="mb-4 rounded-md bg-brand-light px-4 py-2 text-sm font-bold text-ink">{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="mb-4 rounded-md bg-red-50 px-4 py-2 text-sm font-bold text-red-700">{{ session('error') }}</p>
    @endif

    <div class="max-w-2xl space-y-5 p-6 rounded-md bg-white">
        <div>
            <p class="mb-1 text-sm font-bold text-muted">Pozycje zamówienia</p>
            <ul class="divide-y divide-gray-100 rounded-md bg-gray-50">
                @foreach ($order->items as $item)
                    <li class="flex justify-between px-3 py-2 text-sm">
                        <span>{{ $item->title }}</span>
                        <span class="text-muted">{{ number_format($item->unit_price_grosze / 100, 2, ',', ' ') }} {{ $order->currency }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <dl class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="font-bold text-muted">Status</dt>
                <dd>{{ \App\Models\SklepOrder::STATUSES[$order->status] ?? $order->status }}</dd>
            </div>
            <div>
                <dt class="font-bold text-muted">Kupujący</dt>
                <dd>{{ $order->buyer_email }} @if ($order->buyer_name) ({{ $order->buyer_name }}) @endif</dd>
            </div>
            <div>
                <dt class="font-bold text-muted">Konto</dt>
                <dd>{{ $order->user?->email ?? 'zakup jako gość' }}</dd>
            </div>
            <div>
                <dt class="font-bold text-muted">Suma częściowa</dt>
                <dd>{{ number_format($order->subtotal_grosze / 100, 2, ',', ' ') }} {{ $order->currency }}</dd>
            </div>
            @if ($order->discountCode)
                <div>
                    <dt class="font-bold text-muted">Kod rabatowy</dt>
                    <dd>{{ $order->discountCode->code }} (-{{ number_format($order->discount_amount_grosze / 100, 2, ',', ' ') }} {{ $order->currency }})</dd>
                </div>
            @endif
            <div>
                <dt class="font-bold text-muted">Kwota do zapłaty</dt>
                <dd>{{ number_format($order->amount_grosze / 100, 2, ',', ' ') }} {{ $order->currency }}</dd>
            </div>
            <div>
                <dt class="font-bold text-muted">Data zamówienia</dt>
                <dd>{{ $order->created_at->format('d.m.Y H:i') }}</dd>
            </div>
            <div>
                <dt class="font-bold text-muted">P24 orderId</dt>
                <dd>{{ $order->p24_order_id ?? '—' }}</dd>
            </div>
            <div>
                <dt class="font-bold text-muted">Dostęp wysłany</dt>
                <dd>{{ $order->access_delivered_at?->format('d.m.Y H:i') ?? '—' }}</dd>
            </div>
        </dl>

        @if ($order->isPaid())
            <form method="POST" action="{{ route('admin.sklep.orders.resend', $order) }}">
                @csrf
                <button type="submit" class="rounded-md px-4 py-2 text-sm font-bold text-muted hover:text-brand-dark bg-gray-100 hover:bg-brand-light">
                    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Wyślij ponownie e-mail z dostępem
                </button>
            </form>
        @endif

        <div>
            <p class="mb-1 text-sm font-bold text-muted">Surowe dane (payload)</p>
            <pre class="overflow-x-auto rounded bg-gray-50 p-3 text-xs">{{ json_encode($order->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    </div>
@endsection
