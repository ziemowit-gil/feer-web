@extends('admin.layout')

@section('title', $course->exists ? 'Edytuj szkolenie' : 'Nowe szkolenie')

@php
    $in = 'mt-1 block w-full rounded border-gray-500 text-sm focus:border-brand-dark focus:ring-brand-dark';
    $lbl = 'block text-sm font-bold text-ink';
    $btn = 'rounded bg-brand-dark px-4 py-2 text-sm font-bold text-white hover:bg-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark focus-visible:ring-offset-2';
    $edFields = function ($e = null) use ($in, $lbl) {
        $v = fn ($k, $d = '') => old($k, $e?->{$k} ?? $d);
        return [$v, $in, $lbl];
    };
@endphp

@section('content')
    @if ($errors->any())
        <div class="mb-4 max-w-3xl rounded-md bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            <p class="font-bold">Popraw poniższe pola:</p>
            <ul class="mt-1 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $course->exists ? route('admin.kursy.update', $course) : route('admin.kursy.store') }}" class="max-w-3xl space-y-5 rounded-md bg-white p-6">
        @csrf
        @if ($course->exists) @method('PUT') @endif

        <div><label for="title" class="{{ $lbl }}">Tytuł *</label><input id="title" name="title" required value="{{ old('title', $course->title) }}" class="{{ $in }}"></div>
        <div><label for="slug" class="{{ $lbl }}">Adres (slug)</label><input id="slug" name="slug" value="{{ old('slug', $course->slug) }}" class="{{ $in }}" aria-describedby="slug-h"><p id="slug-h" class="mt-1 text-xs text-muted">Zostaw puste, żeby powstał z tytułu.</p></div>
        <div><label for="lead" class="{{ $lbl }}">Krótki opis</label><textarea id="lead" name="lead" rows="2" maxlength="500" class="{{ $in }}">{{ old('lead', $course->lead) }}</textarea></div>
        <div><label for="description" class="{{ $lbl }}">Pełny opis (HTML)</label><textarea id="description" name="description" rows="10" class="{{ $in }} font-mono">{{ old('description', $course->description) }}</textarea></div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div><label for="category" class="{{ $lbl }}">Kategoria *</label>
                <select id="category" name="category" class="{{ $in }}">@foreach (\App\Models\Course::CATEGORIES as $k => $l)<option value="{{ $k }}" @selected(old('category', $course->category) === $k)>{{ $l }}</option>@endforeach</select></div>
            <div><label for="format" class="{{ $lbl }}">Forma *</label>
                <select id="format" name="format" class="{{ $in }}">@foreach (\App\Models\Course::FORMATS as $k => $l)<option value="{{ $k }}" @selected(old('format', $course->format) === $k)>{{ $l }}</option>@endforeach</select></div>
            <div><label for="price" class="{{ $lbl }}">Cena (zł, puste = bezpłatnie)</label><input id="price" name="price" type="number" step="0.01" min="0" value="{{ old('price', $course->price) }}" class="{{ $in }}"></div>
            <div><label for="status" class="{{ $lbl }}">Status *</label>
                <select id="status" name="status" class="{{ $in }}">@foreach (\App\Models\Course::STATUSES as $k => $l)<option value="{{ $k }}" @selected(old('status', $course->status) === $k)>{{ $l }}</option>@endforeach</select></div>
            <div><label for="order" class="{{ $lbl }}">Kolejność</label><input id="order" name="order" type="number" min="0" value="{{ old('order', $course->order ?? 0) }}" class="{{ $in }}"></div>
            <div class="flex items-end"><label class="flex items-center gap-2 text-sm font-bold text-ink"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $course->is_featured)) class="h-5 w-5 rounded border-gray-500"> Wyróżnij na liście</label></div>
        </div>

        <fieldset class="space-y-5 border-t border-gray-100 pt-5">
            <legend class="text-sm font-bold uppercase tracking-wide text-muted">Szczegóły oferty</legend>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="facilitator_id" class="{{ $lbl }}">Trener (z bazy prowadzących)</label>
                    <select id="facilitator_id" name="facilitator_id" class="{{ $in }}" aria-describedby="facilitator-h">
                        <option value="">— bez przypisanego trenera —</option>
                        @foreach (\App\Models\Facilitator::query()->orderBy('name')->get() as $f)
                            <option value="{{ $f->id }}" @selected((int) old('facilitator_id', $course->facilitator_id) === $f->id)>{{ $f->name }}@if ($f->role) ({{ $f->role }})@endif</option>
                        @endforeach
                    </select>
                    <p id="facilitator-h" class="mt-1 text-xs text-muted">Zdjęcie, rola i biogram pokażą się na stronie szkolenia. Prowadzących dodasz w Wydarzenia → Prowadzący.</p>
                </div>
                <div><label for="duration" class="{{ $lbl }}">Czas trwania</label><input id="duration" name="duration" value="{{ old('duration', $course->duration) }}" placeholder="np. 16 godzin dydaktycznych" class="{{ $in }}"></div>
                <div><label for="level" class="{{ $lbl }}">Poziom</label><input id="level" name="level" value="{{ old('level', $course->level) }}" placeholder="np. podstawowy" class="{{ $in }}"></div>
                <div><label for="certificate" class="{{ $lbl }}">Certyfikat / zaświadczenie</label><input id="certificate" name="certificate" value="{{ old('certificate', $course->certificate) }}" placeholder="np. zaświadczenie o ukończeniu" class="{{ $in }}"></div>
                <div><label for="price_note" class="{{ $lbl }}">Uwaga do ceny</label><input id="price_note" name="price_note" value="{{ old('price_note', $course->price_note) }}" placeholder="np. cena zawiera materiały" class="{{ $in }}"></div>
                <div><label for="funding" class="{{ $lbl }}">Dofinansowanie</label><input id="funding" name="funding" value="{{ old('funding', $course->funding) }}" placeholder="np. możliwe z BUR i KFS" class="{{ $in }}"></div>
            </div>

            <div class="rounded-md bg-gray-50 p-4 space-y-3" data-td-repeater>
                <p class="text-xs font-bold uppercase text-muted">Dla kogo</p>
                <div data-td-rows class="space-y-2">
                    @foreach (old('audience', $course->audience ?? []) as $i => $row)
                    <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_auto]">
                        <input type="text" name="audience[{{ $i }}][text]" value="{{ $row['text'] ?? '' }}" placeholder="np. Pracownicy urzędów i instytucji" aria-label="Odbiorca" class="{{ $in }}">
                        <button type="button" data-td-remove class="inline-flex h-10 w-10 items-center justify-center rounded text-red-700 hover:bg-red-50" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                    </div>
                    @endforeach
                </div>
                <button type="button" data-td-add class="inline-flex items-center gap-2 rounded border border-brand-dark px-3 py-1.5 text-sm font-bold text-brand-dark hover:bg-brand-dark hover:text-white"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj odbiorcę</button>
                <template data-td-template>
                    <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_auto]">
                        <input type="text" name="audience[__INDEX__][text]" value="" placeholder="np. Pracownicy urzędów i instytucji" aria-label="Odbiorca" class="{{ $in }}">
                        <button type="button" data-td-remove class="inline-flex h-10 w-10 items-center justify-center rounded text-red-700 hover:bg-red-50" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                    </div>
                </template>
            </div>
            <div class="rounded-md bg-gray-50 p-4 space-y-3" data-td-repeater>
                <p class="text-xs font-bold uppercase text-muted">Czego się nauczysz (efekty)</p>
                <div data-td-rows class="space-y-2">
                    @foreach (old('outcomes', $course->outcomes ?? []) as $i => $row)
                    <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_auto]">
                        <input type="text" name="outcomes[{{ $i }}][text]" value="{{ $row['text'] ?? '' }}" placeholder="np. Przygotujesz dostępny dokument PDF" aria-label="Efekt" class="{{ $in }}">
                        <button type="button" data-td-remove class="inline-flex h-10 w-10 items-center justify-center rounded text-red-700 hover:bg-red-50" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                    </div>
                    @endforeach
                </div>
                <button type="button" data-td-add class="inline-flex items-center gap-2 rounded border border-brand-dark px-3 py-1.5 text-sm font-bold text-brand-dark hover:bg-brand-dark hover:text-white"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj efekt</button>
                <template data-td-template>
                    <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_auto]">
                        <input type="text" name="outcomes[__INDEX__][text]" value="" placeholder="np. Przygotujesz dostępny dokument PDF" aria-label="Efekt" class="{{ $in }}">
                        <button type="button" data-td-remove class="inline-flex h-10 w-10 items-center justify-center rounded text-red-700 hover:bg-red-50" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                    </div>
                </template>
            </div>
            <div class="rounded-md bg-gray-50 p-4 space-y-3" data-td-repeater>
                <p class="text-xs font-bold uppercase text-muted">Program (moduły)</p>
                <div data-td-rows class="space-y-2">
                    @foreach (old('program', $course->program ?? []) as $i => $row)
                    <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_5rem_2fr_auto]">
                        <input type="text" name="program[{{ $i }}][title]" value="{{ $row['title'] ?? '' }}" placeholder="Tytuł modułu" aria-label="Tytuł modułu" class="{{ $in }}">
                        <input type="text" name="program[{{ $i }}][hours]" value="{{ $row['hours'] ?? '' }}" placeholder="Godz." aria-label="Liczba godzin" class="{{ $in }}">
                        <input type="text" name="program[{{ $i }}][text]" value="{{ $row['text'] ?? '' }}" placeholder="Opis modułu" aria-label="Opis modułu" class="{{ $in }}">
                        <button type="button" data-td-remove class="inline-flex h-10 w-10 items-center justify-center rounded text-red-700 hover:bg-red-50" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                    </div>
                    @endforeach
                </div>
                <button type="button" data-td-add class="inline-flex items-center gap-2 rounded border border-brand-dark px-3 py-1.5 text-sm font-bold text-brand-dark hover:bg-brand-dark hover:text-white"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj moduł</button>
                <template data-td-template>
                    <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_5rem_2fr_auto]">
                        <input type="text" name="program[__INDEX__][title]" value="" placeholder="Tytuł modułu" aria-label="Tytuł modułu" class="{{ $in }}">
                        <input type="text" name="program[__INDEX__][hours]" value="" placeholder="Godz." aria-label="Liczba godzin" class="{{ $in }}">
                        <input type="text" name="program[__INDEX__][text]" value="" placeholder="Opis modułu" aria-label="Opis modułu" class="{{ $in }}">
                        <button type="button" data-td-remove class="inline-flex h-10 w-10 items-center justify-center rounded text-red-700 hover:bg-red-50" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                    </div>
                </template>
            </div>
            <div class="rounded-md bg-gray-50 p-4 space-y-3" data-td-repeater>
                <p class="text-xs font-bold uppercase text-muted">Najczęstsze pytania</p>
                <div data-td-rows class="space-y-2">
                    @foreach (old('faq', $course->faq ?? []) as $i => $row)
                    <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_2fr_auto]">
                        <input type="text" name="faq[{{ $i }}][question]" value="{{ $row['question'] ?? '' }}" placeholder="Pytanie" aria-label="Pytanie" class="{{ $in }}">
                        <textarea name="faq[{{ $i }}][answer]" rows="2" placeholder="Odpowiedź" aria-label="Odpowiedź" class="{{ $in }}">{{ $row['answer'] ?? '' }}</textarea>
                        <button type="button" data-td-remove class="inline-flex h-10 w-10 items-center justify-center rounded text-red-700 hover:bg-red-50" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                    </div>
                    @endforeach
                </div>
                <button type="button" data-td-add class="inline-flex items-center gap-2 rounded border border-brand-dark px-3 py-1.5 text-sm font-bold text-brand-dark hover:bg-brand-dark hover:text-white"><i class="fa-solid fa-plus" aria-hidden="true"></i> Dodaj pytanie</button>
                <template data-td-template>
                    <div data-td-row class="grid gap-2 sm:grid-cols-[1fr_2fr_auto]">
                        <input type="text" name="faq[__INDEX__][question]" value="" placeholder="Pytanie" aria-label="Pytanie" class="{{ $in }}">
                        <textarea name="faq[__INDEX__][answer]" rows="2" placeholder="Odpowiedź" aria-label="Odpowiedź" class="{{ $in }}"></textarea>
                        <button type="button" data-td-remove class="inline-flex h-10 w-10 items-center justify-center rounded text-red-700 hover:bg-red-50" aria-label="Usuń wiersz"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                    </div>
                </template>
            </div>

            <div><label for="prerequisites" class="{{ $lbl }}">Wymagania wstępne</label><textarea id="prerequisites" name="prerequisites" rows="2" class="{{ $in }}">{{ old('prerequisites', $course->prerequisites) }}</textarea></div>
            <div><label for="terms" class="{{ $lbl }}">Warunki udziału (HTML)</label><textarea id="terms" name="terms" rows="6" class="{{ $in }} font-mono" aria-describedby="terms-h">{{ old('terms', $course->terms) }}</textarea><p id="terms-h" class="mt-1 text-xs text-muted">Zapisy, płatność, rezygnacja, zaświadczenie. Pokazuje się w sekcji „Warunki udziału” na stronie szkolenia.</p></div>
        </fieldset>

        <div class="flex gap-3"><button type="submit" class="{{ $btn }}">Zapisz</button><a href="{{ route('admin.kursy.index') }}" class="rounded border border-gray-500 px-4 py-2 text-sm font-bold text-ink hover:bg-gray-100">Wróć do listy</a></div>
    </form>

    @if ($course->exists)
        <section class="mt-10 max-w-3xl" aria-labelledby="ed-h">
            <h2 id="ed-h" class="text-xl font-bold text-ink">Terminy</h2>

            <div class="mt-4 space-y-3">
                @forelse ($course->editions as $e)
                    <details class="rounded-md bg-white p-4">
                        <summary class="cursor-pointer text-sm font-bold text-ink">
                            {{ $e->datesLabel() }} · zajęte {{ $e->seats_taken }}/{{ $e->capacity }}@if ($e->waitlist_count) · rezerwowa: {{ $e->waitlist_count }}@endif · {{ $e->enrollmentLabel() }}
                        </summary>
                        <p class="mt-3"><a href="{{ route('admin.kursy.registrations.index', $e) }}" class="text-sm font-bold text-brand-dark underline">Uczestnicy i zapisy →</a></p>
                        <form method="POST" action="{{ route('admin.kursy.editions.update', $e) }}" class="mt-3">
                            @csrf @method('PUT')
                            @include('admin.courses.partials.edition-fields', ['e' => $e, 'in' => $in, 'lbl' => $lbl, 'uid' => 'e'.$e->id])
                            <button type="submit" class="mt-3 {{ $btn }}">Zapisz termin</button>
                        </form>
                        <form method="POST" action="{{ route('admin.kursy.editions.destroy', $e) }}" class="mt-3" onsubmit="return confirm('Usunąć termin razem z zapisami?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-sm font-bold text-red-700 underline">Usuń termin</button>
                        </form>
                    </details>
                @empty
                    <p class="rounded-md bg-gray-50 p-4 text-sm text-ink">Brak terminów. Dodaj pierwszy poniżej.</p>
                @endforelse
            </div>

            <form method="POST" action="{{ route('admin.kursy.editions.store', $course) }}" class="mt-6 rounded-md bg-white p-5">
                @csrf
                <h3 class="text-base font-bold text-ink">Dodaj termin</h3>
                @include('admin.courses.partials.edition-fields', ['e' => null, 'in' => $in, 'lbl' => $lbl, 'uid' => 'new'])
                <button type="submit" class="mt-3 {{ $btn }}">Dodaj termin</button>
            </form>
        </section>

        @isset($notifications)
            @php
                $pending = $notifications->whereNull('notified_at');
                $sendable = $course->editions->filter(fn ($e) => $e->acceptsRegistrations());
            @endphp
            <section class="mt-10 max-w-3xl" aria-labelledby="notif-h">
                <h2 id="notif-h" class="text-xl font-bold text-ink">Powiadomienia o terminie</h2>
                <p class="mt-1 text-sm text-ink">Osoby, które na stronie szkolenia zostawiły adres e-mail przy „Powiadom mnie o terminie”. Każda dostaje jedną wiadomość o wybranym terminie z otwartą rekrutacją.</p>

                @if ($notifications->isEmpty())
                    <p class="mt-4 rounded-md bg-gray-50 p-4 text-sm text-ink">Nikt jeszcze nie zapisał się na powiadomienie.</p>
                @else
                    <form method="POST" action="{{ route('admin.kursy.notifications.send', $course) }}" class="mt-4 rounded-md bg-white p-5"
                          onsubmit="return confirm('Wysłać powiadomienie do {{ $pending->count() }} os.?');">
                        @csrf
                        <p class="text-sm font-bold text-ink">Czeka na powiadomienie: {{ $pending->count() }} · już powiadomionych: {{ $notifications->count() - $pending->count() }}</p>
                        @if ($sendable->isEmpty())
                            <p class="mt-2 text-sm text-ink">Najpierw dodaj termin z otwartą rekrutacją (sekcja „Terminy” wyżej).</p>
                        @elseif ($pending->isNotEmpty())
                            <label for="notif-edition" class="mt-3 {{ $lbl }}">Termin do ogłoszenia</label>
                            <select id="notif-edition" name="edition_id" class="{{ $in }}">
                                @foreach ($sendable as $e)<option value="{{ $e->id }}">{{ $e->datesLabel() }}@if ($e->location), {{ $e->location }}@endif</option>@endforeach
                            </select>
                            <button type="submit" class="mt-3 {{ $btn }}">Wyślij powiadomienie ({{ $pending->count() }})</button>
                        @endif
                    </form>

                    <table class="mt-4 w-full max-w-3xl rounded-md bg-white text-left text-sm text-ink">
                        <caption class="sr-only">Adresy zapisane na powiadomienie</caption>
                        <thead><tr><th scope="col" class="p-3">Adres e-mail</th><th scope="col" class="p-3">Zapisano</th><th scope="col" class="p-3">Powiadomiono</th><th scope="col" class="p-3"><span class="sr-only">Akcje</span></th></tr></thead>
                        <tbody>
                            @foreach ($notifications as $n)
                                <tr class="border-t border-gray-200">
                                    <td class="p-3">{{ $n->email }}</td>
                                    <td class="p-3">{{ $n->created_at->format('d.m.Y') }}</td>
                                    <td class="p-3">{{ $n->notified_at ? $n->notified_at->format('d.m.Y H:i') : 'jeszcze nie' }}</td>
                                    <td class="p-3">
                                        <form method="POST" action="{{ route('admin.kursy.notifications.destroy', $n) }}" onsubmit="return confirm('Usunąć adres z listy?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="font-bold text-red-700 underline">Usuń<span class="sr-only"> {{ $n->email }}</span></button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>
        @endisset
    @endif
@endsection

@push('scripts')
<script>
    // Listy wierszy (dla kogo, efekty, program, FAQ): dodawanie z szablonu i usuwanie, bez przeładowania.
    document.querySelectorAll('[data-td-repeater]').forEach(function (box) {
        var rows = box.querySelector('[data-td-rows]'), tpl = box.querySelector('[data-td-template]');
        box.querySelector('[data-td-add]').addEventListener('click', function () {
            var html = tpl.innerHTML.replace(/__INDEX__/g, String(Date.now()));
            rows.insertAdjacentHTML('beforeend', html);
            var added = rows.lastElementChild.querySelector('input, textarea'); if (added) added.focus();
        });
        box.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-td-remove]'); if (btn) btn.closest('[data-td-row]').remove();
        });
    });
</script>
@endpush
