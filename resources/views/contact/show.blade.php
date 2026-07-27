@extends('layouts.site')

@section('title', 'Kontakt — ' . $siteSettings->site_name)
@section('meta_description', 'Skontaktuj się z ' . $siteSettings->site_name . '.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => 'Kontakt', 'url' => null],
    ]])
@endsection

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-12">
        <h1 class="mb-6 text-3xl font-bold text-ink">Kontakt</h1>

        @if ($siteSettings->contact_intro)
            <div class="prose mb-8 max-w-2xl text-muted">{!! $siteSettings->contact_intro !!}</div>
        @endif

        <div class="grid gap-10 md:grid-cols-[1fr_300px]">
            <div>
                @if (session('status'))
                    <div class="mb-6 rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('contact.store') }}" class="max-w-xl space-y-5">
                    @csrf

                    <div class="hidden" aria-hidden="true">
                        <label for="website">Zostaw to pole puste</label>
                        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div>
                        <label for="name" class="mb-1 block text-sm font-bold">Imię i nazwisko</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="email" class="mb-1 block text-sm font-bold">E-mail</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                        @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="message" class="mb-1 block text-sm font-bold">Wiadomość</label>
                        <textarea id="message" name="message" rows="6" required
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('message') }}</textarea>
                        @error('message') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="flex items-start gap-2">
                            <input type="checkbox" name="rodo_consent" value="1" required {{ old('rodo_consent') ? 'checked' : '' }}
                                class="mt-1 flex-none rounded border-gray-300 text-brand focus:ring-brand">
                            <span class="text-sm text-muted">
                                Wyrażam zgodę na przetwarzanie moich danych osobowych (imienia i adresu e-mail) w celu udzielenia odpowiedzi na przesłaną wiadomość, zgodnie z
                                <a href="{{ route('page.show', 'polityka-prywatnosci') }}" class="font-bold text-brand hover:text-brand-dark">Polityką prywatności</a>.
                                <span aria-hidden="true" class="font-bold text-red-600">*</span>
                            </span>
                        </label>
                        @error('rodo_consent') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <p class="text-xs text-muted">
                        Administratorem Twoich danych osobowych jest {{ $siteSettings->site_name }}. Dane z formularza przetwarzamy wyłącznie w celu obsługi Twojego zapytania.
                        Masz prawo dostępu do danych, ich sprostowania, usunięcia oraz wycofania zgody w dowolnym momencie. Szczegóły znajdziesz w
                        <a href="{{ route('page.show', 'polityka-prywatnosci') }}" class="font-bold text-brand hover:text-brand-dark">Polityce prywatności</a>.
                    </p>

                    <button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">Wyślij wiadomość</button>
                </form>
            </div>

            <aside aria-label="Dane kontaktowe">
                <p class="mb-5 text-xl font-bold text-ink">{{ $siteSettings->site_name }}</p>
                <ul class="space-y-5">
                        <li>
                            <a href="https://www.google.com/maps?q={{ urlencode($siteSettings->contact_address.', '.$siteSettings->contact_city) }}" target="_blank" rel="noopener"
                                class="group flex items-start gap-3">
                                <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-brand-light text-brand" aria-hidden="true">
                                    <i class="fa-solid fa-location-dot"></i>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-xs font-bold uppercase tracking-wide text-muted">Adres</span>
                                    <span class="font-medium text-ink group-hover:text-brand">{{ $siteSettings->contact_address }}<br>{{ $siteSettings->contact_city }}</span>
                                </span>
                            </a>
                        </li>
                        <li>
                            <a href="mailto:{{ $siteSettings->contact_email }}" class="group flex items-start gap-3">
                                <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-brand-light text-brand" aria-hidden="true">
                                    <i class="fa-solid fa-envelope"></i>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-xs font-bold uppercase tracking-wide text-muted">E-mail</span>
                                    <span class="block break-all font-medium text-ink group-hover:text-brand">{{ $siteSettings->contact_email }}</span>
                                </span>
                            </a>
                        </li>
                        @if ($siteSettings->contact_phone)
                            <li>
                                <a href="tel:{{ $siteSettings->contact_phone }}" class="group flex items-start gap-3">
                                    <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-brand-light text-brand" aria-hidden="true">
                                        <i class="fa-solid fa-phone"></i>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block text-xs font-bold uppercase tracking-wide text-muted">Telefon</span>
                                        <span class="font-medium text-ink group-hover:text-brand">{{ $siteSettings->contact_phone }}</span>
                                    </span>
                                </a>
                            </li>
                        @endif
                        @if ($siteSettings->contact_edelivery_address)
                            <li class="flex items-start gap-3">
                                <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-brand-light text-brand" aria-hidden="true">
                                    <i class="fa-solid fa-envelope-circle-check"></i>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-xs font-bold uppercase tracking-wide text-muted">Adres do e-Doręczeń</span>
                                    <span class="block break-all font-mono text-sm font-medium text-ink">{{ $siteSettings->contact_edelivery_address }}</span>
                                </span>
                            </li>
                        @endif
                    </ul>

                    @if ($siteSettings->contactBoxIsVisible())
                        <div class="mt-8 rounded-xl border border-brand/30 bg-brand-light p-5">
                            @if ($siteSettings->contact_box_text)
                                <p class="text-sm text-ink">{{ $siteSettings->contact_box_text }}</p>
                            @endif
                            @if ($siteSettings->contact_box_link_url && $siteSettings->contact_box_link_label)
                                @php $external = \Illuminate\Support\Str::startsWith($siteSettings->contact_box_link_url, ['http://', 'https://']); @endphp
                                <a href="{{ $siteSettings->contact_box_link_url }}" @if ($external) target="_blank" rel="noopener" @endif
                                    class="{{ $siteSettings->contact_box_text ? 'mt-3' : '' }} inline-flex items-center gap-2 rounded-full bg-brand px-4 py-2 text-sm font-bold text-white hover:bg-brand-dark">
                                    {{ $siteSettings->contact_box_link_label }}
                                    <i class="fa-solid {{ $external ? 'fa-arrow-up-right-from-square' : 'fa-arrow-right' }}" aria-hidden="true"></i>
                                </a>
                            @endif
                        </div>
                    @endif
            </aside>
        </div>

        @php
            $meetingTitle = $siteSettings->contact_meeting_title ?: 'Spotkajmy się';
            $onlineUrl = $siteSettings->contact_online_meeting_url;
            $onlineLabel = $siteSettings->contact_online_meeting_label ?: 'Wybierz dogodny termin';
            $onlineText = $siteSettings->contact_online_meeting_text ?: 'Najwygodniej spotkać się online — umów rozmowę w dogodnym dla Ciebie terminie.';
            $remoteNote = $siteSettings->contact_remote_note;
            $scheduleTitle = $siteSettings->contact_schedule_title ?: 'Kiedy i gdzie jesteśmy w Krakowie';
            $scheduleEnabled = $siteSettings->contact_schedule_enabled;
            $scheduleItems = $siteSettings->contactScheduleUpcoming();
            $showOnline = filled($onlineUrl);
            $showSchedule = $scheduleEnabled && ! empty($scheduleItems);
            // Gdy wybór terminu wyłączony — zamiast harmonogramu i przycisków komunikat.
            $showNoSchedule = ! $scheduleEnabled;
            $noScheduleNote = $siteSettings->contact_no_schedule_note ?: 'Jeszcze nie ustaliliśmy żadnych terminów.';
            $onlineExternal = $showOnline && \Illuminate\Support\Str::startsWith($onlineUrl, ['http://', 'https://']);
        @endphp

        @if ($showOnline || $showSchedule || $showNoSchedule || filled($remoteNote))
            <div id="spotkania" class="mt-12 scroll-mt-24 border-t border-gray-100 pt-8">
                <h2 class="mb-4 text-xl font-bold text-ink">{{ $meetingTitle }}</h2>

                {{-- Zaproszenie na spotkanie online — wyróżniony baner (hero) --}}
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

                {{-- Wybór terminu wyłączony w ustawieniach — komunikat zamiast przycisków. --}}
                @if ($showNoSchedule)
                    <div class="flex flex-col gap-4 rounded-lg border border-gray-200 p-5 sm:flex-row sm:items-center">
                        <span class="flex h-11 w-11 flex-none items-center justify-center rounded-full bg-brand-light text-brand" aria-hidden="true">
                            <i class="fa-solid fa-calendar-xmark"></i>
                        </span>
                        <div>
                            <h3 class="text-lg font-bold text-ink">{{ $scheduleTitle }}</h3>
                            <p class="mt-0.5 max-w-xl text-sm text-muted">{{ $noScheduleNote }}</p>
                        </div>
                    </div>
                @endif

                {{-- W Krakowie: harmonogram + formularz w wyskakującym okienku (modal).
                     Modal otwiera się sam, gdy walidacja formularza zwróci błędy. --}}
                @if ($showSchedule)
                    <div x-data="{ open: {{ $errors->meeting->isNotEmpty() ? 'true' : 'false' }} }"
                        x-effect="document.body.style.overflow = open ? 'hidden' : ''">

                        @if (session('meeting_signed_up'))
                            <div class="mb-4 flex max-w-2xl items-start gap-2 rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                                <i class="fa-solid fa-circle-check mt-0.5" aria-hidden="true"></i>
                                <span>Dziękujemy! Zapisaliśmy Twoje zgłoszenie. Jeśli termin się zmieni — damy znać e-mailem.</span>
                            </div>
                        @endif

                        {{-- Wyzwalacz modala --}}
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

                        {{-- Modal --}}
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
                                                <label for="mtg-email" class="mb-1 block text-sm font-bold">E-mail</label>
                                                <input type="email" id="mtg-email" name="email" value="{{ old('email') }}" required
                                                    class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                                                @error('email', 'meeting') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                            </div>
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

        @php
            $shipNote = $siteSettings->contact_shipping_note;
            $pkCode = $siteSettings->contact_paczkomat_code;
            $pkAddr = $siteSettings->contact_paczkomat_address;
            $pkLoc = $siteSettings->contact_paczkomat_location;
            $shipPhone = $siteSettings->contact_shipping_phone;
            $showShipping = $siteSettings->contact_shipping_visible && (filled($shipNote) || filled($pkCode) || filled($pkAddr) || filled($shipPhone));
        @endphp

        @if ($showShipping)
            <div class="mt-12 border-t border-gray-100 pt-8">
                <h2 class="mb-2 text-lg font-bold text-ink">Wyślij do nas przesyłkę</h2>
                <p class="mb-4 max-w-2xl text-sm text-muted">{{ $shipNote ?: 'Możesz nadać do nas paczkę lub list — również na paczkomat.' }}</p>

                <div class="rounded-lg border border-gray-200 p-4">
                    <div class="mb-3 flex items-center gap-2">
                        <span class="flex h-8 w-8 flex-none items-center justify-center rounded-full bg-brand-light text-sm text-brand" aria-hidden="true">
                            <i class="fa-solid fa-box-open"></i>
                        </span>
                        <h3 class="text-sm font-bold text-ink">Paczkomat InPost</h3>
                    </div>

                    <div class="grid gap-x-8 gap-y-3 sm:grid-cols-2">
                        @if (filled($pkCode))
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-muted">Kod paczkomatu</p>
                                <div class="mt-0.5 flex items-baseline gap-2">
                                    <p class="font-mono text-base font-bold text-ink">{{ $pkCode }}</p>
                                    <button type="button" data-copy-button data-copy-value="{{ $pkCode }}"
                                        class="inline-flex items-center gap-1 text-xs font-bold text-brand hover:text-brand-dark">
                                        <i class="fa-regular fa-copy" aria-hidden="true"></i> Kopiuj
                                    </button>
                                </div>
                            </div>
                        @endif

                        <ul class="space-y-1 text-sm">
                            @if (filled($pkAddr))
                                <li class="flex items-start gap-2">
                                    <i class="fa-solid fa-location-dot mt-0.5 w-4 flex-none text-center text-muted" aria-hidden="true"></i>
                                    <span class="text-ink">{{ $pkAddr }}</span>
                                </li>
                            @endif
                            @if (filled($pkLoc))
                                <li class="flex items-start gap-2">
                                    <i class="fa-solid fa-circle-info mt-0.5 w-4 flex-none text-center text-muted" aria-hidden="true"></i>
                                    <span class="text-muted">{{ $pkLoc }}</span>
                                </li>
                            @endif
                            @if (filled($shipPhone))
                                <li class="flex items-start gap-2">
                                    <i class="fa-solid fa-phone mt-0.5 w-4 flex-none text-center text-muted" aria-hidden="true"></i>
                                    <a href="tel:{{ $shipPhone }}" class="font-medium text-ink hover:text-brand">{{ $shipPhone }}</a>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        @if (!empty($siteSettings->contact_bank_accounts))
            <div class="mt-12 border-t border-gray-100 pt-8">
                <h2 class="mb-2 text-xl font-bold text-ink">Numery rachunków bankowych</h2>
                <p class="mb-5 max-w-2xl text-sm text-muted">Przy każdym rachunku opisujemy, do czego służy i co można na niego wpłacić.</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($siteSettings->contact_bank_accounts as $account)
                        <div class="flex items-start gap-4 rounded-2xl border border-gray-200 bg-gray-50/60 p-5">
                            <span class="flex h-11 w-11 flex-none items-center justify-center rounded-full bg-brand-light text-brand" aria-hidden="true">
                                <i class="fa-solid fa-building-columns"></i>
                            </span>
                            <div class="min-w-0">
                                @if (!empty($account['purpose']))
                                    <p class="font-bold text-ink">{{ $account['purpose'] }}</p>
                                @endif
                                {{-- Pełny numer w jednej linii; na wąskich ekranach przewija się poziomo we własnym polu zamiast zawijać. --}}
                                <p class="{{ !empty($account['purpose']) ? 'mt-1' : '' }} overflow-x-auto whitespace-nowrap font-mono text-sm text-ink">{{ $account['number'] }}</p>
                                <button type="button" data-copy-button data-copy-value="{{ $account['number'] }}"
                                    class="mt-2.5 inline-flex items-center gap-1.5 rounded-full border border-brand px-3 py-1 text-xs font-bold text-brand transition hover:bg-brand-light">
                                    <i class="fa-regular fa-copy" aria-hidden="true"></i> Kopiuj numer
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($projects->isNotEmpty())
            <div class="mt-12 border-t border-gray-100 pt-8">
                <h2 class="mb-4 text-xl font-bold text-ink"> Koordynatorzy poszczególnych działań</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($projects as $project)
                        <div class="rounded-lg border p-4 text-sm {{ $project->is_featured_contact ? 'border-brand/30 bg-brand-light ring-1 ring-brand/20' : 'border-gray-200' }}">
                            @if ($project->is_featured_contact)
                                <span class="mb-2 inline-flex items-center gap-1 rounded-full bg-brand px-2.5 py-0.5 text-xs font-bold text-white">
                                    <i class="fa-solid fa-star" aria-hidden="true"></i> Wyróżniony kontakt
                                </span>
                            @endif
                            <a href="{{ route('projects.show', $project) }}" class="block font-bold text-ink hover:text-brand">{{ $project->title }}</a>
                            @if ($project->coordinator_name)
                                <p class="text-muted">{{ $project->coordinator_name }}</p>
                            @endif
                            <p><a href="mailto:{{ $project->contactEmail() }}" class="text-brand hover:text-brand-dark">{{ $project->contactEmail() }}</a></p>
                            @if ($project->coordinator_phone)
                                <p><a href="tel:{{ $project->coordinator_phone }}" class="text-brand hover:text-brand-dark">{{ $project->coordinator_phone }}</a></p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    @if (!empty($siteSettings->contact_bank_accounts) || filled($siteSettings->contact_paczkomat_code))
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
@endsection
