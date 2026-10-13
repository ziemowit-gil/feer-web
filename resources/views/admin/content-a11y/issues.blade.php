{{--
    Lista uwag dostępności treści (ContentA11yChecker) — w panelu bocznym edycji i w raporcie.
    Expects: $issues; opcjonalnie $compact (bez podpowiedzi, sam opis).
--}}
@if (empty($issues))
    <p class="text-sm text-ink"><i class="fa-solid fa-circle-check mr-1 text-brand-dark" aria-hidden="true"></i>Nie znaleziono problemów z dostępnością treści.</p>
@else
    <ul class="space-y-3" role="list">
        @foreach ($issues as $issue)
            <li class="text-sm">
                <p class="flex gap-2 font-bold text-ink">
                    <i class="fa-solid {{ $issue['level'] === 'error' ? 'fa-circle-xmark' : 'fa-circle-exclamation' }} mt-0.5 flex-none" aria-hidden="true"></i>
                    <span><span class="sr-only">{{ $issue['level'] === 'error' ? 'Błąd: ' : 'Uwaga: ' }}</span>{{ $issue['message'] }}</span>
                </p>
                @unless ($compact ?? false)
                    <p class="ml-6 mt-0.5 text-ink">{{ $issue['hint'] }}</p>
                @endunless
            </li>
        @endforeach
    </ul>
@endif
