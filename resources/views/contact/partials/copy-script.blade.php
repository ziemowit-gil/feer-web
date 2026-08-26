{{-- Przyciski „Kopiuj" przy numerach rachunków i kodzie paczkomatu. --}}
@if (! empty($siteSettings->contact_bank_accounts) || filled($siteSettings->contact_paczkomat_code) || filled($siteSettings->bank_account_number) || filled($siteSettings->bank_account_tax_number))
    <script>
        document.querySelectorAll('[data-copy-button]').forEach(function (button) {
            button.addEventListener('click', function () {
                navigator.clipboard.writeText(button.dataset.copyValue).then(function () {
                    const original = button.innerHTML;
                    button.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i> Skopiowano';
                    setTimeout(function () { button.innerHTML = original; }, 2000);
                });
            });
        });
    </script>
@endif
