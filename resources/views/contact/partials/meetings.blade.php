{{-- Spotkania: online + harmonogram. Wymaga $showMeetings, $meetingTitle, $onlineUrl. --}}
@php
    // Sekcje strony kontaktowej mają dwa style opakowania: „plain" — kreska nad
    // sekcją (wariant klasyczny) i „card" — karta w siatce (nowe wyglądy).
    $sectionClass = ($sectionStyle ?? 'plain') === 'card'
        ? 'h-full scroll-mt-24 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm'
        : 'mt-12 scroll-mt-24 border-t border-gray-100 pt-8';
@endphp
@if ($showMeetings)
    @php
        $onlineLabel    = $siteSettings->contact_online_meeting_label ?: 'Wybierz dogodny termin';
        $onlineText     = $siteSettings->contact_online_meeting_text ?: 'Najwygodniej spotkać się online — umów rozmowę w dogodnym dla Ciebie terminie.';
        $remoteNote     = $siteSettings->contact_remote_note;
        $scheduleTitle  = $siteSettings->contact_schedule_title ?: 'Kiedy i gdzie jesteśmy w Krakowie';
        $showOnline     = filled($onlineUrl);
        $onlineExternal = $showOnline && \Illuminate\Support\Str::startsWith($onlineUrl, ['http://', 'https://']);
    @endphp

    <div id="spotkania" class="{{ $sectionClass }}">
        <h2 class="mb-4 text-xl font-bold text-ink">{{ $meetingTitle }}</h2>

        @if ($showOnline)
            <div class="mb-6 flex flex-col gap-4 rounded-xl bg-linear-to-br from-brand to-brand-dark p-6 text-white sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <span class="flex h-12 w-12 flex-none items-center justify-center rounded-full bg-white/15 text-xl" aria-hidden="true"><i class="fa-solid fa-video"></i></span>
                    <div>
                        <p class="text-lg font-bold">Zapraszamy na spotkanie online</p>
                        <p class="mt-0.5 max-w-xl text-sm text-white/90">{{ $onlineText }}</p>
                    </div>
                </div>
                <a href="{{ $onlineUrl }}" @if ($onlineExternal) target="_blank" rel="noopener" @endif
                    class="inline-flex w-fit flex-none items-center gap-2 rounded bg-white px-5 py-2.5 text-sm font-bold text-brand transition hover:bg-white/90">
                    <i class="fa-solid fa-calendar-check" aria-hidden="true"></i> {{ $onlineLabel }}
                    @if ($onlineExternal)<i class="fa-solid fa-arrow-up-right-from-square text-xs" aria-hidden="true"></i>@endif
                </a>
            </div>
        @endif

        @if (filled($remoteNote))
            <p class="mb-6 flex max-w-2xl items-start gap-2.5 rounded-lg bg-brand-light px-4 py-3 text-sm text-ink">
                <i class="fa-solid fa-house-laptop mt-0.5 flex-none text-brand" aria-hidden="true"></i>
                <span>{{ $remoteNote }}</span>
            </p>
        @endif

        @if (! empty($scheduleItems))
            <div x-data="{ open: {{ $errors->meeting->isNotEmpty() ? 'true' : 'false' }} }"
                x-effect="document.body.style.overflow = open ? 'hidden' : ''">

                @if (session('meeting_signed_up'))
                    <div class="mb-4 flex max-w-2xl items-start gap-2 rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                        <i class="fa-solid fa-circle-check mt-0.5" aria-hidden="true"></i>
                        <span>Dziękujemy! Zapisaliśmy Twoje zgłoszenie. Jeśli termin się zmieni — damy znać e-mailem.</span>
                    </div>
                @endif

                <div class="flex flex-col gap-4 rounded-lg border border-gray-200 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="flex h-11 w-11 flex-none items-center justify-center rounded-full bg-brand-light text-brand" aria-hidden="true">
                            <i class="fa-solid fa-location-dot"></i>
                        </span>
                        <div>
                            <h3 class="text-lg font-bold text-ink">{{ $scheduleTitle }}</h3>
                            <p class="mt-0.5 max-w-xl text-sm text-muted">Bywamy też na miejscu. Sprawdź najbliższe terminy i daj znać, że przyjdziesz.</p>
                        </div>
                    </div>
                    <button type="button" @click="open = true"
                        class="inline-flex w-fit flex-none items-center gap-2 rounded bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
                        <i class="fa-solid fa-calendar-days" aria-hidden="true"></i> Zobacz terminy i daj znać
                    </button>
                </div>

                {{-- Modal z terminami i formularzem zapisu --}}
                <div x-show="open" x-cloak x-transition.opacity
                    @keydown.escape.window="open = false"
                    @click.self="open = false"
                    class="fixed inset-0 z-[200] flex items-start justify-center overflow-y-auto bg-black/60 p-4 sm:items-center"
                    role="dialog" aria-modal="true" aria-labelledby="mtg-modal-title">
                    <div class="relative my-8 max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-lg bg-white p-6 shadow-xl"
                        x-init="$watch('open', value => value && $nextTick(() => $refs.firstField && $refs.firstField.focus()))">
                        <button type="button" @click="open = false" aria-label="Zamknij okno"
                            class="absolute right-3 top-3 flex h-9 w-9 items-center justify-center rounded-full text-muted hover:bg-gray-100 hover:text-ink">
                            <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
                        </button>

                        <h3 id="mtg-modal-title" class="mb-4 pr-8 text-lg font-bold text-ink">{{ $scheduleTitle }}</h3>

                        <ul class="mb-6 space-y-3">
                            @foreach ($scheduleItems as $item)
                                <li class="rounded-lg border p-3 {{ $item['is_next'] ? 'border-brand/40 bg-brand-light/50' : 'border-gray-100' }}">
                                    @if ($item['is_next'])
                                        <span class="mb-1 inline-flex items-center gap-1 rounded-full bg-brand px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide text-white">
                                            <i class="fa-regular fa-star" aria-hidden="true"></i> Najbliższy termin
                                        </span>
                                    @endif
                                    <p class="text-sm font-bold text-ink">
                                        <i class="fa-regular fa-calendar mr-1.5 text-muted" aria-hidden="true"></i>{{ $item['when_label'] }}
                                        @if ($item['recurring'])
                                            <span class="ml-1 font-normal text-muted">(cyklicznie)</span>
                                        @endif
                                    </p>
                                    @if ($item['where'] !== '')
                                        <p class="mt-0.5 text-sm text-ink"><i class="fa-solid fa-location-dot mr-1.5 text-muted" aria-hidden="true"></i>{{ $item['where'] }}</p>
                                    @endif
                                    @if ($item['note'] !== '')
                                        <p class="mt-0.5 text-xs text-muted">{{ $item['note'] }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>

                        <div class="border-t border-gray-100 pt-5">
                            <h4 class="text-base font-bold text-ink">Daj znać, że przyjdziesz</h4>
                            <p class="mt-1 mb-4 text-sm text-muted">Będziemy na Ciebie czekać, a gdyby termin się zmienił, poinformujemy Cię e-mailem.</p>

                            <form method="POST" action="{{ route('meeting.signup') }}" class="space-y-4">
                                @csrf

                                <div class="hidden" aria-hidden="true">
                                    <label for="mtg-website">Zostaw to pole puste</label>
                                    <input type="text" id="mtg-website" name="website" tabindex="-1" autocomplete="off">
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="mtg-name" class="mb-1 block text-sm font-bold">Imię i nazwisko</label>
                                        <input type="text" id="mtg-name" name="name" value="{{ old('name') }}" required x-ref="firstField"
                                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                                        @error('name', 'meeting') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="mtg-phone" class="mb-1 block text-sm font-bold">Telefon <span class="font-normal text-muted">(opcjonalnie)</span></label>
                                        <input type="tel" id="mtg-phone" name="phone" value="{{ old('phone') }}" autocomplete="tel"
                                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                                        @error('phone', 'meeting') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <div>
                                    <label for="mtg-email" class="mb-1 block text-sm font-bold">E-mail</label>
                                    <input type="email" id="mtg-email" name="email" value="{{ old('email') }}" required
                                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                                    @error('email', 'meeting') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="mtg-term" class="mb-1 block text-sm font-bold">Którego terminu dotyczy? <span class="font-normal text-muted">(opcjonalnie)</span></label>
                                    <select id="mtg-term" name="term" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                                        <option value="">— wybierz termin —</option>
                                        @foreach ($scheduleItems as $item)
                                            @php $termLabel = trim($item['when_label'].($item['where'] !== '' ? ' — '.$item['where'] : '')); @endphp
                                            <option value="{{ $termLabel }}" @selected(old('term') === $termLabel)>{{ $termLabel }}</option>
                                        @endforeach
                                        <option value="Nie wiem jeszcze / inny termin" @selected(old('term') === 'Nie wiem jeszcze / inny termin')>Nie wiem jeszcze / inny termin</option>
                                    </select>
                                    @error('term', 'meeting') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="mtg-message" class="mb-1 block text-sm font-bold">Wiadomość <span class="font-normal text-muted">(opcjonalnie)</span></label>
                                    <textarea id="mtg-message" name="message" rows="3"
                                        class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('message') }}</textarea>
                                    @error('message', 'meeting') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label class="flex items-start gap-2">
                                        <input type="checkbox" name="rodo_consent" value="1" required {{ old('rodo_consent') ? 'checked' : '' }}
                                            class="mt-1 flex-none rounded border-gray-300 text-brand focus:ring-brand">
                                        <span class="text-sm text-muted">
                                            Wyrażam zgodę na przetwarzanie moich danych (imienia i adresu e-mail) w celu obsługi zgłoszenia i poinformowania mnie o ewentualnej zmianie terminu, zgodnie z
                                            <a href="{{ route('page.show', 'polityka-prywatnosci') }}" class="font-bold text-brand hover:text-brand-dark">Polityką prywatności</a>.
                                            <span aria-hidden="true" class="font-bold text-red-600">*</span>
                                        </span>
                                    </label>
                                    @error('rodo_consent', 'meeting') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>

                                <div class="flex items-center gap-3">
                                    <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">Daj znać, że przyjdę</button>
                                    <button type="button" @click="open = false" class="text-sm font-bold text-muted hover:text-ink">Anuluj</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endif
