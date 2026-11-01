@php
    $gaId = $siteSettings->ga_measurement_id ?? null;
    $matomo = isset($siteSettings) ? $siteSettings->matomoConfig() : null;
    $clarityId = isset($siteSettings) ? trim((string) ($siteSettings->clarity_project_id ?? '')) ?: null : null;
@endphp
@if ($gaId || $matomo || $clarityId)
    {{-- Statystyki: Google Analytics 4, Matomo i/lub Microsoft Clarity.
         GA, Clarity (i Matomo w trybie z cookies) ładuje się dopiero po akceptacji cookies (RODO).
         Zgoda: localStorage['feer_cookie_consent'] === '1', ustawiana przez baner cookies,
         który przy kliknięciu „Akceptuję" wysyła zdarzenie „feer:cookie-consent". --}}
    <script>
        (function () {
            var GA_ID = @json($gaId);
            var MATOMO = @json($matomo);
            var CLARITY_ID = @json($clarityId);

            function loadScript(src) {
                var s = document.createElement('script');
                s.async = true;
                s.src = src;
                document.head.appendChild(s);
            }

            function loadGa() {
                if (!GA_ID || window.__gaLoaded) return;
                window.__gaLoaded = true;
                loadScript('https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(GA_ID));
                window.dataLayer = window.dataLayer || [];
                window.gtag = function () { window.dataLayer.push(arguments); };
                window.gtag('js', new Date());
                window.gtag('config', GA_ID, { anonymize_ip: true });
            }

            function loadMatomo() {
                if (!MATOMO || window.__matomoLoaded) return;
                window.__matomoLoaded = true;
                var _paq = window._paq = window._paq || [];
                if (MATOMO.cookieless) _paq.push(['disableCookies']);
                _paq.push(['trackPageView']);
                _paq.push(['enableLinkTracking']);
                _paq.push(['setTrackerUrl', MATOMO.url + 'matomo.php']);
                _paq.push(['setSiteId', MATOMO.site_id]);
                loadScript(MATOMO.url + 'matomo.js');
            }

            // Kod Clarity dokładnie wg instrukcji Microsoftu (ID z ustawień), doklejany po zgodzie na cookies.
            function loadClarity() {
                if (!CLARITY_ID || window.__clarityLoaded) return;
                window.__clarityLoaded = true;
                (function(c,l,a,r,i,t,y){
                    c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
                    t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
                    y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
                })(window, document, "clarity", "script", CLARITY_ID);
            }

            function loadConsented() {
                loadGa();
                loadMatomo();
                loadClarity();
            }

            // Matomo bez cookies nie potrzebuje zgody — startuje od razu.
            if (MATOMO && MATOMO.cookieless) loadMatomo();

            var consent;
            try { consent = localStorage.getItem('feer_cookie_consent'); } catch (e) { consent = null; }

            if (consent === '1') {
                loadConsented();
            } else {
                document.addEventListener('feer:cookie-consent', loadConsented);
            }
        })();
    </script>
@endif
