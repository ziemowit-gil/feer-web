{{-- Ostrzeżenie o równoczesnej edycji. Wymaga: $lockType, $lockId. --}}
<div data-edit-lock-warning hidden role="alert"
    class="mb-4 flex items-start gap-2 rounded border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
    <i class="fa-solid fa-triangle-exclamation mt-0.5" aria-hidden="true"></i>
    <span><span data-edit-lock-text></span> Zapis może nadpisać cudze zmiany — uzgodnijcie, kto edytuje.</span>
</div>
<script>
    (function () {
        var TYPE = @json($lockType), ID = @json($lockId);
        var URL = @json(route('admin.edit-lock')), TOKEN = @json(csrf_token());
        var box = document.querySelector('[data-edit-lock-warning]');
        var txt = box ? box.querySelector('[data-edit-lock-text]') : null;
        if (!box) return;

        function ping() {
            fetch(URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': TOKEN, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ type: TYPE, id: ID }),
            }).then(function (r) { return r.json(); }).then(function (d) {
                if (d.locked_by) {
                    txt.textContent = 'Uwaga: ' + d.locked_by + ' prawdopodobnie właśnie edytuje tę treść.';
                    box.hidden = false;
                } else {
                    box.hidden = true;
                }
            }).catch(function () {});
        }

        ping();
        setInterval(ping, 60000);
    })();
</script>
