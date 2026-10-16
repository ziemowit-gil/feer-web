@php
    // Data powstania treści do komunikatu archiwalnego.
    $archivedDate = $date ?? null;
@endphp
<div class="archival-notice mb-6 flex items-start gap-3 rounded-lg bg-gray-50 px-4 py-3 text-sm text-ink" role="note">
    <i class="fa-solid fa-clock-rotate-left mt-0.5 shrink-0 text-brand-dark" aria-hidden="true"></i>
    <p>
        <strong>Treść archiwalna.</strong>
        @if ($archivedDate)
            Powstała {{ $archivedDate->locale('pl')->isoFormat('D MMMM YYYY') }} i może być już nieaktualna.
        @else
            Powstała jakiś czas temu i może być już nieaktualna.
        @endif
    </p>
</div>
