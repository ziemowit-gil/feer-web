@extends('admin.layout')
@section('title', 'Ustawienia newslettera')
@section('content')
    @include('newsletter::admin.partials.flash')
    @php $s = $settings; $inp = 'w-full rounded border-gray-300 focus:border-brand focus:ring-brand'; @endphp
    <form method="POST" action="{{ route('admin.newsletter.ustawienia.update') }}" class="space-y-6" x-data="{ tab: 'wysylka', mailer: '{{ old('newsletter_mailer', $s->newsletter_mailer ?? '') }}' }">
        @csrf @method('PUT')
        <nav class="flex flex-wrap gap-1 border-b border-gray-200" aria-label="Sekcje ustawień">
            @foreach (['wysylka' => 'Wysyłka e-mail', 'kanaly' => 'Push i SMS', 'zapisy' => 'Zapisy i RODO', 'integracje' => 'SZO i CRM', 'webhooki' => 'Webhooki', 'osadzenie' => 'Kod zewnętrzny'] as $k => $l)
                <button type="button" @click="tab = '{{ $k }}'" :class="tab === '{{ $k }}' ? 'border-brand text-brand-dark' : 'border-transparent text-gray-700 hover:text-ink'" class="-mb-px border-b-2 px-4 py-2 text-sm font-bold" :aria-current="tab === '{{ $k }}' ? 'page' : null">{{ $l }}</button>
            @endforeach
        </nav>

        <section x-show="tab === 'wysylka'" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6">
            <div class="grid gap-4 sm:grid-cols-3">
                <div><label for="newsletter_from_name" class="mb-1 block text-sm font-bold">Nadawca — nazwa</label><input id="newsletter_from_name" name="newsletter_from_name" value="{{ old('newsletter_from_name', $s->newsletter_from_name) }}" class="{{ $inp }}"></div>
                <div><label for="newsletter_from_address" class="mb-1 block text-sm font-bold">Nadawca — adres</label><input id="newsletter_from_address" name="newsletter_from_address" type="email" value="{{ old('newsletter_from_address', $s->newsletter_from_address) }}" placeholder="{{ config('mail.from.address') }}" class="{{ $inp }}"></div>
                <div><label for="newsletter_reply_to" class="mb-1 block text-sm font-bold">Odpowiedzi do</label><input id="newsletter_reply_to" name="newsletter_reply_to" type="email" value="{{ old('newsletter_reply_to', $s->newsletter_reply_to) }}" class="{{ $inp }}"></div>
            </div>
            <div><label for="newsletter_mailer" class="mb-1 block text-sm font-bold">Dostawca wysyłki</label><select id="newsletter_mailer" name="newsletter_mailer" x-model="mailer" class="{{ $inp }}">@foreach ($mailers as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                <p class="mt-1 text-xs text-muted">Osobny mailer „newsletter” — poczta transakcyjna (Graph) pozostaje bez zmian. Graph ma limit ok. 10 000 adresatów/dzień; dla większych list wybierz SES lub Mailgun (region UE).</p></div>
            <div x-show="mailer === 'ses'" class="grid gap-4 sm:grid-cols-3"><div><label for="newsletter_ses_key" class="mb-1 block text-sm font-bold">SES Access Key</label><input id="newsletter_ses_key" name="newsletter_ses_key" value="" placeholder="{{ $s->newsletter_ses_key ? '•••• zapisany' : '' }}" class="{{ $inp }}" autocomplete="off"></div><div><label for="newsletter_ses_secret" class="mb-1 block text-sm font-bold">SES Secret</label><input id="newsletter_ses_secret" name="newsletter_ses_secret" type="password" value="" placeholder="{{ $s->newsletter_ses_secret ? '•••• zapisany' : '' }}" class="{{ $inp }}" autocomplete="new-password"></div><div><label for="newsletter_ses_region" class="mb-1 block text-sm font-bold">Region</label><input id="newsletter_ses_region" name="newsletter_ses_region" value="{{ old('newsletter_ses_region', $s->newsletter_ses_region ?: 'eu-central-1') }}" class="{{ $inp }}"></div></div>
            <div x-show="mailer === 'mailgun'" class="grid gap-4 sm:grid-cols-3"><div><label for="newsletter_mailgun_domain" class="mb-1 block text-sm font-bold">Domena Mailgun</label><input id="newsletter_mailgun_domain" name="newsletter_mailgun_domain" value="{{ old('newsletter_mailgun_domain', $s->newsletter_mailgun_domain) }}" class="{{ $inp }}"></div><div><label for="newsletter_mailgun_secret" class="mb-1 block text-sm font-bold">API key</label><input id="newsletter_mailgun_secret" name="newsletter_mailgun_secret" type="password" value="" placeholder="{{ $s->newsletter_mailgun_secret ? '•••• zapisany' : '' }}" class="{{ $inp }}" autocomplete="new-password"></div><div><label for="newsletter_mailgun_endpoint" class="mb-1 block text-sm font-bold">Endpoint</label><input id="newsletter_mailgun_endpoint" name="newsletter_mailgun_endpoint" value="{{ old('newsletter_mailgun_endpoint', $s->newsletter_mailgun_endpoint ?: 'api.eu.mailgun.net') }}" class="{{ $inp }}"></div></div>
            <div x-show="mailer === 'postmark'"><label for="newsletter_postmark_token" class="mb-1 block text-sm font-bold">Postmark server token</label><input id="newsletter_postmark_token" name="newsletter_postmark_token" type="password" value="" placeholder="{{ $s->newsletter_postmark_token ? '•••• zapisany' : '' }}" class="{{ $inp }}" autocomplete="new-password"></div>
            <div x-show="mailer === 'smtp'" class="grid gap-4 sm:grid-cols-5"><div class="sm:col-span-2"><label for="newsletter_smtp_host" class="mb-1 block text-sm font-bold">Host</label><input id="newsletter_smtp_host" name="newsletter_smtp_host" value="{{ old('newsletter_smtp_host', $s->newsletter_smtp_host) }}" class="{{ $inp }}"></div><div><label for="newsletter_smtp_port" class="mb-1 block text-sm font-bold">Port</label><input id="newsletter_smtp_port" name="newsletter_smtp_port" type="number" value="{{ old('newsletter_smtp_port', $s->newsletter_smtp_port ?: 587) }}" class="{{ $inp }}"></div><div><label for="newsletter_smtp_username" class="mb-1 block text-sm font-bold">Login</label><input id="newsletter_smtp_username" name="newsletter_smtp_username" value="{{ old('newsletter_smtp_username', $s->newsletter_smtp_username) }}" class="{{ $inp }}" autocomplete="off"></div><div><label for="newsletter_smtp_password" class="mb-1 block text-sm font-bold">Hasło</label><input id="newsletter_smtp_password" name="newsletter_smtp_password" type="password" value="" placeholder="{{ $s->newsletter_smtp_password ? '••••' : '' }}" class="{{ $inp }}" autocomplete="new-password"></div><div><label for="newsletter_smtp_encryption" class="mb-1 block text-sm font-bold">Szyfrowanie</label><select id="newsletter_smtp_encryption" name="newsletter_smtp_encryption" class="{{ $inp }}">@foreach (['tls' => 'TLS (STARTTLS)', 'ssl' => 'SSL', 'none' => 'brak'] as $k => $l)<option value="{{ $k }}" @selected(old('newsletter_smtp_encryption', $s->newsletter_smtp_encryption ?: 'tls') === $k)>{{ $l }}</option>@endforeach</select></div></div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div><label for="newsletter_rate_per_minute" class="mb-1 block text-sm font-bold">Limit wysyłki / minutę</label><input id="newsletter_rate_per_minute" name="newsletter_rate_per_minute" type="number" min="1" value="{{ old('newsletter_rate_per_minute', $s->newsletter_rate_per_minute ?: 60) }}" class="{{ $inp }}"><p class="mt-1 text-xs text-muted">SES ~600, Mailgun ~300, Graph ~30, SMTP współdzielony 10–30.</p></div>
                <div><label for="newsletter_rate_per_day" class="mb-1 block text-sm font-bold">Limit dzienny (opcjonalnie)</label><input id="newsletter_rate_per_day" name="newsletter_rate_per_day" type="number" min="1" value="{{ old('newsletter_rate_per_day', $s->newsletter_rate_per_day) }}" class="{{ $inp }}"></div>
                <div class="space-y-2 pt-6 text-sm">
                    <label class="flex items-center gap-2"><input type="hidden" name="newsletter_track_opens" value="0"><input type="checkbox" name="newsletter_track_opens" value="1" class="rounded border-gray-300 text-brand focus:ring-brand" @checked(old('newsletter_track_opens', $s->newsletter_track_opens ?? true))> Śledź otwarcia (piksel)</label>
                    <label class="flex items-center gap-2"><input type="hidden" name="newsletter_track_clicks" value="0"><input type="checkbox" name="newsletter_track_clicks" value="1" class="rounded border-gray-300 text-brand focus:ring-brand" @checked(old('newsletter_track_clicks', $s->newsletter_track_clicks ?? true))> Śledź kliknięcia</label>
                    <label class="flex items-center gap-2"><input type="hidden" name="newsletter_require_approval" value="0"><input type="checkbox" name="newsletter_require_approval" value="1" class="rounded border-gray-300 text-brand focus:ring-brand" @checked(old('newsletter_require_approval', $s->newsletter_require_approval))> Zapisuj, kto zatwierdził wysyłkę</label>
                </div>
            </div>
        </section>

        <section x-show="tab === 'kanaly'" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6">
            <label class="flex items-center gap-2 text-sm"><input type="hidden" name="newsletter_webpush_enabled" value="0"><input type="checkbox" name="newsletter_webpush_enabled" value="1" class="rounded border-gray-300 text-brand focus:ring-brand" @checked(old('newsletter_webpush_enabled', $s->newsletter_webpush_enabled)) @disabled(! $vapidConfigured)> Kanał Web Push {{ $vapidConfigured ? '' : '(brak kluczy VAPID w .env)' }}</label>
            <div class="grid gap-4 sm:grid-cols-3">
                <div><label for="newsletter_sms_provider" class="mb-1 block text-sm font-bold">Dostawca SMS</label><input id="newsletter_sms_provider" name="newsletter_sms_provider" value="{{ old('newsletter_sms_provider', $s->newsletter_sms_provider) }}" placeholder="smsapi lub URL webhooka" class="{{ $inp }}"><p class="mt-1 text-xs text-muted">„smsapi” = SMSAPI.pl (token OAuth). Inny adres https = generyczny POST JSON {to, from, text}. Puste = kanał wyłączony.</p></div>
                <div><label for="newsletter_sms_token" class="mb-1 block text-sm font-bold">Token</label><input id="newsletter_sms_token" name="newsletter_sms_token" type="password" value="" placeholder="{{ $s->newsletter_sms_token ? '•••• zapisany' : '' }}" class="{{ $inp }}" autocomplete="new-password"></div>
                <div><label for="newsletter_sms_sender" class="mb-1 block text-sm font-bold">Nadawca (pole „from”)</label><input id="newsletter_sms_sender" name="newsletter_sms_sender" value="{{ old('newsletter_sms_sender', $s->newsletter_sms_sender) }}" maxlength="20" class="{{ $inp }}"></div>
            </div>
            <p class="text-xs text-muted">SMS wymaga osobnej, potwierdzonej zgody subskrybenta (formularz z polem telefonu i checkboxem SMS).</p>
        </section>

        <section x-show="tab === 'zapisy'" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6">
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="newsletter_doi_ttl_days" class="mb-1 block text-sm font-bold">Ważność linku potwierdzającego (dni)</label><input id="newsletter_doi_ttl_days" name="newsletter_doi_ttl_days" type="number" min="1" max="60" value="{{ old('newsletter_doi_ttl_days', $s->newsletter_doi_ttl_days ?: 7) }}" class="{{ $inp }}"></div>
                <div><label for="newsletter_retention_days" class="mb-1 block text-sm font-bold">Retencja logów otwarć/kliknięć (dni)</label><input id="newsletter_retention_days" name="newsletter_retention_days" type="number" min="30" max="3650" value="{{ old('newsletter_retention_days', $s->newsletter_retention_days ?: 730) }}" class="{{ $inp }}"><p class="mt-1 text-xs text-muted">Starsze wpisy usuwa <code>newsletter:prune</code>; liczniki w raportach zostają.</p></div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="newsletter_turnstile_site_key" class="mb-1 block text-sm font-bold">Cloudflare Turnstile — site key (opcjonalnie)</label><input id="newsletter_turnstile_site_key" name="newsletter_turnstile_site_key" value="{{ old('newsletter_turnstile_site_key', $s->newsletter_turnstile_site_key) }}" class="{{ $inp }}"></div>
                <div><label for="newsletter_turnstile_secret" class="mb-1 block text-sm font-bold">Turnstile — secret</label><input id="newsletter_turnstile_secret" name="newsletter_turnstile_secret" type="password" value="" placeholder="{{ $s->newsletter_turnstile_secret ? '•••• zapisany' : '' }}" class="{{ $inp }}" autocomplete="new-password"></div>
            </div>
            <p class="text-xs text-muted">Domyślna ochrona formularza: honeypot + żeton czasowy + zadanie tekstowe (dostępne dla czytników ekranu) + limity. Bez obrazkowej CAPTCHY.</p>
        </section>

        <section x-show="tab === 'integracje'" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6">
            <h2 class="text-sm font-bold uppercase text-muted">SZO (feerSZO)</h2>
            <label class="flex items-center gap-2 text-sm"><input type="hidden" name="newsletter_szo_sync" value="0"><input type="checkbox" name="newsletter_szo_sync" value="1" class="rounded border-gray-300 text-brand focus:ring-brand" @checked(old('newsletter_szo_sync', $s->newsletter_szo_sync))> Przekazuj potwierdzonych subskrybentów do SZO jako zgłoszenie formularza {{ $szoConfigured ? '' : '— SZO nie jest skonfigurowane (Ustawienia → Integracje → SZO)' }}</label>
            <div><label for="newsletter_szo_form" class="mb-1 block text-sm font-bold">Domyślny slug formularza w SZO</label><input id="newsletter_szo_form" name="newsletter_szo_form" value="{{ old('newsletter_szo_form', $s->newsletter_szo_form) }}" placeholder="newsletter" class="{{ $inp }}"><p class="mt-1 text-xs text-muted">Formularz w SZO powinien mieć zgody o id „rodo” i „newsletter”. Poszczególne formularze zapisu mogą wskazać inny slug. Ponowienia: <code>php84 artisan newsletter:sync-crm</code>.</p></div>
            <h2 class="pt-4 text-sm font-bold uppercase text-muted">CRM (webhook)</h2>
            <label class="flex items-center gap-2 text-sm"><input type="hidden" name="newsletter_crm_sync" value="0"><input type="checkbox" name="newsletter_crm_sync" value="1" class="rounded border-gray-300 text-brand focus:ring-brand" @checked(old('newsletter_crm_sync', $s->newsletter_crm_sync))> Wysyłaj zdarzenia subskrybenta (subscribed, confirmed, preferences_changed, unsubscribed, anonymized) do CRM</label>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="newsletter_crm_webhook_url" class="mb-1 block text-sm font-bold">Adres webhooka CRM</label><input id="newsletter_crm_webhook_url" name="newsletter_crm_webhook_url" type="url" value="{{ old('newsletter_crm_webhook_url', $s->newsletter_crm_webhook_url) }}" class="{{ $inp }}"></div>
                <div><label for="newsletter_crm_webhook_secret" class="mb-1 block text-sm font-bold">Sekret podpisu (HMAC-SHA256, nagłówek X-Signature)</label><input id="newsletter_crm_webhook_secret" name="newsletter_crm_webhook_secret" type="password" value="" placeholder="{{ $s->newsletter_crm_webhook_secret ? '•••• zapisany' : '' }}" class="{{ $inp }}" autocomplete="new-password"></div>
            </div>
            <p class="text-xs text-muted">Ładunek JSON: <code>{event, occurred_at, subscriber{id,email,name,phone,status,topics,channels,tags,source,…}, consents[]}</code>. HubSpot, Pipedrive czy Zapier przyjmą go przez „Webhook → Create contact”.</p>
        </section>

        <section x-show="tab === 'webhooki'" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6">
            <p class="text-sm">Adresy do wpisania u dostawcy (odbicia, skargi, dostarczenia):</p>
            <ul class="space-y-1 text-sm">@foreach ($webhookUrls as $p => $url)<li><strong class="inline-block w-20">{{ $p }}</strong> <code class="break-all">{{ $url }}</code></li>@endforeach</ul>
            <div><label for="newsletter_webhook_secret" class="mb-1 block text-sm font-bold">Sekret webhooków (Postmark: nagłówek X-Webhook-Secret; generic: HMAC w X-Signature)</label><input id="newsletter_webhook_secret" name="newsletter_webhook_secret" type="password" value="" placeholder="{{ $s->newsletter_webhook_secret ? '•••• zapisany' : '' }}" class="{{ $inp }}" autocomplete="new-password"></div>
            <p class="text-xs text-muted">SES: podpis SNS (potwierdzenie subskrypcji automatyczne). Mailgun: podpis HMAC kluczem API. Dla Microsoft Graph odbicia obsługuje worker (błędy transportu) — brak webhooka.</p>
        </section>

        <section x-show="tab === 'osadzenie'" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6">
            <label for="newsletter_code" class="mb-1 block text-sm font-bold">Kod osadzenia zewnętrznego narzędzia (fallback)</label>
            <textarea id="newsletter_code" name="newsletter_code" rows="8" class="{{ $inp }} font-mono text-xs">{{ old('newsletter_code', $s->newsletter_code) }}</textarea>
            <p class="text-xs text-muted">Używany tylko wtedy, gdy nie ma żadnego aktywnego formularza zapisu modułu (np. Mailchimp/Freshmail). Zalecamy formularz systemowy — zgody i DOI są wtedy w naszej bazie.</p>
        </section>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">Zapisz ustawienia</button>
            <span class="text-xs text-muted">Pola sekretów: puste = bez zmian.</span>
        </div>
    </form>
    <form method="POST" action="{{ route('admin.newsletter.ustawienia.test') }}" class="mt-4 flex flex-wrap items-end gap-3 rounded-lg border border-gray-200 bg-white p-4">
        @csrf
        <div><label for="test_email" class="mb-1 block text-sm font-bold">Wyślij wiadomość testową (zapisanym dostawcą)</label><input id="test_email" name="email" type="email" required value="{{ auth()->user()->email }}" class="rounded border-gray-300 focus:border-brand focus:ring-brand"></div>
        <button type="submit" class="rounded border border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50">Wyślij test</button>
    </form>
@endsection
