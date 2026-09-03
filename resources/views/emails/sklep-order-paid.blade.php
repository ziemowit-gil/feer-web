<p>Dzień dobry{{ $order->buyer_name ? ', ' . $order->buyer_name : '' }},</p>

<p>Dziękujemy za zakup materiału <strong>{{ $order->material?->title }}</strong> w sklepie {{ config('app.name') }}.</p>

<p>Płatność została zaksięgowana — dostęp możesz odebrać poniższym linkiem:</p>

<p style="margin: 24px 0;">
    <a href="{{ route('sklep.download', $order->access_token) }}"
       style="background:#005fa3;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;">
        Odbierz materiał
    </a>
</p>

<p style="font-size:13px;color:#666;">
    Link bezpośredni: {{ route('sklep.download', $order->access_token) }}
</p>

<p>Zachowaj tę wiadomość — link pozostaje aktywny i będzie działał również w przyszłości.</p>
