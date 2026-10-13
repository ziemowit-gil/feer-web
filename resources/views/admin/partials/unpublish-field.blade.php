{{--
    Panel Publikacja: „Zdejmij z publikacji” (unpublish_at — content:unpublish-due) i notka przeglądu treści,
    gdy opublikowana treść długo się nie zmieniała. Expects: $model.
--}}
@php
    $lastTouch = $model->exists ? collect([$model->updated_at, $model->reviewed_at ?? null])->filter()->max() : null;
    $stale = $lastTouch && $model->is_published && $lastTouch->lt(now()->subMonths(\App\Support\ContentReview::DEFAULT_MONTHS));
@endphp
<div class="mt-3">
    <label for="unpublish_at" class="mb-1.5 block text-sm font-bold text-ink"><i class="fa-regular fa-calendar-xmark mr-1 text-brand-dark" aria-hidden="true"></i>Zdejmij z publikacji <span class="font-normal text-muted">(opcjonalnie)</span></label>
    <input type="datetime-local" id="unpublish_at" name="unpublish_at" value="{{ old('unpublish_at', $model->unpublish_at?->format('Y-m-d\TH:i')) }}"
        aria-describedby="unpublish_at-help" class="w-full rounded-lg border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark">
    @error('unpublish_at') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    <p id="unpublish_at-help" class="mt-1 text-xs text-muted">Np. koniec naboru albo promocji — po tym terminie treść przestanie być publiczna (zostanie jako szkic).</p>
</div>
@if ($stale)
    <p class="mt-3 rounded-md bg-gray-100 p-3 text-sm text-ink">
        <i class="fa-solid fa-clock-rotate-left mr-1 text-brand-dark" aria-hidden="true"></i>
        Ostatnia zmiana {{ $lastTouch->locale('pl')->diffForHumans() }}. Sprawdź, czy treść jest aktualna — zapis odnotuje przegląd.
    </p>
@endif
