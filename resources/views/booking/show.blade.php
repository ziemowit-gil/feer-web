@extends('layouts.site')

@section('title', ($siteSettings->contact_meeting_title ?: 'Umów spotkanie') . ' — ' . $siteSettings->site_name)
@section('meta_description', 'Zarezerwuj termin spotkania z ' . $siteSettings->site_name . '. Formularz działa przez całą dobę.')

@section('breadcrumbs')
    @include('partials.breadcrumbs', ['items' => [
        ['label' => $siteSettings->contact_meeting_title ?: 'Umów spotkanie', 'url' => null],
    ]])
@endsection

@section('content')
    @php
        $meetingTitle   = $siteSettings->contact_meeting_title ?: 'Umów spotkanie';
        $scheduleTitle  = $siteSettings->contact_schedule_title ?: 'Kiedy i gdzie jesteśmy';
        $scheduleEnabled = $siteSettings->contact_schedule_enabled;
        $hasSchedule    = $scheduleEnabled && ! empty($scheduleItems);
        $onlineUrl      = $siteSettings->contact_online_meeting_url;
        $onlineLabel    = $siteSettings->contact_online_meeting_label ?: 'Wybierz termin online';
        $onlineText     = $siteSettings->contact_online_meeting_text ?: 'Najwygodniej spotkać się online — umów rozmowę w dogodnym dla Ciebie terminie.';
        $onlineExternal = filled($onlineUrl) && \Illuminate\Support\Str::startsWith($onlineUrl, ['http://', 'https://']);
    @endphp

    <div class="mx-auto max-w-5xl px-4 py-10">
        <h1 class="mb-2 text-3xl font-bold text-ink">{{ $meetingTitle }}</h1>
        <p class="mb-8 max-w-2xl text-muted">Wypełnij formularz, żebyśmy wiedzieli, że przyjdziesz. Jeśli termin się zmieni, poinformujemy Cię e-mailem.</p>

        @if (session('booking_signed_up'))
            <div class="mb-8 flex items-start gap-3 rounded-lg border border-green-200 bg-green-50 px-5 py-4 text-green-800" role="alert">
                <i class="fa-solid fa-circle-check mt-0.5 flex-none text-green-600 text-lg" aria-hidden="true"></i>
                <div>
                    <p class="font-bold">Zgłoszenie przyjęte!</p>
                    <p class="mt-0.5 text-sm">Dziękujemy — zapisaliśmy Twoje zgłoszenie. Jeśli termin się zmieni, damy znać e-mailem.</p>
                </div>
            </div>
        @endif

        {{-- Zaproszenie online --}}
        @if (filled($onlineUrl))
            <div class="mb-8 flex flex-col gap-4 rounded-xl bg-linear-to-br from-brand to-brand-dark p-6 text-white sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <span class="flex h-12 w-12 flex-none items-center justify-center rounded-full bg-white/15 text-xl" aria-hidden="true">
                        <i class="fa-solid fa-video"></i>
                    </span>
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

        <div class="grid gap-10 lg:grid-cols-[1fr_380px]">

            {{-- Formularz --}}
            <div>
                <h2 class="mb-5 text-xl font-bold text-ink">Daj znać, że przyjdziesz</h2>

                <form method="POST" action="{{ route('booking.store') }}" class="space-y-5" novalidate>
                    @csrf

                    <div class="hidden" aria-hidden="true">
                        <label for="bk-website">Zostaw to pole puste</label>
                        <input type="text" id="bk-website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="bk-name" class="mb-1 block text-sm font-bold">
                                Imię i nazwisko <span class="font-bold text-red-600" aria-hidden="true">*</span>
                            </label>
                            <input type="text" id="bk-name" name="name" value="{{ old('name') }}" required autocomplete="name"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand @error('name', 'booking') border-red-400 @enderror">
                            @error('name', 'booking')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="bk-phone" class="mb-1 block text-sm font-bold">
                                Telefon <span class="font-normal text-muted">(opcjonalnie)</span>
                            </label>
                            <input type="tel" id="bk-phone" name="phone" value="{{ old('phone') }}" autocomplete="tel"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand @error('phone', 'booking') border-red-400 @enderror">
                            @error('phone', 'booking')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="bk-email" class="mb-1 block text-sm font-bold">
                            E-mail <span class="font-bold text-red-600" aria-hidden="true">*</span>
                        </label>
                        <input type="email" id="bk-email" name="email" value="{{ old('email') }}" required autocomplete="email"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand @error('email', 'booking') border-red-400 @enderror">
                        @error('email', 'booking')
                            <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    @if ($hasSchedule)
                        <div>
                            <label for="bk-term" class="mb-1 block text-sm font-bold">
                                Którego terminu dotyczy? <span class="font-normal text-muted">(opcjonalnie)</span>
                            </label>
                            <select id="bk-term" name="term"
                                class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                                <option value="">— wybierz termin —</option>
                                @foreach ($scheduleItems as $item)
                                    @php $termLabel = trim($item['when_label'] . ($item['where'] !== '' ? ' — ' . $item['where'] : '')); @endphp
                                    <option value="{{ $termLabel }}" @selected(old('term') === $termLabel)>{{ $termLabel }}</option>
                                @endforeach
                                <option value="Nie wiem jeszcze / inny termin" @selected(old('term') === 'Nie wiem jeszcze / inny termin')>Nie wiem jeszcze / inny termin</option>
                            </select>
                            @error('term', 'booking')
                                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <div>
                        <label for="bk-message" class="mb-1 block text-sm font-bold">
                            Wiadomość <span class="font-normal text-muted">(opcjonalnie)</span>
                        </label>
                        <textarea id="bk-message" name="message" rows="4"
                            class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand @error('message', 'booking') border-red-400 @enderror">{{ old('message') }}</textarea>
                        @error('message', 'booking')
                            <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="flex items-start gap-2.5">
                            <input type="checkbox" name="rodo_consent" value="1" required {{ old('rodo_consent') ? 'checked' : '' }}
                                class="mt-1 flex-none rounded border-gray-300 text-brand focus:ring-brand">
                            <span class="text-sm text-muted">
                                Wyrażam zgodę na przetwarzanie moich danych osobowych (imienia i adresu e-mail) w celu obsługi zgłoszenia i poinformowania mnie o ewentualnej zmianie terminu, zgodnie z
                                <a href="{{ route('page.show', 'polityka-prywatnosci') }}" class="font-bold text-brand hover:text-brand-dark">Polityką prywatności</a>.
                                <span aria-hidden="true" class="font-bold text-red-600">*</span>
                            </span>
                        </label>
                        @error('rodo_consent', 'booking')
                            <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <p class="text-xs text-muted">
                        Administratorem Twoich danych osobowych jest {{ $siteSettings->site_name }}. Pola oznaczone gwiazdką (<span class="font-bold text-red-600">*</span>) są wymagane.
                        Masz prawo dostępu do danych, ich sprostowania oraz wycofania zgody.
                        Szczegóły w <a href="{{ route('page.show', 'polityka-prywatnosci') }}" class="font-bold text-brand hover:text-brand-dark">Polityce prywatności</a>.
                    </p>

                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded bg-brand px-6 py-2.5 text-sm font-bold text-white hover:bg-brand-dark focus:outline-none focus:ring-2 focus:ring-brand focus:ring-offset-2">
                        <i class="fa-solid fa-calendar-check" aria-hidden="true"></i> Wyślij zgłoszenie
                    </button>
                </form>
            </div>

            {{-- Harmonogram terminów --}}
            @if ($hasSchedule)
                <aside aria-label="{{ $scheduleTitle }}">
                    <h2 class="mb-4 text-xl font-bold text-ink">{{ $scheduleTitle }}</h2>
                    <ul class="space-y-3">
                        @foreach ($scheduleItems as $item)
                            <li class="rounded-lg border p-4 {{ $item['is_next'] ? 'border-brand/40 bg-brand-light/50' : 'border-gray-200' }}">
                                @if ($item['is_next'])
                                    <span class="mb-2 inline-flex items-center gap-1 rounded-full bg-brand px-2.5 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide text-white">
                                        <i class="fa-regular fa-star" aria-hidden="true"></i> Najbliższy termin
                                    </span>
                                @endif
                                <p class="font-bold text-ink">
                                    <i class="fa-regular fa-calendar mr-1.5 text-muted" aria-hidden="true"></i>{{ $item['when_label'] }}
                                    @if ($item['recurring'])
                                        <span class="ml-1 font-normal text-muted">(cyklicznie)</span>
                                    @endif
                                </p>
                                @if ($item['where'] !== '')
                                    <p class="mt-1 text-sm text-ink">
                                        <i class="fa-solid fa-location-dot mr-1.5 text-muted" aria-hidden="true"></i>{{ $item['where'] }}
                                    </p>
                                @endif
                                @if ($item['note'] !== '')
                                    <p class="mt-1 text-xs text-muted">{{ $item['note'] }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-6 rounded-lg border border-gray-100 bg-gray-50 p-4 text-sm text-muted">
                        <p class="flex items-start gap-2">
                            <i class="fa-solid fa-circle-info mt-0.5 flex-none text-brand" aria-hidden="true"></i>
                            <span>Terminy aktualizujemy na bieżąco. Jeśli wybierzesz konkretną datę, powiadomimy Cię e-mailem o ewentualnych zmianach.</span>
                        </p>
                    </div>

                    @if ($siteSettings->contact_email)
                        <div class="mt-4">
                            <a href="mailto:{{ $siteSettings->contact_email }}"
                                class="inline-flex items-center gap-2 text-sm font-bold text-brand hover:text-brand-dark">
                                <i class="fa-solid fa-envelope" aria-hidden="true"></i> {{ $siteSettings->contact_email }}
                            </a>
                        </div>
                    @endif
                </aside>
            @elseif (! $scheduleEnabled)
                <aside>
                    <div class="rounded-lg border border-gray-200 p-5">
                        <span class="mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-brand-light text-brand" aria-hidden="true">
                            <i class="fa-solid fa-calendar-days"></i>
                        </span>
                        <h2 class="text-base font-bold text-ink">{{ $scheduleTitle }}</h2>
                        <p class="mt-1 text-sm text-muted">{{ $siteSettings->contact_no_schedule_note ?: 'Jeszcze nie ustaliliśmy żadnych terminów — wypełnij formularz, a odezwiemy się do Ciebie.' }}</p>
                        @if ($siteSettings->contact_email)
                            <a href="mailto:{{ $siteSettings->contact_email }}"
                                class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-brand hover:text-brand-dark">
                                <i class="fa-solid fa-envelope" aria-hidden="true"></i> {{ $siteSettings->contact_email }}
                            </a>
                        @endif
                    </div>
                </aside>
            @endif
        </div>
    </div>
@endsection
