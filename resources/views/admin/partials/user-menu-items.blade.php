{{-- Pozycje menu konta (profil, powiadomienia, dokumentacja, O weCMS, powrót do strony, wylogowanie) — w menu bocznym
     i w górnym menu stylu Metro. Zmienne: $authUser, $roleLabel. --}}
<div class="border-b border-gray-100 px-3 py-2">
    <p class="truncate font-semibold text-ink">{{ $authUser?->name ?: $authUser?->email }}</p>
    <p class="truncate text-xs text-muted">{{ $authUser?->email }}</p>
    <span class="mt-1 inline-block text-[11px] font-bold uppercase tracking-wide {{ $authUser?->isAdmin() ? 'text-brand-dark' : 'text-muted' }}">{{ $roleLabel }}</span>
</div>
<a href="{{ route('profile.edit') }}" role="menuitem" class="flex items-center gap-3 px-3 py-2 text-ink hover:bg-gray-50 hover:text-brand-dark">
    <i class="fa-solid fa-key w-4 text-center text-gray-400" aria-hidden="true"></i> Profil i hasło
</a>
<a href="{{ route('profile.edit') }}#powiadomienia" role="menuitem" class="flex items-center gap-3 px-3 py-2 text-ink hover:bg-gray-50 hover:text-brand-dark">
    <i class="fa-solid fa-bell w-4 text-center text-gray-400" aria-hidden="true"></i> Powiadomienia
</a>
@if ($authUser?->isAdmin())
    <a href="{{ route('admin.dokumentacja') }}" target="_blank" rel="noopener" role="menuitem" class="flex items-center gap-3 px-3 py-2 text-ink hover:bg-gray-50 hover:text-brand-dark">
        <i class="fa-solid fa-book w-4 text-center text-gray-400" aria-hidden="true"></i> Dokumentacja
        <i class="fa-solid fa-arrow-up-right-from-square ml-auto text-[0.65rem] text-gray-300" aria-hidden="true"></i>
        <span class="sr-only">(otwiera się w nowej karcie)</span>
    </a>
@endif
<a href="{{ route('admin.o-cms') }}" role="menuitem" class="flex items-center gap-3 px-3 py-2 text-ink hover:bg-gray-50 hover:text-brand-dark">
    <i class="fa-solid fa-circle-info w-4 text-center text-gray-400" aria-hidden="true"></i> O weCMS
</a>
<a href="{{ admin_site_home() }}" role="menuitem" class="flex items-center gap-3 border-t border-gray-100 px-3 py-2 text-ink hover:bg-gray-50 hover:text-brand-dark">
    <i class="fa-solid fa-arrow-left w-4 text-center text-gray-400" aria-hidden="true"></i> Wróć do strony
</a>
<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit" role="menuitem" class="flex w-full items-center gap-3 px-3 py-2 text-left text-ink hover:bg-gray-50 hover:text-brand-dark">
        <i class="fa-solid fa-right-from-bracket w-4 text-center text-gray-400" aria-hidden="true"></i> Wyloguj
    </button>
</form>
<p class="border-t border-gray-100 px-3 py-2 text-[11px] text-muted">
    Napędzane przez <a href="{{ route('admin.o-cms') }}" class="font-bold hover:text-ink">weCMS</a> · <a href="mailto:ziemowit.gil@gmail.com" class="hover:text-ink">Ziemowit Gil</a>
</p>
