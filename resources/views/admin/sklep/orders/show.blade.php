@extends('admin.layout')

@section('title', 'Zamówienie #' . $order->id)

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.sklep.orders.index') }}" class="text-sm text-muted hover:text-brand">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Wróć do listy
        </a>
    </div>

    @if (session('status'))
        <p class="mb-4 rounded-lg bg-green-50 px-4 py-2 text-sm font-bold text-green-700">{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="mb-4 rounded-lg bg-red-50 px-4 py-2 text-sm font-bold text-red-700">{{ session('error') }}</p>
    @endif

    <div class="max-w-2xl space-y-5 rounded-lg border border-gray-200 bg-white p-6">
        <dl class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="font-bold text-muted">Materiał</dt>
                <dd>{{ $order->material?->title ?? '—' }}</dd>
            </div>
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
                <dt class="font-bold text-muted">Kwota</dt>
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
                <button type="submit" class="rounded border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-muted hover:border-brand hover:text-brand">
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
