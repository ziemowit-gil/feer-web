@extends('admin.layout')

@section('title', $campaign->exists ? 'Edytuj zbiórkę' : 'Nowa zbiórka')

@php
    use App\Models\Campaign;
    use App\Support\FundraisingTexts;
    use App\Support\Money;

    $field = 'w-full rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark';
    $label = 'mb-1 block text-sm font-bold';
    $mode = old('payment_mode', $campaign->payment_mode ?? 'online');
    $globalTexts = FundraisingTexts::for(null);
    $onlineSum = $campaign->exists ? (int) $campaign->donations()->where('status', 'paid')->sum('amount_grosze') : 0;
@endphp

@section('content')
    @if ($errors->any())
        <div id="campaign-errors" class="mb-6 rounded-md bg-red-50 p-4 text-sm text-red-800" role="alert" tabindex="-1">
            <p class="font-bold">Popraw zaznaczone pola ({{ $errors->count() }}):</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->messages() as $errField => $errMessages)
                    @php $errId = str_starts_with($errField, 'texts.') ? 'texts-'.substr($errField, 6) : $errField; @endphp
                    <li><a href="#{{ $errId }}" class="underline">{{ $errMessages[0] }}</a></li>
                @endforeach
            </ul>
        </div>
        <script>document.addEventListener('DOMContentLoaded', () => document.getElementById('campaign-errors')?.focus());</script>
    @endif

    <form method="POST" action="{{ $campaign->exists ? route('admin.kampanie.update', $campaign) : route('admin.kampanie.store') }}"
        enctype="multipart/form-data" class="space-y-6">
        @csrf
        @if ($campaign->exists) @method('PUT') @endif

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- ===== LEWA KOLUMNA ===== --}}
            <div class="space-y-6 lg:col-span-2">
                <div class="space-y-5 rounded-md bg-white p-6">
                    <h2 class="text-base font-bold">Podstawowe</h2>

                    <div>
                        <label for="title" class="{{ $label }}">Tytuł zbiórki (nagłówek H1 strony)</label>
                        <input type="text" id="title" @error('title') aria-invalid=\"true\" aria-errormessage=\"title-error\" @enderror name="title" value="{{ old('title', $campaign->title) }}" required class="{{ $field }}">
                        @error('title') <p id="title-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="slug" class="{{ $label }}">Adres (slug)</label>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-ink">/kampanie/</span>
                            <input type="text" id="slug" @error('slug') aria-invalid=\"true\" aria-errormessage=\"slug-error\" @enderror name="slug" value="{{ old('slug', $campaign->slug) }}" placeholder="zostanie wygenerowany z tytułu" class="flex-1 rounded border-gray-500 focus:border-brand-dark focus:ring-brand-dark">
                        </div>
                        @error('slug') <p id="slug-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="excerpt" class="{{ $label }}">Krótki opis (podtytuł i kafelek na liście)</label>
                        <textarea id="excerpt" @error('excerpt') aria-invalid=\"true\" aria-errormessage=\"excerpt-error\" @enderror name="excerpt" rows="2" class="{{ $field }}">{{ old('excerpt', $campaign->excerpt) }}</textarea>
                        @error('excerpt') <p id="excerpt-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="content" class="{{ $label }}">Opis zbiórki</label>
                        <textarea id="content" @error('content') aria-invalid=\"true\" aria-errormessage=\"content-error\" @enderror name="content" rows="10" class="{{ $field }}">{{ old('content', $campaign->content) }}</textarea>
                        @error('content') <p id="content-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="space-y-5 rounded-md bg-white p-6">
                    <h2 class="text-base font-bold">Cel i kwoty</h2>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="goal_amount" class="{{ $label }}">Cel (zł)</label>
                            <input type="number" id="goal_amount" @error('goal_amount') aria-invalid=\"true\" aria-errormessage=\"goal_amount-error\" @enderror name="goal_amount" min="0" value="{{ old('goal_amount', $campaign->goal_amount ?? 0) }}" required class="{{ $field }}" aria-describedby="goal_amount_help">
                            <p id="goal_amount_help" class="mt-1 text-xs text-ink">0 = zbiórka bez celu (bez paska procentowego).</p>
                            @error('goal_amount') <p id="goal_amount-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="external_amount" class="{{ $label }}">Zebrano poza stroną (zł)</label>
                            <input type="number" id="external_amount" @error('external_amount') aria-invalid=\"true\" aria-errormessage=\"external_amount-error\" @enderror name="external_amount" min="0" value="{{ old('external_amount', $campaign->external_amount ?? 0) }}" class="{{ $field }}" aria-describedby="external_amount_help">
                            <p id="external_amount_help" class="mt-1 text-xs text-ink">Np. wpłaty z wplacam.ngo.pl, puszek, przelewów niewpisanych w „Wpłaty”. Doliczane do paska.</p>
                            @error('external_amount') <p id="external_amount-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    @if ($campaign->exists)
                        <p class="rounded-md bg-gray-50 p-4 text-sm text-ink">
                            Zebrano razem: <strong>{{ Money::zl($campaign->collected_amount) }}</strong>
                            (wpłaty zaksięgowane w systemie: {{ Money::grosze($onlineSum) }} + poza stroną: {{ Money::zl($campaign->external_amount) }}).
                            <a href="{{ route('admin.darowizny.index', ['kampania' => $campaign->id]) }}" class="font-bold text-brand-dark underline">Wpłaty tej zbiórki</a>
                        </p>
                    @endif

                    <div>
                        <label for="amount_options" class="{{ $label }}">Proponowane kwoty (zł)</label>
                        <input type="text" id="amount_options" @error('amount_options') aria-invalid=\"true\" aria-errormessage=\"amount_options-error\" @enderror name="amount_options" inputmode="numeric" maxlength="100"
                            value="{{ old('amount_options', $campaign->amount_options) }}" placeholder="{{ implode(', ', Campaign::DEFAULT_AMOUNTS) }}"
                            aria-describedby="amount_options_help" class="{{ $field }}">
                        <p id="amount_options_help" class="mt-1 text-xs text-ink">Oddzielone przecinkami, {{ Campaign::MIN_AMOUNT }}–{{ number_format(Campaign::MAX_AMOUNT, 0, ',', ' ') }} zł, najwyżej 8. Puste = {{ implode(', ', Campaign::DEFAULT_AMOUNTS) }}.</p>
                        @error('amount_options') <p id="amount_options-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        @foreach ([
                            ['allow_custom_amount', 'Pozwól wpisać własną kwotę', $campaign->exists ? $campaign->allow_custom_amount : true],
                            ['allow_recurring', 'Pokaż wybór „co miesiąc” (wpłata cykliczna: pierwsza online, kolejne zleceniem stałym)', $campaign->allow_recurring],
                            ['show_recent_donations', 'Pokaż listę ostatnich wpłat (imię i inicjał albo „Wpłata anonimowa”)', $campaign->exists ? $campaign->show_recent_donations : true],
                        ] as [$name, $text, $current])
                            <label class="flex min-h-11 items-center gap-3 text-sm">
                                <input type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $current)) class="h-5 w-5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                                <span>{{ $text }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <fieldset class="space-y-5 rounded-md bg-white p-6" x-data="{ mode: @js($mode) }">
                    <legend class="sr-only">Sposób przyjmowania wpłat</legend>
                    <h2 class="text-base font-bold" aria-hidden="true">Sposób przyjmowania wpłat</h2>
                    <div class="space-y-2">
                        @foreach (Campaign::PAYMENT_MODES as $value => $text)
                            <label class="flex min-h-11 items-center gap-3 text-sm">
                                <input type="radio" name="payment_mode" value="{{ $value }}" x-model="mode" @checked($mode === $value) class="h-5 w-5 border-gray-500 text-brand-dark focus:ring-brand-dark">
                                <span>{{ $text }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('payment_mode') <p class="text-sm text-red-700">{{ $message }}</p> @enderror

                    <div x-show="mode === 'online'" class="rounded-md bg-gray-50 p-4 text-sm text-ink">
                        Wpłaty przez Przelewy24 z konfiguracji w Ustawienia → Logowanie i integracje → Przelewy24. Opłacone trafiają do „Wpłaty” i rejestru darowizn SZO.
                    </div>

                    <div x-show="mode === 'wplacam'" class="space-y-4" @if ($mode !== 'wplacam') x-cloak @endif>
                        <div>
                            <label for="wplacam_url" class="{{ $label }}">Adres zbiórki w wplacam.ngo.pl</label>
                            <input type="url" id="wplacam_url" @error('wplacam_url') aria-invalid=\"true\" aria-errormessage=\"wplacam_url-error\" @enderror name="wplacam_url" value="{{ old('wplacam_url', $campaign->wplacam_url) }}" placeholder="https://wplacam.ngo.pl/…" class="{{ $field }}">
                            @error('wplacam_url') <p id="wplacam_url-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                        </div>
                        <fieldset>
                            <legend class="{{ $label }}">Jak pokazać wpłatę</legend>
                            @foreach (Campaign::WPLACAM_DISPLAYS as $value => $text)
                                <label class="flex min-h-11 items-center gap-3 text-sm">
                                    <input type="radio" name="wplacam_display" value="{{ $value }}" @checked(old('wplacam_display', $campaign->wplacam_display ?? 'redirect') === $value) class="h-5 w-5 border-gray-500 text-brand-dark focus:ring-brand-dark">
                                    <span>{{ $text }}</span>
                                </label>
                            @endforeach
                            <p class="mt-1 text-xs text-ink">Kwot z wplacam.ngo.pl strona nie widzi — aktualizuj je polem „Zebrano poza stroną”.</p>
                        </fieldset>
                    </div>

                    <div x-show="mode === 'external'" @if ($mode !== 'external') x-cloak @endif>
                        <label for="donation_url" class="{{ $label }}">Adres zewnętrznej zbiórki</label>
                        <input type="url" id="donation_url" @error('donation_url') aria-invalid=\"true\" aria-errormessage=\"donation_url-error\" @enderror name="donation_url" value="{{ old('donation_url', $campaign->donation_url) }}" placeholder="https://…" class="{{ $field }}">
                        @error('donation_url') <p id="donation_url-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                </fieldset>

                <div class="space-y-5 rounded-md bg-white p-6">
                    <h2 class="text-base font-bold">Grafiki</h2>
                    @include('admin.partials.library-image-picker', [
                        'name' => 'image', 'label' => 'Zdjęcie zbiórki', 'current' => $campaign->imageUrl,
                    ])
                    @if ($campaign->imageUrl)
                        <label class="flex items-center gap-2 text-sm text-red-700"><input type="checkbox" name="remove_image" value="1" class="rounded border-gray-500"> Usuń zdjęcie zbiórki</label>
                    @endif
                    <div>
                        <label for="image_alt" class="{{ $label }}">Opis alternatywny zdjęcia (ALT)</label>
                        <input type="text" id="image_alt" name="image_alt" maxlength="255" value="{{ old('image_alt', $campaign->image_alt) }}" class="{{ $field }}" aria-describedby="image_alt_help">
                        <p id="image_alt_help" class="mt-1 text-xs text-ink">Opisz, co widać na zdjęciu. Puste = zdjęcie dekoracyjne.</p>
                    </div>

                    @include('admin.partials.library-image-picker', [
                        'name' => 'thanks_image', 'label' => 'Zdjęcie na stronie podziękowania (nadpisuje globalne)',
                        'current' => $campaign->getFirstMediaUrl('thanks_image') ?: null,
                        'help' => 'Opis alternatywny ustawisz w Teksty → Podziękowanie: wpłata przyjęta.',
                    ])
                    @if ($campaign->getFirstMediaUrl('thanks_image'))
                        <label class="flex items-center gap-2 text-sm text-red-700"><input type="checkbox" name="remove_thanks_image" value="1" class="rounded border-gray-500"> Usuń zdjęcie podziękowania</label>
                    @endif

                    @include('admin.partials.library-image-picker', [
                        'name' => 'thanks_email_image', 'label' => 'Grafika e-maila z podziękowaniem (nadpisuje globalną)',
                        'current' => $campaign->getFirstMediaUrl('thanks_email_image') ?: null,
                        'help' => 'Opis alternatywny ustawisz w Teksty → E-mail z podziękowaniem.',
                    ])
                    @if ($campaign->getFirstMediaUrl('thanks_email_image'))
                        <label class="flex items-center gap-2 text-sm text-red-700"><input type="checkbox" name="remove_thanks_email_image" value="1" class="rounded border-gray-500"> Usuń grafikę e-maila</label>
                    @endif
                </div>

                <div class="rounded-md bg-white p-6">
                    <h2 class="text-base font-bold">Teksty tej zbiórki</h2>
                    <p class="mt-1 text-sm text-ink">Nadpisują <a href="{{ route('admin.zbiorki-teksty.edit') }}" class="font-bold text-brand-dark underline">teksty globalne</a> tylko na tej zbiórce.</p>
                    <div class="mt-4">
                        @include('admin.fundraising.partials.texts-fields', [
                            'groups' => FundraisingTexts::CAMPAIGN_GROUPS,
                            'values' => $campaign->texts ?? [],
                            'inherit' => fn ($key) => $globalTexts->globalValue($key),
                            'inheritLabel' => 'z ustawień globalnych',
                        ])
                    </div>
                </div>

                <div class="space-y-4 rounded-md bg-white p-6">
                    <h2 class="text-base font-bold">SEO</h2>
                    <div>
                        <label for="meta_title" class="{{ $label }}">Meta tytuł</label>
                        <input type="text" id="meta_title" name="meta_title" value="{{ old('meta_title', $campaign->meta_title) }}" class="{{ $field }}">
                    </div>
                    <div>
                        <label for="meta_description" class="{{ $label }}">Meta opis</label>
                        <textarea id="meta_description" name="meta_description" rows="2" class="{{ $field }}">{{ old('meta_description', $campaign->meta_description) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- ===== PRAWA KOLUMNA ===== --}}
            <div class="space-y-6">
                <div class="space-y-4 rounded-md bg-white p-6">
                    <h2 class="text-base font-bold">Publikacja</h2>
                    <div>
                        <label for="status" class="{{ $label }}">Status</label>
                        <select id="status" @error('status') aria-invalid=\"true\" aria-errormessage=\"status-error\" @enderror name="status" class="{{ $field }}" aria-describedby="status_help">
                            @foreach (Campaign::STATUSES as $value => $text)
                                <option value="{{ $value }}" @selected(old('status', $campaign->status ?? 'draft') === $value)>{{ $text }}</option>
                            @endforeach
                        </select>
                        <p id="status_help" class="mt-1 text-xs text-ink">Zakończona zostaje na stronie, ale nie przyjmuje wpłat.</p>
                        @error('status') <p id="status-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    </div>
                    <label class="flex min-h-11 items-center gap-3 text-sm font-bold">
                        <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $campaign->is_featured)) class="h-5 w-5 rounded border-gray-500 text-brand-dark focus:ring-brand-dark">
                        Wyróżniona (pierwsza na liście)
                    </label>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                        <div>
                            <label for="starts_at" class="{{ $label }}">Początek</label>
                            <input type="date" id="starts_at" @error('starts_at') aria-invalid=\"true\" aria-errormessage=\"starts_at-error\" @enderror name="starts_at" value="{{ old('starts_at', $campaign->starts_at?->format('Y-m-d')) }}" class="{{ $field }}">
                            @error('starts_at') <p id="starts_at-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="ends_at" class="{{ $label }}">Koniec</label>
                            <input type="date" id="ends_at" @error('ends_at') aria-invalid=\"true\" aria-errormessage=\"ends_at-error\" @enderror name="ends_at" value="{{ old('ends_at', $campaign->ends_at?->format('Y-m-d')) }}" class="{{ $field }}">
                            @error('ends_at') <p id="ends_at-error" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label for="order" class="{{ $label }}">Kolejność</label>
                        <input type="number" id="order" name="order" min="0" value="{{ old('order', $campaign->order ?? 0) }}" class="{{ $field }}">
                    </div>

                    @if ($campaign->exists)
                        <div class="rounded-md bg-gray-50 p-4">
                            @include('campaigns.partials.progress', ['campaign' => $campaign, 'texts' => $globalTexts, 'compact' => true])
                        </div>
                        @if ($campaign->is_published)
                            <a href="{{ route('kampanie.show', $campaign->slug) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-sm font-bold text-brand-dark underline">
                                <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Zobacz na stronie<span class="sr-only"> (otwiera się w nowej karcie)</span>
                            </a>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="rounded bg-brand-dark px-6 py-2.5 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2">
                {{ $campaign->exists ? 'Zapisz zmiany' : 'Utwórz zbiórkę' }}
            </button>
            <a href="{{ route('admin.kampanie.index') }}" class="rounded-md bg-white px-6 py-2.5 text-sm font-bold text-ink hover:bg-brand-light">Anuluj</a>
        </div>
    </form>
@endsection
