{{-- Inline edytor jednej osoby w sekcji Zespół.
     $tp  – Page|null (istniejąca osoba lub null dla nowego wiersza)
     $ti  – int|string (indeks lub '__INDEX__' dla template)
--}}
@php
    $isTemplate = $ti === '__INDEX__';
    $pId           = $tp?->id;
    $pName         = $tp?->title ?? '';
    $pGenitive     = $tp?->person_name_genitive ?? '';
    $pRole         = $tp?->person_role ?? '';
    $pMemberLabel  = $tp?->person_member_label ?? '';
    $pBio          = $tp?->person_bio ?? '';
    $pImage        = $tp?->content_image ?? '';
    $pPhone        = $tp?->person_phone ?? '';
    $pEmail        = $tp?->person_email ?? '';
    $pFacebook     = $tp?->person_social['facebook'] ?? '';
    $pInstagram    = $tp?->person_social['instagram'] ?? '';
    $pLinkedin     = $tp?->person_social['linkedin'] ?? '';
    $pWebsite      = $tp?->person_social['website'] ?? '';
    $pPublished    = $tp?->is_published ?? false;
    $detailsOpen   = ! $isTemplate && (filled($pBio) || filled($pImage) || filled($pPhone) || filled($pEmail) || filled($pFacebook) || filled($pInstagram) || filled($pLinkedin) || filled($pWebsite));
@endphp

<div class="team-person-row rounded-lg border border-gray-200 bg-white" data-index="{{ $ti }}">
    <input type="hidden" name="team[{{ $ti }}][id]" value="{{ $pId }}">

    {{-- Nagłówek: podstawowe pola + akcje --}}
    <div class="flex items-center gap-2 px-3 py-2">
        <div class="grid min-w-0 flex-1 gap-2 sm:grid-cols-4">
            <input type="text" name="team[{{ $ti }}][name]" value="{{ $pName }}"
                placeholder="Imię i nazwisko" aria-label="Imię i nazwisko osoby {{ $ti }}"
                class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            <input type="text" name="team[{{ $ti }}][name_genitive]" value="{{ $pGenitive }}"
                placeholder="Odmiana (Alicji, Ziemowita…)" aria-label="Odmiana imienia"
                class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            <input type="text" name="team[{{ $ti }}][role]" value="{{ $pRole }}"
                placeholder="Co robi w FEER" aria-label="Rola"
                class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="team[{{ $ti }}][is_published]" value="1"
                    @checked($pPublished)
                    class="rounded border-gray-300 text-brand focus:ring-brand">
                <span class="text-muted">Opublikuj</span>
            </label>
        </div>
        <div class="flex shrink-0 items-center gap-1">
            <button type="button" class="team-expand rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand"
                aria-label="Rozwiń szczegóły osoby">
                <i class="fa-solid fa-chevron-down text-xs" aria-hidden="true"></i>
            </button>
            <button type="button" class="team-move-up rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Przenieś wyżej"><i class="fa-solid fa-arrow-up text-xs" aria-hidden="true"></i></button>
            <button type="button" class="team-move-down rounded p-1.5 text-muted hover:bg-gray-100 hover:text-brand" aria-label="Przenieś niżej"><i class="fa-solid fa-arrow-down text-xs" aria-hidden="true"></i></button>
            <button type="button" class="team-remove rounded p-1.5 text-muted hover:bg-red-50 hover:text-red-600" aria-label="Usuń osobę"><i class="fa-solid fa-trash text-xs" aria-hidden="true"></i></button>
        </div>
    </div>

    {{-- Szczegóły (zwijane) --}}
    <div class="team-details border-t border-gray-100 px-3 pb-4 pt-3 {{ $detailsOpen ? '' : 'hidden' }}">
        <div class="grid gap-3 sm:grid-cols-2">
            {{-- Bio --}}
            <div class="sm:col-span-2">
                <label class="mb-1 block text-xs font-bold text-muted">Krótkie bio (widoczne w listingu zespołu)</label>
                <textarea name="team[{{ $ti }}][bio]" rows="2" maxlength="500"
                    placeholder="Kilka zdań o tej osobie…"
                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">{{ $pBio }}</textarea>
            </div>
            {{-- Etykieta --}}
            <div>
                <label class="mb-1 block text-xs font-bold text-muted">Etykieta (Członkini/Członek…)</label>
                <input type="text" name="team[{{ $ti }}][member_label]" value="{{ $pMemberLabel }}"
                    placeholder="np. Członkini zespołu FEER"
                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
            {{-- Zdjęcie --}}
            <div>
                <label class="mb-1 block text-xs font-bold text-muted">Zdjęcie — URL</label>
                <input type="url" name="team[{{ $ti }}][content_image]" value="{{ $pImage }}"
                    placeholder="https://…"
                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
            {{-- Telefon --}}
            <div>
                <label class="mb-1 block text-xs font-bold text-muted">Telefon</label>
                <input type="tel" name="team[{{ $ti }}][phone]" value="{{ $pPhone }}"
                    placeholder="+48 000 000 000"
                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
            {{-- E-mail --}}
            <div>
                <label class="mb-1 block text-xs font-bold text-muted">E-mail</label>
                <input type="email" name="team[{{ $ti }}][email]" value="{{ $pEmail }}"
                    placeholder="osoba@feer.org.pl"
                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
            {{-- Social --}}
            <div>
                <label class="mb-1 block text-xs font-bold text-muted">Facebook</label>
                <input type="url" name="team[{{ $ti }}][facebook]" value="{{ $pFacebook }}"
                    placeholder="https://facebook.com/…"
                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold text-muted">Instagram</label>
                <input type="url" name="team[{{ $ti }}][instagram]" value="{{ $pInstagram }}"
                    placeholder="https://instagram.com/…"
                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold text-muted">LinkedIn</label>
                <input type="url" name="team[{{ $ti }}][linkedin]" value="{{ $pLinkedin }}"
                    placeholder="https://linkedin.com/in/…"
                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
            <div>
                <label class="mb-1 block text-xs font-bold text-muted">Strona WWW</label>
                <input type="url" name="team[{{ $ti }}][website]" value="{{ $pWebsite }}"
                    placeholder="https://…"
                    class="w-full rounded border-gray-300 text-sm focus:border-brand focus:ring-brand">
            </div>
        </div>
        @if ($pId && ! $isTemplate)
            <p class="mt-3 text-xs text-muted">
                Slug: <code class="font-mono">{{ $tp?->slug }}</code>
                &mdash;
                <a href="{{ $tp?->publicUrl() }}" target="_blank" class="text-brand hover:underline">
                    <i class="fa-solid fa-arrow-up-right-from-square text-xs" aria-hidden="true"></i> podgląd
                </a>
            </p>
        @endif
    </div>
</div>
