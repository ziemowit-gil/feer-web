@php
    // Data powstania treści do komunikatu archiwalnego.
    $archivedDate = $date ?? null;
@endphp
<div class="mb-6 flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="note">
    <i class="fa-solid fa-clock-rotate-left mt-0.5 shrink-0 text-amber-500" aria-hidden="true"></i>
    <p>
        <strong>Treść archiwalna.</strong>
        @if ($archivedDate)
            Powstała {{ $archivedDate->locale('pl')->isoFormat('D MMMM YYYY') }} i może być już nieaktualna.
        @else
            Powstała jakiś czas temu i może być już nieaktualna.
        @endif
    </p>
</div>
