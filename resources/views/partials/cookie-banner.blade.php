@if ($siteSettings->cookie_banner_enabled ?? true)
    {{-- Baner cookies: ukryty do czasu decyzji, sterowany przez localStorage.
         Zgodność (WCAG): region z aria-label, focus na przycisku, kontrast. --}}
    <div data-cookie-banner hidden
        class="fixed inset-x-0 bottom-0 z-[150] border-t border-gray-200 bg-white/95 p-4 shadow-[0_-2px_12px_rgba(0,0,0,.08)] backdrop-blur"
        role="region" aria-label="Informacja o plikach cookies" aria-live="polite">
        <div class="mx-auto flex max-w-5xl flex-col items-start gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-ink">
                {{ $siteSettings->cookieBannerText() }}
                <a href="{{ url('/polityka-prywatnosci') }}" class="font-bold text-brand hover:text-brand-dark">Polityka prywatności</a>.
            </p>
            <button type="button" data-cookie-accept
                class="shrink-0 rounded-lg bg-brand px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-dark focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
                Akceptuję
            </button>
        </div>
    </div>

    <script>
        (function () {
            var KEY = 'feer_cookie_consent';
            var banner = document.querySelector('[data-cookie-banner]');
            if (!banner) return;

            var stored;
            try { stored = localStorage.getItem(KEY); } catch (e) { stored = '1'; } // brak localStorage = nie zawracamy głowy

            if (stored === '1') return; // zgoda już zapisana — baner się nie pokazuje

            banner.hidden = false;
            banner.querySelector('[data-cookie-accept]').addEventListener('click', function () {
                try { localStorage.setItem(KEY, '1'); } catch (e) {}
                banner.hidden = true;
            });
        })();
    </script>
@endif
