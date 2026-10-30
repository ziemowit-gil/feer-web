@if ($siteSettings->cookie_banner_enabled ?? true)
    {{-- Baner cookies: ukryty do czasu decyzji, sterowany przez localStorage.
         „Akceptuję" i „Odrzucam" są równorzędne (RODO: odmowa tak łatwa jak zgoda),
         decyzję można zmienić linkiem „Ustawienia cookies" w stopce ([data-cookie-settings]).
         Decyzja trafia do rejestru zgód (POST /cookie-consent).
         Zgodność (WCAG): region z aria-label, focus na przyciskach, Escape zamyka, kontrast. --}}
    <div data-cookie-banner hidden data-endpoint="{{ route('cookie-consent.store') }}" data-site-id="{{ $siteSettings->id ?? '' }}"
        class="fixed inset-x-0 bottom-0 z-[150] border-t border-gray-200 bg-white p-4 shadow-[0_-2px_12px_rgba(0,0,0,.08)]"
        role="region" aria-label="Ustawienia plików cookies" tabindex="-1">
        <div class="mx-auto flex max-w-5xl flex-col items-start gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm text-ink">
                <p>
                    {{ $siteSettings->cookieBannerText() }}
                    <a href="{{ url('/polityka-prywatnosci') }}" class="font-bold text-brand underline hover:text-brand-dark">Polityka prywatności</a>.
                </p>
                <p data-cookie-status hidden class="mt-1 font-bold"></p>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2">
                <button type="button" data-cookie-accept
                    class="rounded-lg bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    Akceptuję
                </button>
                <button type="button" data-cookie-reject
                    class="rounded-lg bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                    Odrzucam
                </button>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var KEY = 'feer_cookie_consent';      // '1' = zgoda, '0' = odmowa
            var ID_KEY = 'feer_cookie_consent_id'; // losowy identyfikator do rejestru zgód
            var banner = document.querySelector('[data-cookie-banner]');
            if (!banner) return;

            var status = banner.querySelector('[data-cookie-status]');
            var opener = null;

            function get(key) { try { return localStorage.getItem(key); } catch (e) { return null; } }
            function set(key, value) { try { localStorage.setItem(key, value); } catch (e) {} }

            function consentId() {
                var id = get(ID_KEY);
                if (!id) {
                    id = (window.crypto && crypto.randomUUID) ? crypto.randomUUID()
                        : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
                            var r = Math.random() * 16 | 0;
                            return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
                        });
                    set(ID_KEY, id);
                }
                return id;
            }

            function log(decision) {
                try {
                    fetch(banner.dataset.endpoint, {
                        method: 'POST',
                        keepalive: true,
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({ consent_id: consentId(), decision: decision, site_id: banner.dataset.siteId || null })
                    }).catch(function () {});
                } catch (e) {}
            }

            // Usuwa ciasteczka statystyk (GA: _ga*, _gid, _gat*; Matomo: _pk_*, mtm_*) z bieżącej domeny i domen nadrzędnych.
            function clearAnalyticsCookies() {
                var parts = location.hostname.split('.');
                var domains = [''];
                for (var i = 0; i < parts.length - 1; i++) domains.push('; domain=.' + parts.slice(i).join('.'));
                document.cookie.split(';').forEach(function (c) {
                    var name = c.split('=')[0].trim();
                    if (/^(_ga|_gid|_gat|_pk_|mtm_)/.test(name)) {
                        domains.forEach(function (d) {
                            document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/' + d;
                        });
                    }
                });
            }

            function show(fromButton) {
                opener = fromButton || null;
                var current = get(KEY);
                if (current === '1' || current === '0') {
                    status.textContent = current === '1'
                        ? 'Obecnie: zgoda na pliki cookies statystyczne.'
                        : 'Obecnie: odmowa. Używamy tylko niezbędnych plików cookies.';
                    status.hidden = false;
                } else {
                    status.hidden = true;
                }
                banner.hidden = false;
                if (opener) banner.querySelector('[data-cookie-accept]').focus();
            }

            function hide() {
                banner.hidden = true;
                if (opener) { opener.focus(); opener = null; }
            }

            banner.querySelector('[data-cookie-accept]').addEventListener('click', function () {
                set(KEY, '1');
                log('accepted');
                hide();
                // Sygnał dla kodów statystyk (partials/analytics), że można je załadować.
                document.dispatchEvent(new CustomEvent('feer:cookie-consent'));
            });

            banner.querySelector('[data-cookie-reject]').addEventListener('click', function () {
                var hadConsent = get(KEY) === '1';
                set(KEY, '0');
                log('rejected');
                clearAnalyticsCookies();
                hide();
                // Skrypty statystyk już działają na tej stronie — przeładowanie je wyłącza.
                if (hadConsent && (window.__gaLoaded || window.__matomoLoaded)) location.reload();
            });

            banner.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && get(KEY) !== null) hide();
            });

            document.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-cookie-settings]');
                if (!btn) return;
                e.preventDefault();
                show(btn);
            });

            var stored = get(KEY);
            if (stored !== '1' && stored !== '0') show();
        })();
    </script>
@endif
