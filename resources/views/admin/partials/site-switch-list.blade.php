{{-- Lista witryn sieci z przełączaniem (POST admin.witryny.przelacz) i linkami zarządzania — w menu bocznym i w górnym menu
     stylu Metro. Zmienne: $adminSites, $siteSettings. --}}
<p class="px-2 pb-1 text-[10px] font-bold uppercase tracking-wide text-muted">Witryny</p>
<ul class="space-y-0.5" role="list">
    @foreach ($adminSites as $adminSite)
        @php $isCurrent = $adminSite->is($siteSettings); @endphp
        <li>
            <form method="POST" action="{{ route('admin.witryny.przelacz', $adminSite) }}">
                @csrf
                <button type="submit" @if ($isCurrent) aria-current="true" @endif
                        class="flex w-full items-center gap-2 rounded-lg px-2 py-2 text-left text-sm hover:bg-gray-100 focus-visible:bg-gray-100 focus-visible:outline-none {{ $isCurrent ? 'bg-brand-light font-bold text-brand-dark' : 'text-ink' }} {{ $adminSite->parent_site_id ? 'pl-6' : '' }}">
                    <i class="fa-solid {{ $adminSite->parent_site_id ? 'fa-sitemap' : 'fa-house' }} w-4 text-center text-[0.7rem] {{ $isCurrent ? 'text-brand-dark' : 'text-gray-500' }}" aria-hidden="true"></i>
                    <span class="min-w-0 flex-1 truncate">{{ $adminSite->site_name }}</span>
                    @if ($isCurrent)<i class="fa-solid fa-check text-xs" aria-hidden="true"></i><span class="sr-only">(bieżąca)</span>@endif
                </button>
            </form>
        </li>
    @endforeach
</ul>
<div class="mt-1 flex flex-wrap gap-x-3 border-t border-gray-100 px-2 pt-1 text-sm">
    <a href="{{ route('admin.wybor-witryny') }}" class="inline-flex min-h-11 items-center font-bold text-brand-dark hover:underline">Ekran wyboru</a>
    <a href="{{ route('admin.witryny.index') }}" class="inline-flex min-h-11 items-center font-bold text-brand-dark hover:underline">Zarządzaj subwitrynami</a>
    @if (Route::has('admin.witryny.nazwa'))
        <a href="{{ route('admin.witryny.nazwa', $siteSettings) }}" class="inline-flex min-h-11 items-center font-bold text-brand-dark hover:underline">Zmień nazwę i adres<span class="sr-only"> tej witryny</span></a>
    @endif
    <a href="{{ route('admin.witryny.ustawienia') }}" class="inline-flex min-h-11 items-center font-bold text-brand-dark hover:underline">Przenieś ustawienia</a>
</div>
