{{--
    Komunikaty z systemu SZO w strefie współpracownika.
    Oczekuje $szoKomunikaty = ['ok' => bool, 'items' => [...]].
    ok = false → „Błąd połączenia"; ok = true + pusto → brak komunikatów.
--}}
<section class="mt-10" aria-labelledby="szo-komunikaty-heading">
    <h2 id="szo-komunikaty-heading" class="mb-4 flex items-center gap-2 text-2xl font-bold text-ink">
        <i class="fa-solid fa-bullhorn text-brand" aria-hidden="true"></i> Komunikaty
    </h2>

    @if (! ($szoKomunikaty['ok'] ?? false))
        <div role="alert" class="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <i class="fa-solid fa-triangle-exclamation mt-0.5" aria-hidden="true"></i>
            <span>Błąd połączenia — nie udało się pobrać komunikatów z systemu SZO. Spróbuj ponownie za chwilę.</span>
        </div>
    @elseif (empty($szoKomunikaty['items']))
        <p class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-muted">
            Brak komunikatów do wyświetlenia.
        </p>
    @else
        <ul class="space-y-4">
            @foreach ($szoKomunikaty['items'] as $komunikat)
                <li class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h3 class="text-lg font-bold text-ink">
                            @if (! empty($komunikat['url']))
                                <a href="{{ $komunikat['url'] }}" target="_blank" rel="noopener"
                                    class="hover:text-brand">
                                    {{ $komunikat['title'] }}
                                    <i class="fa-solid fa-arrow-up-right-from-square ml-1 text-xs" aria-hidden="true"></i>
                                    <span class="sr-only">(otwiera się w nowej karcie)</span>
                                </a>
                            @else
                                {{ $komunikat['title'] }}
                            @endif
                        </h3>
                        @if (! empty($komunikat['date']))
                            <time datetime="{{ $komunikat['date']->toIso8601String() }}" class="text-xs font-medium text-muted">
                                {{ $komunikat['date']->locale('pl')->isoFormat('D MMMM YYYY, HH:mm') }}
                            </time>
                        @endif
                    </div>
                    @if (! empty($komunikat['body']))
                        <div class="prose prose-sm mt-2 max-w-none text-ink">{!! nl2br(e($komunikat['body'])) !!}</div>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</section>
