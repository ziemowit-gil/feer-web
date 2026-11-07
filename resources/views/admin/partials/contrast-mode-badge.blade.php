{{--
    Plakietka trybu kontrastu w panelu: gdy w przeglądarce włączono skalę szarości albo wysoki kontrast (przycisk
    „Dostępność”, zapamiętane w localStorage), całe menu i strony panelu wyglądają inaczej niż zwykle. Plakietka mówi o
    tym wprost i pozwala wyłączyć tryb jednym kliknięciem. Własny skrypt (bez buildu JS); role=status, cel dotyku ≥ 44 px,
    kontrast biały na #1d1d1a ≥ 15:1. Plakietka sama podlega filtrowi, więc jest czytelna także w odcieniach szarości.
--}}
<div id="a11y-mode-badge" role="status" hidden
     style="position:fixed;left:1rem;bottom:1rem;z-index:60;display:none;align-items:center;gap:.75rem;padding:.5rem .5rem .5rem 1rem;border-radius:.5rem;background:#1d1d1a;color:#fff;font:700 .875rem/1.25 system-ui,sans-serif;box-shadow:0 2px 8px rgba(0,0,0,.25)">
    <span id="a11y-mode-badge-text"></span>
    <button type="button" id="a11y-mode-badge-off"
            style="min-height:2.75rem;padding:0 1rem;border:0;border-radius:.375rem;background:#fff;color:#1d1d1a;font:inherit;cursor:pointer">Wyłącz</button>
</div>
<script>
    (function () {
        var badge = document.getElementById('a11y-mode-badge');
        if (! badge) return;
        var modes = { 'contrast-gray': 'Włączona skala szarości', 'contrast-bw': 'Włączony kontrast czarno-żółty', 'contrast': 'Włączony wysoki kontrast' };
        function sync() {
            var active = Object.keys(modes).filter(function (c) { return document.documentElement.classList.contains(c); })[0];
            badge.style.display = active ? 'flex' : 'none';
            badge.hidden = ! active;
            document.getElementById('a11y-mode-badge-text').textContent = active ? modes[active] + ' (ustawienie przeglądarki)' : '';
        }
        document.getElementById('a11y-mode-badge-off').addEventListener('click', function () {
            Object.keys(modes).forEach(function (c) { document.documentElement.classList.remove(c); });
            try { localStorage.setItem('a11y-contrast-mode', ''); } catch (e) {}
            document.querySelectorAll('[data-a11y-contrast]').forEach(function (b) { b.setAttribute('aria-pressed', 'false'); });
            sync();
        });
        new MutationObserver(sync).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        sync();
    })();
</script>
