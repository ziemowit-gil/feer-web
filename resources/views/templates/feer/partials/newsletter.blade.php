{{--
    Szablon FEER — newsletter na dole strony głównej: zwarty zapis na miejscu (e-mail, krótkie zabezpieczenie
    antyspamowe i zgoda) do domyślnego formularza modułu Newsletter. Tematy: domyślne z formularza (ukryte),
    resztę ustawień subskrybent zmieni w linku z e-maila. Bez aktywnego formularza modułu — sekcji nie ma.
    WCAG: widoczne etykiety, błędy przy polach (aria-invalid + aria-describedby), komunikaty z serwera w role="alert".
--}}
@php
    $nlForm = $siteSettings->isModuleEnabled('newsletter') && class_exists(\Modules\Newsletter\Models\NewsletterForm::class)
        && \Illuminate\Support\Facades\Schema::hasTable('newsletter_forms')
        ? \Modules\Newsletter\Models\NewsletterForm::defaultFor($siteSettings->id) : null;
@endphp
@if ($nlForm && $nlForm->is_active)
    @php
        $nlChallenge = \App\Support\SpamGuard::challenge();
        $nlTopics = $nlForm->show_topics ? ($nlForm->default_topics ?: array_keys($nlForm->topicOptions())) : [];
    @endphp
    <section class="bg-brand-light py-12" aria-labelledby="feer-nl-heading">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] lg:items-center">
            <div>
                <h2 id="feer-nl-heading" class="text-2xl font-bold text-ink md:text-3xl">{{ $nlForm->heading ?: 'Newsletter' }}</h2>
                <span class="mt-3 block h-1 w-14 bg-brand" aria-hidden="true"></span>
                <p class="mt-4 text-ink">Raz w miesiącu: aktualności, szkolenia i materiały. Bez spamu, wypis jednym kliknięciem.</p>
            </div>

            <form action="{{ route('newsletter.subscribe') }}" method="post" class="rounded-md bg-white p-6">
                @csrf
                <input type="hidden" name="form_id" value="{{ $nlForm->id }}">
                <input type="hidden" name="source" value="home_compact">
                <input type="hidden" name="{{ \App\Support\SpamGuard::TOKEN_FIELD }}" value="{{ $nlChallenge['token'] }}">
                @foreach ($nlTopics as $topic)<input type="hidden" name="topics[]" value="{{ $topic }}">@endforeach
                <div class="absolute -left-[9999px]" aria-hidden="true">
                    <label for="feer-nl-website">Strona WWW</label>
                    <input id="feer-nl-website" type="text" name="{{ \App\Support\SpamGuard::HONEYPOT_FIELD }}" tabindex="-1" autocomplete="off">
                </div>

                @if ($errors->hasAny(['email', 'consent', 'form_answer', 'topics']))
                    <p class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm font-bold text-red-800" role="alert">Sprawdź pola zaznaczone poniżej.</p>
                @endif

                <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_9rem]">
                    <div>
                        <label for="feer-nl-email" class="mb-1 block text-sm font-bold text-ink">Adres e-mail</label>
                        <input id="feer-nl-email" type="email" name="email" value="{{ old('email') }}" required aria-required="true" autocomplete="email" inputmode="email"
                            @error('email') aria-invalid="true" aria-describedby="feer-nl-email-err" @enderror
                            class="min-h-11 w-full rounded-t-md bg-gray-100 text-base text-ink focus:bg-white focus:ring-2 focus:ring-brand-dark" style="border:0;box-shadow:inset 0 -2px 0 #4b5563">
                        @error('email')<p id="feer-nl-email-err" class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="feer-nl-answer" class="mb-1 block text-sm font-bold text-ink">{{ $nlChallenge['question'] }}</label>
                        <input id="feer-nl-answer" type="text" name="{{ \App\Support\SpamGuard::ANSWER_FIELD }}" required aria-required="true" autocomplete="off" inputmode="numeric"
                            @error('form_answer') aria-invalid="true" aria-describedby="feer-nl-answer-err" @enderror
                            class="min-h-11 w-full rounded-t-md bg-gray-100 text-base text-ink focus:bg-white focus:ring-2 focus:ring-brand-dark" style="border:0;box-shadow:inset 0 -2px 0 #4b5563">
                        @error('form_answer')<p id="feer-nl-answer-err" class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>
                </div>

                <label class="mt-4 flex items-start gap-3 text-sm text-ink">
                    <input type="checkbox" name="consent" value="1" required aria-required="true" @checked(old('consent'))
                        @error('consent') aria-invalid="true" aria-describedby="feer-nl-consent-err" @enderror
                        class="mt-0.5 h-5 w-5 flex-none rounded text-brand-dark focus:ring-brand-dark">
                    <span>{{ $nlForm->consent_text }}@if ($nlForm->privacy_url) <a href="{{ $nlForm->privacy_url }}" class="font-bold text-brand-dark underline underline-offset-2 hover:no-underline">Jak przetwarzamy dane</a>.@endif</span>
                </label>
                @error('consent')<p id="feer-nl-consent-err" class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror

                <button type="submit" class="mt-5 inline-flex min-h-11 items-center gap-2 rounded-md bg-brand-dark px-6 font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                    {{ $nlForm->button_label ?: 'Zapisz się' }} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </button>
            </form>
        </div>
    </section>
@endif
