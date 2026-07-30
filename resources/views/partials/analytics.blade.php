@php $gaId = $siteSettings->ga_measurement_id ?? null; @endphp
@if ($gaId)
    {{-- Google Analytics 4 — ładowany dopiero po akceptacji cookies (RODO), z anonimizacją IP.
         Zgoda trzymana w localStorage['feer_cookie_consent'] przez baner cookies. --}}
    <script>
        (function () {
            var ID = @json($gaId);

            function loadGa() {
                if (window.__gaLoaded) return;
                window.__gaLoaded = true;
                var s = document.createElement('script');
                s.async = true;
                s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(ID);
                document.head.appendChild(s);
                window.dataLayer = window.dataLayer || [];
                window.gtag = function () { window.dataLayer.push(arguments); };
                window.gtag('js', new Date());
                window.gtag('config', ID, { anonymize_ip: true });
            }

            var consent;
            try { consent = localStorage.getItem('feer_cookie_consent'); } catch (e) { consent = null; }

            if (consent === '1') {
                loadGa();
            } else {
                // Brak zgody: poczekaj na kliknięcie „Akceptuję" w banerze cookies.
                document.addEventListener('DOMContentLoaded', function () {
                    var btn = document.querySelector('[data-cookie-accept]');
                    if (btn) btn.addEventListener('click', loadGa);
                });
            }
        })();
    </script>
@endif
