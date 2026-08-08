<p>Dzień dobry{{ $subscriber->name ? ', ' . $subscriber->name : '' }},</p>

<p>Dziękujemy za zapisanie się na powiadomienia z&nbsp;serwisu <strong>{{ config('app.name') }}</strong>.</p>

<p>Aby potwierdzić subskrypcję, kliknij poniższy przycisk. Link jest ważny przez 48 godzin.</p>

<p style="margin: 24px 0;">
    <a href="{{ route('subskrypcje.confirm', $subscriber->token) }}"
       style="background:#005fa3;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;">
        Potwierdź subskrypcję
    </a>
</p>

<p>Jeśli nie składałeś/łaś tego zapisu, możesz zignorować tę wiadomość.</p>

<p style="font-size:13px;color:#666;">
    Link bezpośredni: {{ route('subskrypcje.confirm', $subscriber->token) }}
</p>
