{{-- Pasek „którą witrynę edytujesz”: widoczny dla administratora sieci, gdy jest więcej niż jedna witryna. --}}
@if ((new \App\Http\Middleware\EnsureCanManageSites)->allows(auth()->user()) && \App\Http\Controllers\Admin\SiteChooserController::sites()->count() > 1 && ! request()->routeIs('admin.wybor-witryny'))
    @php
        $barSite = \App\Models\SiteSetting::current();
        $barIsSub = (bool) $barSite->parent_site_id;
        $barName = $barSite->site_name ?: ($barSite->slug ?: 'Witryna '.$barSite->id);
    @endphp
    <div class="mb-4 flex flex-wrap items-center gap-x-4 gap-y-2 rounded-md px-4 py-3 text-sm {{ $barIsSub ? 'bg-ink text-white' : 'bg-gray-100 text-ink' }}" role="status">
        <span class="flex items-center gap-2">
            <i class="fa-solid {{ $barIsSub ? 'fa-sitemap' : 'fa-house' }}" aria-hidden="true"></i>
            <span>Edytujesz: <strong>{{ $barName }}</strong> <span class="{{ $barIsSub ? 'text-white' : 'text-ink' }}">({{ $barIsSub ? 'witryna w sieci' : 'witryna główna' }})</span></span>
        </span>
        <a href="{{ route('admin.wybor-witryny') }}" class="font-bold underline hover:no-underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-dark {{ $barIsSub ? 'text-white focus-visible:ring-white' : 'text-brand-dark' }}">Zmień witrynę</a>
    </div>
@endif
