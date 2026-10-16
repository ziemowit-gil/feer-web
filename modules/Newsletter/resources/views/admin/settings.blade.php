@extends('admin.layout')
@section('title', 'Ustawienia newslettera')
@section('content')
    @include('newsletter::admin.partials.flash')
    @php $s = $settings; $inp = 'w-full rounded border-gray-300 focus:border-brand-dark focus:ring-brand-dark'; @endphp
    <form method="POST" action="{{ route('admin.newsletter.ustawienia.update') }}" class="space-y-6" x-data="{ tab: 'wysylka', mailer: '{{ old('newsletter_mailer', $s->newsletter_mailer ?? '') }}' }">
        @csrf @method('PUT')
        <nav class="flex flex-wrap gap-1 border-b border-gray-200" aria-label="Sekcje ustawień">
            @foreach (['wysylka' => 'Wysyłka e-mail', 'kanaly' => 'Push i SMS', 'zapisy' => 'Zapisy i RODO', 'integracje' => 'SZO i CRM', 'webhooki' => 'Webhooki', 'osadzenie' => 'Kod zewnętrzny'] as $k => $l)
                <button type="button" @click="tab = '{{ $k }}'" :class="tab === '{{ $k }}' ? 'border-brand-dark text-brand-dark' : 'border-transparent text-gray-700 hover:text-ink'" class="-mb-px border-b-2 px-4 py-2 text-sm font-bold" :aria-current="tab === '{{ $k }}' ? 'page' : null">{{ $l }}</button>
            @endforeach
        </nav>

        <section x-show="tab === 'wysylka'" class="space-y-4 p-6 rounded-md bg-white">
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
                <div><label for="newsletter_rate_per_day" class="mb-1 block text-sm font-bold">Limit dzienny (opcjonalnie)</label><input id="newsletter_rate_per_day" name="newsletter_rate_per_day" type="number" min="1" value="{{ old('newsletter_rate_per_day', $s->newsletter_rate_per_day) }}" aria-describedby="rate_day_help" class="{{ $inp }}"><p id="rate_day_help" class="mt-1 text-xs text-muted">Duża kampania wyjdzie w częściach — po wyczerpaniu limitu reszta następnego dnia (w oknie wysyłki).</p></div>
                <div class="space-y-2 pt-6 text-sm">
                    <label class="flex items-center gap-2"><input type="hidden" name="newsletter_track_opens" value="0"><input type="checkbox" name="newsletter_track_opens" value="1" class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark" @checked(old('newsletter_track_opens', $s->newsletter_track_opens ?? true))> Śledź otwarcia (piksel)</label>
                    <label class="flex items-center gap-2"><input type="hidden" name="newsletter_track_clicks" value="0"><input type="checkbox" name="newsletter_track_clicks" value="1" class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark" @checked(old('newsletter_track_clicks', $s->newsletter_track_clicks ?? true))> Śledź kliknięcia</label>
                    <label class="flex items-center gap-2"><input type="hidden" name="newsletter_require_approval" value="0"><input type="checkbox" name="newsletter_require_approval" value="1" class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark" @checked(old('newsletter_require_approval', $s->newsletter_require_approval))> Zapisuj, kto zatwierdził wysyłkę</label>
                </div>
            </div>
            @php $win = $sendWindow->config; @endphp
            <fieldset class="rounded-md bg-gray-50 p-4">
                <legend class="px-1 text-sm font-bold">Okno wysyłki</legend>
                <label class="flex items-center gap-2 text-sm"><input type="hidden" name="send_window[enabled]" value="0"><input type="checkbox" name="send_window[enabled]" value="1" class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark" @checked(old('send_window.enabled', $win['enabled']))> Wysyłaj tylko w wybrane dni i godziny</label>
                <p class="mt-1 text-xs text-muted">Poza oknem dostawy czekają w kolejce do najbliższego otwarcia (np. kampania zaplanowana na piątek 17:50 dokończy się w poniedziałek o 8:00). Teraz: <strong class="text-ink">{{ $sendWindow->describe() }}</strong>.</p>
                <div class="mt-3 flex flex-wrap gap-x-4 gap-y-2" role="group" aria-label="Dni tygodnia">
                    @foreach (\Modules\Newsletter\Services\SendWindow::DAYS as $d => $label)
                        <label class="inline-flex items-center gap-1.5 text-sm"><input type="checkbox" name="send_window[days][]" value="{{ $d }}" class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark" @checked(in_array($d, array_map('intval', (array) old('send_window.days', $win['days'])), true))> {{ $label }}</label>
                    @endforeach
                </div>
                <div class="mt-3 grid max-w-sm grid-cols-2 gap-4">
                    <div><label for="send_window_from" class="mb-1 block text-sm font-bold">Od</label><input id="send_window_from" name="send_window[from]" type="time" value="{{ old('send_window.from', $win['from']) }}" class="{{ $inp }}"></div>
                    <div><label for="send_window_to" class="mb-1 block text-sm font-bold">Do</label><input id="send_window_to" name="send_window[to]" type="time" value="{{ old('send_window.to', $win['to']) }}" class="{{ $inp }}"></div>
                </div>
            </fieldset>

            <div class="rounded-md bg-gray-50 p-4" aria-labelledby="dns-h">
                <div class="flex flex-wrap items-center justify-between gap-2"><h2 id="dns-h" class="text-sm font-bold">Dostarczalność domeny nadawcy: {{ $dns['domain'] ?: '—' }}</h2>
                    <a href="{{ route('admin.newsletter.ustawienia.edit', ['odswiez-dns' => 1]) }}" class="text-sm font-bold text-brand-dark underline">Sprawdź ponownie</a></div>
                <dl class="mt-2 grid gap-x-4 gap-y-1 text-sm sm:grid-cols-[6rem_1fr]">
                    <dt class="font-bold">SPF</dt><dd>@if ($dns['spf_ok'])<span class="font-bold text-green-800">✔ poprawny</span>@elseif ($dns['spf'])<span class="font-bold text-red-800">✘ do poprawy</span>@else<span class="font-bold text-red-800">✘ brak</span>@endif @if ($dns['spf'])<code class="block break-all text-xs text-muted">{{ $dns['spf'] }}</code>@endif</dd>
                    <dt class="font-bold">DKIM</dt><dd>@if ($dns['dkim'])<span class="font-bold text-green-800">✔ selektor „{{ $dns['dkim'] }}”</span>@else<span class="font-bold text-amber-800">? nie znaleziono pod typowymi selektorami</span>@endif</dd>
                    <dt class="font-bold">DMARC</dt><dd>@if ($dns['dmarc_ok'])<span class="font-bold text-green-800">✔ polityka {{ $dns['dmarc_policy'] ?? '?' }}</span><code class="block break-all text-xs text-muted">{{ $dns['dmarc'] }}</code>@else<span class="font-bold text-red-800">✘ brak</span>@endif</dd>
                    <dt class="font-bold">Wypis</dt><dd><span class="font-bold text-green-800">✔ nagłówki List-Unsubscribe i wypis jednym kliknięciem w każdym mailu</span></dd>
                </dl>
                @if ($dns['advice'])
                <ul class="mt-2 list-disc space-y-0.5 pl-5 text-sm">@foreach ($dns['advice'] as $a)<li>{{ $a }}</li>@endforeach</ul>
                @endif
            </div>
        </section>

        <section x-show="tab === 'kanaly'" class="space-y-4 p-6 rounded-md bg-white">
            <label class="flex items-center gap-2 text-sm"><input type="hidden" name="newsletter_webpush_enabled" value="0"><input type="checkbox" name="newsletter_webpush_enabled" value="1" class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark" @checked(old('newsletter_webpush_enabled', $s->newsletter_webpush_enabled)) @disabled(! $vapidConfigured)> Kanał Web Push {{ $vapidConfigured ? '' : '(brak kluczy VAPID w .env)' }}</label>
            <div class="grid gap-4 sm:grid-cols-3">
                <div><label for="newsletter_sms_provider" class="mb-1 block text-sm font-bold">Dostawca SMS</label><input id="newsletter_sms_provider" name="newsletter_sms_provider" value="{{ old('newsletter_sms_provider', $s->newsletter_sms_provider) }}" placeholder="smsapi lub URL webhooka" class="{{ $inp }}"><p class="mt-1 text-xs text-muted">„smsapi” = SMSAPI.pl (token OAuth). Inny adres https = generyczny POST JSON {to, from, text}. Puste = kanał wyłączony.</p></div>
                <div><label for="newsletter_sms_token" class="mb-1 block text-sm font-bold">Token</label><input id="newsletter_sms_token" name="newsletter_sms_token" type="password" value="" placeholder="{{ $s->newsletter_sms_token ? '•••• zapisany' : '' }}" class="{{ $inp }}" autocomplete="new-password"></div>
                <div><label for="newsletter_sms_sender" class="mb-1 block text-sm font-bold">Nadawca (pole „from”)</label><input id="newsletter_sms_sender" name="newsletter_sms_sender" value="{{ old('newsletter_sms_sender', $s->newsletter_sms_sender) }}" maxlength="20" class="{{ $inp }}"></div>
            </div>
            <p class="text-xs text-muted">SMS wymaga osobnej, potwierdzonej zgody subskrybenta (formularz z polem telefonu i checkboxem SMS).</p>
        </section>

        <section x-show="tab === 'zapisy'" class="space-y-4 p-6 rounded-md bg-white">
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

        <section x-show="tab === 'integracje'" class="space-y-4 p-6 rounded-md bg-white">
            <h2 class="text-sm font-bold uppercase text-muted">SZO (feerSZO)</h2>
            <label class="flex items-center gap-2 text-sm"><input type="hidden" name="newsletter_szo_sync" value="0"><input type="checkbox" name="newsletter_szo_sync" value="1" class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark" @checked(old('newsletter_szo_sync', $s->newsletter_szo_sync))> Przekazuj potwierdzonych subskrybentów do SZO jako zgłoszenie formularza {{ $szoConfigured ? '' : '— SZO nie jest skonfigurowane (Ustawienia → Integracje → SZO)' }}</label>
            <div><label for="newsletter_szo_form" class="mb-1 block text-sm font-bold">Domyślny slug formularza w SZO</label><input id="newsletter_szo_form" name="newsletter_szo_form" value="{{ old('newsletter_szo_form', $s->newsletter_szo_form) }}" placeholder="newsletter" class="{{ $inp }}"><p class="mt-1 text-xs text-muted">Formularz w SZO powinien mieć zgody o id „rodo” i „newsletter”. Poszczególne formularze zapisu mogą wskazać inny slug. Ponowienia: <code>php84 artisan newsletter:sync-crm</code>.</p></div>
            <h2 class="pt-4 text-sm font-bold uppercase text-muted">CRM (webhook)</h2>
            <label class="flex items-center gap-2 text-sm"><input type="hidden" name="newsletter_crm_sync" value="0"><input type="checkbox" name="newsletter_crm_sync" value="1" class="rounded border-gray-500 text-brand-dark focus:ring-brand-dark" @checked(old('newsletter_crm_sync', $s->newsletter_crm_sync))> Wysyłaj zdarzenia subskrybenta (subscribed, confirmed, preferences_changed, unsubscribed, anonymized) do CRM</label>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label for="newsletter_crm_webhook_url" class="mb-1 block text-sm font-bold">Adres webhooka CRM</label><input id="newsletter_crm_webhook_url" name="newsletter_crm_webhook_url" type="url" value="{{ old('newsletter_crm_webhook_url', $s->newsletter_crm_webhook_url) }}" class="{{ $inp }}"></div>
                <div><label for="newsletter_crm_webhook_secret" class="mb-1 block text-sm font-bold">Sekret podpisu (HMAC-SHA256, nagłówek X-Signature)</label><input id="newsletter_crm_webhook_secret" name="newsletter_crm_webhook_secret" type="password" value="" placeholder="{{ $s->newsletter_crm_webhook_secret ? '•••• zapisany' : '' }}" class="{{ $inp }}" autocomplete="new-password"></div>
            </div>
            <p class="text-xs text-muted">Ładunek JSON: <code>{event, occurred_at, subscriber{id,email,name,phone,status,topics,channels,tags,source,…}, consents[]}</code>. HubSpot, Pipedrive czy Zapier przyjmą go przez „Webhook → Create contact”.</p>
        </section>

        <section x-show="tab === 'webhooki'" class="space-y-4 p-6 rounded-md bg-white">
            <p class="text-sm">Adresy do wpisania u dostawcy (odbicia, skargi, dostarczenia):</p>
            <ul class="space-y-1 text-sm">@foreach ($webhookUrls as $p => $url)<li><strong class="inline-block w-20">{{ $p }}</strong> <code class="break-all">{{ $url }}</code></li>@endforeach</ul>
            <div><label for="newsletter_webhook_secret" class="mb-1 block text-sm font-bold">Sekret webhooków (Postmark: nagłówek X-Webhook-Secret; generic: HMAC w X-Signature)</label><input id="newsletter_webhook_secret" name="newsletter_webhook_secret" type="password" value="" placeholder="{{ $s->newsletter_webhook_secret ? '•••• zapisany' : '' }}" class="{{ $inp }}" autocomplete="new-password"></div>
            <p class="text-xs text-muted">SES: podpis SNS (potwierdzenie subskrypcji automatyczne). Mailgun: podpis HMAC kluczem API. Dla Microsoft Graph odbicia obsługuje worker (błędy transportu) — brak webhooka.</p>
        </section>

        <section x-show="tab === 'osadzenie'" class="space-y-4 p-6 rounded-md bg-white">
            <label for="newsletter_code" class="mb-1 block text-sm font-bold">Kod osadzenia zewnętrznego narzędzia (fallback)</label>
            <textarea id="newsletter_code" name="newsletter_code" rows="8" class="{{ $inp }} font-mono text-xs">{{ old('newsletter_code', $s->newsletter_code) }}</textarea>
            <p class="text-xs text-muted">Używany tylko wtedy, gdy nie ma żadnego aktywnego formularza zapisu modułu (np. Mailchimp/Freshmail). Zalecamy formularz systemowy — zgody i DOI są wtedy w naszej bazie.</p>
        </section>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="rounded bg-brand-dark px-5 py-2 text-sm font-bold text-white hover:bg-ink">Zapisz ustawienia</button>
            <span class="text-xs text-muted">Pola sekretów: puste = bez zmian.</span>
        </div>
    </form>
    <form method="POST" action="{{ route('admin.newsletter.ustawienia.test') }}" class="mt-4 flex flex-wrap items-end gap-3 p-4 rounded-md bg-white">
        @csrf
        <div><label for="test_email" class="mb-1 block text-sm font-bold">Wyślij wiadomość testową (zapisanym dostawcą)</label><input id="test_email" name="email" type="email" required value="{{ auth()->user()->email }}" class="rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark"></div>
        <button type="submit" class="rounded-md px-4 py-2 text-sm font-bold text-gray-700 bg-gray-100 hover:bg-brand-light">Wyślij test</button>
    </form>
@endsection
