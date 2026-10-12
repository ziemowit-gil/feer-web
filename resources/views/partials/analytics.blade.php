@php
    $gaId = $siteSettings->ga_measurement_id ?? null;
    $matomo = isset($siteSettings) ? $siteSettings->matomoConfig() : null;
@endphp
@if ($gaId || $matomo)
    {{-- Statystyki: Google Analytics 4 i/lub Matomo.
         GA (i Matomo w trybie z cookies) ładuje się dopiero po akceptacji cookies (RODO).
         Zgoda: localStorage['feer_cookie_consent'] === '1', ustawiana przez baner cookies,
         który przy kliknięciu „Akceptuję" wysyła zdarzenie „feer:cookie-consent". --}}
    <script>
        (function () {
            var GA_ID = @json($gaId);
            var MATOMO = @json($matomo);

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

            function loadConsented() {
                loadGa();
                loadMatomo();
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
