{{-- Sekcja formularza strony: typ "contact" — wszystkie opcje strony kontaktowej (zapisywane w ustawieniach witryny przez App\Support\ContactSettings). --}}
<div data-contact-fields class="space-y-5 border-t border-gray-100 pt-5 {{ $currentType === 'contact' ? '' : 'hidden' }}">
    <div>
        <p class="text-sm font-bold uppercase tracking-wide text-muted">Strona kontaktowa</p>
        <p class="mt-1 text-xs text-muted">Te opcje dotyczą strony <a href="{{ route('contact.show') }}" target="_blank" rel="noopener" class="text-brand underline">/kontakt</a>. Treść, zdjęcie i szablon wizualny nie są tu potrzebne — strona ma stały układ.</p>
        <p class="mt-2 text-xs"><a href="{{ route('admin.wiadomosci-kontaktowe.index') }}" class="inline-flex items-center gap-2 font-bold text-brand underline-offset-4 hover:underline"><i class="fa-solid fa-inbox" aria-hidden="true"></i> Wiadomości z formularza</a></p>
    </div>

    @include('admin.settings.partials.contact-fields', ['settings' => $siteSettings, 'layoutDefault' => $page->exists ? null : 'tabs'])
</div>
