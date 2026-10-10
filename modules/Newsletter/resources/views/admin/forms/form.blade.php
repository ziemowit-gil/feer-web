@extends('admin.layout')
@section('title', $form->exists ? 'Formularz: ' . $form->name : 'Nowy formularz zapisu')
@section('content')
    @include('newsletter::admin.partials.flash')
    <form method="POST" action="{{ $form->exists ? route('admin.newsletter.formularze.update', $form) : route('admin.newsletter.formularze.store') }}" class="grid gap-6 lg:grid-cols-3">
        @csrf @if ($form->exists) @method('PUT') @endif
        <div class="space-y-5 lg:col-span-2">
            <section class="space-y-4 rounded-lg border border-gray-200 bg-white p-6" aria-labelledby="f-copy"><h2 id="f-copy" class="text-sm font-bold uppercase text-muted">Treści</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label for="name" class="mb-1 block text-sm font-bold">Nazwa robocza</label><input id="name" name="name" required value="{{ old('name', $form->name) }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand"></div>
                    <div><label for="slug" class="mb-1 block text-sm font-bold">Slug (adres /newsletter/…)</label><input id="slug" name="slug" value="{{ old('slug', $form->slug) }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand"></div>
                </div>
                <div><label for="eyebrow" class="mb-1 block text-sm font-bold">Nadtytuł</label><input id="eyebrow" name="eyebrow" value="{{ old('eyebrow', $form->eyebrow) }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand"></div>
                <div><label for="heading" class="mb-1 block text-sm font-bold">Nagłówek</label><input id="heading" name="heading" required value="{{ old('heading', $form->heading) }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand"></div>
                <div><label for="lead" class="mb-1 block text-sm font-bold">Tekst wprowadzający</label><textarea id="lead" name="lead" rows="2" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('lead', $form->lead) }}</textarea></div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label for="button_label" class="mb-1 block text-sm font-bold">Napis na przycisku</label><input id="button_label" name="button_label" required value="{{ old('button_label', $form->button_label) }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand"></div>
                    <div><label for="privacy_url" class="mb-1 block text-sm font-bold">Link do polityki prywatności</label><input id="privacy_url" name="privacy_url" value="{{ old('privacy_url', $form->privacy_url) }}" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand"></div>
                </div>
                <div><label for="success_message" class="mb-1 block text-sm font-bold">Komunikat po wysłaniu</label><textarea id="success_message" name="success_message" rows="2" required class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('success_message', $form->success_message) }}</textarea></div>
            </section>
            <section class="space-y-4 rounded-lg border border-gray-200 bg-white p-6" aria-labelledby="f-fields"><h2 id="f-fields" class="text-sm font-bold uppercase text-muted">Pola i tematy</h2>
                <div class="flex flex-wrap gap-4 text-sm">
                    @foreach (['ask_name' => 'Pole „Imię”', 'ask_phone' => 'Pole „Telefon”', 'show_topics' => 'Wybór tematów', 'offer_sms' => 'Zgoda na SMS (wymaga telefonu)', 'offer_webpush' => 'Zachęta do push po potwierdzeniu'] as $k => $l)
                        <label class="inline-flex items-center gap-2"><input type="hidden" name="{{ $k }}" value="0"><input type="checkbox" name="{{ $k }}" value="1" class="rounded border-gray-300 text-brand focus:ring-brand" @checked(old($k, $form->$k))> {{ $l }}</label>
                    @endforeach
                </div>
                <fieldset><legend class="mb-1 text-sm font-bold">Tematy do wyboru (puste = wszystkie)</legend><div class="flex flex-wrap gap-3 text-sm">@foreach ($topics as $k => $l)<label class="inline-flex items-center gap-2"><input type="checkbox" name="topics[]" value="{{ $k }}" class="rounded border-gray-300 text-brand focus:ring-brand" @checked(in_array($k, old('topics', $form->topics ?? []), true))> {{ $l }}</label>@endforeach</div></fieldset>
                <fieldset><legend class="mb-1 text-sm font-bold">Zaznaczone domyślnie</legend><div class="flex flex-wrap gap-3 text-sm">@foreach ($topics as $k => $l)<label class="inline-flex items-center gap-2"><input type="checkbox" name="default_topics[]" value="{{ $k }}" class="rounded border-gray-300 text-brand focus:ring-brand" @checked(in_array($k, old('default_topics', $form->default_topics ?? []), true))> {{ $l }}</label>@endforeach</div></fieldset>
            </section>
            <section class="space-y-4 rounded-lg border border-gray-200 bg-white p-6" aria-labelledby="f-consent"><h2 id="f-consent" class="text-sm font-bold uppercase text-muted">Zgoda (RODO / PKE)</h2>
                <div><label for="consent_text" class="mb-1 block text-sm font-bold">Treść zgody przy checkboxie</label><textarea id="consent_text" name="consent_text" rows="3" required class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">{{ old('consent_text', $form->consent_text) }}</textarea><p class="mt-1 text-xs text-muted">Każda zmiana treści tworzy nową wersję klauzuli w rejestrze zgód (snapshot przy zapisie subskrybenta).</p></div>
                @if ($clauses->isNotEmpty())
                <div><label for="clause_id" class="mb-1 block text-sm font-bold">Powiązana klauzula informacyjna</label><select id="clause_id" name="clause_id" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand"><option value="">—</option>@foreach ($clauses as $c)<option value="{{ $c->id }}" @selected((int) old('clause_id', $form->clause_id) === $c->id)>{{ $c->name }}</option>@endforeach</select></div>
                @endif
            </section>
        </div>
        <div class="space-y-5">
            <section class="space-y-4 rounded-lg border border-gray-200 bg-white p-6" aria-labelledby="f-pub"><h2 id="f-pub" class="text-sm font-bold uppercase text-muted">Publikacja</h2>
                <label class="flex items-center gap-2 text-sm"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-brand focus:ring-brand" @checked(old('is_active', $form->is_active))> Aktywny</label>
                <label class="flex items-center gap-2 text-sm"><input type="hidden" name="is_default" value="0"><input type="checkbox" name="is_default" value="1" class="rounded border-gray-300 text-brand focus:ring-brand" @checked(old('is_default', $form->is_default))> Domyślny (strona /newsletter, strona główna)</label>
                <div><label for="style" class="mb-1 block text-sm font-bold">Układ</label><select id="style" name="style" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">@foreach ($styles as $k => $l)<option value="{{ $k }}" @selected(old('style', $form->style) === $k)>{{ $l }}</option>@endforeach</select></div>
                <div><label for="accent_color" class="mb-1 block text-sm font-bold">Kolor akcentu (pasek)</label><div class="flex items-center gap-2"><input id="accent_color" name="accent_color" value="{{ old('accent_color', $form->accent_color) }}" placeholder="#EA8F00" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand" data-admin-color-input></div><p class="mt-1 text-xs text-muted">Puste = pomarańcz z brandbooku (#EA8F00). Tylko dekoracja — tekst i przyciski pilnują kontrastu same.</p></div>
            </section>
            <section class="space-y-4 rounded-lg border border-gray-200 bg-white p-6" aria-labelledby="f-look"><h2 id="f-look" class="text-sm font-bold uppercase text-muted">Wygląd i dostępność (WCAG)</h2>
                <p class="text-xs text-muted">Kolory są sprawdzane przy zapisie: tekst min. 4,5:1, obramowania pól i fokus min. 3:1 względem tła. Kolor, który tego nie spełnia, nie zostanie zapisany.</p>
                @php $themeValues = $form->themeValues(); @endphp
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($themeColors as $key => [$label, $default])
                        <div>
                            <div class="flex items-center gap-3">
                                <input type="color" id="theme-{{ $key }}" name="theme[{{ $key }}]" value="{{ old("theme.$key", $themeValues[$key]) }}" class="h-10 w-14 shrink-0 cursor-pointer rounded border border-gray-300 bg-white p-1">
                                <label for="theme-{{ $key }}" class="text-sm">{{ $label }} <span class="text-xs text-muted">({{ $default }})</span></label>
                            </div>
                            @error("theme.$key")<p class="mt-1 text-xs font-bold text-red-700">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
                <div class="flex flex-wrap gap-4 text-sm">
                    @foreach ($themeFlags as $key => [$label])
                        <label class="inline-flex items-center gap-2"><input type="hidden" name="theme[{{ $key }}]" value="0"><input type="checkbox" name="theme[{{ $key }}]" value="1" class="rounded border-gray-300 text-brand focus:ring-brand" @checked(old("theme.$key", $themeValues[$key]))> {{ $label }}</label>
                    @endforeach
                </div>
            </section>
            <section class="space-y-4 rounded-lg border border-gray-200 bg-white p-6" aria-labelledby="f-int"><h2 id="f-int" class="text-sm font-bold uppercase text-muted">Listy i integracje</h2>
                <fieldset><legend class="mb-1 text-sm font-bold">Dodaj zapisanych do list</legend><div class="space-y-1 text-sm">@forelse ($lists as $l)<label class="flex items-center gap-2"><input type="checkbox" name="list_ids[]" value="{{ $l->id }}" class="rounded border-gray-300 text-brand focus:ring-brand" @checked(in_array($l->id, old('list_ids', $form->list_ids ?? []), true))> {{ $l->name }}</label>@empty<span class="text-muted">Brak list.</span>@endforelse</div></fieldset>
                <div><label for="szo_form_slug" class="mb-1 block text-sm font-bold">Formularz w SZO (kontakt + zgoda „newsletter”)</label>
                    @if ($szoForms)
                        <select id="szo_form_slug" name="szo_form_slug" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand"><option value="">— domyślny z ustawień newslettera —</option>@foreach ($szoForms as $slug => $label)<option value="{{ $slug }}" @selected(old('szo_form_slug', $form->szo_form_slug) === $slug)>{{ $label }} ({{ $slug }})</option>@endforeach</select>
                    @else
                        <input id="szo_form_slug" name="szo_form_slug" value="{{ old('szo_form_slug', $form->szo_form_slug) }}" placeholder="np. newsletter" class="w-full rounded border-gray-300 focus:border-brand focus:ring-brand">
                    @endif
                    <p class="mt-1 text-xs text-muted">Po potwierdzeniu DOI subskrybent trafia do SZO jako zgłoszenie tego formularza (gdy synchronizacja SZO jest włączona w <a href="{{ route('admin.newsletter.ustawienia.edit') }}" class="underline">ustawieniach</a>).</p></div>
            </section>
            <div class="flex gap-2"><button type="submit" class="rounded bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand-dark">Zapisz</button><a href="{{ route('admin.newsletter.formularze.index') }}" class="rounded border border-gray-300 bg-white px-5 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50">Anuluj</a></div>
        </div>
    </form>
@endsection
